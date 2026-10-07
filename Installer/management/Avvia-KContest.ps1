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

if (-not (Test-Path $stateFile)) {
    throw "File di stato KContest non trovato: $stateFile"
}

$state = Get-Content $stateFile -Raw | ConvertFrom-Json
$postgresServiceName = $state.postgresService
$apacheServiceName = $state.apacheService
$taskNames = @($state.syncTask, $state.backupTask)

if (-not $postgresServiceName -or -not $apacheServiceName) {
    throw "Il file di stato KContest non contiene i nomi dei servizi."
}

Write-Host "Avvio PostgreSQL..."
Start-Service $postgresServiceName
(Get-Service $postgresServiceName).WaitForStatus(
    [ServiceProcess.ServiceControllerStatus]::Running,
    (New-TimeSpan -Seconds 60)
)

Write-Host "Avvio Apache..."
Start-Service $apacheServiceName
(Get-Service $apacheServiceName).WaitForStatus(
    [ServiceProcess.ServiceControllerStatus]::Running,
    (New-TimeSpan -Seconds 30)
)

Write-Host "Attivazione backup e sincronizzazione..."

foreach ($taskName in $taskNames) {
    if (-not $taskName) {
        continue
    }

    $task = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue

    if (-not $task) {
        throw "Attivita pianificata non trovata: $taskName"
    }

    Enable-ScheduledTask -TaskName $taskName | Out-Null
    Start-ScheduledTask -TaskName $taskName
}

$applicationUrl = "http://localhost:8080/csenveneto/"
$available = $false

for ($attempt = 1; $attempt -le 15; $attempt++) {
    try {
        Invoke-WebRequest `
            -Uri $applicationUrl `
            -UseBasicParsing `
            -TimeoutSec 3 | Out-Null

        $available = $true
        break
    }
    catch {
        Start-Sleep -Seconds 2
    }
}

if (-not $available) {
    throw "I servizi sono avviati, ma KContest non risponde su $applicationUrl"
}

Write-Host ""
Write-Host "KContest avviato correttamente." -ForegroundColor Green
Start-Process $applicationUrl

