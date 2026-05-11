<?php

namespace App\Http\Controllers;
use App\Models\Eventos;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function get(Request $request)
    {
        $cart = session('svg_cart', []);
        return response()->json([
            'cart' => $cart,
            'coupon_code' => session('coupon_code'),
        ]);
    }

    public function add(Request $request)
    {
        $eventId = $request->input('event_id')
            ?? data_get($request->input('cart', []), '0.event_id')
            ?? session('event_id');

        if ($eventId) {
            $evento = Eventos::find($eventId);

            if ($evento && $evento->stop_online_sales && !$this->canBypassOnlineStop()) {
                return response()->json([
                    'error' => 'La venta en línea está detenida para este evento.',
                ], 403);
            }
        }

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
                    'base_price' => (float) ($item['base_price'] ?? $item['price'] ?? 0),
                    'discount_percent' => array_key_exists('discount_percent', $item)
                        ? ($item['discount_percent'] !== null ? (float) $item['discount_percent'] : null)
                        : null,
                    'discount_amount' => (float) ($item['discount_amount'] ?? 0),
                    'coupon_code' => $item['coupon_code'] ?? null,
                    'coupon_id' => $item['coupon_id'] ?? null,
                    'qty' => (int) ($item['qty'] ?? 1),
                    'selectorSVG' => $item['selectorSVG'] ?? null,
                    'type' => $item['type'] ?? 'ticket', // 👈 CLAVE
                ];
            })->values()->toArray();

            session(['svg_cart' => $cart]);
            $couponCode = trim((string) $request->input('coupon_code', ''));

            if ($couponCode !== '') {
                session(['coupon_code' => strtoupper($couponCode)]);
            } else {
                session()->forget('coupon_code');
            }

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
                'base_price' => (float) ($request->base_price ?? $request->price),
                'discount_percent' => $request->discount_percent !== null
                    ? (float) $request->discount_percent
                    : null,
                'discount_amount' => (float) ($request->discount_amount ?? 0),
                'coupon_code' => $request->coupon_code,
                'coupon_id' => $request->coupon_id,
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
            $couponCode = trim((string) $request->input('coupon_code', ''));

            if ($couponCode !== '') {
                session(['coupon_code' => strtoupper($couponCode)]);
            } else {
                session()->forget('coupon_code');
            }

            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Nada que agregar'], 400);
    }

    private function canBypassOnlineStop(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return $user->hasRole('admin')
            || $user->hasRole('taquillero')
            || $user->can('vender boletos')
            || $user->can('genera cortesias');
    }


}
