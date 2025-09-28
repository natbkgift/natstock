<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected string $model = Product::class;

    private static int $sequence = 1;

    public function definition(): array
    {
        $names = [
            ['ชื่อ' => 'หน้ากากอนามัยแบบผ้า', 'โน้ต' => 'เหมาะกับการแจกในโครงการชุมชน'],
            ['ชื่อ' => 'เจลแอลกอฮอล์ล้างมือ', 'โน้ต' => 'ควรเก็บให้ห่างจากความร้อน'],
            ['ชื่อ' => 'ถุงมือยางตรวจโรค', 'โน้ต' => 'สินค้าใช้แล้วต้องทิ้งทันที'],
            ['ชื่อ' => 'วิตามินรวมบำรุงร่างกาย', 'โน้ต' => 'นิยมสั่งซื้อในช่วงปลายปี'],
            ['ชื่อ' => 'สเปรย์ทำความสะอาดพื้นผิว', 'โน้ต' => 'ต้องปิดฝาก่อนเคลื่อนย้าย'],
        ];

        $index = (self::$sequence - 1) % count($names);
        $item = $names[$index];

        $id = self::$sequence;
        $sku = sprintf('SKU-%04d', self::$sequence);
        self::$sequence++;

        $reorder = [5, 10, 20, 15, 8][$index];
        $qty = [20, 120, 40, 12, 6][$index];

        $expireDates = [
            null,
            date('Y-m-d', strtotime('+180 days')),
            null,
            date('Y-m-d', strtotime('+90 days')),
            date('Y-m-d', strtotime('+30 days')),
        ];

        return [
            'id' => $id,
            'sku' => $sku,
            'name' => $item['ชื่อ'],
            'note' => $item['โน้ต'],
            'category_id' => Category::factory()->create()->getKey(),
            'cost_price' => round([45, 30, 12, 150, 85][$index], 2),
            'sale_price' => round([65, 55, 25, 210, 110][$index], 2),
            'expire_date' => $expireDates[$index],
            'reorder_point' => $reorder,
            'qty' => $qty,
            'is_active' => $qty > 0,
        ];
    }

    public function lowStock(): self
    {
        return $this->state(fn () => [
            'qty' => 3,
            'reorder_point' => 10,
        ]);
    }
}
