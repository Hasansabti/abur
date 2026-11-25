<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\agency\AgencySubscription;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionPaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    private function agency()
    {
        return auth('agency')->user() ?? auth()->user()->agency;
    }

    public function index(SubscriptionService $service)
    {
        $agency = $this->agency();

        return view('agency.subscription.index', [
            'agency'   => $agency,
            'current'  => $agency->currentSubscription(),
            'plan'     => $agency->currentPlan(),
            'plans'    => SubscriptionPlan::active()->orderBy('sort_order')->orderBy('id')->get(),
            'usage'    => $service->usage($agency),
            'awaiting' => $agency->subscriptions()->where('status', 'awaiting_payment')->with('plan')->latest()->first(),
            'history'  => $agency->subscriptions()->with('plan')->latest()->limit(10)->get(),
            'service'  => $service,
        ]);
    }

    /** إنشاء عملية اشتراك ثم الانتقال لصفحة الدفع */
    public function checkout(Request $request, SubscriptionService $service)
    {
        $data = $request->validate([
            'plan_id'       => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $agency = $this->agency();
        $plan   = SubscriptionPlan::active()->findOrFail($data['plan_id']);
        $price  = $service->priceFor($plan, $data['billing_cycle']);

        if ($plan->is_default || $price <= 0) {
            return back()->with('error', 'هذه الباقة لا تحتاج دفع.');
        }

        // أي عملية سابقة لم يكتمل دفعها تُلغى (عملية واحدة فقط بانتظار الدفع)
        $agency->subscriptions()->where('status', 'awaiting_payment')->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
            'admin_notes'  => 'استبدلت بعملية اشتراك جديدة قبل إكمال الدفع',
        ]);

        $subscription = AgencySubscription::create([
            'agency_id'      => $agency->id,
            'plan_id'        => $plan->id,
            'status'         => 'awaiting_payment',
            'billing_cycle'  => $data['billing_cycle'],
            'price_paid'     => $price,
            'payment_method' => 'moyasar',
        ]);

        return redirect()->route('agency.subscription.pay', $subscription);
    }

    public function pay(AgencySubscription $subscription)
    {
        abort_unless($subscription->agency_id === $this->agency()->id, 403);

        if ($subscription->status !== 'awaiting_payment') {
            return redirect()->route('agency.subscription.index')
                ->with('error', 'هذه العملية لم تعد بانتظار الدفع.');
        }

        $subscription->load('plan', 'agency');

        return view('agency.subscription.pay', compact('subscription'));
    }

    /**
     * رجوع ميسر بعد الدفع (GET: id, status, message).
     * خارج middleware الدخول عمداً: التأكيد يعتمد على التحقق من ميسر لا على الجلسة،
     * فلا يضيع الدفع لو انتهت جلسة الوكالة أثناء الدفع.
     */
    public function callback(Request $request, AgencySubscription $subscription, SubscriptionPaymentService $payments)
    {
        $paymentId = $request->query('id');

        if (!$paymentId) {
            return redirect()->route('agency.subscription.pay', $subscription)
                ->with('error', 'تم إلغاء عملية الدفع قبل اكتمالها، تقدر تحاول مرة أخرى.');
        }

        $result = $payments->confirm($subscription, $paymentId);

        if ($result['ok']) {
            return redirect()->route('agency.subscription.index')->with('success', $result['message']);
        }

        // فشل الدفع والعملية ما زالت بانتظار الدفع: نرجعه لنفس الصفحة ليحاول
        if ($subscription->fresh()->status === 'awaiting_payment') {
            return redirect()->route('agency.subscription.pay', $subscription)->with('error', $result['message']);
        }

        return redirect()->route('agency.subscription.index')->with('error', $result['message']);
    }

    public function cancelCheckout(AgencySubscription $subscription)
    {
        abort_unless($subscription->agency_id === $this->agency()->id, 403);

        if ($subscription->status === 'awaiting_payment') {
            $subscription->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        }

        return redirect()->route('agency.subscription.index')->with('success', 'تم إلغاء عملية الاشتراك.');
    }
}