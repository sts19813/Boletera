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
        'registration_instance_id',
        'team_name',
        'subtotal',
        'commission',
        'total',
        'event_id',
    ];

    protected $casts = [
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
