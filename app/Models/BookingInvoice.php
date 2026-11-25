<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingInvoice extends Model
{
    protected $fillable = [
        'booking_id',
        'agency_id',
        'invoice_number',
        'issued_at',
        'snapshot',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'paid_amount',
        'remaining_amount',
        'issued_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'snapshot' => 'array',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingInvoiceItem::class);
    }
}
