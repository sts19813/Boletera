<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\Eventos;

class EventHeader extends Component
{
    public Eventos $evento;

    public function __construct(Eventos $evento)
    {
        $this->evento = $evento;
    }

    public function render()
    {
        return view('components.event-header');
    }
}
