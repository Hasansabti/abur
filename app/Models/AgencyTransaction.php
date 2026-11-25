<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyTransaction extends Model
{
    protected $fillable = [
        'agency_id',
        'booking_id',
        'type',
        'amount',
        'gross_amount',
        'commission_amount',
        'passengers_count',
        'status',
        'description',
        'idempotency_key',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gross_amount'      => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
