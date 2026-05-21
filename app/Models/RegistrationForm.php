<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationForm extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'schema',
        'ui_settings',
        'is_active',
    ];

    protected $casts = [
        'schema' => 'array',
        'ui_settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function events()
    {
        return $this->hasMany(Eventos::class, 'registration_form_id');
    }
}
