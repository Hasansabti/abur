<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'type',
        'vehicle_model',
        'plate_number',
        'total_seats',
        'check_date',
        'status',
    ];

    /**
     * المركبة تنتمي إلى أسطول وكالة سفر محددة
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}