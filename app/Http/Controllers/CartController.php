<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Illuminate\Support\Facades\Log;


class CartController extends Controller
{
    public function get(Request $request)
    {
        $cart = session('svg_cart', []);
        return response()->json(['cart' => $cart]);
    }

    public function add(Request $request)
    {
        // Recibir `cart` completo o un item simple
        if ($request->has('cart')) {
            session(['svg_cart' => $request->input('cart')]);
        } elseif ($request->has('id')) {
            $cart = session('svg_cart', []);
            $item = $request->only(['id','name','price','qty','selectorSVG']);
            $idx = collect($cart)->search(fn($i) => $i['id'] == $item['id']);
            if ($idx !== false) {
                $cart[$idx]['qty'] = ($cart[$idx]['qty'] ?? 1) + ($item['qty'] ?? 1);
            } else {
                $cart[] = array_merge(['qty' => 1], $item);
            }
            session(['svg_cart' => $cart]);
        }
        return response()->json(['success' => true]);
    }

    public function remove(Request $request)
    {
        $id = $request->input('id');
        $cart = session('svg_cart', []);
        $cart = array_values(array_filter($cart, function($i) use ($id) {
            return (string)$i['id'] !== (string)$id;
        }));
        session(['svg_cart' => $cart]);
        return response()->json(['success' => true]);
    }

    public function clear()
    {
        session()->forget('svg_cart');
        return response()->json(['success' => true]);
    }

    public function checkout(Request $request)
    {
        $cart = $request->input('cart', session('svg_cart', []));
        if (empty($cart)) {
            return response()->json(['success' => false, 'message' => 'Carrito vacío'], 400);
        }

        // --- Ejemplo con Stripe Checkout Sessions ---
        Stripe::setApiKey(config('services.stripe.secret'));

        $line_items = [];
        foreach ($cart as $item) {
            $price = max(0, floatval($item['price'] ?? 0));
            $line_items[] = [
                'price_data' => [
                    'currency' => 'mxn',
                    'product_data' => [
                        'name' => $item['name'] ?? ('Asiento ' . $item['id']),
                    ],
                    'unit_amount' => (int)round($price * 100),
                ],
                'quantity' => (int)($item['qty'] ?? 1),
            ];
        }

        try {
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => $line_items,
                'mode' => 'payment',
                'success_url' => url('/pago/success'),
                'cancel_url' => url()->current(),
                // puedes guardar metadata con ids de lotes
                'metadata' => ['cart' => json_encode($cart)],
            ]);

            // opcional: guardar orden en BD antes de redirigir
            // Order::create([...]);

            return response()->json(['success' => true, 'checkoutUrl' => $session->url]);
        } catch (\Exception $e) {
            \Log::error('Stripe error: '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'No se pudo crear la sesión de pago'], 500);
        }
    }
}
