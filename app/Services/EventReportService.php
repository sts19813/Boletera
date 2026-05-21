<?php

namespace App\Services;

use App\Models\Eventos;
use App\Models\TicketInstance;
use App\Support\EventReportColumns;
use Illuminate\Support\Collection;

class EventReportService
{
    public function __construct(
        private RegistrationFormSchemaService $schemaService
    ) {
    }

    /**
     * @return array{
     *   columns: array<int, array{key: string, label: string}>,
     *   rows: array<int, array<string, mixed>>
     * }
     */
    public function build(Eventos $event): array
    {
        $columnKeys = EventReportColumns::normalizeKeys($event->report_settings);
        if (($event->allows_multiple_registrations ?? false) && !in_array('registrations_count', $columnKeys, true)) {
            $columnKeys[] = 'registrations_count';
        }
        $definitions = EventReportColumns::definitions();

        $columns = array_values(array_map(function (string $key) use ($definitions) {
            return [
                'key' => $key,
                'label' => $definitions[$key],
            ];
        }, $columnKeys));

        $instances = TicketInstance::query()
            ->with(['ticket:id,name,type', 'evento:id,name,registration_form_id', 'evento.registrationForm:id,schema'])
            ->where('event_id', $event->id)
            ->orderByDesc('purchased_at')
            ->orderByDesc('created_at')
            ->get();

        $rows = [];

        foreach ($this->groupByTransaction($instances) as $transactionInstances) {
            $rows = array_merge($rows, $this->buildRowsForTransaction($event, $transactionInstances, $columnKeys));
        }

        usort($rows, function (array $a, array $b) {
            return strcmp((string) ($b['_sort_date'] ?? ''), (string) ($a['_sort_date'] ?? ''));
        });

        return [
            'columns' => $columns,
            'rows' => array_values(array_map(function (array $row) {
                unset($row['_sort_date']);
                return $row;
            }, $rows)),
        ];
    }

    /**
     * @param  Collection<int, TicketInstance>  $instances
     * @return Collection<string, Collection<int, TicketInstance>>
     */
    private function groupByTransaction(Collection $instances): Collection
    {
        return $instances->groupBy(function (TicketInstance $instance) {
            return $this->transactionReference($instance);
        });
    }

    /**
     * @param  Collection<int, TicketInstance>  $instances
     * @param  string[]  $columnKeys
     * @return array<int, array<string, mixed>>
     */
    private function buildRowsForTransaction(Eventos $event, Collection $instances, array $columnKeys): array
    {
        /** @var TicketInstance $primary */
        $primary = $instances->first();
        $transactionRef = $this->transactionReference($primary);
        $ticketInstances = $instances->filter(fn(TicketInstance $instance) => $instance->sale_type !== 'registration')->values();
        $ticketEntries = $this->buildTicketEntries($ticketInstances);
        $registrationInstances = $instances->filter(fn(TicketInstance $instance) => $instance->sale_type === 'registration')->values();
        $registrationEntries = $this->buildRegistrationEntries($registrationInstances);
        $itemsCount = $instances->count();
        $ticketCount = $ticketInstances->count();
        $registrationsCount = $registrationInstances->count();
        $totalPaid = $instances->sum(function (TicketInstance $instance) {
            return (float) ($instance->total ?? $instance->price ?? 0);
        });

        $couponCodes = $instances->pluck('coupon_code')
            ->filter(fn($value) => is_string($value) && trim($value) !== '')
            ->unique()
            ->values()
            ->all();

        $discountValues = $instances->map(function (TicketInstance $instance) {
            $amount = (float) ($instance->coupon_discount_amount ?? 0);
            $percent = $instance->coupon_discount_percent;

            if ($amount <= 0 && (is_null($percent) || (float) $percent <= 0)) {
                return null;
            }

            $parts = [];

            if ($amount > 0) {
                $parts[] = '$' . number_format($amount, 2);
            }

            if (!is_null($percent) && (float) $percent > 0) {
                $parts[] = number_format((float) $percent, 2) . '%';
            }

            return implode(' | ', $parts);
        })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $purchaseDate = optional($primary->purchased_at)->format('d/m/Y H:i');
        $sortDate = optional($primary->purchased_at)?->toIso8601String() ?? '';
        $paymentMethod = $this->mapPaymentMethod((string) $primary->payment_method);
        $paymentStatus = $this->resolvePaymentStatus($instances);
        $buyerData = $this->resolveBuyerData($primary);
        /** @var TicketInstance|null $firstTicket */
        $firstTicket = $ticketInstances->first();

        $saleType = '-';
        $seat = '-';
        $ticketType = '-';
        $recordData = '-';

        if ($ticketCount > 0 && $registrationsCount === 0) {
            $saleType = 'Boleto';
            $ticketType = $ticketInstances
                ->map(fn(TicketInstance $instance) => (string) ($instance->ticket?->type ?? $instance->ticket?->name ?? '-'))
                ->filter(fn(string $value) => trim($value) !== '')
                ->unique()
                ->implode(', ');

            if ($ticketType === '') {
                $ticketType = '-';
            }

            if ($ticketCount === 1) {
                $seat = $firstTicket?->ticket?->name ?? '-';
                $recordData = trim(implode(' | ', array_filter([
                    $firstTicket?->nombre ? 'Nombre: ' . $firstTicket->nombre : null,
                    $firstTicket?->email ? 'Email: ' . $firstTicket->email : null,
                    $firstTicket?->celular ? 'Cel: ' . $firstTicket->celular : null,
                ]))) ?: '-';
            } else {
                $seat = 'Multiples (' . $ticketCount . ')';
                $recordData = 'Compra con ' . $ticketCount . ' boletos.';
            }
        } elseif ($ticketCount === 0 && $registrationsCount > 0) {
            $saleType = 'Registro';
            $seat = '-';
            $ticketType = 'Inscripcion';
            $recordData = !empty($registrationEntries)
                ? $this->formatRegistrationEntriesAsText($registrationEntries)
                : '-';
        } elseif ($ticketCount > 0 && $registrationsCount > 0) {
            $saleType = 'Mixta';
            $seat = 'Multiples (' . $ticketCount . ')';
            $ticketType = 'Boletos + Inscripcion';
            $recordData = !empty($registrationEntries)
                ? $this->formatRegistrationEntriesAsText($registrationEntries)
                : 'Compra con boletos e inscripciones.';
        }

        $searchBlob = implode(' | ', array_filter([
            $event->name,
            $transactionRef,
            (string) ($primary->reference ?? ''),
            $buyerData,
            $saleType,
            $ticketType,
            $seat,
            $recordData,
        ]));

        $row = [
            'instance_id' => (string) $primary->id,
            'reference' => $primary->reference,
            'raw_sale_type' => $ticketCount > 0 ? 'ticket' : 'registration',
            '_sort_date' => $sortDate,
            'purchase_reference' => $transactionRef,
            'event_name' => $event->name,
            'sale_type' => $saleType,
            'seat' => $seat,
            'ticket_type' => $ticketType,
            'coupon' => empty($couponCodes) ? '-' : implode(', ', $couponCodes),
            'discount' => empty($discountValues) ? '-' : implode(', ', $discountValues),
            'payment_method' => $paymentMethod,
            'total_paid' => '$' . number_format($totalPaid, 2),
            'purchased_at' => $purchaseDate ?? '-',
            'payment_status' => $paymentStatus,
            'buyer_data' => $buyerData,
            'record_data' => $recordData,
            'search_blob' => $searchBlob,
            'registrations_count' => ($event->allows_multiple_registrations ?? false) && $registrationsCount > 0
                ? $registrationsCount
                : '-',
            'items_count' => $itemsCount,
        ];

        $visibleData = array_intersect_key(
            $row,
            array_flip(array_merge(['_sort_date'], $columnKeys))
        );

        return [array_merge($visibleData, [
            'instance_id' => $row['instance_id'],
            'reference' => $row['reference'],
            'raw_sale_type' => $row['raw_sale_type'],
            'ticket_entries' => $ticketEntries,
            'registration_entries' => $registrationEntries,
            'search_blob' => $row['search_blob'],
        ])];
    }

    /**
     * @param  Collection<int, TicketInstance>  $instances
     * @param  array<int, array{instance_id: string, title: string, fields: array<int, array{label: string, value: string}>}>  $registrationEntries
     * @return array<int, array{sale_type: string, seat: string, ticket_type: string, record_data: string, instance_id?: string}>
     */
    private function buildDetailRows(Collection $instances, array $registrationEntries): array
    {
        $rows = [];

        $ticketInstances = $instances->filter(fn(TicketInstance $instance) => $instance->sale_type !== 'registration');
        foreach ($ticketInstances as $instance) {
            $rows[] = [
                'instance_id' => $instance->id,
                'sale_type' => 'Boleto',
                'seat' => $instance->ticket?->name ?? '-',
                'ticket_type' => $instance->ticket?->type ?? ($instance->ticket?->name ?? '-'),
                'record_data' => trim(implode(' | ', array_filter([
                    $instance->nombre ? 'Nombre: ' . $instance->nombre : null,
                    $instance->email ? 'Email: ' . $instance->email : null,
                    $instance->celular ? 'Cel: ' . $instance->celular : null,
                ]))) ?: '-',
            ];
        }

        $registrationInstances = $instances->filter(fn(TicketInstance $instance) => $instance->sale_type === 'registration');
        if (!empty($registrationEntries)) {
            $rows[] = [
                'sale_type' => 'Registro',
                'seat' => '-',
                'ticket_type' => 'Inscripcion',
                'record_data' => $this->formatRegistrationEntriesAsText($registrationEntries),
            ];
        } elseif ($registrationInstances->isNotEmpty()) {
            /** @var TicketInstance $fallback */
            $fallback = $registrationInstances->first();
            $rows[] = [
                'sale_type' => 'Registro',
                'seat' => '-',
                'ticket_type' => 'Inscripcion',
                'record_data' => trim(implode(' | ', array_filter([
                    $fallback->nombre ? 'Nombre: ' . $fallback->nombre : null,
                    $fallback->email ? 'Email: ' . $fallback->email : null,
                    $fallback->celular ? 'Cel: ' . $fallback->celular : null,
                ]))) ?: '-',
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, TicketInstance>  $registrationInstances
     * @return array<int, array{instance_id: string, title: string, fields: array<int, array{label: string, value: string}>}>
     */
    private function buildRegistrationEntries(Collection $registrationInstances): array
    {
        if ($registrationInstances->isEmpty()) {
            return [];
        }

        /** @var TicketInstance $first */
        $first = $registrationInstances->first();
        $labelMap = $this->resolveLabelMap($first);
        $firstFormData = is_array($first->form_data) ? $first->form_data : [];
        $instanceList = $registrationInstances->values();

        if (isset($firstFormData['registrations']) && is_array($firstFormData['registrations'])) {
            return $this->buildIndexedEntries(
                $firstFormData['registrations'],
                $instanceList,
                $labelMap,
                'registrations',
                'Registro'
            );
        }

        if (isset($firstFormData['participants']) && is_array($firstFormData['participants'])) {
            return $this->buildIndexedEntries(
                $firstFormData['participants'],
                $instanceList,
                $labelMap,
                'participants',
                'Participante'
            );
        }

        if (isset($firstFormData['players']) && is_array($firstFormData['players'])) {
            return $this->buildIndexedEntries(
                $firstFormData['players'],
                $instanceList,
                $labelMap,
                'players',
                'Jugador'
            );
        }

        $entries = [];
        foreach ($instanceList as $index => $instance) {
            $formData = is_array($instance->form_data) ? $instance->form_data : [];
            $fields = $this->collectFieldsFromPayload($formData, $labelMap);

            if (empty($fields)) {
                $fields = $this->fallbackInstanceFields($instance);
            }

            $entries[] = [
                'instance_id' => $instance->id,
                'title' => 'Registro ' . ($index + 1),
                'fields' => $fields,
            ];
        }

        return $entries;
    }

    /**
     * @param  array<int, mixed>  $items
     * @param  Collection<int, TicketInstance>  $instances
     * @param  array<string, string>  $labelMap
     * @return array<int, array{instance_id: string, title: string, fields: array<int, array{label: string, value: string}>}>
     */
    private function buildIndexedEntries(array $items, Collection $instances, array $labelMap, string $rootKey, string $titlePrefix): array
    {
        $entries = [];

        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            /** @var TicketInstance|null $instance */
            $instance = $instances->get($index) ?? $instances->first();
            if (!$instance) {
                continue;
            }

            $fields = [];
            foreach ($item as $field => $fieldValue) {
                $value = $this->stringifyValue($fieldValue);
                $labelKey = $rootKey . '.*.' . (string) $field;
                $label = $labelMap[$labelKey] ?? ucfirst(str_replace('_', ' ', (string) $field));
                $fields[] = [
                    'label' => $label,
                    'value' => $value,
                ];
            }

            if (empty($fields)) {
                $fields = $this->fallbackInstanceFields($instance);
            }

            $entries[] = [
                'instance_id' => $instance->id,
                'title' => $titlePrefix . ' ' . ($index + 1),
                'fields' => $fields,
            ];
        }

        return $entries;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $labelMap
     * @return array<int, array{label: string, value: string}>
     */
    private function collectFieldsFromPayload(array $payload, array $labelMap): array
    {
        $fields = [];

        foreach ($payload as $key => $value) {
            if (!is_array($value)) {
                $fields[] = [
                    'label' => $labelMap[(string) $key] ?? ucfirst(str_replace('_', ' ', (string) $key)),
                    'value' => $this->stringifyValue($value),
                ];
                continue;
            }

            foreach ($this->stringifyFormData([(string) $key => $value]) as $line) {
                $parts = explode(': ', $line, 2);
                $path = $parts[0] ?? '';
                $mappedPath = str_replace(['[', ']'], ['.*.', ''], $path);
                $fields[] = [
                    'label' => $labelMap[$mappedPath] ?? ucfirst(str_replace(['_', '.'], [' ', ' '], $path)),
                    'value' => $parts[1] ?? '',
                ];
            }
        }

        return $fields;
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function fallbackInstanceFields(TicketInstance $instance): array
    {
        $fields = [];

        if ($instance->nombre) {
            $fields[] = ['label' => 'Nombre', 'value' => $instance->nombre];
        }
        if ($instance->email) {
            $fields[] = ['label' => 'Email', 'value' => $instance->email];
        }
        if ($instance->celular) {
            $fields[] = ['label' => 'Celular', 'value' => $instance->celular];
        }

        return $fields;
    }

    /**
     * @param  array<int, array{instance_id: string, title: string, fields: array<int, array{label: string, value: string}>}>  $entries
     */
    private function formatRegistrationEntriesAsText(array $entries): string
    {
        $lines = [];

        foreach ($entries as $index => $entry) {
            $lines[] = '--- ' . strtoupper($entry['title'] ?? ('Registro ' . ($index + 1))) . ' ---';
            foreach (($entry['fields'] ?? []) as $field) {
                $lines[] = ($field['label'] ?? 'Campo') . ': ' . ($field['value'] ?? '');
            }
            $lines[] = '';
        }

        while (!empty($lines) && end($lines) === '') {
            array_pop($lines);
        }

        return empty($lines) ? '-' : implode("\n", $lines);
    }

    /**
     * @return array<string, string>
     */
    private function resolveLabelMap(TicketInstance $instance): array
    {
        if ($instance->evento?->registrationForm) {
            return $this->schemaService->labelMap($instance->evento->registrationForm);
        }

        return [];
    }

    private function transactionReference(TicketInstance $instance): string
    {
        return $instance->payment_intent_id
            ?: $instance->reference
            ?: $instance->id;
    }

    private function mapPaymentMethod(string $method): string
    {
        return match ($method) {
            'cash' => 'Efectivo',
            'card' => 'Tarjeta',
            default => ucfirst($method ?: 'Sin definir'),
        };
    }

    /**
     * @param  Collection<int, TicketInstance>  $instances
     */
    private function resolvePaymentStatus(Collection $instances): string
    {
        $hasStripeOrCash = $instances->contains(function (TicketInstance $instance) {
            return in_array($instance->sale_channel, ['stripe', 'taquilla'], true);
        });

        return $hasStripeOrCash ? 'Pagado' : 'Pendiente';
    }

    private function resolveBuyerData(TicketInstance $instance): string
    {
        $parts = array_filter([
            $instance->nombre ? 'Nombre: ' . $instance->nombre : null,
            $instance->email ? 'Email: ' . $instance->email : null,
            $instance->celular ? 'Cel: ' . $instance->celular : null,
        ]);

        return empty($parts) ? '-' : implode(' | ', $parts);
    }

    /**
     * @param  Collection<int, TicketInstance>  $ticketInstances
     * @return array<int, array{instance_id: string, title: string, fields: array<int, array{label: string, value: string}>}>
     */
    private function buildTicketEntries(Collection $ticketInstances): array
    {
        $entries = [];

        foreach ($ticketInstances->values() as $index => $instance) {
            $fields = [];

            $fields[] = ['label' => 'Boleto', 'value' => (string) ($instance->ticket?->name ?? '-')];
            $fields[] = ['label' => 'Tipo', 'value' => (string) ($instance->ticket?->type ?? $instance->ticket?->name ?? '-')];
            $fields[] = ['label' => 'Precio', 'value' => '$' . number_format((float) ($instance->total ?? $instance->price ?? 0), 2)];

            if ($instance->nombre) {
                $fields[] = ['label' => 'Nombre', 'value' => $instance->nombre];
            }
            if ($instance->email) {
                $fields[] = ['label' => 'Email', 'value' => $instance->email];
            }
            if ($instance->celular) {
                $fields[] = ['label' => 'Celular', 'value' => $instance->celular];
            }
            if ($instance->purchased_at) {
                $fields[] = ['label' => 'Fecha', 'value' => $instance->purchased_at->format('d/m/Y H:i')];
            }

            $entries[] = [
                'instance_id' => $instance->id,
                'title' => 'Boleto ' . ($index + 1),
                'fields' => $fields,
            ];
        }

        return $entries;
    }

    private function stringifyValue(mixed $value): string
    {
        if (is_array($value)) {
            if ($this->isAssoc($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '-';
            }

            return implode(', ', array_map(function ($item) {
                return is_scalar($item) || $item === null
                    ? (string) $item
                    : (json_encode($item, JSON_UNESCAPED_UNICODE) ?: '');
            }, $value));
        }

        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        if ($value === null) {
            return '-';
        }

        return (string) $value;
    }

    private function stringifyFormData(array $data, string $prefix = ''): array
    {
        $result = [];

        foreach ($data as $key => $value) {

            $label = $prefix !== ''
                ? $prefix . '.' . $key
                : $key;

            if (is_array($value)) {

                if ($this->isAssoc($value)) {
                    $result = array_merge(
                        $result,
                        $this->stringifyFormData($value, $label)
                    );
                } else {

                    foreach ($value as $index => $item) {

                        if (is_array($item)) {
                            $result = array_merge(
                                $result,
                                $this->stringifyFormData($item, $label . '[' . $index . ']')
                            );
                        } else {
                            $result[] = $label . '[' . $index . ']: ' . $item;
                        }
                    }
                }

            } else {

                $result[] = $label . ': ' . $value;
            }
        }

        return $result;
    }

    private function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
