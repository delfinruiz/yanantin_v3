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
        Schema::create('cpanel_file_share_links', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 64);
            $table->unsignedBigInteger('owner_id');
            $table->string('token', 64)->unique();
            $table->string('path', 500);
            $table->string('name', 255);
            $table->enum('permission', ['view', 'edit'])->default('view');
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('downloads')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'owner_id']);
            $table->foreign('owner_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpanel_file_share_links');
    }
};
