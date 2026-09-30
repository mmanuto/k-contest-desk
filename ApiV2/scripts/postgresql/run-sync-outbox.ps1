$ErrorActionPreference = "Stop"

$projectRoot = Resolve-Path (
    Join-Path $PSScriptRoot "..\.."
)

$phpExe = "C:\xampp\php\php.exe"
$cakeScript = Join-Path $projectRoot "bin\cake.php"
$logDirectory = Join-Path $projectRoot "logs"
$logFile = Join-Path $logDirectory "sync-outbox.log"

if (-not (Test-Path $phpExe)) {
    throw "PHP non trovato: $phpExe"
}

if (-not (Test-Path $cakeScript)) {
    throw "CakePHP non trovato: $cakeScript"
}

if (-not (Test-Path $logDirectory)) {
    New-Item `
        -ItemType Directory `
        -Path $logDirectory `
        -Force | Out-Null
}

$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

Add-Content `
    -Path $logFile `
    -Value "[$timestamp] Avvio sincronizzazione."

& $phpExe $cakeScript sync_outbox *>> $logFile

$exitCode = $LASTEXITCODE
$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

Add-Content `
    -Path $logFile `
    -Value "[$timestamp] Fine sincronizzazione, codice $exitCode."

exit $exitCode