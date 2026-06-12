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
        Schema::create('meet_room_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_room_id')->constrained()->cascadeOnDelete();
            $table->string('invitable_type')->nullable();
            $table->bigInteger('invitable_id')->nullable();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('token', 128)->unique();
            $table->enum('invitation_type', ['internal', 'external'])->default('external');
            $table->enum('status', ['pending', 'accepted', 'declined', 'attended'])->default('pending');
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->index(['invitable_type', 'invitable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meet_room_invitations');
    }
};
