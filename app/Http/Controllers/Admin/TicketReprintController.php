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

    public function print(Request $request)
    {
        $reference = $request->get('ref');

        abort_if(!$reference, 400);

        $pdf = route('boletos.reprint', ['ref' => $reference]);

        return view('boletos.print', compact('pdf'));
    }


    public function reprint(
        TicketInstance $instance,
        TicketBuilderService $builder,
        TicketPdfService $pdfService
    ) {
        $ticket = $instance->ticket;
        $email = $instance->email ?? 'taquilla@local';

        $boleto = $builder->build(
            ticket: $ticket,
            instance: $instance,
            email: $email,
            purchasedAt: $instance->purchased_at,
            reference: $instance->reference
        );

        $pdf = Pdf::loadView('pdf.boletos', [
            'boletos' => [$boleto],
            'email' => $email,
        ])->setPaper([0, 0, 380, 600]);

        return $pdf->download("boleto-{$instance->id}.pdf");
    }
}
