<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eventos extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'total_asientos',
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
}
