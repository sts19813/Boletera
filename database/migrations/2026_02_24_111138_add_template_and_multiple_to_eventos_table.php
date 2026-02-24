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
        Schema::table('eventos', function (Blueprint $table) {

            // Tipo de formulario que usará el evento
            $table->string('template_form')
                ->nullable()
                ->after('template');

            // Permite comprar múltiples inscripciones en una sola orden
            $table->boolean('allows_multiple_registrations')
                ->default(false)
                ->after('template_form');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn([
                'template_form',
                'allows_multiple_registrations'
            ]);
        });
    }
};
