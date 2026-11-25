<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CustomerPointTransaction;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PointsService
{
    public function redeem(User $customer, Booking $booking, int $points, ?string $reason = null, ?string $idempotencyKey = null): CustomerPointTransaction
    {
        if ($points <= 0) {
            throw ValidationException::withMessages(['points' => 'عدد النقاط غير صالح.']);
        }

        $valuePerPoint = (float) config('booking.points_value', 0.01);
        $maxRedemption = config('booking.points_max_redemption');

        return DB::transaction(function () use ($customer, $booking, $points, $reason, $idempotencyKey, $valuePerPoint, $maxRedemption) {
            if ($idempotencyKey) {
                $existing = CustomerPointTransaction::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing;
                }
            }

            $user = User::where('id', $customer->id)->lockForUpdate()->first();
            $balance = (int) ($user->points_balance ?? 0);
            if ($points > $balance) {
                throw ValidationException::withMessages(['points' => 'رصيد النقاط غير كافٍ.']);
            }

            if ($maxRedemption !== null && $points > (int) $maxRedemption) {
                throw ValidationException::withMessages(['points' => 'تجاوز الحد الأعلى لصرف النقاط.']);
            }

            $monetary = Money::mul($valuePerPoint, $points);
            $newBalance = $balance - $points;

            $user->points_balance = $newBalance;
            $user->save();

            $trx = CustomerPointTransaction::create([
                'customer_id' => $user->id,
                'agency_id' => $booking->agency_id ?? $booking->trip?->agency_id,
                'booking_id' => $booking->id,
                'transaction_type' => 'redeem',
                'points' => -$points,
                'monetary_value' => $monetary,
                'balance_before' => $balance,
                'balance_after' => $newBalance,
                'reference' => 'BK-' . $booking->id,
                'notes' => $reason,
                'created_by' => BookingChangeLogger::actor()['name'],
                'idempotency_key' => $idempotencyKey,
            ]);

            $booking->points_redeemed = (int) $booking->points_redeemed + $points;
            $booking->points_value = Money::add($booking->points_value ?? 0, $monetary);
            $booking->total_amount = Money::maxZero(Money::sub($booking->total_amount, $monetary));
            $booking->save();

            BookingChangeLogger::log($booking, 'redeem_points', [
                'points' => $balance,
            ], [
                'points' => $newBalance,
                'value' => $monetary,
            ], 'points', $trx->id, $reason);

            return $trx;
        });
    }

    public function refundForBooking(Booking $booking, ?string $reason = null): ?CustomerPointTransaction
    {
        if (!config('booking.refund_points_on_cancel', true)) {
            return null;
        }

        $redeemed = CustomerPointTransaction::where('booking_id', $booking->id)
            ->where('transaction_type', 'redeem')
            ->get();

        if ($redeemed->isEmpty()) {
            return null;
        }

        $alreadyRefunded = CustomerPointTransaction::where('booking_id', $booking->id)
            ->where('transaction_type', 'refund')
            ->exists();
        if ($alreadyRefunded) {
            return null;
        }

        $points = abs((int) $redeemed->sum('points'));
        if ($points <= 0) {
            return null;
        }

        return DB::transaction(function () use ($booking, $points, $reason, $redeemed) {
            $user = User::where('id', $booking->user_id)->lockForUpdate()->first();
            if (!$user) {
                return null;
            }
            $balance = (int) ($user->points_balance ?? 0);
            $user->points_balance = $balance + $points;
            $user->save();

            return CustomerPointTransaction::create([
                'customer_id' => $user->id,
                'agency_id' => $booking->agency_id,
                'booking_id' => $booking->id,
                'transaction_type' => 'refund',
                'points' => $points,
                'monetary_value' => $redeemed->sum('monetary_value'),
                'balance_before' => $balance,
                'balance_after' => $balance + $points,
                'reference' => 'BK-' . $booking->id,
                'notes' => $reason ?: 'استرجاع نقاط بسبب إلغاء/تعديل الحجز',
                'created_by' => BookingChangeLogger::actor()['name'],
                'idempotency_key' => 'refund-points-booking-' . $booking->id,
            ]);
        });
    }
}
