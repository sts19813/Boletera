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
         Schema::create('ticket_checkins', function (Blueprint $table) {
            $table->id();

            $table->uuid('ticket_instance_id')->nullable();
            $table->string('hash')->nullable();

            $table->enum('result', ['success', 'used', 'invalid']);
            $table->string('message')->nullable();

            $table->timestamp('scanned_at')->useCurrent();
            $table->string('scanner_ip')->nullable();

            $table->timestamps();

            $table->index('ticket_instance_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_checkins');
    }
};
