<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class RegistrationInstance extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'event_id',
        'email',
        'payment_intent_id',
        'qr_hash',
        'registered_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
    ];
    public function evento()
    {
        return $this->belongsTo(Eventos::class, 'event_id');
    }

    public function registration()
    {
        return $this->hasOne(Registration::class);
    }
}
