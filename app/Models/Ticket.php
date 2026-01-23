<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Ticket extends Model
{
    use HasFactory, HasUuids;

    // UUID como primary key
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'stage_id',
        'name',
        'type',
        'total_price',
        'status',
        'stock',
        'sold',
        'available_from',
        'available_until',
        'purchased_at',
        'description',
        'is_courtesy',
        'max_checkins'
    ];

    protected $casts = [
        'is_courtesy' => 'boolean',
        'total_price' => 'decimal:2',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'purchased_at' => 'datetime',
    ];

    // Relaciones
    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function customFields()
    {
        return $this->morphMany(CustomField::class, 'customizable');
    }


    public function scopePurchased($query)
    {
        return $query->whereNotNull('purchased_at');
    }

    public function instances()
    {
        return $this->hasMany(TicketInstance::class);
    }

}
