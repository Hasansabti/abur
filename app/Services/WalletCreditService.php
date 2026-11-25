<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\AgencyTransaction;
use App\Models\Booking;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletCreditService
{
    public function creditAgency(Agency $agency, float $amount, string $method, ?string $reference, ?string $reason, ?Booking $booking = null, ?string $idempotencyKey = null): AgencyTransaction
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'مبلغ الإضافة غير صالح.']);
        }

        return DB::transaction(function () use ($agency, $amount, $method, $reference, $reason, $booking, $idempotencyKey) {
            if ($idempotencyKey) {
                $existing = AgencyTransaction::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing;
                }
            }

            $trx = AgencyTransaction::create([
                'agency_id' => $agency->id,
                'booking_id' => $booking?->id,
                'type' => 'manual_credit',
                'amount' => Money::fromCents(Money::toCents($amount)),
                'status' => 'available',
                'description' => trim(($reason ?: 'إضافة رصيد يدوية') . ' | طريقة الاستلام: ' . $method . ($reference ? ' | مرجع: ' . $reference : '')),
                'idempotency_key' => $idempotencyKey,
                'reference' => $reference,
            ]);

            if ($booking) {
                BookingChangeLogger::log($booking, 'add_balance', [], [
                    'amount' => $amount,
                    'method' => $method,
                    'wallet' => 'agency',
                ], 'wallet', $trx->id, $reason);
            }

            return $trx;
        });
    }
}
