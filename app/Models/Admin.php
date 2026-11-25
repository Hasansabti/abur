<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    // تحديد الـ Guard الخاص بهذا الموديل ليكون منفصلاً عن المستخدمين
    protected $guard = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    // دالات مساعدة (Helpers) للتحقق من الصلاحيات بسهولة داخل الكود والـ Blade
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isPartner(): bool
    {
        return $this->role === 'partner';
    }

    public function isSupport(): bool
    {
        return $this->role === 'support';
    }
}