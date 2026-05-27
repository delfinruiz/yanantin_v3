<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
        });

        $this->migrateExistingPlanData();
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });
    }

    protected function migrateExistingPlanData(): void
    {
        $slugToId = DB::table('plans')->pluck('id', 'slug');

        DB::table('tenants')
            ->whereNotNull('plan')
            ->where('plan', '!=', '')
            ->orderBy('id')
            ->each(function ($tenant) use ($slugToId) {
                $planId = $slugToId[$tenant->plan] ?? null;

                if ($planId) {
                    DB::table('tenants')
                        ->where('id', $tenant->id)
                        ->update(['plan_id' => $planId]);
                }
            });
    }
};
