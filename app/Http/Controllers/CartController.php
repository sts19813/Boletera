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
        /**
         * =========================
         * EVENTO
         * =========================
         */
        if ($request->filled('event_id')) {
            session(['event_id' => $request->event_id]);
        }

        /**
         * =========================
         * FORMULARIO DE INSCRIPCIÓN
         * =========================
         */
        if ($request->filled('registration')) {
            session(['registration_form' => $request->registration]);
        }
        /**
         * =========================
         * CARRITO COMPLETO (NORMALIZADO)
         * =========================
         */
        if ($request->has('cart')) {

            $cart = collect($request->cart)->map(function ($item) {
                return [
                    'id' => $item['id'],
                    'event_id' => $item['event_id'] ?? null,
                    'name' => $item['name'] ?? '',
                    'price' => (float) $item['price'],
                    'qty' => (int) ($item['qty'] ?? 1),
                    'selectorSVG' => $item['selectorSVG'] ?? null,
                    'type' => $item['type'] ?? 'ticket', // 👈 CLAVE
                ];
            })->values()->toArray();

            session(['svg_cart' => $cart]);

            return response()->json(['success' => true]);
        }

        /**
         * =========================
         * ITEM INDIVIDUAL (LEGACY)
         * =========================
         */
        if ($request->has('id')) {

            $cart = session('svg_cart', []);

            $item = [
                'id' => $request->id,
                'event_id' => $request->event_id,
                'name' => $request->name,
                'price' => (float) $request->price,
                'qty' => (int) ($request->qty ?? 1),
                'selectorSVG' => $request->selectorSVG,
                'type' => $request->type ?? 'ticket', 
            ];

            $idx = collect($cart)->search(fn($i) => $i['id'] === $item['id']);

            if ($idx !== false) {
                $cart[$idx]['qty'] += $item['qty'];
            } else {
                $cart[] = $item;
            }

            session(['svg_cart' => $cart]);

            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Nada que agregar'], 400);
    }



    public function remove(Request $request)
    {
        $id = $request->input('id');
        $cart = session('svg_cart', []);
        $cart = array_values(array_filter($cart, function ($i) use ($id) {
            return (string) $i['id'] !== (string) $id;
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
                    'unit_amount' => (int) round($price * 100),
                ],
                'quantity' => (int) ($item['qty'] ?? 1),
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
            Log::error('Stripe error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'No se pudo crear la sesión de pago'], 500);
        }
    }
}
