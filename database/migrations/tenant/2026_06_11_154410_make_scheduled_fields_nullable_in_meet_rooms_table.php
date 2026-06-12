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
        Schema::table('meet_rooms', function (Blueprint $table) {
            $table->date('scheduled_date')->nullable()->change();
            $table->time('scheduled_time')->nullable()->change();
            $table->unsignedInteger('duration_minutes')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meet_rooms', function (Blueprint $table) {
            $table->date('scheduled_date')->nullable(false)->change();
            $table->time('scheduled_time')->nullable(false)->change();
            $table->unsignedInteger('duration_minutes')->nullable(false)->default(60)->change();
        });
    }
};
