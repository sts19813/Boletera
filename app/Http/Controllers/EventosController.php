<?php

namespace App\Http\Controllers;

use App\Models\Eventos;
use App\Models\Ticket;
use App\Models\TicketSvgMapping;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventosController extends Controller
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {
    }

    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            $events = Eventos::orderBy('created_at', 'desc')->get();
        } else {
            $allowedEventIds = $user->events()->pluck('eventos.id');

            $events = Eventos::whereIn('id', $allowedEventIds)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('events.index', compact('events'));
    }

    public function admin()
    {
        $lots = Eventos::orderBy('updated_at', 'desc')->get();

        return view('lots.admin', compact('lots'));
    }

    public function fetch(Request $request)
    {
        return response()->json([], 410);
    }

    public function create()
    {
        $Eventos = Eventos::select('id', 'name')->get();

        return view('events.create', compact('Eventos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'event_date' => 'required|date',
            'hora_inicio' => 'nullable',
            'hora_fin' => 'nullable',
            'total_asientos' => 'required|integer|min:0',
            'has_seat_mapping' => 'nullable|boolean',
            'is_registration' => 'nullable|boolean',
            'price' => 'nullable|required_if:is_registration,1|numeric|min:0',
            'max_capacity' => 'nullable|required_if:is_registration,1|integer|min:1',
            'template' => 'nullable|string|max:100',
            'template_form' => 'nullable|required_if:is_registration,1|string|max:100',
            'allows_multiple_registrations' => 'nullable|boolean',
            'registration_max_checkins' => 'nullable|required_if:is_registration,1|integer|min:1',
            'modal_color' => 'nullable|string|max:50',
            'modal_selector' => 'nullable|string|max:255',
            'color_primario' => 'nullable|string|max:50',
            'color_acento' => 'nullable|string|max:50',
            'redirect_return' => 'nullable|string|max:255',
            'redirect_next' => 'nullable|string|max:255',
            'redirect_previous' => 'nullable|string|max:255',
            'stop_online_sales' => 'nullable|boolean',
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
                'registration_max_checkins',
                'modal_color',
                'modal_selector',
                'color_primario',
                'color_acento',
                'redirect_return',
                'redirect_next',
                'redirect_previous',
                'stop_online_sales',
                'template_form',
            ]);

            if (!empty($data['is_registration'])) {
                $data['total_asientos'] = 0;
                $data['has_seat_mapping'] = false;
                $data['template'] = $data['template'] ?? 'registration';
                $data['registration_max_checkins'] = max(1, (int) ($data['registration_max_checkins'] ?? 1));
            } else {
                $data['price'] = null;
                $data['max_capacity'] = null;
                $data['template'] = $data['template'] ?? 'default';
                $data['registration_max_checkins'] = 1;
            }

            if ($request->hasFile('svg_image')) {
                $data['svg_image'] = $this->fileUploadService
                    ->upload($request->file('svg_image'), 'eventos-assets');
            }

            if ($request->hasFile('png_image')) {
                $data['png_image'] = $this->fileUploadService
                    ->upload($request->file('png_image'), 'eventos-assets');
            }

            $data['has_seat_mapping'] = $request->boolean('has_seat_mapping');
            $data['allows_multiple_registrations'] = $request->has('allows_multiple_registrations');
            $data['stop_online_sales'] = $request->boolean('stop_online_sales');

            Eventos::create($data);

            DB::commit();

            return redirect()
                ->route('events.index')
                ->with('success', 'Evento creado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error al crear evento', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['error' => 'Ocurrio un error al crear el evento'])
                ->withInput();
        }
    }

    public function storeSettings(Request $request)
    {
        try {
            $request->validate([
                'lot_id' => 'nullable|uuid|exists:tickets,id',
                'polygonId' => 'required|string',
                'redirect' => 'nullable|boolean',
                'redirect_url' => 'nullable|string',
                'desarrollo_id' => 'required|uuid|exists:eventos,id',
                'color' => 'nullable|string|max:9',
                'color_active' => 'nullable|string|max:9',
            ]);

            $redirectChecked = $request->has('redirect') && $request->redirect;
            $redirectUrl = $redirectChecked ? $request->redirect_url : null;

            $mapping = TicketSvgMapping::updateOrCreate(
                [
                    'evento_id' => $request->desarrollo_id,
                    'svg_selector' => $request->polygonId,
                ],
                [
                    'ticket_id' => $request->lot_id ?: null,
                    'redirect' => $redirectChecked,
                    'redirect_url' => $redirectUrl,
                    'color' => $redirectChecked ? $request->color : null,
                    'color_active' => $redirectChecked ? $request->color_active : null,
                ]
            );

            return response()->json([
                'success' => true,
                'lote' => $mapping,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function configurator(string $id)
    {
        $lot = Eventos::findOrFail($id);
        $Eventos = Eventos::all();
        $sourceType = $lot->source_type ?? 'adara';

        $lots = Ticket::where('event_id', $lot->id)
            ->orderBy('name')
            ->get();

        $dbLotes = TicketSvgMapping::where('evento_id', $lot->id)->get();

        return view('events.configurator', compact('lot', 'lots', 'dbLotes', 'Eventos'))
            ->with('sourceType', $sourceType);
    }

    public function iframe(string $id)
    {
        $lot = Eventos::findOrFail($id);

        $lots = Ticket::where('event_id', $lot->id)
            ->orderBy('name')
            ->get();

        $tickets = Ticket::where('event_id', $lot->id)
            ->where('status', 'available')
            ->orderBy('name')
            ->get();

        $dbLotes = TicketSvgMapping::where('evento_id', $lot->id)->get();

        return view('events.iframe', compact('lot', 'lots', 'dbLotes', 'tickets'));
    }

    public function edit(string $id)
    {
        $event = Eventos::findOrFail($id);
        $Eventos = Eventos::select('id', 'name')->get();

        return view('events.edit', compact('event', 'Eventos'));
    }

    public function update(Request $request, string $id)
    {
        $event = Eventos::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'event_date' => 'required|date',
            'hora_inicio' => 'nullable',
            'hora_fin' => 'nullable',
            'total_asientos' => 'required|integer|min:0',
            'has_seat_mapping' => 'nullable|boolean',
            'is_registration' => 'nullable|boolean',
            'price' => 'nullable|required_if:is_registration,1|numeric|min:0',
            'max_capacity' => 'nullable|required_if:is_registration,1|integer|min:1',
            'template' => 'nullable|string|max:100',
            'template_form' => 'nullable|required_if:is_registration,1|string|max:100',
            'allows_multiple_registrations' => 'nullable|boolean',
            'registration_max_checkins' => 'nullable|required_if:is_registration,1|integer|min:1',
            'modal_color' => 'nullable|string|max:50',
            'modal_selector' => 'nullable|string|max:255',
            'color_primario' => 'nullable|string|max:50',
            'color_acento' => 'nullable|string|max:50',
            'redirect_return' => 'nullable|string|max:255',
            'redirect_next' => 'nullable|string|max:255',
            'redirect_previous' => 'nullable|string|max:255',
            'stop_online_sales' => 'nullable|boolean',
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
                'template_form',
                'registration_max_checkins',
                'modal_color',
                'modal_selector',
                'color_primario',
                'color_acento',
                'redirect_return',
                'redirect_next',
                'redirect_previous',
                'stop_online_sales',
            ]);

            $data['has_seat_mapping'] = $request->boolean('has_seat_mapping');
            $data['allows_multiple_registrations'] = $request->has('allows_multiple_registrations');
            $data['is_registration'] = $request->boolean('is_registration');
            $data['stop_online_sales'] = $request->boolean('stop_online_sales');

            if ($data['is_registration']) {
                $data['total_asientos'] = 0;
                $data['has_seat_mapping'] = false;
                $data['template'] = $data['template'] ?? 'registration';
                $data['registration_max_checkins'] = max(1, (int) ($data['registration_max_checkins'] ?? 1));
            } else {
                $data['price'] = null;
                $data['max_capacity'] = null;
                $data['template_form'] = null;
                $data['allows_multiple_registrations'] = false;
                $data['template'] = $data['template'] ?? 'default';
                $data['registration_max_checkins'] = 1;
            }

            if ($request->hasFile('svg_image')) {
                $data['svg_image'] = $this->fileUploadService
                    ->upload($request->file('svg_image'), 'eventos-assets');
            }

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
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['error' => 'Ocurrio un error al actualizar el evento'])
                ->withInput();
        }
    }

    public function destroy(string $id)
    {
        $desarrollo = Eventos::findOrFail($id);
        $desarrollo->delete();

        return redirect()
            ->route('events.index')
            ->with('success', 'Evento eliminado correctamente.');
    }

    public function destroyMapping(Request $request, Eventos $event)
    {
        $mapping = TicketSvgMapping::where('evento_id', $event->id)
            ->where('id', $request->id)
            ->firstOrFail();

        $mapping->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
