<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('happened_at')->useCurrent();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
            $table->index('happened_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
