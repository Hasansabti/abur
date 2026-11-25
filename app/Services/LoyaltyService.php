<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CustomerPointTransaction;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoyaltyService
{
    public function balance(User $user): int
    {
        return (int) ($user->loyalty_points ?? 0);
    }

    public function redeem(User $user, Booking $booking, int $points, string $notes, string $actor, string $reference = null): CustomerPointTransaction
    {
        if ($points <= 0) {
            throw new \InvalidArgumentException('عدد النقاط غير صالح.');
        }

        $rate = (float) config('bookings.loyalty.sar_per_point', 1);
        $value = Money::toFloat(Money::mul($points, $rate));
        $maxPercent = (float) config('bookings.loyalty.max_redeem_percent', 100);
        $maxValue = Money::toFloat(Money::mul($booking->total_amount, $maxPercent / 100));
        if (Money::gt($value, $maxValue)) {
            throw new \InvalidArgumentException('تجاوز الحد الأعلى لصرف النقاط على هذا الحجز.');
        }

        return DB::transaction(function () use ($user, $booking, $points, $value, $notes, $actor, $reference) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
            $balance = (int) $lockedUser->loyalty_points;
            if ($points > $balance) {
                throw new \InvalidArgumentException('رصيد النقاط غير كافٍ.');
            }

            $ref = $reference ?: ('PTS-' . $booking->id . '-' . Str::uuid());
            if (CustomerPointTransaction::where('reference', $ref)->exists()) {
                return CustomerPointTransaction::where('reference', $ref)->firstOrFail();
            }

            $lockedUser->loyalty_points = $balance - $points;
            $lockedUser->save();

            $tx = CustomerPointTransaction::create([
                'customer_id' => $lockedUser->id,
                'agency_id' => $booking->agency_id,
                'booking_id' => $booking->id,
                'transaction_type' => 'redeem',
                'points' => -$points,
                'monetary_value' => $value,
                'balance_before' => $balance,
                'balance_after' => $lockedUser->loyalty_points,
                'reference' => $ref,
                'notes' => $notes,
                'created_by' => $actor,
            ]);

            $booking->points_redeemed = (int) $booking->points_redeemed + $points;
            $booking->points_value = Money::toFloat(Money::add($booking->points_value, $value));
            $booking->total_amount = Money::max(0, Money::sub($booking->total_amount, $value));
            $booking->save();

            return $tx;
        });
    }

    public function refund(Booking $booking, string $actor): ?CustomerPointTransaction
    {
        if (!config('bookings.loyalty.refund_on_cancel', true) || (int) $booking->points_redeemed <= 0 || !$booking->user_id) {
            return null;
        }

        $user = User::find($booking->user_id);
        if (!$user) {
            return null;
        }

        return DB::transaction(function () use ($user, $booking, $actor) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
            $points = (int) $booking->points_redeemed;
            $ref = 'PTS-REFUND-' . $booking->id;
            if (CustomerPointTransaction::where('reference', $ref)->exists()) {
                return CustomerPointTransaction::where('reference', $ref)->first();
            }

            $before = (int) $lockedUser->loyalty_points;
            $lockedUser->loyalty_points = $before + $points;
            $lockedUser->save();

            return CustomerPointTransaction::create([
                'customer_id' => $lockedUser->id,
                'agency_id' => $booking->agency_id,
                'booking_id' => $booking->id,
                'transaction_type' => 'refund',
                'points' => $points,
                'monetary_value' => $booking->points_value,
                'balance_before' => $before,
                'balance_after' => $lockedUser->loyalty_points,
                'reference' => $ref,
                'notes' => 'استرجاع نقاط عند إلغاء الحجز',
                'created_by' => $actor,
            ]);
        });
    }
}
