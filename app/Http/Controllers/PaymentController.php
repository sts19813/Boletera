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


class PaymentController extends Controller
{
    /**
     * Vista de pago (DOMINIO PROPIO)
     */
    public function formulario(Request $request)
    {
        // Normalmente esto vendrá del carrito en sesión
        $carrito = session('svg_cart', []);

        if (empty($carrito)) {
            abort(404, 'Carrito vacío');
        }

        $subtotal = collect($carrito)->sum(fn ($i) => $i['price'] * ($i['qty'] ?? 1));

        // comisión ejemplo
        $comision = round($subtotal * 0.05, 2);

        $total = $subtotal + $comision;

        return view('pago.form', [
            'carrito' => $carrito,
            'subtotal' => $subtotal,
            'comision' => $comision,
            'total' => $total
        ]);
    }

    /**
     * Crear PaymentIntent
     */
    public function crearIntent(Request $request)
    {
        $carrito = session('svg_cart', []);

        if (empty($carrito)) {
            return response()->json(['error' => 'Carrito vacío'], 400);
        }

        $subtotal = collect($carrito)->sum(fn ($i) => $i['price'] * ($i['qty'] ?? 1));
        $comision = round($subtotal * 0.05, 2);
        $total = $subtotal + $comision;

        Stripe::setApiKey(config('services.stripe.secret'));

        $intent = PaymentIntent::create([
            'amount' => (int) round($total * 100),
            'currency' => 'mxn',
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'cart' => json_encode($carrito),
                'subtotal' => $subtotal,
                'comision' => $comision
            ],
        ]);

        return response()->json([
            'clientSecret' => $intent->client_secret
        ]);
    }


   

public function success(Request $request)
{
    $paymentIntentId = $request->query('pi');
    $email = $request->query('email');

    if (!$paymentIntentId) {
        abort(400, 'Pago inválido');
    }

    // 1️⃣ Generar boletos (YA LO TIENES)
    $boletos = $this->generateBoletosFromPaymentIntent(
        $paymentIntentId,
        $email
    );

    // 2️⃣ Generar PDF
    $pdfContent = $this->generateBoletosPdf($boletos, $email);

    // 3️⃣ Enviar correo (solo una vez)
    Mail::to($email)->send(
        new BoletosMail($pdfContent)
    );

    // 4️⃣ Mostrar vista
    return view('pago.success', compact('boletos', 'email'));
}





public function downloadPdf(Request $request)
{
    $paymentIntentId = $request->get('pi');
    $email = $request->get('email');

    $boletos = $this->generateBoletosFromPaymentIntent(
        $paymentIntentId,
        $email
    );

    $pdf = Pdf::loadView('pdf.boletos', [
        'boletos' => $boletos,
        'email'   => $email,
    ])->setPaper('A4');

    return $pdf->download("boletos-{$paymentIntentId}.pdf");
}

public function resendBoletos(Request $request)
{
    $paymentIntentId = $request->get('pi');
    $email = $request->get('email');

    $boletos = $this->generateBoletosFromPaymentIntent(
        $paymentIntentId,
        $email
    );

    $pdf = Pdf::loadView('pdf.boletos', compact('boletos'))->output();

    Mail::to($email)->send(new BoletosMail($pdf));

    return response()->json(['ok' => true]);
}


private function generateBoletosPdf(array $boletos, string $email)
{
    return Pdf::loadView('pdf.boletos', [
        'boletos' => $boletos,
        'email'   => $email,
    ])->output();
}



    private function generateBoletosFromPaymentIntent(string $paymentIntentId, string $email): array
{
    Stripe::setApiKey(config('services.stripe.secret'));

    $intent = PaymentIntent::retrieve($paymentIntentId);

    if ($intent->status !== 'succeeded') {
        abort(403, 'Pago no confirmado');
    }

    $cart = json_decode($intent->metadata->cart ?? '[]', true);

    if (empty($cart)) {
        abort(400, 'Carrito vacío');
    }

    $purchaseAt = now();
    $purchaseAtString = $purchaseAt->toDateTimeString();

    $boletos = [];

    foreach ($cart as $item) {

        $ticket = Ticket::findOrFail($item['id']);
        $qty = max(1, (int) ($item['qty'] ?? 1));

        if ($ticket->stock == 1) {

            if ($ticket->status === 'sold') {

                $boletos[] = $this->buildTicketData(
                    $ticket,
                    null,
                    $email,
                    optional($ticket->purchased_at)->toDateTimeString(),
                    $paymentIntentId
                );

                continue;
            }

            $ticket->update([
                'status'       => 'sold',
                'purchased_at' => $purchaseAt,
            ]);

            $boletos[] = $this->buildTicketData(
                $ticket,
                null,
                $email,
                $purchaseAtString,
                $paymentIntentId
            );

            continue;
        }

        $existingInstances = TicketInstance::where('payment_intent_id', $paymentIntentId)
            ->where('ticket_id', $ticket->id)
            ->get();

        if ($existingInstances->isNotEmpty()) {

            foreach ($existingInstances as $instance) {

                $boletos[] = $this->buildTicketData(
                    $ticket,
                    $instance,
                    $instance->email,
                    $instance->purchased_at,
                    $paymentIntentId
                );
            }

            continue;
        }

        for ($i = 0; $i < $qty; $i++) {

            $instance = TicketInstance::create([
                'ticket_id'         => $ticket->id,
                'email'             => $email,
                'purchased_at'      => $purchaseAt,
                'qr_hash'           => (string) Str::uuid(),
                'payment_intent_id' => $paymentIntentId,
            ]);

            $boletos[] = $this->buildTicketData(
                $ticket,
                $instance,
                $email,
                $purchaseAtString,
                $paymentIntentId
            );
        }

        $ticket->increment('sold', $qty);

        if ($ticket->sold >= $ticket->stock) {
            $ticket->update(['status' => 'sold']);
        }
    }

    return $boletos;
}

private function buildTicketData(
    Ticket $ticket,
    ?TicketInstance $instance,
    string $email,
    string $purchasedAt,
    string $paymentIntentId
) {
    return [
        'event' => [
            'name'       => $ticket->event->name ?? 'Evento - Box Azteca',
            'date'      => '17 de enero de 2026',
            'time'      => '7:00 PM',
            'venue'     => 'Centro de Convenciones Siglo XXI',
            'organizer' => 'Maxboxing',
        ],
        'ticket' => [
            'name'   => $ticket->name,
            'row'    => $ticket->row ?? null,
            'seat'   => $ticket->seat ?? null,
            'price'  => $ticket->total_price,
        ],
        'order' => [
            'payment_intent' => $paymentIntentId,
            'purchased_at'   => $purchasedAt,
        ],
        'user' => [
            'email' => $email,
        ],
        'qr' => $this->makeQr([
            'type' => 'ticket',
            'ticket_id' => $ticket->id,
            'ticket_instance_id' => $instance?->id,
            'hash' => $instance?->qr_hash,
        ]),
    ];
}


private function makeQr(array $payload): string
{
    $filename = 'qr_' . md5(json_encode($payload)) . '.png';
    $path = 'qrs/' . $filename;

    if (!Storage::disk('public')->exists($path)) {

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data(json_encode($payload))
            ->size(220)
            ->margin(10)
            ->build();

        Storage::disk('public')->put($path, $result->getString());
    }

    return asset('storage/' . $path);
}



    public function cancel()
    {
        return view('pago.cancel');
    }
}
