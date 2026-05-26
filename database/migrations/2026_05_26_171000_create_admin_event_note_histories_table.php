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
        Schema::create('admin_event_note_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_event_note_id')->constrained('admin_event_notes')->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained('users');
            $table->json('old_values');
            $table->json('new_values');
            $table->timestamps();

            $table->index(['admin_event_note_id', 'created_at'], 'idx_note_history_note_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_event_note_histories');
    }
};
