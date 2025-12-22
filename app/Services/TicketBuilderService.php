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
        ?TicketInstance $instance,
        string $email,
        string|\DateTimeInterface $purchasedAt,
        string $reference
    ): array {


        if ($purchasedAt instanceof Carbon) {
            $purchasedAt = $purchasedAt->format('Y-m-d H:i:s');
        } elseif ($purchasedAt instanceof \DateTimeInterface) {
            $purchasedAt = $purchasedAt->format('Y-m-d H:i:s');
        } elseif (empty($purchasedAt)) {
            $purchasedAt = now()->format('Y-m-d H:i:s');
        }
        return [
            'event' => [
                'name' => $ticket->event->name ?? 'Evento - Box Azteca',
                'date' => '17 de Enero de 2026',
                'time' => '7:00 PM',
                'venue' => 'Centro de Convenciones Siglo XXI',
                'organizer' => 'Maxboxing',
            ],

            'ticket' => [
                'id' => $ticket->id,
                'name' => $ticket->name,
                'row' => $ticket->row ?? null,
                'seat' => $ticket->seat ?? null,
                'price' => $ticket->total_price,
            ],

            'order' => [
                'reference' => $reference,
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

    /**
     * Genera o reutiliza QR
     */
    private function makeQr(array $payload): string
    {
        $filename = 'qr_' . md5(json_encode($payload)) . '.png';
        $dir = public_path('qrs');

        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . '/' . $filename;

        if (!file_exists($path)) {

            $result = Builder::create()
                ->writer(new PngWriter())
                ->data(json_encode($payload))
                ->size(220)
                ->margin(10)
                ->build();

            file_put_contents($path, $result->getString());
        }

        return asset('qrs/' . $filename);
    }
}
