<?php

namespace App\Support;

use App\Models\Eventos;

class RegistrationPricing
{
    public const PROMO_EVENT_ID = '019c91a4-9f3b-7039-93cc-83f50c44c835';
    public const PROMO_MIN_QTY = 2;
    public const PROMO_PRICE = 1500.00;

    public static function resolveUnitPrice(Eventos $evento, int $qty, ?float $defaultPrice = null): float
    {
        $basePrice = self::resolveBasePrice($evento, $defaultPrice);

        if ((string) $evento->id === self::PROMO_EVENT_ID && $qty >= self::PROMO_MIN_QTY) {
            return self::PROMO_PRICE;
        }

        return $basePrice;
    }

    public static function resolvePromotionMeta(Eventos $evento, int $qty, ?float $defaultPrice = null): ?array
    {
        $basePrice = self::resolveBasePrice($evento, $defaultPrice);

        if ((string) $evento->id !== self::PROMO_EVENT_ID || $qty < self::PROMO_MIN_QTY) {
            return null;
        }

        return [
            'applied' => true,
            'type' => 'registration_qty_discount',
            'label' => 'Promocion aplicada: 2 o mas registros a $1,500 c/u.',
            'original_price' => $basePrice,
            'discounted_price' => self::PROMO_PRICE,
            'min_qty' => self::PROMO_MIN_QTY,
        ];
    }

    private static function resolveBasePrice(Eventos $evento, ?float $defaultPrice = null): float
    {
        if ($defaultPrice !== null) {
            return round((float) $defaultPrice, 2);
        }

        return round((float) ($evento->price ?? 0), 2);
    }
}
