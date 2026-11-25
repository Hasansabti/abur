<?php

namespace App\Services;

use App\Models\AgencyTransaction;
use App\Models\Booking;

class BookingSettlement
{
    /**
     * عدد المسافرين المعتمد لحساب العمولة (بدون المقاعد الإضافية).
     * غيّره هنا فقط إذا قررت تحسب المقعد الإضافي.
     */
    public function countPassengers(Booking $booking): int
    {
        return $booking->bookingSeats()->where('is_extra', 0)->count();
    }

    /**
     * تسوية حجز مدفوع: حساب العمولة، حفظها على الحجز، وإضافة حركة معلقة لمحفظة الوكالة.
     * آمنة للتكرار: إذا فيه حركة سابقة غير ملغاة ترجعها بدون إنشاء جديدة.
     */
    public function settle(Booking $booking): ?AgencyTransaction
    {
        $booking->loadMissing(['trip.agency', 'agency']);

        $agency   = $booking->agency ?? $booking->trip?->agency;
        $agencyId = $booking->agency_id ?? $booking->trip?->agency_id;

        if (!$agencyId) {
            return null;
        }

        $existing = AgencyTransaction::where('booking_id', $booking->id)
            ->where('type', 'booking_credit')
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($existing) {
            return $existing;
        }

        $passengers = $this->countPassengers($booking);
        $gross      = round((float) $booking->total_amount, 2);

        // العمولة ما تتجاوز قيمة الحجز (حماية لو الحد الأدنى أكبر من حجز صغير)
        $commission = $agency ? $agency->calculateBookingCommission($passengers) : 0.00;
        $commission = min($commission, $gross);
        $net        = round($gross - $commission, 2);

        $booking->update([
            'platform_commission' => $commission,
            'agency_amount'       => $net,
        ]);

        return AgencyTransaction::create([
            'agency_id'         => $agencyId,
            'booking_id'        => $booking->id,
            'type'              => 'booking_credit',
            'amount'            => $net,
            'gross_amount'      => $gross,
            'commission_amount' => $commission,
            'passengers_count'  => $passengers,
            'description'       => "إيراد معلق للحجز رقم (#{$booking->id}) بعد اقتطاع عمولة المنصة لـ ({$passengers}) مسافرين",
            'status'            => 'pending',
        ]);
    }

    /**
     * عكس التسوية (إلغاء/استرداد): تصفير الحركة والعمولة.
     */
    public function reverse(Booking $booking): void
    {
        AgencyTransaction::where('booking_id', $booking->id)
            ->where('type', 'booking_credit')
            ->update([
                'status'            => 'cancelled',
                'amount'            => 0,
                'gross_amount'      => 0,
                'commission_amount' => 0,
            ]);

        $booking->update([
            'platform_commission' => 0,
            'agency_amount'       => 0,
        ]);
    }
}