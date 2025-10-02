<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function row($k,$v){ echo '<tr><td style="padding:4px 8px"><b>'.h($k).'</b></td><td style="padding:4px 8px">'.h($v).'</td></tr>'; }

echo '<!doctype html><meta charset="utf-8"><title>NATStock deploy diag</title>';
echo '<div style="font:14px/1.4 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Arial,sans-serif;max-width:900px;margin:16px auto">';
echo '<h2>Deployment diagnostics</h2>';

$docroot = __DIR__;
$cand = [
  $docroot.'/natstock',
  dirname($docroot).'/natstock',
];

echo '<table border="1" cellspacing="0" cellpadding="0" style="border-collapse:collapse">';
row('Document root', $docroot);
foreach ($cand as $i=>$p) {
  $exists = is_dir($p) ? 'YES' : 'NO';
  $boot = is_file($p.'/bootstrap/app.php') ? 'YES' : 'NO';
  $vendor = is_file($p.'/vendor/autoload.php') ? 'YES' : 'NO';
  row("Candidate #".($i+1)." path", $p);
  row(' - exists', $exists);
  row(' - has bootstrap/app.php', $boot);
  row(' - has vendor/autoload.php', $vendor);
}
echo '</table>';

$found = null;
foreach ($cand as $p) { if (is_file($p.'/bootstrap/app.php')) { $found = $p; break; } }

if ($found) {
  echo '<h3>App folder detected</h3>';
  echo '<p>Path: <code>'.h($found)."</code></p>";
  if (!is_file($found.'/vendor/autoload.php')) {
    echo '<p style="color:#b00">Missing <code>vendor/autoload.php</code>. Please run <a href="/extract.php">/extract.php</a> until it shows <b>Extraction complete</b>, then reload this page.</p>';
  } else {
    echo '<p style="color:green">vendor/autoload.php is present ✅</p>';
  }

  // Show top-level entries for quick inspection
  $entries = @scandir($found) ?: [];
  $entries = array_values(array_filter($entries, fn($x)=>$x!=='.' && $x!=='..'));
  echo '<details><summary>Top-level entries in app folder</summary><pre>'.h(implode("\n", array_slice($entries, 0, 200))).'</pre></details>';
} else {
  echo '<h3 style="color:#b00">Could not locate app folder</h3>';
  echo '<p>Expected a folder named <code>natstock/</code> alongside this file. Please upload and run <a href="/extract.php">/extract.php</a> until it reaches 100%.</p>';
}

echo '<p><small>After installation completes, remove diag.php, extract.php, and install.php from the document root.</small></p>';
echo '</div>';
