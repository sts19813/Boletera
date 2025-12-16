<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id'); // tipo de boleto
            $table->string('email');
            $table->timestamp('purchased_at');
            $table->string('qr_hash')->unique();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets');
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
