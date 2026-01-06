<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TicketInstance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        $query = TicketInstance::query();

        if ($request->from && $request->to) {
            $query->whereBetween('ticket_instances.purchased_at', [
                $request->from . ' 00:00:00',
                $request->to . ' 23:59:59'
            ]);
        }

        // ================================
        // KPIs
        // ================================
        $totalBoletos = (clone $query)->count();

        $ingresosTotales = (clone $query)
            ->join('tickets', 'ticket_instances.ticket_id', '=', 'tickets.id')
            ->where('ticket_instances.email', '!=', 'CORTESIA')
            ->sum('tickets.total_price');

        $ventasHoy = (clone $query)
            ->whereDate('ticket_instances.purchased_at', Carbon::today())
            ->count();

        $boletosCortesia = (clone $query)
            ->where('ticket_instances.email', 'CORTESIA')
            ->count();

        $ultimaVenta = (clone $query)
            ->latest('ticket_instances.purchased_at')
            ->first();

        // ================================
        // ÚLTIMAS 5 VENTAS
        // ================================
        $ultimasVentas = (clone $query)
            ->with('ticket')
            ->latest('ticket_instances.purchased_at')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'email' => $item->email,
                    'ticket' => $item->ticket->name ?? '-',
                    'precio' => $item->ticket->total_price ?? 0,
                    'fecha' => $item->purchased_at->format('d/m/Y H:i'),
                    'payment_intent' => $item->payment_intent_id,
                ];
            });

        // ================================
        // GRÁFICA: VENTAS POR DÍA
        // ================================
        $chartData = (clone $query)
            ->select(
                DB::raw('DATE(ticket_instances.purchased_at) as date'),
                DB::raw("SUM(CASE WHEN ticket_instances.email = 'CORTESIA' THEN 1 ELSE 0 END) as cortesia"),
                DB::raw("SUM(CASE WHEN ticket_instances.email != 'CORTESIA' THEN 1 ELSE 0 END) as pagados"),
                DB::raw("COUNT(*) as total")
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => $row->date,
                    'pagados' => (int) $row->pagados,
                    'cortesia' => (int) $row->cortesia,
                    'total' => (int) $row->total,
                    'isToday' => $row->date === now()->toDateString(),
                ];
            });

        // ================================
        // RESPONSE
        // ================================
        return response()->json([
            'cards' => [
                'total_boletos' => $totalBoletos,
                'ingresos' => number_format($ingresosTotales, 2),
                'ventas_hoy' => $ventasHoy,
                'ultima_venta' => $ultimaVenta
                    ? $ultimaVenta->purchased_at->format('d/m/Y H:i')
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
            ->orderBy('purchased_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'boleto' => $item->ticket->name ?? '-',
                    'email' => $item->email ?? 'Taquilla',
                    'metodo' => $item->payment_method,
                    'referencia' => $item->reference,
                    'fecha' => $item->purchased_at->format('d/m/Y H:i:s'),
                ];
            });

        return response()->json($boletos);
    }

}
