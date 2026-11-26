@extends('layouts.agency')

@section('content')
    @php
        $statusMap = [
            'pending'   => ['قيد المراجعة', 'bg-amber-50 text-amber-700 border-amber-200'],
            'active'    => ['نشط', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'expired'   => ['منتهي', 'bg-slate-100 text-slate-600 border-slate-200'],
            'cancelled' => ['ملغي', 'bg-rose-50 text-rose-700 border-rose-200'],
            'rejected'  => ['مرفوض', 'bg-rose-50 text-rose-700 border-rose-200'],
        ];
        $cycleMap = ['monthly' => 'شهري', 'yearly' => 'سنوي'];

        $limits = [
            ['الرحلات النشطة', 'max_active_trips', 'trips'],
            ['المركبات', 'max_vehicles', 'vehicles'],
            ['السائقون', 'max_drivers', 'drivers'],
            ['الموظفون', 'max_employees', 'employees'],
        ];

        $plansJson = $plans->map(fn($p) => [
            'id'      => $p->id,
            'name'    => $p->name,
            'monthly' => (float) $service->priceFor($p, 'monthly'),
            'yearly'  => (float) $service->priceFor($p, 'yearly'),
        ])->keyBy('id');

        $bank = config('subscriptions.bank');
        $daysLeft = $current?->daysLeft();
    @endphp

    <div class="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto space-y-6" dir="rtl"
        x-data="{ requestModal: false, chosen: null, cycle: 'monthly', plansMap: @js($plansJson),
            open(id) { this.chosen = this.plansMap[id]; this.cycle = 'monthly'; this.requestModal = true; },
            get price() { return this.chosen ? this.chosen[this.cycle] : 0; } }">

        <div>
            <h3 class="text-2xl font-bold text-slate-900 tracking-tight">الاشتراك والباقات</h3>
            <p class="text-xs text-slate-500 mt-1">باقتك الحالية، استهلاكك من الحدود، وترقية أو تجديد الاشتراك.</p>
        </div>

        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-200/80 text-sm font-medium">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="p-4 rounded-2xl bg-rose-50 text-rose-700 border border-rose-200/80 text-sm font-medium">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-rose-50 text-rose-700 border border-rose-200/80 text-sm font-medium">
                <ul class="list-disc pr-5 text-xs space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- تنبيه قرب الانتهاء --}}
        @if ($current && $daysLeft !== null && $daysLeft <= 7)
            <div class="p-4 rounded-2xl bg-amber-50 text-amber-800 border border-amber-200 text-sm font-bold">
                ⏳ ينتهي اشتراكك خلال {{ $daysLeft }} {{ $daysLeft === 1 ? 'يوم' : 'أيام' }}. جدّد قبل الانتهاء لتبقى مميزات باقتك.
            </div>
        @endif

        {{-- طلب قيد المراجعة --}}
        @if ($pending)
            <div class="p-4 rounded-2xl bg-sky-50 border border-sky-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <p class="text-xs font-bold text-sky-800">
                    ⏳ طلب اشتراك في باقة ({{ $pending->plan->name }} - {{ $cycleMap[$pending->billing_cycle] }}) بمبلغ
                    {{ number_format($pending->price_paid, 2) }} ر.س قيد مراجعة الإدارة.
                </p>
                <form method="POST" action="{{ route('agency.subscription.cancel-request', $pending->id) }}"
                    onsubmit="return confirm('إلغاء طلب الاشتراك؟')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-1.5 border border-sky-300 text-sky-700 hover:bg-sky-100 rounded-lg text-[11px] font-bold">إلغاء الطلب</button>
                </form>
            </div>
        @endif

        {{-- الباقة الحالية + الاستهلاك --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="bg-slate-900 text-white rounded-2xl p-6 shadow-sm flex flex-col justify-between gap-4">
                <div>
                    <span class="text-slate-400 text-xs font-medium">باقتك الحالية</span>
                    <div class="text-2xl font-black mt-1">{{ $plan->name ?? 'غير محددة' }}</div>
                    @if ($current)
                        <p class="text-xs text-slate-300 mt-2">
                            {{ $cycleMap[$current->billing_cycle] }} • تنتهي في
                            <span class="font-mono">{{ $current->ends_at->format('Y-m-d') }}</span>
                            ({{ $daysLeft }} يوم)
                        </p>
                    @else
                        <p class="text-xs text-slate-300 mt-2">الباقة المجانية الافتراضية (بدون اشتراك مدفوع)</p>
                    @endif
                </div>
                @if ($plan)
                    <ul class="text-[11px] text-slate-300 space-y-1 border-t border-white/10 pt-3">
                        <li>💰 عمولة المنصة: {{ number_format($plan->commission_per_passenger, 2) }} ر.س/مسافر
                            @if ($plan->min_booking_commission !== null) • أدنى {{ number_format($plan->min_booking_commission, 2) }} @endif
                            @if ($plan->max_booking_commission !== null) • أعلى {{ number_format($plan->max_booking_commission, 2) }} @endif
                        </li>
                        <li>💬 واتساب: {{ $plan->whatsapp_monthly_limit }} رسالة/شهر</li>
                        <li>🚌➕ الباص الثاني: {{ $plan->allows_overflow_bus ? 'متاح' : 'غير متاح' }}</li>
                    </ul>
                @endif
            </div>

            <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <h4 class="font-bold text-slate-800 text-sm mb-4">استهلاكك من حدود الباقة</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    @foreach ($limits as [$label, $key, $usageKey])
                        @php
                            $max = $plan?->{$key};
                            $used = $usage[$usageKey] ?? 0;
                            $pct = ($max === null || $max == 0) ? 0 : min(100, round(($used / $max) * 100));
                            $barColor = $pct >= 100 ? 'bg-rose-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-indigo-600');
                        @endphp
                        <div>
                            <div class="flex justify-between text-xs mb-1.5">
                                <span class="font-bold text-slate-700">{{ $label }}</span>
                                <span class="font-mono text-slate-500">{{ $used }} / {{ $max === null ? '∞' : $max }}</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="h-full {{ $barColor }} rounded-full transition-all" style="width: {{ $max === null ? 8 : $pct }}%"></div>
                            </div>
                            @if ($max !== null && $pct >= 100)
                                <p class="text-[10px] text-rose-500 font-bold mt-1">وصلت للحد، رقّ باقتك للإضافة.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- الباقات المتاحة --}}
        <div class="space-y-3">
            <h4 class="font-bold text-slate-800 text-sm">الباقات المتاحة</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($plans as $p)
                    @php $isCurrent = $plan && $plan->id === $p->id; @endphp
                    <div class="bg-white border rounded-2xl shadow-sm p-5 flex flex-col gap-3 {{ $isCurrent ? 'border-indigo-400 ring-2 ring-indigo-100' : 'border-slate-200' }}">
                        <div class="flex items-center justify-between">
                            <h5 class="font-black text-slate-900">{{ $p->name }}</h5>
                            @if ($isCurrent)
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">باقتك الحالية</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-slate-400 -mt-1">{{ $p->description }}</p>

                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-black text-slate-900 font-mono">{{ $p->monthly_price > 0 ? number_format($p->monthly_price, 0) : 'مجاناً' }}</span>
                            @if ($p->monthly_price > 0)
                                <span class="text-xs text-slate-400">ر.س / شهر</span>
                            @endif
                        </div>
                        @if ($p->monthly_price > 0)
                            <p class="text-[11px] text-slate-400 -mt-2">أو {{ number_format($service->priceFor($p, 'yearly'), 0) }} ر.س سنوياً</p>
                        @endif

                        <ul class="text-[11px] text-slate-600 space-y-1 border-t border-slate-100 pt-3 flex-1">
                            <li>🧭 رحلات نشطة: <strong>{{ $p->max_active_trips ?? 'غير محدود' }}</strong></li>
                            <li>🚌 مركبات: <strong>{{ $p->max_vehicles ?? 'غير محدود' }}</strong> • 🪪 سائقون: <strong>{{ $p->max_drivers ?? 'غير محدود' }}</strong></li>
                            <li>👥 موظفون: <strong>{{ $p->max_employees ?? 'غير محدود' }}</strong></li>
                            <li>💰 عمولة: <strong>{{ number_format($p->commission_per_passenger, 2) }}</strong> ر.س/مسافر
                                @if ($p->min_booking_commission !== null) • أدنى {{ number_format($p->min_booking_commission, 2) }} @endif
                                @if ($p->max_booking_commission !== null) • أعلى {{ number_format($p->max_booking_commission, 2) }} @endif
                            </li>
                            <li>💬 واتساب: <strong>{{ $p->whatsapp_monthly_limit }}</strong> رسالة/شهر</li>
                            <li>🚌➕ الباص الثاني: <strong>{{ $p->allows_overflow_bus ? 'متاح' : 'غير متاح' }}</strong></li>
                        </ul>

                        @if ($p->monthly_price > 0)
                            <button type="button" @click="open({{ $p->id }})" {{ $pending ? 'disabled' : '' }}
                                class="w-full py-2.5 rounded-xl text-xs font-bold transition-all disabled:opacity-40 disabled:cursor-not-allowed
                                    {{ $isCurrent ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-indigo-600 hover:bg-indigo-700 text-white' }}">
                                {{ $isCurrent ? 'تجديد الاشتراك' : 'اشترك في هذه الباقة' }}
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- سجل الاشتراكات --}}
        @if ($history->isNotEmpty())
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h4 class="font-bold text-slate-800 text-sm">سجل الاشتراكات</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[11px] font-semibold text-slate-400 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-3">الباقة</th>
                                <th class="px-4 py-3">المبلغ</th>
                                <th class="px-4 py-3">الفترة</th>
                                <th class="px-4 py-3">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($history as $h)
                                <tr>
                                    <td class="px-4 py-3 font-bold text-slate-800">{{ $h->plan->name }}
                                        <span class="text-[10px] font-normal text-slate-400">({{ $cycleMap[$h->billing_cycle] }})</span></td>
                                    <td class="px-4 py-3 font-bold">{{ number_format($h->price_paid, 2) }} <span class="font-normal text-[10px] text-slate-400">ر.س</span></td>
                                    <td class="px-4 py-3 font-mono text-[11px] text-slate-500">
                                        {{ $h->starts_at ? $h->starts_at->format('Y-m-d') . ' ← ' . $h->ends_at?->format('Y-m-d') : '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-[11px] font-medium border {{ $statusMap[$h->status][1] ?? '' }}">
                                            {{ $statusMap[$h->status][0] ?? $h->status }}
                                        </span>
                                        @if ($h->status === 'rejected' && $h->admin_notes)
                                            <span class="block text-[10px] text-rose-500 mt-1">{{ $h->admin_notes }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- مودال طلب الاشتراك --}}
        <div x-show="requestModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="requestModal = false"></div>
                <div class="relative z-10 w-full max-w-md p-6 my-8 text-right bg-white shadow-xl rounded-2xl border border-slate-100">
                    <form method="POST" action="{{ route('agency.subscription.request') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="plan_id" :value="chosen ? chosen.id : ''">

                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-sm font-bold text-slate-800">الاشتراك في باقة <span x-text="chosen ? chosen.name : ''"></span></h3>
                            <button type="button" @click="requestModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                        </div>

                        <div class="py-4 space-y-4">
                            <div class="grid grid-cols-2 gap-2">
                                <label class="cursor-pointer">
                                    <input type="radio" name="billing_cycle" value="monthly" x-model="cycle" class="sr-only peer">
                                    <span class="block text-center p-3 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600">
                                        شهري<br><span class="font-mono" x-text="chosen ? chosen.monthly : ''"></span> ر.س
                                    </span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="billing_cycle" value="yearly" x-model="cycle" class="sr-only peer">
                                    <span class="block text-center p-3 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600">
                                        سنوي<br><span class="font-mono" x-text="chosen ? chosen.yearly : ''"></span> ر.س
                                    </span>
                                </label>
                            </div>

                            <div class="p-3 bg-indigo-50/60 rounded-xl border border-indigo-100 text-xs text-indigo-900 space-y-1">
                                <p class="font-bold">حوّل <span class="font-mono" x-text="price"></span> ر.س إلى:</p>
                                <p>🏦 {{ $bank['name'] }}</p>
                                <p>👤 {{ $bank['holder'] }}</p>
                                <p class="font-mono dir-ltr text-left" dir="ltr">{{ $bank['iban'] }}</p>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 text-xs mb-1.5">رقم عملية التحويل (اختياري)</label>
                                <input type="text" name="payment_reference" maxlength="100"
                                    class="w-full p-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-800 outline-none focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 text-xs mb-1.5">إيصال التحويل (مطلوب)</label>
                                <input type="file" name="payment_receipt" accept="image/*" required
                                    class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="requestModal = false"
                                class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-xs font-medium hover:bg-slate-50">إلغاء</button>
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm">إرسال الطلب</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection