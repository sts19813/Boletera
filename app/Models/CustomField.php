<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'value',
        'customizable_type',
        'customizable_id',
    ];

    /**
     * Relación polimórfica con los modelos que admiten campos personalizados.
     * Ejemplo: Stage o Lot.
     */
    public function customizable()
    {
        return $this->morphTo();
    }
}
