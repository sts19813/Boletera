<?php

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use App\Models\Eventos;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TicketViewController extends Controller
{
    public function index()
    {
        $events = Eventos::select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('api.tickets.index', compact('events'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        DB::beginTransaction();

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            unset($rows[1]);

            foreach ($rows as $row) {
                $id = $row['A'] ?? null;
                $eventId = $row['B'] ?? null;
                $name = $row['C'] ?? null;

                if (!$name || !$eventId) {
                    continue;
                }

                $payload = [
                    'event_id' => $eventId,
                    'name' => $name,
                    'type' => $row['D'] ?? null,
                    'total_price' => $row['E'] ?? 0,
                    'stock' => $row['F'] ?? 0,
                    'sold' => $row['G'] ?? 0,
                    'available_from' => $row['H'] ?: null,
                    'available_until' => $row['I'] ?: null,
                    'description' => $row['J'] ?? null,
                    'is_courtesy' => (bool) ($row['K'] ?? false),
                    'status' => $row['L'] ?? 'active',
                ];

                if (empty($id)) {
                    Ticket::create($payload);
                    continue;
                }

                $ticket = Ticket::find($id);

                if ($ticket) {
                    $ticket->update($payload);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Importacion completada correctamente',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error en importacion',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
