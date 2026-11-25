<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\AgencyEmployee;
use App\Models\agency\AgencySubscription;
use App\Models\Driver;
use App\Models\SubscriptionPlan;
use App\Models\Trip;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function priceFor(SubscriptionPlan $plan, string $cycle): float
    {
        return $cycle === 'yearly'
            ? (float) ($plan->yearly_price ?? ((float) $plan->monthly_price * 12))
            : (float) $plan->monthly_price;
    }

    private function endsAt(Carbon $from, string $cycle): Carbon
    {
        return $cycle === 'yearly' ? $from->copy()->addYear() : $from->copy()->addMonth();
    }

    /**
     * تفعيل اشتراك (pending → active).
     * - نفس باقة الاشتراك الحالي: تجديد يبدأ بعد نهاية الحالي (لا تضيع أيام مدفوعة).
     * - باقة مختلفة: تحل محل الحالية فوراً (بدون استرجاع أو احتساب نسبي).
     */
    public function activate(AgencySubscription $sub, ?int $adminId = null): AgencySubscription
    {
        return DB::transaction(function () use ($sub, $adminId) {
            $agency  = Agency::lockForUpdate()->findOrFail($sub->agency_id);
            $plan    = $sub->plan;
            $current = $agency->currentSubscription();
            $starts  = now();

            if ($current && $current->id !== $sub->id) {
                if ($current->plan_id === $plan->id) {
                    $latestEnd = $agency->subscriptions()
                        ->where('status', 'active')
                        ->where('plan_id', $plan->id)
                        ->where('id', '!=', $sub->id)
                        ->max('ends_at');
                    $starts = Carbon::parse($latestEnd ?: $current->ends_at);
                } else {
                    $current->update([
                        'status'       => 'cancelled',
                        'cancelled_at' => now(),
                        'ends_at'      => now(),
                        'admin_notes'  => trim(($current->admin_notes ?? '') . "\nتم استبدالها بباقة {$plan->name}"),
                    ]);
                }
            }

            $sub->update([
                'status'       => 'active',
                'starts_at'    => $starts,
                'ends_at'      => $this->endsAt($starts, $sub->billing_cycle),
                'processed_by' => $adminId,
            ]);

            if ($starts->lte(now())) {
                $this->applyPlanToAgency($agency, $plan);
            }

            return $sub->fresh();
        });
    }

    /** نسخ إعدادات الباقة (العمولة + الواتساب) لحقول الوكالة */
    public function applyPlanToAgency(Agency $agency, SubscriptionPlan $plan): void
    {
        $agency->update([
            'commission_per_passenger' => $plan->commission_per_passenger,
            'min_booking_commission'   => $plan->min_booking_commission,
            'max_booking_commission'   => $plan->max_booking_commission,
            'whatsapp_monthly_limit'   => $plan->whatsapp_monthly_limit,
            'whatsapp_extra_rate'      => $plan->whatsapp_extra_rate,
        ]);
    }

    /** بعد تعديل باقة: نعيد تطبيق إعداداتها على الوكالات المرتبطة بها حالياً */
    public function syncPlanToAgencies(SubscriptionPlan $plan): void
    {
        $agencyIds = AgencySubscription::where('plan_id', $plan->id)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->pluck('agency_id');

        Agency::whereIn('id', $agencyIds)->each(fn (Agency $a) => $this->applyPlanToAgency($a, $plan));
    }

    /** إنهاء الاشتراكات المنتهية وإرجاع الوكالة للباقة الافتراضية إذا ما بقي لها اشتراك ساري */
    public function expireDue(): int
    {
        $count   = 0;
        $default = SubscriptionPlan::defaultPlan();

        AgencySubscription::where('status', 'active')
            ->where('ends_at', '<=', now())
            ->chunkById(100, function ($rows) use (&$count, $default) {
                foreach ($rows as $sub) {
                    $sub->update(['status' => 'expired']);
                    $count++;

                    $agency = $sub->agency;
                    if ($agency && $default && !$agency->currentSubscription()) {
                        $this->applyPlanToAgency($agency, $default);
                    }
                }
            });

        return $count;
    }

    // ===================== الاستهلاك والحدود =====================

    public function activeTripsCount(Agency $agency): int
    {
        return Trip::where('agency_id', $agency->id)
            ->where('is_overflow_standby', false)
            ->whereNotIn('status', ['draft', 'cancelled', 'completed', 'تمت الرحلة', 'rejected'])
            ->where('departure_at', '>=', now())
            ->count();
    }

    public function usage(Agency $agency): array
    {
        return [
            'trips'     => $this->activeTripsCount($agency),
            'vehicles'  => Vehicle::where('agency_id', $agency->id)->count(),
            'drivers'   => Driver::where('agency_id', $agency->id)->count(),
            'employees' => AgencyEmployee::where('agency_id', $agency->id)->count(),
        ];
    }

    /** رسالة خطأ إذا وصلت الوكالة للحد، وإلا null */
    public function limitError(Agency $agency, string $limitKey, int $current, string $label): ?string
    {
        $plan = $agency->currentPlan();
        $max  = $plan?->{$limitKey};

        if ($max === null) {
            return null;
        }

        return $current >= $max
            ? "وصلت للحد الأقصى ({$max}) من {$label} في باقتك ({$plan->name}). رقّ باقتك للمتابعة."
            : null;
    }

    /** فحص نشر رحلة جديدة (مو مسودة) */
    public function publishError(Agency $agency, bool $wantsOverflow = false, bool $countsAsNew = true): ?string
    {
        $plan = $agency->currentPlan();
        if (!$plan) {
            return null;
        }

        if ($countsAsNew) {
            $error = $this->limitError($agency, 'max_active_trips', $this->activeTripsCount($agency), 'الرحلات النشطة');
            if ($error) {
                return $error;
            }
        }

        if ($wantsOverflow && !$plan->allows_overflow_bus) {
            return "ميزة الباص الثاني الاحتياطي غير متاحة في باقتك ({$plan->name}). رقّ باقتك لاستخدامها.";
        }

        return null;
    }
}