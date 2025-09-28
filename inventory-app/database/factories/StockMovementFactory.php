<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    protected string $model = StockMovement::class;

    private static int $sequence = 1;

    public function definition(): array
    {
        $types = ['in', 'out', 'adjust'];
        $notes = [
            'รับเข้าจากซัพพลายเออร์',
            'ตัดจ่ายเพื่อนำไปใช้ในห้องพยาบาล',
            'ปรับยอดหลังตรวจนับประจำเดือน',
        ];

        $index = (self::$sequence - 1) % count($types);
        $id = self::$sequence;
        self::$sequence++;

        return [
            'id' => $id,
            'product_id' => Product::factory()->create()->getKey(),
            'type' => $types[$index],
            'qty' => [40, 15, 5][$index],
            'note' => $notes[$index],
            'actor_id' => User::factory()->create()->getKey(),
            'happened_at' => date('Y-m-d H:i:s'),
        ];
    }
}
