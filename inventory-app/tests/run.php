<?php

declare(strict_types=1);

require __DIR__ . '/autoload.php';
require __DIR__ . '/framework.php';

use Tests\Framework\TestSuite;

$suite = TestSuite::getInstance();

// Load Pest configuration first.
$pestFile = __DIR__ . '/Pest.php';
if (file_exists($pestFile)) {
    require $pestFile;
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/Feature', FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() === 'php') {
        require $file->getPathname();
    }
}

$success = $suite->run();

exit($success ? 0 : 1);

