<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Player extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'registration_id',
        'name',
        'cumbres',
        'phone',
        'email',
        'campo',
        'handicap',
        'ghin',
        'shirt',
        'is_captain',
    ];

    protected $casts = [
        'cumbres' => 'array',
        'is_captain' => 'boolean',
    ];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}
