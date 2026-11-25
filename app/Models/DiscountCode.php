<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountCode extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'max_discount',
        'min_booking_amount',
        'starts_at',
        'ends_at',
        'is_active',
        'max_uses',
        'max_uses_per_customer',
        'uses_count',
        'agency_ids',
        'trip_ids',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'min_booking_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'agency_ids' => 'array',
            'trip_ids' => 'array',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(DiscountCodeUsage::class);
    }
}
