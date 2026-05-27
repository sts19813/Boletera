<?php

namespace App\Http\Controllers;

use App\Models\Eventos;
use App\Models\TicketCheckin;
use App\Models\TicketInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CheckinController extends Controller
{
    /**
     * Vista del escaner
     */
    public function index(Request $request)
    {
        $events = $this->resolveAccessibleEvents($request);
        $selectedEventId = $this->resolveSelectedEventId($request, $events);
        $canScanAnyEvent = $request->user()?->hasRole('admin') || $events->isNotEmpty();

        return view('checkin.scanner', [
            'events' => $events,
            'selectedEventId' => $selectedEventId,
            'canScanAnyEvent' => $canScanAnyEvent,
        ]);
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
            'event_id' => 'nullable|uuid',
        ]);

        $instanceId = $request->ticket_instance_id ?? $request->registration_instance_id;
        $scanHash = (string) $request->hash;
        $selectedEventId = filled($request->input('event_id')) ? (string) $request->input('event_id') : null;
        $accessibleEventIds = $this->resolveAccessibleEventIds($request);

        $result = DB::transaction(function () use ($instanceId, $scanHash, $request, $selectedEventId, $accessibleEventIds) {
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

            if ($accessibleEventIds !== null && !$accessibleEventIds->contains((string) $ticket->event_id)) {
                TicketCheckin::create([
                    'ticket_instance_id' => $ticket->id,
                    'hash' => $scanHash,
                    'result' => 'invalid',
                    'message' => 'Evento no autorizado para este scanner',
                    'scanned_at' => now(),
                    'scanner_ip' => $request->ip(),
                ]);

                return [
                    'statusCode' => 403,
                    'payload' => [
                        'status' => 'error',
                        'message' => 'No tienes permiso para escanear este evento',
                    ],
                ];
            }

            if ($selectedEventId !== null && (string) $ticket->event_id !== $selectedEventId) {
                return [
                    'statusCode' => 422,
                    'payload' => [
                        'status' => 'error',
                        'message' => 'Este codigo corresponde a otro evento.',
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

    public function stats(Request $request)
    {
        $events = $this->resolveAccessibleEvents($request);
        $selectedEventId = $this->resolveSelectedEventId($request, $events);
        $accessibleEventIds = $this->resolveAccessibleEventIds($request);

        $totalQuery = TicketInstance::query();
        $scannedQuery = TicketCheckin::query()->where('result', 'success');
        $courtesyQuery = TicketInstance::query()->where('email', 'CORTESIA');

        if ($selectedEventId !== null) {
            $totalQuery->where('event_id', $selectedEventId);
            $scannedQuery->whereHas('ticketInstance', fn($query) => $query->where('event_id', $selectedEventId));
            $courtesyQuery->where('event_id', $selectedEventId);
        } elseif ($accessibleEventIds !== null) {
            if ($accessibleEventIds->isEmpty()) {
                $total = 0;
                $scanned = 0;
                $courtesyScanned = 0;
                $pending = 0;

                return view('checkin.stats', [
                    'total' => $total,
                    'scanned' => $scanned,
                    'courtesyScanned' => $courtesyScanned,
                    'pending' => $pending,
                    'events' => $events,
                    'selectedEventId' => null,
                ]);
            }

            $totalQuery->whereIn('event_id', $accessibleEventIds);
            $scannedQuery->whereHas('ticketInstance', fn($query) => $query->whereIn('event_id', $accessibleEventIds));
            $courtesyQuery->whereIn('event_id', $accessibleEventIds);
        }

        $total = $totalQuery->count();

        $scanned = $scannedQuery
            ->distinct('ticket_instance_id')
            ->count('ticket_instance_id');

        $courtesyScanned = $courtesyQuery
            ->whereHas('checkins', function ($q) {
                $q->where('result', 'success');
            })
            ->count();

        $pending = $total - $scanned;

        return view('checkin.stats', [
            'total' => $total,
            'scanned' => $scanned,
            'courtesyScanned' => $courtesyScanned,
            'pending' => $pending,
            'events' => $events,
            'selectedEventId' => $selectedEventId,
        ]);
    }

    /**
     * @return Collection<int, Eventos>
     */
    private function resolveAccessibleEvents(Request $request): Collection
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return Eventos::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get();
        }

        $allowedEventIds = $user?->events()->pluck('eventos.id') ?? collect();

        return Eventos::query()
            ->select(['id', 'name'])
            ->whereIn('id', $allowedEventIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, string>|null
     */
    private function resolveAccessibleEventIds(Request $request): ?Collection
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return null;
        }

        return $user?->events()
            ->pluck('eventos.id')
            ->map(fn($id) => (string) $id)
            ->values() ?? collect();
    }

    private function resolveSelectedEventId(Request $request, Collection $accessibleEvents): ?string
    {
        $selectedEventId = $request->input('event_id');
        if (!filled($selectedEventId)) {
            return null;
        }

        $selectedEventId = (string) $selectedEventId;
        $allowed = $accessibleEvents
            ->pluck('id')
            ->map(fn($id) => (string) $id);

        return $allowed->contains($selectedEventId) ? $selectedEventId : null;
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
