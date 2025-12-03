<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;

use Stripe\Webhook;
use Stripe\PaymentIntent;



class PaymentController extends Controller
{
     public function checkout()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

  $carrito = [
            [
                'nombre' => 'Laptop HP',
                'precio' => 12500, // MXN
                'cantidad' => 1
            ],
            [
                'nombre' => 'Mouse Logitech',
                'precio' => 350,
                'cantidad' => 2
            ],
        ];

        $lineItems = [];
        $total = 0;

        // Agregar productos del carrito
        foreach ($carrito as $item) {
            $subtotal = $item['precio'] * $item['cantidad'];
            $total += $subtotal;

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'mxn',
                    'product_data' => [
                        'name' => $item['nombre'],
                    ],
                    'unit_amount' => $item['precio'] * 100, // convertir a centavos
                ],
                'quantity' => $item['cantidad'],
            ];
        }

        // =============================
        // CÁLCULO DE COMISIÓN STRIPE
        // (Para que recibas el total exacto)
        // =============================

        $comision = $this->calcularComision($total);

        // Agregar la comisión como un ítem extra
        $lineItems[] = [
            'price_data' => [
                'currency' => 'mxn',
                'product_data' => ['name' => 'Comisión por servicio'],
                'unit_amount' => round($comision * 100),
            ],
            'quantity' => 1,
        ];

        // =============================
        // CREAR CHECKOUT DE STRIPE
        // =============================

        $session = CheckoutSession::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => url('/pago/success'),
            'cancel_url' => url('/pago/cancel'),
        ]);
        return redirect($session->url);
    }

       // Función para calcular comisión de Stripe (3.6% + 3 MXN)
    private function calcularComision($monto)
    {
        $porcentaje = 0.036;
        $tarifaFija = 3;

        return (($monto + $tarifaFija) / (1 - $porcentaje)) - $monto;
    }

    public function success()
    {
        return "Pago exitoso!";
    }

    public function cancel()
    {
        return "Pago cancelado.";
    }

     public function formulario()
    {
        $carrito = [
            ['nombre' => 'Producto A', 'precio' => 20000, 'cantidad' => 1], // 200 MXN
            ['nombre' => 'Producto B', 'precio' => 15000, 'cantidad' => 2], // 150 MXN c/u
        ];

        // Calcular subtotal
        $subtotal = 0;
        foreach ($carrito as $item) {
            $subtotal += $item['precio'] * $item['cantidad'];
        }

        // Agregar comisión que tu deseas ganar (ejemplo: +$100 MXN)
        $comision = 10000; // 100.00 MXN

        $total = $subtotal + $comision;

        // Crear PaymentIntent
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        $intent = \Stripe\PaymentIntent::create([
            'amount' => $total,
            'currency' => 'mxn',
            'metadata' => [
                'carrito' => json_encode($carrito),
                'comision' => $comision,
            ],
        ]);

        return view('pago', [
            'clientSecret' => $intent->client_secret,
            'carrito' => $carrito,
            'subtotal' => $subtotal,
            'comision' => $comision,
            'total' => $total,
        ]);
    }



    public function procesarPago(Request $request)
    {
        return response()->json([
            'status' => 'success'
        ]);
    }


    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->server('HTTP_STRIPE_SIGNATURE');
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\Exception $e) {
            return response('Invalid signature', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            // Aquí puedes marcar el pedido como pagado
        }

        return response('Webhook handled', 200);
    }

}
