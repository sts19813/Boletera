<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['user_id', 'name', 'email', 'phone', 'logo', 'quotation'];

    public function user() { return $this->belongsTo(User::class); }
    public function phases() { return $this->hasMany(Phase::class); }
}
