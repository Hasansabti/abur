<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripRoom extends Model
{
    protected $fillable = ['trip_id', 'room_uid', 'room_type', 'room_number', 'notes', 'hotel_notes', 'bed_type', 'extra_bed_quantity'];

    // الغرفة تنتمي لرحلة معينة
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    // الغرفة الواحدة بداخلها مجموعة كراسي/ركاب
    public function seats()
    {
        return $this->hasMany(BookingSeat::class, 'trip_room_id');
    }
}
