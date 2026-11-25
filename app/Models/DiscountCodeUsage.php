<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountCodeUsage extends Model
{
    protected $fillable = [
        'discount_code_id',
        'booking_id',
        'agency_id',
        'customer_id',
        'customer_phone',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
