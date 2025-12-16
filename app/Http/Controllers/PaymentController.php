<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
        $email = $request->query('email', 'no-definido');

        $payload = [
            'email' => $email,
            'timestamp' => now()->toDateTimeString(),
        ];

        $qr = QrCode::size(250)
            ->format('svg')
            ->generate(json_encode($payload));

        return view('pago.success', [
            'qr' => $qr,
            'email' => $email,
        ]);
    }


    public function cancel()
    {
        return view('pago.cancel');
    }
}
