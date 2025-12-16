<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\Ticket;
use App\Models\TicketInstance;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;


class PaymentController extends Controller
{
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

        $subtotal = collect($carrito)->sum(fn ($i) => $i['price'] * ($i['qty'] ?? 1));

        // comisión ejemplo
        $comision = round($subtotal * 0.05, 2);

        $total = $subtotal + $comision;

        return view('pago.form', [
            'carrito' => $carrito,
            'subtotal' => $subtotal,
            'comision' => $comision,
            'total' => $total
        ]);
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

        $subtotal = collect($carrito)->sum(fn ($i) => $i['price'] * ($i['qty'] ?? 1));
        $comision = round($subtotal * 0.05, 2);
        $total = $subtotal + $comision;

        Stripe::setApiKey(config('services.stripe.secret'));

        $intent = PaymentIntent::create([
            'amount' => (int) round($total * 100),
            'currency' => 'mxn',
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'cart' => json_encode($carrito),
                'subtotal' => $subtotal,
                'comision' => $comision
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

        $intent = PaymentIntent::retrieve($paymentIntentId);

        if ($intent->status !== 'succeeded') {
            abort(403, 'Pago no confirmado');
        }

        $cart = json_decode($intent->metadata->cart ?? '[]', true);

        if (empty($cart)) {
            abort(400, 'Carrito vacío');
        }

        /**
         * Timestamp único para TODA la compra
         */
        $purchaseAt = now();
        $purchaseAtString = $purchaseAt->toDateTimeString();

        $qrs = [];

        foreach ($cart as $item) {

            $ticket = Ticket::findOrFail($item['id']);
            $qty = max(1, (int) ($item['qty'] ?? 1));

            /**
             * ==================================================
             * CASO 1 — BOLETO ÚNICO (VIP / ASIENTO)
             * ==================================================
             */
            if ($ticket->stock == 1) {

                // Ya vendido → solo regenerar QR
                if ($ticket->status === 'sold') {

                    $qrs[] = $this->makeQr([
                        'type' => 'ticket',
                        'ticket_id' => $ticket->id,
                        'ticket_instance_id' => null,
                        'email' => $email,
                        'purchased_at' => optional($ticket->purchased_at)->toDateTimeString(),
                    ]);

                    continue;
                }

                // Marcar como vendido
                $ticket->update([
                    'status'       => 'sold',
                    'purchased_at' => $purchaseAt,
                ]);

                $qrs[] = $this->makeQr([
                    'type' => 'ticket',
                    'ticket_id' => $ticket->id,
                    'ticket_instance_id' => null,
                    'email' => $email,
                    'purchased_at' => $purchaseAtString,
                ]);

                continue;
            }

            /**
             * ==================================================
             * CASO 2 — BOLETOS GENERALES (stock > 1)
             * ==================================================
             */

            // Idempotencia por PaymentIntent
            $existingInstances = TicketInstance::where('payment_intent_id', $paymentIntentId)
                ->where('ticket_id', $ticket->id)
                ->get();

            if ($existingInstances->isNotEmpty()) {

                foreach ($existingInstances as $instance) {

                    $qrs[] = $this->makeQr([
                        'type' => 'ticket',
                        'ticket_id' => $ticket->id,
                        'ticket_instance_id' => $instance->id,
                        'email' => $instance->email,
                        'purchased_at' => $instance->purchased_at->toDateTimeString(),
                    ]);
                }

                continue;
            }

            // Crear instancias nuevas
            for ($i = 0; $i < $qty; $i++) {

                $instance = TicketInstance::create([
                    'ticket_id'         => $ticket->id,
                    'email'             => $email,
                    'purchased_at'      => $purchaseAt,
                    'qr_hash'           => (string) Str::uuid(),
                    'payment_intent_id' => $paymentIntentId,
                ]);

                $qrs[] = $this->makeQr([
                    'type' => 'ticket',
                    'ticket_id' => $ticket->id,
                    'ticket_instance_id' => $instance->id,
                    'email' => $email,
                    'purchased_at' => $purchaseAtString,
                ]);
            }

            // Actualizar contadores
            $ticket->increment('sold', $qty);

            if ($ticket->sold >= $ticket->stock) {
                $ticket->update(['status' => 'sold']);
            }
        }

        return view('pago.success', [
            'qrs' => $qrs,
            'email' => $email,
        ]);
    }

    private function makeQr(array $payload)
    {
        return QrCode::size(220)
            ->format('svg')
            ->generate(json_encode($payload));
    }


    public function cancel()
    {
        return view('pago.cancel');
    }
}
