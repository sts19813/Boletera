<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Eventos;
use App\Models\TicketInstance;
use App\Services\QueueMailTaskService;
use App\Services\RegistrationBuilderService;
use App\Services\TicketBuilderService;

class TicketResendController extends Controller
{
    public function __construct(
        private TicketBuilderService $ticketBuilder,
        private RegistrationBuilderService $registrationBuilder,
        private QueueMailTaskService $queueMailTaskService
    ) {
    }

    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = strtolower(trim((string) $request->email));

        $instances = TicketInstance::with(['ticket', 'evento'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->orderBy('purchased_at')
            ->get();

        if ($instances->isEmpty()) {
            return back()->withErrors([
                'email' => 'No se encontraron boletos con ese correo'
            ]);
        }

        $boletos = $instances
            ->map(function (TicketInstance $instance) use ($email) {
                $evento = $instance->evento ?? Eventos::findOrFail($instance->event_id);

                if ($instance->sale_type === 'registration') {
                    return $this->registrationBuilder->build(
                        $evento,
                        $instance,
                        $instance->email ?? $email
                    );
                }

                if (!$instance->ticket) {
                    return null;
                }

                return $this->ticketBuilder->build(
                    $instance->ticket,
                    $instance,
                    $instance->email ?? $email,
                    $instance->purchased_at,
                    $evento,
                    $instance->payment_intent_id ?? $instance->reference ?? 'N/A'
                );
            })
            ->filter()
            ->values()
            ->all();

        if (empty($boletos)) {
            return back()->withErrors([
                'email' => 'No se encontraron boletos validos para reenviar'
            ]);
        }

        $reference = (string) ($instances->first()?->payment_intent_id ?? $instances->first()?->reference ?? '');
        $this->queueMailTaskService->queueBoletos(
            recipient: $email,
            boletos: $boletos,
            type: 'boletos_resend',
            reference: $reference !== '' ? $reference : null,
            subject: '🎟️ Reenvío de boletos'
        );

        return back()->with('success', 'Boletos reenviados a tu correo');
    }
}
