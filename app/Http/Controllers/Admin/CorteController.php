<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Eventos;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;

class CorteController extends Controller
{
    public function index(Request $request)
    {
        $events = $this->resolveAccessibleEvents($request);
        $selectedEventIds = $this->resolveSelectedEventIds($request, $events);

        $data = $this->getCorteData(
            $request->input('from'),
            $request->input('to'),
            $selectedEventIds
        );

        return view('admin.corte.index', [
            'corte' => $data['rows'],
            'totales' => $data['totales'],
            'events' => $events,
            'selectedEventIds' => $selectedEventIds,
        ]);
    }

    public function exportGeneral(Request $request)
    {
        $events = $this->resolveAccessibleEvents($request);
        $selectedEventIds = $this->resolveSelectedEventIds($request, $events);

        $data = $this->getCorteData(
            $request->input('from'),
            $request->input('to'),
            $selectedEventIds
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
    protected function getCorteData($from = null, $to = null, array $eventIds = [])
    {
        $fromDateTime = $this->normalizeDateTimeInput($from);
        $toDateTime = $this->normalizeDateTimeInput($to);

        $baseQuery = DB::table('ticket_instances as ti')
            ->leftJoin('tickets', 'ti.ticket_id', '=', 'tickets.id');

        if ($fromDateTime) {
            $baseQuery->where('ti.created_at', '>=', $fromDateTime);
        }

        if ($toDateTime) {
            $baseQuery->where('ti.created_at', '<=', $toDateTime);
        }

        if (!empty($eventIds)) {
            $baseQuery->whereIn('ti.event_id', $eventIds);
        }

        $ticketRows = (clone $baseQuery)
            ->where(function ($query) {
                $query->where('ti.sale_type', 'ticket')
                    ->orWhereNull('ti.sale_type');
            })
            ->select([
                DB::raw("COALESCE(NULLIF(tickets.type, ''), 'BOLETO') as tipo"),
                DB::raw('AVG(CASE WHEN UPPER(COALESCE(ti.email, \'\')) NOT LIKE \'%CORTESIA%\' THEN ti.price END) as precio_unitario'),
                DB::raw('COUNT(ti.id) as vendidos'),
                DB::raw("SUM(CASE WHEN UPPER(COALESCE(ti.email, '')) LIKE '%CORTESIA%' THEN 1 ELSE 0 END) as cortesias"),
                DB::raw("SUM(CASE WHEN ti.sale_channel = 'stripe' AND UPPER(COALESCE(ti.email, '')) NOT LIKE '%CORTESIA%' THEN 1 ELSE 0 END) as web_qty"),
                DB::raw("SUM(CASE WHEN ti.sale_channel = 'stripe' AND UPPER(COALESCE(ti.email, '')) NOT LIKE '%CORTESIA%' THEN ti.price ELSE 0 END) as web_total"),
                DB::raw("SUM(CASE WHEN ti.sale_channel = 'taquilla' AND ti.payment_method = 'cash' AND UPPER(COALESCE(ti.email, '')) NOT LIKE '%CORTESIA%' THEN 1 ELSE 0 END) as taquilla_cash_qty"),
                DB::raw("SUM(CASE WHEN ti.sale_channel = 'taquilla' AND ti.payment_method = 'cash' AND UPPER(COALESCE(ti.email, '')) NOT LIKE '%CORTESIA%' THEN ti.price ELSE 0 END) as taquilla_cash_total"),
                DB::raw("SUM(CASE WHEN ti.sale_channel = 'taquilla' AND ti.payment_method = 'card' AND UPPER(COALESCE(ti.email, '')) NOT LIKE '%CORTESIA%' THEN 1 ELSE 0 END) as taquilla_card_qty"),
                DB::raw("SUM(CASE WHEN ti.sale_channel = 'taquilla' AND ti.payment_method = 'card' AND UPPER(COALESCE(ti.email, '')) NOT LIKE '%CORTESIA%' THEN ti.price ELSE 0 END) as taquilla_card_total"),
                DB::raw("SUM(CASE WHEN UPPER(COALESCE(ti.email, '')) NOT LIKE '%CORTESIA%' THEN ti.price ELSE 0 END) as total_generado"),
            ])
            ->groupBy('tipo')
            ->get();

        $registrationRows = (clone $baseQuery)
            ->where('ti.sale_type', 'registration')
            ->select([
                DB::raw("'INSCRIPCION' as tipo"),
                DB::raw('AVG(CASE WHEN ti.price > 0 THEN ti.price END) as precio_unitario'),
                DB::raw('COUNT(ti.id) as vendidos'),
                DB::raw('SUM(CASE WHEN ti.price = 0 THEN 1 ELSE 0 END) as cortesias'),
                DB::raw("SUM(CASE WHEN ti.sale_channel = 'stripe' AND ti.price > 0 THEN 1 ELSE 0 END) as web_qty"),
                DB::raw("SUM(CASE WHEN ti.sale_channel = 'stripe' AND ti.price > 0 THEN ti.price ELSE 0 END) as web_total"),
                DB::raw("SUM(CASE WHEN ti.sale_channel = 'taquilla' AND ti.payment_method = 'cash' AND ti.price > 0 THEN 1 ELSE 0 END) as taquilla_cash_qty"),
                DB::raw("SUM(CASE WHEN ti.sale_channel = 'taquilla' AND ti.payment_method = 'cash' AND ti.price > 0 THEN ti.price ELSE 0 END) as taquilla_cash_total"),
                DB::raw("SUM(CASE WHEN ti.sale_channel = 'taquilla' AND ti.payment_method = 'card' AND ti.price > 0 THEN 1 ELSE 0 END) as taquilla_card_qty"),
                DB::raw("SUM(CASE WHEN ti.sale_channel = 'taquilla' AND ti.payment_method = 'card' AND ti.price > 0 THEN ti.price ELSE 0 END) as taquilla_card_total"),
                DB::raw('SUM(CASE WHEN ti.price > 0 THEN ti.price ELSE 0 END) as total_generado'),
            ])
            ->groupBy('tipo')
            ->get();

        $rows = $ticketRows
            ->concat($registrationRows)
            ->groupBy('tipo')
            ->map(function ($groupedRows, $tipo) {
                $vendidos = (int) $groupedRows->sum('vendidos');
                $cortesias = (int) $groupedRows->sum('cortesias');
                $pagados = $vendidos - $cortesias;
                $precioUnitario = (float) $groupedRows
                    ->pluck('precio_unitario')
                    ->filter(fn($price) => $price !== null)
                    ->avg();

                return [
                    'tipo' => $tipo,
                    'precio_unitario' => $precioUnitario,
                    'vendidos' => $vendidos,
                    'cortesias' => $cortesias,
                    'pagados' => $pagados,
                    'cash_qty' => (int) $groupedRows->sum('taquilla_cash_qty'),
                    'cash_total' => (float) $groupedRows->sum('taquilla_cash_total'),
                    'card_qty' => (int) $groupedRows->sum('taquilla_card_qty'),
                    'card_total' => (float) $groupedRows->sum('taquilla_card_total'),
                    'web_qty' => (int) $groupedRows->sum('web_qty'),
                    'web_total' => (float) $groupedRows->sum('web_total'),
                    'total_generado' => (float) $groupedRows->sum('total_generado'),
                ];
            })
            ->sortBy('tipo')
            ->values();

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

    private function normalizeDateTimeInput($value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace('T', ' ', $value);

        if (strlen($value) === 16) {
            $value .= ':00';
        }

        return $value;
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
