<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const ESTOM_TOURNAMENT_EVENT_ID = '019e8e99-47fe-700e-89f3-277f1790cfbf';
    private const ESTOM_TOURNAMENT_WHATSAPP_LINK = 'https://chat.whatsapp.com/DYjStJDRVO1Fy8mP0BtkZy?mode=gi_t';

    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->string('whatsapp_group_link')->nullable()->after('registration_form_id');
        });

        DB::table('eventos')
            ->where('id', self::ESTOM_TOURNAMENT_EVENT_ID)
            ->update(['whatsapp_group_link' => self::ESTOM_TOURNAMENT_WHATSAPP_LINK]);
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn('whatsapp_group_link');
        });
    }
};
