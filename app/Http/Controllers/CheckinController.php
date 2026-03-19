<?php

namespace App\Http\Controllers;

use App\Models\TicketCheckin;
use App\Models\TicketInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckinController extends Controller
{
    /**
     * Vista del escaner
     */
    public function index()
    {
        return view('checkin.scanner');
    }

    /**
     * Validar boleto
     */
    public function validateTicket(Request $request)
    {
        $request->validate([
            'ticket_instance_id' => 'nullable|uuid|required_without:registration_instance_id',
            'registration_instance_id' => 'nullable|uuid|required_without:ticket_instance_id',
            'hash' => 'required|string',
        ]);

        $instanceId = $request->ticket_instance_id ?? $request->registration_instance_id;
        $scanHash = (string) $request->hash;

        $result = DB::transaction(function () use ($instanceId, $scanHash, $request) {
            $ticket = TicketInstance::with(['ticket', 'evento'])
                ->where('id', $instanceId)
                ->lockForUpdate()
                ->first();

            if (!$ticket || !hash_equals((string) $ticket->qr_hash, $scanHash)) {
                TicketCheckin::create([
                    'ticket_instance_id' => $instanceId,
                    'hash' => $scanHash,
                    'result' => 'invalid',
                    'message' => 'Boleto invalido',
                    'scanned_at' => now(),
                    'scanner_ip' => $request->ip(),
                ]);

                return [
                    'statusCode' => 404,
                    'payload' => [
                        'status' => 'error',
                        'message' => 'Boleto invalido',
                    ],
                ];
            }

            $maxCheckins = $this->resolveMaxCheckins($ticket);

            $usedCount = TicketCheckin::where('ticket_instance_id', $ticket->id)
                ->where('result', 'success')
                ->count();

            if ($usedCount >= $maxCheckins) {
                TicketCheckin::create([
                    'ticket_instance_id' => $ticket->id,
                    'hash' => $scanHash,
                    'result' => 'used',
                    'message' => 'Cupo agotado',
                    'scanned_at' => now(),
                    'scanner_ip' => $request->ip(),
                ]);

                $history = TicketCheckin::where('ticket_instance_id', $ticket->id)
                    ->where('result', 'success')
                    ->orderBy('scanned_at')
                    ->get()
                    ->map(fn($h, $i) => [
                        'numero' => ($i + 1) . '/' . $maxCheckins,
                        'hora' => $h->scanned_at->format('d/m/Y H:i:s'),
                    ]);

                return [
                    'statusCode' => 200,
                    'payload' => [
                        'status' => 'used',
                        'message' => 'Este boleto ya alcanzo su limite',
                        'history' => $history,
                    ],
                ];
            }

            if (!$ticket->used_at) {
                $ticket->update([
                    'used_at' => now(),
                ]);
            }

            TicketCheckin::create([
                'ticket_instance_id' => $ticket->id,
                'hash' => $scanHash,
                'result' => 'success',
                'message' => 'Acceso permitido',
                'scanned_at' => now(),
                'scanner_ip' => $request->ip(),
            ]);

            return [
                'statusCode' => 200,
                'payload' => [
                    'status' => 'success',
                    'message' => 'Acceso permitido',
                    'email' => $ticket->email,
                    'progress' => ($usedCount + 1) . '/' . $maxCheckins,
                    'used_at' => $ticket->used_at->format('d/m/Y H:i:s'),
                ],
            ];
        });

        return response()->json($result['payload'], $result['statusCode']);
    }

    public function stats()
    {
        $total = TicketInstance::count();

        $scanned = TicketCheckin::where('result', 'success')
            ->distinct('ticket_instance_id')
            ->count('ticket_instance_id');

        $courtesyScanned = TicketInstance::where('email', 'CORTESIA')
            ->whereHas('checkins', function ($q) {
                $q->where('result', 'success');
            })
            ->count();

        $pending = $total - $scanned;

        return view('checkin.stats', compact(
            'total',
            'scanned',
            'courtesyScanned',
            'pending'
        ));
    }

    private function resolveMaxCheckins(TicketInstance $instance): int
    {
        if ($instance->ticket_id) {
            return $this->parsePositiveInteger($instance->ticket?->max_checkins) ?? 1;
        }

        $instanceOverride = $this->extractRegistrationCheckinOverride($instance->form_data);
        if ($instanceOverride !== null) {
            return $instanceOverride;
        }

        return $this->parsePositiveInteger($instance->evento?->registration_max_checkins) ?? 1;
    }

    private function extractRegistrationCheckinOverride(mixed $formData): ?int
    {
        if (!is_array($formData)) {
            return null;
        }

        $supportedKeys = [
            'max_checkins',
            'maxCheckins',
            'checkin_max',
            'checkinMax',
            'checkin_limit',
            'checkinLimit',
        ];

        foreach ($supportedKeys as $key) {
            if (!array_key_exists($key, $formData)) {
                continue;
            }

            $parsed = $this->parsePositiveInteger($formData[$key]);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    private function parsePositiveInteger(mixed $value): ?int
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === null || $value === '') {
            return null;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        return $parsed === false ? null : (int) $parsed;
    }
}
