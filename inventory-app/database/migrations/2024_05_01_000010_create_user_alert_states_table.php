<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_alert_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('alert_type', 32);
            $table->string('payload_hash', 64);
            $table->timestamp('snooze_until')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'alert_type', 'payload_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_alert_states');
    }
};
