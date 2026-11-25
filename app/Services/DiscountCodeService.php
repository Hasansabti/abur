<?php

namespace App\Services;

use App\Models\DiscountCode;
use App\Models\DiscountCodeUsage;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DiscountCodeService
{
    public function preview(string $code, int $agencyId, float $eligibleAmount, ?int $tripId = null, ?string $customerKey = null): array
    {
        $discount = $this->findUsable($code, $agencyId, $tripId, $customerKey, $eligibleAmount);

        $amount = $this->computeAmount($discount, $eligibleAmount);

        return [
            'ok' => true,
            'discount_code_id' => $discount->id,
            'code' => $discount->code,
            'type' => $discount->type,
            'value' => (float) $discount->value,
            'amount' => Money::toFloat($amount),
        ];
    }

    public function applyOnBooking($booking, string $code, float $eligibleAmount, ?string $customerKey = null): array
    {
        return DB::transaction(function () use ($booking, $code, $eligibleAmount, $customerKey) {
            $agencyId = $booking->agency_id ?? $booking->trip?->agency_id;
            $discount = $this->findUsable($code, $agencyId, $booking->trip_id, $customerKey, $eligibleAmount, true);
            $amount = $this->computeAmount($discount, $eligibleAmount);

            DiscountCodeUsage::updateOrCreate(
                [
                    'discount_code_id' => $discount->id,
                    'booking_id' => $booking->id,
                ],
                [
                    'agency_id' => $agencyId,
                    'customer_key' => $customerKey,
                    'amount' => $amount,
                ]
            );

            $booking->discount_code_id = $discount->id;
            $booking->discount_code = $discount->code;
            $booking->discount_amount = $amount;
            $booking->save();

            return [
                'amount' => Money::toFloat($amount),
                'code' => $discount->code,
                'id' => $discount->id,
            ];
        });
    }

    public function releaseOnCancel($booking): void
    {
        if (!config('booking.refund_discount_code_on_cancel', true)) {
            return;
        }

        DiscountCodeUsage::where('booking_id', $booking->id)->delete();
    }

    protected function findUsable(string $code, int $agencyId, ?int $tripId, ?string $customerKey, float $eligibleAmount, bool $lock = false)
    {
        $query = DiscountCode::query()
            ->whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($code))])
            ->where(function ($q) use ($agencyId) {
                $q->whereNull('agency_id')->orWhere('agency_id', $agencyId);
            });

        if ($lock) {
            $query->lockForUpdate();
        }

        $discount = $query->first();

        if (!$discount || !$discount->is_active) {
            throw ValidationException::withMessages(['discount_code' => 'كود الخصم غير صالح أو غير مفعّل.']);
        }

        $now = now();
        if ($discount->starts_at && $now->lt($discount->starts_at)) {
            throw ValidationException::withMessages(['discount_code' => 'كود الخصم لم يبدأ بعد.']);
        }
        if ($discount->ends_at && $now->gt($discount->ends_at)) {
            throw ValidationException::withMessages(['discount_code' => 'كود الخصم منتهي الصلاحية.']);
        }

        if ($discount->min_booking_amount && $eligibleAmount < (float) $discount->min_booking_amount) {
            throw ValidationException::withMessages(['discount_code' => 'قيمة الحجز أقل من الحد الأدنى لاستخدام الكود.']);
        }

        $tripIds = $discount->eligible_trip_ids ?? [];
        if (!empty($tripIds) && $tripId && !in_array($tripId, $tripIds, false) && !in_array((string) $tripId, $tripIds, true)) {
            throw ValidationException::withMessages(['discount_code' => 'هذا الكود غير مؤهل لهذه الرحلة.']);
        }

        if ($discount->max_uses !== null) {
            $used = DiscountCodeUsage::where('discount_code_id', $discount->id)->count();
            if ($used >= $discount->max_uses) {
                throw ValidationException::withMessages(['discount_code' => 'تم استنفاد الحد الأقصى لاستخدام الكود.']);
            }
        }

        if ($discount->max_uses_per_customer && $customerKey) {
            $usedByCustomer = DiscountCodeUsage::where('discount_code_id', $discount->id)
                ->where('customer_key', $customerKey)
                ->count();
            if ($usedByCustomer >= $discount->max_uses_per_customer) {
                throw ValidationException::withMessages(['discount_code' => 'تجاوز هذا العميل حد استخدام الكود.']);
            }
        }

        return $discount;
    }

    protected function computeAmount(DiscountCode $discount, float $eligibleAmount): string
    {
        if ($discount->type === 'percent') {
            $amount = Money::percent($eligibleAmount, $discount->value);
        } else {
            $amount = Money::fromCents(Money::toCents($discount->value));
        }

        if ($discount->max_discount) {
            $amount = Money::min($amount, $discount->max_discount);
        }

        return Money::min($amount, $eligibleAmount);
    }
}
