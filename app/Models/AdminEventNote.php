<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminEventNote extends Model
{
    use HasFactory;

    public const CATEGORY_COURTESY = 'courtesy';
    public const CATEGORY_CANCELLATION = 'cancellation';
    public const CATEGORY_DISPUTE = 'dispute';
    public const CATEGORY_PAYMENT = 'payment';
    public const CATEGORY_OTHER = 'other';

    protected $fillable = [
        'event_id',
        'category',
        'title',
        'note',
        'counterparty',
        'amount',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public static function categories(): array
    {
        return [
            self::CATEGORY_COURTESY => 'Cortesia',
            self::CATEGORY_CANCELLATION => 'Cancelacion',
            self::CATEGORY_DISPUTE => 'Disputa',
            self::CATEGORY_PAYMENT => 'Pago/Entrega',
            self::CATEGORY_OTHER => 'Otro',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Eventos::class, 'event_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments()
    {
        return $this->hasMany(AdminEventNoteAttachment::class);
    }
}
