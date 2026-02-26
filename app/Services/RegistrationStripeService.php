<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\Eventos;
use App\Models\RegistrationInstance;
use App\Models\Registration;
use Illuminate\Support\Str;
use App\Services\RegistrationBuilderService;

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

            $existingInstances = RegistrationInstance::where(
                'payment_intent_id',
                $paymentIntentId
            )
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

            for ($i = 0; $i < $toCreate; $i++) {

                $instance = RegistrationInstance::create([
                    'event_id' => $evento->id,
                    'email' => $email,
                    'nombre' => $nombre,
                    'celular' => $celular,
                    'payment_intent_id' => $paymentIntentId,
                    'qr_hash' => (string) Str::uuid(),
                    'registered_at' => $purchaseAt,
                    'price' => $evento['price'] ?? 0,
                ]);

                $registrationForm = session('registration_form');
                if ($registrationForm) {

                    $existingRegistration = Registration::where(
                        'registration_instance_id',
                        $instance->id
                    )->first();

                    if (!$existingRegistration) {

                        $subtotal = collect($cart)->sum(
                            fn($i) => $i['price'] * ($i['qty'] ?? 1)
                        );

                        $commission = round($subtotal * 0.05, 2);
                        $total = $subtotal;

                        Registration::create([
                            'registration_instance_id' => $instance->id,
                            'event_id' => $evento->id,
                            'subtotal' => $subtotal,
                            'commission' => $commission,
                            'total' => $total,
                            'form_data' => $registrationForm,
                        ]);
                    }
                }

                $boletos[] = $this->registrationBuilder->build(
                    $evento,
                    $instance,
                    $email
                );
            }

            if ($existingInstances->isEmpty()) {
                $evento->decrement('max_capacity', $qty);
            }

            session()->forget('registration_form');
        }

        return $boletos;
    }
}