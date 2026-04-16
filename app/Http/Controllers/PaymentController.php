<?php

namespace App\Http\Controllers;

use App\Mail\BoletosMail;
use App\Models\Eventos;
use App\Models\Ticket;
use App\Models\TicketInstance;
use App\Services\RegistrationBuilderService;
use App\Services\RegistrationStripeService;
use App\Services\TicketBuilderService;
use App\Services\TicketService;
use App\Support\RegistrationPricing;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function __construct(
        private TicketBuilderService $ticketBuilder,
        private TicketService $ticketService,
        private RegistrationStripeService $registrationStripeService,
        private RegistrationBuilderService $registrationBuilder
    ) {
    }

    /**
     * Vista de pago (DOMINIO PROPIO)
     */
    public function formulario(Request $request)
    {
        $carrito = session('svg_cart', []);

        if (empty($carrito)) {
            abort(404, 'Carrito vacio');
        }

        $eventId = session('event_id');

        if (!$eventId) {
            abort(400, 'Evento no definido');
        }

        $registration = session('registration_form');
        $evento = Eventos::findOrFail($eventId);
        $this->abortIfOnlineSalesStopped($evento);
        $carrito = $this->applyRegistrationPricingRules($carrito);
        session(['svg_cart' => $carrito]);

        $subtotal = collect($carrito)->sum(fn($i) => $i['price'] * ($i['qty'] ?? 1));
        $comision = round($subtotal * 0.05, 2);
        $total = $subtotal;

        return view('pago.form', compact(
            'carrito',
            'evento',
            'subtotal',
            'comision',
            'total',
            'registration'
        ));
    }

    /**
     * Crear PaymentIntent
     */
    public function crearIntent(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'celular' => 'required|string|max:20',
            'email' => 'required|email',
        ]);

        $carrito = session('svg_cart', []);

        if (empty($carrito)) {
            return response()->json(['error' => 'Carrito vacio'], 400);
        }

        $eventId = session('event_id');
        $evento = $eventId ? Eventos::find($eventId) : null;

        if ($evento && $evento->stop_online_sales && !$this->canBypassOnlineStop()) {
            return response()->json([
                'error' => 'La venta en línea está detenida para este evento.',
            ], 403);
        }

        foreach ($carrito as $item) {
            $type = $item['type'] ?? 'ticket';
            $qtySolicitado = $item['qty'] ?? 1;

            if ($type === 'ticket') {
                $ticket = Ticket::withCount('instances')->find($item['id']);

                if (!$ticket) {
                    return response()->json([
                        'error' => "El boleto '{$item['name']}' ya no esta disponible.",
                    ], 409);
                }

                $disponibles = $ticket->stock;

                if ($disponibles <= 0) {
                    $ultimaCompra = $ticket->instances()
                        ->latest('purchased_at')
                        ->first();

                    $horaCompra = $ultimaCompra
                        ? Carbon::parse($ultimaCompra->purchased_at)->format('d/m/Y H:i')
                        : 'recientemente';

                    return response()->json([
                        'error' => "El boleto '{$ticket->name}' esta agotado.",
                        'detalle' => "Ultima compra registrada el {$horaCompra}.",
                    ], 409);
                }

                if ($disponibles < $qtySolicitado) {
                    return response()->json([
                        'error' => "Solo quedan {$disponibles} boletos disponibles para '{$ticket->name}'.",
                    ], 409);
                }
            }

            if ($type === 'registration') {
                $evento = Eventos::find($item['event_id']);

                if (!$evento) {
                    return response()->json([
                        'error' => 'El evento ya no esta disponible.',
                    ], 409);
                }

                if ($evento->max_capacity <= 0) {
                    return response()->json([
                        'error' => "La inscripcion '{$evento->name}' esta agotada.",
                        'detalle' => 'El evento ya no cuenta con cupo disponible.',
                    ], 409);
                }

                if ($evento->max_capacity < $qtySolicitado) {
                    return response()->json([
                        'error' => "Solo quedan {$evento->max_capacity} lugares disponibles para '{$evento->name}'.",
                    ], 409);
                }
            }
        }

        $carrito = $this->applyRegistrationPricingRules($carrito);
        session(['svg_cart' => $carrito]);

        $subtotal = collect($carrito)->sum(fn($i) => $i['price'] * ($i['qty'] ?? 1));
        $total = $subtotal;

        Stripe::setApiKey(config('services.stripe.secret'));

        $intent = PaymentIntent::create([
            'amount' => (int) round($total * 100),
            'currency' => 'mxn',
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'event_id' => session('event_id'),
                'nombre' => $request->nombre,
                'celular' => $request->celular,
                'email' => $request->email,
            ],
        ]);

        return response()->json([
            'clientSecret' => $intent->client_secret,
        ]);
    }

    public function success(Request $request)
    {
        $paymentIntentId = $request->query('pi');

        if (!$paymentIntentId) {
            abort(400, 'Pago invalido');
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        $intent = PaymentIntent::retrieve($paymentIntentId);
        $email = $intent->metadata->email ?? null;
        $cart = session('svg_cart', []);

        if (empty($cart)) {
            abort(400, 'Carrito vacio o sesion expirada');
        }

        $containsTickets = collect($cart)->contains(
            fn($i) => ($i['type'] ?? 'ticket') === 'ticket'
        );
        $containsRegistrations = collect($cart)->contains(
            fn($i) => ($i['type'] ?? null) === 'registration'
        );

        $boletos = [];

        if ($containsTickets) {
            $boletos = array_merge(
                $boletos,
                $this->ticketService->createFromStripeIntent($paymentIntentId)
            );
        }

        if ($containsRegistrations) {
            $boletos = array_merge(
                $boletos,
                $this->registrationStripeService->createFromStripeIntent($paymentIntentId)
            );
        }

        if (empty($boletos)) {
            abort(400, 'No se generaron boletos para esta compra');
        }

        $pdfContent = $this->generateBoletosPdf($boletos, $email);

        Mail::to($email)->send(
            new BoletosMail($pdfContent, $boletos)
        );

        $eventId = $cart[0]['event_id'] ?? null;
        $evento = Eventos::findOrFail($eventId);

        return view('pago.success', compact('boletos', 'email', 'evento'));
    }

    private function generateBoletosPdf(array $boletos, string $email)
    {
        return Pdf::loadView('pdf.boletos', [
            'boletos' => $boletos,
            'email' => $email,
        ])->setPaper([0, 0, 400, 700])->output();
    }

    public function reprint(Request $request)
    {
        $reference = $request->get('ref');
        $email = $request->get('email') ?: 'taquilla@local';

        if (!$reference) {
            abort(400, 'Referencia requerida');
        }

        $instances = TicketInstance::with(['ticket', 'evento'])
            ->where(function ($q) use ($reference) {
                $q->where('payment_intent_id', $reference)
                    ->orWhere('reference', $reference);
            })
            ->orderBy('purchased_at')
            ->get();

        if ($instances->isEmpty()) {
            abort(404, 'Boletos o inscripciones no encontrados');
        }

        $boletos = $instances->map(function (TicketInstance $instance) use ($email) {
            $evento = $instance->evento ?? Eventos::findOrFail($instance->event_id);

            if ($instance->sale_type === 'registration') {
                return $this->registrationBuilder->build(
                    $evento,
                    $instance,
                    $instance->email ?? $email
                );
            }

            return $this->ticketBuilder->build(
                $instance->ticket,
                $instance,
                $instance->email ?? $email,
                $instance->purchased_at,
                $evento,
                $instance->payment_intent_id ?? $instance->reference
            );
        })->toArray();

        $pdf = Pdf::loadView('pdf.boletos', [
            'boletos' => $boletos,
            'email' => $email,
        ])->setPaper([0, 0, 400, 700]);

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="boletos.pdf"');
    }

    public function cancel()
    {
        return view('pago.cancel');
    }

    private function abortIfOnlineSalesStopped(Eventos $evento): void
    {
        if ($evento->stop_online_sales && !$this->canBypassOnlineStop()) {
            abort(403, 'La venta en línea está detenida para este evento.');
        }
    }

    private function applyRegistrationPricingRules(array $carrito): array
    {
        return collect($carrito)->map(function (array $item) {
            if (($item['type'] ?? 'ticket') !== 'registration') {
                unset($item['promotion']);
                return $item;
            }

            $evento = Eventos::find($item['event_id'] ?? null);

            if (!$evento) {
                unset($item['promotion']);
                return $item;
            }

            $qty = max(1, (int) ($item['qty'] ?? 1));
            $item['price'] = RegistrationPricing::resolveUnitPrice($evento, $qty);
            $promotion = RegistrationPricing::resolvePromotionMeta($evento, $qty);

            if ($promotion) {
                $item['promotion'] = $promotion;
            } else {
                unset($item['promotion']);
            }

            return $item;
        })->values()->toArray();
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
