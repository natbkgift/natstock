<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => $this->faker->randomElement(['in', 'out', 'adjust']),
            'qty' => $this->faker->numberBetween(1, 200),
            'note' => $this->faker->optional()->randomElement([
                'ปรับยอดจากการนับสต็อก',
                'รับเข้าจากซัพพลายเออร์',
                'ตัดจ่ายออกเพื่อใช้งานด่วน',
            ]),
            'actor_id' => User::factory(),
            'happened_at' => $this->faker->dateTimeBetween('-2 months', 'now', 'UTC'),
        ];
    }
}
