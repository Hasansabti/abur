<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyEmployee extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'agency_employees';

    protected $fillable = [
        'agency_id',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'permissions',
        'is_active',

    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'  => 'hashed',
            'is_active' => 'boolean',
            'permissions' => 'array',
        ];
    }

    // علاقة الموظف بالوكالة التابع لها
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}