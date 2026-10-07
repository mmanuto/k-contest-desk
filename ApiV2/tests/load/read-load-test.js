'use strict';

const http = require('http');
const https = require('https');
const fs = require('fs');
const path = require('path');

function option(name, fallback) {
  const index = process.argv.indexOf(`--${name}`);
  return index >= 0 && process.argv[index + 1] ? process.argv[index + 1] : fallback;
}

const baseUrl = option('base-url', 'http://localhost/k-contest-desk/ApiV2/').replace(/\/?$/, '/');
const virtualUsers = Number(option('users', '12'));
const durationSeconds = Number(option('duration', '120'));
const intervalMs = Number(option('interval', '2000'));
const timeoutMs = Number(option('timeout', '10000'));

if (!Number.isInteger(virtualUsers) || virtualUsers < 1 || virtualUsers > 100) {
  throw new Error('--users deve essere un intero tra 1 e 100');
}
if (!Number.isFinite(durationSeconds) || durationSeconds < 10) {
  throw new Error('--duration deve essere almeno 10 secondi');
}
if (!Number.isFinite(intervalMs) || intervalMs < 100) {
  throw new Error('--interval deve essere almeno 100 ms');
}

const endpoints = [
  { name: 'totale_iscrizioni', method: 'POST', url: 'competitions/getTotaleIscrizioni', body: {} },
  { name: 'totale_prove', method: 'POST', url: 'athleteInscriptions/getTotaleProve', body: {} },
  { name: 'categorie', method: 'POST', url: 'categorycodes/getCategories', body: {} },
  { name: 'tatami', method: 'POST', url: 'users/getTatami', body: {} },
  { name: 'stato_tatami', method: 'GET', url: 'tatamiAssignments/getTatamiStatus' },
  { name: 'lista_atleti', method: 'POST', url: 'athleteInscriptions/getAthleteList', body: {} },
];

const measurements = [];
const startedAt = Date.now();
const stopAt = startedAt + durationSeconds * 1000;

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

function request(endpoint, userId) {
  return new Promise(resolve => {
    const target = new URL(endpoint.url, baseUrl);
    const transport = target.protocol === 'https:' ? https : http;
    const payload = endpoint.body === undefined ? null : JSON.stringify(endpoint.body);
    const requestStarted = process.hrtime.bigint();

    const req = transport.request(target, {
      method: endpoint.method,
      headers: {
        Accept: 'application/json',
        ...(payload ? {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(payload),
        } : {}),
      },
      timeout: timeoutMs,
    }, response => {
      let bytes = 0;
      response.on('data', chunk => { bytes += chunk.length; });
      response.on('end', () => {
        const elapsedMs = Number(process.hrtime.bigint() - requestStarted) / 1e6;
        measurements.push({
          endpoint: endpoint.name,
          userId,
          status: response.statusCode,
          elapsedMs,
          bytes,
          ok: response.statusCode >= 200 && response.statusCode < 300,
        });
        resolve();
      });
    });

    req.on('timeout', () => req.destroy(new Error(`timeout dopo ${timeoutMs} ms`)));
    req.on('error', error => {
      const elapsedMs = Number(process.hrtime.bigint() - requestStarted) / 1e6;
      measurements.push({
        endpoint: endpoint.name,
        userId,
        status: 0,
        elapsedMs,
        bytes: 0,
        ok: false,
        error: error.message,
      });
      resolve();
    });

    if (payload) req.write(payload);
    req.end();
  });
}

async function virtualUser(userId) {
  let index = userId % endpoints.length;
  await sleep(Math.min(userId * 75, 1000));
  while (Date.now() < stopAt) {
    const endpoint = endpoints[index % endpoints.length];
    await request(endpoint, userId);
    index++;
    const jitter = Math.floor(Math.random() * Math.min(250, intervalMs / 4));
    await sleep(intervalMs + jitter);
  }
}

function percentile(values, percent) {
  if (!values.length) return 0;
  const sorted = [...values].sort((a, b) => a - b);
  const index = Math.min(sorted.length - 1, Math.ceil((percent / 100) * sorted.length) - 1);
  return sorted[Math.max(0, index)];
}

function rounded(value) {
  return Math.round(value * 100) / 100;
}

function summarize(records) {
  const latencies = records.map(record => record.elapsedMs);
  const errors = records.filter(record => !record.ok);
  return {
    requests: records.length,
    errors: errors.length,
    errorRatePercent: records.length ? rounded((errors.length / records.length) * 100) : 0,
    averageMs: records.length ? rounded(latencies.reduce((sum, value) => sum + value, 0) / records.length) : 0,
    p50Ms: rounded(percentile(latencies, 50)),
    p95Ms: rounded(percentile(latencies, 95)),
    p99Ms: rounded(percentile(latencies, 99)),
    maximumMs: rounded(Math.max(0, ...latencies)),
  };
}

async function main() {
  console.log('K-Contest - test multi-postazione di sola lettura');
  console.log(`API: ${baseUrl}`);
  console.log(`Postazioni: ${virtualUsers}`);
  console.log(`Durata: ${durationSeconds}s - intervallo medio: ${intervalMs}ms`);
  console.log('Nessun endpoint di scrittura verrà chiamato.\n');

  await Promise.all(Array.from({ length: virtualUsers }, (_, index) => virtualUser(index + 1)));

  const finishedAt = Date.now();
  const byEndpoint = {};
  for (const endpoint of endpoints) {
    byEndpoint[endpoint.name] = summarize(measurements.filter(item => item.endpoint === endpoint.name));
  }

  const summary = summarize(measurements);
  summary.elapsedSeconds = rounded((finishedAt - startedAt) / 1000);
  summary.requestsPerSecond = summary.elapsedSeconds
    ? rounded(summary.requests / summary.elapsedSeconds)
    : 0;

  const report = {
    generatedAt: new Date().toISOString(),
    configuration: { baseUrl, virtualUsers, durationSeconds, intervalMs, timeoutMs },
    summary,
    byEndpoint,
    errors: measurements.filter(item => !item.ok).slice(0, 100),
  };

  const reportsDirectory = path.join(__dirname, 'reports');
  fs.mkdirSync(reportsDirectory, { recursive: true });
  const stamp = new Date().toISOString().replace(/[:.]/g, '-');
  const reportPath = path.join(reportsDirectory, `read-load-test-${stamp}.json`);
  fs.writeFileSync(reportPath, JSON.stringify(report, null, 2), 'utf8');

  console.table({ totale: summary, ...byEndpoint });
  console.log(`\nReport: ${reportPath}`);

  const failed = summary.errors > 0 || summary.p95Ms > 1000;
  if (failed) {
    console.error('ESITO: da analizzare (errori presenti o p95 superiore a 1000 ms).');
    process.exitCode = 1;
  } else {
    console.log('ESITO: superato (nessun errore e p95 entro 1000 ms).');
  }
}

main().catch(error => {
  console.error(`ERRORE: ${error.stack || error.message}`);
  process.exitCode = 1;
});
