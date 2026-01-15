<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketInstance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Services\TicketBuilderService;
use App\Services\TicketPdfService;

class TicketReprintController extends Controller
{
    public function index()
    {
        $instances = TicketInstance::with([
            'ticket',
            'ticket.stage',
        ])->orderByDesc('purchased_at')->get();


        return view('admin.ticket_instances.index', compact('instances'));
    }

    public function reprint(Request $request)
    {
        $reference = $request->get('ref');
        $email = $request->get('email') ?: 'taquilla@local';

        if (!$reference) {
            abort(400, 'Referencia requerida');
        }

        $instances = TicketInstance::where(function ($q) use ($reference) {
            $q->where('payment_intent_id', $reference)
                ->orWhere('reference', $reference);
        })->get();

        if ($instances->isEmpty()) {
            abort(404, 'Boletos no encontrados');
        }

        $boletos = $instances->map(
            fn($instance) =>
            $this->buildTicketDataFromInstance($instance, $email)
        )->toArray();

        $pdf = Pdf::loadView('pdf.boletos', [
            'boletos' => $boletos,
            'email' => $email,
        ])->setPaper([0, 0, 400, 700]);

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header(
                'Content-Disposition',
                'inline; filename="boletos.pdf"'
            );
    }

    private function buildTicketDataFromInstance(
        TicketInstance $instance,
        string $email
    ): array {
        return [
            'event' => [
                'name' => $instance->ticket->event->name ?? 'Box Azteca',
                'date' => '17 de Enero de 2026',
                'time' => '6:00 PM',
                'venue' => 'Centro de Convenciones Siglo XXI',
                'organizer' => 'Maxboxing',
            ],
            'ticket' => [
                'name' => $instance->ticket->name,
                'row' => $instance->ticket->row,
                'seat' => $instance->ticket->seat,
                'price' => $instance->ticket->total_price,
            ],
            'order' => [
                'payment_intent' => $instance->payment_intent_id
                    ?: $instance->reference,
                'purchased_at' => $instance->purchased_at,
            ],
            'user' => [
                'email' => $instance->email ?? $email,
            ],
            'qr' => asset('qrs/qr_' . md5(json_encode([
                'type' => 'ticket',
                'ticket_id' => $instance->ticket_id,
                'ticket_instance_id' => $instance->id,
                'hash' => $instance->qr_hash,
            ])) . '.png'),
            'wallet' => [
                'instance_id' => $instance->id,
            ],
        ];
    }
}
