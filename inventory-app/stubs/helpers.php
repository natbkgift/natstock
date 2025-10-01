<?php

use Carbon\Carbon;

if (!function_exists('now')) {
    function now(): Carbon
    {
        return Carbon::today();
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        $base = dirname(__DIR__) . '/storage';

        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('bcrypt')) {
    function bcrypt(string $value): string
    {
        return password_hash($value, PASSWORD_BCRYPT);
    }
}

if (!function_exists('response')) {
    function response(): object
    {
        return new class
        {
            public function download(string $path, string $name): string
            {
                return sprintf('download:%s:%s', $path, $name);
            }

            public function streamDownload(callable $callback, string $name, array $headers = []): string
            {
                unset($headers);

                ob_start();
                $callback();
                $content = ob_get_clean();

                return sprintf('stream-download:%s:%s', $name, base64_encode($content ?: ''));
            }
        };
    }
}
