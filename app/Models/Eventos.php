<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Eventos extends Model
{
    use HasFactory, HasUuids;
    // UUID como primary key
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'name',
        'description',
        'event_date',
        'hora_inicio',
        'hora_fin',
        'location',
        'total_asientos',
        'has_seat_mapping',
        'is_registration',
        'template_form', // Para determinar blade archivo formulario de registro usar contiene logica js
        'allows_multiple_registrations',
        'price',
        'template', // Para determinar plantilla formulario de registro usar visual utilizar al futuro
        'max_capacity',
        'project_id',
        'phase_id',
        'stage_id',
        'modal_color',
        'modal_selector',
        'color_primario',
        'color_acento',
        'redirect_return',
        'redirect_next',
        'redirect_previous',
        'svg_image',
        'png_image'
    ];

    protected $casts = [
        'event_date' => 'date',
        'has_seat_mapping' => 'boolean',
        'is_registration' => 'boolean',
        'price' => 'decimal:2',
        'allows_multiple_registrations' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
