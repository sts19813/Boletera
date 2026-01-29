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
        Schema::create('players', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // FK UUID → registrations.id
            $table->uuid('registration_id');

            $table->string('name');

            // Relación con Cumbres
            $table->json('cumbres')->nullable();

            // Contacto
            $table->string('phone', 10);
            $table->string('email');

            // Golf
            $table->string('campo');
            $table->string('handicap');
            $table->string('ghin')->nullable();

            // Otros
            $table->string('shirt');
            $table->boolean('is_captain')->default(false);

            $table->timestamps();

            $table
                ->foreign('registration_id')
                ->references('id')
                ->on('registrations')
                ->cascadeOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
