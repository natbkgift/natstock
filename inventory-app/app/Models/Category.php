<?php
namespace App\Models;

use PDO;

class Category extends Model
{
    protected static string $table = 'categories';

    public int $id;
    public string $name;
    public ?string $note = null;
    public int $is_active;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public static function create(array $data): void
    {
        $stmt = db()->prepare('INSERT INTO categories (name, note, is_active, created_at, updated_at) VALUES (:name, :note, :is_active, :created_at, :updated_at)');
        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            'name' => $data['name'],
            'note' => $data['note'] ?? '',
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public static function updateById(int $id, array $data): void
    {
        $stmt = db()->prepare('UPDATE categories SET name = :name, note = :note, is_active = :is_active, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'name' => $data['name'],
            'note' => $data['note'] ?? '',
            'is_active' => $data['is_active'] ?? 1,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]);
    }

    public static function deleteById(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function active(): array
    {
        $stmt = db()->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
