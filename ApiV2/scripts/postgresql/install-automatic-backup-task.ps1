[CmdletBinding()]
param(
    [int]$Minutes = 5,
    [int]$Retention = 120
)

if ($Minutes -lt 2) { throw 'Intervallo minimo consentito: 2 minuti.' }
if ($Retention -lt 1) { throw 'La conservazione deve essere almeno 1.' }

$backupScript = (Resolve-Path (Join-Path $PSScriptRoot 'backup-postgresql.ps1')).Path
if (-not (Test-Path -LiteralPath $backupScript)) { throw "Script non trovato: $backupScript" }

$taskName = 'KContest-PostgreSQL-AutomaticBackup'
$powerShellExecutable = Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe'
$arguments = "-NoProfile -NonInteractive -ExecutionPolicy Bypass -File `"$backupScript`" -Type automatici -AutomaticRetention $Retention"
$workingDirectory = Split-Path -Parent $backupScript
$currentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name

$action = New-ScheduledTaskAction `
    -Execute $powerShellExecutable `
    -Argument $arguments `
    -WorkingDirectory $workingDirectory

$trigger = New-ScheduledTaskTrigger `
    -Once `
    -At ((Get-Date).AddMinutes(1)) `
    -RepetitionInterval (New-TimeSpan -Minutes $Minutes)

$settings = New-ScheduledTaskSettingsSet `
    -StartWhenAvailable `
    -MultipleInstances IgnoreNew `
    -ExecutionTimeLimit (New-TimeSpan -Minutes ([Math]::Max(2, $Minutes - 1)))

$principal = New-ScheduledTaskPrincipal `
    -UserId $currentUser `
    -LogonType Interactive `
    -RunLevel Highest

Register-ScheduledTask `
    -TaskName $taskName `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Principal $principal `
    -Description 'Backup automatico PostgreSQL di KContest.' `
    -Force | Out-Null

Write-Host "Attività pianificata creata: $taskName"
Write-Host "Intervallo: $Minutes minuti - conservazione: $Retention backup."
Write-Host "Utente: $currentUser"
Write-Host "Eseguibile: $powerShellExecutable"
Write-Host "Script: $backupScript"
Write-Host "Eseguire subito un test con: Start-ScheduledTask -TaskName `"$taskName`""
