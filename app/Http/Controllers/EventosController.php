<?php

namespace App\Http\Controllers;

use App\Models\Eventos;
use App\Models\Lote;
use App\Models\Ticket;
use App\Models\TicketSvgMapping;
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
    public function __construct(FileUploadService $fileUploadService)
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
        return view('events.create', compact('Eventos'));
    }

    /**
     * Guardar un nuevo evento en la base de datos
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',

            'event_date' => 'required|date',
            'hora_inicio' => 'nullable',
            'hora_fin' => 'nullable',

            // Evento normal
            'total_asientos' => 'required|integer|min:0',
            'has_seat_mapping' => 'required|boolean',

            // Tipo de evento
            'is_registration' => 'nullable|boolean',
            'price' => 'nullable|required_if:is_registration,1|numeric|min:0',
            'max_capacity' => 'nullable|required_if:is_registration,1|integer|min:1',
            'template' => 'nullable|string|max:100',

            'project_id' => 'nullable|integer',
            'phase_id' => 'nullable|integer',
            'stage_id' => 'nullable|integer',

            'modal_color' => 'nullable|string|max:50',
            'modal_selector' => 'nullable|string|max:255',
            'color_primario' => 'nullable|string|max:50',
            'color_acento' => 'nullable|string|max:50',

            'redirect_return' => 'nullable|string|max:255',
            'redirect_next' => 'nullable|string|max:255',
            'redirect_previous' => 'nullable|string|max:255',

            'svg_image' => 'nullable|mimes:svg,xml',
            'png_image' => 'nullable|image|mimes:png,jpg,jpeg,webp',
        ]);

        DB::beginTransaction();

        try {

            $data = $request->only([
                'name',
                'description',
                'location',
                'event_date',
                'hora_inicio',
                'hora_fin',
                'total_asientos',
                'has_seat_mapping',
                'is_registration',
                'price',
                'max_capacity',
                'template',
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

            /**
             * Normalización para eventos de inscripción
             */
            if (!empty($data['is_registration'])) {
                $data['total_asientos'] = 0;
                $data['has_seat_mapping'] = false;
                $data['template'] = $data['template'] ?? 'registration';
            } else {
                // Evento normal
                $data['price'] = null;
                $data['max_capacity'] = null;
                $data['template'] = $data['template'] ?? 'default';
            }

            // Upload SVG
            if ($request->hasFile('svg_image')) {
                $data['svg_image'] = $this->fileUploadService
                    ->upload($request->file('svg_image'), 'eventos-assets');
            }

            // Upload PNG
            if ($request->hasFile('png_image')) {
                $data['png_image'] = $this->fileUploadService
                    ->upload($request->file('png_image'), 'eventos-assets');
            }

            Eventos::create($data);

            DB::commit();

            return redirect()
                ->route('events.index')
                ->with('success', 'Evento creado correctamente.');

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error al crear evento', [
                'error' => $e->getMessage()
            ]);

            return back()
                ->withErrors(['error' => 'Ocurrió un error al crear el evento'])
                ->withInput();
        }
    }


    //guarda el mapeo de los boletos del configurador
    public function storeSettings(Request $request)
    {
        try {
            // Validación
            $request->validate([
                'project_id' => 'nullable|integer',
                'phase_id' => 'nullable|integer',
                'stage_id' => 'nullable|integer',
                'lot_id' => 'nullable|string',
                'polygonId' => 'nullable|string',
                'redirect' => 'nullable|boolean',
                'redirect_url' => 'nullable|string',
                'desarrollo_id' => 'required|uuid',
                'color' => 'nullable|string|max:9',
                'color_active' => 'nullable|string|max:9',
            ]);

            // Solo tomar redirect_url si está marcado
            $redirectChecked = $request->has('redirect') && $request->redirect;
            $redirectUrl = $redirectChecked ? $request->redirect_url : null;

            // Crear registro
            $lote = TicketSvgMapping::create([
                'evento_id' => $request->desarrollo_id,
                'project_id' => $request->project_id ?: null,
                'phase_id' => $request->phase_id ?: null,
                'stage_id' => $request->stage_id ?: null,
                'ticket_id' => $request->lot_id ?: null,
                'svg_selector' => $request->polygonId,
                'redirect' => $redirectChecked,
                'redirect_url' => $redirectUrl,
                'color' => $redirectChecked ? $request->color : null,
                'color_active' => $redirectChecked ? $request->color_active : null,
            ]);

            return response()->json([
                'success' => true,
                'lote' => $lote
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Devolver errores de validación en JSON
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Captura cualquier otro error
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
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
        $dbLotes = TicketSvgMapping::where([
            'evento_id' => $lot->id,
            'project_id' => $lot->project_id,
            'phase_id' => $lot->phase_id,
            'stage_id' => $lot->stage_id
        ])->get();


        return view('events.configurator', compact('lot', 'projects', 'lots', 'dbLotes', 'Eventos'))
            ->with('sourceType', $sourceType);
    }

    /**
     * Vista de iframe para mostrar lotes en SVG
     */
    public function iframe($id)
    {
        $lot = Eventos::findOrFail($id);
        $projects = Eventos::all();

        $lots = [];
        $dbLotes = [];

        $lots = Ticket::where('stage_id', $lot->stage_id)->get();

        $tickets = Ticket::where('stage_id', $lot->stage_id)
            ->where('status', 'available')
            ->get();

        $dbLotes = TicketSvgMapping::where([
            'evento_id' => $lot->id,
            'project_id' => $lot->project_id,
            'phase_id' => $lot->phase_id,
            'stage_id' => $lot->stage_id
        ])->get();

        return view('events.iframe', compact('lot', 'projects', 'lots', 'dbLotes', 'tickets'));
    }

    /**
     * Formulario de edición de un desarrollo
     */
    public function edit($id)
    {
        $event = Eventos::findOrFail($id);
        $sourceType = $lot->source_type ?? 'adara';
        $projects = [];
        $phases = [];
        $stages = [];

        $Eventos = Eventos::select('id', 'name')->get();

        return view('events.edit', compact('event', 'projects', 'phases', 'stages', 'Eventos'));
    }

    /**
     * Actualizar un evento existente
     */
    public function update(Request $request, $id)
    {
        $event = Eventos::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',

            'event_date' => 'required|date',
            'hora_inicio' => 'nullable',
            'hora_fin' => 'nullable',

            // Evento normal
            'total_asientos' => 'required|integer|min:0',
            'has_seat_mapping' => 'required|boolean',

            // Tipo de evento
            'is_registration' => 'nullable|boolean',
            'price' => 'nullable|required_if:is_registration,1|numeric|min:0',
            'max_capacity' => 'nullable|required_if:is_registration,1|integer|min:1',
            'template' => 'nullable|string|max:100',

            'project_id' => 'nullable|integer',
            'phase_id' => 'nullable|integer',
            'stage_id' => 'nullable|integer',

            'modal_color' => 'nullable|string|max:50',
            'modal_selector' => 'nullable|string|max:255',
            'color_primario' => 'nullable|string|max:50',
            'color_acento' => 'nullable|string|max:50',

            'redirect_return' => 'nullable|string|max:255',
            'redirect_next' => 'nullable|string|max:255',
            'redirect_previous' => 'nullable|string|max:255',

            'svg_image' => 'nullable|mimes:svg,xml',
            'png_image' => 'nullable|image|mimes:png,jpg,jpeg,webp',
        ]);

        DB::beginTransaction();

        try {

            $data = $request->only([
                'name',
                'description',
                'location',
                'event_date',
                'hora_inicio',
                'hora_fin',
                'total_asientos',
                'has_seat_mapping',

                // Nuevos campos
                'is_registration',
                'price',
                'max_capacity',
                'template',

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

            /**
             * Normalización según tipo de evento
             */
            if (!empty($data['is_registration'])) {
                $data['total_asientos'] = 0;
                $data['has_seat_mapping'] = false;
                $data['template'] = $data['template'] ?? 'registration';
            } else {
                // Evento normal
                $data['price'] = null;
                $data['max_capacity'] = null;
                $data['template'] = $data['template'] ?? 'default';
            }

            // SVG nuevo (si se sube)
            if ($request->hasFile('svg_image')) {
                $data['svg_image'] = $this->fileUploadService
                    ->upload($request->file('svg_image'), 'eventos-assets');
            }

            // PNG nuevo (si se sube)
            if ($request->hasFile('png_image')) {
                $data['png_image'] = $this->fileUploadService
                    ->upload($request->file('png_image'), 'eventos-assets');
            }

            $event->update($data);

            DB::commit();

            return redirect()
                ->route('events.index')
                ->with('success', 'Evento actualizado correctamente.');

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Error al actualizar evento', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->withErrors(['error' => 'Ocurrió un error al actualizar el evento'])
                ->withInput();
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
    
    /// Eliminar un mapeo específico
    public function destroyMapping(Request $request, Eventos $event)
    {
        $mapping = TicketSvgMapping::findOrFail($request->id);
        $mapping->delete();

        return response()->json([
            'success' => true
        ]);
    }
}
