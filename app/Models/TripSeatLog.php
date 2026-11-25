<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripSeatLog extends Model
{
    protected $fillable = [
        'trip_id',
        'booking_seat_id',
        'passenger_name',
        'old_seat',
        'new_seat',
        'reason'
    ];

    /**
     * السجل ينتمي لرحلة معينة
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * السجل يرتبط بمقعد المسافر الحالي المتأثر بالإزاحة
     */
    public function bookingSeat(): BelongsTo
    {
        return $this->belongsTo(BookingSeat::class, 'booking_seat_id');
    }
}