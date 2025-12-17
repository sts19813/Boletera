<?php

namespace App\Mail;



use Illuminate\Mail\Mailable;

class BoletosMail extends Mailable
{
    public string $pdfContent;

    public function __construct(string $pdfContent)
    {
        $this->pdfContent = $pdfContent;
    }

    public function build()
    {
        return $this
            ->subject('🎟️ Tus boletos – Box Azteca')
            ->view('emails.boletos')
            ->attachData(
                $this->pdfContent,
                'boletos.pdf',
                ['mime' => 'application/pdf']
            );
    }
}

