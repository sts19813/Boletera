<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('tickets', 'stage_id')) {
            try {
                Schema::table('tickets', function (Blueprint $table) {
                    $table->dropForeign(['stage_id']);
                });
            } catch (\Throwable $e) {
                // Ignorar si la FK no existe en este entorno.
            }
        }

        $driver = Schema::getConnection()->getDriverName();
        if (Schema::hasColumn('tickets', 'stage_id')) {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement('ALTER TABLE tickets MODIFY stage_id BIGINT UNSIGNED NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE tickets ALTER COLUMN stage_id DROP NOT NULL');
            }
        }

        if (!Schema::hasColumn('tickets', 'event_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->uuid('event_id')
                    ->nullable()
                    ->after('stage_id');

                $table->index('event_id');
            });
        }

        if (Schema::hasTable('ticket_svg_mappings')) {
            $mappings = DB::table('ticket_svg_mappings')
                ->whereNotNull('ticket_id')
                ->whereNotNull('evento_id')
                ->select('ticket_id', DB::raw('MIN(evento_id) as evento_id'))
                ->groupBy('ticket_id')
                ->get();

            foreach ($mappings as $mapping) {
                DB::table('tickets')
                    ->where('id', $mapping->ticket_id)
                    ->whereNull('event_id')
                    ->update(['event_id' => $mapping->evento_id]);
            }
        }

        if (Schema::hasColumn('tickets', 'stage_id') && Schema::hasColumn('eventos', 'stage_id')) {
            $stageToEvent = DB::table('eventos')
                ->whereNotNull('stage_id')
                ->orderBy('created_at')
                ->get(['id', 'stage_id'])
                ->groupBy('stage_id')
                ->map(fn($rows) => $rows->first()->id);

            $ticketsWithoutEvent = DB::table('tickets')
                ->whereNull('event_id')
                ->whereNotNull('stage_id')
                ->get(['id', 'stage_id']);

            foreach ($ticketsWithoutEvent as $ticket) {
                $eventId = $stageToEvent->get($ticket->stage_id);

                if (!$eventId) {
                    continue;
                }

                DB::table('tickets')
                    ->where('id', $ticket->id)
                    ->whereNull('event_id')
                    ->update(['event_id' => $eventId]);
            }
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign('event_id')
                ->references('id')
                ->on('eventos')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('tickets', 'event_id')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropIndex(['event_id']);
            $table->dropColumn('event_id');
        });

        $driver = Schema::getConnection()->getDriverName();
        if (Schema::hasColumn('tickets', 'stage_id')) {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement('ALTER TABLE tickets MODIFY stage_id BIGINT UNSIGNED NOT NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE tickets ALTER COLUMN stage_id SET NOT NULL');
            }
        }

        if (Schema::hasColumn('tickets', 'stage_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreign('stage_id')
                    ->references('id')
                    ->on('stages')
                    ->cascadeOnDelete();
            });
        }
    }
};
