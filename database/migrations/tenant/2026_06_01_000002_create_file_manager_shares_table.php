<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_manager_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_item_id')->constrained('file_manager_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('permission', ['view', 'edit'])->default('view');
            $table->boolean('requires_ack')->default(false);
            $table->string('ack_code', 16)->nullable();
            $table->timestamp('ack_code_expires_at')->nullable();
            $table->timestamp('ack_completed_at')->nullable();
            $table->timestamps();

            $table->unique(['file_item_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_manager_shares');
    }
};
