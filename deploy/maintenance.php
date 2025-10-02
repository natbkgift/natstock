<?php
// Lightweight maintenance tasks (clear/rebuild caches) - remove after use
// Usage: /maintenance.php?token=ns-debug-9f6c7a4
error_reporting(E_ALL);
ini_set('display_errors', '1');

$TOKEN = 'ns-debug-9f6c7a4';
if (!isset($_GET['token']) || $_GET['token'] !== $TOKEN) { http_response_code(403); echo 'Forbidden'; exit; }

function err($m){ echo '<div style="color:#b00">'.htmlentities($m).'</div>'; }
function ok($m){ echo '<div style="color:green">'.htmlentities($m).'</div>'; }

// Locate app root
$candidates = [ __DIR__.'/natstock', dirname(__DIR__).'/natstock' ];
$appRoot = null; foreach ($candidates as $c) { if (is_dir($c) && is_file($c.'/bootstrap/app.php')) { $appRoot = $c; break; } }
if (!$appRoot) { err('App folder natstock not found alongside this file.'); exit; }

require $appRoot.'/vendor/autoload.php';
$app = require $appRoot.'/bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

$kernel = $app->make(ConsoleKernel::class);
$kernel->bootstrap();

try {
    chdir($appRoot);
    Artisan::call('config:clear'); ok('config:clear');
    Artisan::call('route:clear'); ok('route:clear');
    Artisan::call('view:clear'); ok('view:clear');
    // Reset PHP opcache so updated PHP files are used immediately (InfinityFree may cache aggressively)
    if (function_exists('opcache_reset')) { opcache_reset(); ok('opcache_reset'); } else { ok('opcache_reset not available'); }
    clearstatcache(); ok('clearstatcache');
    // Rebuild caches (optional)
    Artisan::call('config:cache'); ok('config:cache');
    Artisan::call('route:cache'); ok('route:cache');
    Artisan::call('view:cache'); ok('view:cache');
    echo '<p>Done. You can now test the site and then delete maintenance.php.</p>';
} catch (Throwable $e) {
    err((string)$e);
}
