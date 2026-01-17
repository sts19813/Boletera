<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TicketCheckin extends Model
{
    protected $fillable = [
        'ticket_instance_id',
        'hash',
        'result',
        'message',
        'scanned_at',
        'scanner_ip',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function ticketInstance()
    {
        return $this->belongsTo(TicketInstance::class);
    }
}
