<?php

namespace App\Services;

use App\Models\TicketInstance;

class BoletosPayloadService
{
    public function __construct(
        private TicketBuilderService $ticketBuilder,
        private RegistrationBuilderService $registrationBuilder
    ) {
    }

    /**
     * @param array<int, string> $instanceIds
     * @return array<int, array<string, mixed>>
     */
    public function buildFromInstanceIds(array $instanceIds, string $fallbackEmail = 'taquilla@local'): array
    {
        if (empty($instanceIds)) {
            return [];
        }

        $instances = TicketInstance::with(['ticket', 'evento'])
            ->whereIn('id', $instanceIds)
            ->get()
            ->keyBy('id');

        $boletos = [];

        foreach ($instanceIds as $id) {
            $instance = $instances->get($id);

            if (!$instance) {
                continue;
            }

            $email = $instance->email ?: $fallbackEmail;

            if ($instance->sale_type === 'registration') {
                $boletos[] = $this->registrationBuilder->build(
                    $instance->evento,
                    $instance,
                    $email
                );

                continue;
            }

            $boletos[] = $this->ticketBuilder->build(
                $instance->ticket,
                $instance,
                $email,
                $instance->purchased_at,
                $instance->evento,
                $instance->payment_intent_id ?? $instance->reference
            );
        }

        return $boletos;
    }
}
