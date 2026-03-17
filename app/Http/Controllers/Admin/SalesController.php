<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketInstance;

class SalesController extends Controller
{
    public function index()
    {
        $tickets = TicketInstance::ticketSales()
            ->with([
                'ticket',
                'ticket.stage',
                'evento',
            ])
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'ticket',
                    'event' => $item->evento?->name ?? $item->ticket?->event?->name,
                    'email' => $item->email,
                    'reference' => $item->payment_intent_id ?? $item->reference,
                    'date' => $item->purchased_at,
                    'status' => $item->used_at ? 'Usado' : 'Valido',
                    'total' => $item->total ?? $item->price ?? 0,
                    'instance' => $item,
                ];
            });

        $registrations = TicketInstance::registrationSales()
            ->with(['evento'])
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'registration',
                    'event' => $item->evento?->name,
                    'email' => $item->email,
                    'reference' => $item->payment_intent_id ?? $item->reference,
                    'date' => $item->purchased_at,
                    'status' => 'Inscripcion',
                    'total' => $item->total ?? $item->price ?? 0,
                    'instance' => $item,
                ];
            });

        $sales = $tickets
            ->concat($registrations)
            ->sortByDesc('date')
            ->values();

        return view('admin.sales.index', compact('sales'));
    }
}