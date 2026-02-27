<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\TicketInstance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\BoletosMail;
use App\Services\TicketBuilderService;
use App\Models\Eventos;
use App\Services\TicketService;
use App\Services\RegistrationStripeService;
use App\Models\RegistrationInstance;
use App\Services\RegistrationBuilderService;


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
        // Normalmente esto vendrá del carrito en sesión
        $carrito = session('svg_cart', []);

        if (empty($carrito)) {
            abort(404, 'Carrito vacío');
        }

        $eventId = session('event_id');

        if (!$eventId) {
            abort(400, 'Evento no definido');
        }

        $registration = session('registration_form');


        $evento = Eventos::findOrFail($eventId);


        $subtotal = collect($carrito)->sum(fn($i) => $i['price'] * ($i['qty'] ?? 1));

        // comisión ejemplo
        $subtotal = collect($carrito)->sum(fn($i) => $i['price'] * ($i['qty'] ?? 1));

        // cargo solo informativo
        $comision = round($subtotal * 0.05, 2);

        // TOTAL SIN COMISIÓN
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
            return response()->json(['error' => 'Carrito vacío'], 400);
        }

        $subtotal = collect($carrito)->sum(fn($i) => $i['price'] * ($i['qty'] ?? 1));

        // solo informativo
        $comision = round($subtotal * 0.05, 2);

        Stripe::setApiKey(config('services.stripe.secret'));
        // Stripe cobra SOLO el subtotal
        $total = $subtotal;

        $intent = PaymentIntent::create([
            'amount' => (int) round($total * 100),
            'currency' => 'mxn',
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'cart' => json_encode($carrito),
                'subtotal' => $subtotal,
                'comision' => $comision,
                'comision_aplicada' => false,

                'nombre' => $request->nombre,
                'celular' => $request->celular,
                'email' => $request->email,
            ],
        ]);

        return response()->json([
            'clientSecret' => $intent->client_secret
        ]);
    }




    public function success(Request $request)
    {
        $paymentIntentId = $request->query('pi');


        if (!$paymentIntentId) {
            abort(400, 'Pago inválido');
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        $intent = PaymentIntent::retrieve($paymentIntentId);
        $email = $intent->metadata->email ?? null;
        $cart = json_decode($intent->metadata->cart ?? '[]', true);

        if (empty($cart)) {
            abort(400, 'Carrito vacío');
        }

        $isRegistration = collect($cart)->contains(
            fn($i) => ($i['type'] ?? null) === 'registration'
        );

        if ($isRegistration) {
            $boletos = $this->registrationStripeService->createFromStripeIntent(
                $paymentIntentId
            );

        } else {
            $boletos = $this->ticketService->createFromStripeIntent(
                $paymentIntentId
            );
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

        // ================================
        // 1️⃣ Buscar Tickets
        // ================================
        $ticketInstances = TicketInstance::where(function ($q) use ($reference) {
            $q->where('payment_intent_id', $reference)
                ->orWhere('reference', $reference);
        })->get();

        if ($ticketInstances->isNotEmpty()) {

            $instance = $ticketInstances->first();
            $evento = Eventos::findOrFail($instance->event_id);

            $boletos = $ticketInstances->map(
                fn($instance) =>
                $this->ticketBuilder->build(
                    $instance->ticket,
                    $instance,
                    $email,
                    $instance->purchased_at,
                    $evento,
                    $instance->payment_intent_id ?? $instance->reference
                )
            )->toArray();
        }

        // ================================
        // 2️⃣ Buscar Registrations
        // ================================
        else {

            $registrationInstances = RegistrationInstance::where(
                'payment_intent_id',
                $reference
            )->get();

            if ($registrationInstances->isEmpty()) {
                abort(404, 'Boletos o inscripciones no encontrados');
            }

            $instance = $registrationInstances->first();
            $evento = Eventos::findOrFail($instance->event_id);

            $boletos = $registrationInstances->map(
                fn($instance) =>
                $this->registrationBuilder->build(
                    $evento,
                    $instance,
                    $instance->email
                )
            )->toArray();
        }

        // ================================
        // PDF
        // ================================
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
}
