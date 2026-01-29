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
        Schema::create('registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // FK UUID → eventos.id (UUID)
            $table->uuid('event_id');

            $table->string('team_name');

            // JSON para jugadores
            $table->json('players')->nullable();

            // Totales
            $table->decimal('subtotal', 10, 2);
            $table->decimal('commission', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            $table->timestamps();

            $table
                ->foreign('event_id')
                ->references('id')
                ->on('eventos')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
