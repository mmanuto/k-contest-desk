# K-Contest Desktop - prima migrazione PostgreSQL

Questa copia introduce il driver PostgreSQL senza modificare il frontend Angular.

## Prerequisiti

- PHP 8.2 con estensioni `PDO`, `pdo_pgsql` e `pgsql`.
- PostgreSQL installato localmente.
- Dipendenze Composer già installate oppure installabili tramite `composer install`.

## Creazione iniziale del database

Eseguire come amministratore PostgreSQL, sostituendo la password:

```sql
CREATE ROLE kcontest_app LOGIN PASSWORD 'PASSWORD_SICURA';
CREATE DATABASE kcontest_desk OWNER kcontest_app ENCODING 'UTF8';
```

Applicare quindi lo schema:

```bat
psql -h 127.0.0.1 -U kcontest_app -d kcontest_desk -f database\postgresql\schema.sql
```

Impostare le variabili `DB_*` usando `config/.env.postgresql.example` come riferimento.
Non salvare la password reale nel repository.

## Modifiche già effettuate

- datasource CakePHP spostato da `Mysql` a `Postgres`;
- credenziali lette da variabili d'ambiente;
- sostituzione di `SET FOREIGN_KEY_CHECKS` con `TRUNCATE ... RESTART IDENTITY CASCADE`;
- parametrizzazione delle query native in `AthletesController`;
- schema PostgreSQL iniziale per le 23 tabelle;
- indici iniziali per gli accessi operativi più frequenti;
- trigger PostgreSQL per aggiornare `modified_date` come faceva MariaDB.

## Scelte conservative

I flag `tinyint` restano `smallint` nella prima migrazione. Alcuni campi, come
`scores.win`, usano anche `-1` e non possono diventare booleani senza modificare
la logica applicativa. `athletes.data_nascita` resta temporaneamente testo perché
i dati presenti usano formati non uniformi.

Le foreign key non vengono ancora applicate: il dump MariaDB non ne contiene e
prima bisogna controllare eventuali record orfani. Saranno aggiunte dopo la
validazione dell'importazione.

## Primo collaudo

1. Verificare lo schema con `psql -d kcontest_desk -c "\\dt"` e provare un endpoint semplice.
2. Importare una gara tramite la funzione esistente.
3. Verificare login, categorie e assegnazione tatami.
4. Completare una categoria campione, compresi punteggi e classifica.
5. Eseguire una simulazione con più browser solo dopo il test verticale.

## Importazione dei dati MariaDB

Prima dell'importazione avviare MySQL dal pannello XAMPP e verificare nel file
`config/.env` le variabili `MYSQL_SOURCE_*`. Il primo comando è una simulazione
che si limita a mostrare il numero di record presenti:

```bat
php database\postgresql\migrate-data.php
```

Se i conteggi sono corretti, eseguire la migrazione:

```bat
php database\postgresql\migrate-data.php --execute
```

La modalità `--execute` svuota le 23 tabelle PostgreSQL, importa tutti i record,
riallinea le sequenze e confronta i conteggi. Tutto avviene in una transazione:
in caso di errore PostgreSQL esegue il rollback e conserva lo stato precedente.

## Ancora da completare

- conversione e importazione controllata dei dati storici MariaDB;
- verifica delle sequenze dopo l'importazione di ID espliciti;
- controllo dei record orfani e introduzione delle foreign key;
- test automatici di concorrenza;
- centralizzazione della sincronizzazione online;
- servizio Windows, backup e installer finale.
