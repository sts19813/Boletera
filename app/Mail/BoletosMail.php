<?php

namespace App\Mail;
use Illuminate\Mail\Mailable;

class BoletosMail extends Mailable
{
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

        return $this
            ->subject('🎟️ Stomtickets ' . $evento['name'] . ' | Confirmación de compra')
            ->view('emails.boletos')
            ->with([
                'boletos' => $this->boletos,
                'evento' => $evento
            ])
            ->attachData(
                $this->pdfContent,
                'boletos.pdf',
                ['mime' => 'application/pdf']
            );
    }
}
