# Deploy NATStock to InfinityFree via FTP and web installers
param(
  [string]$Domain = "https://natstock.kesug.com",
  [string]$FtpHost = "ftpupload.net",
  [int]$FtpPort = 21,
  [string]$FtpUser = "if0_40066737",
  [string]$FtpPass = "I068rLAkKxR"
)

$ErrorActionPreference = 'Stop'

function Upload-File {
  param([string]$Local, [string]$Remote)
  $uri = "ftp://$FtpHost$Remote"
  $client = New-Object System.Net.WebClient
  $client.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
  Write-Host "Uploading $Local -> $uri"
  $client.UploadFile($uri, $Local) | Out-Null
}

# 1) Upload deploy artifacts to htdocs
$deployDir = Join-Path $PSScriptRoot '..\deploy'
$htdocsRoot = '/htdocs'

$filesToUpload = @(
  'diag.php',
  'extract.php',
  'install.php',
  'natstock-cpanel.part00.txt',
  'natstock-cpanel.part01.txt',
  'natstock-cpanel.part02.txt',
  'natstock-cpanel.part03.txt'
)

foreach ($f in $filesToUpload) {
  $local = Join-Path $deployDir $f
  if (Test-Path $local) {
    Upload-File -Local $local -Remote "$htdocsRoot/$f"
  }
}

# 2) Trigger extractor to completion
# Reuse a session to persist cookies and try to bypass InfinityFree JS challenge by using ?i=1
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

function Join-Url([string]$base, [string]$path) {
  if ($path -match '^https?://') { return $path }
  $b = $base.TrimEnd('/')
  if ($path -notmatch '^/') { return "$b/$path" }
  return "$b$path"
}

function Invoke-Get($url) {
  try {
    (Invoke-WebRequest -UseBasicParsing -WebSession $session -Uri $url -Headers @{ 'Cache-Control'='no-cache' }).Content
  } catch {
    $_.Exception.Response | Out-Null
    throw
  }
}

function Invoke-PostForm($url, $body) {
  try {
    (Invoke-WebRequest -UseBasicParsing -WebSession $session -Uri $url -Method Post -Body $body -Headers @{ 'Content-Type'='application/x-www-form-urlencoded'; 'Cache-Control'='no-cache' }).Content
  } catch {
    $_.Exception.Response | Out-Null
    throw
  }
}

$extractUrl = "$Domain/extract.php"
Write-Host "Starting extraction: $extractUrl"

# Loop until it stops returning a meta refresh (basic heuristic)
for ($i=0; $i -lt 240; $i++) {
  $html = Invoke-Get $extractUrl
  if ($html -match 'aes.js' -or $html -match '__test') {
    # Try bypass variant
    if ($extractUrl -notmatch '\?') { $extractUrl = (Join-Url $Domain 'extract.php?i=1') } else { $extractUrl = "$extractUrl&i=1" }
    Start-Sleep -Milliseconds 700
    continue
  }
  if ($html -match 'Extraction complete') { break }
  if ($html -match 'refresh" content="[0-9.]+;url=([^"]+)"') {
    $next = $Matches[1]
    $next = (Join-Url $Domain $next)
    Start-Sleep -Milliseconds 800
    $extractUrl = $next
  } else {
    Start-Sleep -Milliseconds 800
  }
}

# 3) Submit .env values to install.php if needed
$installUrl = "$Domain/install.php"
Write-Host "Running installer: $installUrl"

# First GET to see if it asks for env
$first = Invoke-Get $installUrl
if ($first -match 'aes.js' -or $first -match '__test') {
  $installUrl = (Join-Url $Domain 'install.php?i=1')
  $first = Invoke-Get $installUrl
}
if ($first -match 'Configure environment' -or $first -match 'APP_ENV') {
  $body = @{
    APP_NAME='NATStock'
    APP_ENV='production'
    APP_DEBUG='false'
    APP_URL=$Domain
    DB_CONNECTION='mysql'
    DB_HOST='sql100.infinityfree.com'
    DB_PORT='3306'
    DB_DATABASE='if0_40066737_natstock'
    DB_USERNAME='if0_40066737'
    DB_PASSWORD='I068rLAkKxR'
  }
  $content = Invoke-PostForm $installUrl $body
  if ($content -notmatch 'APP_KEY generated') {
    Write-Warning 'Installer did not report APP_KEY generation yet; reloading page.'
  }
}

# Final GET to run artisan actions
$final = Invoke-Get $installUrl
if ($final -match 'aes.js' -or $final -match '__test') {
  if ($installUrl -match '\?') { $final = Invoke-Get "$installUrl&i=1" } else { $final = Invoke-Get "$installUrl?i=1" }
}
$final | Out-String | Write-Host

Write-Host 'Done. Please remove extract.php and install.php from htdocs after verifying the site works.'
