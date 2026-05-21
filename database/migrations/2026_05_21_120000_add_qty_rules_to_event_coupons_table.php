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

        if (!Schema::hasColumn('event_coupons', 'min_qty')) {
            Schema::table('event_coupons', function (Blueprint $table) {
                $table->unsignedInteger('min_qty')->default(1)->after('discount_value');
            });
        }

        DB::table('event_coupons')
            ->whereNull('min_qty')
            ->update(['min_qty' => 1]);

        DB::statement("ALTER TABLE event_coupons MODIFY discount_type ENUM('percentage', 'fixed', 'unit_price') NOT NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('event_coupons')) {
            return;
        }

        DB::table('event_coupons')
            ->where('discount_type', 'unit_price')
            ->update([
                'discount_type' => 'fixed',
                'discount_value' => 0,
            ]);

        DB::statement("ALTER TABLE event_coupons MODIFY discount_type ENUM('percentage', 'fixed') NOT NULL");

        if (Schema::hasColumn('event_coupons', 'min_qty')) {
            Schema::table('event_coupons', function (Blueprint $table) {
                $table->dropColumn('min_qty');
            });
        }
    }
};
