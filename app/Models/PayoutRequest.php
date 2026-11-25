<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutRequest extends Model
{
    protected $fillable = [
        'agency_id', 'requested_amount', 'whatsapp_fee_deducted', 
        'net_payout_amount', 'status', 'bank_receipt', 'admin_notes', 'processed_at'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}