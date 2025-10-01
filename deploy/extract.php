<?php
// Incremental extractor for shared hosting (avoids timeouts by extracting in small batches).
// 1) Upload this file and one of: natstock-cpanel.zip | natstock-deploy.zip | natstock.zip to document root (htdocs)
// 2) Open /extract.php; it will auto-continue until done
// 3) When finished, it will flatten public_html/ and point you to /install.php

error_reporting(E_ALL);
ini_set('display_errors', '1');

// If split parts exist, join them into a single archive file first and return its path
function join_parts_if_any(string $dir): ?string {
    $candidates = [
        // Standard split pattern
        ['glob' => $dir.'/natstock-cpanel.zip.part*', 'target' => $dir.'/natstock-cpanel.zip'],
        // Text-suffixed parts to bypass host filters
        ['glob' => $dir.'/natstock-cpanel.part*.txt', 'target' => $dir.'/natstock-cpanel.zip'],
        // Generic part naming without extension
        ['glob' => $dir.'/natstock-cpanel.part*', 'target' => $dir.'/natstock-cpanel.zip'],
        // Alternative names, if needed
        ['glob' => $dir.'/natstock-deploy.zip.part*', 'target' => $dir.'/natstock-deploy.zip'],
        ['glob' => $dir.'/natstock.zip.part*', 'target' => $dir.'/natstock.zip'],
    ];
    foreach ($candidates as $c) {
        $parts = glob($c['glob']);
        if ($parts && count($parts) > 0) {
            natsort($parts);
            // Try writing to .zip, then fall back to .bin and .dat
            $targets = [$c['target'], preg_replace('/\.zip$/', '.bin', $c['target']), preg_replace('/\.zip$/', '.dat', $c['target'])];
            foreach ($targets as $target) {
                if (@filesize($target) > 1024) { return $target; }
                $out = @fopen($target, 'wb');
                if ($out === false) { continue; }
                foreach ($parts as $p) {
                    $in = @fopen($p, 'rb');
                    if ($in) {
                        while (!feof($in)) { fwrite($out, fread($in, 1048576)); }
                        fclose($in);
                    }
                }
                fclose($out);
                if (@filesize($target) > 1024) { return $target; }
            }
        }
    }
    return null;
}

$joined = join_parts_if_any(__DIR__);

// Find deployment zip (ZipArchive can open .zip/.bin/.dat if content is ZIP)
$candidates = [
    __DIR__.'/natstock-cpanel.zip', __DIR__.'/natstock-deploy.zip', __DIR__.'/natstock.zip',
    __DIR__.'/natstock-cpanel.bin', __DIR__.'/natstock-deploy.bin', __DIR__.'/natstock.bin',
    __DIR__.'/natstock-cpanel.dat', __DIR__.'/natstock-deploy.dat', __DIR__.'/natstock.dat'
];
if ($joined && is_file($joined)) { array_unshift($candidates, $joined); }
$zipFile = null;
foreach ($candidates as $c) { if (is_file($c)) { $zipFile = $c; break; } }

if (!$zipFile) {
    $zips = array_merge(glob(__DIR__.'/*.zip') ?: [], glob(__DIR__.'/*.bin') ?: [], glob(__DIR__.'/*.dat') ?: []);
    echo '<h3>No deployment zip found.</h3><p>Upload natstock-cpanel.zip or split parts like <code>natstock-cpanel.zip.part00</code> or <code>natstock-cpanel.part00.txt</code> to this folder, then reload this page.</p>';
    if ($zips) { echo '<p>Found archives:</p><ul>'; foreach ($zips as $z) echo '<li>'.htmlentities(basename($z)).'</li>'; echo '</ul>'; }
    exit;
}

$start = isset($_GET['s']) ? max(0, (int)$_GET['s']) : 0;
$batch = isset($_GET['b']) ? max(50, min(1500, (int)$_GET['b'])) : 500;

$zip = new ZipArchive();
if ($zip->open($zipFile) !== true) { exit('Failed to open zip: '.htmlentities(basename($zipFile))); }
$total = $zip->numFiles;

for ($i = $start; $i < min($start + $batch, $total); $i++) {
    $stat = $zip->statIndex($i);
    if (!$stat) continue;
    $name = $stat['name'];
    // Normalize paths
    $name = str_replace('\\', '/', $name);
    if ($name === '' || $name === './') continue;
    if (substr($name, -1) === '/') {
        @mkdir(__DIR__.'/'.$name, 0755, true);
        continue;
    }
    $dest = __DIR__ . '/' . $name;
    @mkdir(dirname($dest), 0755, true);
    // Try direct extract of a single entry
    $ok = false;
    if (method_exists($zip, 'extractTo')) {
        $ok = @$zip->extractTo(__DIR__, [$name]);
    }
    if (!$ok) {
        $stream = $zip->getStream($name);
        if ($stream) {
            $out = fopen($dest, 'w');
            while (!feof($stream)) { fwrite($out, fread($stream, 8192)); }
            fclose($out); fclose($stream);
        }
    }
}

$zip->close();

if ($start + $batch < $total) {
    $next = $start + $batch;
    $pct = $total ? floor(100 * $next / $total) : 100;
    echo '<h3>Extracting... '.intval($pct)."% (".$next.' / '.$total.")</h3>";
    echo '<p>Do not close this page. It will continue automatically.</p>';
    $url = htmlspecialchars($_SERVER['PHP_SELF']).'?s='.$next.'&b='.$batch;
    echo '<meta http-equiv="refresh" content="0.8;url='.$url.'">';
    echo '<p><a href="'.$url.'">Continue</a></p>';
    exit;
}

// Finalize: flatten public_html/ -> current dir and remove the folder
$publicHtml = __DIR__ . '/public_html';
if (is_dir($publicHtml)) {
    // Move children to root
    $it = new DirectoryIterator($publicHtml);
    foreach ($it as $node) {
        if ($node->isDot()) continue;
        $src = $node->getPathname();
        $dst = __DIR__ . '/' . $node->getFilename();
        if ($node->isDir()) {
            @mkdir($dst, 0755, true);
            $rit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($rit as $sub) {
                $rel = substr($sub->getPathname(), strlen($src)+1);
                $to = $dst . '/' . $rel;
                if ($sub->isDir()) { @mkdir($to, 0755, true); }
                else { @mkdir(dirname($to), 0755, true); @rename($sub->getPathname(), $to); }
            }
        } else {
            @rename($src, $dst);
        }
    }
    // Remove empty public_html
    $rit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($publicHtml, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($rit as $path) {
        $path->isDir() ? @rmdir($path->getPathname()) : @unlink($path->getPathname());
    }
    @rmdir($publicHtml);
}

echo '<h3>Extraction complete.</h3><p>Next step: <a href="/install.php">/install.php</a></p><p>Finally, delete extract.php and the zip file.</p>';
