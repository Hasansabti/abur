<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\agency\AgencySubscription;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name', 'description', 'monthly_price', 'yearly_price',
        'max_active_trips', 'max_vehicles', 'max_drivers', 'max_employees',
        'allows_overflow_bus',
        'commission_per_passenger', 'min_booking_commission', 'max_booking_commission',
        'whatsapp_monthly_limit', 'whatsapp_extra_rate',
        'is_default', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price'            => 'decimal:2',
            'yearly_price'             => 'decimal:2',
            'commission_per_passenger' => 'decimal:2',
            'min_booking_commission'   => 'decimal:2',
            'max_booking_commission'   => 'decimal:2',
            'whatsapp_extra_rate'      => 'decimal:2',
            'allows_overflow_bus'      => 'boolean',
            'is_default'               => 'boolean',
            'is_active'                => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(AgencySubscription::class, 'plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function defaultPlan(): ?self
    {
        return static::where('is_default', true)->first();
    }
}