<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ticket_instances', function (Blueprint $table) {

            // 1️⃣ payment_intent_id pasa a nullable
            $table->string('payment_intent_id')
                  ->nullable()
                  ->change();

            // 2️⃣ reference (si no existe)
            if (!Schema::hasColumn('ticket_instances', 'reference')) {
                $table->string('reference')->after('payment_intent_id');
            }

            // 3️⃣ sale_channel
            if (!Schema::hasColumn('ticket_instances', 'sale_channel')) {
                $table->enum('sale_channel', ['stripe', 'taquilla'])
                      ->default('stripe')
                      ->after('reference');
            }

            // 4️⃣ payment_method
            if (!Schema::hasColumn('ticket_instances', 'payment_method')) {
                $table->enum('payment_method', ['card', 'cash'])
                      ->nullable()
                      ->after('sale_channel');
            }
        });

        /**
         * 5️⃣ BACKFILL DATOS EXISTENTES (MUY IMPORTANTE)
         * No rompe ventas ya hechas
         */
        DB::table('ticket_instances')
            ->whereNull('reference')
            ->update([
                'reference' => DB::raw('payment_intent_id'),
                'sale_channel' => 'stripe',
                'payment_method' => 'card',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_instances', function (Blueprint $table) {
            // rollback lógico, no destructivo
            $table->string('payment_intent_id')->nullable(false)->change();
        });
    }
};
