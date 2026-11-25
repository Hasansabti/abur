<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;


class PrivateTripRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agency_id',
        'from_city',
        'to_city',
        'departure_date',
        'return_date',
        'trip_type',
        'transport_type',
        'passengers_count',
        'contact_name',
        'contact_phone',
        'notes',
        'status',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
        'passengers_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    // 🟢 علاقة الطلب بالوكالة
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
    public function trip(): HasOne
    {
        return $this->hasOne(Trip::class);
    }
}
