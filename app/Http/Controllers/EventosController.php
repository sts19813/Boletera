<?php

namespace App\Http\Controllers;

use App\Models\Eventos;
use App\Models\Lote;
use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EventosController extends Controller
{

    protected FileUploadService $fileUploadService;

    /**
     * Constructor
     * Inyecta los servicios necesarios
     */
    public function __construct( FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }



    /**
     * Listado de Eventos públicos
     */
    public function index()
    {
        $events = Eventos::orderBy('created_at', 'desc')->get();
        return view('events.index', compact('events'));
    }

    /**
     * Listado de Eventos administrativos
     */
    public function admin()
    {
        $lots = Eventos::orderBy('updated_at', 'desc')->get();
        return view('lots.admin', compact('lots'));
    }

    /**
     * Fetch de lotes para un proyecto/fase/etapa específico
     */
    public function fetch(Request $request)
    {
        $request->validate([
            'project_id' => 'required|integer',
            'phase_id' => 'required|integer',
            'stage_id' => 'required|integer',
        ]);



        return response()->json();
    }

    /**
     * Formulario para crear un nuevo desarrollo
     */
    public function create()
    {
       
        $Eventos = Eventos::select('id', 'name')->get();
        return view('events.create', compact( 'Eventos'));
    }

    /**
     * Guardar un nuevo evento en la base de datos
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string',
            'total_asientos'  => 'required|integer|min:1',

            'event_date'      => 'required|date',
            'status'          => 'required|string|in:activo,inactivo',

            'source_type'     => 'required|string|in:adara,naboo',

            'project_id'      => 'nullable|integer',
            'phase_id'        => 'nullable|integer',
            'stage_id'        => 'nullable|integer',

            'modal_color'     => 'nullable|string|max:50',
            'modal_selector'  => 'nullable|string|max:255',
            'color_primario'  => 'nullable|string|max:50',
            'color_acento'    => 'nullable|string|max:50',

            'redirect_return'   => 'nullable|string|max:255',
            'redirect_next'     => 'nullable|string|max:255',
            'redirect_previous' => 'nullable|string|max:255',

            'svg_image'       => 'nullable|mimes:svg,xml',
            'png_image'       => 'nullable|image|mimes:png,jpg,jpeg',
        ]);

        // Mapeo limpio y correcto según tu modelo
        $data = $request->only([
            'name',
            'description',
            'total_asientos',
            'project_id',
            'phase_id',
            'stage_id',
            'modal_color',
            'modal_selector',
            'color_primario',
            'color_acento',
            'redirect_return',
            'redirect_next',
            'redirect_previous',
        ]);

        // Campos extra que tienes en el formulario pero no en el modelo
        // Si ya los agregaste en migración, habilítalos en $fillable del modelo
        $data['event_date'] = $request->event_date ?? null;
        $data['status']     = $request->status ?? 'activo';
        $data['source_type'] = $request->source_type ?? 'naboo';

        // Subida de imágenes
        if ($request->hasFile('svg_image')) {
            $data['svg_image'] = $this->fileUploadService
                ->upload($request->file('svg_image'), 'eventos-assets');
        }

        if ($request->hasFile('png_image')) {
            $data['png_image'] = $this->fileUploadService
                ->upload($request->file('png_image'), 'eventos-assets');
        }

        Eventos::create($data);

        return redirect()
            ->route('events.index')
            ->with('success', 'Evento creado correctamente.');
    }


    /**
     * Obtener fases de un proyecto
     */
    public function getPhases($id)
    {
       
    }

    /**
     * Obtener etapas de un proyecto/fase
     */
    public function getStages($projectId, $phaseId)
    {
      
    }

    /**
     * Configurador de un evento (vincula boletos con SVG)
     */
    public function configurator($id)
    {
        $lot = Eventos::findOrFail($id);
        $Eventos = Eventos::all();
        $sourceType = $lot->source_type ?? 'adara';

        $projects = [];
        $lots = [];
        $dbLotes = [];

    
        $projects = Eventos::all();

        $lots = Ticket::where('stage_id', $lot->stage_id)->get();
        $dbLotes = Lote::where([
                        'desarrollo_id' => $lot->id,
                        'project_id' => $lot->project_id,
                        'phase_id' => $lot->phase_id,
                        'stage_id' => $lot->stage_id
                    ])->get();
   

        return view('events.configurator', compact('lot','projects','lots','dbLotes','Eventos'))
               ->with('sourceType', $sourceType);
    }

    /**
     * Vista de iframe para mostrar lotes en SVG
     */
    public function iframe($id)
    {
        $lot = Eventos::findOrFail($id);
        $sourceType = $lot->source_type ?? 'adara';

        $lots = [];
        $dbLotes = [];

        $lots = Lot::where('stage_id', $lot->stage_id)->get();

        $dbLotes = Lote::where([
            'desarrollo_id' => $lot->id,
            'project_id' => $lot->project_id,
            'phase_id' => $lot->phase_id,
            'stage_id' => $lot->stage_id
        ])->get();


         //  Obtener financiamientos relacionados (solo activos)
        $financiamientos = $lot->financiamientos()->activos()->get();
        $templateModal = $lot->iframe_template_modal ?? 'emedos';

        return view('iframe.index', compact('lot','projects','lots','dbLotes', 'financiamientos', 'templateModal'));
    }

    /**
     * Formulario de edición de un desarrollo
     */
    public function edit($id)
    {
        $lot = Eventos::findOrFail($id);
        $sourceType = $lot->source_type ?? 'adara';
        $projects = [];
        $phases = [];
        $stages = [];

        $Eventos = Eventos::select('id', 'name')->get();

        return view('Eventos.edit', compact('lot','projects','phases','stages','Eventos'));
    }

    /**
     * Actualizar desarrollo existente en la basse de datos
     */
    
    public function update(Request $request, $id)
    {
        $desarrollo = Eventos::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'total_lots' => 'required|integer|min:1',
            'svg_image' => 'nullable|mimes:svg,xml',
            'png_image' => 'nullable|image|mimes:png,jpg,jpeg',
            'project_id' => 'nullable|integer',
            'phase_id' => 'nullable|integer',
            'stage_id' => 'nullable|integer',
            'modal_color' => 'nullable|string|max:10',
            'modal_selector' => 'nullable|string|max:255',
            'color_primario' => 'nullable|string|max:50',
            'color_acento' => 'nullable|string|max:50',
            'financing_months' => 'nullable|integer|min:0',
            'redirect_return' => 'nullable|string|max:255',
            'redirect_next' => 'nullable|string|max:255',
            'redirect_previous' => 'nullable|string|max:255',
            'plusvalia' => 'nullable|numeric|min:0|max:100',
            'iframe_template_modal'=> 'nullable|string|max:255',
            'is_migrated' => 'nullable|boolean',
        ]);

        $data = $request->only([
            'name','description','total_lots','project_id','phase_id','stage_id',
            'modal_color','modal_selector','color_primario','color_acento',
            'financing_months','redirect_return','redirect_next','redirect_previous',
            'plusvalia','source_type','iframe_template_modal','is_migrated'
        ]);

        $data['is_migrated'] = $request->boolean('is_migrated');

        // Manejo de archivos
        if ($request->hasFile('svg_image')) {
            $data['svg_image'] = $this->fileUploadService->upload($request->file('svg_image'), 'lots');
        }
        if ($request->hasFile('png_image')) {
            $data['png_image'] = $this->fileUploadService->upload($request->file('png_image'), 'lots');
        }

         DB::beginTransaction();

        try {
            // 1) Guardar cambios del desarrollo (IMPORTANTE)
            $desarrollo->update($data);

            // 2) Si marcó migrado, actualizar lotes antiguos para apuntar a los nuevos IDs
            if ($data['is_migrated']) {

                $nProject = $data['project_id'];
                $nPhase = $data['phase_id'];
                $nStage = $data['stage_id'];

                // Validar que vengan IDs nuevos
                if (!$nProject || !$nPhase || !$nStage) {
                    Log::warning("Intento de migración sin IDs completos (project/phase/stage) para desarrollo {$desarrollo->id}");
                    // opcional: lanzar excepción para abortar
                    throw new \Exception('Faltan project_id / phase_id / stage_id para completar la migración de lotes.');
                }

                // Obtener lotes antiguos por desarrollo
                $lotesViejos = Lote::where('desarrollo_id', $desarrollo->id)->get();

                // Actualizar en bloque (si prefieres row-by-row para triggers, usa loop)
                foreach ($lotesViejos as $lote) {
                    // Si tu tabla Lote tiene fillable con project_id/phase_id/stage_id puedes:
                    $lote->update([
                        'project_id' => $nProject,
                        'phase_id'   => $nPhase,
                        'stage_id'   => $nStage,
                    ]);
                }

                Log::info("Migración de lotes completa para desarrollo {$desarrollo->id}. Lotes actualizados: {$lotesViejos->count()}");
            }

            DB::commit();

            return redirect()->route('admin.index')->with('success', 'Desarrollo y lotes actualizados correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error actualizando desarrollo {$desarrollo->id}: " . $e->getMessage());
            return redirect()->back()->withInput()->withErrors(['error' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    /**
     * Eliminar desarrollo
     */
    public function destroy($id)
    {
        $desarrollo = Eventos::findOrFail($id);
        $desarrollo->delete();

        return redirect()->route('Eventos.index')->with('success', 'Desarrollo eliminado correctamente.');
    }
}
