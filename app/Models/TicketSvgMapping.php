<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketSvgMapping extends Model
{
    use HasFactory;

    protected $table = 'ticket_svg_mappings';

    protected $fillable = [
        'evento_id',
        'project_id',
        'phase_id',
        'stage_id',
        'ticket_id',
        'svg_selector',
        'redirect',
        'redirect_url',
        'color',
        'color_active',
    ];

    protected $casts = [
        'redirect' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function evento()
    {
        return $this->belongsTo(Eventos::class, 'evento_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function phase()
    {
        return $this->belongsTo(Phase::class, 'phase_id');
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class, 'stage_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes útiles
    |--------------------------------------------------------------------------
    */

    public function scopeByEvento($query, $eventoId)
    {
        return $query->where('evento_id', $eventoId);
    }

    public function scopeByTicket($query, $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    public function scopeClickable($query)
    {
        return $query->where('redirect', true);
    }
}
