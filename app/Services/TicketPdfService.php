<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class TicketPdfService
{
    public function make(array $boleto): string
    {
        return Pdf::loadView('pdf.ticket', [
            'boleto' => $boleto
        ])->output();
    }
}
