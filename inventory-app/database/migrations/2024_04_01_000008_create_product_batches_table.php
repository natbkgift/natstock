<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('lot_no', 16);
            $table->date('expire_date')->nullable();
            $table->unsignedInteger('qty')->default(0);
            $table->dateTime('received_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'lot_no']);
            $table->index(['product_id', 'expire_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
