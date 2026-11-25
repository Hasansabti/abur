<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingMessageLog extends Model
{
    protected $fillable = [
        'booking_id',
        'agency_id',
        'customer_id',
        'channel',
        'phone',
        'body',
        'template_key',
        'status',
        'provider_message_id',
        'failure_reason',
        'sent_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
