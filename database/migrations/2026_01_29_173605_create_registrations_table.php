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

            $table->uuid('registration_instance_id');
            $table->string('team_name');

            // totales informativos
            $table->decimal('subtotal', 10, 2);
            $table->decimal('commission', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            $table->timestamps();

            $table->foreign('registration_instance_id')
                ->references('id')
                ->on('registration_instances')
                ->cascadeOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
