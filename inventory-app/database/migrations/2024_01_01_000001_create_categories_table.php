<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id()->comment('รหัสหมวดหมู่');
            $table->string('name', 100)->comment('ชื่อหมวดหมู่');
            $table->text('note')->nullable()->comment('หมายเหตุ');
            $table->boolean('is_active')->default(true)->comment('สถานะการใช้งาน');
            $table->timestamps();

            $table->unique('name', 'categories_name_unique');
            $table->index(['is_active', 'name'], 'categories_is_active_name_index');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->comment('ตารางหมวดหมู่สินค้า');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
