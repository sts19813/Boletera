<?php

namespace App\Mail;
use Illuminate\Mail\Mailable;

class BoletosMail extends Mailable
{
    private const WHATSAPP_BUTTON_EVENT_ID = '019e4bfe-7d59-70ec-a083-6d6e8c2cc15e';
    private const WHATSAPP_GROUP_LINK = 'https://chat.whatsapp.com/FFla6maYdkAKyNmUp8MWt3?mode=gi_t';

    public string $pdfContent;
    public array $boletos;

    public function __construct(string $pdfContent, array $boletos)
    {
        $this->pdfContent = $pdfContent;
        $this->boletos = $boletos;
    }

    public function build()
    {
        $evento = $this->boletos[0]['event'];
        $eventId = (string) ($evento['id'] ?? '');
        $whatsappGroupLink = $eventId === self::WHATSAPP_BUTTON_EVENT_ID
            ? self::WHATSAPP_GROUP_LINK
            : null;

        return $this
            ->subject('🎟️ Stomtickets ' . $evento['name'] . ' | Confirmación de compra')
            ->view('emails.boletos')
            ->with([
                'boletos' => $this->boletos,
                'evento' => $evento,
                'whatsappGroupLink' => $whatsappGroupLink,
            ])
            ->attachData(
                $this->pdfContent,
                'boletos.pdf',
                ['mime' => 'application/pdf']
            );
    }
}
