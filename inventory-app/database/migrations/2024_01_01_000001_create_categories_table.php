<?php

return new class extends \Illuminate\Database\Migrations\Migration
{
    public function up(): void
    {
        \Illuminate\Support\Facades\Schema::create('categories', function (\Illuminate\Database\Schema\Blueprint $table) {
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
        \Illuminate\Support\Facades\Schema::dropIfExists('categories');
    }
};
