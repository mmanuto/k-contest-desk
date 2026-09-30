Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Import-KContestEnv {
    param([Parameter(Mandatory = $true)][string]$Path)

    if (-not (Test-Path -LiteralPath $Path)) {
        throw "File di configurazione non trovato: $Path"
    }

    foreach ($line in Get-Content -LiteralPath $Path) {
        $value = $line.Trim()
        if (-not $value -or $value.StartsWith('#')) { continue }
        if ($value.StartsWith('export ')) { $value = $value.Substring(7).Trim() }

        $separator = $value.IndexOf('=')
        if ($separator -lt 1) { continue }

        $name = $value.Substring(0, $separator).Trim()
        $content = $value.Substring($separator + 1).Trim()
        if (($content.StartsWith('"') -and $content.EndsWith('"')) -or
            ($content.StartsWith("'") -and $content.EndsWith("'"))) {
            $content = $content.Substring(1, $content.Length - 2)
        }

        [Environment]::SetEnvironmentVariable($name, $content, 'Process')
    }
}

function Get-KContestSetting {
    param(
        [Parameter(Mandatory = $true)][string]$Name,
        [string]$Default = ''
    )
    $value = [Environment]::GetEnvironmentVariable($Name, 'Process')
    if ([string]::IsNullOrWhiteSpace($value)) { return $Default }
    return $value
}

function Resolve-PostgresTool {
    param([Parameter(Mandatory = $true)][string]$Name)

    $command = Get-Command "$Name.exe" -ErrorAction SilentlyContinue
    if ($command) { return $command.Source }

    $postgresRoot = Join-Path $env:ProgramFiles 'PostgreSQL'
    if (Test-Path -LiteralPath $postgresRoot) {
        $candidate = Get-ChildItem -LiteralPath $postgresRoot -Directory |
            Where-Object { $_.Name -match '^\d+(\.\d+)*$' } |
            Sort-Object { [version]$_.Name } -Descending |
            ForEach-Object { Join-Path $_.FullName "bin\$Name.exe" } |
            Where-Object { Test-Path -LiteralPath $_ } |
            Select-Object -First 1
        if ($candidate) { return $candidate }
    }

    throw "$Name.exe non trovato. Aggiungere PostgreSQL al PATH o verificarne l'installazione."
}

function Write-KContestLog {
    param(
        [Parameter(Mandatory = $true)][string]$LogPath,
        [Parameter(Mandatory = $true)][string]$Message,
        [ValidateSet('INFO', 'WARN', 'ERROR')][string]$Level = 'INFO'
    )
    $directory = Split-Path -Parent $LogPath
    if (-not (Test-Path -LiteralPath $directory)) {
        New-Item -ItemType Directory -Path $directory -Force | Out-Null
    }
    $entry = '{0:yyyy-MM-dd HH:mm:ss} [{1}] {2}' -f (Get-Date), $Level, $Message
    Add-Content -LiteralPath $LogPath -Value $entry -Encoding UTF8
    Write-Host $entry
}

function Use-PostgresPassword {
    param(
        [Parameter(Mandatory = $true)][string]$Password,
        [Parameter(Mandatory = $true)][scriptblock]$Action
    )
    $previous = [Environment]::GetEnvironmentVariable('PGPASSWORD', 'Process')
    try {
        [Environment]::SetEnvironmentVariable('PGPASSWORD', $Password, 'Process')
        & $Action
    } finally {
        [Environment]::SetEnvironmentVariable('PGPASSWORD', $previous, 'Process')
    }
}
