<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAccessToken extends Model
{
    protected $fillable = [
        'booking_id', 'agency_id', 'token', 'expires_at', 'revoked_at',
        'created_by', 'last_accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_accessed_at' => 'datetime',
        ];
    }

    public function isValid(): bool
    {
        if ($this->revoked_at) {
            return false;
        }

        return !$this->expires_at || $this->expires_at->isFuture();
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
