<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAccessToken extends Model
{
    protected $fillable = [
        'booking_id',
        'token',
        'expires_at',
        'revoked_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function isValid(): bool
    {
        if ($this->revoked_at) {
            return false;
        }

        return !$this->expires_at || $this->expires_at->isFuture();
    }
}
