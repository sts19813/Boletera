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
        Schema::table('ticket_instances', function (Blueprint $table) {
            $table->uuid('event_id')->after('id');
            $table->string('nombre')->after('event_id');
            $table->string('celular', 20)->nullable()->after('nombre');

            $table->foreign('event_id')
                ->references('id')
                ->on('eventos')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_instances', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropColumn(['event_id', 'nombre', 'celular']);
        });
    }
};
