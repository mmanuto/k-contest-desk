$ErrorActionPreference = "Stop"

function Request-Administrator {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)

    if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        Start-Process powershell.exe `
            -Verb RunAs `
            -ArgumentList "-NoProfile -ExecutionPolicy Bypass -File `"$PSCommandPath`""
        exit
    }
}

Request-Administrator

$stateFile = "C:\ProgramData\KContest\install-state.json"
$apiRoot = "C:\KContest\application\csenveneto\ApiV2"
$backupScript = "$apiRoot\scripts\postgresql\backup-postgresql.ps1"

if (-not (Test-Path $stateFile)) {
    throw "File di stato KContest non trovato: $stateFile"
}

$state = Get-Content $stateFile -Raw | ConvertFrom-Json
$taskNames = @($state.syncTask, $state.backupTask)

Write-Host "Disattivazione backup e sincronizzazione..."

foreach ($taskName in $taskNames) {
    if (-not $taskName) {
        continue
    }

    if (Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue) {
        Disable-ScheduledTask -TaskName $taskName | Out-Null
        Stop-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    }
}

Write-Host "Arresto del sito per bloccare nuove scritture..."
Stop-Service $state.apacheService -ErrorAction SilentlyContinue

try {
    if (-not (Test-Path $backupScript)) {
        throw "Script di backup non trovato: $backupScript"
    }

    Write-Host "Creazione del backup finale..."
    $label = "chiusura_gara_" + (Get-Date -Format "yyyyMMdd_HHmmss")

    & $backupScript -Type finali -Label $label

    if ($LASTEXITCODE -ne 0) {
        throw "Il backup finale e terminato con codice $LASTEXITCODE."
    }
}
catch {
    Write-Warning "Backup finale fallito. KContest non verra spento completamente."

    Start-Service $state.apacheService -ErrorAction SilentlyContinue

    foreach ($taskName in $taskNames) {
        if ($taskName -and (Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue)) {
            Enable-ScheduledTask -TaskName $taskName | Out-Null
        }
    }

    throw
}

if ($state.postgresInstalledByKContest -eq $true) {
    Write-Host "Arresto PostgreSQL..."
    Stop-Service $state.postgresService -ErrorAction SilentlyContinue
}
else {
    Write-Host "PostgreSQL era gia presente: il servizio viene lasciato attivo."
}

Write-Host ""
Write-Host "KContest arrestato correttamente." -ForegroundColor Green
Write-Host "Backup finale salvato in C:\ProgramData\KContest\backups\finali"

