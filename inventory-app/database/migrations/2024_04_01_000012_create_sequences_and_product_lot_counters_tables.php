<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->string('key', 32)->primary();
            $table->unsignedInteger('next_val');
            $table->timestamps();
        });

        Schema::create('product_lot_counters', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('next_no')->default(1);
            $table->timestamps();
        });

        DB::table('sequences')->insert([
            'key' => 'SKU',
            'next_val' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_lot_counters');
        Schema::dropIfExists('sequences');
    }
};
