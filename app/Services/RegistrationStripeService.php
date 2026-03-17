<?php

namespace App\Services;

use App\Models\Eventos;
use App\Models\TicketInstance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class RegistrationStripeService
{
    public function __construct(
        private RegistrationBuilderService $registrationBuilder
    ) {
    }

    public function createFromStripeIntent(string $paymentIntentId): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $intent = PaymentIntent::retrieve($paymentIntentId);

        $email = $intent->metadata->email ?? null;
        $nombre = $intent->metadata->nombre ?? null;
        $celular = $intent->metadata->celular ?? null;

        if ($intent->status !== 'succeeded') {
            abort(403, 'Pago no confirmado');
        }

        $cart = session('svg_cart', []);

        if (empty($cart)) {
            abort(400, 'Carrito vacio o sesion expirada');
        }

        $purchaseAt = now();
        $boletos = [];
        $registrationForm = session('registration_form');

        foreach ($cart as $item) {
            if (($item['type'] ?? null) !== 'registration') {
                continue;
            }

            $evento = Eventos::findOrFail($item['event_id']);
            $qty = max(1, (int) ($item['qty'] ?? 1));

            $existingInstances = TicketInstance::registrationSales()
                ->where('payment_intent_id', $paymentIntentId)
                ->where('event_id', $evento->id)
                ->get();

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

            $toCreate = $qty - $existingInstances->count();

            if ($evento->max_capacity < $toCreate) {
                abort(409, 'Cupo agotado');
            }

            $subtotal = collect($cart)->sum(
                fn($i) => ((float) ($i['price'] ?? 0)) * (int) ($i['qty'] ?? 1)
            );
            $commission = round($subtotal * 0.05, 2);
            $total = $subtotal;

            for ($i = 0; $i < $toCreate; $i++) {
                $instance = TicketInstance::create([
                    'ticket_id' => null,
                    'sale_type' => 'registration',
                    'event_id' => $evento->id,
                    'user_id' => Auth::id(),
                    'email' => $email,
                    'nombre' => $nombre,
                    'celular' => $celular,
                    'team_name' => $registrationForm['team_name'] ?? null,
                    'payment_intent_id' => $paymentIntentId,
                    'reference' => $paymentIntentId,
                    'qr_hash' => (string) Str::uuid(),
                    'registered_at' => $purchaseAt,
                    'purchased_at' => $purchaseAt,
                    'price' => $evento->price ?? 0,
                    'subtotal' => $subtotal,
                    'commission' => $commission,
                    'total' => $total,
                    'form_data' => $registrationForm,
                    'sale_channel' => 'stripe',
                    'payment_method' => 'card',
                ]);

                $boletos[] = $this->registrationBuilder->build(
                    $evento,
                    $instance,
                    $email
                );
            }

            if ($toCreate > 0) {
                $evento->decrement('max_capacity', $toCreate);
            }
        }

        session()->forget('registration_form');

        return $boletos;
    }
}
