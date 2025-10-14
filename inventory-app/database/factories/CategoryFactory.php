<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    private static int $index = 0;

    public function definition(): array
    {
        $options = [
            ['ชื่อ' => 'ยา', 'หมายเหตุ' => 'หมวดสินค้าสามัญประจำบ้าน'],
            ['ชื่อ' => 'วิตามิน', 'หมายเหตุ' => 'หมวดอาหารเสริมเพื่อสุขภาพ'],
            ['ชื่อ' => 'เวชภัณฑ์', 'หมายเหตุ' => 'อุปกรณ์การแพทย์และของใช้ในคลัง'],
            ['ชื่อ' => 'อุปกรณ์ปฐมพยาบาล', 'หมายเหตุ' => 'สำหรับจัดชุดปฐมพยาบาล'],
            ['ชื่อ' => 'ผลิตภัณฑ์ทำความสะอาด', 'หมายเหตุ' => 'สินค้าเพื่อสุขอนามัยและฆ่าเชื้อ'],
        ];

        $index = self::$index % count($options);
        $item = $options[$index];
        $id = self::$index + 1;
        self::$index++;

        $name = $item['ชื่อ'];

        if ($id > count($options)) {
            $name .= ' #' . $id;
        }

        return [
            'id' => $id,
            'name' => $name,
            'note' => $item['หมายเหตุ'],
            'is_active' => true,
        ];
    }
}
