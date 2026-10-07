[CmdletBinding()]
param(
    [string]$DependenciesRoot = "C:\KContest-Dependencies",
    [string]$BuildRoot = "C:\KContest-Build",
    [switch]$SkipFrontendBuild
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

function Write-Step {
    param([string]$Message)
    Write-Host "`n==> $Message" -ForegroundColor Cyan
}

function Assert-Path {
    param(
        [string]$Path,
        [string]$Description
    )

    if (-not (Test-Path -LiteralPath $Path)) {
        throw "Elemento mancante: $Description`nPercorso: $Path"
    }
}

function Copy-DirectoryContent {
    param(
        [string]$Source,
        [string]$Destination
    )

    Assert-Path $Source "cartella sorgente"
    New-Item -ItemType Directory -Path $Destination -Force | Out-Null
    Copy-Item -Path (Join-Path $Source "*") -Destination $Destination -Recurse -Force
}

function Invoke-RobocopyChecked {
    param(
        [string]$Source,
        [string]$Destination,
        [string[]]$ExtraArguments = @()
    )

    New-Item -ItemType Directory -Path $Destination -Force | Out-Null

    $arguments = @(
        $Source,
        $Destination,
        "/E",
        "/COPY:DAT",
        "/DCOPY:DAT",
        "/R:2",
        "/W:1",
        "/NP",
        "/NFL",
        "/NDL",
        "/NJH",
        "/NJS"
    ) + $ExtraArguments

    & robocopy.exe @arguments
    $robocopyCode = $LASTEXITCODE

    if ($robocopyCode -gt 7) {
        throw "Robocopy non riuscito. Codice: $robocopyCode. Origine: $Source"
    }
}

$repositoryRoot = Split-Path -Parent $PSScriptRoot
$uiRoot = Join-Path $repositoryRoot "UI"
$backendRoot = Join-Path $repositoryRoot "ApiV2"
$installerSource = Join-Path $repositoryRoot "Installer"
$versionFile = Join-Path $repositoryRoot "VERSION"

Assert-Path $uiRoot "progetto Angular UI"
Assert-Path $backendRoot "backend CakePHP ApiV2"
Assert-Path $installerSource "sorgenti Installer"
Assert-Path $versionFile "file VERSION"

$version = (Get-Content -LiteralPath $versionFile -Raw).Trim()
if ($version -notmatch '^\d+\.\d+\.\d+([-.][0-9A-Za-z.-]+)?$') {
    throw "Versione non valida nel file VERSION: '$version'. Esempio valido: 1.0.0"
}

$packageRoot = Join-Path $BuildRoot "KContest-Installer-$version"
$zipPath = Join-Path $BuildRoot "KContest-Installer-$version.zip"
$applicationRoot = Join-Path $packageRoot "application\csenveneto"
$packagedBackend = Join-Path $applicationRoot "ApiV2"

Write-Step "Controllo dello stato Git"
$gitCommand = Get-Command git.exe -ErrorAction SilentlyContinue
if ($gitCommand) {
    $gitStatus = & git.exe -C $repositoryRoot status --porcelain
    if ($LASTEXITCODE -ne 0) {
        throw "Impossibile leggere lo stato del repository Git."
    }

    if ($gitStatus) {
        Write-Warning "Il repository contiene modifiche non ancora registrate in Git. Il pacchetto le includera."
        $gitStatus | ForEach-Object { Write-Host "  $_" -ForegroundColor Yellow }
    }
}
else {
    Write-Warning "Git non trovato nel PATH: il commit non verra registrato in BUILD-INFO.json."
}

Write-Step "Verifica delle dipendenze"
$requiredDependencies = @(
    (Join-Path $DependenciesRoot "runtime\apache\bin\httpd.exe"),
    (Join-Path $DependenciesRoot "runtime\php\php.exe"),
    (Join-Path $DependenciesRoot "runtime\php\php.ini"),
    (Join-Path $DependenciesRoot "database\kcontest-base.backup")
)

foreach ($requiredDependency in $requiredDependencies) {
    Assert-Path $requiredDependency "dipendenza necessaria al pacchetto"
}

$postgresInstaller = Get-ChildItem -LiteralPath (Join-Path $DependenciesRoot "prerequisites") `
    -File -Filter "*postgresql*.exe" -ErrorAction SilentlyContinue |
    Select-Object -First 1

if (-not $postgresInstaller) {
    throw "Installer PostgreSQL non trovato in '$DependenciesRoot\prerequisites'."
}

Write-Step "Preparazione della cartella di build"
New-Item -ItemType Directory -Path $BuildRoot -Force | Out-Null

if (Test-Path -LiteralPath $packageRoot) {
    Remove-Item -LiteralPath $packageRoot -Recurse -Force
}

if (Test-Path -LiteralPath $zipPath) {
    Remove-Item -LiteralPath $zipPath -Force
}

New-Item -ItemType Directory -Path $packageRoot -Force | Out-Null
New-Item -ItemType Directory -Path $applicationRoot -Force | Out-Null

Write-Step "Compilazione del frontend Angular"
if (-not $SkipFrontendBuild) {
    Assert-Path (Join-Path $uiRoot "package.json") "package.json del frontend"
    Assert-Path (Join-Path $uiRoot "angular.json") "angular.json del frontend"

    $npmCommand = Get-Command npm.cmd -ErrorAction SilentlyContinue
    if (-not $npmCommand) {
        throw "npm.cmd non trovato nel PATH. Installa Node.js oppure usa -SkipFrontendBuild se dist e gia aggiornato."
    }

    Push-Location $uiRoot
    try {
        & npm.cmd ci --legacy-peer-deps
        if ($LASTEXITCODE -ne 0) {
            throw "npm ci non riuscito."
        }

        & npm.cmd run build -- --configuration production --base-href /csenveneto/
        if ($LASTEXITCODE -ne 0) {
            throw "Compilazione Angular non riuscita."
        }
    }
    finally {
        Pop-Location
    }
}

$angularConfig = Get-Content -LiteralPath (Join-Path $uiRoot "angular.json") -Raw | ConvertFrom-Json
$angularProjectName = $angularConfig.projects.PSObject.Properties.Name | Select-Object -First 1
$angularProject = $angularConfig.projects.$angularProjectName
$outputPathValue = $angularProject.architect.build.options.outputPath

if ($outputPathValue -is [string]) {
    $frontendOutput = Join-Path $uiRoot $outputPathValue
}
elseif ($outputPathValue.browser) {
    $frontendOutput = Join-Path $uiRoot $outputPathValue.browser
}
else {
    throw "Impossibile determinare outputPath da UI\angular.json."
}

if ((Test-Path -LiteralPath (Join-Path $frontendOutput "browser\index.html")) -and
    -not (Test-Path -LiteralPath (Join-Path $frontendOutput "index.html"))) {
    $frontendOutput = Join-Path $frontendOutput "browser"
}

Assert-Path (Join-Path $frontendOutput "index.html") "frontend Angular compilato"
Copy-DirectoryContent -Source $frontendOutput -Destination $applicationRoot

Write-Step "Copia del backend CakePHP dal repository"
$backendCopyArguments = @(
    "/XD", ".git", "logs", "tmp",
    "/XF", ".env", ".env.local", "app_local.php"
)
Invoke-RobocopyChecked -Source $backendRoot -Destination $packagedBackend -ExtraArguments $backendCopyArguments

New-Item -ItemType Directory -Path (Join-Path $packagedBackend "logs") -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $packagedBackend "tmp") -Force | Out-Null

# Composer puo incorporare nel finder di Symfony il percorso del PHP usato
# durante l'installazione delle dipendenze. Nel pacchetto deve puntare al
# runtime autonomo di KContest, non al PHP di sviluppo sotto XAMPP.
$phpExecutableFinder = Join-Path $packagedBackend "vendor\symfony\process\PhpExecutableFinder.php"
if (Test-Path -LiteralPath $phpExecutableFinder) {
    $finderContent = Get-Content -LiteralPath $phpExecutableFinder -Raw
    $finderContent = $finderContent.Replace("C:\xampp\php", "C:\KContest\runtime\php")
    Set-Content -LiteralPath $phpExecutableFinder -Value $finderContent -Encoding UTF8
}

Write-Step "Copia degli script dell'installer"
Copy-DirectoryContent -Source $installerSource -Destination $packageRoot

Write-Step "Copia dei runtime e dei prerequisiti"
Copy-DirectoryContent -Source (Join-Path $DependenciesRoot "runtime") -Destination (Join-Path $packageRoot "runtime")
Copy-DirectoryContent -Source (Join-Path $DependenciesRoot "prerequisites") -Destination (Join-Path $packageRoot "prerequisites")
Copy-DirectoryContent -Source (Join-Path $DependenciesRoot "database") -Destination (Join-Path $packageRoot "database")
New-Item -ItemType Directory -Path (Join-Path $packageRoot "runtime\tmp") -Force | Out-Null

# Questi file provengono dalla distribuzione XAMPP originale, ma non sono
# necessari al runtime KContest: Apache viene gestito come servizio Windows
# e le dipendenze PHP dell'applicazione sono gia presenti in vendor.
$unusedRuntimeItems = @(
    (Join-Path $packageRoot "runtime\apache\scripts"),
    (Join-Path $packageRoot "runtime\php\pear"),
    (Join-Path $packageRoot "runtime\php\pci.bat"),
    (Join-Path $packageRoot "runtime\php\pciconf.bat"),
    (Join-Path $packageRoot "runtime\php\pear.bat"),
    (Join-Path $packageRoot "runtime\php\peardev.bat"),
    (Join-Path $packageRoot "runtime\php\pecl.bat"),
    (Join-Path $packageRoot "runtime\php\phpunit"),
    (Join-Path $packageRoot "runtime\php\phpunit.bat")
)

foreach ($unusedRuntimeItem in $unusedRuntimeItems) {
    if (Test-Path -LiteralPath $unusedRuntimeItem) {
        Remove-Item -LiteralPath $unusedRuntimeItem -Recurse -Force
    }
}

Write-Step "Generazione delle informazioni di build"
$gitCommit = "non-disponibile"
if ($gitCommand) {
    $resolvedCommit = & git.exe -C $repositoryRoot rev-parse HEAD 2>$null
    if ($LASTEXITCODE -eq 0 -and $resolvedCommit) {
        $gitCommit = $resolvedCommit.Trim()
    }
}

$buildInfo = [ordered]@{
    version = $version
    gitCommit = $gitCommit
    buildDate = (Get-Date).ToString("o")
    computerName = $env:COMPUTERNAME
}

$buildInfo |
    ConvertTo-Json |
    Set-Content -LiteralPath (Join-Path $packageRoot "BUILD-INFO.json") -Encoding UTF8

Write-Step "Controllo di configurazioni locali e riferimenti XAMPP"
$forbiddenFiles = Get-ChildItem -LiteralPath $packageRoot -Recurse -File -Force |
    Where-Object {
        $_.Name -in @(".env", ".env.local", "app_local.php")
    }

if ($forbiddenFiles) {
    $paths = ($forbiddenFiles.FullName -join "`n")
    throw "Nel pacchetto sono presenti configurazioni locali vietate:`n$paths"
}

$textExtensions = @(".ps1", ".cmd", ".bat", ".php", ".ini", ".conf", ".json", ".env", ".txt")
$xamppReferences = Get-ChildItem -LiteralPath $packageRoot -Recurse -File |
    Where-Object { $textExtensions -contains $_.Extension.ToLowerInvariant() } |
    Select-String -Pattern "C:\xampp", "C:/xampp" -SimpleMatch

if ($xamppReferences) {
    $matches = ($xamppReferences | ForEach-Object {
        "$($_.Path):$($_.LineNumber):$($_.Line.Trim())"
    }) -join "`n"
    throw "Sono stati trovati riferimenti residui a XAMPP:`n$matches"
}

Write-Step "Generazione del manifesto SHA256"
$manifestPath = Join-Path $packageRoot "manifest-sha256.txt"
$packagePrefixLength = $packageRoot.TrimEnd('\').Length + 1

$manifestLines = Get-ChildItem -LiteralPath $packageRoot -Recurse -File |
    Where-Object { $_.FullName -ne $manifestPath } |
    Sort-Object FullName |
    ForEach-Object {
        $relativePath = $_.FullName.Substring($packagePrefixLength).Replace('\', '/')
        $hash = (Get-FileHash -LiteralPath $_.FullName -Algorithm SHA256).Hash.ToLowerInvariant()
        "$hash  $relativePath"
    }

$manifestLines | Set-Content -LiteralPath $manifestPath -Encoding UTF8

Write-Step "Creazione del pacchetto ZIP"
Compress-Archive -Path (Join-Path $packageRoot "*") -DestinationPath $zipPath -CompressionLevel Optimal

$zipHash = (Get-FileHash -LiteralPath $zipPath -Algorithm SHA256).Hash.ToLowerInvariant()
$zipHashPath = "$zipPath.sha256"
"$zipHash  $([System.IO.Path]::GetFileName($zipPath))" |
    Set-Content -LiteralPath $zipHashPath -Encoding ASCII

Write-Host "`nPacchetto generato correttamente." -ForegroundColor Green
Write-Host "Cartella: $packageRoot"
Write-Host "ZIP:      $zipPath"
Write-Host "SHA256:   $zipHashPath"
