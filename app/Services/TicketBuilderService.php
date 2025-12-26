<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketInstance;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Carbon\Carbon;


class TicketBuilderService
{
    /**
     * Construye la data estándar de un boleto
     * Usado por: online (Stripe) y taquilla
     */
    public function build(
        Ticket $ticket,
        TicketInstance $instance,
        string $email,
        $purchasedAt,
        string $reference
    ): array {
        return [
            'event' => [
                'name' => $ticket->event->name ?? 'Evento - Box Azteca',
                'date' => '17 de Enero de 2026',
                'time' => '7:00 PM',
                'venue' => 'Centro de Convenciones Siglo XXI',
                'organizer' => 'Maxboxing',
            ],
            'ticket' => [
                'name' => $ticket->name,
                'row' => $ticket->row ?? null,
                'seat' => $ticket->seat ?? null,
                'price' => $ticket->total_price,
            ],
            'order' => [
                'reference' => $instance?->reference,
                'sale_channel' => $instance?->sale_channel,
                'payment_method' => $instance?->payment_method,
                'payment_intent' => $instance?->payment_intent_id 
                    ?? $instance?->reference 
                    ?? 'N/A',
                'purchased_at' => $purchasedAt,
            ],
            'user' => [
                'email' => $email,
            ],
            'qr' => $this->makeQr([
                'type' => 'ticket',
                'ticket_id' => $ticket->id,
                'ticket_instance_id' => $instance?->id,
                'hash' => $instance?->qr_hash,
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
