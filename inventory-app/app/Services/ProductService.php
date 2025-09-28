<?php
namespace App\Services;

use App\Models\Product;
use App\Support\Database;

class ProductService
{
    public function create(array $data): int
    {
        return Product::create($data);
    }

    public function update(int $id, array $data): void
    {
        Product::updateById($id, $data);
    }

    public function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function findOrCreateBySku(string $sku, array $data): Product
    {
        $existing = Product::findBySku($sku);
        if ($existing) {
            $this->update($existing->id, $data);
            return Product::find($existing->id);
        }

        $id = $this->create($data);
        return Product::find($id);
    }
}
