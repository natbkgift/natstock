<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = Str::title($this->faker->unique()->randomElement([
            'ยา',
            'เวชภัณฑ์',
            'วิตามิน',
            'ผลิตภัณฑ์ทำความสะอาด',
            'อุปกรณ์การแพทย์',
        ]));

        return [
            'name' => $name,
            'note' => $this->faker->optional()->randomElement([
                'ใช้สำหรับสินค้าขายดีในคลัง',
                'หมวดหมู่สินค้าประจำไตรมาส',
                'สินค้าที่ต้องควบคุมอุณหภูมิ',
                'รายการใหม่ที่เพิ่งเพิ่มเข้ามา',
            ]),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
