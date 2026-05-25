<?php

namespace App\Http\Controllers;

use App\Models\Eventos;
use App\Models\TicketInstance;
use App\Services\RegistrationFormSchemaService;
use App\Services\QueueMailTaskService;
use App\Services\RegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DirectRegistrationController extends Controller
{
    private const WHATSAPP_GROUP_LINK = 'https://chat.whatsapp.com/FaPvvNc1XyV9QxLKk6xb5w?mode=gi_t';

    public function __construct(
        private RegistrationService $registrationService,
        private RegistrationFormSchemaService $schemaService,
        private QueueMailTaskService $queueMailTaskService
    ) {
    }

    public function store(Request $request, Eventos $event)
    {
        if (!$event->is_registration) {
            return response()->json(['message' => 'El evento seleccionado no permite inscripciones.'], 422);
        }

        if ($event->stop_online_sales && !$this->canBypassOnlineStop()) {
            return response()->json(['message' => 'La venta en linea esta detenida para este evento.'], 403);
        }

        if ($event->template_form === 'whatsapp_direct') {
            return $this->storeWhatsappDirect($request, $event);
        }

        $rawData = collect($request->except(['_token', 'qty']))
            ->filter(fn($v) => $v !== null && $v !== '')
            ->toArray();

        $formData = $this->schemaService->validateSubmissionForEvent($event->loadMissing('registrationForm'), $rawData);

        $qtyFromPayload = isset($formData['registrations']) && is_array($formData['registrations'])
            ? count($formData['registrations'])
            : null;

        $qty = $qtyFromPayload ?? max(1, (int) $request->input('qty', 1));
        if (!$event->allows_multiple_registrations) {
            $qty = 1;
        }

        if (!is_null($event->max_capacity) && (int) $event->max_capacity < $qty) {
            return response()->json(['message' => 'No hay cupo suficiente para completar el registro.'], 409);
        }

        $baseData = $this->extractBaseRegistrant($formData);
        $email = $this->normalizeEmail($baseData['email'] ?? null);

        $instances = $this->registrationService->create($event, [
            'qty' => $qty,
            'email' => $email,
            'nombre' => $baseData['name'] ?? 'Registro directo',
            'celular' => $this->normalizePhone((string) ($baseData['phone'] ?? '')),
            'form_data' => $formData,
            'reference' => 'DIRECT-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
            'sale_channel' => 'taquilla',
            'payment_method' => 'cash',
            'price' => (float) ($event->price ?? 0),
            'base_price' => (float) ($event->price ?? 0),
        ]);

        return response()->json([
            'message' => 'Registro completado correctamente.',
            'title' => 'Registro completado',
            'description' => 'Tu información fue guardada correctamente.',
            'whatsapp_link' => '',
            'reference' => $instances[0]->reference ?? null,
        ]);
    }

    private function storeWhatsappDirect(Request $request, Eventos $event)
    {
        if (!is_null($event->max_capacity) && (int) $event->max_capacity <= 0) {
            return response()->json(['message' => 'El evento ya no cuenta con cupo disponible.'], 409);
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'age' => 'required|integer|min:1|max:120',
            'city' => 'required|string|max:120',
            'state' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|max:255',
            'game_id' => 'required|string|max:120',
            'console' => 'required|string|max:120',
            'participated_before' => 'required|in:si,no',
            'participation_count' => 'required|integer|min:0|max:999',
            'how_known' => 'required|in:facebook,instagram,youtube,referido',
            'stream_user' => 'nullable|string|max:120',
        ]);

        $normalizedEmail = Str::lower(trim((string) $validated['email']));
        $normalizedPhone = $this->normalizePhone((string) $validated['phone']);

        if (strlen($normalizedPhone) < 10) {
            return response()->json(['message' => 'El telefono debe tener al menos 10 digitos.'], 422);
        }

        $existsByEmail = TicketInstance::registrationSales()
            ->where('event_id', $event->id)
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->exists();

        if ($existsByEmail) {
            return response()->json(['message' => 'Ya existe un registro con ese correo para este evento.'], 409);
        }

        $existsByPhone = TicketInstance::registrationSales()
            ->where('event_id', $event->id)
            ->where('celular', $normalizedPhone)
            ->exists();

        if ($existsByPhone) {
            return response()->json(['message' => 'Ya existe un registro con ese telefono para este evento.'], 409);
        }

        $howKnownLabel = match ($validated['how_known']) {
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'youtube' => 'YouTube',
            default => 'Alguien me conto',
        };

        $formData = [
            'full_name' => $validated['full_name'],
            'age' => (int) $validated['age'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'phone' => $normalizedPhone,
            'email' => $normalizedEmail,
            'game_id' => $validated['game_id'],
            'console' => $validated['console'],
            'participated_before' => $validated['participated_before'],
            'participation_count' => (int) $validated['participation_count'],
            'how_known' => $validated['how_known'],
            'how_known_label' => $howKnownLabel,
            'stream_user' => $validated['stream_user'] ?? null,
            'receipt_file_path' => '',
            'receipt_file_url' => '',
            'template_form' => 'whatsapp_direct',
        ];

        $instances = $this->registrationService->create($event, [
            'qty' => 1,
            'email' => $normalizedEmail,
            'nombre' => $validated['full_name'],
            'celular' => $normalizedPhone,
            'form_data' => $formData,
            'reference' => 'DIRECT-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
            'sale_channel' => 'taquilla',
            'payment_method' => 'cash',
            'price' => (float) ($event->price ?? 0),
            'base_price' => (float) ($event->price ?? 0),
        ]);

        $this->queueMailTaskService->queueDirectRegistration(
            recipient: $normalizedEmail,
            eventId: (string) $event->id,
            registrationData: $formData,
            reference: $instances[0]->reference ?? null
        );

        return response()->json([
            'message' => 'Registro completado correctamente.',
            'title' => 'Gracias por tu registro',
            'description' => 'Ya estas inscrito al torneo',
            'whatsapp_link' => self::WHATSAPP_GROUP_LINK,
            'reference' => $instances[0]->reference ?? null,
        ]);
    }

    private function extractBaseRegistrant(array $formData): array
    {
        $source = $formData;
        if (isset($formData['registrations'][0]) && is_array($formData['registrations'][0])) {
            $source = $formData['registrations'][0];
        }

        return [
            'name' => trim((string) ($source['full_name'] ?? $source['nombre'] ?? $source['name'] ?? 'Registro directo')),
            'email' => $source['email'] ?? $source['correo'] ?? null,
            'phone' => $source['phone'] ?? $source['celular'] ?? null,
        ];
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    private function normalizeEmail(?string $email): string
    {
        $value = Str::lower(trim((string) $email));
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : 'registro@local';
    }

    private function canBypassOnlineStop(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return $user->hasRole('admin')
            || $user->hasRole('taquillero')
            || $user->can('vender boletos')
            || $user->can('genera cortesias');
    }
}
