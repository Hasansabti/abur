<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use HasFactory;

    // الحقول المسموح بحفظها تلقائياً (Mass Assignment)
    protected $fillable = [
        'agency_id',
        'name',
        'phone',
        'national_id',
        'license_number',
        'license_expiry_date',
        'status',
    ];

    /**
     * الكابتن ينتمي إلى وكالة سفر محددة
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * الرحلات التي قادها كـ (سائق رئيسي / أول)
     */
    public function primaryTrips(): HasMany
    {
        return $this->hasMany(Trip::class, 'driver_id');
    }

    /**
     * الرحلات التي شارك فيها كـ (سائق ثاني / مساعد)
     */
    public function secondaryTrips(): HasMany
    {
        // تنبيه: أعد التسمية إلى 'driver_2_id' إذا كان العمود يحتوي على شرطة سفلية في قاعدة البيانات
        return $this->hasMany(Trip::class, 'driver2_id');
    }
}