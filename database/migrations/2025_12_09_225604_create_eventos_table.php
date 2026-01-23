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
        Schema::create('eventos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('total_asientos')->default(0);

            // Relaciones opcionales
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('phase_id')->nullable();
            $table->unsignedBigInteger('stage_id')->nullable();

            $table->string('modal_color')->nullable();
            $table->string('modal_selector')->nullable();
            $table->string('color_primario')->nullable();
            $table->string('color_acento')->nullable();

            $table->string('redirect_return')->nullable();
            $table->string('redirect_next')->nullable();
            $table->string('redirect_previous')->nullable();

            $table->string('svg_image')->nullable();
            $table->string('png_image')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
