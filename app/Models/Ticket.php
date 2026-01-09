<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

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
        'is_courtesy'     => 'boolean',
        'total_price'     => 'decimal:2',
        'available_from'  => 'datetime',
        'available_until' => 'datetime',
        'purchased_at'    => 'datetime',
    ];

    // Genera UUID automáticamente
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

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
}
