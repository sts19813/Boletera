<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\Ticket;
use App\Models\TicketInstance;
use App\Models\Eventos;
use Illuminate\Support\Str;
use App\Services\TicketBuilderService;
use Illuminate\Support\Facades\Auth;
class TicketService
{
    public function __construct(
        private TicketBuilderService $ticketBuilder
    ) {
    }

    public function createFromStripeIntent(string $paymentIntentId): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $intent = PaymentIntent::retrieve($paymentIntentId);

        if ($intent->status !== 'succeeded') {
            abort(403, 'Pago no confirmado');
        }

        $cart = session('svg_cart', []);

        if (empty($cart)) {
            abort(400, 'Carrito vacío o sesión expirada');
        }

        $email = $intent->metadata->email ?? null;
        $nombre = $intent->metadata->nombre ?? null;
        $celular = $intent->metadata->celular ?? null;

        $purchaseAt = now();
        $purchaseAtString = $purchaseAt->toDateTimeString();

        $boletos = [];

        foreach ($cart as $item) {
            if (($item['type'] ?? 'ticket') !== 'ticket') {
                continue;
            }

            $evento = Eventos::findOrFail($item['event_id']);
            $ticket = Ticket::findOrFail($item['id']);
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $baseUnitPrice = round((float) ($ticket->total_price ?? 0), 2);
            $finalUnitPrice = array_key_exists('price', $item)
                ? round((float) $item['price'], 2)
                : $baseUnitPrice;
            $discountPercent = array_key_exists('discount_percent', $item)
                ? (float) $item['discount_percent']
                : null;
            $discountAmount = array_key_exists('discount_amount', $item)
                ? round((float) $item['discount_amount'], 2)
                : round(max(0, $baseUnitPrice - $finalUnitPrice), 2);

            if ((string) $ticket->event_id !== (string) $evento->id) {
                abort(409, 'El boleto no pertenece al evento seleccionado');
            }

            if ($ticket->stock == 1) {

                $existingInstance = TicketInstance::ticketSales()
                    ->where('payment_intent_id', $paymentIntentId)
                    ->where('ticket_id', $ticket->id)
                    ->first();

                if ($existingInstance) {
                    $boletos[] = $this->ticketBuilder->build(
                        $ticket,
                        $existingInstance,
                        $email,
                        $purchaseAtString,
                        $evento,
                        $paymentIntentId
                    );
                    continue;
                }

                $instance = TicketInstance::create([
                    'sale_type' => 'ticket',
                    'event_id' => $evento->id,
                    'user_id' => Auth::id(),
                    'ticket_id' => $ticket->id,
                    'email' => $email,
                    'nombre' => $nombre,
                    'celular' => $celular,
                    'purchased_at' => $purchaseAt,
                    'qr_hash' => (string) Str::uuid(),
                    'payment_intent_id' => $paymentIntentId,
                    'reference' => $paymentIntentId,
                    'price' => $finalUnitPrice,
                    'sale_channel' => 'stripe',
                    'payment_method' => 'card',
                    'subtotal' => $baseUnitPrice,
                    'commission' => 0,
                    'total' => $finalUnitPrice,
                    'registered_at' => $purchaseAt,
                    'coupon_id' => $item['coupon_id'] ?? null,
                    'coupon_code' => $item['coupon_code'] ?? null,
                    'coupon_discount_percent' => $discountPercent,
                    'coupon_discount_amount' => $discountAmount,
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
                    $evento,
                    $paymentIntentId
                );

                continue;
            }

            $existingInstances = TicketInstance::ticketSales()
                ->where('payment_intent_id', $paymentIntentId)
                ->where('ticket_id', $ticket->id)
                ->get();

            if ($existingInstances->isNotEmpty()) {

                foreach ($existingInstances as $instance) {

                    $boletos[] = $this->ticketBuilder->build(
                        $ticket,
                        $instance,
                        $email,
                        $purchaseAtString,
                        $evento,
                        $paymentIntentId
                    );
                }

                continue;
            }

            for ($i = 0; $i < $qty; $i++) {

                $instance = TicketInstance::create([
                    'sale_type' => 'ticket',
                    'ticket_id' => $ticket->id,
                    'user_id' => Auth::id(),
                    'event_id' => $evento->id,
                    'email' => $email,
                    'nombre' => $nombre,
                    'celular' => $celular,
                    'purchased_at' => $purchaseAt,
                    'qr_hash' => (string) Str::uuid(),
                    'payment_intent_id' => $paymentIntentId,
                    'reference' => $paymentIntentId,
                    'price' => $finalUnitPrice,
                    'sale_channel' => 'stripe',
                    'payment_method' => 'card',
                    'subtotal' => $baseUnitPrice,
                    'commission' => 0,
                    'total' => $finalUnitPrice,
                    'coupon_id' => $item['coupon_id'] ?? null,
                    'coupon_code' => $item['coupon_code'] ?? null,
                    'coupon_discount_percent' => $discountPercent,
                    'coupon_discount_amount' => $discountAmount,
                ]);

                $boletos[] = $this->ticketBuilder->build(
                    $ticket,
                    $instance,
                    $email,
                    $purchaseAtString,
                    $evento,
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


    public function createFromTaquilla(
        Eventos $evento,
        array $item,
        string $email,
        string $nombreComprador,
        string $reference,
        string $paymentMethod,
        array $pricing = []
    ): array {

        $ticket = Ticket::lockForUpdate()->findOrFail($item['id']);
        $qty = max(1, (int) ($item['qty'] ?? 1));
        $baseUnitPrice = round((float) ($pricing['base_price'] ?? $ticket->total_price ?? 0), 2);
        $finalUnitPrice = array_key_exists('price', $pricing)
            ? round((float) $pricing['price'], 2)
            : $baseUnitPrice;
        $discountPercent = array_key_exists('discount_percent', $pricing)
            ? (float) $pricing['discount_percent']
            : null;
        $discountAmount = array_key_exists('discount_amount', $pricing)
            ? round((float) $pricing['discount_amount'], 2)
            : round(max(0, $baseUnitPrice - $finalUnitPrice), 2);

        if ((string) $ticket->event_id !== (string) $evento->id) {
            abort(409, 'El boleto no pertenece al evento seleccionado');
        }

        if ($ticket->stock < $qty) {
            abort(409, 'Stock insuficiente');
        }

        $boletos = [];

        for ($i = 0; $i < $qty; $i++) {

            $instance = TicketInstance::create([
                'sale_type' => 'ticket',
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'event_id' => $evento->id,
                'nombre' => $nombreComprador,
                'email' => $email,
                'purchased_at' => now(),
                'qr_hash' => (string) Str::uuid(),
                'reference' => $reference,
                'sale_channel' => 'taquilla',
                'price' => $finalUnitPrice,
                'payment_method' => $paymentMethod,
                'subtotal' => $baseUnitPrice,
                'commission' => 0,
                'total' => $finalUnitPrice,
                'coupon_id' => $pricing['coupon_id'] ?? null,
                'coupon_code' => $pricing['coupon_code'] ?? null,
                'coupon_discount_percent' => $discountPercent,
                'coupon_discount_amount' => $discountAmount,
            ]);

            $boletos[] = $this->ticketBuilder->build(
                $ticket,
                $instance,
                $email,
                $instance->purchased_at,
                $evento,
                $reference
            );
        }

        $ticket->increment('sold', $qty);
        $ticket->decrement('stock', $qty);

        if ($ticket->stock <= 0) {
            $ticket->update(['status' => 'sold']);
        }

        return $boletos;
    }
}
