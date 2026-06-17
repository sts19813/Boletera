<?php

namespace App\Mail;

use App\Models\Eventos;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DirectRegistrationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private const WHATSAPP_GROUP_LINK = 'https://chat.whatsapp.com/FaPvvNc1XyV9QxLKk6xb5w?mode=gi_t';

    public int $tries = 5;
    public int $timeout = 120;
    public array $backoff = [15, 60, 180];

    public function __construct(
        public Eventos $event,
        public array $registrationData
    ) {
        $this->onQueue(config('queue.ticket_delivery_queue', 'ticket-delivery'));
        $this->afterCommit();
    }

    public function build()
    {
        $templateForm = (string) ($this->registrationData['template_form'] ?? '');
        $configuredWhatsappLink = trim((string) ($this->event->whatsapp_group_link ?? ''));
        $whatsappLink = $configuredWhatsappLink !== ''
            ? $configuredWhatsappLink
            : ($templateForm === 'whatsapp_direct' ? self::WHATSAPP_GROUP_LINK : null);

        return $this
            ->subject('Registro confirmado - ' . $this->event->name)
            ->view('emails.direct-registration')
            ->with([
                'evento' => $this->event,
                'registration' => $this->registrationData,
                'whatsappLink' => $whatsappLink,
            ]);
    }
}
