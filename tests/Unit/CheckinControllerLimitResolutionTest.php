<?php

namespace Tests\Unit;

use App\Http\Controllers\CheckinController;
use App\Models\Eventos;
use App\Models\Ticket;
use App\Models\TicketInstance;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CheckinControllerLimitResolutionTest extends TestCase
{
    public function test_ticket_instance_uses_ticket_max_checkins(): void
    {
        $instance = new TicketInstance([
            'ticket_id' => 'ticket-uuid',
            'form_data' => ['checkin_max' => 9],
        ]);
        $instance->setRelation('ticket', new Ticket(['max_checkins' => 4]));
        $instance->setRelation('evento', new Eventos(['registration_max_checkins' => 2]));

        $maxCheckins = $this->callResolveMaxCheckins($instance);

        $this->assertSame(4, $maxCheckins);
    }

    public function test_registration_uses_instance_override_before_event_default(): void
    {
        $instance = new TicketInstance([
            'ticket_id' => null,
            'form_data' => ['checkin_max' => '3'],
        ]);
        $instance->setRelation('evento', new Eventos(['registration_max_checkins' => 1]));

        $maxCheckins = $this->callResolveMaxCheckins($instance);

        $this->assertSame(3, $maxCheckins);
    }

    public function test_registration_uses_event_default_and_falls_back_to_one(): void
    {
        $withEventDefault = new TicketInstance([
            'ticket_id' => null,
            'form_data' => null,
        ]);
        $withEventDefault->setRelation('evento', new Eventos(['registration_max_checkins' => 5]));

        $withoutAnyDefault = new TicketInstance([
            'ticket_id' => null,
            'form_data' => ['checkin_max' => '0'],
        ]);
        $withoutAnyDefault->setRelation('evento', new Eventos(['registration_max_checkins' => 0]));

        $maxWithEventDefault = $this->callResolveMaxCheckins($withEventDefault);
        $maxWithoutAnyDefault = $this->callResolveMaxCheckins($withoutAnyDefault);

        $this->assertSame(5, $maxWithEventDefault);
        $this->assertSame(1, $maxWithoutAnyDefault);
    }

    private function callResolveMaxCheckins(TicketInstance $instance): int
    {
        $controller = new CheckinController();

        $method = new ReflectionMethod(CheckinController::class, 'resolveMaxCheckins');
        $method->setAccessible(true);

        return $method->invoke($controller, $instance);
    }
}
