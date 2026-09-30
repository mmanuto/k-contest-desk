# Backup e ripristino PostgreSQL

Gli script leggono `config/.env`; la password non è duplicata nei file PowerShell.
I backup sono in formato custom PostgreSQL e vengono verificati con `pg_restore --list`
prima della pubblicazione del file definitivo.

## Backup manuale

Aprire PowerShell nella cartella `ApiV2`:

```powershell
.\scripts\postgresql\backup-postgresql.ps1 -Type manuali -Label primo_test
```

Il percorso predefinito è `C:\ProgramData\KContest\backups`. È configurabile
nel `.env` tramite `KCONTEST_BACKUP_ROOT`.

Tipologie disponibili:

- `automatici`: conserva automaticamente gli ultimi 30 file;
- `categorie`: usare `-Label` con l'identificativo della categoria;
- `manuali`;
- `finali`.

## Backup automatico ogni cinque minuti

Dopo aver verificato manualmente un backup, aprire PowerShell come amministratore:

```powershell
.\scripts\postgresql\install-automatic-backup-task.ps1 -Minutes 5
```

Provare subito l'attività:

```powershell
schtasks.exe /Run /TN "KContest-PostgreSQL-AutomaticBackup"
```

Controllare `C:\ProgramData\KContest\backups\logs\backup.log` e la cartella
`automatici`.

## Prova di ripristino

Creare una sola volta il database di test, entrando in `psql` come `postgres`:

```sql
CREATE DATABASE kcontest_restore_test OWNER kcontest_app ENCODING 'UTF8';
```

Poi eseguire:

```powershell
.\scripts\postgresql\restore-postgresql.ps1 `
  -BackupFile "C:\ProgramData\KContest\backups\manuali\NOME_FILE.backup" `
  -TargetDatabase kcontest_restore_test `
  -ResetTarget
```

`-ResetTarget` è obbligatorio perché il ripristino cancella e ricrea lo schema
`public` del database di test. Lo script blocca il database operativo
`kcontest_desk` salvo autorizzazione esplicita.

Il test è superato quando vengono ripristinate almeno 23 tabelle e il log riporta
`Ripristino verificato con successo`.
