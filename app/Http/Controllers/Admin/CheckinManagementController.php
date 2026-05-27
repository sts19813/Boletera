<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Eventos;
use App\Models\Ticket;
use App\Models\TicketInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckinManagementController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = (string) $request->input('tab', 'history');
        if (!in_array($activeTab, ['history', 'tickets', 'registrations'], true)) {
            $activeTab = 'history';
        }

        $historyItems = TicketInstance::query()
            ->with([
                'ticket:id,name,max_checkins,event_id',
                'evento:id,name,is_registration,registration_max_checkins',
            ])
            ->withCount([
                'checkins as total_scans',
                'checkins as successful_scans' => fn($query) => $query->where('result', 'success'),
                'checkins as used_scans' => fn($query) => $query->where('result', 'used'),
                'checkins as invalid_scans' => fn($query) => $query->where('result', 'invalid'),
            ])
            ->withMax('checkins', 'scanned_at')
            ->orderByDesc('checkins_max_scanned_at')
            ->orderByDesc('created_at')
            ->get();

        $historyItems = $historyItems->map(function (TicketInstance $instance) {
                $instance->resolved_max_checkins = $this->resolveMaxCheckins($instance);

                return $instance;
            });

        $ticketLimits = Ticket::query()
            ->with('event:id,name')
            ->select(['id', 'event_id', 'name', 'max_checkins'])
            ->orderBy('name')
            ->get();

        $registrationEvents = Eventos::query()
            ->select(['id', 'name', 'registration_max_checkins'])
            ->where('is_registration', true)
            ->orderBy('name')
            ->get();

        $events = Eventos::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return view('admin.checkin_management.index', [
            'historyItems' => $historyItems,
            'ticketLimits' => $ticketLimits,
            'registrationEvents' => $registrationEvents,
            'events' => $events,
            'selectedTicketEventId' => (string) $request->input('ticket_event_id', ''),
            'activeTab' => $activeTab,
        ]);
    }

    public function show(TicketInstance $instance)
    {
        $instance->load([
            'ticket:id,name,max_checkins,event_id',
            'evento:id,name,is_registration,registration_max_checkins',
        ]);

        $history = $instance->checkins()
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $summary = $instance->checkins()
            ->selectRaw('result, COUNT(*) as total')
            ->groupBy('result')
            ->pluck('total', 'result');

        return view('admin.checkin_management.show', [
            'instance' => $instance,
            'history' => $history,
            'maxCheckins' => $this->resolveMaxCheckins($instance),
            'summaryTotal' => (int) $summary->sum(),
            'summarySuccess' => (int) ($summary['success'] ?? 0),
            'summaryUsed' => (int) ($summary['used'] ?? 0),
            'summaryInvalid' => (int) ($summary['invalid'] ?? 0),
        ]);
    }

    public function updateTicketMaxCheckins(Request $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'max_checkins' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $ticket->update([
            'max_checkins' => (int) $data['max_checkins'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Limite de escaneos actualizado para el ticket.',
                'max_checkins' => (int) $ticket->max_checkins,
            ]);
        }

        return back()->with('status', 'Limite de escaneos actualizado para el ticket.');
    }

    public function updateRegistrationMaxCheckins(Request $request, Eventos $event): RedirectResponse|JsonResponse
    {
        if (!$event->is_registration) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'El evento seleccionado no es de tipo registro.',
                ], 422);
            }

            return back()->withErrors([
                'registration_max_checkins' => 'El evento seleccionado no es de tipo registro.',
            ]);
        }

        $data = $request->validate([
            'registration_max_checkins' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $event->update([
            'registration_max_checkins' => (int) $data['registration_max_checkins'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Limite de escaneos por registro actualizado.',
                'registration_max_checkins' => (int) $event->registration_max_checkins,
            ]);
        }

        return back()->with('status', 'Limite de escaneos por registro actualizado.');
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
