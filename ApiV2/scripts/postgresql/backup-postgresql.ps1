[CmdletBinding()]
param(
    [ValidateSet('automatici', 'categorie', 'manuali', 'finali')]
    [string]$Type = 'manuali',
    [string]$Label = '',
    [int]$AutomaticRetention = 120,
    [string]$BackupRoot = '',
    [string]$ConfigPath = ''
)

. (Join-Path $PSScriptRoot 'common.ps1')

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
if (-not $ConfigPath) { $ConfigPath = Join-Path $projectRoot 'config\.env' }
Import-KContestEnv -Path $ConfigPath

if (-not $BackupRoot) {
    $BackupRoot = Get-KContestSetting -Name 'KCONTEST_BACKUP_ROOT' -Default 'C:\ProgramData\KContest\backups'
}

$hostName = Get-KContestSetting -Name 'DB_HOST' -Default '127.0.0.1'
$port = Get-KContestSetting -Name 'DB_PORT' -Default '5432'
$database = Get-KContestSetting -Name 'DB_DATABASE' -Default 'kcontest_desk'
$username = Get-KContestSetting -Name 'DB_USERNAME' -Default 'kcontest_app'
$password = Get-KContestSetting -Name 'DB_PASSWORD'
if (-not $password) { throw 'DB_PASSWORD non valorizzata nel file .env.' }

$pgDump = Resolve-PostgresTool -Name 'pg_dump'
$pgRestore = Resolve-PostgresTool -Name 'pg_restore'
$destinationDirectory = Join-Path $BackupRoot $Type
$logPath = Join-Path $BackupRoot 'logs\backup.log'
New-Item -ItemType Directory -Path $destinationDirectory -Force | Out-Null

$safeLabel = ($Label -replace '[^a-zA-Z0-9_-]', '_').Trim('_')
$suffix = if ($safeLabel) { "_$safeLabel" } else { '' }
$timestamp = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'
$fileName = "kcontest_${Type}_${timestamp}${suffix}.backup"
$finalPath = Join-Path $destinationDirectory $fileName
$partialPath = "$finalPath.partial"

Write-KContestLog -LogPath $logPath -Message "Avvio backup $Type del database $database."

try {
    Use-PostgresPassword -Password $password -Action {
        $dumpArguments = @(
            '--host', $hostName,
            '--port', $port,
            '--username', $username,
            '--dbname', $database,
            '--format=custom',
            '--compress=6',
            '--no-owner',
            '--no-privileges',
            '--file', $partialPath
        )
        $output = & $pgDump @dumpArguments 2>&1
        $exitCode = $LASTEXITCODE
        if ($exitCode -ne 0) { throw "pg_dump terminato con codice $exitCode. $output" }

        if (-not (Test-Path -LiteralPath $partialPath)) { throw 'Il file temporaneo non è stato creato.' }
        if ((Get-Item -LiteralPath $partialPath).Length -le 0) { throw 'Il backup creato è vuoto.' }

        $verification = & $pgRestore '--list' $partialPath 2>&1
        if ($LASTEXITCODE -ne 0) { throw "Verifica pg_restore fallita. $verification" }
    }

    Move-Item -LiteralPath $partialPath -Destination $finalPath -Force
    $size = (Get-Item -LiteralPath $finalPath).Length
    Write-KContestLog -LogPath $logPath -Message "Backup completato: $finalPath ($size byte)."

    if ($Type -eq 'automatici' -and $AutomaticRetention -gt 0) {
        $expired = Get-ChildItem -LiteralPath $destinationDirectory -Filter '*.backup' -File |
            Sort-Object LastWriteTime -Descending |
            Select-Object -Skip $AutomaticRetention
        foreach ($file in $expired) {
            Remove-Item -LiteralPath $file.FullName -Force
            Write-KContestLog -LogPath $logPath -Message "Rimosso backup automatico scaduto: $($file.Name)."
        }
    }

    Write-Output $finalPath
    exit 0
} catch {
    if (Test-Path -LiteralPath $partialPath) { Remove-Item -LiteralPath $partialPath -Force }
    Write-KContestLog -LogPath $logPath -Message $_.Exception.Message -Level 'ERROR'
    exit 1
}
