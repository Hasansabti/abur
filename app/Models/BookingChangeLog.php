<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingChangeLog extends Model
{
    protected $fillable = [
        'booking_id',
        'agency_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'reason',
        'performed_by',
        'actor_type',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
