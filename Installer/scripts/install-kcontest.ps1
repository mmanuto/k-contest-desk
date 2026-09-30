# Installer completo KContest per Windows
# Eseguire esclusivamente da una console con privilegi amministrativi.

#Requires -RunAsAdministrator

[CmdletBinding()]
param()

$ErrorActionPreference = "Stop"

$packageRoot = Split-Path $PSScriptRoot -Parent
$installRoot = "C:\KContest"
$applicationRoot = "$installRoot\application\csenveneto"
$apiRoot = "$applicationRoot\ApiV2"
$managementRoot = "$installRoot\management"
$programDataRoot = "C:\ProgramData\KContest"

$apacheServiceName = "KContest-Apache"
$apacheExe = "$installRoot\runtime\apache\bin\httpd.exe"

$postgresMajorVersion = "18"
$postgresServiceExpectedName = "postgresql-x64-18"
$postgresPrefix = "C:\Program Files\PostgreSQL\18"
$pgBin = "$postgresPrefix\bin"
$psqlExe = "$pgBin\psql.exe"
$createdbExe = "$pgBin\createdb.exe"
$pgRestoreExe = "$pgBin\pg_restore.exe"

$postgresInstaller = "$packageRoot\prerequisites\postgresql-18-windows-x64.exe"
$backupFile = "$packageRoot\database\kcontest-base.backup"
$envTemplate = "$packageRoot\configuration\.env.template"

$dbHost = "127.0.0.1"
$dbPort = "5432"
$dbAdmin = "postgres"
$dbUser = "kcontest_app"
$dbName = "kcontest_desk"

$syncTaskName = "KContest-SyncOutbox"
$backupTaskName = "KContest-PostgreSQL-AutomaticBackup"
$firewallRuleName = "KContest HTTP 8080"

function Copy-Directory {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Source,

        [Parameter(Mandatory = $true)]
        [string]$Destination
    )

    if (-not (Test-Path $Source)) {
        throw "Cartella sorgente non trovata: $Source"
    }

    & robocopy.exe $Source $Destination /E /R:2 /W:2 /NFL /NDL /NJH /NJS
    $robocopyExitCode = $LASTEXITCODE

    if ($robocopyExitCode -ge 8) {
        throw "Errore durante la copia da $Source a $Destination. Codice robocopy: $robocopyExitCode"
    }
}

function New-RandomValue {
    param(
        [int]$Bytes = 32
    )

    $buffer = New-Object byte[] $Bytes
    $generator = [System.Security.Cryptography.RandomNumberGenerator]::Create()

    try {
        $generator.GetBytes($buffer)
        return [Convert]::ToBase64String($buffer)
    }
    finally {
        $generator.Dispose()
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

function Write-Utf8WithoutBom {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Path,

        [Parameter(Mandatory = $true)]
        [string]$Content
    )

    $encoding = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($Path, $Content, $encoding)
}

function New-DesktopShortcut {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Name,

        [Parameter(Mandatory = $true)]
        [string]$ScriptPath
    )

    $desktopPath = [Environment]::GetFolderPath("CommonDesktopDirectory")
    $shortcutPath = Join-Path $desktopPath "$Name.lnk"
    $powershellExe = "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe"

    $shell = New-Object -ComObject WScript.Shell
    $shortcut = $shell.CreateShortcut($shortcutPath)
    $shortcut.TargetPath = $powershellExe
    $shortcut.Arguments = "-NoProfile -ExecutionPolicy Bypass -File `"$ScriptPath`""
    $shortcut.WorkingDirectory = Split-Path $ScriptPath -Parent
    $shortcut.IconLocation = "$installRoot\runtime\apache\bin\httpd.exe,0"
    $shortcut.Save()
}

Write-Host ""
Write-Host "=== Installazione KContest ===" -ForegroundColor Cyan
Write-Host ""

$requiredFiles = @(
    "$packageRoot\runtime\apache\bin\httpd.exe",
    "$packageRoot\runtime\php\php.exe",
    "$packageRoot\application\csenveneto\index.html",
    "$packageRoot\application\csenveneto\ApiV2\bin\cake.php",
    "$packageRoot\application\csenveneto\ApiV2\scripts\postgresql\backup-postgresql.ps1",
    "$packageRoot\application\csenveneto\ApiV2\scripts\postgresql\run-sync-outbox.ps1",
    "$packageRoot\management\Avvia-KContest.ps1",
    "$packageRoot\management\Arresta-KContest.ps1",
    "$packageRoot\management\Disinstalla-KContest.ps1",
    $backupFile,
    $envTemplate,
    $postgresInstaller
)

foreach ($file in $requiredFiles) {
    if (-not (Test-Path $file)) {
        throw "File richiesto non trovato: $file"
    }
}

if (Test-Path $installRoot) {
    throw "La cartella $installRoot esiste gia. L'installer e utilizzabile solamente per una nuova installazione."
}

if (Get-Service -Name $apacheServiceName -ErrorAction SilentlyContinue) {
    throw "Il servizio $apacheServiceName esiste gia. Rimuovere la precedente installazione prima di continuare."
}

$adminSecurePassword = Read-Host `
    "Password amministrativa PostgreSQL" `
    -AsSecureString

$adminPassword = ConvertFrom-SecureValue $adminSecurePassword

if ([string]::IsNullOrWhiteSpace($adminPassword)) {
    throw "La password PostgreSQL non puo essere vuota."
}

if ($adminPassword.Contains('"')) {
    throw "Per compatibilita con l'installer PostgreSQL, la password non puo contenere doppi apici."
}

$appPassword = New-RandomValue 24
$securitySalt = New-RandomValue 48
$postgresInstalledByKContest = $false
$postgresService = $null

try {
    $postgresService = Get-Service |
        Where-Object { $_.Name -like "postgresql-x64-$postgresMajorVersion*" } |
        Select-Object -First 1

    if (-not $postgresService) {
        Write-Host "PostgreSQL 18 non presente: avvio installazione..."

        $postgresArguments = @(
            "--mode", "unattended",
            "--unattendedmodeui", "minimal",
            "--superpassword", "`"$adminPassword`"",
            "--servicepassword", "`"$adminPassword`"",
            "--servicename", $postgresServiceExpectedName,
            "--serverport", $dbPort,
            "--prefix", "`"$postgresPrefix`"",
            "--datadir", "`"$postgresPrefix\data`"",
            "--disable-components", "pgAdmin,stackbuilder"
        )

        $postgresProcess = Start-Process `
            -FilePath $postgresInstaller `
            -ArgumentList $postgresArguments `
            -Wait `
            -PassThru

        if ($postgresProcess.ExitCode -ne 0) {
            throw "Installazione PostgreSQL fallita. Codice: $($postgresProcess.ExitCode)"
        }

        $postgresInstalledByKContest = $true
        $postgresService = Get-Service `
            -Name $postgresServiceExpectedName `
            -ErrorAction SilentlyContinue

        if (-not $postgresService) {
            throw "PostgreSQL risulta installato, ma il servizio $postgresServiceExpectedName non e stato trovato."
        }
    }
    else {
        Write-Host "PostgreSQL 18 gia installato: verra riutilizzato."
    }

    if ($postgresService.Status -ne "Running") {
        Start-Service $postgresService.Name
    }

    (Get-Service $postgresService.Name).WaitForStatus(
        [ServiceProcess.ServiceControllerStatus]::Running,
        (New-TimeSpan -Seconds 60)
    )

    if ($postgresInstalledByKContest) {
        Set-Service `
            -Name $postgresService.Name `
            -StartupType Manual
    }

    $postgresExecutables = @(
        $psqlExe,
        $createdbExe,
        $pgRestoreExe
    )

    foreach ($postgresExecutable in $postgresExecutables) {
        if (-not (Test-Path $postgresExecutable)) {
            throw "Eseguibile PostgreSQL non trovato: $postgresExecutable"
        }
    }

    $env:PGPASSWORD = $adminPassword

    $connectionTest = & $psqlExe `
        -h $dbHost `
        -p $dbPort `
        -U $dbAdmin `
        -d postgres `
        -tAc "SELECT 1;"

    if ($LASTEXITCODE -ne 0 -or ([string]$connectionTest).Trim() -ne "1") {
        throw "Impossibile collegarsi a PostgreSQL con l'utente $dbAdmin."
    }

    $databaseExists = & $psqlExe `
        -h $dbHost `
        -p $dbPort `
        -U $dbAdmin `
        -d postgres `
        -tAc "SELECT 1 FROM pg_database WHERE datname = '$dbName';"

    if ($LASTEXITCODE -ne 0) {
        throw "Impossibile verificare il database $dbName."
    }

    if (([string]$databaseExists).Trim() -eq "1") {
        throw "Il database $dbName esiste gia. Installazione interrotta per proteggere i dati."
    }

    Write-Host "Copia dei file applicativi..."

    New-Item `
        -Path $installRoot `
        -ItemType Directory `
        -Force | Out-Null

    Copy-Directory `
        "$packageRoot\runtime" `
        "$installRoot\runtime"

    Copy-Directory `
        "$packageRoot\application" `
        "$installRoot\application"

    Copy-Directory `
        "$packageRoot\management" `
        $managementRoot

    $runtimeDirectories = @(
        "$apiRoot\logs",
        "$apiRoot\tmp",
        "$apiRoot\tmp\cache",
        "$apiRoot\tmp\cache\models",
        "$apiRoot\tmp\cache\persistent",
        "$apiRoot\tmp\sessions",
        "$installRoot\runtime\tmp",
        "$programDataRoot\backups\automatici",
        "$programDataRoot\backups\manuali",
        "$programDataRoot\backups\finali",
        "$programDataRoot\backups\categorie",
        "$programDataRoot\backups\logs"
    )

    foreach ($directory in $runtimeDirectories) {
        New-Item `
            -Path $directory `
            -ItemType Directory `
            -Force | Out-Null
    }

    Write-Host "Creazione configurazione .env..."

    $envContent = Get-Content $envTemplate -Raw
    $envContent = $envContent.Replace("__DB_PASSWORD__", $appPassword)
    $envContent = $envContent.Replace("__SECURITY_SALT__", $securitySalt)

    if ($envContent.Contains("__DB_PASSWORD__") -or $envContent.Contains("__SECURITY_SALT__")) {
        throw "Impossibile compilare correttamente il modello .env."
    }

    Write-Utf8WithoutBom `
        -Path "$apiRoot\config\.env" `
        -Content $envContent

    Write-Host "Configurazione ruolo PostgreSQL..."

    $escapedPassword = $appPassword.Replace("'", "''")

    $roleExists = & $psqlExe `
        -h $dbHost `
        -p $dbPort `
        -U $dbAdmin `
        -d postgres `
        -tAc "SELECT 1 FROM pg_roles WHERE rolname = '$dbUser';"

    if ($LASTEXITCODE -ne 0) {
        throw "Impossibile verificare il ruolo $dbUser."
    }

    if (([string]$roleExists).Trim() -eq "1") {
        & $psqlExe `
            -h $dbHost `
            -p $dbPort `
            -U $dbAdmin `
            -d postgres `
            -v ON_ERROR_STOP=1 `
            -c "ALTER ROLE $dbUser WITH LOGIN PASSWORD '$escapedPassword';"
    }
    else {
        & $psqlExe `
            -h $dbHost `
            -p $dbPort `
            -U $dbAdmin `
            -d postgres `
            -v ON_ERROR_STOP=1 `
            -c "CREATE ROLE $dbUser LOGIN PASSWORD '$escapedPassword';"
    }

    if ($LASTEXITCODE -ne 0) {
        throw "Configurazione del ruolo $dbUser fallita."
    }

    Write-Host "Creazione database $dbName..."

    & $createdbExe `
        -h $dbHost `
        -p $dbPort `
        -U $dbAdmin `
        -O $dbUser `
        -E UTF8 `
        $dbName

    if ($LASTEXITCODE -ne 0) {
        throw "Creazione del database $dbName fallita."
    }

    Write-Host "Ripristino database iniziale..."

    & $pgRestoreExe `
        -h $dbHost `
        -p $dbPort `
        -U $dbAdmin `
        -d $dbName `
        --role=$dbUser `
        --no-owner `
        --no-privileges `
        --exit-on-error `
        $backupFile

    if ($LASTEXITCODE -ne 0) {
        throw "Ripristino del database fallito."
    }

    $syncOutboxExists = & $psqlExe `
        -h $dbHost `
        -p $dbPort `
        -U $dbAdmin `
        -d $dbName `
        -tAc "SELECT to_regclass('public.sync_outbox');"

    if ($LASTEXITCODE -ne 0 -or ([string]$syncOutboxExists).Trim() -ne "sync_outbox") {
        throw "La tabella sync_outbox non e presente dopo il ripristino."
    }

    Write-Host "Installazione servizio Apache..."

    & $apacheExe -t

    if ($LASTEXITCODE -ne 0) {
        throw "Configurazione Apache non valida."
    }

    & $apacheExe `
        -k install `
        -n $apacheServiceName

    if ($LASTEXITCODE -ne 0) {
        throw "Installazione del servizio Apache fallita."
    }

    Set-Service `
        -Name $apacheServiceName `
        -StartupType Manual

    Start-Service -Name $apacheServiceName

    (Get-Service $apacheServiceName).WaitForStatus(
        [ServiceProcess.ServiceControllerStatus]::Running,
        (New-TimeSpan -Seconds 30)
    )

    Write-Host "Creazione attivita pianificate..."

    $powershellExe = "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe"

    $taskPrincipal = New-ScheduledTaskPrincipal `
        -UserId "SYSTEM" `
        -LogonType ServiceAccount `
        -RunLevel Highest

    $taskSettings = New-ScheduledTaskSettingsSet `
        -StartWhenAvailable `
        -MultipleInstances IgnoreNew `
        -ExecutionTimeLimit (New-TimeSpan -Minutes 30)

    $syncAction = New-ScheduledTaskAction `
        -Execute $powershellExe `
        -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$apiRoot\scripts\postgresql\run-sync-outbox.ps1`"" `
        -WorkingDirectory $apiRoot

    $syncTrigger = New-ScheduledTaskTrigger `
        -Once `
        -At (Get-Date).AddMinutes(1) `
        -RepetitionInterval (New-TimeSpan -Minutes 1)

    Register-ScheduledTask `
        -TaskName $syncTaskName `
        -Action $syncAction `
        -Trigger $syncTrigger `
        -Principal $taskPrincipal `
        -Settings $taskSettings `
        -Force | Out-Null

    $backupAction = New-ScheduledTaskAction `
        -Execute $powershellExe `
        -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$apiRoot\scripts\postgresql\backup-postgresql.ps1`" -Type automatici -AutomaticRetention 120" `
        -WorkingDirectory $apiRoot

    $backupTrigger = New-ScheduledTaskTrigger `
        -Once `
        -At (Get-Date).AddMinutes(2) `
        -RepetitionInterval (New-TimeSpan -Minutes 5)

    Register-ScheduledTask `
        -TaskName $backupTaskName `
        -Action $backupAction `
        -Trigger $backupTrigger `
        -Principal $taskPrincipal `
        -Settings $taskSettings `
        -Force | Out-Null

    Disable-ScheduledTask `
        -TaskName $syncTaskName | Out-Null

    Disable-ScheduledTask `
        -TaskName $backupTaskName | Out-Null

    if (-not (Get-NetFirewallRule -DisplayName $firewallRuleName -ErrorAction SilentlyContinue)) {
        New-NetFirewallRule `
            -DisplayName $firewallRuleName `
            -Direction Inbound `
            -Protocol TCP `
            -LocalPort 8080 `
            -Action Allow | Out-Null
    }

    $installationState = [PSCustomObject]@{
        installedAt                 = (Get-Date).ToString("o")
        installRoot                 = $installRoot
        databaseName                = $dbName
        databaseUser                = $dbUser
        apacheService               = $apacheServiceName
        postgresService             = $postgresService.Name
        postgresInstalledByKContest = $postgresInstalledByKContest
        syncTask                    = $syncTaskName
        backupTask                  = $backupTaskName
    }

    $stateJson = $installationState | ConvertTo-Json

    Write-Utf8WithoutBom `
        -Path "$programDataRoot\install-state.json" `
        -Content $stateJson

    New-DesktopShortcut `
        -Name "Avvia KContest" `
        -ScriptPath "$managementRoot\Avvia-KContest.ps1"

    New-DesktopShortcut `
        -Name "Arresta KContest" `
        -ScriptPath "$managementRoot\Arresta-KContest.ps1"

    New-DesktopShortcut `
        -Name "Disinstalla KContest" `
        -ScriptPath "$managementRoot\Disinstalla-KContest.ps1"

    Stop-Service `
        -Name $apacheServiceName `
        -ErrorAction SilentlyContinue

    if ($postgresInstalledByKContest) {
        Stop-Service `
            -Name $postgresService.Name `
            -ErrorAction SilentlyContinue
    }

    Write-Host ""
    Write-Host "Installazione completata." -ForegroundColor Green
    Write-Host ""
    Write-Host "KContest e installato ma spento."
    Write-Host "Usa il collegamento 'Avvia KContest' sul desktop per iniziare la gara."
    Write-Host "Database: $dbName"
    Write-Host "Backup: $programDataRoot\backups"
}
finally {
    Remove-Item Env:\PGPASSWORD -ErrorAction SilentlyContinue
    $adminPassword = $null
    $adminSecurePassword = $null
}
