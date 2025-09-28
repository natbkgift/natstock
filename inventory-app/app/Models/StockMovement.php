<?php
namespace App\Models;

use PDO;

class StockMovement extends Model
{
    protected static string $table = 'stock_movements';

    public int $id;
    public int $product_id;
    public string $type;
    public int $amount;
    public ?string $note = null;
    public ?int $actor_id = null;
    public ?string $happened_at = null;
    public ?string $created_at = null;

    public static function record(array $data): void
    {
        $stmt = db()->prepare('INSERT INTO stock_movements (product_id, type, amount, note, actor_id, happened_at, created_at)
            VALUES (:product_id, :type, :amount, :note, :actor_id, :happened_at, :created_at)');
        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            'product_id' => $data['product_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'note' => $data['note'] ?? '',
            'actor_id' => $data['actor_id'],
            'happened_at' => $data['happened_at'] ?? $now,
            'created_at' => $now,
        ]);
    }

    public static function latest(int $limit = 20): array
    {
        $stmt = db()->prepare('SELECT sm.*, p.name as product_name, u.name as actor_name FROM stock_movements sm
            LEFT JOIN products p ON p.id = sm.product_id
            LEFT JOIN users u ON u.id = sm.actor_id
            ORDER BY sm.happened_at DESC, sm.id DESC
            LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
