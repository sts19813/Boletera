<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\Ticket;
use App\Models\TicketInstance;
use App\Models\Eventos;
use Illuminate\Support\Str;
use App\Services\TicketBuilderService;

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

        $cart = json_decode($intent->metadata->cart ?? '[]', true);

        if (empty($cart)) {
            abort(400, 'Carrito vacío');
        }

        $email = $intent->metadata->email ?? null;
        $nombre = $intent->metadata->nombre ?? null;
        $celular = $intent->metadata->celular ?? null;

        $purchaseAt = now();
        $purchaseAtString = $purchaseAt->toDateTimeString();

        $boletos = [];

        foreach ($cart as $item) {

            $evento = Eventos::findOrFail($item['event_id']);
            $ticket = Ticket::findOrFail($item['id']);
            $qty = max(1, (int) ($item['qty'] ?? 1));

            if ($ticket->stock == 1) {

                $existingInstance = TicketInstance::where('payment_intent_id', $paymentIntentId)
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
                    'event_id' => $evento->id,
                    'ticket_id' => $ticket->id,
                    'email' => $email,
                    'nombre' => $nombre,
                    'celular' => $celular,
                    'purchased_at' => $purchaseAt,
                    'qr_hash' => (string) Str::uuid(),
                    'payment_intent_id' => $paymentIntentId,
                    'reference' => $paymentIntentId,
                    'price' => $ticket->total_price,
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
                    $evento,
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
                        $evento,
                        $paymentIntentId
                    );
                }

                continue;
            }

            for ($i = 0; $i < $qty; $i++) {

                $instance = TicketInstance::create([
                    'ticket_id' => $ticket->id,
                    'event_id' => $evento->id,
                    'email' => $email,
                    'nombre' => $nombre,
                    'celular' => $celular,
                    'purchased_at' => $purchaseAt,
                    'qr_hash' => (string) Str::uuid(),
                    'payment_intent_id' => $paymentIntentId,
                    'reference' => $paymentIntentId,
                    'price' => $ticket->total_price,
                    'sale_channel' => 'stripe',
                    'payment_method' => 'card',
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
        string $reference,
        string $paymentMethod
    ): array {

        $ticket = Ticket::lockForUpdate()->findOrFail($item['id']);
        $qty = max(1, (int) ($item['qty'] ?? 1));

        if ($ticket->stock < $qty) {
            abort(409, 'Stock insuficiente');
        }

        $boletos = [];

        for ($i = 0; $i < $qty; $i++) {

            $instance = TicketInstance::create([
                'ticket_id' => $ticket->id,
                'event_id' => $evento->id,
                'nombre' => 'taquilla',
                'email' => $email,
                'purchased_at' => now(),
                'qr_hash' => (string) Str::uuid(),
                'reference' => $reference,
                'sale_channel' => 'taquilla',
                'price' => $ticket->total_price,
                'payment_method' => $paymentMethod,
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