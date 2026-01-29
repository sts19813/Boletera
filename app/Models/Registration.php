<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Registration extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'event_id',
        'team_name',
        'players',
        'subtotal',
        'commission',
        'total',
    ];

    protected $casts = [
        'players' => 'array',
        'subtotal' => 'decimal:2',
        'commission' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function event()
    {
        return $this->belongsTo(Eventos::class, 'event_id');
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }
}
