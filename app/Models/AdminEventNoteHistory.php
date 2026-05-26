<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminEventNoteHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_event_note_id',
        'changed_by',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function note()
    {
        return $this->belongsTo(AdminEventNote::class, 'admin_event_note_id');
    }

    public function changedByUser()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
