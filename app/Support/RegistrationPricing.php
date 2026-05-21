<?php

namespace App\Support;

use App\Models\Eventos;

class RegistrationPricing
{
    public static function resolveUnitPrice(Eventos $evento, int $qty, ?float $defaultPrice = null): float
    {
        return self::resolveBasePrice($evento, $defaultPrice);
    }

    public static function resolvePromotionMeta(Eventos $evento, int $qty, ?float $defaultPrice = null): ?array
    {
        return null;
    }

    private static function resolveBasePrice(Eventos $evento, ?float $defaultPrice = null): float
    {
        if ($defaultPrice !== null) {
            return round((float) $defaultPrice, 2);
        }

        return round((float) ($evento->price ?? 0), 2);
    }
}
