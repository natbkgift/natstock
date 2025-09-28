<?php
namespace App\Support;

class Config
{
    protected static array $items = [];

    public static function load(): void
    {
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) {
                    continue;
                }
                [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
                static::$items[$key] = trim($value, "\"'");
            }
        }

        foreach (['APP_NAME', 'DB_CONNECTION', 'DB_DATABASE'] as $key) {
            $envValue = getenv($key);
            if ($envValue !== false) {
                static::$items[$key] = $envValue;
            }
        }

        static::$items['APP_NAME'] = static::$items['APP_NAME'] ?? 'ระบบคลังยา';
        static::$items['DB_CONNECTION'] = static::$items['DB_CONNECTION'] ?? 'sqlite';
        static::$items['DB_DATABASE'] = static::$items['DB_DATABASE'] ?? 'database/database.sqlite';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::$items[$key] ?? $default;
    }
}
