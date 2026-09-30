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

function ConvertFrom-SecureValue {
    param(
        [Parameter(Mandatory = $true)]
        [Security.SecureString]$SecureValue
    )

    $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($SecureValue)

    try {
        return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)
    }
    finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer)
    }
}

Request-Administrator

$installRoot = "C:\KContest"
$programDataRoot = "C:\ProgramData\KContest"
$stateFile = "$programDataRoot\install-state.json"
$apiRoot = "$installRoot\application\csenveneto\ApiV2"
$backupScript = "$apiRoot\scripts\postgresql\backup-postgresql.ps1"
$pgBin = "C:\Program Files\PostgreSQL\18\bin"

if ($installRoot -ne "C:\KContest") {
    throw "Percorso di installazione non valido."
}

if (-not (Test-Path $stateFile)) {
    throw "File di stato KContest non trovato. Disinstallazione interrotta."
}

$state = Get-Content $stateFile -Raw | ConvertFrom-Json

Write-Host ""
Write-Host "DISINSTALLAZIONE KCONTEST" -ForegroundColor Yellow
Write-Host "Verrà creato un backup finale prima di rimuovere qualsiasi componente."
Write-Host "I backup saranno conservati in $programDataRoot\backups."
Write-Host ""

$confirmation = Read-Host "Scrivi DISINSTALLA per continuare"

if ($confirmation -cne "DISINSTALLA") {
    Write-Host "Operazione annullata."
    exit
}

$postgresService = Get-Service $state.postgresService -ErrorAction SilentlyContinue

if (-not $postgresService) {
    throw "Servizio PostgreSQL non trovato."
}

if ($postgresService.Status -ne "Running") {
    Start-Service $state.postgresService
    (Get-Service $state.postgresService).WaitForStatus(
        [ServiceProcess.ServiceControllerStatus]::Running,
        (New-TimeSpan -Seconds 60)
    )
}

$taskNames = @($state.syncTask, $state.backupTask)

foreach ($taskName in $taskNames) {
    if ($taskName -and (Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue)) {
        Disable-ScheduledTask -TaskName $taskName | Out-Null
        Stop-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    }
}

Stop-Service $state.apacheService -ErrorAction SilentlyContinue

if (-not (Test-Path $backupScript)) {
    throw "Script di backup non trovato. Nessun componente e stato rimosso."
}

Write-Host "Creazione del backup finale..."
$backupLabel = "prima_disinstallazione_" + (Get-Date -Format "yyyyMMdd_HHmmss")

& $backupScript -Type finali -Label $backupLabel

if ($LASTEXITCODE -ne 0) {
    throw "Backup finale fallito. Nessun componente e stato rimosso."
}

Write-Host "Backup finale completato." -ForegroundColor Green

$databaseRemoved = $false
$removeDatabase = Read-Host "Vuoi eliminare anche kcontest_desk e kcontest_app? Scrivi SI"

if ($removeDatabase -ceq "SI") {
    $adminSecurePassword = Read-Host "Password dell'utente PostgreSQL postgres" -AsSecureString
    $adminPassword = ConvertFrom-SecureValue $adminSecurePassword
    $env:PGPASSWORD = $adminPassword

    try {
        $psqlExe = "$pgBin\psql.exe"
        $dropdbExe = "$pgBin\dropdb.exe"

        if (-not (Test-Path $psqlExe) -or -not (Test-Path $dropdbExe)) {
            throw "Strumenti PostgreSQL non trovati."
        }

        & $psqlExe `
            -h 127.0.0.1 -p 5432 -U postgres -d postgres `
            -v ON_ERROR_STOP=1 `
            -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = 'kcontest_desk' AND pid <> pg_backend_pid();"

        if ($LASTEXITCODE -ne 0) {
            throw "Impossibile terminare le connessioni al database."
        }

        & $dropdbExe -h 127.0.0.1 -p 5432 -U postgres --if-exists kcontest_desk

        if ($LASTEXITCODE -ne 0) {
            throw "Eliminazione del database fallita."
        }

        & $psqlExe `
            -h 127.0.0.1 -p 5432 -U postgres -d postgres `
            -v ON_ERROR_STOP=1 `
            -c "DROP ROLE IF EXISTS kcontest_app;"

        if ($LASTEXITCODE -ne 0) {
            throw "Eliminazione del ruolo kcontest_app fallita."
        }

        $databaseRemoved = $true
    }
    finally {
        Remove-Item Env:\PGPASSWORD -ErrorAction SilentlyContinue
        $adminPassword = $null
        $adminSecurePassword = $null
    }
}

foreach ($taskName in $taskNames) {
    if ($taskName -and (Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue)) {
        Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
    }
}

$apacheExe = "$installRoot\runtime\apache\bin\httpd.exe"

if (Test-Path $apacheExe) {
    & $apacheExe -k uninstall -n $state.apacheService
}
elseif (Get-Service $state.apacheService -ErrorAction SilentlyContinue) {
    & sc.exe delete $state.apacheService | Out-Null
}

Get-NetFirewallRule -DisplayName "KContest HTTP 8080" -ErrorAction SilentlyContinue |
    Remove-NetFirewallRule

$desktopPath = [Environment]::GetFolderPath("CommonDesktopDirectory")

@(
    "Avvia KContest.lnk",
    "Arresta KContest.lnk",
    "Disinstalla KContest.lnk",
    "Apri KContest.url"
) | ForEach-Object {
    Remove-Item (Join-Path $desktopPath $_) -Force -ErrorAction SilentlyContinue
}

$uninstallPostgres = $false

if ($state.postgresInstalledByKContest -eq $true -and $databaseRemoved) {
    $answer = Read-Host "PostgreSQL era stato installato da KContest. Vuoi disinstallarlo? Scrivi SI"
    $uninstallPostgres = ($answer -ceq "SI")
}

Set-Location $env:TEMP

if (Test-Path $installRoot) {
    Remove-Item $installRoot -Recurse -Force
}

if ($uninstallPostgres) {
    $postgresUninstaller = "C:\Program Files\PostgreSQL\18\uninstall-postgresql.exe"

    if (Test-Path $postgresUninstaller) {
        Start-Process $postgresUninstaller -Wait
    }
    else {
        Write-Warning "Disinstallatore PostgreSQL non trovato."
    }
}
elseif ($state.postgresInstalledByKContest -eq $true) {
    Stop-Service $state.postgresService -ErrorAction SilentlyContinue
}

Remove-Item $stateFile -Force -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "KContest e stato disinstallato." -ForegroundColor Green
Write-Host "I backup sono conservati in $programDataRoot\backups"

