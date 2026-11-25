<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPointTransaction extends Model
{
    protected $fillable = [
        'customer_id', 'agency_id', 'booking_id', 'transaction_type', 'points',
        'monetary_value', 'balance_before', 'balance_after', 'reference', 'notes',
        'created_by', 'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'monetary_value' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
