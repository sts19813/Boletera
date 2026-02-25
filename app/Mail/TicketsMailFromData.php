<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use App\Services\TicketPdfService;

class TicketsMailFromData extends Mailable
{
    public array $boletos;

    public function __construct(array $boletos)
    {
        $this->boletos = $boletos;
    }

    public function build()
    {
        $mail = $this
            ->subject('🎟️ Reenvío de boletos')
            ->view('emails.resend');

        foreach ($this->boletos as $i => $boleto) {

            $pdf = app(TicketPdfService::class)
                ->make($boleto);

            $mail->attachData(
                $pdf,
                "boleto-" . ($i + 1) . ".pdf",
                ['mime' => 'application/pdf']
            );
        }

        return $mail;
    }
}
