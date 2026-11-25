<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'user_id',
        'trip_id',
        'accommodation_rating',
        'meals_rating',
        'staff_rating',
        'organization_rating',
        'price_value_rating',
        'transport_rating',
        'services_rating',
        'comment',
    ];

    /**
     * الوكالة
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * المستخدم
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الرحلة
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }


    
}