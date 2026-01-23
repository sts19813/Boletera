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
        Schema::create('ticket_svg_mappings', function (Blueprint $table) {
            $table->id();

            // UUID porque eventos.id es UUID
            $table->uuid('evento_id');

            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('phase_id')->nullable();
            $table->unsignedBigInteger('stage_id')->nullable();

            $table->uuid('ticket_id')->nullable();

            // SVG
            $table->string('svg_selector'); // Ej: #seat-A1, .zona-vip, path[data-id="23"]

            // Comportamiento
            $table->boolean('redirect')->default(false);
            $table->string('redirect_url')->nullable();

            // Colores
            $table->string('color')->nullable();        // Color base
            $table->string('color_active')->nullable(); // Hover / activo

            $table->timestamps();

            // Foreign keys
            $table->foreign('evento_id')
                ->references('id')
                ->on('eventos')
                ->onDelete('cascade');

            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_svg_mappings');
    }
};
