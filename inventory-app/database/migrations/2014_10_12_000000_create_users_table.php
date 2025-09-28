<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('ชื่อผู้ใช้งาน');
            $table->string('email')->unique()->comment('อีเมล');
            $table->timestamp('email_verified_at')->nullable()->comment('เวลายืนยันอีเมล');
            $table->string('password')->comment('รหัสผ่าน');
            $table->enum('role', ['admin', 'staff', 'viewer'])->default('viewer')->comment('บทบาทผู้ใช้งาน');
            $table->rememberToken();
            $table->timestamps();

            $table->index('role', 'users_role_index');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->comment('ตารางผู้ใช้งานในระบบคลังสินค้า');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
