<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\agency\AgencySubscription;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{
    public function __construct(private SubscriptionService $service) {}

    // ============ صفحة الإدارة ============
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $search = trim((string) $request->query('search', ''));
        $planId = $request->query('plan_id');

        $subscriptions = AgencySubscription::with(['agency:id,name,email', 'plan:id,name'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($planId, fn($q) => $q->where('plan_id', $planId))
            ->when($search !== '', fn($q) => $q->whereHas('agency', function ($a) use ($search) {
                $a->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $current = AgencySubscription::where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());

        $stats = [
            'active'    => (clone $current)->count(),
            'awaiting'  => AgencySubscription::where('status', 'awaiting_payment')->count(),
            'expiring'  => (clone $current)->where('ends_at', '<=', now()->addDays(7))->count(),
            // الإيراد الشهري المتكرر: السنوي يُقسم على 12
            'mrr'       => (clone $current)->get(['billing_cycle', 'price_paid'])
                ->sum(fn($s) => $s->billing_cycle === 'yearly' ? $s->price_paid / 12 : $s->price_paid),
            'collected' => AgencySubscription::whereIn('status', ['active', 'expired', 'cancelled'])
                ->whereMonth('starts_at', now()->month)
                ->whereYear('starts_at', now()->year)
                ->sum('price_paid'),
        ];

        $plans = SubscriptionPlan::withCount(['subscriptions as active_count' => function ($q) {
            $q->where('status', 'active')->where('starts_at', '<=', now())->where('ends_at', '>', now());
        }])->orderBy('sort_order')->orderBy('id')->get();

        $agencies = Agency::orderBy('name')->get(['id', 'name']);

        return view('admin.subscriptions.index', compact('subscriptions', 'stats', 'plans', 'agencies', 'status', 'planId'));
    }

    // ============ الاشتراكات ============
    public function approve(AgencySubscription $subscription)
    {
        if ($subscription->status !== 'pending') {
            return back()->with('error', 'تمت معالجة هذا الطلب مسبقاً.');
        }

        $this->service->activate($subscription, auth()->id());

        return back()->with('success', "تم اعتماد اشتراك ({$subscription->agency->name}) وتفعيل الباقة.");
    }

    public function reject(Request $request, AgencySubscription $subscription)
    {
        if ($subscription->status !== 'pending') {
            return back()->with('error', 'تمت معالجة هذا الطلب مسبقاً.');
        }

        $data = $request->validate(['admin_notes' => 'required|string|max:500'], [
            'admin_notes.required' => 'يرجى كتابة سبب الرفض.',
        ]);

        $subscription->update([
            'status'       => 'rejected',
            'admin_notes'  => $data['admin_notes'],
            'processed_by' => auth()->id(),
        ]);

        return back()->with('success', 'تم رفض طلب الاشتراك.');
    }

    public function extend(Request $request, AgencySubscription $subscription)
    {
        if ($subscription->status !== 'active') {
            return back()->with('error', 'يمكن تمديد الاشتراكات النشطة فقط.');
        }

        $data = $request->validate(['days' => 'required|integer|min:1|max:365']);

        $subscription->update(['ends_at' => $subscription->ends_at->copy()->addDays((int) $data['days'])]);

        return back()->with('success', "تم تمديد الاشتراك {$data['days']} يوم.");
    }

    public function cancel(AgencySubscription $subscription)
    {
        if (!in_array($subscription->status, ['active', 'pending'], true)) {
            return back()->with('error', 'لا يمكن إلغاء هذا الاشتراك.');
        }

        DB::transaction(function () use ($subscription) {
            $wasActive = $subscription->status === 'active';

            $subscription->update([
                'status'       => 'cancelled',
                'cancelled_at' => now(),
                'ends_at'      => $wasActive ? now() : $subscription->ends_at,
                'processed_by' => auth()->id(),
            ]);

            $agency  = $subscription->agency;
            $default = SubscriptionPlan::defaultPlan();
            if ($wasActive && $agency && $default && !$agency->currentSubscription()) {
                $this->service->applyPlanToAgency($agency, $default);
            }
        });

        return back()->with('success', 'تم إلغاء الاشتراك، وعادت الوكالة للباقة الافتراضية.');
    }

    /** تعيين باقة لوكالة مباشرة (بدون طلب) */
    public function assign(Request $request)
    {
        $data = $request->validate([
            'agency_id'     => 'required|exists:agencies,id',
            'plan_id'       => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'price_paid'    => 'nullable|numeric|min:0',
            'ends_at'       => 'nullable|date|after:today',
            'admin_notes'   => 'nullable|string|max:500',
        ]);

        $plan = SubscriptionPlan::findOrFail($data['plan_id']);

        $subscription = AgencySubscription::create([
            'agency_id'      => $data['agency_id'],
            'plan_id'        => $plan->id,
            'status'         => 'pending',
            'billing_cycle'  => $data['billing_cycle'],
            'price_paid'     => $data['price_paid'] ?? $this->service->priceFor($plan, $data['billing_cycle']),
            'payment_method' => 'manual',
            'admin_notes'    => $data['admin_notes'] ?? null,
        ]);

        $subscription = $this->service->activate($subscription, auth()->id());

        if (!empty($data['ends_at'])) {
            $subscription->update(['ends_at' => Carbon::parse($data['ends_at'])->endOfDay()]);
        }

        return back()->with('success', "تم تعيين باقة ({$plan->name}) للوكالة بنجاح.");
    }

    // ============ الباقات ============
    public function storePlan(Request $request)
    {
        $data = $this->validatedPlan($request);

        $plan = SubscriptionPlan::create($data);
        $this->afterPlanSaved($plan);

        return back()->with('success', "تمت إضافة الباقة ({$plan->name}).");
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan)
    {
        $data = $this->validatedPlan($request);

        // الباقة الافتراضية ما تتعطل
        if ($plan->is_default && !$data['is_default']) {
            throw ValidationException::withMessages(['is_default' => 'لازم تكون عندك باقة افتراضية واحدة. عيّن باقة ثانية كافتراضية أولاً.']);
        }

        $plan->update($data);
        $this->afterPlanSaved($plan);
        $this->service->syncPlanToAgencies($plan);

        return back()->with('success', "تم تحديث الباقة ({$plan->name}) وتطبيق إعداداتها على الوكالات المشتركة.");
    }

    public function togglePlan(SubscriptionPlan $plan)
    {
        if ($plan->is_default) {
            return back()->with('error', 'لا يمكن إيقاف الباقة الافتراضية.');
        }

        $plan->update(['is_active' => !$plan->is_active]);

        return back()->with('success', $plan->is_active ? 'تم تفعيل الباقة.' : 'تم إيقاف الباقة (ما تظهر للوكالات للاشتراك الجديد).');
    }

    public function destroyPlan(SubscriptionPlan $plan)
    {
        if ($plan->is_default) {
            return back()->with('error', 'لا يمكن حذف الباقة الافتراضية.');
        }
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'عليها اشتراكات مسجلة، فما تنحذف. أوقفها بدل الحذف.');
        }

        $plan->delete();

        return back()->with('success', 'تم حذف الباقة.');
    }

    private function validatedPlan(Request $request): array
    {
        $data = $request->validate([
            'name'                     => 'required|string|max:100',
            'description'              => 'nullable|string|max:500',
            'monthly_price'            => 'required|numeric|min:0',
            'yearly_price'             => 'nullable|numeric|min:0',
            'max_active_trips'         => 'nullable|integer|min:0',
            'max_vehicles'             => 'nullable|integer|min:0',
            'max_drivers'              => 'nullable|integer|min:0',
            'max_employees'            => 'nullable|integer|min:0',
            'commission_per_passenger' => 'required|numeric|min:0',
            'min_booking_commission'   => 'nullable|numeric|min:0',
            'max_booking_commission'   => 'nullable|numeric|min:0',
            'whatsapp_monthly_limit'   => 'required|integer|min:0',
            'whatsapp_extra_rate'      => 'required|numeric|min:0',
            'sort_order'               => 'nullable|integer|min:0',
        ]);

        $min = $data['min_booking_commission'] ?? null;
        $max = $data['max_booking_commission'] ?? null;
        if ($min !== null && $max !== null && (float) $max < (float) $min) {
            throw ValidationException::withMessages([
                'max_booking_commission' => 'الحد الأعلى للعمولة يجب أن يكون أكبر من أو يساوي الحد الأدنى.',
            ]);
        }

        $data['allows_overflow_bus'] = $request->boolean('allows_overflow_bus');
        $data['is_default']          = $request->boolean('is_default');
        $data['is_active']           = $data['is_default'] ? true : $request->boolean('is_active');
        $data['sort_order']          = $data['sort_order'] ?? 0;

        return $data;
    }

    private function afterPlanSaved(SubscriptionPlan $plan): void
    {
        // باقة افتراضية واحدة فقط
        if ($plan->is_default) {
            SubscriptionPlan::where('id', '!=', $plan->id)->update(['is_default' => false]);
        }
    }
}
