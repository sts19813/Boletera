<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Ticket;
use App\Models\Eventos;
use App\Models\TicketInstance;
use App\Services\CouponService;
use App\Services\RegistrationBuilderService;
use App\Services\RegistrationService;
use App\Services\TicketBuilderService;
use App\Services\QueueMailTaskService;
use App\Services\TicketService;
use App\Support\RegistrationPricing;

class TaquillaController extends Controller
{
    public function __construct(
        private TicketBuilderService $ticketBuilder,
        private RegistrationBuilderService $registrationBuilder,
        private RegistrationService $registrationService,
        private TicketService $ticketService,
        private CouponService $couponService,
        private QueueMailTaskService $queueMailTaskService
    ) {
    }

    public function index()
    {
        $tickets = Ticket::where('status', 'available')->get();
        return view('taquilla.index', compact('tickets'));
    }

    public function sell(Request $request)
    {
        $request->validate([
            'cart' => 'required|array|min:1',
            'email' => 'nullable|string',
            'payment_method' => 'required|in:cash,card,cortesia',
            'event_id' => 'required|string',
            'registration' => 'nullable|array',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        return DB::transaction(function () use ($request) {

            $paymentInput = $request->input('payment_method');
            $esCortesia = $paymentInput === 'cortesia';
            $paymentMethod = $esCortesia ? 'cash' : $paymentInput;

            $emailInput = trim($request->input('email'));

            $emailEsValido = !empty($emailInput) && filter_var($emailInput, FILTER_VALIDATE_EMAIL);

            // ==============================
            // DETERMINAR NOMBRE DEL COMPRADOR
            // ==============================

            if ($esCortesia) {

                // Si es cortesía y escribieron algo, guardar eso
                if (!empty($emailInput)) {
                    $nombreComprador = $emailInput;
                } else {
                    $nombreComprador = 'CORTESIA';
                }

            } else {

                // Venta normal
                if ($emailEsValido) {
                    $nombreComprador = 'taquilla';
                } elseif (!empty($emailInput)) {
                    $nombreComprador = $emailInput;
                } else {
                    $nombreComprador = 'taquilla';
                }
            }

            // ==============================
            // DETERMINAR EMAIL FINAL
            // ==============================

            $email = $esCortesia
                ? 'CORTESIA'
                : ($emailEsValido ? $emailInput : 'taquilla@local');

            $reference = 'TAQ-' . now()->format('YmdHis');
            $boletos = [];

            $evento = Eventos::findOrFail($request->input('event_id'));
            $couponResult = $this->couponService->applyCouponToCart(
                $evento,
                collect($request->cart)->map(function ($item) use ($evento) {
                    $qty = max(1, (int) ($item['qty'] ?? 1));
                    $type = $item['type'] ?? 'ticket';
                    $basePrice = (float) ($item['price'] ?? 0);

                    if ($type === 'ticket' && !empty($item['id'])) {
                        $ticketModel = Ticket::find($item['id']);
                        $basePrice = (float) ($ticketModel?->total_price ?? $basePrice);
                    }

                    if ($type === 'registration') {
                        $basePrice = RegistrationPricing::resolveUnitPrice($evento, $qty);
                    }

                    return [
                        'id' => $item['id'] ?? null,
                        'event_id' => $item['event_id'] ?? null,
                        'name' => $item['name'] ?? '',
                        'type' => $type,
                        'qty' => $qty,
                        'base_price' => round($basePrice, 2),
                        'price' => round($basePrice, 2),
                    ];
                })->values()->toArray(),
                $request->input('coupon_code')
            );

            if (!$couponResult['success']) {
                abort(422, $couponResult['message']);
            }

            foreach ($couponResult['cart'] as $item) {

                // =============================
                // REGISTRATIONS
                // =============================
                if (($item['type'] ?? null) === 'registration') {

                    $instances = $this->registrationService->create(
                        $evento,
                        [
                            'qty' => $item['qty'] ?? 1,
                            'email' => $email,
                            'nombre' => $nombreComprador,
                            'celular' => null,
                            'form_data' => $request->input('registration'),
                            'reference' => $reference,
                            'sale_channel' => 'taquilla',
                            'payment_method' => $paymentMethod,
                            'price' => $item['price'] ?? $evento->price,
                            'base_price' => $item['base_price'] ?? $evento->price,
                            'coupon_id' => $item['coupon_id'] ?? null,
                            'coupon_code' => $item['coupon_code'] ?? null,
                            'discount_percent' => $item['discount_percent'] ?? null,
                            'discount_amount' => $item['discount_amount'] ?? 0,
                        ]
                    );

                    foreach ($instances as $instance) {
                        $boletos[] = $this->registrationBuilder->build(
                            $evento,
                            $instance,
                            $instance->email
                        );
                    }

                    continue;
                }

                // =============================
                // TICKETS (delegado al service)
                // =============================
                $boletos = array_merge(
                    $boletos,
                    $this->ticketService->createFromTaquilla(
                        $evento,
                        $item,
                        $email,
                        $nombreComprador,
                        $reference,
                        $paymentMethod,
                        [
                            'price' => $item['price'] ?? null,
                            'base_price' => $item['base_price'] ?? null,
                            'coupon_id' => $item['coupon_id'] ?? null,
                            'coupon_code' => $item['coupon_code'] ?? null,
                            'discount_percent' => $item['discount_percent'] ?? null,
                            'discount_amount' => $item['discount_amount'] ?? 0,
                        ]
                    )
                );

            }


            if (
                $emailEsValido &&
                !$esCortesia &&
                $email !== 'taquilla@local' &&
                count($boletos) > 0
            ) {
                $this->queueMailTaskService->queueBoletos(
                    recipient: $email,
                    boletos: $boletos,
                    type: 'boletos_taquilla',
                    reference: $reference
                );
            }

            return view('pago.success', [
                'boletos' => $boletos,
                'email' => $email,
                'evento' => $evento
            ]);
        });
    }

    public function pdf(TicketInstance $instance)
    {
        $evento = Eventos::findOrFail($instance->event_id);

        $boleto = $this->ticketBuilder->build(
            ticket: $instance->ticket,
            instance: $instance,
            email: $instance->email ?? 'taquilla@local',
            purchasedAt: $instance->purchased_at,
            event: $evento,
            reference: $instance->reference
        );

        return Pdf::loadView('pdf.boletos', [
            'boletos' => [$boleto],
            'email' => $instance->email,
        ])->download('boleto-taquilla.pdf');
    }
}
