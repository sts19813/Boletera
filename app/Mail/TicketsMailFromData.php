<?php

namespace App\Mail;

class TicketsMailFromData extends BoletosMail
{
    public function __construct(array $boletos)
    {
        parent::__construct($boletos, '🎟️ Reenvío de boletos');
    }
}
