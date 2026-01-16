<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketInstance;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;

class CorteController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->getCorteData(
            $request->from,
            $request->to
        );

        return view('admin.corte.index', [
            'corte' => $data['rows'],
            'totales' => $data['totales'],
        ]);
    }

    public function exportGeneral(Request $request)
    {
        $data = $this->getCorteData(
            $request->from,
            $request->to
        );
        $corte = $data['rows'];
        $totales = $data['totales'];

        // Fecha y hora con minutos y segundos
        $timestamp = now()->format('Y-m-d_H-i-s');

        $rows = $corte->map(fn($row) => [
            $row['tipo'],
            $row['precio_unitario'],
            $row['vendidos'],
            $row['cortesias'],
            $row['pagados'],

            $row['web_qty'],
            $row['web_total'],

            $row['cash_qty'],
            $row['cash_total'],

            $row['card_qty'],
            $row['card_total'],

            $row['total_generado'],
        ]);

        // Agregar fila TOTAL (igual que la vista)
        $rows->push([
            'TOTAL',
            '',
            $totales['vendidos'],
            $totales['cortesias'],
            $totales['pagados'],

            $totales['web_qty'],
            $totales['web_total'],

            $totales['cash_qty'],
            $totales['cash_total'],

            $totales['card_qty'],
            $totales['card_total'],

            $totales['gran_total'],
        ]);

        return $this->streamExcel(
            "corte_ventas_{$timestamp}.xlsx",
            [
                'Tipo',
                'Precio',
                'Boletos entregados',
                'Cortesías',
                'Pagados',
                'Web (cantidad)',
                'Total Web',
                'Cash taquilla',
                'Total Cash',
                'Card taquilla',
                'Total Card',
                'Total Global',
            ],
            $rows
        );
    }

    /**
     * ===============================
     * CORTE AGRUPADO POR TIPO
     * ===============================
     */
    protected function getCorteData($from = null, $to = null)
    {
        $query = Ticket::query()
            ->leftJoin('ticket_instances as ti', 'ti.ticket_id', '=', 'tickets.id');

        // ===============================
        // Filtros por fecha y hora
        // ===============================
        if ($from) {
            $query->where('ti.created_at', '>=', $from);
        }

        if ($to) {
            $query->where('ti.created_at', '<=', $to);
        }

        $rows = $query
            ->select([
                'tickets.type',

                DB::raw('MAX(tickets.total_price) as precio_unitario'),
                DB::raw('COUNT(ti.id) as vendidos'),

                // Web (Stripe)
                DB::raw("
                SUM(
                    CASE
                        WHEN ti.sale_channel = 'stripe'
                             AND ti.email NOT LIKE '%CORTESIA%'
                        THEN 1 ELSE 0
                    END
                ) as web_qty
            "),

                // Cortesías
                DB::raw("
                SUM(
                    CASE
                        WHEN ti.email LIKE '%CORTESIA%'
                        THEN 1 ELSE 0
                    END
                ) as cortesias
            "),

                // Taquilla cash
                DB::raw("
                SUM(
                    CASE
                        WHEN ti.sale_channel = 'taquilla'
                             AND ti.payment_method = 'cash'
                             AND ti.email NOT LIKE '%CORTESIA%'
                        THEN 1 ELSE 0
                    END
                ) as taquilla_cash_qty
            "),

                // Taquilla card
                DB::raw("
                SUM(
                    CASE
                        WHEN ti.sale_channel = 'taquilla'
                             AND ti.payment_method = 'card'
                             AND ti.email NOT LIKE '%CORTESIA%'
                        THEN 1 ELSE 0
                    END
                ) as taquilla_card_qty
            "),
            ])
            ->groupBy('tickets.type')
            ->orderBy('tickets.type')
            ->get()
            ->map(function ($row) {
                $precio = (float) $row->precio_unitario;
                $vendidos = (int) $row->vendidos;
                $cortesias = (int) $row->cortesias;
                $pagados = $vendidos - $cortesias;

                $cashQty = (int) $row->taquilla_cash_qty;
                $cardQty = (int) $row->taquilla_card_qty;
                $webQty = (int) $row->web_qty;

                return [
                    'tipo' => $row->type,

                    'precio_unitario' => $precio,

                    'vendidos' => $vendidos,
                    'cortesias' => $cortesias,
                    'pagados' => $pagados,

                    // Taquilla
                    'cash_qty' => $cashQty,
                    'cash_total' => $cashQty * $precio,

                    'card_qty' => $cardQty,
                    'card_total' => $cardQty * $precio,

                    // Web
                    'web_qty' => $webQty,
                    'web_total' => $webQty * $precio,

                    // Total global por tipo
                    'total_generado' => $pagados * $precio,
                ];
            });

        $totales = [
            'vendidos' => $rows->sum('vendidos'),
            'cortesias' => $rows->sum('cortesias'),
            'pagados' => $rows->sum('pagados'),

            'web_qty' => $rows->sum('web_qty'),
            'web_total' => $rows->sum('web_total'),

            'cash_qty' => $rows->sum('cash_qty'),
            'cash_total' => $rows->sum('cash_total'),

            'card_qty' => $rows->sum('card_qty'),
            'card_total' => $rows->sum('card_total'),

            'gran_total' => $rows->sum('total_generado'),
        ];

        return compact('rows', 'totales');
    }

    /**
     * Stream CSV compatible con Excel
     */
    protected function streamExcel(string $filename, array $headers, $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
