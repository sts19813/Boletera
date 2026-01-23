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
        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Relación con stages
            $table->unsignedBigInteger('stage_id');
            $table->foreign('stage_id')->references('id')->on('stages')->onDelete('cascade');

            // Datos del boleto
            $table->string('name');                      // Nombre del boleto o categoría
            $table->string('type')->nullable();          // Ej: VIP, General, Preferente
            $table->decimal('total_price', 10, 2);       // Precio final del boleto
            $table->string('status')->default('active'); // Ej: active, inactive, sold_out

            $table->integer('stock')->nullable();        // Boletos disponibles
            $table->integer('sold')->default(0);         // Vendidos
            $table->dateTime('available_from')->nullable();
            $table->dateTime('available_until')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('purchased_at')->nullable();
            // Indica si es cortesía
            $table->boolean('is_courtesy')->default(false);

            $table->unsignedInteger('max_checkins')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
