<?php

return new class extends \Illuminate\Database\Migrations\Migration
{
    public function up(): void
    {
        \Illuminate\Support\Facades\Schema::create('products', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id()->comment('รหัสสินค้า');
            $table->string('sku', 64)->comment('รหัสสินค้า');
            $table->string('name', 150)->comment('ชื่อสินค้า');
            $table->text('note')->nullable()->comment('หมายเหตุ');
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('หมวดหมู่สินค้า');
            $table->decimal('cost_price', 12, 2)->default(0)->comment('ราคาทุน')->check('cost_price >= 0');
            $table->decimal('sale_price', 12, 2)->default(0)->comment('ราคาขาย')->check('sale_price >= 0');
            $table->date('expire_date')->nullable()->comment('วันหมดอายุ');
            $table->unsignedInteger('reorder_point')->default(0)->comment('จุดสั่งซื้อซ้ำ')->check('reorder_point >= 0');
            $table->integer('qty')->default(0)->comment('ปริมาณคงเหลือ')->check('qty >= 0');
            $table->boolean('is_active')->default(true)->comment('สถานะการใช้งาน');
            $table->timestamps();

            $table->unique('sku', 'products_sku_unique');
            $table->index('category_id', 'products_category_id_index');
            $table->index('expire_date', 'products_expire_date_index');
            $table->index('reorder_point', 'products_reorder_point_index');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->comment('ตารางสินค้า');
        });
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('products');
    }
};
