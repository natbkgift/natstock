<?php

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'App\\' => __DIR__ . '/../app/',
        'Tests\\' => __DIR__ . '/../tests/',
        'Carbon\\' => __DIR__ . '/Carbon/',
        'Illuminate\\' => __DIR__ . '/Illuminate/',
        'Symfony\\Component\\HttpFoundation\\' => __DIR__ . '/Symfony/Component/HttpFoundation/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $path = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
            if (is_file($path)) {
                require_once $path;
            }
        }
    }
});

require_once __DIR__ . '/helpers.php';

return true;
