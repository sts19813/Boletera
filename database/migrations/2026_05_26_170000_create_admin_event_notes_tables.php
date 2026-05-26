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
        Schema::create('admin_event_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id');
            $table->string('category', 40);
            $table->string('title', 150);
            $table->text('note');
            $table->string('counterparty', 120)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('eventos')->cascadeOnDelete();
            $table->index(['category', 'created_at']);
        });

        Schema::create('admin_event_note_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_event_note_id')->constrained('admin_event_notes')->cascadeOnDelete();
            $table->string('original_name', 255);
            $table->string('storage_path', 500);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();

            $table->index('admin_event_note_id', 'idx_note_attachment_note_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_event_note_attachments');
        Schema::dropIfExists('admin_event_notes');
    }
};
