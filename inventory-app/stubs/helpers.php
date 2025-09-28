<?php

use Carbon\Carbon;

if (!function_exists('now')) {
    function now(): Carbon
    {
        return Carbon::today();
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
        };
    }
}
