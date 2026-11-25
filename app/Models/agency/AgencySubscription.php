<?php

namespace App\Models\agency;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\SubscriptionPlan; 
use App\Models\Agency; 


class AgencySubscription extends Model
{
    protected $fillable = [
        'agency_id', 'plan_id', 'status', 'billing_cycle', 'price_paid',
        'payment_method', 'payment_reference', 'payment_receipt',
        'starts_at', 'ends_at', 'cancelled_at', 'admin_notes', 'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'price_paid'   => 'decimal:2',
            'starts_at'    => 'datetime',
            'ends_at'      => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /** الاشتراك ساري الآن (نشط وضمن فترته) */
    public function isCurrent(): bool
    {
        return $this->status === 'active'
            && $this->starts_at && $this->starts_at->lte(now())
            && $this->ends_at && $this->ends_at->gt(now());
    }

    public function daysLeft(): ?int
    {
        return $this->ends_at ? max(0, (int) ceil(now()->diffInDays($this->ends_at, false))) : null;
    }
}