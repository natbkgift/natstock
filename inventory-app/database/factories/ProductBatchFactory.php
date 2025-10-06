<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductBatchFactory extends Factory
{
    protected $model = ProductBatch::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sub_sku' => $this->faker->unique()->bothify('SKU-###-LOT'),
            'expire_date' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'qty' => $this->faker->numberBetween(0, 200),
            'note' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
