<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Eventos;
use App\Models\TicketInstance;
use App\Services\RegistrationBuilderService;
use App\Services\TicketBuilderService;
use Barryvdh\DomPDF\Facade\Pdf;

class TicketReprintController extends Controller
{
    public function index()
    {
        $instances = TicketInstance::ticketSales()
            ->with([
                'ticket',
                'evento',
            ])
            ->orderByDesc('purchased_at')
            ->get();

        return view('admin.ticket_instances.index', compact('instances'));
    }

    public function reprintAdmin(
        TicketInstance $instance,
        TicketBuilderService $builder
    ) {
        if ($instance->sale_type === 'registration') {
            abort(404, 'La instancia corresponde a una inscripcion');
        }

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
        TicketInstance $instance,
        RegistrationBuilderService $builder
    ) {
        if ($instance->sale_type !== 'registration') {
            abort(404, 'La instancia no corresponde a una inscripcion');
        }

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
