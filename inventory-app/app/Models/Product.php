<?php
namespace App\Models;

use PDO;

class Product extends Model
{
    protected static string $table = 'products';

    public int $id;
    public string $sku;
    public string $name;
    public ?string $note = null;
    public ?int $category_id = null;
    public float $cost_price;
    public float $sale_price;
    public ?string $expire_date = null;
    public int $reorder_point;
    public int $is_active;
    public int $quantity;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public static function create(array $data): int
    {
        $stmt = db()->prepare('INSERT INTO products (sku, name, note, category_id, cost_price, sale_price, expire_date, reorder_point, is_active, quantity, created_at, updated_at)
            VALUES (:sku, :name, :note, :category_id, :cost_price, :sale_price, :expire_date, :reorder_point, :is_active, :quantity, :created_at, :updated_at)');
        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            'sku' => $data['sku'],
            'name' => $data['name'],
            'note' => $data['note'] ?? '',
            'category_id' => $data['category_id'],
            'cost_price' => $data['cost_price'],
            'sale_price' => $data['sale_price'],
            'expire_date' => $data['expire_date'] ?? null,
            'reorder_point' => $data['reorder_point'],
            'is_active' => $data['is_active'],
            'quantity' => $data['quantity'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) db()->lastInsertId();
    }

    public static function updateById(int $id, array $data): void
    {
        $stmt = db()->prepare('UPDATE products SET sku = :sku, name = :name, note = :note, category_id = :category_id, cost_price = :cost_price,
            sale_price = :sale_price, expire_date = :expire_date, reorder_point = :reorder_point, is_active = :is_active, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'sku' => $data['sku'],
            'name' => $data['name'],
            'note' => $data['note'] ?? '',
            'category_id' => $data['category_id'],
            'cost_price' => $data['cost_price'],
            'sale_price' => $data['sale_price'],
            'expire_date' => $data['expire_date'] ?? null,
            'reorder_point' => $data['reorder_point'],
            'is_active' => $data['is_active'],
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]);
    }

    public static function updateQuantity(int $id, int $quantity): void
    {
        $stmt = db()->prepare('UPDATE products SET quantity = :quantity, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'quantity' => $quantity,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]);
    }

    public static function findBySku(string $sku): ?self
    {
        $stmt = db()->prepare('SELECT * FROM products WHERE sku = :sku LIMIT 1');
        $stmt->execute(['sku' => $sku]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? static::fromArray($data) : null;
    }

    public static function search(string $keyword): array
    {
        $stmt = db()->prepare('SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.sku LIKE :keyword OR p.name LIKE :keyword ORDER BY p.name');
        $stmt->execute(['keyword' => '%'.$keyword.'%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function withCategory(): array
    {
        $stmt = db()->query('SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
