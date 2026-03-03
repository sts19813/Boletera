<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;



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



}
