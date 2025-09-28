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
