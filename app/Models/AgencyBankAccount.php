<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyBankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'bank_name',
        'account_number',
        'iban',
        'qr_code',
        'is_active',
    ];

    // العلاقة العكسية: الحساب البنكي ينتمي لوكالة واحدة
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}