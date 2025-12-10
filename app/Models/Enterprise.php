<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enterprise extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_name',
    ];

    /**
     * Relación con las etapas (stages)
     * Una empresa puede estar asociada a varias etapas.
     */
    public function stages()
    {
        return $this->hasMany(Stage::class);
    }
}
