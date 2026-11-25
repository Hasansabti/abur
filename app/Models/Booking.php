<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'user_id',
        'agency_id',
        'is_locked',
        'room_count',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_national_id',
        'room_type',
        'status',
        'payment_status',
        'total_amount',
        'cash_paid',
        'net_amount',
        'platform_commission',
        'agency_amount',
        'expires_at',
        'payment_method',
        'bank_receipt',
        'card_name',
        'moyasar_payment_id',
        'trip_completed_at',
        'edit_notes',
        'edit_count',

        'room_bed_types',
        'agency_note',
        'agency_note_status',

    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'cash_paid' => 'decimal:2',
            'platform_commission' => 'decimal:2',
            'agency_amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'trip_completed_at' => 'datetime',
            'room_bed_types' => 'array',

        ];
    }
    public function extraCharges(): HasMany
    {
        return $this->hasMany(BookingExtraCharge::class);
    }

    public function isExpired(): bool
    {   
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function uploadReceipt($file): self
    {
        if ($file) {
            if ($this->bank_receipt && Storage::disk('public')->exists($this->bank_receipt)) {
                Storage::disk('public')->delete($this->bank_receipt);
            }

            $path = $file->store('receipts', 'public');

            $this->update([
                'bank_receipt' => $path
            ]);
        }

        return $this;
    }

    /* ================= العلاقات ================= */

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class, 'trip_id');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class, 'booking_id');
    }
    public function payments(): HasMany
    {
        return $this->hasMany(BookingPayment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function passengers(): HasMany
    {
        return $this->hasMany(BookingSeat::class, 'booking_id');
    }


    public function seats(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }

    public function roomOption(): BelongsTo
    {
        return $this->belongsTo(TripRoom::class, 'trip_room_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AgencyTransaction::class);
    }
}
