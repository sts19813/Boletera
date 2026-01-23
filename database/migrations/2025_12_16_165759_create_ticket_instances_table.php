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
        Schema::create('ticket_instances', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->uuid('ticket_id');

            $table->string('email');

            $table->timestamp('purchased_at');

            $table->string('qr_hash')->unique();           

            $table->timestamp('used_at')->nullable();

            $table->string('payment_intent_id')->nullable();

            $table->string('reference')->nullable();

            $table->enum('sale_channel', ['stripe', 'taquilla'])
                ->default('stripe');

            $table->enum('payment_method', ['card', 'cash'])
                ->nullable();

            $table->timestamps();

            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_instances');
    }
};
