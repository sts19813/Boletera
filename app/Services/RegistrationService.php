<?php

namespace App\Services;

use App\Models\Eventos;
use App\Models\RegistrationInstance;
use App\Models\Registration;
use Illuminate\Support\Str;

class RegistrationService
{
    public function create(
        Eventos $evento,
        array $data
    ): array {

        $qty = max(1, (int) ($data['qty'] ?? 1));
        $email = $data['email'] ?? 'taquilla@local';
        $nombre = $data['nombre'] ?? null;
        $celular = $data['celular'] ?? null;
        $formData = $data['form_data'] ?? null;
        $reference = $data['reference'] ?? null;
        $saleChannel = $data['sale_channel'] ?? 'taquilla';
        $paymentMethod = $data['payment_method'] ?? 'cash';
        $price = $data['price'] ?? $evento->price ?? 0;

        if ($evento->max_capacity < $qty) {
            abort(409, 'Cupo agotado');
        }

        $instances = [];

        for ($i = 0; $i < $qty; $i++) {

            $instance = RegistrationInstance::create([
                'event_id' => $evento->id,
                'email' => $email,
                'nombre' => $nombre,
                'celular' => $celular,
                'qr_hash' => (string) Str::uuid(),
                'registered_at' => now(),
                'price' => $price,
                'payment_intent_id' => $reference,
                'sale_channel' => $saleChannel,
                'payment_method' => $paymentMethod,
            ]);

            if ($formData) {

                Registration::create([
                    'registration_instance_id' => $instance->id,
                    'event_id' => $evento->id,
                    'subtotal' => $price,
                    'commission' => 0.00,
                    'total' => $price,
                    'form_data' => $formData,
                ]);
            }

            $instances[] = $instance;
        }

        $evento->decrement('max_capacity', $qty);

        return $instances;
    }
}