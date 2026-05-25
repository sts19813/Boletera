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
        Schema::create('queue_mail_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 80);
            $table->string('recipient');
            $table->string('reference')->nullable()->index();
            $table->string('queue_name', 80)->default('ticket-delivery')->index();
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('queued_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('failed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_mail_tasks');
    }
};

