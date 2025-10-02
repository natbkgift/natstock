<?php
// Web installer that boots Laravel and runs Artisan commands programmatically.
error_reporting(E_ALL);
ini_set('display_errors', '1');

function ok($msg) { echo '<div style="color:green">'.htmlentities($msg)."</div>"; }
function info($msg) { echo '<div>'.htmlentities($msg)."</div>"; }
function err($msg) { echo '<div style="color:red">'.htmlentities($msg)."</div>"; }

// Locate the Laravel app root relative to this script
// Prefer a sibling folder first (most shared hosts place app under the webroot)
$candidates = [
    __DIR__ . '/natstock',    // when this lives in document root
    __DIR__ . '/../natstock', // when app is one level above public_html/
];

// Fallback: scan nearby directories for a folder that contains bootstrap/app.php
$searchRoots = [__DIR__, dirname(__DIR__)];
$scanned = [];
$appRoot = null;
foreach ($candidates as $c) {
    if (is_dir($c) && is_file($c.'/bootstrap/app.php')) { $appRoot = $c; break; }
}
if (!$appRoot) {
    foreach ($searchRoots as $root) {
        if (!is_dir($root)) continue;
        $entries = @scandir($root) ?: [];
        foreach ($entries as $e) {
            if ($e === '.' || $e === '..') continue;
            $path = $root . DIRECTORY_SEPARATOR . $e;
            if (is_dir($path)) {
                $scanned[] = $path;
                if (is_file($path . '/bootstrap/app.php')) { $appRoot = $path; break 2; }
            }
        }
    }
}

if (!$appRoot) {
    err('Could not locate Laravel app. Please run /extract.php first so that the app folder (e.g. "natstock/") is created.');
    if ($scanned) {
        info('Searched:');
        echo '<ul style="margin:4px 0 12px">';
        foreach ($scanned as $s) echo '<li>'.htmlentities($s).'</li>';
        echo '</ul>';
    }
    exit;
}

// Basic .env editor on first GET if .env missing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !is_file($appRoot.'/.env')) {
    $defaults = [
        'APP_NAME' => 'NATStock',
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'APP_URL' => ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'],
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => 'localhost',
        'DB_PORT' => '3306',
        'DB_DATABASE' => '',
        'DB_USERNAME' => '',
        'DB_PASSWORD' => '',
    ];
    echo '<h3>Configure environment</h3>';
    echo '<form method="post">';
    foreach ($defaults as $k => $v) {
        $type = ($k === 'DB_PASSWORD') ? 'password' : 'text';
        echo '<div><label>'.htmlentities($k).': <input type="'.$type.'" name="'.htmlentities($k).'" value="'.htmlentities($v).'" style="width:320px"></label></div>';
    }
    echo '<button type="submit">Save and continue</button>';
    echo '</form>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !is_file($appRoot.'/.env')) {
    $pairs = [];
    foreach ($_POST as $k => $v) {
        if (!preg_match('/^[A-Z0-9_]+$/', $k)) continue;
        $val = str_replace(["\r", "\n"], [' ', ' '], (string)$v);
        // Quote values that contain spaces or special characters
        if (preg_match('/\s|#|"/', $val)) $val = '"'.str_replace('"', '\"', $val).'"';
        $pairs[] = $k.'='.$val;
    }
    // Ensure required keys exist
    if (!preg_grep('/^APP_ENV=/', $pairs)) $pairs[] = 'APP_ENV=production';
    if (!preg_grep('/^APP_DEBUG=/', $pairs)) $pairs[] = 'APP_DEBUG=false';
    file_put_contents($appRoot.'/.env', implode("\n", $pairs)."\n");
    info('.env created');
}

// Ensure vendor exists before bootstrapping
if (!is_file($appRoot . '/vendor/autoload.php')) {
    err('Missing vendor/autoload.php at: ' . $appRoot . '/vendor/autoload.php');
    info('This usually means the extraction did not complete or the deployment package did not include vendor files.');
    echo '<ul style="margin:6px 0 12px">';
    echo '<li>Re-upload all deployment parts (e.g. natstock-cpanel.zip or natstock-cpanel.part00.txt .. partNN.txt) to the same folder as this file.</li>';
    echo '<li>Open <code>/extract.php</code> and let it reach 100% (it auto-continues). Then reload this page.</li>';
    echo '<li>After extraction, you should see a <code>natstock/</code> folder here with <code>bootstrap/app.php</code> and <code>vendor/autoload.php</code>.</li>';
    echo '</ul>';
    if (is_dir($appRoot)) {
        // Print a small directory peek for debugging
        $peek = array_slice(array_values(array_filter(scandir($appRoot) ?: [], function($x){return $x!=='.' && $x!=='..';})), 0, 10);
        info('Found app folder at: ' . $appRoot);
        if ($peek) {
            echo '<div>Top-level entries: '.htmlentities(implode(', ', $peek)).'</div>';
        }
    }
    exit;
}

// Bootstrap Laravel
require $appRoot . '/vendor/autoload.php';
$app = require $appRoot . '/bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

$kernel = $app->make(ConsoleKernel::class);
$kernel->bootstrap();

try {
    chdir($appRoot);

    if (!file_exists($appRoot.'/.env')) {
        if (file_exists($appRoot.'/.env.example')) {
            copy($appRoot.'/.env.example', $appRoot.'/.env');
            info('Copied .env.example to .env');
        } else {
            throw new RuntimeException('Missing .env and .env.example');
        }
    }

    Artisan::call('key:generate', ['--force' => true]);
    ok('APP_KEY generated');

    try {
        Artisan::call('storage:link');
        ok('storage link created');
    } catch (Throwable $le) {
        // Fallback: copy storage/app/public to public/storage
        try {
            $from = $appRoot.'/storage/app/public';
            $to = $appRoot.'/public/storage';
            if (!is_dir($to)) { @mkdir($to, 0755, true); }
            $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS));
            foreach ($rii as $file) {
                $dest = $to.'/'.substr($file->getPathname(), strlen($from)+1);
                if ($file->isDir()) { @mkdir($dest, 0755, true); continue; }
                @mkdir(dirname($dest), 0755, true);
                @copy($file->getPathname(), $dest);
            }
            info('storage link not allowed; copied files instead');
        } catch (Throwable $ce) {
            info('storage link fallback failed: '.$ce->getMessage());
        }
    }

    Artisan::call('migrate', ['--force' => true]);
    ok('migrations executed');

    try {
        Artisan::call('db:seed', ['--force' => true]);
        ok('database seeded');
    } catch (Throwable $se) {
        info('seeding skipped or failed: '.$se->getMessage());
    }

    Artisan::call('config:cache');
    Artisan::call('route:cache');
    Artisan::call('view:cache');
    ok('caches built');

    echo '<h3>Installation complete.</h3>';
    echo '<p>Delete this file: '.htmlentities(basename(__FILE__)).'</p>';
    echo '<p><a href="/">Go to site</a></p>';
} catch (Throwable $e) {
    echo '<h3>Installation failed</h3>';
    err((string)$e);
}
