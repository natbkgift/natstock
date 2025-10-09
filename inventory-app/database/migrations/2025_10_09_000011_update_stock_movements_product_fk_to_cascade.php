<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Update FK to cascade on delete so a product can be deleted even if it has stock movements.
     */
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            // Drop existing foreign key constraint on product_id (default name: stock_movements_product_id_foreign)
            $table->dropForeign(['product_id']);

            // Recreate foreign key with cascade on delete
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
        });
    }

    /**
     * Revert FK to restrict on delete (original behavior) if needed.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['product_id']);

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->restrictOnDelete();
        });
    }
};
