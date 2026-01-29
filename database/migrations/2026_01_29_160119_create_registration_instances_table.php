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
        Schema::create('registration_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_id');
            $table->string('email');
            $table->uuid('payment_intent_id');
            $table->string('qr_hash')->unique();
            $table->timestamp('registered_at');
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('eventos');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_instances');
    }
};
