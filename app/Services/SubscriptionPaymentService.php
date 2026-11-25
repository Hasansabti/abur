<?php

namespace App\Services;

use App\Models\agency\AgencySubscription;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubscriptionPaymentService
{
    public function __construct(private SubscriptionService $subscriptions) {}

    /**
     * تأكيد دفعة ميسر لاشتراك وتفعيله. آمنة للتكرار (callback + webhook).
     * ترجع ['ok' => bool, 'message' => string]
     */
    public function confirm(AgencySubscription $sub, string $paymentId): array
    {
        // استدعاء مكرر لنفس الدفعة بعد التفعيل
        if ($sub->status === 'active' && $sub->moyasar_payment_id === $paymentId) {
            return ['ok' => true, 'message' => 'تم تفعيل اشتراكك مسبقاً.'];
        }

        if ($sub->status !== 'awaiting_payment') {
            return ['ok' => false, 'message' => 'هذه العملية لم تعد بانتظار الدفع.'];
        }

        // دفعة واحدة = اشتراك واحد
        if (AgencySubscription::where('moyasar_payment_id', $paymentId)->where('id', '!=', $sub->id)->exists()) {
            return ['ok' => false, 'message' => 'رقم الدفعة مستخدم في عملية أخرى.'];
        }

        $secret = config('services.moyasar.secret_key');
        if (empty($secret)) {
            Log::error('MOYASAR_SECRET_KEY مفقود (اشتراكات).');
            return ['ok' => false, 'message' => 'خطأ في إعدادات بوابة الدفع.'];
        }

        // الحالة والمبلغ دائماً من ميسر مباشرة، لا من الرابط ولا من الـ webhook
        $response = Http::withBasicAuth($secret, '')->get("https://api.moyasar.com/v1/payments/{$paymentId}");
        if (!$response->successful()) {
            Log::error('Moyasar fetch failed (subscription): ' . $response->body());
            return ['ok' => false, 'message' => 'تعذر التحقق من الدفعة، حاول مرة أخرى.'];
        }

        $payment = $response->json();

        if (($payment['status'] ?? '') !== 'paid') {
            return ['ok' => false, 'message' => $payment['source']['message'] ?? 'لم تكتمل عملية الدفع.'];
        }

        $expected = (int) round(((float) $sub->price_paid) * 100);
        if ((int) ($payment['amount'] ?? 0) !== $expected || strtoupper($payment['currency'] ?? '') !== 'SAR') {
            Log::warning("Subscription #{$sub->id}: مبلغ الدفعة {$paymentId} لا يطابق المطلوب ({$expected}).");
            return ['ok' => false, 'message' => 'المبلغ المدفوع لا يطابق قيمة الاشتراك.'];
        }

        if ((string) ($payment['metadata']['subscription_id'] ?? '') !== (string) $sub->id) {
            Log::warning("Subscription #{$sub->id}: الدفعة {$paymentId} غير مربوطة بهذا الاشتراك.");
            return ['ok' => false, 'message' => 'الدفعة غير مرتبطة بهذا الاشتراك.'];
        }

        try {
            DB::transaction(function () use ($sub, $paymentId) {
                $locked = AgencySubscription::lockForUpdate()->find($sub->id);

                if ($locked->status !== 'awaiting_payment') {
                    return;
                }

                $locked->update([
                    'moyasar_payment_id' => $paymentId,
                    'payment_method'     => 'moyasar',
                    'paid_at'            => now(),
                ]);

                $this->subscriptions->activate($locked);
            });
        } catch (QueryException $e) {
            // سباق مع callback/webhook متزامن على نفس الدفعة
            Log::info("Subscription #{$sub->id}: تعارض عند التأكيد: " . $e->getMessage());
        }

        return $sub->fresh()->status === 'active'
            ? ['ok' => true, 'message' => 'تم الدفع وتفعيل اشتراكك بنجاح ✨']
            : ['ok' => false, 'message' => 'تعذر تفعيل الاشتراك، تواصل مع الدعم برقم الاشتراك #' . $sub->id];
    }
}