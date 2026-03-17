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
        'sale_type',
        'user_id',
        'event_id',
        'email',
        'nombre',
        'celular',
        'team_name',
        'purchased_at',
        'registered_at',
        'qr_hash',
        'payment_intent_id',
        'reference',
        'sale_channel',
        'payment_method',
        'used_at',
        'price',
        'subtotal',
        'commission',
        'total',
        'form_data',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'registered_at' => 'datetime',
        'used_at' => 'datetime',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'commission' => 'decimal:2',
        'total' => 'decimal:2',
        'form_data' => 'array',
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

    public function evento()
    {
        return $this->belongsTo(Eventos::class, 'event_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function checkins()
    {
        return $this->hasMany(TicketCheckin::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeTicketSales($query)
    {
        return $query->where(function ($q) {
            $q->where('sale_type', 'ticket')
                ->orWhereNull('sale_type');
        });
    }

    public function scopeRegistrationSales($query)
    {
        return $query->where('sale_type', 'registration');
    }

    public function getSoldAtAttribute()
    {
        return $this->registered_at ?? $this->purchased_at;
    }
}
