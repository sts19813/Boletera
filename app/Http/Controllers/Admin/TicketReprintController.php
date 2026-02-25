<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketInstance;
use App\Models\RegistrationInstance;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\TicketBuilderService;
use App\Services\RegistrationBuilderService;
use App\Models\Eventos;

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

    public function reprintAdmin(
        TicketInstance $instance,
        TicketBuilderService $builder
    ) {
        $ticket = $instance->ticket;
        $email = $instance->email ?? 'taquilla@local';
        $evento = Eventos::findOrFail($instance->event_id);

        $boleto = $builder->build(
            ticket: $ticket,
            instance: $instance,
            email: $email,
            purchasedAt: $instance->purchased_at,
            event: $evento,
            reference: $instance->reference
        );

        return Pdf::loadView('pdf.boletos', [
            'boletos' => [$boleto],
            'email' => $email,
        ])
            ->setPaper([0, 0, 400, 700])
            ->stream("boleto-{$instance->reference}.pdf");
    }


    public function reprintInscription(
        RegistrationInstance $instance,
        RegistrationBuilderService $builder
    ) {
        $evento = $instance->evento;
        $email = $instance->email ?? 'taquilla@local';

        $registro = $builder->build(
            evento: $evento,
            instance: $instance,
            email: $email
        );

        return Pdf::loadView('pdf.boletos', [
            'boletos' => [$registro],
            'email' => $email,
        ])
            ->setPaper([0, 0, 400, 700])
            ->stream("inscripcion-{$instance->id}.pdf");
    }
}
