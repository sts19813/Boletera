<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lot;
use App\Models\Ticket;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;


class LotController extends Controller
{

    public function index(Request $request)
    {
        $query = Ticket::with(['stage.phase.project']);

        if ($request->project_id) {
            $query->whereHas('stage.phase.project', function($q) use ($request) {
                $q->where('id', $request->project_id);
            });
        }
        if ($request->phase_id) {
            $query->whereHas('stage.phase', function($q) use ($request) {
                $q->where('id', $request->phase_id);
            });
        }
        if ($request->stage_id) {
            $query->where('stage_id', $request->stage_id);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'stage_id' => 'required|exists:stages,id',
            'name' => 'required|string',
            'depth' => 'nullable|numeric',
            'front' => 'nullable|numeric',
            'area' => 'nullable|numeric',
            'price_square_meter' => 'nullable|numeric',
            'total_price' => 'nullable|numeric',
            'status' => 'nullable|string',
            'chepina' => 'nullable|string'
        ]);

        $lot = Ticket::create($request->all());

        return response()->json(
            $lot->load(['stage.phase.project', 'customFields']),
            201
        );
    }

    public function show(Lot $lot)
    {
        return $lot->load(['stage.phase.project', 'customFields']);
    }

    public function update(Request $request, Lot $lot)
    {
        $lot->update($request->all());
        return response()->json($lot->load(['stage.phase.project', 'customFields']));
    }

    public function destroy(Lot $lot)
    {
        $lot->delete();
        return response()->json(null, 204);
    }


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx'
        ]);

        /**
         * ============================================================
         * Mapeo de estatus Español → Inglés (para guardar)
         * ============================================================
         */
        $statusReverseMap = [
            "Disponible" => "for_sale",
            "Vendido"    => "sold",
            "Apartado"   => "reserved",
            "Bloqueado"  => "locked_sale"
        ];

        $file = $request->file('file')->getPathname();
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $errors = [];
        $successCount = 0;

        /**
         * ============================================================
         * 1) OBTENER IDS BASE (PROYECTO, FASE, ETAPA)
         * ============================================================
         */
        $baseProjectId = null;
        $basePhaseId   = null;
        $baseStageId   = null;

        foreach (array_slice($rows, 3) as $row) {
            if (!empty($row[4])) { // "name"
                $baseProjectId = $row[1];
                $basePhaseId   = $row[2];
                $baseStageId   = $row[3];
                break;
            }
        }

        if (!$baseProjectId || !$basePhaseId || !$baseStageId) {
            return response()->json([
                'success' => 0,
                'errors' => ["No se encontraron IDs base en la plantilla."]
            ], 400);
        }

        /**
         * ============================================================
         * 2) PROCESAR TODAS LAS FILAS DEL EXCEL
         * ============================================================
         */
        foreach (array_slice($rows, 3) as $index => $row) {

            $excelRow = $index + 4;

            if (empty($row[4])) continue; // nombre vacío → ignorar

            $id          = $row[0];
            $project_id  = $row[1] ?: $baseProjectId;
            $phase_id    = $row[2] ?: $basePhaseId;
            $stage_id    = $row[3] ?: $baseStageId;

            /**
             * Convertir ESTATUS de español → clave interna
             */
            $statusSpanish = trim($row[10]);
            $statusInternal = $statusReverseMap[$statusSpanish] ?? 'for_sale';

            $data = [
                'project_id' => $project_id,
                'phase_id'   => $phase_id,
                'stage_id'   => $stage_id,
                'name'       => $row[4],
                'depth'      => $row[5],
                'front'      => $row[6],
                'area'       => $row[7],
                'price_square_meter' => $row[8],
                'total_price' => $row[9],
                'status'     => $statusInternal, // ← Mapeo correcto
                'chepina'    => $row[11],
            ];

            // Validación
            $validator = Validator::make($data, [
                'project_id' => 'required|exists:projects,id',
                'phase_id'   => 'required|exists:phases,id',
                'stage_id'   => 'required|exists:stages,id',
                'name'       => 'required|string',
                'depth'      => 'nullable|numeric|min:0',
                'front'      => 'nullable|numeric|min:0',
                'area'       => 'nullable|numeric|min:0',
                'price_square_meter' => 'nullable|numeric|min:0',
                'total_price' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                $errors[] = "Fila $excelRow: " . implode(" | ", $validator->errors()->all());
                continue;
            }

            /**
             * ============================================================
             * 3) ¿ACTUALIZAR O CREAR?
             * ============================================================
             */
            if ($id) {
                // Actualizar existente
                $lot = Lot::find($id);

                if ($lot) {
                    $lot->update($data);
                } else {
                    $errors[] = "Fila $excelRow: No existe el lote con ID $id";
                    continue;
                }

            } else {
                // Crear nuevo usando los IDs base correctos
                Lot::create($data);
            }

            $successCount++;
        }

        return response()->json([
            'success' => $successCount,
            'errors' => $errors
        ], 200);
    }


    /**
     * Metodo para realizar el cambio de un estatus desde el combo del datatables del registro
     * @param Request $request contiene el nuevo estatus
     * @param Lot $lot unidad o lote a cambiar su estatus
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, Lot $lot)
    {
        $request->validate([
            'status' => 'required|in:for_sale,sold,reserved,locked_sale'
        ]);

        $lot->status = $request->status;
        $lot->save();

        return response()->json(['message' => 'Status actualizado']);
    }

    /**
     * metodo para subir una chepina de una unidad. desde el datatables
     * @param Request $request contiene la imagen nueva a cargar
     * @param Lot $lot contiene la unidad a la que se le actualizara el registro de la chepina  
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadChepina(Request $request, Lot $lot)
    {
        if (!$request->hasFile('chepina')) {
            return response()->json([
                'message' => 'No se envió ningún archivo'
            ], 400);
        }

        $file = $request->file('chepina');
        $filename = time() . '_' . $file->getClientOriginalName();

        // Ruta absoluta hacia public/chepinas
        $destination = public_path('chepinas');

        // Crear carpeta si no existe
        if (!file_exists($destination)) {
            mkdir($destination, 0777, true);
        }

        // Mover archivo directamente al public/
        $file->move($destination, $filename);

        // Guardar nombre en BD
        $lot->update([
            'chepina' => $filename
        ]);

        return response()->json([
            'message' => 'Imagen subida correctamente',
            'file' => $filename
        ]);
    }
}
