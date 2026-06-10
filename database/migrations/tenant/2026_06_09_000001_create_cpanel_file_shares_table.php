<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpanel_file_shares', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 64);
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('user_id');
            $table->string('path', 500);
            $table->string('name', 255);
            $table->enum('permission', ['view', 'edit'])->default('view');
            $table->boolean('requires_ack')->default(false);
            $table->string('ack_code', 16)->nullable();
            $table->timestamp('ack_code_expires_at')->nullable();
            $table->timestamp('ack_completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpanel_file_shares');
    }
};
