<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'agency_id',
        'trip_id',
        'action_type',
        'category',
        'amount_changed',
        'seats_changed',
        'description',
        'operator_name'
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}