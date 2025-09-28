<?php

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'App\\' => __DIR__ . '/../app/',
        'Illuminate\\' => __DIR__ . '/../stubs/Illuminate/',
        'Carbon\\' => __DIR__ . '/../stubs/Carbon/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $path = $baseDir . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($path)) {
                require $path;
            }
        }
    }
});

require_once __DIR__ . '/../stubs/helpers.php';
