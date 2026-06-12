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
        Schema::create('meet_room_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meet_room_logs');
    }
};
