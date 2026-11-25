<?php

namespace App\Services;

use App\Models\DiscountCode;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class DiscountCodeService
{
    public function preview(string $code, float $eligibleAmount, ?int $agencyId = null, ?int $tripId = null, ?string $customerPhone = null): array
    {
        $discount = DiscountCode::whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($code))])->first();

        if (!$discount) {
            return ['ok' => false, 'message' => 'كود الخصم غير صالح.'];
        }

        $reason = $this->rejectionReason($discount, $eligibleAmount, $agencyId, $tripId, $customerPhone);
        if ($reason) {
            return ['ok' => false, 'message' => $reason];
        }

        $amount = $this->computeAmount($discount, $eligibleAmount);

        return [
            'ok' => true,
            'amount' => Money::toFloat($amount),
            'code' => $discount->code,
            'type' => $discount->type,
            'discount_id' => $discount->id,
            'message' => 'تم تطبيق كود الخصم.',
        ];
    }

    public function applyForBooking(string $code, $booking, float $eligibleAmount): array
    {
        return DB::transaction(function () use ($code, $booking, $eligibleAmount) {
            $preview = $this->preview(
                $code,
                $eligibleAmount,
                $booking->agency_id,
                $booking->trip_id,
                $booking->customer_phone
            );

            if (!$preview['ok']) {
                return $preview;
            }

            $discount = DiscountCode::lockForUpdate()->find($preview['discount_id']);
            $reason = $this->rejectionReason($discount, $eligibleAmount, $booking->agency_id, $booking->trip_id, $booking->customer_phone);
            if ($reason) {
                return ['ok' => false, 'message' => $reason];
            }

            $amount = Money::toFloat($this->computeAmount($discount, $eligibleAmount));

            $discount->usages()->create([
                'booking_id' => $booking->id,
                'agency_id' => $booking->agency_id,
                'customer_id' => $booking->user_id,
                'customer_phone' => $booking->customer_phone,
                'amount' => $amount,
            ]);

            $discount->increment('uses_count');

            $booking->discount_code = $discount->code;
            $booking->discount_amount = $amount;
            $booking->save();

            $preview['amount'] = $amount;
            return $preview;
        });
    }

    private function rejectionReason(DiscountCode $discount, float $eligibleAmount, ?int $agencyId, ?int $tripId, ?string $customerPhone): ?string
    {
        if (!$discount->is_active) {
            return 'كود الخصم غير مفعّل.';
        }
        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            return 'كود الخصم لم يبدأ بعد.';
        }
        if ($discount->ends_at && $discount->ends_at->isPast()) {
            return 'كود الخصم منتهي الصلاحية.';
        }
        if ($discount->max_uses !== null && $discount->uses_count >= $discount->max_uses) {
            return 'تم استنفاد الحد الأقصى لاستخدام الكود.';
        }
        if (Money::gt($discount->min_booking_amount, $eligibleAmount)) {
            return 'قيمة الحجز أقل من الحد الأدنى للكود.';
        }
        $agencyIds = $discount->agency_ids ?? [];
        if ($agencyIds && $agencyId && !in_array((int) $agencyId, array_map('intval', $agencyIds), true)) {
            return 'هذا الكود غير متاح لوكالتكم.';
        }
        $tripIds = $discount->trip_ids ?? [];
        if ($tripIds && $tripId && !in_array((int) $tripId, array_map('intval', $tripIds), true)) {
            return 'هذا الكود غير متاح لهذه الرحلة.';
        }
        if ($discount->max_uses_per_customer && $customerPhone) {
            $used = $discount->usages()->where('customer_phone', $customerPhone)->count();
            if ($used >= $discount->max_uses_per_customer) {
                return 'تجاوز العميل الحد المسموح لاستخدام هذا الكود.';
            }
        }

        return null;
    }

    private function computeAmount(DiscountCode $discount, float $eligibleAmount): string
    {
        if ($discount->type === 'percent') {
            $raw = Money::mul($eligibleAmount, Money::of($discount->value / 100));
        } else {
            $raw = Money::of($discount->value);
        }

        if ($discount->max_discount) {
            $raw = Money::min($raw, $discount->max_discount);
        }

        return Money::min($raw, $eligibleAmount);
    }
}
