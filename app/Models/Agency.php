<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\agency\AgencySubscription;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Agency extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'agencies';

    protected $fillable = [
        'name',
        'email',
        'iconA',
        'password',
        'ibanA',
        'phone',
        'commercial_name',
        'commercial_id',
        'pending_commercial_id',
        'pending_cr_expiry_date',
        'pending_cr_document_path',
        'cr_status',
        'cr_expiry_date',
        'status',
        'commission_per_passenger',
        'min_booking_commission',
        'max_booking_commission',
        'whatsapp_monthly_limit',
        'whatsapp_extra_rate',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'general_rating',
        'ratings_count',
        'criteria_averages',
        'pending_balance',
        'available_balance',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'commission_per_passenger' => 'decimal:2',
            'min_booking_commission' => 'decimal:2',
            'max_booking_commission' => 'decimal:2',
            'whatsapp_extra_rate' => 'decimal:2',
        ];
    }

    /**
     * تشغيل الأحداث الخاصة بالنموذج (Model Booted)
     */
    protected static function booted(): void
    {
        static::created(function (Agency $agency) {
            if ($plan = SubscriptionPlan::defaultPlan()) {
                app(\App\Services\SubscriptionService::class)->applyPlanToAgency($agency, $plan);
            }
        });
    }

    /* ================= العلاقات (Relationships) ================= */

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(AgencyBankAccount::class, 'agency_id')->where('is_active', true);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(AgencyRating::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(AgencyEmployee::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AgencyTransaction::class);
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(PayoutRequest::class);
    }

    public function whatsappLogs(): HasMany
    {
        return $this->hasMany(WhatsappLog::class);
    }


    public function subscriptions(): HasMany
    {
        return $this->hasMany(AgencySubscription::class);
    }

    /** الاشتراك الساري حالياً (إن وجد) */
    public function currentSubscription(): ?AgencySubscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->latest('starts_at')
            ->first();
    }

    /** الباقة الفعّالة: باقة الاشتراك الساري، وإلا الباقة الافتراضية */
    public function currentPlan(): ?SubscriptionPlan
    {
        return $this->currentSubscription()?->plan ?? SubscriptionPlan::defaultPlan();
    }

    /* ================= حساب عمولة الحجز (Commission Calculation) ================= */

    /**
     * حساب عمولة الوكالة بناءً على عدد المسافرين مع تطبيق الحد الأدنى والحد الأعلى.
     */
    public function calculateBookingCommission(int $passengersCount): float
    {
        $perPassengerRate = (float) ($this->commission_per_passenger ?? 0);
        $minLimit = $this->min_booking_commission !== null ? (float) $this->min_booking_commission : null;
        $maxLimit = $this->max_booking_commission !== null ? (float) $this->max_booking_commission : null;

        // تم تصحيح سطر الضرب المكسور هنا
        $calculatedCommission = $passengersCount * $perPassengerRate;

        // تطبيق الحد الأدنى (إذا وجد وكان الناتج أقل منه)
        if ($minLimit !== null && $calculatedCommission < $minLimit) {
            $calculatedCommission = $minLimit;
        }

        // تطبيق الحد الأعلى (إذا وجد وكان الناتج أكبر منه)
        if ($maxLimit !== null && $calculatedCommission > $maxLimit) {
            $calculatedCommission = $maxLimit;
        }

        return round($calculatedCommission, 2);
    }

    /* ================= المحفظة والحسابات المالية (Wallet Accessors) ================= */

    // 1. الرصيد المعلق (رحلات قادمة لم تُنجز بعد)
    public function getPendingBalanceAttribute(): float
    {
        return (float) $this->transactions()
            ->where('status', 'pending')
            ->where('status', '!=', 'cancelled')
            ->sum('amount');
    }

    // 2. الرصيد المتاح للسحب
    public function getAvailableBalanceAttribute(): float
    {
        $credits = $this->transactions()
            ->where('status', 'available')
            ->sum('amount');

        $payoutsAndDeductions = $this->transactions()
            ->where('status', 'completed')
            ->whereIn('type', ['payout', 'whatsapp_fee', 'booking_debit'])
            ->sum('amount');

        return (float) ($credits + $payoutsAndDeductions);
    }

    // 3. حساب رسوم الواتساب الزائدة للشهر الحالي
    public function calculateCurrentMonthWhatsappFee(): array
    {
        $sentCount = $this->whatsappLogs()
            ->whereYear('sent_at', now()->year)
            ->whereMonth('sent_at', now()->month)
            ->count();

        // تم تصحيح الأسطر المكسورة هنا
        $extraMessages = max(0, $sentCount - (int) $this->whatsapp_monthly_limit);
        $extraFee = $extraMessages * (float) $this->whatsapp_extra_rate;

        return [
            'sent_count' => $sentCount,
            'limit' => $this->whatsapp_monthly_limit,
            'extra_messages' => $extraMessages,
            'extra_fee' => $extraFee
        ];
    }

    /* ================= التقييمات والحسابات (Ratings Accessors) ================= */

    public function getGeneralRatingAttribute()
    {
        if ($this->ratings->isEmpty()) {
            return 0;
        }

        return round($this->ratings->avg(function ($rating) {
            $scores = array_filter([
                $rating->transport_rating,
                $rating->staff_rating,
                $rating->organization_rating,
                $rating->price_value_rating,
                $rating->services_rating,
                $rating->accommodation_rating,
                $rating->meals_rating,
            ], fn($val) => !is_null($val) && $val > 0);

            return count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
        }), 1);
    }

    public function getRatingsCountAttribute(): int
    {
        return $this->ratings->count();
    }

    public function getCriteriaAveragesAttribute(): array
    {
        if ($this->ratings->isEmpty()) {
            return [
                'accommodation' => 0,
                'meals'         => 0,
                'staff'         => 0,
                'organization'  => 0,
                'price_value'   => 0,
                'transport'     => 0,
                'services'      => 0
            ];
        }

        return [
            'accommodation' => round($this->ratings->avg('accommodation_rating'), 1),
            'meals'         => round($this->ratings->avg('meals_rating'), 1),
            'staff'         => round($this->ratings->avg('staff_rating'), 1),
            'organization'  => round($this->ratings->avg('organization_rating'), 1),
            'price_value'   => round($this->ratings->avg('price_value_rating'), 1),
            'transport'     => round($this->ratings->avg('transport_rating'), 1),
            'services'      => round($this->ratings->avg('services_rating'), 1),
        ];
    }

    
}
