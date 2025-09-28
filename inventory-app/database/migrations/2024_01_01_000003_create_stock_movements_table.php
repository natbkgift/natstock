<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id()->comment('รหัสเหตุการณ์สต็อก');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('สินค้า');
            $table->enum('type', ['in', 'out', 'adjust'])->comment('ประเภทการเคลื่อนไหว');
            $table->unsignedInteger('qty')->comment('จำนวน');
            $table->text('note')->nullable()->comment('หมายเหตุ');
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('ผู้ปฏิบัติ');
            $table->timestampTz('happened_at')->useCurrent()->comment('เวลาเกิดเหตุการณ์');
            $table->timestamps();

            $table->index(['product_id', 'happened_at'], 'stock_movements_product_happened_at_index');
            $table->index('type', 'stock_movements_type_index');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->comment('ประวัติการเคลื่อนไหวของสต็อกสินค้า');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
