<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meet_room_participants', function (Blueprint $table) {
            $table->boolean('jitsi_connected')->default(false)->after('is_moderator');
        });
    }

    public function down(): void
    {
        Schema::table('meet_room_participants', function (Blueprint $table) {
            $table->dropColumn('jitsi_connected');
        });
    }
};
