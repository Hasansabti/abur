@extends('layouts.admin')

@section('content')
    @php
        $statusMap = [
            'awaiting_payment' => ['بانتظار الدفع', 'bg-amber-50 text-amber-700 border-amber-200'],
            'pending'   => ['قيد المراجعة', 'bg-amber-50 text-amber-700 border-amber-200'],
            'active'    => ['نشط', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'expired'   => ['منتهي', 'bg-slate-100 text-slate-600 border-slate-200'],
            'cancelled' => ['ملغي', 'bg-rose-50 text-rose-700 border-rose-200'],
            'rejected'  => ['مرفوض', 'bg-rose-50 text-rose-700 border-rose-200'],
        ];
        $cycleMap = ['monthly' => 'شهري', 'yearly' => 'سنوي'];

        $plansJson = $plans->map(fn($p) => $p->only([
            'id', 'name', 'description', 'monthly_price', 'yearly_price',
            'max_active_trips', 'max_vehicles', 'max_drivers', 'max_employees',
            'allows_overflow_bus', 'commission_per_passenger', 'min_booking_commission',
            'max_booking_commission', 'whatsapp_monthly_limit', 'whatsapp_extra_rate',
            'is_default', 'is_active', 'sort_order',
        ]))->values();

        $limitText = fn($v) => $v === null ? 'غير محدود' : $v;
        $inputClass = 'w-full p-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none';
        $labelClass = 'block font-semibold text-slate-700 text-xs mb-1.5';
    @endphp

    <div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6" dir="rtl" x-data="subscriptionsPage(@js($plansJson))">

        {{-- الهيدر --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2 border-b border-slate-200/60">
            <div>
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">إدارة الباقات والاشتراكات</h3>
                <p class="text-xs text-slate-500 mt-1">تعريف الباقات، متابعة اشتراكات الدفع الإلكتروني (ميسر)، وتعيين الباقات للوكالات.</p>
            </div>
            <div class="flex items-center gap-2 self-start md:self-auto shrink-0">
                <button type="button" @click="assignModal = true"
                    class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition-all shadow-sm">
                    🎁 تعيين باقة لوكالة
                </button>
                <button type="button" @click="openPlan(null)"
                    class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition-all shadow-sm">
                    ➕ إضافة باقة
                </button>
            </div>
        </div>

        {{-- المؤشرات --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
                <span class="text-[11px] font-semibold text-slate-400 block mb-1">اشتراكات نشطة</span>
                <span class="text-xl font-bold text-emerald-600 font-mono">{{ $stats['active'] }}</span>
            </div>
            <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/80 shadow-sm">
                <span class="text-[11px] font-bold text-amber-800 block mb-1">بانتظار الدفع</span>
                <span class="text-xl font-bold text-amber-700 font-mono">{{ $stats['awaiting'] }}</span>
            </div>
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
                <span class="text-[11px] font-semibold text-slate-400 block mb-1">تنتهي خلال 7 أيام</span>
                <span class="text-xl font-bold text-rose-600 font-mono">{{ $stats['expiring'] }}</span>
            </div>
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
                <span class="text-[11px] font-semibold text-slate-400 block mb-1">الإيراد الشهري المتكرر</span>
                <span class="text-xl font-bold text-slate-800 font-mono">{{ number_format($stats['mrr'], 2) }}</span>
                <span class="text-xs text-slate-400">ر.س</span>
            </div>
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm col-span-2 lg:col-span-1">
                <span class="text-[11px] font-semibold text-slate-400 block mb-1">محصّل هذا الشهر</span>
                <span class="text-xl font-bold text-slate-800 font-mono">{{ number_format($stats['collected'], 2) }}</span>
                <span class="text-xs text-slate-400">ر.س</span>
            </div>
        </div>

        {{-- التنبيهات --}}
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-200/80 text-sm font-medium shadow-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="p-4 rounded-2xl bg-rose-50 text-rose-700 border border-rose-200/80 text-sm font-medium shadow-sm">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-rose-50 text-rose-700 border border-rose-200/80 text-sm font-medium shadow-sm">
                <span class="font-bold block mb-1">يرجى تصحيح الأخطاء التالية:</span>
                <ul class="list-disc pr-5 text-xs space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- الباقات --}}
        <div class="space-y-3">
            <h4 class="font-bold text-slate-800 text-sm">الباقات المعرّفة</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($plans as $plan)
                    <div class="bg-white border rounded-2xl shadow-sm p-5 flex flex-col gap-3 {{ $plan->is_active ? 'border-slate-200' : 'border-dashed border-slate-300 opacity-70' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h5 class="font-black text-slate-900 text-sm">{{ $plan->name }}</h5>
                                    @if ($plan->is_default)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">افتراضية</span>
                                    @endif
                                    @unless ($plan->is_active)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">موقوفة</span>
                                    @endunless
                                </div>
                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $plan->description }}</p>
                            </div>
                            <span class="text-[11px] font-bold text-slate-500 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 shrink-0">
                                {{ $plan->active_count }} وكالة
                            </span>
                        </div>

                        <div class="flex items-baseline gap-3">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ number_format($plan->monthly_price, 0) }}</span>
                            <span class="text-xs text-slate-400">ر.س / شهر</span>
                            @if ($plan->yearly_price !== null)
                                <span class="text-[11px] text-slate-400 mr-auto">سنوي {{ number_format($plan->yearly_price, 0) }}</span>
                            @endif
                        </div>

                        <ul class="text-[11px] text-slate-600 space-y-1 border-t border-slate-100 pt-3">
                            <li>🧭 الرحلات النشطة: <strong>{{ $limitText($plan->max_active_trips) }}</strong></li>
                            <li>🚌 المركبات: <strong>{{ $limitText($plan->max_vehicles) }}</strong> • 🪪 السائقون: <strong>{{ $limitText($plan->max_drivers) }}</strong> • 👥 الموظفون: <strong>{{ $limitText($plan->max_employees) }}</strong></li>
                            <li>
                                💰 العمولة: <strong>{{ number_format($plan->commission_per_passenger, 2) }}</strong> ر.س/مسافر
                                @if ($plan->min_booking_commission !== null) • أدنى {{ number_format($plan->min_booking_commission, 2) }} @endif
                                @if ($plan->max_booking_commission !== null) • أعلى {{ number_format($plan->max_booking_commission, 2) }} @endif
                            </li>
                            <li>💬 واتساب: <strong>{{ $plan->whatsapp_monthly_limit }}</strong> رسالة/شهر (الزائدة {{ number_format($plan->whatsapp_extra_rate, 2) }} ر.س)</li>
                            <li>🚌➕ الباص الثاني: <strong>{{ $plan->allows_overflow_bus ? 'متاح' : 'غير متاح' }}</strong></li>
                        </ul>

                        <div class="flex items-center gap-2 mt-auto pt-1">
                            <button type="button" @click="openPlan(plansList.find(p => p.id === {{ $plan->id }}))"
                                class="px-3 py-1.5 bg-slate-100 hover:bg-indigo-600 hover:text-white text-slate-700 rounded-lg text-[11px] font-semibold transition-all">تعديل</button>
                            @unless ($plan->is_default)
                                <form method="POST" action="{{ route('admin.subscription-plans.toggle', $plan) }}">
                                    @csrf
                                    <button class="px-3 py-1.5 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-lg text-[11px] font-semibold">
                                        {{ $plan->is_active ? 'إيقاف' : 'تفعيل' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.subscription-plans.destroy', $plan) }}"
                                    onsubmit="return confirm('حذف الباقة نهائياً؟')">
                                    @csrf @method('DELETE')
                                    <button class="px-3 py-1.5 border border-rose-200 text-rose-600 hover:bg-rose-50 rounded-lg text-[11px] font-semibold">حذف</button>
                                </form>
                            @endunless
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- جدول الاشتراكات --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <h4 class="font-bold text-slate-800 text-sm">الاشتراكات
                    <span class="px-2.5 py-0.5 rounded-full bg-slate-200/70 text-slate-600 text-xs font-semibold mr-1">{{ $subscriptions->total() }}</span>
                </h4>

                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="inline-flex p-1 bg-slate-200/60 rounded-xl overflow-x-auto max-w-full shrink-0">
                        @foreach (['active' => 'النشطة', 'awaiting_payment' => 'بانتظار الدفع', 'expired' => 'المنتهية', 'cancelled' => 'الملغاة', 'all' => 'الكل'] as $key => $label)
                            <a href="{{ route('admin.subscriptions', array_filter(['status' => $key, 'plan_id' => $planId, 'search' => request('search')])) }}"
                                class="px-3 py-1 rounded-lg text-xs font-semibold transition-all whitespace-nowrap {{ $status === $key ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>

                    <form method="GET" action="{{ route('admin.subscriptions') }}" class="flex items-center gap-2">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <select name="plan_id" onchange="this.form.submit()"
                            class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-600 outline-none">
                            <option value="">كل الباقات</option>
                            @foreach ($plans as $p)
                                <option value="{{ $p->id }}" {{ (string) $planId === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث باسم الوكالة..."
                            class="w-40 sm:w-52 px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 outline-none focus:border-indigo-500">
                        <button type="submit" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold shrink-0">بحث</button>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[11px] font-semibold text-slate-400 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3.5">#</th>
                            <th class="px-4 py-3.5">الوكالة</th>
                            <th class="px-4 py-3.5">الباقة</th>
                            <th class="px-4 py-3.5">المبلغ</th>
                            <th class="px-4 py-3.5">الفترة</th>
                            <th class="px-4 py-3.5">الحالة</th>
                            <th class="px-4 py-3.5 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($subscriptions as $sub)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-4 font-mono font-bold text-slate-800">#{{ $sub->id }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="font-bold text-slate-900 block">{{ $sub->agency->name ?? 'غير محدد' }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $sub->agency->email ?? '' }}</span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="font-bold text-slate-800">{{ $sub->plan->name ?? '—' }}</span>
                                    <span class="text-[10px] text-slate-400 block">{{ $cycleMap[$sub->billing_cycle] ?? $sub->billing_cycle }}
                                        • {{ $sub->payment_method === 'manual' ? 'تعيين يدوي' : 'ميسر' }}</span>
                                </td>
                                <td class="px-4 py-4 font-bold text-slate-800 whitespace-nowrap">
                                    {{ number_format($sub->price_paid, 2) }} <span class="font-normal text-[10px] text-slate-400">ر.س</span>
                                </td>
                                <td class="px-4 py-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                    @if ($sub->starts_at)
                                        {{ $sub->starts_at->format('Y-m-d') }} ← {{ $sub->ends_at?->format('Y-m-d') }}
                                        @if ($sub->status === 'active' && $sub->daysLeft() !== null)
                                            <span class="block text-[10px] {{ $sub->daysLeft() <= 7 ? 'text-rose-500 font-bold' : 'text-slate-400' }}">متبقي {{ $sub->daysLeft() }} يوم</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">لم يبدأ بعد</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium border {{ $statusMap[$sub->status][1] ?? '' }}">
                                        {{ $statusMap[$sub->status][0] ?? $sub->status }}
                                    </span>
                                    @if ($sub->admin_notes)
                                        <span class="block text-[10px] text-slate-400 mt-1 max-w-[180px] truncate" title="{{ $sub->admin_notes }}">{{ $sub->admin_notes }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-2 flex-wrap">
                                        @if ($sub->moyasar_payment_id)
                                            <span class="px-2 py-1 bg-slate-50 text-slate-500 rounded-lg text-[10px] font-mono border border-slate-200" title="{{ $sub->moyasar_payment_id }}">ميسر: {{ \Illuminate\Support\Str::limit($sub->moyasar_payment_id, 8, '…') }}</span>
                                        @endif

                                        @if ($sub->status === 'active')
                                            <button type="button" @click="extendId = {{ $sub->id }}"
                                                class="px-3 py-1.5 bg-sky-50 text-sky-700 hover:bg-sky-100 rounded-lg font-semibold text-[11px]">تمديد</button>
                                            <form method="POST" action="{{ route('admin.subscriptions.cancel', $sub) }}"
                                                onsubmit="return confirm('إلغاء الاشتراك؟ ترجع الوكالة للباقة الافتراضية فوراً. الاسترداد (إن لزم) يتم يدوياً من لوحة ميسر.')">
                                                @csrf
                                                <button class="px-3 py-1.5 border border-rose-200 text-rose-600 hover:bg-rose-50 rounded-lg font-semibold text-[11px]">إلغاء</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-xs">لا توجد اشتراكات مطابقة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                {{ $subscriptions->links() }}
            </div>
        </div>

        {{-- مودال الباقة (إضافة / تعديل) --}}
        <div x-show="planModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="planModal = false"></div>
                <div class="relative z-10 w-full max-w-2xl p-6 my-8 text-right bg-white shadow-xl rounded-2xl border border-slate-100">
                    <form method="POST"
                        :action="plan.id ? '{{ url('admin/subscription-plans') }}/' + plan.id : '{{ route('admin.subscription-plans.store') }}'">
                        @csrf
                        <template x-if="plan.id"><input type="hidden" name="_method" value="PUT"></template>

                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-sm font-bold text-slate-800" x-text="plan.id ? 'تعديل الباقة' : 'إضافة باقة جديدة'"></h3>
                            <button type="button" @click="planModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                        </div>

                        <div class="py-4 space-y-4 max-h-[70vh] overflow-y-auto pr-1">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="{{ $labelClass }}">اسم الباقة</label>
                                    <input type="text" name="name" required x-model="plan.name" class="{{ $inputClass }}">
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">ترتيب العرض</label>
                                    <input type="number" min="0" name="sort_order" x-model="plan.sort_order" class="{{ $inputClass }}">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="{{ $labelClass }}">وصف مختصر</label>
                                    <input type="text" name="description" x-model="plan.description" class="{{ $inputClass }}">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="{{ $labelClass }}">السعر الشهري (ر.س)</label>
                                    <input type="number" step="0.01" min="0" name="monthly_price" required x-model="plan.monthly_price" class="{{ $inputClass }}">
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">السعر السنوي (ر.س)</label>
                                    <input type="number" step="0.01" min="0" name="yearly_price" x-model="plan.yearly_price" placeholder="فارغ = شهري × 12" class="{{ $inputClass }}">
                                </div>
                            </div>

                            <div>
                                <p class="text-[11px] font-black text-slate-500 mb-2">الحدود <span class="font-medium text-slate-400">(اتركها فارغة = غير محدود)</span></p>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div><label class="{{ $labelClass }}">رحلات نشطة</label>
                                        <input type="number" min="0" name="max_active_trips" x-model="plan.max_active_trips" class="{{ $inputClass }}"></div>
                                    <div><label class="{{ $labelClass }}">مركبات</label>
                                        <input type="number" min="0" name="max_vehicles" x-model="plan.max_vehicles" class="{{ $inputClass }}"></div>
                                    <div><label class="{{ $labelClass }}">سائقون</label>
                                        <input type="number" min="0" name="max_drivers" x-model="plan.max_drivers" class="{{ $inputClass }}"></div>
                                    <div><label class="{{ $labelClass }}">موظفون</label>
                                        <input type="number" min="0" name="max_employees" x-model="plan.max_employees" class="{{ $inputClass }}"></div>
                                </div>
                            </div>

                            <div>
                                <p class="text-[11px] font-black text-slate-500 mb-2">عمولة المنصة على حجوزات الوكالة</p>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div><label class="{{ $labelClass }}">لكل مسافر (ر.س)</label>
                                        <input type="number" step="0.01" min="0" name="commission_per_passenger" required x-model="plan.commission_per_passenger" class="{{ $inputClass }}"></div>
                                    <div><label class="{{ $labelClass }}">الحد الأدنى للحجز</label>
                                        <input type="number" step="0.01" min="0" name="min_booking_commission" x-model="plan.min_booking_commission" placeholder="بدون" class="{{ $inputClass }}"></div>
                                    <div><label class="{{ $labelClass }}">الحد الأعلى للحجز</label>
                                        <input type="number" step="0.01" min="0" name="max_booking_commission" x-model="plan.max_booking_commission" placeholder="بدون" class="{{ $inputClass }}"></div>
                                </div>
                            </div>

                            <div>
                                <p class="text-[11px] font-black text-slate-500 mb-2">رسائل الواتساب</p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="{{ $labelClass }}">الحد الشهري (رسالة)</label>
                                        <input type="number" min="0" name="whatsapp_monthly_limit" required x-model="plan.whatsapp_monthly_limit" class="{{ $inputClass }}"></div>
                                    <div><label class="{{ $labelClass }}">سعر الرسالة الزائدة (ر.س)</label>
                                        <input type="number" step="0.01" min="0" name="whatsapp_extra_rate" required x-model="plan.whatsapp_extra_rate" class="{{ $inputClass }}"></div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-x-6 gap-y-2 pt-1">
                                <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                                    <input type="hidden" name="allows_overflow_bus" value="0">
                                    <input type="checkbox" name="allows_overflow_bus" value="1" x-model="plan.allows_overflow_bus" class="rounded border-slate-300 text-indigo-600">
                                    تتيح ميزة الباص الثاني الاحتياطي
                                </label>
                                <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" x-model="plan.is_active" class="rounded border-slate-300 text-indigo-600">
                                    ظاهرة للوكالات للاشتراك
                                </label>
                                <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                                    <input type="hidden" name="is_default" value="0">
                                    <input type="checkbox" name="is_default" value="1" x-model="plan.is_default" class="rounded border-slate-300 text-indigo-600">
                                    الباقة الافتراضية (المجانية)
                                </label>
                            </div>

                            <p x-show="plan.id" class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-xl p-2.5">
                                حفظ التعديل يطبّق العمولة وحدود الواتساب الجديدة فوراً على كل الوكالات المشتركة في هذه الباقة.
                            </p>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="planModal = false"
                                class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-xs font-medium hover:bg-slate-50">إلغاء</button>
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm">حفظ الباقة</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- مودال تعيين باقة لوكالة --}}
        <div x-show="assignModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="assignModal = false"></div>
                <div class="relative z-10 w-full max-w-md p-6 my-8 text-right bg-white shadow-xl rounded-2xl border border-slate-100">
                    <form method="POST" action="{{ route('admin.subscriptions.assign') }}">
                        @csrf
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-sm font-bold text-slate-800">تعيين باقة لوكالة</h3>
                            <button type="button" @click="assignModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                        </div>

                        <div class="py-4 space-y-3">
                            <div>
                                <label class="{{ $labelClass }}">الوكالة</label>
                                <select name="agency_id" required class="{{ $inputClass }}">
                                    <option value="">اختر الوكالة...</option>
                                    @foreach ($agencies as $a)
                                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="{{ $labelClass }}">الباقة</label>
                                    <select name="plan_id" required class="{{ $inputClass }}">
                                        @foreach ($plans as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">الدورة</label>
                                    <select name="billing_cycle" required class="{{ $inputClass }}">
                                        <option value="monthly">شهري</option>
                                        <option value="yearly">سنوي</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="{{ $labelClass }}">المبلغ (ر.س)</label>
                                    <input type="number" step="0.01" min="0" name="price_paid" placeholder="فارغ = سعر الباقة (0 = هدية)" class="{{ $inputClass }}">
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">تاريخ الانتهاء</label>
                                    <input type="date" name="ends_at" class="{{ $inputClass }}">
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-400 -mt-1">اترك تاريخ الانتهاء فارغاً ليحسب من الدورة (شهر أو سنة). تحديده يتجاوز الحساب.</p>
                            <div>
                                <label class="{{ $labelClass }}">ملاحظة (اختياري)</label>
                                <input type="text" name="admin_notes" maxlength="500" class="{{ $inputClass }}">
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="assignModal = false"
                                class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-xs font-medium hover:bg-slate-50">إلغاء</button>
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm">تعيين وتفعيل</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- مودال التمديد --}}
        <div x-show="extendId !== null" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="extendId = null"></div>
                <div class="relative z-10 w-full max-w-sm p-6 my-8 text-right bg-white shadow-xl rounded-2xl border border-slate-100">
                    <form method="POST" :action="'{{ url('admin/subscriptions') }}/' + extendId + '/extend'">
                        @csrf
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-sm font-bold text-slate-800">تمديد الاشتراك</h3>
                            <button type="button" @click="extendId = null" class="text-slate-400 hover:text-slate-600">✕</button>
                        </div>
                        <div class="py-4">
                            <label class="{{ $labelClass }}">عدد الأيام</label>
                            <input type="number" name="days" min="1" max="365" value="30" required class="{{ $inputClass }}">
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="extendId = null"
                                class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-xs font-medium hover:bg-slate-50">إلغاء</button>
                            <button type="submit"
                                class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold shadow-sm">تمديد</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('subscriptionsPage', (plansList) => ({
                plansList,
                planModal: false,
                assignModal: false,
                extendId: null,
                plan: {},
                blank: {
                    id: null, name: '', description: '', monthly_price: 0, yearly_price: '',
                    max_active_trips: '', max_vehicles: '', max_drivers: '', max_employees: '',
                    allows_overflow_bus: false, commission_per_passenger: 0,
                    min_booking_commission: '', max_booking_commission: '',
                    whatsapp_monthly_limit: 0, whatsapp_extra_rate: 0,
                    is_default: false, is_active: true, sort_order: 0,
                },
                openPlan(p) {
                    this.plan = p ? JSON.parse(JSON.stringify(p)) : { ...this.blank };
                    this.planModal = true;
                },
            }));
        });
    </script>
@endsection