<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingExtraCharge extends Model
{
    protected $fillable = [
        'booking_id', 'agency_id', 'amount', 'description', 'status', 'token',
        'moyasar_invoice_id', 'moyasar_payment_id', 'payment_url', 'paid_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}