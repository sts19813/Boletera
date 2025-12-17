<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\Ticket;
use App\Models\TicketInstance;

use App\Mail\TicketsMailFromData;

class TicketResendController extends Controller
{
    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = $request->email;

        /**
         * Buscar boletos generales
         */
        $instances = TicketInstance::where('email', $email)->get();

        /**
         * Buscar boletos únicos
         */
        $singleTickets = Ticket::where('stock', 1)
            ->where('status', 'sold')
            ->where('purchased_at', '!=', null)
            ->get()
            ->filter(fn($t) => $t->buyer_email === $email);

        if ($instances->isEmpty() && $singleTickets->isEmpty()) {
            return back()->withErrors([
                'email' => 'No se encontraron boletos con ese correo'
            ]);
        }

        /**
         * Construir boletos
         */
        $boletos = [];

        foreach ($instances as $instance) {
            $boletos[] = app(PaymentController::class)
                ->buildTicketData(
                    $instance->ticket,
                    $instance,
                    $instance->email,
                    $instance->purchased_at,
                    $instance->payment_intent_id
                );
        }

        foreach ($singleTickets as $ticket) {
            $boletos[] = app(PaymentController::class)
                ->buildTicketData(
                    $ticket,
                    null,
                    $email,
                    $ticket->purchased_at,
                    'N/A'
                );
        }

        /**
         * Enviar correo
         */
        Mail::to($email)->send(
            new TicketsMailFromData($boletos)
        );

        return back()->with('success', 'Boletos reenviados a tu correo');
    }
}
