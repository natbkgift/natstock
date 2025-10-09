<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ติดตั้งใหม่: ไม่ใส่ตัวอย่างหมวดหมู่/สินค้า

        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'ผู้ดูแลระบบตัวอย่าง',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        $this->command?->info('สร้างผู้ดูแลระบบตัวอย่างเรียบร้อยแล้ว');
        Log::info('สร้างผู้ใช้เริ่มต้นสำหรับระบบคลังสินค้าในภาษาไทยแล้ว', ['user_id' => $user->id]);
    }
}
