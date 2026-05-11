<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ticket_instances', function (Blueprint $table) {
            $table->uuid('coupon_id')->nullable()->after('payment_method');
            $table->string('coupon_code', 50)->nullable()->after('coupon_id');
            $table->decimal('coupon_discount_percent', 5, 2)->nullable()->after('coupon_code');
            $table->decimal('coupon_discount_amount', 10, 2)->nullable()->after('coupon_discount_percent');

            $table->index('coupon_id');
            $table->index('coupon_code');

            $table->foreign('coupon_id')->references('id')->on('event_coupons')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_instances', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropIndex(['coupon_id']);
            $table->dropIndex(['coupon_code']);
            $table->dropColumn([
                'coupon_id',
                'coupon_code',
                'coupon_discount_percent',
                'coupon_discount_amount',
            ]);
        });
    }
};
