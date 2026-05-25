<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class TicketPdfService
{
    public function make(array $boleto): string
    {
        return Pdf::loadView('pdf.boletos', [
            'boletos' => [$boleto],
            'email' => (string) ($boleto['user']['email'] ?? ''),
        ])->setPaper([0, 0, 400, 700])->output();
    }
}
