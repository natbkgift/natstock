<?php
namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Support\Database;

class StockService
{
    public function move(int $productId, string $type, int $amount, string $note, int $actorId, ?string $happenedAt = null): void
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('จำนวนต้องไม่ติดลบ');
        }

        Database::transaction(function () use ($productId, $type, $amount, $note, $actorId, $happenedAt) {
            $product = Product::find($productId);
            if (!$product) {
                throw new \RuntimeException('ไม่พบสินค้า');
            }

            $currentQty = (int) $product->quantity;
            $newQty = match ($type) {
                'in' => $currentQty + $amount,
                'out' => max(0, $currentQty - $amount),
                'adjust' => $amount,
                default => throw new \InvalidArgumentException('ประเภทไม่ถูกต้อง'),
            };

            if ($type === 'adjust') {
                $delta = $amount - $currentQty;
            } elseif ($type === 'out') {
                $delta = -$amount;
            } else {
                $delta = $amount;
            }

            if ($type !== 'adjust') {
                Product::updateQuantity($productId, $newQty);
            } else {
                Product::updateQuantity($productId, $amount);
            }

            if ($type === 'adjust') {
                $movementAmount = abs($delta);
                $movementType = $delta >= 0 ? 'adjust' : 'adjust';
                $loggedAmount = $amount;
            } else {
                $movementAmount = $amount;
                $movementType = $type;
                $loggedAmount = $newQty;
            }

            StockMovement::record([
                'product_id' => $productId,
                'type' => $movementType,
                'amount' => $movementAmount,
                'note' => $note,
                'actor_id' => $actorId,
                'happened_at' => $happenedAt,
            ]);
        });
    }
}
