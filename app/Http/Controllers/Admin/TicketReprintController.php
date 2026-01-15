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

    public function reprint(
        TicketInstance $instance,
        TicketBuilderService $builder
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

        return Pdf::loadView('pdf.boletos', [
            'boletos' => [$boleto],
            'email' => $email,
        ])
            ->setPaper([0, 0, 400, 700])
            ->stream("boleto-{$instance->reference}.pdf");
    }
}
