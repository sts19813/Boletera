<?php

namespace App\Services;

use App\Models\EventCoupon;
use App\Models\Eventos;

class CouponService
{
    public function hasAvailableCoupons(Eventos $event): bool
    {
        return EventCoupon::query()
            ->where('event_id', $event->id)
            ->where('is_active', true)
            ->availableAt()
            ->exists();
    }

    public function applyCouponToCart(Eventos $event, array $cart, ?string $couponCode): array
    {
        $normalizedCart = collect($cart)->map(function (array $item) {
            $basePrice = $this->normalizeMoney((float) ($item['base_price'] ?? $item['price'] ?? 0));

            return array_merge($item, [
                'base_price' => $basePrice,
                'price' => $basePrice,
                'discount_percent' => null,
                'discount_amount' => 0,
            ]);
        })->values()->toArray();

        $qty = collect($normalizedCart)->sum(fn(array $item) => max(1, (int) ($item['qty'] ?? 1)));
        $resolved = $this->resolveCoupon($event, $couponCode, $qty);

        if ($resolved['error']) {
            return [
                'success' => false,
                'message' => $resolved['error'],
                'coupon' => null,
                'cart' => $normalizedCart,
            ];
        }

        $coupon = $resolved['coupon'];

        if (!$coupon) {
            return [
                'success' => true,
                'message' => null,
                'coupon' => null,
                'cart' => $normalizedCart,
            ];
        }

        $pricedCart = collect($normalizedCart)->map(function (array $item) use ($coupon) {
            $pricing = $this->calculateDiscount($item['base_price'], $coupon);
            $item['price'] = $pricing['price'];
            $item['discount_percent'] = $pricing['discount_percent'];
            $item['discount_amount'] = $pricing['discount_amount'];
            $item['coupon_code'] = $coupon->code;
            $item['coupon_id'] = $coupon->id;

            return $item;
        })->values()->toArray();

        return [
            'success' => true,
            'message' => null,
            'coupon' => $this->couponPayload($coupon),
            'cart' => $pricedCart,
        ];
    }

    public function resolveCoupon(Eventos $event, ?string $couponCode, int $qty): array
    {
        $code = strtoupper(trim((string) $couponCode));

        if ($code === '') {
            return ['coupon' => null, 'error' => null];
        }

        $coupon = EventCoupon::query()
            ->where('event_id', $event->id)
            ->whereRaw('UPPER(code) = ?', [$code])
            ->first();

        if (!$coupon || !$coupon->is_active) {
            return ['coupon' => null, 'error' => 'Cupón no válido para este evento.'];
        }

        $now = now();

        if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
            return ['coupon' => null, 'error' => 'El cupón aún no está disponible.'];
        }

        if ($coupon->ends_at && $now->gt($coupon->ends_at)) {
            return ['coupon' => null, 'error' => 'El cupón ya expiró.'];
        }

        if ($coupon->max_tickets && $qty > (int) $coupon->max_tickets) {
            return ['coupon' => null, 'error' => 'El cupón no aplica para esa cantidad de boletos.'];
        }

        return ['coupon' => $coupon, 'error' => null];
    }

    public function couponPayload(EventCoupon $coupon): array
    {
        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $this->normalizeMoney((float) $coupon->discount_value),
            'max_tickets' => $coupon->max_tickets,
            'starts_at' => $coupon->starts_at?->toDateTimeString(),
            'ends_at' => $coupon->ends_at?->toDateTimeString(),
        ];
    }

    private function calculateDiscount(float $basePrice, EventCoupon $coupon): array
    {
        $basePrice = $this->normalizeMoney($basePrice);

        if ($coupon->discount_type === 'percentage') {
            $percent = $this->normalizeMoney((float) $coupon->discount_value);
            $discount = $this->normalizeMoney(($basePrice * $percent) / 100);

            return [
                'price' => max(0, $this->normalizeMoney($basePrice - $discount)),
                'discount_percent' => $percent,
                'discount_amount' => $discount,
            ];
        }

        $discount = min($basePrice, $this->normalizeMoney((float) $coupon->discount_value));

        return [
            'price' => max(0, $this->normalizeMoney($basePrice - $discount)),
            'discount_percent' => null,
            'discount_amount' => $discount,
        ];
    }

    private function normalizeMoney(float $amount): float
    {
        return round(max(0, $amount), 2);
    }
}
