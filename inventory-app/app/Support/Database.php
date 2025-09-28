<?php
namespace App\Support;

use PDO;
use PDOException;

class Database
{
    protected static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (static::$connection) {
            return static::$connection;
        }

        Config::load();
        $connection = Config::get('DB_CONNECTION', 'sqlite');

        if ($connection !== 'sqlite') {
            throw new PDOException('ระบบนี้รองรับเฉพาะ sqlite ในโหมดออฟไลน์');
        }

        $database = base_path(Config::get('DB_DATABASE', 'database/database.sqlite'));
        $dir = dirname($database);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        static::$connection = new PDO('sqlite:'.$database);
        static::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        static::$connection->exec('PRAGMA foreign_keys = ON');

        return static::$connection;
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = static::connection();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
