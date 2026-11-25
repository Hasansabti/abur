<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingMessageLog extends Model
{
    protected $fillable = [
        'booking_id', 'agency_id', 'channel', 'template_key', 'customer_name',
        'phone', 'message', 'status', 'provider_message_id', 'delivery_status',
        'failure_reason', 'counts_against_quota', 'sent_by', 'sent_at', 'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'counts_against_quota' => 'boolean',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
