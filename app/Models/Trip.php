<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;


class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'user_id',                   // معرّف العميل إذا كانت الرحلة خاصة
        'private_trip_request_id',   // معرّف طلب الرحلة الخاصة الأصلي
        'title',
        'from_city',
        'to_city',
        'transport_type',
        'trip_type',
        'route_type',
        'departure_at',
        'arrival_at',
        'return_at',
        'total_seats',
        'available_seats',
        'price',
        'allow_accommodation_only',
        'accommodation_only_discount',
        'cover_image',
        'status',
        'duration_days',
        'vehicle_model',

        'auto_split',
        'is_security_approved',
        'allows_shared_rooms',
        'allow_to_add_seat',
        'split_boundary',
        'is_private',

        'is_conditional',
        'min_seats_required',
        'threshold_deadline_at',

        'features',
        'notes',
        'driver_id',
        'driver2_id',
        'vehicle_id',
        'price_single',
        'rooms_count_1',
        'price_double',
        'rooms_count_2',
        'price_triple',
        'rooms_count_3',
        'price_quad',
        'rooms_count_4',
        'breakfast_price',
        'lunch_price',
        'dinner_price',

        'price_bf_ln',
        'price_ln_dn',
        'price_bf_dn',
        'price_all_meals',

        // حقول الـ JSON 
        'room_options',
        'meal_options',
        'extra_services',
        'extra_bed_price',
        'max_extra_beds',
        'bank_accounts',

        //حق الرحلة الاحتياطية
        'parent_trip_id',
        'overflow_enabled',
        'is_overflow_standby',

    ];

    protected function casts(): array
    {
        return [
            'departure_at' => 'datetime',
            'arrival_at' => 'datetime',
            'return_at' => 'datetime',
            'price' => 'decimal:2',

            'allows_shared_rooms' => 'boolean',
            'is_security_approved' => 'boolean',
            'auto_split' => 'boolean',
            'allow_to_add_seat' => 'boolean',
            'is_conditional' => 'boolean',
            'is_private'     => 'boolean',

            'min_seats_required' => 'integer',
            'threshold_deadline_at' => 'datetime',

            'features' => 'array',
            'bank_accounts' => 'array',

            'price_single' => 'decimal:2',
            'price_double' => 'decimal:2',
            'price_triple' => 'decimal:2',
            'price_quad' => 'decimal:2',

            'room_options' => 'array',
            'meal_options' => 'array',
            'extra_services' => 'array',
            'extra_bed_price' => 'decimal:2',
        ];
    }

    /**
     * حدث ينفذ تلقائياً عند استرجاع أي رحلة لتحديث حالتها لحظياً
     */
    protected static function booted()
    {
        static::retrieved(function ($trip) {
            if ($trip->shouldBeCompleted()) {
                $trip->update(['status' => 'completed']);
            }
        });
    }

    // --- منطق ودوال حساب حالة الرحلة ---

    /**
     * تاريخ ووقت اعتبار الرحلة منتهية (بعد مرور 24 ساعة من تاريخ الوصول أو الإياد)
     */
    public function getCompletionDateAttribute()
    {
        if ($this->return_at) {
            return $this->return_at->copy()->addDay();
        }

        $baseDate = $this->arrival_at ?? $this->departure_at;

        return $baseDate ? $baseDate->copy()->addDay() : null;
    }

    /**
     * التحقق مما إذا كانت الرحلة مستوفية لشرط الانتهاء
     */
    public function shouldBeCompleted(): bool
    {
        if (in_array($this->status, ['completed', 'cancelled', 'تمت الرحلة'])) {
            return false;
        }

        $completionDate = $this->completion_date;

        return $completionDate ? now()->greaterThanOrEqualTo($completionDate) : false;
    }

    /**
     * تحديث حالة الرحلة الحالية
     */
    public function checkAndUpdateStatus(): bool
    {
        if ($this->shouldBeCompleted()) {
            $this->update(['status' => 'completed']);
            return true;
        }

        return false;
    }

    /**
     * نطاق استعلام (Scope) لتحديث الرحلات جماعياً
     */
    public function scopeShouldBeCompleted($query)
    {
        $oneDayAgo = now()->subDay();

        return $query->whereNotIn('status', ['completed', 'cancelled', 'تمت الرحلة'])
            ->where(function ($mainQuery) use ($oneDayAgo) {
                $mainQuery->where(function ($q) use ($oneDayAgo) {
                    $q->whereNotNull('return_at')
                        ->where('return_at', '<=', $oneDayAgo);
                })
                    ->orWhere(function ($q) use ($oneDayAgo) {
                        $q->whereNull('return_at')
                            ->where(function ($sub) use ($oneDayAgo) {
                                $sub->where('arrival_at', '<=', $oneDayAgo)
                                    ->orWhere(function ($s) use ($oneDayAgo) {
                                        $s->whereNull('arrival_at')
                                            ->where('departure_at', '<=', $oneDayAgo);
                                    });
                            });
                    });
            });
    }

    // --- العلاقات ---
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function privateTripRequest(): BelongsTo
    {
        return $this->belongsTo(PrivateTripRequest::class, 'private_trip_request_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class, 'trip_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function secondDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver2_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'trip_staff', 'trip_id', 'staff_id');
    }

    public function ratings()
    {
        return $this->hasMany(\App\Models\AgencyRating::class, 'trip_id');
    }

    public function seatLogs()
    {
        return $this->hasMany(TripSeatLog::class, 'trip_id');
    }

    public function getBookedSeatsCountAttribute(): int
    {
        return $this->total_seats - $this->available_seats;
    }

    public function isThresholdReached(): bool
    {
        if (!$this->is_conditional) {
            return true;
        }
        return $this->booked_seats_count >= $this->min_seats_required;
    }


    //الرحلة الثانية
    public function overflowTrip()
    {
        return $this->hasOne(Trip::class, 'parent_trip_id');
    }

    public function parentTrip()
    {
        return $this->belongsTo(Trip::class, 'parent_trip_id');
    }

    /**
     * تفعيل الباص الثاني (احتياطي → ظاهر للعملاء). آمنة للتكرار والتزامن.
     * ترجع الرحلة الثانية، أو null إذا ما فيه باص احتياطي جاهز للتفعيل.
     */
    public function activateOverflow(string $reason = 'auto', ?string $operator = null): ?Trip
    {
        return DB::transaction(function () use ($reason, $operator) {
            $child = static::where('parent_trip_id', $this->id)
                ->where('is_overflow_standby', true)
                ->lockForUpdate()
                ->first();

            if (!$child) {
                return null;
            }

            $child->update([
                'is_overflow_standby' => false,
                // يرث حالة الأم الحالية، وإذا كانت مسودة يصير بانتظار الاعتماد
                'status'              => $this->status === 'draft' ? 'pending' : $this->status,
            ]);

            $description = $reason === 'manual'
                ? "تم فتح الباص الثاني يدوياً للرحلة ({$this->title}) (رحلة #{$child->id})."
                : "اكتملت مقاعد الرحلة ({$this->title}) فتم تفعيل الباص الثاني تلقائياً (رحلة #{$child->id}).";

            \App\Models\AuditLog::create([
                'agency_id'      => $this->agency_id,
                'trip_id'        => $child->id,
                'action_type'    => 'update',
                'category'       => 'trips',
                'amount_changed' => 0,
                'seats_changed'  => 0,
                'description'    => $description,
                'operator_name'  => $operator ?? 'النظام (تلقائي)',
            ]);

            return $child;
        });
    }

    /**
     * التفعيل التلقائي: فقط إذا امتلأ الباص الأول.
     */
    public function openOverflowIfFull(): bool
    {
        if (!$this->overflow_enabled || (int) $this->available_seats > 0) {
            return false;
        }

        return $this->activateOverflow('auto') !== null;
    }

    /**
     * إغلاق الباص الثاني (ظاهر → احتياطي). مسموح فقط إذا ما فيه أي حجز عليه.
     * ترجع null عند النجاح، أو رسالة الخطأ.
     */
    public function deactivateOverflow(?string $operator = null): ?string
    {
        return DB::transaction(function () use ($operator) {
            $child = static::where('parent_trip_id', $this->id)
                ->where('is_overflow_standby', false)
                ->lockForUpdate()
                ->first();

            if (!$child) {
                return 'الباص الثاني غير مفعّل أصلاً.';
            }

            $hasBookings = \App\Models\Booking::where('trip_id', $child->id)
                ->where('status', '!=', 'cancelled')->exists()
                || \App\Models\BookingSeat::where('trip_id', $child->id)->exists();

            if ($hasBookings) {
                return 'لا يمكن إغلاق الباص الثاني لأن عليه حجوزات.';
            }

            $child->update(['is_overflow_standby' => true, 'status' => 'draft']);

            \App\Models\AuditLog::create([
                'agency_id'      => $this->agency_id,
                'trip_id'        => $child->id,
                'action_type'    => 'update',
                'category'       => 'trips',
                'amount_changed' => 0,
                'seats_changed'  => 0,
                'description'    => "تم إغلاق الباص الثاني للرحلة ({$this->title}) وإرجاعه لوضع احتياطي (رحلة #{$child->id}).",
                'operator_name'  => $operator ?? 'النظام (تلقائي)',
            ]);

            return null;
        });
    }
    // في Trip.php
public function getPublicTitleAttribute(): string
{
    return \Illuminate\Support\Str::replaceLast(' (الباص الثاني)', '', (string) $this->title);
}

public function scopeVisibleToClients($query)
{
    return $query->where('is_overflow_standby', false)
        ->where(function ($q) {
            $q->where('available_seats', '>', 0)
              ->orWhereDoesntHave('overflowTrip', fn ($c) => $c->where('is_overflow_standby', false));
        });
}
}
