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
        Schema::create('meet_room_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('participant_id');
            $table->string('display_name');
            $table->string('email')->nullable();
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->boolean('is_moderator')->default(false);
            $table->boolean('is_muted_audio')->default(false);
            $table->boolean('is_muted_video')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('participant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meet_room_participants');
    }
};
