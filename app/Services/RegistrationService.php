<?php

namespace App\Services;

use App\Models\Eventos;
use App\Models\TicketInstance;
use App\Support\RegistrationPricing;
use Illuminate\Support\Facades\Auth;
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
        $price = RegistrationPricing::resolveUnitPrice($evento, $qty);

        // Validar cupo se omite en taquilla,
        // se asume que el taquillero es consciente de la capacidad del evento y no permitirá ventas que excedan el cupo disponible.
        // Sin embargo, si se desea implementar una validación de cupo, se puede descomentar el siguiente bloque de código:
        //if ($evento->max_capacity < $qty) {
        //    abort(409, 'Cupo agotado');
        //}

        $instances = [];

        for ($i = 0; $i < $qty; $i++) {
            $registeredAt = now();

            $instance = TicketInstance::create([
                'ticket_id' => null,
                'sale_type' => 'registration',
                'event_id' => $evento->id,
                'user_id' => Auth::id(),
                'email' => $email,
                'nombre' => $nombre,
                'celular' => $celular,
                'team_name' => $formData['team_name'] ?? null,
                'qr_hash' => (string) Str::uuid(),
                'registered_at' => $registeredAt,
                'purchased_at' => $registeredAt,
                'price' => $price,
                'subtotal' => $price,
                'commission' => 0.00,
                'total' => $price,
                'form_data' => $formData,
                'payment_intent_id' => $reference,
                'reference' => $reference,
                'sale_channel' => $saleChannel,
                'payment_method' => $paymentMethod,
            ]);

            $instances[] = $instance;
        }

        if ($evento->max_capacity > 0) {
            $nuevoCupo = max(0, $evento->max_capacity - $qty);
            $evento->update(['max_capacity' => $nuevoCupo]);
        }

        return $instances;
    }
}
