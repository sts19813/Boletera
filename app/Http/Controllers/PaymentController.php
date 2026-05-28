<?php

namespace App\Http\Controllers;

use App\Models\Eventos;
use App\Models\Ticket;
use App\Models\TicketInstance;
use App\Services\RegistrationBuilderService;
use App\Services\RegistrationStripeService;
use App\Services\TicketBuilderService;
use App\Services\CouponService;
use App\Services\QueueMailTaskService;
use App\Services\TicketService;
use App\Support\RegistrationPricing;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class PaymentController extends Controller
{
    private const STRIPE_MIN_AMOUNT_MXN = 10.00;

    public function __construct(
        private TicketBuilderService $ticketBuilder,
        private TicketService $ticketService,
        private RegistrationStripeService $registrationStripeService,
        private RegistrationBuilderService $registrationBuilder,
        private CouponService $couponService,
        private QueueMailTaskService $queueMailTaskService
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
        $couponCode = session('coupon_code');
        $couponResult = $this->couponService->applyCouponToCart($evento, $carrito, $couponCode);

        if ($couponResult['success']) {
            $carrito = $couponResult['cart'];
        } else {
            $carrito = $couponResult['cart'];
            session()->forget('coupon_code');
        }

        session(['svg_cart' => $carrito]);

        $subtotal = collect($carrito)->sum(fn($i) => $i['price'] * ($i['qty'] ?? 1));
        $comision = round($subtotal * 0.05, 2);
        $total = $subtotal;
        $appliedCoupon = $couponResult['coupon'] ?? null;

        return view('pago.form', compact(
            'carrito',
            'evento',
            'subtotal',
            'comision',
            'total',
            'registration',
            'appliedCoupon'
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
        if ($evento) {
            $couponResult = $this->couponService->applyCouponToCart(
                $evento,
                $carrito,
                session('coupon_code')
            );

            if (!$couponResult['success']) {
                return response()->json([
                    'error' => $couponResult['message'],
                ], 422);
            }

            $carrito = $couponResult['cart'];
        }
        session(['svg_cart' => $carrito]);

        $subtotal = collect($carrito)->sum(fn($i) => $i['price'] * ($i['qty'] ?? 1));
        $total = $subtotal;

        if ($total < self::STRIPE_MIN_AMOUNT_MXN) {
            return response()->json([
                'error' => 'El monto mínimo para pago con tarjeta es de $' . number_format(self::STRIPE_MIN_AMOUNT_MXN, 2) . ' MXN.',
                'detalle' => 'Ajusta la cantidad o precio para continuar con Stripe.',
            ], 422);
        }

        try {
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
        } catch (ApiErrorException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'No se pudo iniciar el pago con Stripe.',
            ], 500);
        }
    }

    /**
     * Confirma la compra en Stripe, materializa los boletos/inscripciones y
     * entrega la vista de éxito con datos de impresión/reimpresión.
     *
     * Flujo principal:
     * 1. Toma `pi` (PaymentIntent ID) desde query string.
     * 2. Recupera metadata de Stripe (incluido email).
     * 3. Lee el carrito de sesión y genera instancias reales según tipo:
     *    - ticket: via TicketService
     *    - registration: via RegistrationStripeService
     * 4. Encola correo de entrega cuando el email es válido.
     * 5. Genera una URL pública firmada y temporal para reimpresión PDF
     *    orientada a usuario no autenticado.
     *
     * @param Request $request Debe incluir `pi` como query param.
     * @return \Illuminate\Contracts\View\View
     */
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

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->queueMailTaskService->queueBoletos(
                recipient: $email,
                boletos: $boletos,
                type: 'boletos_purchase',
                reference: $paymentIntentId,
                deduplicateByReference: true
            );
        }

        $eventId = $cart[0]['event_id'] ?? null;
        $evento = Eventos::findOrFail($eventId);
        session()->forget('coupon_code');
        $publicReprintUrl = null;

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $publicReprintUrl = URL::temporarySignedRoute(
                'boletos.reprint.public',
                now()->addDays(7),
                [
                    'ref' => $paymentIntentId,
                    'email' => $email,
                ]
            );
        }

        return view('pago.success', compact('boletos', 'email', 'evento', 'publicReprintUrl'));
    }

    /**
     * Reimprime boletos/inscripciones en PDF para usuarios autenticados.
     *
     * Busca por referencia de pago (`payment_intent_id`) o referencia interna
     * de taquilla (`reference`) y delega la construcción final del PDF al método
     * privado compartido.
     *
     * @param Request $request Espera `ref` y opcionalmente `email`.
     * @return \Illuminate\Http\Response
     */
    public function reprint(Request $request)
    {
        $reference = $request->get('ref');
        $email = $request->get('email') ?: 'taquilla@local';

        if (!$reference) {
            abort(400, 'Referencia requerida');
        }

        return $this->buildReprintPdfResponse($reference, $email);
    }

    /**
     * Reimpresión pública (sin sesión) de boletos/inscripciones en PDF.
     *
     * Esta acción está diseñada para usarse con ruta firmada (`signed`) y
     * expiración temporal. Solo valida presencia de referencia y reutiliza el
     * mismo pipeline de render que la reimpresión autenticada.
     *
     * @param Request $request Espera `ref` y opcionalmente `email`.
     * @return \Illuminate\Http\Response
     */
    public function reprintPublic(Request $request)
    {
        $reference = (string) $request->get('ref', '');
        $email = (string) $request->get('email', 'taquilla@local');

        if ($reference === '') {
            abort(400, 'Referencia requerida');
        }

        return $this->buildReprintPdfResponse($reference, $email);
    }

    /**
     * Construye la respuesta HTTP con el PDF de boletos/inscripciones.
     *
     * Responsabilidades:
     * - Resolver instancias por `payment_intent_id` o `reference`.
     * - Mapear cada instancia al payload estándar de PDF (ticket o registration).
     * - Renderizar `pdf.boletos` y devolver respuesta inline `application/pdf`.
     *
     * Consideraciones:
     * - Si no hay instancias para la referencia, aborta con 404.
     * - El email recibido funciona como fallback para payload/plantilla.
     *
     * @param string $reference Identificador de compra (Stripe o taquilla).
     * @param string $email Email a mostrar como fallback en el PDF.
     * @return \Illuminate\Http\Response
     */
    private function buildReprintPdfResponse(string $reference, string $email)
    {
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
                $item['base_price'] = round((float) ($item['base_price'] ?? $item['price'] ?? 0), 2);
                return $item;
            }

            $evento = Eventos::find($item['event_id'] ?? null);

            if (!$evento) {
                unset($item['promotion']);
                return $item;
            }

            $qty = max(1, (int) ($item['qty'] ?? 1));
            $item['price'] = RegistrationPricing::resolveUnitPrice($evento, $qty);
            $item['base_price'] = $item['price'];
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


    /**
     * Consulta un PaymentIntent en Stripe y devuelve datos útiles de validación.
     *
     * Uso esperado:
     * - Verificación operativa/manual del estado de pago.
     * - Confirmación de montos cobrados y método de pago asociado.
     *
     * Respuesta:
     * - `id`, `status`, `currency`, `amount`, `amount_received`,
     *   `customer`, `payment_method`, `created_at`.
     *
     * @param string $paymentIntentId ID del PaymentIntent en Stripe.
     * @return \Illuminate\Http\JsonResponse
     */
    public function showPaymentIntent($paymentIntentId)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            return response()->json([
                'id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'currency' => strtoupper($paymentIntent->currency),
                'amount' => $paymentIntent->amount / 100,
                'amount_received' => $paymentIntent->amount_received / 100,
                'customer' => $paymentIntent->customer,
                'payment_method' => $paymentIntent->payment_method,
                'created_at' => date('Y-m-d H:i:s', $paymentIntent->created),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }

    }
}
