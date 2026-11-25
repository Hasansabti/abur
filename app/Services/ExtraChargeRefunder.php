<?php

namespace App\Services;

use App\Models\AgencyTransaction;
use App\Models\Booking;
use App\Models\BookingExtraCharge;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExtraChargeRefunder
{
    /**
     * يسترد كل الفواتير الإضافية المدفوعة للحجز، ويلغي غير المدفوعة.
     * يرجع: ['refunded' => float, 'failed' => bool, 'note' => string]
     */
    public function refundAll(Booking $booking): array
    {
        $refunded = 0.0;
        $failed   = false;
        $notes    = [];

        $charges = BookingExtraCharge::where('booking_id', $booking->id)
            ->whereIn('status', ['pending', 'paid', 'refund_failed'])
            ->get();

        foreach ($charges as $charge) {

            // 1) فاتورة لم تُدفع: نلغيها حتى لا يدفعها العميل بعد الإلغاء
            if ($charge->status === 'pending') {
                $this->cancelInvoice($charge->moyasar_invoice_id);
                $charge->update(['status' => 'cancelled']);
                continue;
            }

            // 2) فاتورة مدفوعة: استرداد من ميسر
            if (empty($charge->moyasar_payment_id)) {
                $charge->update(['status' => 'refund_failed']);
                $failed  = true;
                $notes[] = "تعذر استرداد ({$charge->amount}) ريال: لا يوجد رقم دفعة.";
                continue;
            }

            try {
                $res = Http::withBasicAuth(config('services.moyasar.secret_key'), '')
                    ->timeout(20)
                    ->post("https://api.moyasar.com/v1/payments/{$charge->moyasar_payment_id}/refund", [
                        'amount' => (int) round($charge->amount * 100),
                    ]);

                if ($res->successful()) {
                    $charge->update(['status' => 'refunded']);
                    $refunded += (float) $charge->amount;
                } else {
                    Log::error("Extra charge refund #{$charge->id}: " . $res->body());
                    $charge->update(['status' => 'refund_failed']);
                    $failed  = true;
                    $notes[] = "تعذر استرداد ({$charge->amount}) ريال: " . ($res->json('message') ?? 'خطأ من ميسر');
                }
            } catch (\Throwable $e) {
                Log::error("Extra charge refund exception #{$charge->id}: " . $e->getMessage());
                $charge->update(['status' => 'refund_failed']);
                $failed  = true;
                $notes[] = "تعذر استرداد ({$charge->amount}) ريال.";
            }
        }

        // 3) عكس حركة المحفظة فقط لو كل شيء تم استرداده
        if (!$failed) {
            AgencyTransaction::where('booking_id', $booking->id)
                ->where('type', 'extra_charge')
                ->where('status', '!=', 'cancelled')
                ->update(['status' => 'cancelled']);
        }

        $note = '';
        if ($refunded > 0) {
            $note .= " وتم استرداد الفواتير الإضافية ({$refunded}) ريال.";
        }
        if ($notes) {
            $note .= ' ' . implode(' ', $notes);
        }

        return ['refunded' => $refunded, 'failed' => $failed, 'note' => $note];
    }

    private function cancelInvoice(?string $invoiceId): void
    {
        if (!$invoiceId) {
            return;
        }

        try {
            Http::withBasicAuth(config('services.moyasar.secret_key'), '')
                ->timeout(15)
                ->put("https://api.moyasar.com/v1/invoices/{$invoiceId}/cancel");
        } catch (\Throwable $e) {
            Log::warning("Invoice cancel failed {$invoiceId}: " . $e->getMessage());
        }
    }
}