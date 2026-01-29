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

            // Identifica eventos tipo inscripción
            $table->boolean('is_registration')
                ->default(false)
                ->after('has_seat_mapping');

            // Precio único (inscripción o evento simple)
            $table->decimal('price', 10, 2)
                ->nullable()
                ->after('is_registration');

            // Cupo máximo permitido
            $table->unsignedInteger('max_capacity')
                ->nullable()
                ->after('price');

            // Plantilla / layout a usar
            $table->string('template')
                ->default('default')
                ->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn([
                'is_registration',
                'price',
                'max_capacity',
                'template'
            ]);
        });
    }
};
