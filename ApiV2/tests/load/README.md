# Test multi-postazione

Il primo test usa esclusivamente endpoint di lettura e non modifica database,
risultati, categorie o tabelloni. Richiede Node.js, già usato dal frontend Angular.

## Verifica preliminare

```powershell
node --version
```

Aprire l'applicazione e verificare che l'API risponda, quindi dalla cartella
`ApiV2` eseguire il test breve:

```powershell
node .\tests\load\read-load-test.js `
  --base-url http://localhost/k-contest-desk/ApiV2/ `
  --users 12 `
  --duration 120 `
  --interval 2000
```

Il test simula 12 postazioni, ciascuna con una richiesta ogni circa due secondi.
È volutamente più intenso del polling ordinario dell'applicazione (20-50 secondi).

Il report JSON viene salvato in `tests/load/reports`. Il test iniziale è superato
se non ci sono errori HTTP e il 95° percentile resta sotto 1000 ms. In seguito
l'obiettivo verrà ristretto a 300 ms per le letture ordinarie dopo aver individuato
gli endpoint più pesanti.

Non utilizzare ancora endpoint di scrittura: saranno provati separatamente sul
database `kcontest_restore_test` con dati e categorie dedicati.
