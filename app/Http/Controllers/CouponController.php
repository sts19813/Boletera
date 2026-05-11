<?php

namespace App\Http\Controllers;

use App\Models\Eventos;
use App\Services\CouponService;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(
        private CouponService $couponService
    ) {
    }

    public function validateForEvent(Request $request, Eventos $event)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'nullable|string',
            'cart.*.event_id' => 'nullable|string',
            'cart.*.type' => 'nullable|in:ticket,registration',
            'cart.*.qty' => 'nullable|integer|min:1|max:9999',
            'cart.*.price' => 'nullable|numeric|min:0',
            'cart.*.base_price' => 'nullable|numeric|min:0',
        ]);

        $result = $this->couponService->applyCouponToCart(
            $event,
            $validated['cart'],
            $validated['code'] ?? null
        );

        $subtotal = collect($result['cart'])->sum(function (array $item) {
            return ((float) ($item['price'] ?? 0)) * max(1, (int) ($item['qty'] ?? 1));
        });

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'coupon' => null,
                'cart' => $result['cart'],
                'subtotal' => round($subtotal, 2),
                'total' => round($subtotal, 2),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'coupon' => $result['coupon'],
            'cart' => $result['cart'],
            'subtotal' => round($subtotal, 2),
            'total' => round($subtotal, 2),
        ]);
    }
}
