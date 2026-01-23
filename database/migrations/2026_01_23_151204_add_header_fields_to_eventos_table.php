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

            $table->date('event_date')->nullable()->after('name');
            $table->time('hora_inicio')->nullable()->after('event_date');
            $table->time('hora_fin')->nullable()->after('hora_inicio');

            $table->string('location')->nullable()->after('description');

            // Identifica si usa mapa de asientos o selección libre
            $table->boolean('has_seat_mapping')
                ->default(false)
                ->after('total_asientos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn([
                'event_date',
                'hora_inicio',
                'hora_fin',
                'location',
                'has_seat_mapping'
            ]);
        });
    }
};
