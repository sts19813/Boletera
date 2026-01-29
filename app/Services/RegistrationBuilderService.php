<?php


namespace App\Services;

use App\Models\Eventos;
use App\Models\RegistrationInstance;
use Illuminate\Support\Str;

class RegistrationBuilderService
{
    public function build(
        Eventos $evento,
        RegistrationInstance $instance,
        string $email
    ): array {
        return [
            'event' => [
                'name' => $evento->name,
                'date' => optional($evento->event_date)->format('d/m/Y'),
                'time' => $evento->hora_inicio,
                'venue' => $evento->location,
            ],
            'ticket' => [
                'name' => 'Inscripción',
                'row' => null,
                'seat' => null,
                'price' => $evento->price,
            ],
            'order' => [
                'payment_intent' => $instance->payment_intent_id,
                'purchased_at' => $instance->registered_at,
            ],
            'user' => [
                'email' => $email,
            ],
            'qr' => $this->makeQr([
                'type' => 'registration',
                'event_id' => $evento->id,
                'registration_instance_id' => $instance->id,
                'hash' => $instance->qr_hash,
            ]),
        ];
    }

    private function makeQr(array $payload): string
    {
        $filename = 'qr_' . md5(json_encode($payload)) . '.png';
        $path = public_path('qrs/' . $filename);

        if (!file_exists($path)) {
            $result = \Endroid\QrCode\Builder\Builder::create()
                ->writer(new \Endroid\QrCode\Writer\PngWriter())
                ->data(json_encode($payload))
                ->size(220)
                ->margin(10)
                ->build();

            file_put_contents($path, $result->getString());
        }

        return asset('qrs/' . $filename);
    }
}
