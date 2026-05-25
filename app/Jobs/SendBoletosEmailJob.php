<?php

namespace App\Jobs;

use App\Mail\BoletosMail;
use App\Services\BoletosPayloadService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendBoletosEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<int, string> $instanceIds
     */
    public function __construct(
        public string $email,
        public array $instanceIds,
        public string $fallbackEmail = 'taquilla@local'
    ) {
    }

    public function handle(BoletosPayloadService $payloadService): void
    {
        $boletos = $payloadService->buildFromInstanceIds($this->instanceIds, $this->fallbackEmail);

        if (empty($boletos)) {
            return;
        }

        $pdfContent = Pdf::loadView('pdf.boletos', [
            'boletos' => $boletos,
            'email' => $this->email,
        ])->setPaper([0, 0, 400, 700])->output();

        Mail::to($this->email)->send(new BoletosMail($pdfContent, $boletos));
    }
}
