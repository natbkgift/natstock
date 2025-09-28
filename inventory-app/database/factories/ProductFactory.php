<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $expireDate = $this->faker->optional()->dateTimeBetween('now', '+1 year');

        return [
            'sku' => Str::upper('SKU-' . $this->faker->unique()->bothify('???###')),
            'name' => $this->faker->randomElement([
                'หน้ากากอนามัยแบบผ้า',
                'เจลแอลกอฮอล์ล้างมือ',
                'ถุงมือยางตรวจโรค',
                'วิตามินรวมบำรุงร่างกาย',
                'สเปรย์ทำความสะอาดพื้นผิว',
            ]),
            'note' => $this->faker->optional()->randomElement([
                'สินค้าขายดีในช่วงปลายปี',
                'ต้องจัดเก็บในที่แห้งและเย็น',
                'ใช้คู่กับอุปกรณ์เสริมเฉพาะทาง',
            ]),
            'category_id' => Category::factory(),
            'cost_price' => $this->faker->randomFloat(2, 5, 500),
            'sale_price' => $this->faker->randomFloat(2, 10, 800),
            'expire_date' => $expireDate ? $expireDate->format('Y-m-d') : null,
            'reorder_point' => $this->faker->numberBetween(10, 150),
            'qty' => $this->faker->numberBetween(0, 300),
            'is_active' => $this->faker->boolean(90),
        ];
    }

    public function lowStock(): self
    {
        return $this->state(fn () => [
            'qty' => 5,
            'reorder_point' => 10,
        ]);
    }
}
