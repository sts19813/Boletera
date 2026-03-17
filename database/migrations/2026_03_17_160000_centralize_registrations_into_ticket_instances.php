<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ticket_instances', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
        });

        DB::statement('ALTER TABLE ticket_instances MODIFY ticket_id CHAR(36) NULL');

        Schema::table('ticket_instances', function (Blueprint $table) {
            $table->string('sale_type', 20)
                ->default('ticket')
                ->after('ticket_id');

            $table->timestamp('registered_at')
                ->nullable()
                ->after('purchased_at');

            $table->string('team_name')
                ->nullable()
                ->after('celular');

            $table->decimal('subtotal', 10, 2)
                ->default(0)
                ->after('price');

            $table->decimal('commission', 10, 2)
                ->default(0)
                ->after('subtotal');

            $table->decimal('total', 10, 2)
                ->default(0)
                ->after('commission');

            $table->json('form_data')
                ->nullable()
                ->after('total');

            $table->index(['sale_type', 'event_id'], 'ti_sale_type_event_idx');
            $table->index(['sale_type', 'payment_intent_id'], 'ti_sale_type_pi_idx');
        });

        Schema::table('ticket_instances', function (Blueprint $table) {
            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
                ->cascadeOnDelete();
        });

        DB::table('ticket_instances')->update([
            'registered_at' => DB::raw('COALESCE(registered_at, purchased_at)'),
            'subtotal' => DB::raw('COALESCE(NULLIF(subtotal, 0), price, 0)'),
            'total' => DB::raw('COALESCE(NULLIF(total, 0), price, 0)'),
        ]);

        $registrationRows = DB::table('registration_instances as ri')
            ->leftJoin('registrations as r', 'r.registration_instance_id', '=', 'ri.id')
            ->select([
                'ri.id',
                'ri.event_id',
                'ri.user_id',
                'ri.email',
                'ri.payment_intent_id',
                'ri.qr_hash',
                'ri.registered_at',
                'ri.price',
                'ri.nombre',
                'ri.celular',
                'ri.payment_method',
                'ri.created_at',
                'ri.updated_at',
                'r.id as registration_id',
                'r.team_name',
                'r.subtotal',
                'r.commission',
                'r.total',
                'r.form_data',
            ])
            ->get();

        foreach ($registrationRows as $row) {
            $alreadyMigrated = DB::table('ticket_instances')
                ->where('id', $row->id)
                ->exists();

            if ($alreadyMigrated) {
                continue;
            }

            $saleChannel = ($row->payment_method ?? 'card') === 'cash'
                ? 'taquilla'
                : 'stripe';

            $formData = $this->decodeJsonColumn($row->form_data);

            if (empty($formData) && !empty($row->registration_id)) {
                $players = DB::table('players')
                    ->where('registration_id', $row->registration_id)
                    ->get();

                if ($players->isNotEmpty()) {
                    $formData = [
                        'team_name' => $row->team_name,
                        'players' => $players->map(function ($player) {
                            return [
                                'name' => $player->name,
                                'phone' => $player->phone,
                                'email' => $player->email,
                                'campo' => $player->campo,
                                'handicap' => $player->handicap,
                                'ghin' => $player->ghin,
                                'shirt' => $player->shirt,
                                'cumbres' => $this->decodeJsonColumn($player->cumbres) ?? [],
                                'is_captain' => (bool) $player->is_captain,
                            ];
                        })->values()->all(),
                    ];
                }
            }

            DB::table('ticket_instances')->insert([
                'id' => $row->id,
                'ticket_id' => null,
                'sale_type' => 'registration',
                'user_id' => $row->user_id,
                'event_id' => $row->event_id,
                'email' => $row->email,
                'nombre' => $row->nombre,
                'celular' => $row->celular,
                'team_name' => $row->team_name,
                'purchased_at' => $row->registered_at,
                'registered_at' => $row->registered_at,
                'used_at' => null,
                'qr_hash' => $row->qr_hash,
                'payment_intent_id' => $row->payment_intent_id,
                'reference' => $row->payment_intent_id,
                'sale_channel' => $saleChannel,
                'payment_method' => $row->payment_method ?? 'card',
                'price' => $row->price ?? 0,
                'subtotal' => $row->subtotal ?? ($row->price ?? 0),
                'commission' => $row->commission ?? 0,
                'total' => $row->total ?? ($row->price ?? 0),
                'form_data' => empty($formData) ? null : json_encode($formData),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('ticket_instances')
            ->where('sale_type', 'registration')
            ->delete();

        Schema::table('ticket_instances', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
            $table->dropIndex('ti_sale_type_event_idx');
            $table->dropIndex('ti_sale_type_pi_idx');

            $table->dropColumn([
                'sale_type',
                'registered_at',
                'team_name',
                'subtotal',
                'commission',
                'total',
                'form_data',
            ]);
        });

        DB::statement('ALTER TABLE ticket_instances MODIFY ticket_id CHAR(36) NOT NULL');

        Schema::table('ticket_instances', function (Blueprint $table) {
            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
                ->cascadeOnDelete();
        });
    }

    private function decodeJsonColumn(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded)
            ? $decoded
            : null;
    }
};
