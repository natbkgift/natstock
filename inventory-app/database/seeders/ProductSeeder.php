<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::query()->pluck('id', 'name');
        $today = Carbon::today();

        $products = [
            [
                'sku' => 'MED-PCM500',
                'name' => 'พาราเซตามอล 500 มก.',
                'note' => 'ยาลดไข้และบรรเทาอาการปวด',
                'category' => 'ยา',
                'cost_price' => '12.50',
                'sale_price' => '18.00',
                'expire_date' => $today->copy()->addMonths(18)->toDateString(),
                'reorder_point' => 50,
                'qty' => 120,
            ],
            [
                'sku' => 'VIT-C1000',
                'name' => 'วิตามินซี 1000 มก.',
                'note' => 'เม็ดฟู่รสส้ม เสริมภูมิคุ้มกัน',
                'category' => 'วิตามิน',
                'cost_price' => '8.75',
                'sale_price' => '15.00',
                'expire_date' => $today->copy()->addMonths(6)->toDateString(),
                'reorder_point' => 80,
                'qty' => 60,
            ],
            [
                'sku' => 'GAUZE-10',
                'name' => 'ผ้าก๊อซปลอดเชื้อ ขนาด 10x10 ซม.',
                'note' => 'เวชภัณฑ์สำหรับทำแผล',
                'category' => 'เวชภัณฑ์',
                'cost_price' => '5.20',
                'sale_price' => '9.50',
                'expire_date' => null,
                'reorder_point' => 100,
                'qty' => 90,
            ],
            [
                'sku' => 'BAND-SMALL',
                'name' => 'พลาสเตอร์ปิดแผลขนาดเล็ก',
                'note' => 'สินค้าที่ใกล้ถึงจุดสั่งซื้อซ้ำ',
                'category' => 'เวชภัณฑ์',
                'cost_price' => '3.00',
                'sale_price' => '5.00',
                'expire_date' => null,
                'reorder_point' => 40,
                'qty' => 30,
            ],
        ];

        foreach ($products as $product) {
            $categoryId = $categories[$product['category']] ?? null;

            if (! $categoryId) {
                continue;
            }

            Product::query()->updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'name' => $product['name'],
                    'note' => $product['note'],
                    'category_id' => $categoryId,
                    'cost_price' => $product['cost_price'],
                    'sale_price' => $product['sale_price'],
                    'expire_date' => $product['expire_date'],
                    'reorder_point' => $product['reorder_point'],
                    'qty' => $product['qty'],
                    'is_active' => true,
                ]
            );
        }
    }
}
