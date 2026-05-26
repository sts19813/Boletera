<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminEventNoteAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_event_note_id',
        'original_name',
        'storage_path',
        'mime_type',
        'size_bytes',
    ];

    public function note()
    {
        return $this->belongsTo(AdminEventNote::class, 'admin_event_note_id');
    }
}
