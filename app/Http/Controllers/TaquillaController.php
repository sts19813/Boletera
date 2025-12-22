<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\Ticket;
use App\Models\TicketInstance;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\BoletosMail;
use App\Services\TicketBuilderService;

class TaquillaController extends Controller
{
    public function __construct(
        private TicketBuilderService $ticketBuilder
    ) {}
    public function index()
    {
        $tickets = Ticket::where('status', 'available')->get();
        return view('taquilla.index', compact('tickets'));
    }

    public function sell(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'payment_method' => 'required|in:efectivo,tarjeta',
            'email' => 'nullable|email',
        ]);

        $ticket = Ticket::lockForUpdate()->findOrFail($request->ticket_id);

        if ($ticket->stock <= 0) {
            abort(409, 'Asiento ya vendido');
        }

        $reference = 'TAQ-' . now()->format('YmdHis');

        $instance = TicketInstance::create([
            'ticket_id' => $ticket->id,
            'email' => $request->email,
            'purchased_at' => now(),
            'qr_hash' => (string) Str::uuid(),
            'sale_channel' => 'taquilla',
            'payment_method' => $request->payment_method,
            'reference' => $reference,
        ]);

        // Marcar ticket como vendido
        $ticket->update([
            'stock' => 0,
            'sold' => 1,
            'status' => 'sold',
            'purchased_at' => now(),
        ]);

        return redirect()
            ->route('taquilla.pdf', $instance)
            ->with('success', 'Boleto generado correctamente');
    }

    public function pdf(TicketInstance $instance)
    {
        $boleto = $this->ticketBuilder->build(
            $instance->ticket,
            $instance,
            $instance->email ?? 'taquilla@local',
            $instance->purchased_at,
            $instance->reference
        );

        return Pdf::loadView('pdf.boletos', [
            'boletos' => [$boleto],
            'email' => $instance->email,
        ])->download('boleto-taquilla.pdf');
    }
}

