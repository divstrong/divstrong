<#
.SYNOPSIS
  Deploy the divStrong Laravel app to the production VPS.

.DESCRIPTION
  Pushes source to git, builds assets locally, scp's the built assets to the
  remote server, then SSH's in to run composer install, migrations, cache
  warmup, and queue restart.

.PARAMETER DryRun
  Print every step but do not push, build, scp, or ssh.

.PARAMETER SkipBuild
  Skip the local npm build (use when nothing front-end changed).

.PARAMETER SkipPush
  Skip the local git push (use when the server is on a branch ahead of origin).

.PARAMETER Yes
  Skip the confirmation prompt. Use only when you are sure.

.EXAMPLE
  ./deploy/deploy.ps1
  ./deploy/deploy.ps1 -DryRun
  ./deploy/deploy.ps1 -SkipBuild -Yes
#>
[CmdletBinding()]
param(
    [switch]$DryRun,
    [switch]$SkipBuild,
    [switch]$SkipPush,
    [switch]$Yes
)

$ErrorActionPreference = 'Stop'

# --- locate paths relative to this script ---
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot  = Split-Path -Parent $ScriptDir
$ServerDir = Join-Path $RepoRoot 'server'
$EnvFile   = Join-Path $ScriptDir '.deploy.env'

function Write-Step($msg)    { Write-Host "==> $msg" -ForegroundColor Cyan }
function Write-Ok($msg)      { Write-Host "    $msg" -ForegroundColor Green }
function Write-Warn2($msg)   { Write-Host "    $msg" -ForegroundColor Yellow }
function Write-Err2($msg)    { Write-Host "    $msg" -ForegroundColor Red }

function Fail($msg) {
    Write-Err2 $msg
    exit 1
}

# --- load config ---
if (-not (Test-Path $EnvFile)) {
    Fail "Missing $EnvFile. Copy deploy/.deploy.env.example to deploy/.deploy.env and fill it in."
}

$config = @{}
Get-Content $EnvFile | ForEach-Object {
    $line = $_.Trim()
    if (-not $line -or $line.StartsWith('#')) { return }
    $eq = $line.IndexOf('=')
    if ($eq -lt 1) { return }
    $key = $line.Substring(0, $eq).Trim()
    $val = $line.Substring($eq + 1).Trim().Trim('"').Trim("'")
    $config[$key] = $val
}

foreach ($k in @('SSH_HOST', 'SSH_USER', 'REMOTE_PATH', 'BRANCH')) {
    if (-not $config.ContainsKey($k) -or -not $config[$k]) {
        Fail "Missing $k in $EnvFile"
    }
}

$SshHost     = $config.SSH_HOST
$SshUser     = $config.SSH_USER
$SshPort     = if ($config.SSH_PORT) { $config.SSH_PORT } else { '22' }
$RemotePath  = $config.REMOTE_PATH
$Branch      = $config.BRANCH
$FpmService  = $config.FPM_SERVICE
$UseSudo     = ($config.USE_SUDO -eq '1')

$SshTarget = "$SshUser@$SshHost"

Write-Step "Deploy config"
Write-Host "    target : $SshTarget`:$SshPort" -ForegroundColor Gray
Write-Host "    path   : $RemotePath"          -ForegroundColor Gray
Write-Host "    branch : $Branch"              -ForegroundColor Gray
if ($DryRun) { Write-Warn2 "DRY RUN -- no remote changes will be made." }

# --- pre-flight: git clean + on right branch ---
Write-Step "Pre-flight checks"

Push-Location $RepoRoot
try {
    $status = git status --porcelain 2>&1
    if ($LASTEXITCODE -ne 0) { Fail "git status failed: $status" }
    if ($status) {
        Write-Warn2 "Working tree is not clean:"
        Write-Host $status -ForegroundColor Gray
        if (-not $Yes) {
            $resp = Read-Host "Continue anyway? (y/N)"
            if ($resp -notmatch '^[yY]') { Fail "Aborted." }
        }
    } else {
        Write-Ok "working tree clean"
    }

    $currentBranch = (git rev-parse --abbrev-ref HEAD).Trim()
    if ($currentBranch -ne $Branch) {
        Write-Warn2 "On branch '$currentBranch' but deploy branch is '$Branch'."
        if (-not $Yes) {
            $resp = Read-Host "Continue anyway? (y/N)"
            if ($resp -notmatch '^[yY]') { Fail "Aborted." }
        }
    } else {
        Write-Ok "on branch $Branch"
    }
}
finally {
    Pop-Location
}

# --- confirm ---
if (-not $Yes -and -not $DryRun) {
    Write-Host ""
    Write-Host "About to deploy to $SshTarget`:$RemotePath" -ForegroundColor Yellow
    $resp = Read-Host "Proceed? (y/N)"
    if ($resp -notmatch '^[yY]') { Fail "Aborted." }
}

# --- 1. push source ---
if (-not $SkipPush) {
    Write-Step "Pushing $Branch to origin"
    if ($DryRun) {
        Write-Warn2 "dry-run: git push origin $Branch"
    } else {
        Push-Location $RepoRoot
        try {
            git push origin $Branch
            if ($LASTEXITCODE -ne 0) { Fail "git push failed" }
            Write-Ok "pushed"
        }
        finally { Pop-Location }
    }
} else {
    Write-Warn2 "skipping git push (-SkipPush)"
}

# --- 2. build assets locally ---
if (-not $SkipBuild) {
    Write-Step "Building assets locally (npm run build)"
    if ($DryRun) {
        Write-Warn2 "dry-run: npm ci && npm run build in $ServerDir"
    } else {
        Push-Location $ServerDir
        try {
            if (-not (Test-Path (Join-Path $ServerDir 'node_modules'))) {
                Write-Warn2 "node_modules missing -- running npm ci"
                npm ci
                if ($LASTEXITCODE -ne 0) { Fail "npm ci failed" }
            }
            npm run build
            if ($LASTEXITCODE -ne 0) { Fail "npm run build failed" }
            Write-Ok "built"
        }
        finally { Pop-Location }
    }
} else {
    Write-Warn2 "skipping build (-SkipBuild)"
}

# --- 3. scp built assets and public/bug-reporter.js ---
Write-Step "Uploading built assets"

$LocalBuild  = Join-Path $ServerDir 'public\build'
$LocalEmbed  = Join-Path $ServerDir 'public\bug-reporter.js'
$RemoteBuild = "$RemotePath/public/build"
$RemotePublic = "$RemotePath/public"

if (Test-Path $LocalBuild) {
    if ($DryRun) {
        Write-Warn2 "dry-run: scp -r $LocalBuild  ->  $SshTarget`:$RemoteBuild"
    } else {
        # Wipe remote build first so stale chunks don't linger.
        ssh -p $SshPort $SshTarget "rm -rf '$RemoteBuild' && mkdir -p '$RemoteBuild'"
        if ($LASTEXITCODE -ne 0) { Fail "Failed to reset remote build directory" }

        scp -P $SshPort -r "$LocalBuild\*" "${SshTarget}:${RemoteBuild}/"
        if ($LASTEXITCODE -ne 0) { Fail "scp public/build failed" }
        Write-Ok "uploaded public/build"
    }
} else {
    Write-Warn2 "no local public/build -- skipping (Vite may not be used here)"
}

if (Test-Path $LocalEmbed) {
    if ($DryRun) {
        Write-Warn2 "dry-run: scp $LocalEmbed  ->  $SshTarget`:$RemotePublic/bug-reporter.js"
    } else {
        scp -P $SshPort "$LocalEmbed" "${SshTarget}:${RemotePublic}/bug-reporter.js"
        if ($LASTEXITCODE -ne 0) { Fail "scp bug-reporter.js failed" }
        Write-Ok "uploaded bug-reporter.js"
    }
}

# --- 4. remote: pull, install, migrate, optimize, restart queue ---
Write-Step "Running remote update commands"

$fpmReload = ''
if ($FpmService) {
    $sudo = if ($UseSudo) { 'sudo ' } else { '' }
    $fpmReload = "${sudo}systemctl reload $FpmService"
}

# Build remote command. Single-quoted PowerShell here-string => no $-expansion.
$remoteScript = @"
set -e
cd '$RemotePath'
echo '--> git fetch + reset to origin/$Branch'
git fetch origin $Branch
git reset --hard origin/$Branch
echo '--> composer install'
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
echo '--> php artisan migrate --force'
php artisan migrate --force
echo '--> php artisan storage:link'
php artisan storage:link || true
echo '--> php artisan optimize'
php artisan optimize
echo '--> php artisan queue:restart'
php artisan queue:restart
$(if ($fpmReload) { "echo '--> reload $FpmService'`n$fpmReload" })
echo 'done.'
"@

if ($DryRun) {
    Write-Warn2 "dry-run: would ssh and run:"
    Write-Host $remoteScript -ForegroundColor Gray
} else {
    $remoteScript | ssh -p $SshPort $SshTarget 'bash -s'
    if ($LASTEXITCODE -ne 0) { Fail "Remote update commands failed" }
    Write-Ok "remote updated"
}

Write-Host ""
Write-Host "==> Deploy complete." -ForegroundColor Green
