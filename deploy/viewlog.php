<?php
// Temporary log viewer for NATStock (remove after debugging!)
// Usage: /viewlog.php?token=REPLACE_WITH_TOKEN
error_reporting(E_ALL);
ini_set('display_errors', '0');

$TOKEN = 'ns-debug-9f6c7a4'; // temporary token; delete this file after use
if (!isset($_GET['token']) || $_GET['token'] !== $TOKEN) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function readablePerms($path){
    clearstatcache(true, $path);
    if (!file_exists($path)) return 'missing';
    $perms = fileperms($path);
    $oct = substr(sprintf('%o', $perms), -4);
    return ($oct ?: '?').(is_writable($path)?' (writable)':' (ro)');
}

$root = __DIR__;
$app = is_dir($root.'/natstock') ? $root.'/natstock' : dirname($root).'/natstock';
$logDir = $app.'/storage/logs';
$candidates = [];
if (is_dir($logDir)) {
    $globbed = glob($logDir.'/laravel-*.log') ?: [];
    usort($globbed, fn($a,$b)=>strcmp($b,$a)); // newest first
    $candidates = array_merge($globbed, [$logDir.'/laravel.log']);
}

header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><meta charset="utf-8"><title>NATStock logs</title>';
echo '<style>body{font:13px/1.5 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Arial,sans-serif;padding:16px;max-width:1100px;margin:auto} pre{white-space:pre-wrap;background:#111;color:#ddd;padding:12px;border-radius:6px} code{background:#eee;padding:0 3px;border-radius:3px}</style>';
echo '<h2>Server diagnostics</h2>';
echo '<ul>';
echo '<li>PHP: '.h(PHP_VERSION).' ('.h(PHP_SAPI).')</li>';
echo '<li>Document root: <code>'.h($root).'</code></li>';
echo '<li>App path: <code>'.h($app).'</code></li>';
echo '<li>storage: '.h(readablePerms($app.'/storage')).'</li>';
echo '<li>storage/framework/sessions: '.h(readablePerms($app.'/storage/framework/sessions')).'</li>';
echo '<li>storage/framework/views: '.h(readablePerms($app.'/storage/framework/views')).'</li>';
echo '<li>bootstrap/cache: '.h(readablePerms($app.'/bootstrap/cache')).'</li>';
echo '</ul>';

echo '<h2>Latest Laravel log</h2>';
if (!$candidates) {
    echo '<p>No log files found in <code>'.h($logDir).'</code>.</p>';
} else {
    $shown = false;
    foreach ($candidates as $f) {
        if (!is_file($f)) continue;
        $content = @file($f, FILE_IGNORE_NEW_LINES) ?: [];
        $last = array_slice($content, -300);
        echo '<h3>'.h(basename($f)).'</h3>';
        echo '<pre>'.h(implode("\n", $last)).'</pre>';
        $shown = true;
        break;
    }
    if (!$shown) echo '<p>No readable log file.</p>';
}

echo '<p><small>Remember to delete viewlog.php after fixing the issue.</small></p>';