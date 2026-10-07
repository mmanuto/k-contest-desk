# K-Contest Desktop - prototipo PostgreSQL

Questa è una copia di lavoro del backend `ApiV2` predisposta per il primo
collaudo con PostgreSQL. Il progetto originale caricato non è stato modificato.

Partire da `docs/POSTGRESQL-MIGRATION.md`.

File principali:

- `database/postgresql/verify-environment.bat`: verifica PHP e driver;
- `database/postgresql/schema.sql`: crea le 23 tabelle PostgreSQL;
- `database/postgresql/check-data-integrity.sql`: controlla i record orfani;
- `database/postgresql/migrate-data.php`: importa e verifica i dati MariaDB;
- `scripts/postgresql/backup-postgresql.ps1`: crea e verifica i backup;
- `scripts/postgresql/restore-postgresql.ps1`: prova il ripristino in sicurezza;
- `docs/BACKUP-RESTORE.md`: procedura operativa completa;
- `tests/load/read-load-test.js`: simulazione non distruttiva di 12 postazioni;
- `config/.env.postgresql.example`: variabili richieste, senza credenziali reali.

Questa versione è un primo prototipo di migrazione, non ancora un pacchetto da
usare durante una gara reale. Prima servono importazione dati, test funzionali,
test concorrenti, backup e procedura di ripristino.
