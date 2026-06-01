<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('email');
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('domain');
            $table->integer('quota')->default(250);
            $table->float('used')->default(0);
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->text('encrypted_password')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_accounts');
    }
};
