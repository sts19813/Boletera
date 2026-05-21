<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EventCoupon extends Model
{
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'event_id',
        'code',
        'auto_apply',
        'discount_type',
        'discount_value',
        'min_qty',
        'max_tickets',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'auto_apply' => 'boolean',
        'min_qty' => 'integer',
        'max_tickets' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }

            if (!empty($model->code)) {
                $model->code = strtoupper(trim((string) $model->code));
            }
        });

        static::updating(function ($model) {
            if (!empty($model->code)) {
                $model->code = strtoupper(trim((string) $model->code));
            }
        });
    }

    public function event()
    {
        return $this->belongsTo(Eventos::class, 'event_id');
    }

    public function scopeAvailableAt($query, $at = null)
    {
        $at = $at ?? now();

        return $query
            ->where(function ($q) use ($at) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $at);
            })
            ->where(function ($q) use ($at) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $at);
            });
    }
}
