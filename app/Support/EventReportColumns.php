<?php

namespace App\Support;

class EventReportColumns
{
    /**
     * @return array<string, string>
     */
    public static function definitions(): array
    {
        return [
            'purchase_reference' => 'Compra/Transaccion',
            'event_name' => 'Evento',
            'sale_type' => 'Tipo de registro',
            'seat' => 'Asiento',
            'ticket_type' => 'Tipo de boleto',
            'coupon' => 'Cupon aplicado',
            'discount' => 'Descuento aplicado',
            'payment_method' => 'Metodo de pago',
            'total_paid' => 'Total pagado',
            'purchased_at' => 'Fecha de compra',
            'payment_status' => 'Estado del pago',
            'buyer_data' => 'Datos del comprador',
            'record_data' => 'Datos de registro',
            'items_count' => 'Elementos en compra',
        ];
    }

    /**
     * @return string[]
     */
    public static function defaultKeys(): array
    {
        return [
            'purchase_reference',
            'sale_type',
            'seat',
            'ticket_type',
            'coupon',
            'discount',
            'payment_method',
            'total_paid',
            'purchased_at',
            'payment_status',
            'buyer_data',
            'record_data',
            'items_count',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $settings
     * @return string[]
     */
    public static function normalizeKeys(?array $settings): array
    {
        $allowed = array_keys(self::definitions());
        $raw = $settings['columns'] ?? self::defaultKeys();

        if (!is_array($raw)) {
            return self::defaultKeys();
        }

        $columns = [];

        foreach ($raw as $key) {
            if (is_string($key) && in_array($key, $allowed, true) && !in_array($key, $columns, true)) {
                $columns[] = $key;
            }
        }

        return empty($columns) ? self::defaultKeys() : $columns;
    }

    /**
     * @param  array<string, mixed>|null  $settings
     * @return array<string, bool>
     */
    public static function enabledMap(?array $settings): array
    {
        $keys = self::normalizeKeys($settings);
        $enabled = array_fill_keys(array_keys(self::definitions()), false);

        foreach ($keys as $key) {
            $enabled[$key] = true;
        }

        return $enabled;
    }

    /**
     * @param  array<string, mixed>|null  $settings
     * @return array<string, int>
     */
    public static function orderMap(?array $settings): array
    {
        $normalized = self::normalizeKeys($settings);
        $orders = [];

        foreach (array_keys(self::definitions()) as $index => $key) {
            $position = array_search($key, $normalized, true);
            $orders[$key] = $position === false ? ($index + 1) : ($position + 1);
        }

        return $orders;
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    public static function fromInput(array $inputs): array
    {
        $definitions = self::definitions();
        $rows = [];

        foreach ($inputs as $key => $value) {
            if (!is_string($key) || !array_key_exists($key, $definitions) || !is_array($value)) {
                continue;
            }

            $enabled = (bool) ($value['enabled'] ?? false);
            $order = isset($value['order']) ? (int) $value['order'] : 999;

            if ($enabled) {
                $rows[] = [
                    'key' => $key,
                    'order' => $order,
                ];
            }
        }

        usort($rows, function (array $a, array $b) {
            if ($a['order'] === $b['order']) {
                return strcmp($a['key'], $b['key']);
            }

            return $a['order'] <=> $b['order'];
        });

        $columns = array_values(array_map(fn(array $row) => $row['key'], $rows));

        if (empty($columns)) {
            $columns = self::defaultKeys();
        }

        return [
            'columns' => $columns,
        ];
    }
}
