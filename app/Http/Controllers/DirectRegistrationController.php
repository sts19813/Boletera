<?php

namespace App\Http\Controllers;

use App\Mail\DirectRegistrationMail;
use App\Models\Eventos;
use App\Models\TicketInstance;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DirectRegistrationController extends Controller
{
    private const WHATSAPP_GROUP_LINK = 'https://chat.whatsapp.com/ExypBBvEppnErpfFdXGoR1?mode=gi_t';

    public function __construct(
        private FileUploadService $fileUploadService
    ) {
    }

    public function store(Request $request, Eventos $event)
    {
        if (!$event->is_registration) {
            return response()->json([
                'message' => 'El evento seleccionado no permite inscripciones.',
            ], 422);
        }

        if ($event->stop_online_sales && !$this->canBypassOnlineStop()) {
            return response()->json([
                'message' => 'La venta en linea esta detenida para este evento.',
            ], 403);
        }

        if (!is_null($event->max_capacity) && (int) $event->max_capacity <= 0) {
            return response()->json([
                'message' => 'El evento ya no cuenta con cupo disponible.',
            ], 409);
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
            'purchase_receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $normalizedEmail = Str::lower(trim((string) $validated['email']));
        $normalizedPhone = $this->normalizePhone((string) $validated['phone']);

        if (strlen($normalizedPhone) < 10) {
            return response()->json([
                'message' => 'El telefono debe tener al menos 10 digitos.',
            ], 422);
        }

        $existsByEmail = TicketInstance::registrationSales()
            ->where('event_id', $event->id)
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->exists();

        if ($existsByEmail) {
            return response()->json([
                'message' => 'Ya existe un registro con ese correo para este evento.',
            ], 409);
        }

        $existsByPhone = TicketInstance::registrationSales()
            ->where('event_id', $event->id)
            ->where('celular', $normalizedPhone)
            ->exists();

        if ($existsByPhone) {
            return response()->json([
                'message' => 'Ya existe un registro con ese telefono para este evento.',
            ], 409);
        }

        $receiptFolder = 'eventos-registros-recibos';
        File::ensureDirectoryExists(public_path($receiptFolder));
        $receiptPath = $this->fileUploadService->upload(
            $validated['purchase_receipt'],
            $receiptFolder
        );

        $receiptUrl = asset($receiptPath);
        $reference = 'DIRECT-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
        $price = (float) ($event->price ?? 0);

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
            'receipt_file_path' => $receiptPath,
            'receipt_file_url' => $receiptUrl,
            'template_form' => 'whatsapp_direct',
        ];

        $instance = TicketInstance::create([
            'ticket_id' => null,
            'sale_type' => 'registration',
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'email' => $normalizedEmail,
            'nombre' => $validated['full_name'],
            'celular' => $normalizedPhone,
            'team_name' => null,
            'payment_intent_id' => $reference,
            'reference' => $reference,
            'qr_hash' => (string) Str::uuid(),
            'registered_at' => now(),
            'purchased_at' => now(),
            'price' => $price,
            'subtotal' => $price,
            'commission' => 0,
            'total' => $price,
            'form_data' => $formData,
            'sale_channel' => 'taquilla',
            'payment_method' => 'cash',
        ]);

        if ($event->max_capacity > 0) {
            $event->decrement('max_capacity', 1);
        }

        Mail::to($normalizedEmail)->send(
            new DirectRegistrationMail($event, $formData)
        );

        return response()->json([
            'message' => 'Registro completado correctamente.',
            'title' => 'Gracias por tu registro',
            'description' => 'Ya estas inscrito al torneo de EAFC 26 patrocinado por Super Willys.',
            'whatsapp_link' => self::WHATSAPP_GROUP_LINK,
            'reference' => $instance->reference,
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
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
