<?php

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TicketViewController extends Controller
{
    public function index()
    {
        //  Cargar todos los proyectos para el combo
        $projects = Project::select('id', 'name')->get();

        //  Opcional: cargar fases y etapas vacías, se llenarán dinámicamente según selección
        $phases = collect(); // inicialmente vacío
        $stages = collect(); // inicialmente vacío

        //  Retornar la vista con los combos
        return view('api.tickets.index', compact('projects', 'phases', 'stages'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        DB::beginTransaction();

        try {

            $spreadsheet = IOFactory::load($request->file('file')->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            // Eliminar encabezados
            unset($rows[1]);

            foreach ($rows as $row) {

                // Ajusta las columnas según tu Excel
                $id = $row['A'] ?? null;
                $stageId = $row['B'] ?? null;
                $name = $row['C'] ?? null;

                if (!$name) {
                    continue; // fila vacía
                }

                // Si NO tiene stage_id → nuevo
                if (empty($id) || empty($stageId)) {

                    Ticket::create([
                        'stage_id' => $stageId,
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
                    ]);

                    continue;
                }

                // Actualizar
                $ticket = Ticket::find($id);

                if ($ticket) {
                    $ticket->update([
                        'stage_id' => $stageId,
                        'name' => $name,
                        'type' => $row['D'],
                        'total_price' => $row['E'],
                        'stock' => $row['F'],
                        'sold' => $row['G'],
                        'available_from' => $row['H'],
                        'available_until' => $row['I'],
                        'description' => $row['J'],
                        'is_courtesy' => (bool) $row['K'],
                        'status' => $row['L'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Importación completada correctamente'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Error en importación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
