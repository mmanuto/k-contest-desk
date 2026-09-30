<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Http\Client;
use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\LocatorAwareTrait;

class SyncOutboxCommand extends Command
{
    use LocatorAwareTrait;

    private const MAX_ATTEMPTS = 10;

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $syncOutbox = $this->fetchTable('SyncOutbox');
        $onlineApiUrl = rtrim((string)env('ONLINE_API_URL'), '/') . '/';

        /*
        * Recupera gli eventi rimasti in PROCESSING per più di
        * dieci minuti. Può accadere se PHP o il PC vengono
        * arrestati dopo l'inizio dell'elaborazione.
        */
        $now = FrozenTime::now();
        $processingTimeout = $now->subMinutes(10);

        $recoveredEvents = $syncOutbox->updateAll(
            [
                'status' => 'FAILED',
                'next_attempt_at' => null,
                'last_error' =>
                    'Evento recuperato automaticamente da PROCESSING.',
                'modified' => $now,
            ],
            [
                'status' => 'PROCESSING',
                'modified <=' => $processingTimeout,
            ]
        );

        if ($recoveredEvents > 0) {
            $io->warning(
                sprintf(
                    'Recuperati %d eventi bloccati in PROCESSING.',
                    $recoveredEvents
                )
            );
        }

        if ($onlineApiUrl === '/') {
            $io->err('ONLINE_API_URL non configurato.');

            return static::CODE_ERROR;
        }

        $items = $syncOutbox
            ->find()
            ->where([
                'status IN' => ['PENDING', 'FAILED'],
                'attempts <' => self::MAX_ATTEMPTS,
                'OR' => [
                    'next_attempt_at IS' => null,
                    'next_attempt_at <=' => FrozenTime::now(),
                ],
            ])
            ->orderAsc('created')
            ->limit(20)
            ->all();

        if ($items->isEmpty()) {
            $io->out('Nessun evento da sincronizzare.');

            return static::CODE_SUCCESS;
        }

        $http = new Client([
            'timeout' => 10,
        ]);

        foreach ($items as $item) {
            $item->status = 'PROCESSING';
            $item->attempts++;
            $item->last_error = null;
            $syncOutbox->saveOrFail($item);

            try {
                $payload = $item->payload;

                if (is_string($payload)) {
                    $payload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
                }

                $url = $onlineApiUrl . ltrim($item->endpoint, '/');

                $response = $http->post(
                    $url,
                    json_encode($payload, JSON_THROW_ON_ERROR),
                    [
                        'type' => 'json',
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ],
                    ]
                );

                if (!$response->isOk()) {
                    throw new \RuntimeException(
                        'HTTP ' . $response->getStatusCode() .
                        ': ' . substr($response->getStringBody(), 0, 500)
                    );
                }

                $responseData = $response->getJson();

                if (
                    is_array($responseData) &&
                    isset($responseData['result']['success']) &&
                    $responseData['result']['success'] === false
                ) {
                    throw new \RuntimeException(
                        'Il portale online ha rifiutato la richiesta. Risposta: ' .
                        substr($response->getStringBody(), 0, 1500)
                    );
                }

                $item->status = 'COMPLETED';
                $item->processed_at = FrozenTime::now();
                $item->next_attempt_at = null;
                $item->last_error = null;

                $syncOutbox->saveOrFail($item);

                $io->success(
                    sprintf(
                        'Evento %d sincronizzato.',
                        $item->id
                    )
                );
            } catch (\Throwable $exception) {
                $delayMinutes = min(
                    60,
                    2 ** min($item->attempts - 1, 6)
                );

                $item->status = 'FAILED';
                $item->next_attempt_at = FrozenTime::now()
                    ->addMinutes($delayMinutes);
                $item->last_error = substr(
                    $exception->getMessage(),
                    0,
                    2000
                );

                $syncOutbox->saveOrFail($item);

                $io->warning(
                    sprintf(
                        'Evento %d non sincronizzato: %s',
                        $item->id,
                        $exception->getMessage()
                    )
                );
            }
        }

        return static::CODE_SUCCESS;
    }
}