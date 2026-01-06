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
        // KPIs
        // ================================
        $totalBoletos = TicketInstance::count();

        $ingresosTotales = TicketInstance::join('tickets', 'ticket_instances.ticket_id', '=', 'tickets.id')
            ->where('ticket_instances.email', '!=', 'CORTESIA')
            ->sum('tickets.total_price');


        $ventasHoy = TicketInstance::whereDate('purchased_at', Carbon::today())->count();
        $boletosCortesia = TicketInstance::where('email', 'CORTESIA')->count();

        $ultimaVenta = TicketInstance::latest('purchased_at')->first();

        // ================================
        // Últimas 5 ventas
        // ================================
        $ultimasVentas = TicketInstance::with('ticket')
            ->latest('purchased_at')
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
        // Gráfica: ventas por día
        // ================================
        $chartData = TicketInstance::select(
            DB::raw('DATE(purchased_at) as date'),
            DB::raw('COUNT(*) as total')
        )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => $row->date,
                    'value' => (int) $row->total,
                ];
            });

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
