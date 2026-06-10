<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cpanel_file_shares', function (Blueprint $table) {
            $table->unsignedBigInteger('size')->default(0)->after('name');
            $table->unsignedBigInteger('mtime')->default(0)->after('size');
        });
    }

    public function down(): void
    {
        Schema::table('cpanel_file_shares', function (Blueprint $table) {
            $table->dropColumn(['size', 'mtime']);
        });
    }
};
