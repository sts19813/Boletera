<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Eventos;
use App\Models\Ticket;
use App\Models\TicketInstance;
use App\Services\RegistrationService;
use App\Services\TicketService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatbotAdminController extends Controller
{
    public function __construct(
        private TicketService $ticketService,
        private RegistrationService $registrationService
    ) {
    }

    public function events(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:120',
            'scope' => 'nullable|in:all,upcoming,past,today',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $today = now()->toDateString();
        $scope = $validated['scope'] ?? 'all';
        $limit = (int) ($validated['limit'] ?? 50);

        $query = Eventos::query();

        if (!empty($validated['q'])) {
            $query->where('name', 'like', '%' . $validated['q'] . '%');
        }

        if ($scope === 'upcoming') {
            $query->whereDate('event_date', '>=', $today);
        } elseif ($scope === 'past') {
            $query->whereDate('event_date', '<', $today);
        } elseif ($scope === 'today') {
            $query->whereDate('event_date', $today);
        }

        $events = $query
            ->orderByRaw('event_date IS NULL')
            ->orderBy('event_date')
            ->orderBy('hora_inicio')
            ->limit($limit)
            ->get();

        $data = $events->map(fn(Eventos $event) => $this->eventSummary($event))->values();

        return response()->json([
            'meta' => [
                'scope' => $scope,
                'count' => $data->count(),
                'generated_at' => now()->toIso8601String(),
            ],
            'data' => $data,
        ]);
    }

    public function upcomingEvents(Request $request)
    {
        $request->merge(['scope' => 'upcoming']);
        return $this->events($request);
    }

    public function showEvent(Eventos $evento)
    {
        $ticketRows = Ticket::query()
            ->where('event_id', $evento->id)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'type',
                'total_price',
                'status',
                'stock',
                'sold',
                'available_from',
                'available_until',
            ]);

        $ticketSales = TicketInstance::query()->ticketSales()
            ->where('event_id', $evento->id)
            ->select(
                'ticket_id',
                DB::raw('COUNT(*) as vendidos'),
                DB::raw("SUM(CASE WHEN email = 'CORTESIA' THEN 1 ELSE 0 END) as cortesia"),
                DB::raw("SUM(CASE WHEN email != 'CORTESIA' THEN price ELSE 0 END) as ingresos")
            )
            ->groupBy('ticket_id')
            ->get()
            ->keyBy('ticket_id');

        $registrationStats = TicketInstance::query()->registrationSales()
            ->where('event_id', $evento->id)
            ->select(
                DB::raw('COUNT(*) as vendidos'),
                DB::raw("SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) as cortesia"),
                DB::raw('SUM(price) as ingresos'),
                DB::raw('MAX(purchased_at) as ultima_venta')
            )
            ->first();

        $tickets = $ticketRows->map(function (Ticket $ticket) use ($ticketSales) {
            $stats = $ticketSales->get($ticket->id);

            return [
                'id' => $ticket->id,
                'name' => $ticket->name,
                'type' => $ticket->type,
                'price' => $ticket->total_price !== null ? (float) $ticket->total_price : null,
                'status' => $ticket->status,
                'stock_available' => $ticket->stock !== null ? (int) $ticket->stock : null,
                'sold_recorded' => $ticket->sold !== null ? (int) $ticket->sold : null,
                'sold_instances' => $stats ? (int) $stats->vendidos : 0,
                'courtesy_instances' => $stats ? (int) $stats->cortesia : 0,
                'revenue' => $stats ? round((float) $stats->ingresos, 2) : 0.0,
                'available_from' => $ticket->available_from?->toIso8601String(),
                'available_until' => $ticket->available_until?->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'event' => $this->eventSummary($evento),
            'ticket_breakdown' => $tickets,
            'registration_breakdown' => [
                'price' => $evento->price !== null ? (float) $evento->price : null,
                'capacity_remaining' => $evento->max_capacity !== null ? (int) $evento->max_capacity : null,
                'sold_instances' => $registrationStats ? (int) $registrationStats->vendidos : 0,
                'courtesy_instances' => $registrationStats ? (int) $registrationStats->cortesia : 0,
                'revenue' => $registrationStats ? round((float) $registrationStats->ingresos, 2) : 0.0,
                'last_sale_at' => $registrationStats?->ultima_venta,
            ],
        ]);
    }

    public function salesOverview(Request $request)
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'event_id' => 'nullable|uuid|exists:eventos,id',
        ]);

        $ticketQuery = TicketInstance::query()->ticketSales();
        $registrationQuery = TicketInstance::query()->registrationSales();

        if (!empty($validated['event_id'])) {
            $ticketQuery->where('event_id', $validated['event_id']);
            $registrationQuery->where('event_id', $validated['event_id']);
        }

        if (!empty($validated['from']) && !empty($validated['to'])) {
            $ticketQuery->whereBetween('purchased_at', [
                $validated['from'] . ' 00:00:00',
                $validated['to'] . ' 23:59:59',
            ]);

            $registrationQuery->whereBetween('purchased_at', [
                $validated['from'] . ' 00:00:00',
                $validated['to'] . ' 23:59:59',
            ]);
        }

        $ticketsSold = (clone $ticketQuery)->count();
        $registrationsSold = (clone $registrationQuery)->count();
        $ticketRevenue = (clone $ticketQuery)->where('email', '!=', 'CORTESIA')->sum('price');
        $registrationRevenue = (clone $registrationQuery)->sum('price');
        $courtesyTickets = (clone $ticketQuery)->where('email', 'CORTESIA')->count();
        $courtesyRegistrations = (clone $registrationQuery)->where('price', 0)->count();
        $lastTicketSale = (clone $ticketQuery)->max('purchased_at');
        $lastRegistrationSale = (clone $registrationQuery)->max('purchased_at');

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'filters' => [
                'event_id' => $validated['event_id'] ?? null,
                'from' => $validated['from'] ?? null,
                'to' => $validated['to'] ?? null,
            ],
            'sales' => [
                'tickets_sold' => $ticketsSold,
                'registrations_sold' => $registrationsSold,
                'total_items_sold' => $ticketsSold + $registrationsSold,
                'ticket_revenue' => round((float) $ticketRevenue, 2),
                'registration_revenue' => round((float) $registrationRevenue, 2),
                'total_revenue' => round((float) ($ticketRevenue + $registrationRevenue), 2),
                'courtesy_tickets' => $courtesyTickets,
                'courtesy_registrations' => $courtesyRegistrations,
                'courtesy_total' => $courtesyTickets + $courtesyRegistrations,
                'last_sale_at' => collect([$lastTicketSale, $lastRegistrationSale])->filter()->max(),
            ],
        ]);
    }

    public function searchSales(Request $request)
    {
        $validated = $request->validate([
            'type' => 'nullable|in:all,ticket,registration',
            'q' => 'nullable|string|max:120',
            'name' => 'nullable|string|max:120',
            'email' => 'nullable|string|max:120',
            'event_id' => 'nullable|uuid|exists:eventos,id',
            'date' => 'nullable|date',
            'from' => 'nullable|date|required_with:to',
            'to' => 'nullable|date|after_or_equal:from|required_with:from',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $type = $validated['type'] ?? 'all';
        $limit = (int) ($validated['limit'] ?? 50);

        $salesData = $this->buildSalesData($validated, $type, $limit);

        return response()->json([
            'meta' => [
                'type' => $type,
                'count' => $salesData['items']->count(),
                'total_matches' => $salesData['total_matches'],
                'tickets_matches' => $salesData['tickets_matches'],
                'registrations_matches' => $salesData['registrations_matches'],
                'generated_at' => now()->toIso8601String(),
            ],
            'filters' => [
                'q' => $validated['q'] ?? null,
                'name' => $validated['name'] ?? null,
                'email' => $validated['email'] ?? null,
                'event_id' => $validated['event_id'] ?? null,
                'date' => $validated['date'] ?? null,
                'from' => $validated['from'] ?? null,
                'to' => $validated['to'] ?? null,
            ],
            'data' => $salesData['items']->values(),
        ]);
    }

    public function latestSales(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'nullable|uuid|exists:eventos,id',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $limit = (int) ($validated['limit'] ?? 10);
        $salesData = $this->buildSalesData($validated, 'all', $limit);

        $contacts = $salesData['items']->map(function (array $sale) {
            return [
                'sale_type' => $sale['sale_type'],
                'customer_name' => $sale['customer_name'],
                'customer_email' => $sale['customer_email'],
                'price' => $sale['price'],
                'event_name' => $sale['event_name'],
                'sold_at' => $sale['sold_at'],
                'reference' => $sale['reference'],
                'pdf_url' => $sale['pdf_url'],
            ];
        })->values();

        return response()->json([
            'meta' => [
                'count' => $contacts->count(),
                'generated_at' => now()->toIso8601String(),
            ],
            'data' => $contacts,
        ]);
    }

    public function availability(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'nullable|uuid|exists:eventos,id',
            'q' => 'nullable|string|max:120',
            'scope' => 'nullable|in:all,upcoming,today',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $limit = (int) ($validated['limit'] ?? 100);
        $today = now()->toDateString();
        $scope = $validated['scope'] ?? 'all';

        $query = Eventos::query();

        if (!empty($validated['event_id'])) {
            $query->where('id', $validated['event_id']);
        } else {
            if (!empty($validated['q'])) {
                $query->where('name', 'like', '%' . $validated['q'] . '%');
            }

            if ($scope === 'upcoming') {
                $query->whereDate('event_date', '>=', $today);
            } elseif ($scope === 'today') {
                $query->whereDate('event_date', $today);
            }
        }

        $events = $query
            ->orderByRaw('event_date IS NULL')
            ->orderBy('event_date')
            ->orderBy('hora_inicio')
            ->limit($limit)
            ->get();

        $eventIds = $events->pluck('id')->filter()->unique()->values();
        $ticketsByEvent = collect();

        if ($eventIds->isNotEmpty()) {
            $ticketsByEvent = Ticket::query()
                ->whereIn('event_id', $eventIds)
                ->orderBy('name')
                ->get([
                    'id',
                    'event_id',
                    'name',
                    'type',
                    'total_price',
                    'status',
                    'stock',
                    'sold',
                    'available_from',
                    'available_until',
                ])
                ->groupBy('event_id');
        }

        $data = $events->map(function (Eventos $event) use ($ticketsByEvent) {
            $tickets = collect($ticketsByEvent->get($event->id, collect()))
                ->map(function (Ticket $ticket) {
                    $stock = $ticket->stock !== null ? max(0, (int) $ticket->stock) : 0;

                    return [
                        'type' => 'ticket',
                        'id' => $ticket->id,
                        'name' => $ticket->name,
                        'ticket_type' => $ticket->type,
                        'unit_price' => (float) $ticket->total_price,
                        'stock_available' => $stock,
                        'sold_recorded' => $ticket->sold !== null ? (int) $ticket->sold : 0,
                        'status' => $ticket->status,
                        'can_sell_cash' => $stock > 0,
                        'available_from' => $ticket->available_from?->toIso8601String(),
                        'available_until' => $ticket->available_until?->toIso8601String(),
                    ];
                })
                ->values();

            $registrationSlots = $event->is_registration
                ? ($event->max_capacity !== null ? max(0, (int) $event->max_capacity) : null)
                : null;

            $registrationCanSell = (bool) $event->is_registration
                && ($registrationSlots === null || $registrationSlots > 0);

            $sellableItems = $tickets
                ->where('can_sell_cash', true)
                ->map(fn(array $ticket) => [
                    'type' => 'ticket',
                    'id' => $ticket['id'],
                    'name' => $ticket['name'],
                    'unit_price' => $ticket['unit_price'],
                    'max_qty' => $ticket['stock_available'],
                ])
                ->values();

            if ($registrationCanSell) {
                $sellableItems->push([
                    'type' => 'registration',
                    'id' => $event->id,
                    'name' => 'Inscripcion',
                    'unit_price' => $event->price !== null ? (float) $event->price : 0.0,
                    'max_qty' => $registrationSlots,
                ]);
            }

            return [
                'event_id' => $event->id,
                'event_name' => $event->name,
                'event_date' => $event->event_date?->toDateString(),
                'hora_inicio' => $event->hora_inicio,
                'hora_fin' => $event->hora_fin,
                'location' => $event->location,
                'tickets_available_total' => $tickets->sum('stock_available'),
                'tickets' => $tickets,
                'registration' => [
                    'enabled' => (bool) $event->is_registration,
                    'unit_price' => $event->price !== null ? (float) $event->price : null,
                    'slots_available' => $registrationSlots,
                    'can_sell_cash' => $registrationCanSell,
                ],
                'sellable_items' => $sellableItems->values(),
            ];
        })->values();

        return response()->json([
            'meta' => [
                'count' => $data->count(),
                'generated_at' => now()->toIso8601String(),
            ],
            'data' => $data,
        ]);
    }

    public function sellCash(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|uuid|exists:eventos,id',
            'buyer_name' => 'nullable|string|max:255',
            'buyer_email' => 'nullable|email|max:255',
            'buyer_phone' => 'nullable|string|max:40',
            'registration_form' => 'nullable|array',
            'cart' => 'required|array|min:1',
            'cart.*.type' => 'required|in:ticket,registration',
            'cart.*.id' => 'nullable|uuid',
            'cart.*.qty' => 'nullable|integer|min:1|max:100',
            'cart.*.price' => 'nullable|numeric|min:0',
        ]);

        $response = DB::transaction(function () use ($validated) {
            $event = Eventos::lockForUpdate()->findOrFail($validated['event_id']);

            $buyerName = trim((string) ($validated['buyer_name'] ?? 'taquilla'));
            if ($buyerName === '') {
                $buyerName = 'taquilla';
            }

            $buyerEmail = strtolower(trim((string) ($validated['buyer_email'] ?? '')));
            if (!filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
                $buyerEmail = 'taquilla@local';
            }

            $buyerPhone = $validated['buyer_phone'] ?? null;
            $reference = 'WA-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));

            $totalRegistrationQty = collect($validated['cart'])
                ->where('type', 'registration')
                ->sum(fn($item) => max(1, (int) ($item['qty'] ?? 1)));

            if ($totalRegistrationQty > 0 && !$event->is_registration) {
                $this->throwConflict('El evento no acepta inscripciones.');
            }

            if (
                $totalRegistrationQty > 0
                && $event->max_capacity !== null
                && (int) $event->max_capacity < $totalRegistrationQty
            ) {
                $this->throwConflict('No hay cupo suficiente para completar la venta.');
            }

            $ticketsCreated = collect();
            $registrationsCreated = collect();
            $totalAmount = 0.0;

            foreach ($validated['cart'] as $index => $item) {
                $qty = max(1, (int) ($item['qty'] ?? 1));
                $type = $item['type'];

                if ($type === 'ticket') {
                    if (empty($item['id'])) {
                        throw ValidationException::withMessages([
                            "cart.$index.id" => 'El id del ticket es requerido cuando type=ticket.',
                        ]);
                    }

                    $boletos = $this->ticketService->createFromTaquilla(
                        $event,
                        ['id' => $item['id'], 'qty' => $qty],
                        $buyerEmail,
                        $buyerName,
                        $reference,
                        'cash'
                    );

                    foreach ($boletos as $boleto) {
                        $price = (float) data_get($boleto, 'ticket.price', 0);
                        $totalAmount += $price;

                        $ticketsCreated->push([
                            'instance_id' => data_get($boleto, 'wallet.instance_id'),
                            'ticket_id' => $item['id'],
                            'ticket_name' => data_get($boleto, 'ticket.name'),
                            'unit_price' => round($price, 2),
                        ]);
                    }

                    continue;
                }

                $event->refresh();

                if ($event->max_capacity !== null && (int) $event->max_capacity < $qty) {
                    $this->throwConflict('No hay cupo suficiente para completar la venta.');
                }

                $unitPrice = array_key_exists('price', $item)
                    ? (float) $item['price']
                    : (float) ($event->price ?? 0);

                $instances = $this->registrationService->create($event, [
                    'qty' => $qty,
                    'email' => $buyerEmail,
                    'nombre' => $buyerName,
                    'celular' => $buyerPhone,
                    'form_data' => $validated['registration_form'] ?? null,
                    'reference' => $reference,
                    'sale_channel' => 'taquilla',
                    'payment_method' => 'cash',
                    'price' => $unitPrice,
                ]);

                foreach ($instances as $instance) {
                    $price = (float) ($instance->price ?? 0);
                    $totalAmount += $price;

                    $registrationsCreated->push([
                        'instance_id' => $instance->id,
                        'registration_name' => 'Inscripcion',
                        'unit_price' => round($price, 2),
                    ]);
                }
            }

            return [
                'generated_at' => now()->toIso8601String(),
                'message' => 'Venta en efectivo registrada correctamente.',
                'sale' => [
                    'reference' => $reference,
                    'event_id' => $event->id,
                    'event_name' => $event->name,
                    'buyer_name' => $buyerName,
                    'buyer_email' => $buyerEmail,
                    'buyer_phone' => $buyerPhone,
                    'payment_method' => 'cash',
                    'tickets_count' => $ticketsCreated->count(),
                    'registrations_count' => $registrationsCreated->count(),
                    'total_items' => $ticketsCreated->count() + $registrationsCreated->count(),
                    'total_amount' => round($totalAmount, 2),
                    'reprint_pdf_url' => $this->buildReprintUrl($reference, $buyerEmail),
                ],
                'items' => [
                    'tickets' => $ticketsCreated->values(),
                    'registrations' => $registrationsCreated->values(),
                ],
            ];
        });

        return response()->json($response, 201);
    }

    private function buildSalesData(array $filters, string $type, int $limit): array
    {
        $ticketQuery = TicketInstance::query()->ticketSales()->with(['ticket:id,name', 'evento:id,name']);
        $registrationQuery = TicketInstance::query()->registrationSales()->with(['evento:id,name']);

        $this->applySalesFiltersToTickets($ticketQuery, $filters);
        $this->applySalesFiltersToRegistrations($registrationQuery, $filters);

        $includeTickets = in_array($type, ['all', 'ticket'], true);
        $includeRegistrations = in_array($type, ['all', 'registration'], true);

        $ticketsCount = $includeTickets ? (clone $ticketQuery)->count() : 0;
        $registrationsCount = $includeRegistrations ? (clone $registrationQuery)->count() : 0;

        $tickets = collect();
        if ($includeTickets) {
            $tickets = (clone $ticketQuery)
                ->latest('purchased_at')
                ->limit($limit)
                ->get()
                ->map(function (TicketInstance $sale) {
                    $reference = $sale->payment_intent_id ?: $sale->reference;

                    return [
                        'sale_type' => 'ticket',
                        'instance_id' => $sale->id,
                        'event_id' => $sale->event_id,
                        'event_name' => $sale->evento?->name,
                        'concept' => $sale->ticket?->name ?? 'Boleto',
                        'customer_name' => $sale->nombre,
                        'customer_email' => $sale->email,
                        'customer_phone' => $sale->celular,
                        'price' => round((float) $sale->price, 2),
                        'payment_method' => $sale->payment_method,
                        'reference' => $reference,
                        'sold_at' => $sale->purchased_at?->toIso8601String(),
                        'status' => $sale->used_at ? 'used' : 'valid',
                        'pdf_url' => $this->buildReprintUrl($reference, $sale->email),
                        '_sort_at' => $sale->purchased_at?->timestamp ?? 0,
                    ];
                });
        }

        $registrations = collect();
        if ($includeRegistrations) {
            $registrations = (clone $registrationQuery)
                ->latest('purchased_at')
                ->limit($limit)
                ->get()
                ->map(function (TicketInstance $sale) {
                    $reference = $sale->payment_intent_id ?: $sale->reference;

                    return [
                        'sale_type' => 'registration',
                        'instance_id' => $sale->id,
                        'event_id' => $sale->event_id,
                        'event_name' => $sale->evento?->name,
                        'concept' => 'Inscripcion',
                        'customer_name' => $sale->nombre,
                        'customer_email' => $sale->email,
                        'customer_phone' => $sale->celular,
                        'price' => round((float) $sale->price, 2),
                        'payment_method' => $sale->payment_method,
                        'reference' => $reference,
                        'sold_at' => $sale->purchased_at?->toIso8601String(),
                        'status' => 'registered',
                        'pdf_url' => $this->buildReprintUrl($reference, $sale->email),
                        '_sort_at' => $sale->purchased_at?->timestamp ?? 0,
                    ];
                });
        }

        $items = $tickets
            ->merge($registrations)
            ->sortByDesc('_sort_at')
            ->take($limit)
            ->map(function (array $sale) {
                unset($sale['_sort_at']);
                return $sale;
            })
            ->values();

        return [
            'items' => $items,
            'total_matches' => $ticketsCount + $registrationsCount,
            'tickets_matches' => $ticketsCount,
            'registrations_matches' => $registrationsCount,
        ];
    }

    private function applySalesFiltersToTickets($query, array $filters): void
    {
        if (!empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (!empty($filters['name'])) {
            $query->where('nombre', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['email'])) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }

        if (!empty($filters['q'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('nombre', 'like', '%' . $filters['q'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['q'] . '%')
                    ->orWhere('reference', 'like', '%' . $filters['q'] . '%')
                    ->orWhere('payment_intent_id', 'like', '%' . $filters['q'] . '%');
            });
        }

        if (!empty($filters['date'])) {
            $query->whereDate('purchased_at', $filters['date']);
            return;
        }

        if (!empty($filters['from']) && !empty($filters['to'])) {
            $query->whereBetween('purchased_at', [
                $filters['from'] . ' 00:00:00',
                $filters['to'] . ' 23:59:59',
            ]);
        }
    }

    private function applySalesFiltersToRegistrations($query, array $filters): void
    {
        if (!empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (!empty($filters['name'])) {
            $query->where('nombre', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['email'])) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }

        if (!empty($filters['q'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('nombre', 'like', '%' . $filters['q'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['q'] . '%')
                    ->orWhere('payment_intent_id', 'like', '%' . $filters['q'] . '%')
                    ->orWhere('reference', 'like', '%' . $filters['q'] . '%');
            });
        }

        if (!empty($filters['date'])) {
            $query->whereDate('purchased_at', $filters['date']);
            return;
        }

        if (!empty($filters['from']) && !empty($filters['to'])) {
            $query->whereBetween('purchased_at', [
                $filters['from'] . ' 00:00:00',
                $filters['to'] . ' 23:59:59',
            ]);
        }
    }

    private function buildReprintUrl(?string $reference, ?string $email): ?string
    {
        if (empty($reference)) {
            return null;
        }

        $query = ['ref' => $reference];

        if (!empty($email)) {
            $query['email'] = $email;
        }

        return url('/boletos/reprint?' . http_build_query($query));
    }

    private function throwConflict(string $message): void
    {
        throw new HttpResponseException(
            response()->json(['message' => $message], 409)
        );
    }

    private function eventSummary(Eventos $event): array
    {
        $ticketCatalog = Ticket::query()
            ->where('event_id', $event->id)
            ->select(
                DB::raw('COUNT(*) as tipos_boletos'),
                DB::raw('SUM(COALESCE(stock, 0)) as boletos_disponibles'),
                DB::raw('MIN(total_price) as precio_min'),
                DB::raw('MAX(total_price) as precio_max')
            )
            ->first();

        $ticketStats = TicketInstance::query()->ticketSales()
            ->where('event_id', $event->id)
            ->select(
                DB::raw('COUNT(*) as boletos_vendidos'),
                DB::raw("SUM(CASE WHEN email = 'CORTESIA' THEN 1 ELSE 0 END) as boletos_cortesia"),
                DB::raw("SUM(CASE WHEN email != 'CORTESIA' THEN price ELSE 0 END) as ingresos_boletos"),
                DB::raw('MAX(purchased_at) as ultima_venta')
            )
            ->first();

        $registrationStats = TicketInstance::query()->registrationSales()
            ->where('event_id', $event->id)
            ->select(
                DB::raw('COUNT(*) as inscripciones_vendidas'),
                DB::raw("SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) as inscripciones_cortesia"),
                DB::raw('SUM(price) as ingresos_inscripciones'),
                DB::raw('MAX(purchased_at) as ultima_inscripcion')
            )
            ->first();

        $ticketsRevenue = (float) ($ticketStats?->ingresos_boletos ?? 0);
        $registrationsRevenue = (float) ($registrationStats?->ingresos_inscripciones ?? 0);

        return [
            'id' => $event->id,
            'name' => $event->name,
            'description' => $event->description,
            'event_date' => $event->event_date?->toDateString(),
            'hora_inicio' => $event->hora_inicio,
            'hora_fin' => $event->hora_fin,
            'location' => $event->location,
            'is_registration' => (bool) $event->is_registration,
            'upcoming' => $event->event_date !== null && $event->event_date->toDateString() >= now()->toDateString(),
            'costs' => [
                'registration_price' => $event->price !== null ? (float) $event->price : null,
                'ticket_price_min' => $ticketCatalog?->precio_min !== null ? (float) $ticketCatalog->precio_min : null,
                'ticket_price_max' => $ticketCatalog?->precio_max !== null ? (float) $ticketCatalog->precio_max : null,
            ],
            'availability' => [
                'ticket_types' => (int) ($ticketCatalog?->tipos_boletos ?? 0),
                'tickets_available' => max(0, (int) ($ticketCatalog?->boletos_disponibles ?? 0)),
                'registration_slots_available' => $event->is_registration
                    ? ($event->max_capacity !== null ? max(0, (int) $event->max_capacity) : null)
                    : null,
            ],
            'sales' => [
                'tickets_sold' => (int) ($ticketStats?->boletos_vendidos ?? 0),
                'registrations_sold' => (int) ($registrationStats?->inscripciones_vendidas ?? 0),
                'total_items_sold' => (int) ($ticketStats?->boletos_vendidos ?? 0) + (int) ($registrationStats?->inscripciones_vendidas ?? 0),
                'ticket_revenue' => round($ticketsRevenue, 2),
                'registration_revenue' => round($registrationsRevenue, 2),
                'total_revenue' => round($ticketsRevenue + $registrationsRevenue, 2),
                'courtesy_tickets' => (int) ($ticketStats?->boletos_cortesia ?? 0),
                'courtesy_registrations' => (int) ($registrationStats?->inscripciones_cortesia ?? 0),
                'last_sale_at' => collect([$ticketStats?->ultima_venta, $registrationStats?->ultima_inscripcion])->filter()->max(),
            ],
        ];
    }
}
