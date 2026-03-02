<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('registration_instances', function (Blueprint $table) {
            $table->string('payment_method')
                ->default('card')
                ->after('payment_intent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registration_instances', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
