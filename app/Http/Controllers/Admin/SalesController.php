<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketInstance;
use App\Models\RegistrationInstance;

class SalesController extends Controller
{
    public function index()
    {
        $tickets = TicketInstance::with([
            'ticket',
            'ticket.stage',
        ])->get()->map(function ($item) {

            return [
                'type' => 'ticket',
                'event' => $item->ticket?->event?->name,
                'email' => $item->email,
                'reference' => $item->payment_intent_id ?? $item->reference,
                'date' => $item->purchased_at,
                'status' => $item->used_at ? 'Usado' : 'Válido',
                'total' => $item->ticket?->price ?? 0,
                'instance' => $item
            ];
        });

        $registrations = RegistrationInstance::with([
            'evento',
            'registration'
        ])->get()->map(function ($item) {

            return [
                'type' => 'registration',
                'event' => $item->evento?->name,
                'email' => $item->email,
                'reference' => $item->reference,
                'date' => $item->registered_at,
                'status' => 'Inscripción',
                'total' => $item->registration?->total ?? 0,
                'instance' => $item
            ];
        });

        $sales = $tickets
            ->concat($registrations)
            ->sortByDesc('date')
            ->values();

        return view('admin.sales.index', compact('sales'));
    }
}