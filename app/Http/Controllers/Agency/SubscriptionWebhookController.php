<?php

namespace App\Http\Controllers;

use App\Models\agency\AgencySubscription;
use App\Services\SubscriptionPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionWebhookController extends Controller
{
    public function moyasar(Request $request, SubscriptionPaymentService $payments)
    {
        $secret = config('services.moyasar.webhook_secret');

        // ميسر يرسل secret_token داخل الـ body
        if (empty($secret) || !hash_equals((string) $secret, (string) $request->input('secret_token'))) {
            abort(403);
        }

        if ($request->input('type') !== 'payment_paid') {
            return response()->json(['ok' => true]);
        }

        $data      = $request->input('data', []);
        $paymentId = $data['id'] ?? null;
        $subId     = $data['metadata']['subscription_id'] ?? null;

        // دفعات ثانية (حجوزات العملاء مثلاً) ما لها علاقة بهذي النقطة: نردّ 200 بدون عمل شيء
        if (!$paymentId || !$subId) {
            return response()->json(['ok' => true]);
        }

        if ($subscription = AgencySubscription::find($subId)) {
            $result = $payments->confirm($subscription, $paymentId);

            if (!$result['ok']) {
                Log::warning("Moyasar webhook: تعذر تفعيل اشتراك #{$subId} للدفعة {$paymentId}: {$result['message']}");
            }
        }

        // لازم 2xx بسرعة، وإلا ميسر يعيد الإرسال 5 مرات
        return response()->json(['ok' => true]);
    }
}