# Upload only changed hotfix files to InfinityFree
param(
  [string]$FtpHost = "ftpupload.net",
  [int]$FtpPort = 21,
  [string]$FtpUser = "if0_40066737",
  [string]$FtpPass = "I068rLAkKxR"
)

$ErrorActionPreference = 'Stop'

function UploadOne {
  param([string]$LocalPath, [string]$RemotePath)
  Write-Host "Uploading $LocalPath -> $RemotePath"
  $uri = "ftp://$FtpHost$RemotePath"
  $client = New-Object System.Net.WebClient
  $client.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
  $client.UploadFile($uri, $LocalPath) | Out-Null
}

# compute repo root
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$repo = Split-Path -Parent $here

$files = @(
  @{ local = Join-Path $repo 'inventory-app\app\Providers\AppServiceProvider.php'; remote = '/htdocs/natstock/app/Providers/AppServiceProvider.php' },
  @{ local = Join-Path $repo 'inventory-app\app\Logging\MaskSensitiveData.php'; remote = '/htdocs/natstock/app/Logging/MaskSensitiveData.php' },
  @{ local = Join-Path $repo 'inventory-app\app\Http\Controllers\Admin\DashboardController.php'; remote = '/htdocs/natstock/app/Http/Controllers/Admin/DashboardController.php' },
    @{ local = Join-Path $repo 'inventory-app\app\Http\Controllers\Admin\AuditController.php'; remote = '/htdocs/natstock/app/Http/Controllers/Admin/AuditController.php' },
    @{ local = Join-Path $repo 'inventory-app\resources\views\admin\audit\index.blade.php'; remote = '/htdocs/natstock/resources/views/admin/audit/index.blade.php' },
  @{ local = Join-Path $repo 'inventory-app\app\Http\Controllers\Admin\SettingController.php'; remote = '/htdocs/natstock/app/Http/Controllers/Admin/SettingController.php' },
  @{ local = Join-Path $repo 'inventory-app\app\Http\Controllers\Admin\ImportController.php'; remote = '/htdocs/natstock/app/Http/Controllers/Admin/ImportController.php' },
  @{ local = Join-Path $repo 'inventory-app\app\Services\NotificationTestService.php'; remote = '/htdocs/natstock/app/Services/NotificationTestService.php' },
  @{ local = Join-Path $repo 'inventory-app\routes\web.php'; remote = '/htdocs/natstock/routes/web.php' },
  @{ local = Join-Path $repo 'deploy\maintenance.php'; remote = '/htdocs/maintenance.php' },
  @{ local = Join-Path $repo 'deploy\viewlog.php'; remote = '/htdocs/viewlog.php' }
)

foreach ($f in $files) {
  if (-not (Test-Path $f.local)) { Write-Warning "Missing file: $($f.local)"; continue }
  UploadOne -LocalPath $f.local -RemotePath $f.remote
}

Write-Host 'Upload complete.'
