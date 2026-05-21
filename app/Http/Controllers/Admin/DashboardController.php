<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Eventos;
use App\Models\TicketInstance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Vista principal del dashboard
     */
    public function index(Request $request)
    {
        $events = $this->resolveAccessibleEvents($request);
        $selectedEventIds = $this->resolveSelectedEventIds($request, $events);

        return view('admin.dashboard.index', [
            'events' => $events,
            'selectedEventIds' => $selectedEventIds,
        ]);
    }

    /**
     * Endpoint AJAX para metricas y grafica
     */
    public function data(Request $request)
    {
        $events = $this->resolveAccessibleEvents($request);
        $selectedEventIds = $this->resolveSelectedEventIds($request, $events);
        $eventNameMap = $events->pluck('name', 'id');

        $from = $request->input('from');
        $to = $request->input('to');

        $ticketQuery = TicketInstance::query()->ticketSales();
        $registrationQuery = TicketInstance::query()->registrationSales();
        $this->applyCommonFilters($ticketQuery, $from, $to, $selectedEventIds);
        $this->applyCommonFilters($registrationQuery, $from, $to, $selectedEventIds);

        $courtesyTicketSql = "UPPER(COALESCE(email, '')) LIKE '%CORTESIA%'";
        $paidTicketSql = "UPPER(COALESCE(email, '')) NOT LIKE '%CORTESIA%'";

        $ticketsCount = (clone $ticketQuery)->count();
        $registrationsCount = (clone $registrationQuery)->count();

        $totalItems = $ticketsCount + $registrationsCount;

        $ingresosTickets = (clone $ticketQuery)
            ->whereRaw($paidTicketSql)
            ->sum('price');
        $ingresosInscripciones = (clone $registrationQuery)->sum('price');
        $ingresosTotales = $ingresosTickets + $ingresosInscripciones;

        $ventasHoy = (
            (clone $ticketQuery)
                ->whereDate('purchased_at', Carbon::today())
                ->count()
            +
            (clone $registrationQuery)
                ->whereDate('purchased_at', Carbon::today())
                ->count()
        );

        $ticketCortesias = (clone $ticketQuery)
            ->whereRaw($courtesyTicketSql)
            ->count();
        $registrationCortesias = (clone $registrationQuery)
            ->where('price', 0)
            ->count();
        $boletosCortesia = $ticketCortesias + $registrationCortesias;

        $ventasPagadas =
            (clone $ticketQuery)
                ->whereRaw($paidTicketSql)
                ->count() +
            (clone $registrationQuery)
                ->where('price', '>', 0)
                ->count();

        $ticketPromedio = $ventasPagadas > 0
            ? ($ingresosTotales / $ventasPagadas)
            : 0;

        $porcentajeCortesia = $totalItems > 0
            ? (($boletosCortesia / $totalItems) * 100)
            : 0;

        $ultimaVentaTicket = (clone $ticketQuery)
            ->latest('purchased_at')
            ->first();

        $ultimaInscripcion = (clone $registrationQuery)
            ->latest('purchased_at')
            ->first();

        $ultimaVentaFecha = collect([
            $ultimaVentaTicket?->purchased_at,
            $ultimaInscripcion?->purchased_at,
        ])->filter()->max();

        $ultimosTickets = (clone $ticketQuery)
            ->with('ticket')
            ->latest('purchased_at')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'email' => $item->email,
                'concepto' => $item->ticket->name ?? 'Boleto',
                'evento' => $item->evento->name ?? 'Evento',
                'precio' => $item->price,
                'fecha' => $item->purchased_at,
                'tipo' => 'ticket',
            ]);

        $ultimasInscripciones = (clone $registrationQuery)
            ->with('evento')
            ->latest('purchased_at')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'email' => $item->email,
                'concepto' => $item->evento->name ?? 'Inscripcion',
                'evento' => $item->evento->name ?? 'Evento',
                'precio' => $item->price,
                'fecha' => $item->purchased_at,
                'tipo' => 'inscripcion',
            ]);

        $ultimasVentas = collect($ultimosTickets)
            ->merge($ultimasInscripciones)
            ->sortByDesc('fecha')
            ->take(5)
            ->map(fn($item) => [
                ...$item,
                'fecha' => $item['fecha']->format('d/m/Y H:i'),
            ])
            ->values();

        $chartTickets = (clone $ticketQuery)
            ->select(
                DB::raw('DATE(purchased_at) as date'),
                DB::raw("SUM(CASE WHEN {$courtesyTicketSql} THEN 1 ELSE 0 END) as cortesia"),
                DB::raw("SUM(CASE WHEN {$paidTicketSql} THEN 1 ELSE 0 END) as pagados"),
                DB::raw("SUM(CASE WHEN {$paidTicketSql} THEN price ELSE 0 END) as ingresos"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('date')
            ->get();

        $chartInscripciones = (clone $registrationQuery)
            ->select(
                DB::raw('DATE(purchased_at) as date'),
                DB::raw('SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) as cortesia'),
                DB::raw('SUM(CASE WHEN price > 0 THEN 1 ELSE 0 END) as pagados'),
                DB::raw('SUM(price) as ingresos'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('date')
            ->get();

        $chartData = $chartTickets
            ->concat($chartInscripciones)
            ->groupBy('date')
            ->map(fn($rows) => [
                'date' => $rows->first()->date,
                'pagados' => (int) $rows->sum('pagados'),
                'cortesia' => (int) $rows->sum('cortesia'),
                'total' => (int) $rows->sum('total'),
                'ingresos' => (float) $rows->sum('ingresos'),
                'isToday' => $rows->first()->date === now()->toDateString(),
            ])
            ->values()
            ->sortBy('date')
            ->values();

        $eventTotals = (clone $ticketQuery)
            ->select(
                'event_id',
                DB::raw("SUM(CASE WHEN {$paidTicketSql} THEN price ELSE 0 END) as ingresos"),
                DB::raw("SUM(CASE WHEN {$paidTicketSql} THEN 1 ELSE 0 END) as pagados"),
                DB::raw("SUM(CASE WHEN {$courtesyTicketSql} THEN 1 ELSE 0 END) as cortesia"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('event_id')
            ->get()
            ->concat(
                (clone $registrationQuery)
                    ->select(
                        'event_id',
                        DB::raw('SUM(price) as ingresos'),
                        DB::raw('SUM(CASE WHEN price > 0 THEN 1 ELSE 0 END) as pagados'),
                        DB::raw('SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) as cortesia'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->groupBy('event_id')
                    ->get()
            )
            ->groupBy('event_id')
            ->map(function ($rows, $eventId) use ($eventNameMap) {
                return [
                    'event_id' => $eventId,
                    'evento' => $eventNameMap[$eventId] ?? 'Evento sin nombre',
                    'ingresos' => (float) $rows->sum('ingresos'),
                    'pagados' => (int) $rows->sum('pagados'),
                    'cortesia' => (int) $rows->sum('cortesia'),
                    'total' => (int) $rows->sum('total'),
                ];
            })
            ->values()
            ->sortByDesc('ingresos')
            ->values();

        $paymentMethodTotals = (clone $ticketQuery)
            ->select(
                DB::raw("COALESCE(NULLIF(payment_method, ''), 'sin_metodo') as metodo"),
                DB::raw("SUM(CASE WHEN {$paidTicketSql} THEN price ELSE 0 END) as ingresos"),
                DB::raw("SUM(CASE WHEN {$paidTicketSql} THEN 1 ELSE 0 END) as pagados"),
                DB::raw("SUM(CASE WHEN {$courtesyTicketSql} THEN 1 ELSE 0 END) as cortesia"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('metodo')
            ->get()
            ->concat(
                (clone $registrationQuery)
                    ->select(
                        DB::raw("COALESCE(NULLIF(payment_method, ''), 'sin_metodo') as metodo"),
                        DB::raw('SUM(price) as ingresos'),
                        DB::raw('SUM(CASE WHEN price > 0 THEN 1 ELSE 0 END) as pagados'),
                        DB::raw('SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) as cortesia'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->groupBy('metodo')
                    ->get()
            )
            ->groupBy('metodo')
            ->map(function ($rows, $metodo) {
                return [
                    'metodo' => $metodo,
                    'label' => strtoupper(str_replace('_', ' ', $metodo)),
                    'ingresos' => (float) $rows->sum('ingresos'),
                    'pagados' => (int) $rows->sum('pagados'),
                    'cortesia' => (int) $rows->sum('cortesia'),
                    'total' => (int) $rows->sum('total'),
                ];
            })
            ->values()
            ->sortByDesc('ingresos')
            ->values();

        $saleChannelTotals = (clone $ticketQuery)
            ->select(
                DB::raw("COALESCE(NULLIF(sale_channel, ''), 'sin_canal') as canal"),
                DB::raw("SUM(CASE WHEN {$paidTicketSql} THEN price ELSE 0 END) as ingresos"),
                DB::raw("SUM(CASE WHEN {$paidTicketSql} THEN 1 ELSE 0 END) as pagados"),
                DB::raw("SUM(CASE WHEN {$courtesyTicketSql} THEN 1 ELSE 0 END) as cortesia"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('canal')
            ->get()
            ->concat(
                (clone $registrationQuery)
                    ->select(
                        DB::raw("COALESCE(NULLIF(sale_channel, ''), 'sin_canal') as canal"),
                        DB::raw('SUM(price) as ingresos'),
                        DB::raw('SUM(CASE WHEN price > 0 THEN 1 ELSE 0 END) as pagados'),
                        DB::raw('SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) as cortesia'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->groupBy('canal')
                    ->get()
            )
            ->groupBy('canal')
            ->map(function ($rows, $canal) {
                return [
                    'canal' => $canal,
                    'label' => strtoupper(str_replace('_', ' ', $canal)),
                    'ingresos' => (float) $rows->sum('ingresos'),
                    'pagados' => (int) $rows->sum('pagados'),
                    'cortesia' => (int) $rows->sum('cortesia'),
                    'total' => (int) $rows->sum('total'),
                ];
            })
            ->values()
            ->sortByDesc('ingresos')
            ->values();

        return response()->json([
            'cards' => [
                'total_items' => $totalItems,
                'tickets' => $ticketsCount,
                'registros' => $registrationsCount,
                'ingresos_totales' => (float) $ingresosTotales,
                'ingresos_tickets' => (float) $ingresosTickets,
                'ingresos_registros' => (float) $ingresosInscripciones,
                'ventas_hoy' => $ventasHoy,
                'ultima_venta' => $ultimaVentaFecha
                    ? Carbon::parse($ultimaVentaFecha)->format('d/m/Y H:i')
                    : '-',
                'cortesias' => $boletosCortesia,
                'pagadas' => $ventasPagadas,
                'ticket_promedio' => (float) $ticketPromedio,
                'porcentaje_cortesia' => round($porcentajeCortesia, 2),
            ],
            'ultimas_ventas' => $ultimasVentas,
            'charts' => [
                'volume_by_day' => $chartData,
                'revenue_by_day' => $chartData
                    ->map(fn($row) => [
                        'date' => $row['date'],
                        'ingresos' => (float) $row['ingresos'],
                        'isToday' => $row['isToday'],
                    ])
                    ->values(),
                'events_revenue' => $eventTotals,
                'payment_methods' => $paymentMethodTotals,
                'sale_channels' => $saleChannelTotals,
                'top_events' => $eventTotals->take(7)->values(),
            ],
            'filters' => [
                'selected_event_ids' => $selectedEventIds,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    /**
     * Listado completo de boletos vendidos
     */
    public function boletos(Request $request)
    {
        $events = $this->resolveAccessibleEvents($request);
        $selectedEventIds = $this->resolveSelectedEventIds($request, $events);
        $from = $request->input('from');
        $to = $request->input('to');

        $ticketSales = TicketInstance::query()->ticketSales();
        $registrationSales = TicketInstance::query()->registrationSales();
        $this->applyCommonFilters($ticketSales, $from, $to, $selectedEventIds);
        $this->applyCommonFilters($registrationSales, $from, $to, $selectedEventIds);

        $boletos = $ticketSales
            ->with(['ticket', 'evento', 'user'])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'user_name' => $item->user?->name ?? 'StomTickets',
                    'tipo' => 'boleto',
                    'evento' => $item->evento->name ?? 'Evento',
                    'boleto' => $item->ticket->name ?? 'Boleto',
                    'email' => $item->email ?? 'Taquilla',
                    'nombre' => $item->nombre ?? '-',
                    'metodo' => $item->payment_method,
                    'referencia' => $item->reference,
                    'precio' => $item->price,
                    'es_cortesia' => str_contains(strtoupper((string) $item->email), 'CORTESIA'),
                    'fecha' => $item->purchased_at,
                ];
            });

        $inscripciones = $registrationSales
            ->with(['evento', 'user'])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'user_name' => $item->user?->name ?? 'StomTickets',
                    'tipo' => 'inscripcion',
                    'evento' => $item->evento->name ?? 'Evento',
                    'boleto' => 'N/A - Inscripcion',
                    'nombre' => $item->nombre ?? '-',
                    'email' => $item->email,
                    'metodo' => $item->payment_method,
                    'referencia' => $item->payment_intent_id ?? $item->reference,
                    'precio' => $item->price,
                    'es_cortesia' => (float) $item->price === 0.0,
                    'fecha' => $item->purchased_at,
                ];
            });

        $data = $boletos
            ->concat($inscripciones)
            ->sortByDesc('fecha')
            ->values()
            ->map(function ($item) {
                $item['fecha'] = $item['fecha']->format('d/m/Y H:i:s');
                return $item;
            });

        return response()->json($data);
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
     * @param Collection<int, Eventos> $accessibleEvents
     * @return array<int, string>
     */
    private function resolveSelectedEventIds(Request $request, Collection $accessibleEvents): array
    {
        $rawEventIds = $request->input('event_ids', []);

        if (is_string($rawEventIds)) {
            $rawEventIds = explode(',', $rawEventIds);
        } elseif (!is_array($rawEventIds)) {
            $rawEventIds = [$rawEventIds];
        }

        $normalized = collect($rawEventIds)
            ->map(fn($id) => is_string($id) ? trim($id) : $id)
            ->filter(fn($id) => filled($id))
            ->map(fn($id) => (string) $id)
            ->unique()
            ->values();

        if ($normalized->contains('__all__') || $normalized->isEmpty()) {
            return [];
        }

        $allowed = $accessibleEvents->pluck('id')->map(fn($id) => (string) $id);

        return $normalized
            ->intersect($allowed)
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $eventIds
     */
    private function applyCommonFilters($query, ?string $from, ?string $to, array $eventIds): void
    {
        if ($from && $to) {
            $query->whereBetween('purchased_at', [
                $from . ' 00:00:00',
                $to . ' 23:59:59',
            ]);
        } elseif ($from) {
            $query->where('purchased_at', '>=', $from . ' 00:00:00');
        } elseif ($to) {
            $query->where('purchased_at', '<=', $to . ' 23:59:59');
        }

        if (!empty($eventIds)) {
            $query->whereIn('event_id', $eventIds);
        }
    }
}
