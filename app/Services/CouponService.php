<?php

namespace App\Services;

use App\Models\EventCoupon;
use App\Models\Eventos;
use App\Models\TicketInstance;

/**
 * Servicio encargado de toda la lógica relacionada con cupones de eventos.
 *
 * Responsabilidades:
 * - Verificar disponibilidad de cupones.
 * - Validar si un cupón puede aplicarse.
 * - Aplicar descuentos al carrito.
 * - Calcular descuentos porcentuales o fijos.
 * - Normalizar montos monetarios para evitar inconsistencias.
 *
 * Notas importantes:
 * - Todos los precios se normalizan a 2 decimales.
 * - El servicio asume que los cupones pertenecen a un único evento.
 * - Actualmente los descuentos se aplican a todos los items del carrito.
 * - El tipo de descuento soportado es:
 *      - percentage
 *      - fixed amount
 *
 * Posibles extensiones futuras:
 * - Límites de uso global.
 * - Límites por usuario.
 * - Cupones combinables.
 * - Descuentos por ticket específico.
 */
class CouponService
{

    /**
     * Verifica si un evento tiene cupones activos y disponibles.
     *
     * Se utiliza normalmente para mostrar u ocultar el input
     * del cupon y botones para el frontend.
     *
     * Condiciones:
     * - El cupón debe pertenecer al evento.
     * - Debe estar activo.
     * - Debe estar disponible según fechas configuradas.
     *
     * @param Eventos $event Evento a validar.
     *
     * @return bool True si existe al menos un cupón disponible.
     */
    public function hasAvailableCoupons(Eventos $event): bool
    {
        return EventCoupon::query()
            ->where('event_id', $event->id)
            ->where('is_active', true)
            ->availableAt()
            ->exists();
    }



    /**
     * Aplica un cupón a todos los elementos del carrito.
     *
     * Flujo:
     * 1. Normaliza precios y estructura del carrito.
     * 2. Calcula cantidad total de tickets.
     * 3. Valida el cupón.
     * 4. Si el cupón es válido:
     *      - Calcula descuentos.
     *      - Actualiza precios finales.
     *      - Agrega metadata del cupón.
     *
     * Cada item del carrito termina conteniendo:
     * - base_price
     * - price
     * - discount_percent
     * - discount_amount
     * - coupon_code
     * - coupon_id
     *
     * Estructura esperada del carrito:
     * [
     *     [
     *         'price' => 100,
     *         'qty' => 2
     *     ]
     * ]
     *
     * @param Eventos $event Evento actual.
     * @param array $cart Carrito de compra.
     * @param string|null $couponCode Código del cupón.
     *
     * @return array Resultado de la operación.
     */
    public function applyCouponToCart(Eventos $event, array $cart, ?string $couponCode): array
    {
        $normalizedCart = collect($cart)->map(function (array $item) {

            /**
             * Se toma base_price si existe,
             * en caso contrario se usa price.
             */
            $basePrice = $this->normalizeMoney((float) ($item['base_price'] ?? $item['price'] ?? 0));

            return array_merge($item, [
                'base_price' => $basePrice,
                'price' => $basePrice,
                'discount_percent' => null,
                'discount_amount' => 0,
            ]);
        })->values()->toArray();

        /**
         * Cantidad total de boletos en el carrito.
         * Se usa para validar restricciones del cupón.
         */
        $qty = collect($normalizedCart)
            ->sum(fn(array $item) => max(1, (int) ($item['qty'] ?? 1)));
        $resolved = $this->resolveCoupon($event, $couponCode, $qty);

        /**
         * Si el cupón no es válido,
         * se retorna el carrito sin modificaciones.
         */
        if ($resolved['error']) {
            return [
                'success' => false,
                'message' => $resolved['error'],
                'coupon' => null,
                'cart' => $normalizedCart,
            ];
        }

        $coupon = $resolved['coupon'];

        /**
         * Si no se proporcionó cupón,
         * simplemente se retorna el carrito normalizado.
         */
        if (!$coupon) {
            return [
                'success' => true,
                'message' => null,
                'coupon' => null,
                'cart' => $normalizedCart,
            ];
        }

        /**
         * Aplicación del descuento item por item.
         */
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


    /**
     * Resuelve y valida un cupón para un evento.
     *
     * Validaciones realizadas:
     * - Código vacío.
     * - Existencia del cupón.
     * - Estado activo.
     * - Fecha de inicio.
     * - Fecha de expiración.
     * - Límite máximo de tickets.
     *
     * NOTA:
     * Este método NO verifica límites de uso globales
     * ni límites por usuario.
     *
     * @param Eventos $event Evento actual.
     * @param string|null $couponCode Código ingresado.
     * @param int $qty Cantidad total de tickets.
     *
     * @return array
     * [
     *     'coupon' => ?EventCoupon,
     *     'error' => ?string
     * ]
     */
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


        /** ### Validación de límite máximo de tickets por evento ###
         * Total de boletos vendidos históricamente
         * usando este cupón.
         */
        $soldTicketsWithCoupon = TicketInstance::query()
            ->where('coupon_id', $coupon->id)
            ->ticketSales()
            ->count();

        /**
         * Total que habría después de esta compra.
         */
        $totalAfterPurchase = $soldTicketsWithCoupon + $qty;

        /**
         * Validación del límite global del cupón.
         */
        if (
            $coupon->max_tickets &&
            $totalAfterPurchase > (int) $coupon->max_tickets
        ) {
            $remaining = max(
                0,
                (int) $coupon->max_tickets - $soldTicketsWithCoupon
            );

            return [
                'coupon' => null,
                'error' => $remaining > 0
                    ? "Solo quedan {$remaining} boletos disponibles para este cupón."
                    : 'Este cupón ya alcanzó su límite máximo de uso.'
            ];
        }

        return ['coupon' => $coupon, 'error' => null];
    }


    /**
     * Convierte un modelo EventCoupon
     * en una estructura limpia para frontend/API.
     *
     * Se evita retornar el modelo completo
     * para reducir acoplamiento y exposición innecesaria.
     *
     * @param EventCoupon $coupon Cupón validado.
     *
     * @return array Datos serializados del cupón.
     */
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


    /**
     * Calcula el descuento aplicable a un precio base.
     *
     * Tipos soportados:
     * - percentage
     * - fixed amount
     *
     * Reglas:
     * - El precio final nunca puede ser negativo.
     * - El descuento nunca puede superar el precio base.
     *
     * @param float $basePrice Precio original.
     * @param EventCoupon $coupon Cupón a aplicar.
     *
     * @return array
     * [
     *     'price' => float,
     *     'discount_percent' => ?float,
     *     'discount_amount' => float
     * ]
     */
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


    /**
     * Normaliza montos monetarios.
     *
     * Reglas:
     * - Nunca retorna valores negativos.
     * - Redondea siempre a 2 decimales.
     *
     * Ejemplos:
     * 15.999 => 16.00
     * -50 => 0
     *
     * @param float $amount Monto original.
     *
     * @return float Monto normalizado.
     */
    private function normalizeMoney(float $amount): float
    {
        return round(max(0, $amount), 2);
    }
}
