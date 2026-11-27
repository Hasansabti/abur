<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingSeat extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'trip_id',
        'trip_room_id',
        'seat_number',
        'passenger_name',
        'passenger_national_id',
        'gender',
        'qr_code_path',
        'room_type',

        'with_breakfast',
        'with_lunch',
        'with_dinner',
        'breakfast_price',
        'lunch_price',
        'dinner_price',

        'room_options',
        'meal_options',
        'extra_services',

        'parent_seat_id',
        'is_extra',
        'nationality_code',
        'identity_version',
        'identity_issue_place',
        'identity_expiry_date',
        'date_of_birth',
        'companion_id',
        'bed_type',
        'extra_bed_quantity',
        'extra_bed_unit_price',
        'shared_room_type',

    ];

    protected function casts(): array
    {
        return [
            'with_breakfast'  => 'boolean',
            'with_lunch'      => 'boolean',
            'with_dinner'     => 'boolean',

            'breakfast_price' => 'decimal:2',
            'lunch_price'     => 'decimal:2',
            'dinner_price'    => 'decimal:2',

            'seat_number'     => 'array',
            'room_options'    => 'array',
            'meal_options'    => 'array',
            'extra_services'  => 'array',
            'is_extra'        => 'boolean',
            'identity_expiry_date' => 'date',
            'date_of_birth' => 'date',
            'extra_bed_quantity' => 'integer',
            'extra_bed_unit_price' => 'decimal:2',
        ];
    }

    public function isActualGuest(): bool
    {
        return !$this->is_extra && empty($this->parent_seat_id) && $this->room_type !== 'additional';
    }

    public function getTotalExtraServicesPriceAttribute(): float
    {
        if (empty($this->extra_services) || !is_array($this->extra_services)) {
            return 0.00;
        }

        $total = 0.00;
        foreach ($this->extra_services as $service) {
            $total += floatval($service['price'] ?? $service['cost'] ?? 0);
        }

        return $total;
    }
    // المقعد الإضافي يتبع مقعد أساسي (الأب)
    public function parentSeat(): BelongsTo
    {
        return $this->belongsTo(BookingSeat::class, 'parent_seat_id');
    }

    // المقعد الأساسي قد يكون له عدة مقاعد إضافية (الأبناء)
    public function extraSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class, 'parent_seat_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'passenger_national_id', 'national_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(TripRoom::class, 'trip_room_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TripSeatLog::class, 'booking_seat_id');
    }

    public function companion(): BelongsTo
    {
        return $this->belongsTo(Companion::class);
    }

    public function nationalityLabel(): ?string
    {
        return \App\Support\Nationalities::label($this->nationality_code);
    }
}
