<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staffs'; // أو 'staff' حسب اسم الجدول لديك في Migration

    protected $fillable = [
        'agency_id',
        'name',
        'phone',
        'national_id',
        'role', // مثل: مشرف رحلة، مساعد سائق، مرشد سياحي، طاقم ضيافة
        'status',
    ];

    /**
     * فرد الكادر ينتمي إلى وكالة سفر محددة
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}