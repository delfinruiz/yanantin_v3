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
        Schema::create('meet_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('room_code', 32)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['unique', 'recurrent'])->default('unique');
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');

            $table->boolean('waiting_room_enabled')->default(true);
            $table->string('waiting_room_video_url')->nullable();
            $table->text('waiting_room_message')->nullable();

            $table->boolean('allow_chat')->default(true);
            $table->boolean('allow_screen_share')->default(true);
            $table->boolean('allow_recording')->default(false);
            $table->boolean('require_password')->default(false);
            $table->string('password')->nullable();
            $table->unsignedInteger('max_participants')->nullable();
            $table->boolean('start_muted_audio')->default(false);
            $table->boolean('start_muted_video')->default(false);
            $table->boolean('lobby_enabled')->default(false);
            $table->boolean('break_out_rooms_enabled')->default(false);
            $table->boolean('whiteboard_enabled')->default(false);
            $table->boolean('subtitles_enabled')->default(false);
            $table->boolean('noise_suppression_enabled')->default(true);

            $table->string('redirect_url_on_leave')->nullable();
            $table->string('custom_background_url')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meet_rooms');
    }
};
