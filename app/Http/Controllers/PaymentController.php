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

        $subtotal = collect($carrito)->sum(fn($i) => $i['price'] * ($i['qty'] ?? 1));

        // comisión ejemplo
        $subtotal = collect($carrito)->sum(fn($i) => $i['price'] * ($i['qty'] ?? 1));

        // cargo solo informativo
        $comision = round($subtotal * 0.05, 2);

        // TOTAL SIN COMISIÓN
        $total = $subtotal;

        return view('pago.form', compact(
            'carrito',
            'subtotal',
            'comision',
            'total'
        ));
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

        $subtotal = collect($carrito)->sum(fn($i) => $i['price'] * ($i['qty'] ?? 1));

        // solo informativo
        $comision = round($subtotal * 0.05, 2);

        Stripe::setApiKey(config('services.stripe.secret'));
        // Stripe cobra SOLO el subtotal
        $total = $subtotal;

        $intent = PaymentIntent::create([
            'amount' => (int) round($total * 100),
            'currency' => 'mxn',
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'cart' => json_encode($carrito),
                'subtotal' => $subtotal,
                'comision' => $comision,
                'comision_aplicada' => false,
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


    private function generateBoletosPdf(array $boletos, string $email)
    {
        return Pdf::loadView('pdf.boletos', [
            'boletos' => $boletos,
            'email' => $email,
        ])->setPaper([0, 0, 380, 600])->output();

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

                $existingInstance = TicketInstance::where('payment_intent_id', $paymentIntentId)
                    ->where('ticket_id', $ticket->id)
                    ->first();

                if ($existingInstance) {
                    $boletos[] = $this->buildTicketData(
                        $ticket,
                        $existingInstance,
                        $existingInstance->email,
                        $existingInstance->purchased_at,
                        $paymentIntentId
                    );
                    continue;
                }

                $instance = TicketInstance::create([
                    'ticket_id' => $ticket->id,
                    'email' => $email,
                    'purchased_at' => $purchaseAt,
                    'qr_hash' => (string) Str::uuid(),
                    'payment_intent_id' => $paymentIntentId,
                    'reference' => $paymentIntentId,
                    'sale_channel' => 'stripe',
                    'payment_method' => 'card',
                ]);

                $ticket->update([
                    'stock' => 0,
                    'sold' => 1,
                    'status' => 'sold',
                    'purchased_at' => $purchaseAt,
                ]);

                $boletos[] = $this->buildTicketData(
                    $ticket,
                    $instance,
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
                    'ticket_id' => $ticket->id,
                    'email' => $email,
                    'purchased_at' => $purchaseAt,
                    'qr_hash' => (string) Str::uuid(),
                    'payment_intent_id' => $paymentIntentId,
                    'reference' => $paymentIntentId,
                    'sale_channel' => 'stripe',
                    'payment_method' => 'card',
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
            $ticket->decrement('stock', $qty);
            if ($ticket->stock <= 0) {
                $ticket->update([
                    'stock' => 0,
                    'status' => 'sold'
                ]);
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
                'name' => $ticket->event->name ?? 'Evento - Box Azteca',
                'date' => '17 de Enero de 2026',
                'time' => '6:00 PM',
                'venue' => 'Centro de Convenciones Siglo XXI',
                'organizer' => 'Maxboxing',
            ],
            'ticket' => [
                'name' => $ticket->name,
                'row' => $ticket->row ?? null,
                'seat' => $ticket->seat ?? null,
                'price' => $ticket->total_price,
            ],
            'order' => [
                'payment_intent' => $paymentIntentId,
                'purchased_at' => $purchasedAt,
            ],
            'user' => [
                'email' => $email,
            ],
            'qr' => $this->makeQr([
                'type' => 'ticket',
                'ticket_id' => $ticket->id,
                'ticket_instance_id' => $instance?->id,
                'hash' => $instance?->qr_hash,
            ], ),
            'wallet' => [
                'instance_id' => $instance?->id,
            ],
        ];
    }


    private function makeQr(array $payload): string
    {
        $filename = 'qr_' . md5(json_encode($payload)) . '.png';
        $dir = public_path('qrs');

        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . '/' . $filename;

        if (!file_exists($path)) {

            $result = Builder::create()
                ->writer(new PngWriter())
                ->data(json_encode($payload))
                ->size(220)
                ->margin(10)
                ->build();

            file_put_contents($path, $result->getString());
        }

        return asset('qrs/' . $filename);
    }

    public function reprint(Request $request)
    {
        $reference = $request->get('ref');
        $email = $request->get('email') ?: 'taquilla@local';

        if (!$reference) {
            abort(400, 'Referencia requerida');
        }

        // Buscar boletos EXISTENTES
        $instances = TicketInstance::where(function ($q) use ($reference) {
            $q->where('payment_intent_id', $reference)
                ->orWhere('reference', $reference);
        })->get();


        if ($instances->isEmpty()) {
            abort(404, 'Boletos no encontrados');
        }

        $boletos = $instances->map(
            fn($instance) =>
            $this->buildTicketDataFromInstance($instance, $email)
        )->toArray();

        $pdf = Pdf::loadView('pdf.boletos', [
            'boletos' => $boletos,
            'email' => $email,
        ])->setPaper([0, 0, 400, 700]);

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="boletos.pdf"');
    }

    private function buildTicketDataFromInstance(
        TicketInstance $instance,
        string $email
    ): array {
        return [
            'event' => [
                'name' => $instance->ticket->event->name ?? 'Box Azteca',
                'date' => '17 de Enero de 2026',
                'time' => '6:00 PM',
                'venue' => 'Centro de Convenciones Siglo XXI',
                'organizer' => 'Maxboxing',
            ],
            'ticket' => [
                'name' => $instance->ticket->name,
                'row' => $instance->ticket->row,
                'seat' => $instance->ticket->seat,
                'price' => $instance->ticket->total_price,
            ],
            'order' => [
                'payment_intent' => $instance->payment_intent_id
                    ?: $instance->reference,
                'purchased_at' => $instance->purchased_at,
            ],
            'user' => [
                'email' => $instance->email ?? $email,
            ],
            'qr' => asset('qrs/qr_' . md5(json_encode([
                'type' => 'ticket',
                'ticket_id' => $instance->ticket_id,
                'ticket_instance_id' => $instance->id,
                'hash' => $instance->qr_hash,
            ])) . '.png'),
            'wallet' => [
                'instance_id' => $instance->id,
            ],
        ];
    }

    public function cancel()
    {
        return view('pago.cancel');
    }
}
