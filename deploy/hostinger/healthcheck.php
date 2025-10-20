<?php

// Production hardening: prevent error leakage if bootstrap fails.
error_reporting(0);
ini_set('display_errors', '0');

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

$home = getenv('HOME') ?: null;
$candidates = [];

$repoRoot = realpath(__DIR__ . '/../..');
if ($repoRoot !== false) {
    $candidates[] = $repoRoot . '/inventory-app';
}

if ($home) {
    $candidates[] = rtrim($home, '/') . '/natstock_app/inventory-app';
}

$appPath = null;
foreach ($candidates as $candidate) {
    if ($candidate && is_dir($candidate)) {
        $appPath = $candidate;
        break;
    }
}

if (!$appPath) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'ไม่พบโฟลเดอร์ inventory-app สำหรับบูต Laravel',
    ]);
    exit;
}

require_once $appPath . '/vendor/autoload.php';

$app = require $appPath . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$env = app()->environment();

$dbHealthy = false;
try {
    DB::connection()->getPdo();
    $dbHealthy = true;
} catch (Throwable $e) {
    $dbHealthy = false;
}

$cacheHealthy = false;
try {
    $key = 'healthcheck_' . uniqid('', true);
    Cache::put($key, 'ok', 10);
    $cacheHealthy = Cache::pull($key) === 'ok';
} catch (Throwable $e) {
    $cacheHealthy = false;
}

header('Content-Type: application/json');
echo json_encode([
    'ok' => $dbHealthy && $cacheHealthy,
    'env' => $env,
    'db' => $dbHealthy,
    'cache' => $cacheHealthy,
    'time' => date('c'),
]);

