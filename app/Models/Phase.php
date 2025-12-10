<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Phase extends Model
{
    protected $fillable = ['project_id', 'name', 'start_date'];

    public function project() { return $this->belongsTo(Project::class); }
    public function stages() { return $this->hasMany(Stage::class); }
}