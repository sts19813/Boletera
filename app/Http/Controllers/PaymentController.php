<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\Ticket;
use App\Models\TicketInstance;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\BoletosMail;
use App\Services\TicketBuilderService;
use App\Models\Eventos;
use App\Models\RegistrationInstance;


use App\Services\RegistrationBuilderService;

class PaymentController extends Controller
{

    public function __construct(
        private TicketBuilderService $ticketBuilder,
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
            ],
        ]);

        return response()->json([
            'clientSecret' => $intent->client_secret
        ]);
    }




    public function success(Request $request)
    {
        $paymentIntentId = $request->query('pi');
        $email = $request->query('email');

        if (!$paymentIntentId) {
            abort(400, 'Pago inválido');
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        $intent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

        $cart = json_decode($intent->metadata->cart ?? '[]', true);

        if (empty($cart)) {
            abort(400, 'Carrito vacío');
        }

        // 🔎 Detectar tipo de compra

        $isRegistration = collect($cart)->contains(
            fn($i) => ($i['type'] ?? null) === 'registration'
        );

        if ($isRegistration) {

            // 📝 INSCRIPCIONES
            $boletos = $this->generateRegistrationsFromPaymentIntent(
                $paymentIntentId,
                $email
            );

        } else {

            // 🎟️ TICKETS (flujo existente)
            $boletos = $this->generateBoletosFromPaymentIntent(
                $paymentIntentId,
                $email
            );
        }

        // 📄 PDF (puede ser el mismo o adaptado)
        $pdfContent = $this->generateBoletosPdf($boletos, $email);

        // ✉️ Envío de correo
        Mail::to($email)->send(
            new BoletosMail($pdfContent)
        );

        // 🖥️ Vista final
        return view('pago.success', compact('boletos', 'email'));
    }



    private function generateRegistrationsFromPaymentIntent(
        string $paymentIntentId,
        string $email
    ): array {

        Stripe::setApiKey(config('services.stripe.secret'));

        $intent = PaymentIntent::retrieve($paymentIntentId);

        if ($intent->status !== 'succeeded') {
            abort(403, 'Pago no confirmado');
        }

        $cart = json_decode($intent->metadata->cart ?? '[]', true);

        if (empty($cart)) {
            abort(400, 'Carrito vacío');
        }

        $purchaseAt = now();
        $boletos = [];

        foreach ($cart as $item) {

            if (($item['type'] ?? null) !== 'registration') {
                continue;
            }

            $evento = Eventos::findOrFail($item['event_id']);
            $qty = max(1, (int) ($item['qty'] ?? 1));

            /**
             * 🔒 IDEMPOTENCIA POR EVENTO + PAYMENT
             */
            $existingInstances = RegistrationInstance::where(
                'payment_intent_id',
                $paymentIntentId
            )
                ->where('event_id', $evento->id)
                ->get();

            // ➜ Ya existen todas las necesarias
            if ($existingInstances->count() >= $qty) {

                foreach ($existingInstances as $instance) {
                    $boletos[] = $this->registrationBuilder->build(
                        $evento,
                        $instance,
                        $email
                    );
                }

                continue;
            }

            // ➜ Validar cupo SOLO para las faltantes
            $toCreate = $qty - $existingInstances->count();

            if ($evento->max_capacity < $toCreate) {
                abort(409, 'Cupo agotado');
            }

            // ➜ Crear SOLO las que faltan
            for ($i = 0; $i < $toCreate; $i++) {

                $instance = RegistrationInstance::create([
                    'event_id' => $evento->id,
                    'email' => $email,
                    'payment_intent_id' => $paymentIntentId,
                    'qr_hash' => (string) Str::uuid(),
                    'registered_at' => $purchaseAt,
                ]);

                $boletos[] = $this->registrationBuilder->build(
                    $evento,
                    $instance,
                    $email
                );
            }

            // 📉 Decrementar cupo SOLO una vez
            if ($existingInstances->isEmpty()) {
                $evento->decrement('max_capacity', $qty);
            }
        }

        return $boletos;
    }

    private function generateBoletosPdf(array $boletos, string $email)
    {
        return Pdf::loadView('pdf.boletos', [
            'boletos' => $boletos,
            'email' => $email,
        ])->setPaper([0, 0, 400, 700])->output();

    }



    private function generateBoletosFromPaymentIntent(string $paymentIntentId, string $email): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $intent = PaymentIntent::retrieve($paymentIntentId);

        if ($intent->status !== 'succeeded') {
            abort(403, 'Pago no confirmado');
        }

        $cart = json_decode($intent->metadata->cart ?? '[]', true);

        if (empty($cart)) {
            abort(400, 'Carrito vacío');
        }

        $purchaseAt = now();
        $purchaseAtString = $purchaseAt->toDateTimeString();

        $boletos = [];

        foreach ($cart as $item) {

            $ticket = Ticket::findOrFail($item['id']);
            $qty = max(1, (int) ($item['qty'] ?? 1));

            if ($ticket->stock == 1) {

                $existingInstance = TicketInstance::where('payment_intent_id', $paymentIntentId)
                    ->where('ticket_id', $ticket->id)
                    ->first();

                if ($existingInstance) {
                    $boletos[] = $this->ticketBuilder->build(
                        $ticket,
                        $instance,
                        $email,
                        $purchaseAtString,
                        $paymentIntentId
                    );
                    continue;
                }

                $instance = TicketInstance::create([
                    'ticket_id' => $ticket->id,
                    'email' => $email,
                    'purchased_at' => $purchaseAt,
                    'qr_hash' => (string) Str::uuid(),
                    'payment_intent_id' => $paymentIntentId,
                    'reference' => $paymentIntentId,
                    'sale_channel' => 'stripe',
                    'payment_method' => 'card',
                ]);

                $ticket->update([
                    'stock' => 0,
                    'sold' => 1,
                    'status' => 'sold',
                    'purchased_at' => $purchaseAt,
                ]);

                $boletos[] = $this->ticketBuilder->build(
                    $ticket,
                    $instance,
                    $email,
                    $purchaseAtString,
                    $paymentIntentId
                );

                continue;
            }


            $existingInstances = TicketInstance::where('payment_intent_id', $paymentIntentId)
                ->where('ticket_id', $ticket->id)
                ->get();

            if ($existingInstances->isNotEmpty()) {

                foreach ($existingInstances as $instance) {

                    $boletos[] = $this->ticketBuilder->build(
                        $ticket,
                        $instance,
                        $email,
                        $purchaseAtString,
                        $paymentIntentId
                    );
                }

                continue;
            }

            for ($i = 0; $i < $qty; $i++) {

                $instance = TicketInstance::create([
                    'ticket_id' => $ticket->id,
                    'email' => $email,
                    'purchased_at' => $purchaseAt,
                    'qr_hash' => (string) Str::uuid(),
                    'payment_intent_id' => $paymentIntentId,
                    'reference' => $paymentIntentId,
                    'sale_channel' => 'stripe',
                    'payment_method' => 'card',
                ]);

                $boletos[] = $this->ticketBuilder->build(
                    $ticket,
                    $instance,
                    $email,
                    $purchaseAtString,
                    $paymentIntentId
                );
            }

            $ticket->increment('sold', $qty);
            $ticket->decrement('stock', $qty);
            if ($ticket->stock <= 0) {
                $ticket->update([
                    'stock' => 0,
                    'status' => 'sold'
                ]);
            }
        }

        return $boletos;
    }


    public function reprint(Request $request)
    {
        $reference = $request->get('ref');
        $email = $request->get('email') ?: 'taquilla@local';

        if (!$reference) {
            abort(400, 'Referencia requerida');
        }

        // Buscar boletos EXISTENTES
        $instances = TicketInstance::where(function ($q) use ($reference) {
            $q->where('payment_intent_id', $reference)
                ->orWhere('reference', $reference);
        })->get();


        if ($instances->isEmpty()) {
            abort(404, 'Boletos no encontrados');
        }

        $boletos = $instances->map(
            fn($instance) =>
            $this->ticketBuilder->build(
                $instance->ticket,
                $instance,
                $email,
                $instance->purchased_at,
                $instance->payment_intent_id ?? $instance->reference
            )
        )->toArray();

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
