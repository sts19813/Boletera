<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->string('registration_form_mode', 20)->nullable()->after('template_form');
            $table->uuid('registration_form_id')->nullable()->after('registration_form_mode');
            $table->foreign('registration_form_id')->references('id')->on('registration_forms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropForeign(['registration_form_id']);
            $table->dropColumn(['registration_form_mode', 'registration_form_id']);
        });
    }
};
