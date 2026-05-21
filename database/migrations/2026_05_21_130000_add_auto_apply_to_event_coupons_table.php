<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_coupons')) {
            return;
        }

        if (!Schema::hasColumn('event_coupons', 'auto_apply')) {
            Schema::table('event_coupons', function (Blueprint $table) {
                $table->boolean('auto_apply')->default(false)->after('code');
            });
        }

        DB::statement('ALTER TABLE event_coupons MODIFY code VARCHAR(50) NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('event_coupons')) {
            return;
        }

        DB::table('event_coupons')
            ->whereNull('code')
            ->orderBy('created_at')
            ->get(['id'])
            ->each(function ($row, $index) {
                DB::table('event_coupons')
                    ->where('id', $row->id)
                    ->update(['code' => 'AUTO_' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)]);
            });

        DB::statement('ALTER TABLE event_coupons MODIFY code VARCHAR(50) NOT NULL');

        if (Schema::hasColumn('event_coupons', 'auto_apply')) {
            Schema::table('event_coupons', function (Blueprint $table) {
                $table->dropColumn('auto_apply');
            });
        }
    }
};
