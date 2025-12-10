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
         Schema::create('lots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->decimal('depth', 8, 2)->nullable();
            $table->decimal('front', 8, 2)->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->decimal('price_square_meter', 12, 2)->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->string('status')->default('disponible');
            $table->string('chepina')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
