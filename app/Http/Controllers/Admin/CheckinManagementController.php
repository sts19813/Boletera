<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Eventos;
use App\Models\Ticket;
use App\Models\TicketInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckinManagementController extends Controller
{
    public function index(Request $request)
    {
        $historySearch = trim((string) $request->input('history_search'));
        $historyEventId = $request->input('history_event_id');
        $historyType = $request->input('history_type');

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
            ->when($historyEventId, fn($query) => $query->where('event_id', $historyEventId))
            ->when($historyType === 'ticket', fn($query) => $query->whereNotNull('ticket_id'))
            ->when($historyType === 'registration', fn($query) => $query->whereNull('ticket_id'))
            ->when($historySearch !== '', function ($query) use ($historySearch) {
                $query->where(function ($innerQuery) use ($historySearch) {
                    $likeTerm = '%' . $historySearch . '%';

                    $innerQuery->where('id', 'like', $likeTerm)
                        ->orWhere('email', 'like', $likeTerm)
                        ->orWhere('nombre', 'like', $likeTerm)
                        ->orWhere('reference', 'like', $likeTerm)
                        ->orWhereHas('ticket', fn($ticketQuery) => $ticketQuery->where('name', 'like', $likeTerm))
                        ->orWhereHas('evento', fn($eventQuery) => $eventQuery->where('name', 'like', $likeTerm));
                });
            })
            ->orderByDesc('checkins_max_scanned_at')
            ->orderByDesc('created_at')
            ->paginate(20, ['*'], 'history_page')
            ->withQueryString();

        $historyItems->setCollection(
            $historyItems->getCollection()->map(function (TicketInstance $instance) {
                $instance->resolved_max_checkins = $this->resolveMaxCheckins($instance);

                return $instance;
            })
        );

        $ticketEventFilter = $request->input('ticket_event_id');

        $ticketLimits = Ticket::query()
            ->with('event:id,name')
            ->select(['id', 'event_id', 'name', 'max_checkins'])
            ->when($ticketEventFilter, fn($query) => $query->where('event_id', $ticketEventFilter))
            ->orderBy('name')
            ->paginate(15, ['*'], 'tickets_page')
            ->withQueryString();

        $registrationEventSearch = trim((string) $request->input('registration_event_search'));

        $registrationEvents = Eventos::query()
            ->select(['id', 'name', 'registration_max_checkins'])
            ->where('is_registration', true)
            ->when($registrationEventSearch !== '', fn($query) => $query->where('name', 'like', '%' . $registrationEventSearch . '%'))
            ->orderBy('name')
            ->paginate(15, ['*'], 'events_page')
            ->withQueryString();

        $events = Eventos::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return view('admin.checkin_management.index', [
            'historyItems' => $historyItems,
            'ticketLimits' => $ticketLimits,
            'registrationEvents' => $registrationEvents,
            'events' => $events,
            'historySearch' => $historySearch,
            'historyEventId' => $historyEventId,
            'historyType' => $historyType,
            'ticketEventFilter' => $ticketEventFilter,
            'registrationEventSearch' => $registrationEventSearch,
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

    public function updateTicketMaxCheckins(Request $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'max_checkins' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $ticket->update([
            'max_checkins' => (int) $data['max_checkins'],
        ]);

        return back()->with('status', 'Limite de escaneos actualizado para el ticket.');
    }

    public function updateRegistrationMaxCheckins(Request $request, Eventos $event): RedirectResponse
    {
        if (!$event->is_registration) {
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
