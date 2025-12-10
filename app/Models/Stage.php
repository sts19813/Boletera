<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    protected $fillable = ['phase_id', 'name', 'credit_scheme_id', 'enterprise_id'];

    public function phase() { return $this->belongsTo(Phase::class); }
    public function enterprise() { return $this->belongsTo(Enterprise::class); }
    public function lots() { return $this->hasMany(Ticket::class); }
    public function customFields() { return $this->morphMany(CustomField::class, 'customizable'); }
}

