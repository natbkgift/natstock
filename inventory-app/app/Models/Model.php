<?php
namespace App\Models;

use DateTime;
use PDO;

abstract class Model
{
    protected static string $table;

    public static function all(): array
    {
        $stmt = db()->query('SELECT * FROM '.static::$table.' ORDER BY id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?static
    {
        $stmt = db()->prepare('SELECT * FROM '.static::$table.' WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? static::fromArray($data) : null;
    }

    public static function fromArray(array $attributes): static
    {
        $model = new static();
        foreach ($attributes as $key => $value) {
            $model->{$key} = $value;
        }
        return $model;
    }
}
