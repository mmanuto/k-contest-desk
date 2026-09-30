[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)][string]$BackupFile,
    [string]$TargetDatabase = 'kcontest_restore_test',
    [switch]$ResetTarget,
    [switch]$AllowProductionRestore,
    [string]$BackupRoot = '',
    [string]$ConfigPath = ''
)

. (Join-Path $PSScriptRoot 'common.ps1')

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
if (-not $ConfigPath) { $ConfigPath = Join-Path $projectRoot 'config\.env' }
Import-KContestEnv -Path $ConfigPath

if ($TargetDatabase -notmatch '^[a-zA-Z][a-zA-Z0-9_]{0,62}$') {
    throw 'Nome del database di destinazione non valido.'
}

$productionDatabase = Get-KContestSetting -Name 'DB_DATABASE' -Default 'kcontest_desk'
if ($TargetDatabase -eq $productionDatabase -and -not $AllowProductionRestore) {
    throw 'Ripristino sul database operativo bloccato. Usare un database di test.'
}
if (-not $ResetTarget) {
    throw 'Specificare -ResetTarget per confermare la cancellazione dello schema del database di test.'
}
if (-not (Test-Path -LiteralPath $BackupFile)) { throw "Backup non trovato: $BackupFile" }

if (-not $BackupRoot) {
    $BackupRoot = Get-KContestSetting -Name 'KCONTEST_BACKUP_ROOT' -Default 'C:\ProgramData\KContest\backups'
}

$hostName = Get-KContestSetting -Name 'DB_HOST' -Default '127.0.0.1'
$port = Get-KContestSetting -Name 'DB_PORT' -Default '5432'
$username = Get-KContestSetting -Name 'DB_USERNAME' -Default 'kcontest_app'
$password = Get-KContestSetting -Name 'DB_PASSWORD'
if (-not $password) { throw 'DB_PASSWORD non valorizzata nel file .env.' }
if ($username -notmatch '^[a-zA-Z][a-zA-Z0-9_]{0,62}$') { throw 'DB_USERNAME non valido.' }

$pgRestore = Resolve-PostgresTool -Name 'pg_restore'
$psql = Resolve-PostgresTool -Name 'psql'
$logPath = Join-Path $BackupRoot 'logs\restore.log'

Write-KContestLog -LogPath $logPath -Message "Avvio verifica ripristino di $BackupFile su $TargetDatabase."

try {
    Use-PostgresPassword -Password $password -Action {
        $listOutput = & $pgRestore '--list' $BackupFile 2>&1
        if ($LASTEXITCODE -ne 0) { throw "Backup non valido. $listOutput" }

        $connectionArguments = @(
            '--host', $hostName, '--port', $port, '--username', $username,
            '--dbname', $TargetDatabase, '--no-password', '--tuples-only',
            '--command', 'SELECT 1;'
        )
        $connectionTest = & $psql @connectionArguments 2>&1
        if ($LASTEXITCODE -ne 0) {
            throw "Database di test non disponibile. Crearlo prima del ripristino. $connectionTest"
        }

        $resetSql = "DROP SCHEMA public CASCADE; CREATE SCHEMA public AUTHORIZATION $username;"
        $resetArguments = @(
            '--host', $hostName, '--port', $port, '--username', $username,
            '--dbname', $TargetDatabase, '--no-password', '--set', 'ON_ERROR_STOP=1',
            '--command', $resetSql
        )
        $resetOutput = & $psql @resetArguments 2>&1
        if ($LASTEXITCODE -ne 0) { throw "Reset del database di test fallito. $resetOutput" }

        $restoreArguments = @(
            '--host', $hostName, '--port', $port, '--username', $username,
            '--dbname', $TargetDatabase, '--no-owner', '--no-privileges',
            '--exit-on-error', $BackupFile
        )
        $restoreOutput = & $pgRestore @restoreArguments 2>&1
        if ($LASTEXITCODE -ne 0) { throw "Ripristino fallito. $restoreOutput" }

        $countArguments = @(
            '--host', $hostName, '--port', $port, '--username', $username,
            '--dbname', $TargetDatabase, '--no-password', '--tuples-only', '--no-align',
            '--command', "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE';"
        )
        $tableCountOutput = & $psql @countArguments 2>&1
        if ($LASTEXITCODE -ne 0) { throw 'Impossibile verificare le tabelle ripristinate.' }
        $tableCount = ($tableCountOutput | Select-Object -Last 1).ToString().Trim()
        if ([int]$tableCount -lt 23) { throw "Ripristino incompleto: trovate $tableCount tabelle, attese almeno 23." }
    }

    Write-KContestLog -LogPath $logPath -Message "Ripristino verificato con successo su $TargetDatabase."
    exit 0
} catch {
    Write-KContestLog -LogPath $logPath -Message $_.Exception.Message -Level 'ERROR'
    exit 1
}
