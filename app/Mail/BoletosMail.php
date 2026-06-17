<?php

namespace App\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BoletosMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private const WHATSAPP_BUTTON_EVENT_ID = '019e4bfe-7d59-70ec-a083-6d6e8c2cc15e';
    private const WHATSAPP_GROUP_LINK = 'https://chat.whatsapp.com/FFla6maYdkAKyNmUp8MWt3?mode=gi_t';

    public array $boletos;
    public string $mailSubject;

    public int $tries = 5;
    public int $timeout = 180;
    public array $backoff = [15, 60, 180];

    public function __construct(array $boletos, ?string $mailSubject = null)
    {
        $this->boletos = $boletos;
        $defaultEventName = (string) ($boletos[0]['event']['name'] ?? 'Evento');
        $this->mailSubject = $mailSubject
            ?? ('🎟️ Stomtickets ' . $defaultEventName . ' | Confirmación de compra');

        $this->onQueue(config('queue.ticket_delivery_queue', 'ticket-delivery'));
        $this->afterCommit();
    }

    public function build()
    {
        $evento = $this->boletos[0]['event'] ?? ['id' => null, 'name' => 'Evento'];
        $eventId = (string) ($evento['id'] ?? '');
        $configuredWhatsappLink = trim((string) ($evento['whatsapp_group_link'] ?? ''));
        $whatsappGroupLink = $configuredWhatsappLink !== '' ? $configuredWhatsappLink : null;

        if (!$whatsappGroupLink && $eventId === self::WHATSAPP_BUTTON_EVENT_ID) {
            $whatsappGroupLink = self::WHATSAPP_GROUP_LINK;
        }

        $email = (string) ($this->boletos[0]['user']['email'] ?? '');
        $pdfContent = Pdf::loadView('pdf.boletos', [
            'boletos' => $this->boletos,
            'email' => $email,
        ])->setPaper([0, 0, 400, 700])->output();

        return $this
            ->subject($this->mailSubject)
            ->view('emails.boletos')
            ->with([
                'boletos' => $this->boletos,
                'evento' => $evento,
                'whatsappGroupLink' => $whatsappGroupLink,
            ])
            ->attachData(
                $pdfContent,
                'boletos.pdf',
                ['mime' => 'application/pdf']
            );
    }
}
