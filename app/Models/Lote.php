<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
   
    use HasFactory;

    protected $table = 'lotes';

    protected $fillable = [
        'desarrollo_id',
        'project_id',
        'phase_id',
        'stage_id',
        'lote_id',
        'selectorSVG',
        'redirect',
        'redirect_url',
        'color',
        'color_active',
    ];

    public function desarrollo()
    {
        return $this->belongsTo(Desarrollos::class, 'desarrollo_id');
    }
}
