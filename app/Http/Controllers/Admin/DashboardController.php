<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketInstance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     * Endpoint AJAX para metricas y grafica
     */
    public function data(Request $request)
    {
        $ticketQuery = TicketInstance::query()->ticketSales();
        $registrationQuery = TicketInstance::query()->registrationSales();

        if ($request->from && $request->to) {
            $from = $request->from . ' 00:00:00';
            $to = $request->to . ' 23:59:59';

            $ticketQuery->whereBetween('purchased_at', [$from, $to]);
            $registrationQuery->whereBetween('purchased_at', [$from, $to]);
        }

        $totalBoletos =
            (clone $ticketQuery)->count() +
            (clone $registrationQuery)->count();

        $ingresosTickets = (clone $ticketQuery)
            ->where('email', '!=', 'CORTESIA')
            ->sum('price');

        $ingresosInscripciones = (clone $registrationQuery)->sum('price');
        $ingresosTotales = $ingresosTickets + $ingresosInscripciones;

        $ventasHoy =
            (clone $ticketQuery)
                ->whereDate('purchased_at', Carbon::today())
                ->count() +
            (clone $registrationQuery)
                ->whereDate('purchased_at', Carbon::today())
                ->count();

        $boletosCortesia =
            (clone $ticketQuery)
                ->where('email', 'CORTESIA')
                ->count() +
            (clone $registrationQuery)
                ->where('price', 0)
                ->count();

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
                DB::raw("SUM(CASE WHEN email = 'CORTESIA' THEN 1 ELSE 0 END) as cortesia"),
                DB::raw("SUM(CASE WHEN email != 'CORTESIA' THEN 1 ELSE 0 END) as pagados"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('date')
            ->get();

        $chartInscripciones = (clone $registrationQuery)
            ->select(
                DB::raw('DATE(purchased_at) as date'),
                DB::raw('SUM(CASE WHEN price = 0 THEN 1 ELSE 0 END) as cortesia'),
                DB::raw('SUM(CASE WHEN price > 0 THEN 1 ELSE 0 END) as pagados'),
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
                'isToday' => $rows->first()->date === now()->toDateString(),
            ])
            ->values()
            ->sortBy('date')
            ->values();

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
        $boletos = TicketInstance::ticketSales()
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
                    'fecha' => $item->purchased_at,
                ];
            });

        $inscripciones = TicketInstance::registrationSales()
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
}
