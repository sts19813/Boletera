<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TicketInstance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\RegistrationInstance;

class DashboardController extends Controller
{
    /**
     * Vista principal del dashboard
     */
    public function index()
    {
        return view('admin.dashboard.index');
    }

    /**
     * Endpoint AJAX para métricas y gráfica
     */
    public function data(Request $request)
    {
        // ================================
        // QUERY BASE (CON FILTRO)
        // ================================
        $ticketQuery = TicketInstance::query();
        $registrationQuery = RegistrationInstance::query();

        if ($request->from && $request->to) {
            $ticketQuery->whereBetween('ticket_instances.purchased_at', [
                $request->from . ' 00:00:00',
                $request->to . ' 23:59:59'
            ]);

            $registrationQuery->whereBetween('registered_at', [
                $request->from . ' 00:00:00',
                $request->to . ' 23:59:59'
            ]);
        }

        // ================================
        // KPIs
        // ================================
        $totalBoletos =
            (clone $ticketQuery)->count() +
            (clone $registrationQuery)->count();

        // 💰 INGRESOS (DESDE INSTANCIAS)
        $ingresosTickets = (clone $ticketQuery)
            ->where('ticket_instances.email', '!=', 'CORTESIA')
            ->sum('price');

        $ingresosInscripciones = (clone $registrationQuery)->sum('price');

        $ingresosTotales = $ingresosTickets + $ingresosInscripciones;

        $ventasHoy =
            (clone $ticketQuery)
                ->whereDate('ticket_instances.purchased_at', Carbon::today())
                ->count()
            +
            (clone $registrationQuery)
                ->whereDate('registered_at', Carbon::today())
                ->count();

        $boletosCortesia =
            (clone $ticketQuery)
                ->where('ticket_instances.email', 'CORTESIA')
                ->count()
            +
            (clone $registrationQuery)
                ->where('price', 0)
                ->count();

        // ================================
        // ÚLTIMA VENTA (GLOBAL)
        // ================================
        $ultimaVentaTicket = (clone $ticketQuery)
            ->latest('ticket_instances.purchased_at')
            ->first();

        $ultimaInscripcion = (clone $registrationQuery)
            ->latest('registered_at')
            ->first();

        $ultimaVentaFecha = collect([
            $ultimaVentaTicket?->purchased_at,
            $ultimaInscripcion?->registered_at,
        ])->filter()->max();

        // ================================
        // ÚLTIMAS 5 VENTAS (MEZCLADAS)
        // ================================
        $ultimosTickets = (clone $ticketQuery)
            ->with('ticket')
            ->latest('ticket_instances.purchased_at')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'email' => $item->email,
                'concepto' => $item->ticket->name ?? 'Boleto',
                'precio' => $item->price, // ✅ desde instancia
                'fecha' => $item->purchased_at,
                'tipo' => 'ticket',
            ]);

        $ultimasInscripciones = (clone $registrationQuery)
            ->with('evento')
            ->latest('registered_at')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'email' => $item->email,
                'concepto' => $item->evento->name ?? 'Inscripción',
                'precio' => $item->price, // ✅ desde instancia
                'fecha' => $item->registered_at,
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

        // ================================
        // GRÁFICA: VENTAS POR DÍA (SUMADAS)
        // ================================
        $chartTickets = (clone $ticketQuery)
            ->select(
                DB::raw('DATE(purchased_at) as date'),
                DB::raw("SUM(CASE WHEN email = 'CORTESIA' THEN 1 ELSE 0 END) as cortesia"),
                DB::raw("SUM(CASE WHEN email != 'CORTESIA' THEN 1 ELSE 0 END) as pagados"),
                DB::raw("COUNT(*) as total")
            )
            ->groupBy('date')
            ->get();

        $chartInscripciones = (clone $registrationQuery)
            ->select(
                DB::raw('DATE(registered_at) as date'),
                DB::raw("SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) as cortesia"),
                DB::raw("SUM(CASE WHEN price > 0 THEN 1 ELSE 0 END) as pagados"),
                DB::raw("COUNT(*) as total")
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
                'isToday' => $rows->first()->date === now()->toDateString(),
            ])
            ->values()
            ->sortBy('date')
            ->values();

        // ================================
        // RESPONSE
        // ================================
        return response()->json([
            'cards' => [
                'total_boletos' => $totalBoletos,
                'ingresos' => number_format($ingresosTotales, 2),
                'ventas_hoy' => $ventasHoy,
                'ultima_venta' => $ultimaVentaFecha
                    ? Carbon::parse($ultimaVentaFecha)->format('d/m/Y H:i')
                    : '-',
                'cortesia' => $boletosCortesia,
            ],
            'ultimas_ventas' => $ultimasVentas,
            'chart' => $chartData,
        ]);
    }


    /**
     * Listado completo de boletos vendidos
     */
    public function boletos()
    {
        $boletos = TicketInstance::with('ticket')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'tipo' => 'boleto',
                    'boleto' => $item->ticket->name ?? 'Boleto',
                    'email' => $item->email ?? 'Taquilla',
                    'metodo' => $item->payment_method,
                    'referencia' => $item->reference,
                    'precio' => $item->price,
                    'fecha' => $item->purchased_at,
                ];
            });

        $inscripciones = RegistrationInstance::with('evento')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'tipo' => 'inscripcion',
                    'boleto' => $item->evento->name ?? 'Inscripción',
                    'email' => $item->email,
                    'metodo' => 'card',
                    'referencia' =>  '',
                    'precio' => $item->price,
                    'fecha' => $item->registered_at,
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

}
