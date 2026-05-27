<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->index()->after('id');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->index()->after('user_id');
        });

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->index()->after('email');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->index()->after('id');
            $table->dropUnique(['name', 'guard_name']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->unique(['tenant_id', 'name', 'guard_name']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->index()->after('id');
            $table->dropUnique(['name', 'guard_name']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->unique(['tenant_id', 'name', 'guard_name']);
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->index()->after('model_type');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->index()->after('model_type');
        });

        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->string('tenant_id')->nullable()->index()->after('role_id');
        });
    }

    public function down(): void
    {
        Schema::table('role_has_permissions', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'name', 'guard_name']);
            $table->dropColumn('tenant_id');
            $table->unique(['name', 'guard_name']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'name', 'guard_name']);
            $table->dropColumn('tenant_id');
            $table->unique(['name', 'guard_name']);
        });

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
