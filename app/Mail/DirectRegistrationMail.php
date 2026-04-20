<?php

namespace App\Mail;

use App\Models\Eventos;
use Illuminate\Mail\Mailable;

class DirectRegistrationMail extends Mailable
{
    public function __construct(
        public Eventos $event,
        public array $registrationData
    ) {
    }

    public function build()
    {
        return $this
            ->subject('Registro confirmado - ' . $this->event->name)
            ->view('emails.direct-registration')
            ->with([
                'evento' => $this->event,
                'registration' => $this->registrationData,
                'whatsappLink' => 'https://chat.whatsapp.com/ExypBBvEppnErpfFdXGoR1?mode=gi_t',
            ]);
    }
}
