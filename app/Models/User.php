<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\CustomResetPasswordNotification;


class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'birth_date',
        'city',
        'avatar',
        'id_image',
        'is_verified',
        'tour_seen',
        'password',
        'phone',
        'gender',
        'national_id',
        'birth_date_hijri'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',

    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'tour_seen' => 'boolean',
        ];
    }


    /**
     * إرسال إشعار استعادة كلمة المرور باللغة العربية
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
    // public function sendPasswordResetNotification($token)
    // {
    //     $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
    // }
    public function companions()
    {
        return $this->hasMany(Companion::class);
    }
}
