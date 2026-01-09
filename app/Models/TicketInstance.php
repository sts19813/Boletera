<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TicketInstance extends Model
{
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

     protected $fillable = [
        'ticket_id',
        'email',
        'purchased_at',
        'qr_hash',
        'payment_intent_id',
        'reference',
        'sale_channel',
        'payment_method',
        'used_at'
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'used_at' => 'datetime', // ← IMPORTANTE para el check-in
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
