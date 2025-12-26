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
            'cart' => 'required|array|min:1',
            'email' => 'nullable|email',
        ]);

        $reference = 'TAQ-' . now()->format('YmdHis');
        $boletos = [];

        foreach ($request->cart as $item) {

            $ticket = Ticket::lockForUpdate()->findOrFail($item['id']);
            $qty = max(1, (int) ($item['qty'] ?? 1));

            if ($ticket->stock < $qty) {
                abort(409, 'Stock insuficiente');
            }

            for ($i = 0; $i < $qty; $i++) {

                $instance = TicketInstance::create([
                        'ticket_id' => $ticket->id,
                        'email' => 'taquilla@local',
                        'purchased_at' => now(),
                        'qr_hash' => (string) Str::uuid(),
                        'reference' => $reference,
                        'sale_channel' => 'taquilla',
                        'payment_method' => 'cash',
                ]);

                $boletos[] = $this->ticketBuilder->build(
                    $ticket,
                    $instance,
                    $instance->email,
                    $instance->purchased_at,
                    $reference
                );
            }

            $ticket->increment('sold', $qty);
            $ticket->decrement('stock', $qty);

            if ($ticket->stock <= 0) {
                $ticket->update(['status' => 'sold']);
            }
        }

        return view('pago.success', [
            'boletos' => $boletos,
            'email' => $request->email ?? 'taquilla@local',
        ]);
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

