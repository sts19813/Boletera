<?php

namespace App\Services;

use App\Models\Eventos;
use App\Models\TicketInstance;
use App\Support\EventReportColumns;
use Illuminate\Support\Collection;

class EventReportService
{
    /**
     * @return array{
     *   columns: array<int, array{key: string, label: string}>,
     *   rows: array<int, array<string, mixed>>
     * }
     */
    public function build(Eventos $event): array
    {
        $columnKeys = EventReportColumns::normalizeKeys($event->report_settings);
        $definitions = EventReportColumns::definitions();

        $columns = array_values(array_map(function (string $key) use ($definitions) {
            return [
                'key' => $key,
                'label' => $definitions[$key],
            ];
        }, $columnKeys));

        $instances = TicketInstance::query()
            ->with(['ticket:id,name,type', 'evento:id,name'])
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

        $detailRows = $this->buildDetailRows($instances);
        $itemsCount = count($detailRows);
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

        $rows = [];

        foreach ($detailRows as $detail) {
            $row = [
                '_sort_date' => $sortDate,
                'purchase_reference' => $transactionRef,
                'event_name' => $event->name,
                'sale_type' => $detail['sale_type'] ?? '-',
                'seat' => $detail['seat'] ?? '-',
                'ticket_type' => $detail['ticket_type'] ?? '-',
                'coupon' => empty($couponCodes) ? '-' : implode(', ', $couponCodes),
                'discount' => empty($discountValues) ? '-' : implode(', ', $discountValues),
                'payment_method' => $paymentMethod,
                'total_paid' => '$' . number_format($totalPaid, 2),
                'purchased_at' => $purchaseDate ?? '-',
                'payment_status' => $paymentStatus,
                'buyer_data' => $buyerData,
                'record_data' => $detail['record_data'] ?? '-',
                'items_count' => $itemsCount,
            ];

            $rows[] = array_intersect_key($row, array_flip(array_merge(['_sort_date'], $columnKeys)));
        }

        return $rows;
    }

    /**
     * @param  Collection<int, TicketInstance>  $instances
     * @return array<int, array{sale_type: string, seat: string, ticket_type: string, record_data: string}>
     */
    private function buildDetailRows(Collection $instances): array
    {
        $rows = [];

        $ticketInstances = $instances->filter(fn(TicketInstance $instance) => $instance->sale_type !== 'registration');
        foreach ($ticketInstances as $instance) {
            $rows[] = [
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

        $registrationRows = [];
        $registrationInstances = $instances->filter(fn(TicketInstance $instance) => $instance->sale_type === 'registration');

        foreach ($registrationInstances as $instance) {
            $formData = is_array($instance->form_data) ? $instance->form_data : [];
            $registrationRows = array_merge($registrationRows, $this->extractRegistrationRows($instance, $formData));
        }

        if (empty($registrationRows) && $registrationInstances->isNotEmpty()) {
            /** @var TicketInstance $fallback */
            $fallback = $registrationInstances->first();
            $registrationRows[] = [
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

        $registrationRows = collect($registrationRows)
            ->unique(fn(array $row) => md5(json_encode($row)))
            ->values()
            ->all();

        return array_merge($rows, $registrationRows);
    }

    /**
     * @param  array<string, mixed>  $formData
     * @return array<int, array{sale_type: string, seat: string, ticket_type: string, record_data: string}>
     */
    private function extractRegistrationRows(TicketInstance $instance, array $formData): array
    {
        $rows = [];

        $players = $formData['players'] ?? null;
        if (is_array($players) && !empty($players)) {
            foreach ($players as $player) {
                if (!is_array($player)) {
                    continue;
                }

                $rows[] = [
                    'sale_type' => 'Registro',
                    'seat' => '-',
                    'ticket_type' => 'Inscripcion',
                    'record_data' => trim(implode(' | ', array_filter([
                        isset($player['name']) ? 'Jugador: ' . $player['name'] : null,
                        isset($player['email']) ? 'Email: ' . $player['email'] : null,
                        isset($player['phone']) ? 'Cel: ' . $player['phone'] : null,
                    ]))) ?: '-',
                ];
            }

            return $rows;
        }

        $participants = $formData['participants'] ?? null;
        if (is_array($participants) && !empty($participants)) {
            foreach ($participants as $participant) {
                if (!is_array($participant)) {
                    continue;
                }

                $rows[] = [
                    'sale_type' => 'Registro',
                    'seat' => '-',
                    'ticket_type' => 'Inscripcion',
                    'record_data' => trim(implode(' | ', array_filter([
                        isset($participant['nombre']) ? 'Nombre: ' . $participant['nombre'] : null,
                        isset($participant['email']) ? 'Email: ' . $participant['email'] : null,
                        isset($participant['celular']) ? 'Cel: ' . $participant['celular'] : null,
                    ]))) ?: '-',
                ];
            }

            return $rows;
        }

        $rows[] = [
            'sale_type' => 'Registro',
            'seat' => '-',
            'ticket_type' => 'Inscripcion',
            'record_data' => trim(implode(' | ', array_filter([
                isset($formData['full_name']) ? 'Nombre: ' . $formData['full_name'] : ($instance->nombre ? 'Nombre: ' . $instance->nombre : null),
                isset($formData['email']) ? 'Email: ' . $formData['email'] : ($instance->email ? 'Email: ' . $instance->email : null),
                isset($formData['phone']) ? 'Cel: ' . $formData['phone'] : ($instance->celular ? 'Cel: ' . $instance->celular : null),
            ]))) ?: '-',
        ];

        return $rows;
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
}
