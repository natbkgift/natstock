<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'ยา',
                'note' => 'ยาสามัญประจำบ้านและยาที่ใช้ประจำ',
                'is_active' => true,
            ],
            [
                'name' => 'วิตามิน',
                'note' => 'วิตามินและอาหารเสริมบำรุงร่างกาย',
                'is_active' => true,
            ],
            [
                'name' => 'เวชภัณฑ์',
                'note' => 'อุปกรณ์ทางการแพทย์และของใช้ในคลัง',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
