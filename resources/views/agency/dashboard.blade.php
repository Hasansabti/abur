@extends('layouts.agency')

@section('content')

    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  1) تجهيز البيانات والحسابات                                  ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    @php
        // مجموعات افتراضية لتفادي الأخطاء إذا لم تُمرَّر من الكنترولر
        $trips    = $trips    ?? collect();
        $vehicles = $vehicles ?? collect();
        $drivers  = $drivers  ?? collect();
        $banks    = $banks    ?? collect();

        // الصلاحيات وحالة الوكالة
        $isActive           = $agency->status === 'active';
        $isPending          = $agency->status === 'pending';
        $canManageSettings  = !auth('employee')->check();

        // ----- حالات الرحلات -----
        $statusGroups = [
            'active'    => ['active', 'on_way', 'نشطة'],
            'completed' => ['completed', 'تمت الرحلة'],
            'cancelled' => ['cancelled', 'ملغاة'],
        ];
        $statusBadges = [
            'active'    => ['في الطريق 🟢', 'bg-emerald-50 border-emerald-100 text-emerald-600'],
            'completed' => ['تمت الرحلة ✅', 'bg-blue-50 border-blue-100 text-blue-600'],
            'cancelled' => ['ملغاة ❌',      'bg-rose-50 border-rose-100 text-rose-600'],
            'scheduled' => ['مجدولة 📅',     'bg-slate-100 border-slate-200 text-slate-600'],
        ];
        $tripGroup = function ($status) use ($statusGroups) {
            foreach ($statusGroups as $group => $list) {
                if (in_array($status, $list)) return $group;
            }
            return 'scheduled';
        };

        // ----- البطاقات الإحصائية: [أيقونة، عنوان، قيمة، لاحقة، لون الخلفية] -----
        $money = fn($v) => number_format($v ?? 0, 2);

        // الرحلات المجدولة والنشطة فعلاً (بدون المكتملة والملغاة)
        $upcomingTripsCount = $trips->filter(fn($t) => in_array($tripGroup($t->status), ['active', 'scheduled']))->count();

        $financeStats = [
            ['💰', 'إجمالي الأرباح الصافية', $money($totalEarnings ?? 0),    'ر.س', 'bg-emerald-50'],
            ['💳', 'الرصيد المتاح للسحب',    $money($availableBalance ?? 0), 'ر.س', 'bg-blue-50'],
            ['⏳', 'الرصيد المعلق',          $money($pendingBalance ?? 0),   'ر.س', 'bg-amber-50'],
        ];
        $opsStats = [
            ['🗺️',  'رحلات مجدولة ونشطة', $upcomingTripsCount, null, 'bg-indigo-50'],
            ['🚌',   'مركبات الأسطول',     $vehicles->count(),  null, 'bg-purple-50'],
            ['👨‍✈️', 'الكباتن المسجلين',   $drivers->count(),   null, 'bg-teal-50'],
        ];

        // ----- شارة حالة الوكالة: [نص، لون النقطة، لون النص] -----
        $agencyBadge = match ($agency->status) {
            'pending' => ['قيد المراجعة', 'bg-orange-500', 'text-orange-600'],
            'blocked' => ['محظورة',       'bg-red-600 animate-pulse', 'text-red-600'],
            default   => ['وكالة معتمدة', 'bg-emerald-500', 'text-emerald-600'],
        };

        // ----- باقة الاشتراك (تُحسب هنا، ويمكن نقلها للكنترولر لاحقاً) -----
        $hasSubscriptions = method_exists($agency, 'currentPlan')
            && \Illuminate\Support\Facades\Route::has('agency.subscription.index');

        $subscription = $plan = $awaitingSub = $daysLeft = $periodPct = null;
        $usage = [];
        $planState = ['الباقة المجانية', 'bg-white/10 text-slate-200 border-white/20'];

        if ($hasSubscriptions) {
            $subscription = $agency->currentSubscription();
            $plan         = $agency->currentPlan();
            $usage        = app(\App\Services\SubscriptionService::class)->usage($agency);
            $awaitingSub  = $agency->subscriptions()->where('status', 'awaiting_payment')->latest()->first();
            $daysLeft     = $subscription?->daysLeft();

            if ($subscription && $subscription->starts_at && $subscription->ends_at) {
                $periodTotal = max(1, $subscription->ends_at->timestamp - $subscription->starts_at->timestamp);
                $elapsed     = now()->timestamp - $subscription->starts_at->timestamp;
                $periodPct   = min(100, max(0, round(($elapsed / $periodTotal) * 100)));
            }

            if ($subscription) {
                $planState = ($daysLeft !== null && $daysLeft <= 7)
                    ? ['ينتهي قريباً', 'bg-amber-400/15 text-amber-300 border-amber-400/30']
                    : ['اشتراك نشط',   'bg-emerald-400/15 text-emerald-300 border-emerald-400/30'];
            }
        }

        $planLimits = [
            ['🧭', 'الرحلات النشطة', 'max_active_trips', 'trips'],
            ['🚌', 'المركبات',       'max_vehicles',     'vehicles'],
            ['🪪', 'السائقون',       'max_drivers',      'drivers'],
            ['👥', 'الموظفون',       'max_employees',    'employees'],
        ];

        // ----- نافذة التنبيهات (نجاح / خطأ / أخطاء تحقق): [أيقونة، عنوان، نص الزر، كلاس الأيقونة، كلاس الزر] -----
        $flashType = $errors->any() ? 'validation' : (session('error') ? 'error' : (session('success') ? 'success' : null));
        $flashConfig = [
            'success'    => ['✨', 'تمت العملية بنجاح',            'موافق',                    'bg-emerald-100 text-emerald-600 border-emerald-200', 'bg-emerald-600 hover:bg-emerald-700'],
            'error'      => ['⚠️', 'تنبيه',                        'إغلاق',                    'bg-rose-100 text-rose-600 border-rose-200',          'bg-rose-600 hover:bg-rose-700'],
            'validation' => ['🚨', 'يرجى تصحيح الأخطاء التالية',   'فهمت ذلك، إعادة المحاولة', 'bg-rose-100 text-rose-600 border-rose-200',          'bg-rose-600 hover:bg-rose-700'],
        ];

        // ----- بيانات السجل التجاري (للإعدادات) -----
        $crExpiry = $agency->cr_expiry_date ?? ($agency->commercial_expiry ?? ($agency->commercial_end_date ?? null));
        $diffDays = $crExpiry
            ? (int) \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($crExpiry)->startOfDay(), false)
            : null;
        $updateStatus    = $agency->cr_update_status ?? ($agency->cr_status ?? 'approved');
        $rejectionReason = $agency->cr_rejection_reason ?? ($agency->rejection_reason ?? null);

        $expiryBadge = null;
        if ($diffDays !== null) {
            $expiryBadge = $diffDays < 0
                ? ['🚨 منتهي منذ ' . abs($diffDays) . ' يوم', 'bg-rose-50 text-rose-600 border-rose-100']
                : ($diffDays <= 30
                    ? ['⚠️ ينتهي قريباً (خلال ' . $diffDays . ' يوم)', 'bg-amber-50 text-amber-600 border-amber-100']
                    : ['✅ السجل سارٍ (باقي ' . $diffDays . ' يوم)', 'bg-emerald-50 text-emerald-600 border-emerald-100']);
        }

        // بيانات العرض فقط: [العنوان، القيمة، اتجاه LTR؟]
        $basicInfo = [
            ['اسم الوكالة التجاري',            $agency->name,            false],
            ['اسم صاحب الوكالة / المستفيد',    $agency->commercial_name, false],
            ['البريد الإلكتروني',              $agency->email,           true],
            ['رقم التواصل / الجوال',           $agency->phone,           true],
        ];
        $crInfo = [
            ['رقم السجل التجاري (CR)', $agency->commercial_id, false],
            ['تاريخ انتهاء السجل',     $crExpiry ? \Carbon\Carbon::parse($crExpiry)->format('Y-m-d') : null, false],
        ];
    @endphp


    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  2) الحاوية الرئيسية + حالة Alpine                            ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    <div class="text-right space-y-8"
         x-data="agencyDashboard({ isActive: @js($isActive) })"
         @keydown.escape.window="close()">

        {{-- ═════════════ نافذة التنبيهات (نجاح / خطأ) ═════════════ --}}
        @if ($flashType)
            @php [$fIcon, $fTitle, $fBtn, $fIconClass, $fBtnClass] = $flashConfig[$flashType]; @endphp
            <div x-data="{ show: true }" x-show="show" x-bind="overlayFx" @click.self="show = false"
                 class="modal-overlay">
                <div x-show="show" x-bind="panelFx" class="modal-panel max-w-md p-6 text-center">

                    <div class="w-14 h-14 border rounded-full flex items-center justify-center mx-auto mb-4 {{ $fIconClass }}">
                        <span class="text-2xl">{{ $fIcon }}</span>
                    </div>

                    <h3 class="text-base font-black text-slate-800 mb-2">{{ $fTitle }}</h3>

                    @if ($flashType === 'validation')
                        <div class="bg-rose-50 border border-rose-100 rounded-xl p-3 mb-6 text-right max-h-48 overflow-y-auto">
                            <ul class="list-disc list-inside text-xs font-bold text-rose-800 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="text-xs font-bold text-slate-600 mb-6 leading-relaxed">
                            {{ $flashType === 'success' ? session('success') : session('error') }}
                        </p>
                    @endif

                    <button type="button" @click="show = false"
                            class="btn w-full justify-center text-white {{ $fBtnClass }}">
                        {{ $fBtn }}
                    </button>
                </div>
            </div>
        @endif

        {{-- ═════════════ نافذة تنبيه "الحساب غير معتمد" ═════════════ --}}
        <div x-show="notice" x-bind="overlayFx" @click.self="notice = ''" class="modal-overlay z-[2100]" x-cloak>
            <div x-show="notice" x-bind="panelFx" class="modal-panel max-w-md p-6 text-center">
                <div class="w-14 h-14 bg-amber-100 text-amber-600 border border-amber-200 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl">⚠️</span>
                </div>
                <h3 class="text-base font-black text-slate-800 mb-2">غير متاح حالياً</h3>
                <p class="text-xs font-bold text-slate-600 mb-6 leading-relaxed" x-text="notice"></p>
                <button type="button" @click="notice = ''"
                        class="btn w-full justify-center bg-slate-900 hover:bg-slate-800 text-white">
                    فهمت
                </button>
            </div>
        </div>


        {{-- ═════════════ رسالة الترحيب للوكالة المعلقة ═════════════ --}}
        @if ($isPending)
            <div class="p-6 bg-gradient-to-r from-amber-500 via-orange-500 to-yellow-500 text-white rounded-3xl shadow-lg border border-amber-400/20 flex items-center gap-5 relative overflow-hidden animate-fade-in">
                <div class="absolute -left-10 -top-10 w-32 h-32 bg-white/10 rounded-full blur-xl"></div>
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-xl"></div>

                <span class="text-4xl animate-bounce shrink-0">🎉</span>
                <div class="relative z-10">
                    <h2 class="text-xl font-black">أهلاً بك في منصتنا! مرحباً بوكالتك الجديدة ✨</h2>
                    <p class="text-xs font-bold opacity-90 mt-1 leading-relaxed">
                        يسعدنا انضمامك إلينا. تم تسجيل بياناتك بنجاح وحسابك الآن قيد المراجعة والاعتماد من قبل الإدارة،
                        يمكنك تصفح النظام واستكمال بياناتك البنكية وإعداداتك حتى يتم التفعيل.
                    </p>
                </div>
            </div>
            <div id="confetti-container" class="fixed inset-0 pointer-events-none z-[9999] overflow-hidden"></div>
        @endif


        {{-- ═════════════ الهيدر + الإجراءات السريعة ═════════════ --}}
        <header class="card p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-5">

            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-slate-900 rounded-2xl flex items-center justify-center text-2xl shadow-lg shadow-slate-200 shrink-0">📊</div>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-tight">مركز القيادة والتحكم</h1>
                    <p class="text-[11px] text-slate-400 font-bold mt-0.5">لوحة إدارة المبيعات، الأسطول، والرحلات المباشرة</p>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-2.5">
                @unless ($isPending)
                    <button type="button"
                            @click="openIfActive('bus', 'عذراً، لا يمكنك إضافة مركبات للأسطول حتى يتم اعتماد حساب وكالتك من قبل الإدارة.')"
                            class="btn bg-indigo-600 hover:bg-indigo-700 text-white">
                        <span class="text-base">🚌</span><span>إضافة مركبة</span>
                    </button>

                    <button type="button"
                            @click="openIfActive('driver', 'عذراً، لا يمكنك تسجيل كباتن جدد حتى يتم اعتماد حساب وكالتك من قبل الإدارة.')"
                            class="btn bg-slate-900 hover:bg-slate-800 text-white">
                        <span class="text-base">👨‍✈️</span><span>تسجيل كابتن</span>
                    </button>

                    <button type="button" @click="open('bank')"
                            class="btn bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-100 shadow-none">
                        <span class="text-base">💳</span><span>البيانات البنكية</span>
                    </button>
                @endunless

                @if ($canManageSettings)
                    <button type="button" @click="open('settings')"
                            class="btn bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 shadow-none">
                        <span class="text-base">⚙️</span><span>إعدادات الوكالة والسجل</span>
                    </button>
                @endif
            </div>
        </header>


        {{-- ═════════════ باقة الاشتراك ═════════════ --}}
        @if ($hasSubscriptions && $plan)
            <section>
                <h2 class="sec-title"><span>🎫</span><span>باقة الاشتراك</span></h2>

                <div class="card overflow-hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-12">

                        {{-- الجزء الغامق: الباقة والحالة والمدة --}}
                        <div class="lg:col-span-4 bg-slate-900 text-white p-6 sm:p-8 flex flex-col justify-between gap-6 relative overflow-hidden">
                            <div class="absolute -left-12 -bottom-12 w-40 h-40 bg-indigo-500/20 rounded-full blur-2xl pointer-events-none"></div>

                            <div class="relative">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-slate-400 text-xs font-bold">باقتك الحالية</span>
                                    <span class="pill {{ $planState[1] }}">{{ $planState[0] }}</span>
                                </div>

                                <h3 class="text-2xl font-black mt-2">{{ $plan->name }}</h3>

                                @if ($subscription)
                                    <div class="mt-4 space-y-2">
                                        <div class="flex items-center justify-between text-[11px] font-bold">
                                            <span class="text-slate-300">يتبقى {{ $daysLeft }} {{ $daysLeft === 1 ? 'يوم' : 'أيام' }}</span>
                                            <span class="text-slate-400 font-mono">{{ $subscription->ends_at->format('Y-m-d') }}</span>
                                        </div>
                                        <div class="h-1.5 bg-white/10 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full {{ $daysLeft !== null && $daysLeft <= 7 ? 'bg-amber-400' : 'bg-emerald-400' }}"
                                                 style="width: {{ 100 - ($periodPct ?? 0) }}%"></div>
                                        </div>
                                        <p class="text-[10px] text-slate-400">
                                            {{ $subscription->billing_cycle === 'yearly' ? 'اشتراك سنوي' : 'اشتراك شهري' }}
                                            • بدأ في {{ $subscription->starts_at->format('Y-m-d') }}
                                        </p>
                                    </div>
                                @else
                                    <p class="text-xs text-slate-400 mt-3 leading-relaxed">
                                        أنت على الباقة المجانية الافتراضية. رقّ باقتك للحصول على حدود أعلى وعمولة أقل.
                                    </p>
                                @endif
                            </div>

                            <div class="relative flex flex-col gap-2">
                                @if ($awaitingSub)
                                    <a href="{{ route('agency.subscription.pay', $awaitingSub) }}"
                                       class="px-4 py-2.5 rounded-xl bg-amber-400/15 border border-amber-400/30 text-amber-300 text-[11px] font-bold text-center hover:bg-amber-400/25 transition-colors">
                                        ⏳ عندك عملية دفع غير مكتملة، أكمل الدفع
                                    </a>
                                @endif

                                @if ($canManageSettings)
                                    <a href="{{ route('agency.subscription.index') }}"
                                       class="px-4 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold text-center transition-colors">
                                        @if (!$subscription)
                                            ترقية الباقة
                                        @elseif ($daysLeft !== null && $daysLeft <= 7)
                                            تجديد الاشتراك
                                        @else
                                            إدارة الاشتراك
                                        @endif
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- الجزء الفاتح: الاستهلاك والمزايا --}}
                        <div class="lg:col-span-8 p-6 sm:p-8 space-y-5">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-black text-slate-800">استهلاكك من حدود الباقة</h4>
                            </div>

                            <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
                                @foreach ($planLimits as [$lIcon, $lLabel, $lKey, $lUsageKey])
                                    @php
                                        $lMax  = $plan->{$lKey};
                                        $lUsed = $usage[$lUsageKey] ?? 0;
                                        $lPct  = $lMax === null ? 0 : ($lMax == 0 ? 100 : min(100, round(($lUsed / $lMax) * 100)));
                                        $lBar  = $lPct >= 100 ? 'bg-rose-500' : ($lPct >= 80 ? 'bg-amber-500' : 'bg-indigo-600');
                                    @endphp
                                    <div class="p-4 bg-slate-50/70 border border-slate-100 rounded-2xl">
                                        <p class="text-[11px] font-bold text-slate-500 mb-1.5">{{ $lIcon }} {{ $lLabel }}</p>
                                        <p class="text-lg font-black text-slate-800 font-mono mb-2" dir="ltr">
                                            {{ $lUsed }}<span class="text-xs font-bold text-slate-400"> / {{ $lMax ?? '∞' }}</span>
                                        </p>
                                        <div class="h-1.5 bg-slate-200/70 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full {{ $lBar }}" style="width: {{ $lMax === null ? 8 : $lPct }}%"></div>
                                        </div>
                                        @if ($lMax !== null && $lPct >= 100)
                                            <p class="text-[10px] text-rose-500 font-bold mt-1.5">وصلت للحد</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex flex-wrap gap-2 pt-4 border-t border-slate-100">
                                <span class="pill bg-slate-50 text-slate-600 border-slate-200">
                                    💰 عمولة المنصة: {{ number_format($plan->commission_per_passenger, 2) }} ر.س/مسافر
                                    @if ($plan->min_booking_commission !== null) • أدنى {{ number_format($plan->min_booking_commission, 2) }} @endif
                                    @if ($plan->max_booking_commission !== null) • أعلى {{ number_format($plan->max_booking_commission, 2) }} @endif
                                </span>
                                <span class="pill bg-slate-50 text-slate-600 border-slate-200">💬 {{ $plan->whatsapp_monthly_limit }} رسالة واتساب/شهر</span>
                                <span class="pill {{ $plan->allows_overflow_bus ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-50 text-slate-400 border-slate-200' }}">
                                    🚌➕ الباص الثاني: {{ $plan->allows_overflow_bus ? 'متاح' : 'غير متاح' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif


        {{-- ═════════════ الملخص المالي + التشغيلي ═════════════ --}}
        <section class="space-y-6">
            <div>
                <h2 class="sec-title"><span>💼</span><span>الملخص المالي</span></h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    @foreach ($financeStats as [$icon, $label, $value, $suffix, $iconBg])
                        <div class="card stat-card">
                            <div class="stat-icon {{ $iconBg }}">{{ $icon }}</div>
                            <div>
                                <p class="text-[11px] text-slate-400 font-bold mb-1">{{ $label }}</p>
                                <h4 class="text-2xl font-black text-slate-800">
                                    {{ $value }}
                                    @if ($suffix)<span class="text-xs font-normal text-slate-400">{{ $suffix }}</span>@endif
                                </h4>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h2 class="sec-title"><span>🧭</span><span>نظرة تشغيلية</span></h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                    @foreach ($opsStats as [$icon, $label, $value, $suffix, $iconBg])
                        <div class="card stat-card">
                            <div class="stat-icon {{ $iconBg }}">{{ $icon }}</div>
                            <div>
                                <p class="text-[11px] text-slate-400 font-bold mb-1">{{ $label }}</p>
                                <h4 class="text-2xl font-black text-slate-800">{{ $value }}</h4>
                            </div>
                        </div>
                    @endforeach

                    {{-- حالة الوكالة --}}
                    <div class="card stat-card">
                        <div class="stat-icon bg-slate-50">🛡️</div>
                        <div>
                            <p class="text-[11px] text-slate-400 font-bold mb-1">حالة الوكالة</p>
                            <div class="flex items-center gap-1.5 mt-1">
                                <span class="w-2.5 h-2.5 rounded-full {{ $agencyBadge[1] }}"></span>
                                <h4 class="text-sm font-black {{ $agencyBadge[2] }}">{{ $agencyBadge[0] }}</h4>
                            </div>
                            @if ($agency->status === 'blocked')
                                <p class="text-[10px] text-red-500 font-extrabold mt-1">تواصل مع الدعم الفني</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>


        {{-- ╔══════════════════════════════════════════════════════════════╗
             ║  3) شبكة البيانات: الرحلات (يمين) + التبويبات (يسار)          ║
             ╚══════════════════════════════════════════════════════════════╝ --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- ═════════════ قائمة الرحلات ═════════════ --}}
            <section class="card lg:col-span-7 p-6 flex flex-col max-h-[720px]">

                <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 pb-4 mb-4 gap-3 shrink-0">
                    <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                        <span>قائمة الرحلات</span><span>📋</span>
                    </h3>
                    <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-bold w-fit">
                        إجمالي الرحلات: {{ $trips->count() }}
                    </span>
                </div>

                {{-- البحث والتصفية --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4 shrink-0">
                    <div class="relative">
                        <input type="text" x-model="tripFilter.search" placeholder="ابحث باسم الرحلة أو المدينة..."
                               class="field-input !py-2.5 pr-9">
                        <span class="absolute right-3 top-2.5 text-slate-400 text-xs">🔍</span>
                    </div>

                    <select x-model="tripFilter.type" class="field-input !py-2.5">
                        <option value="all">كل أنواع النقل</option>
                        <option value="bus">حافلات برية 🚌</option>
                        <option value="flight">رحلات جوية ✈️</option>
                    </select>

                    <select x-model="tripFilter.status" class="field-input !py-2.5">
                        <option value="all">جميع الحالات</option>
                        <option value="active">في الطريق 🟢</option>
                        <option value="scheduled">مجدولة 📅</option>
                        <option value="completed">تمت ✅</option>
                        <option value="cancelled">ملغاة ❌</option>
                    </select>
                </div>

                {{-- الرحلات --}}
                <div class="space-y-3 overflow-y-auto pr-1 flex-1 min-h-[200px] soft-scroll">
                    @forelse ($trips as $trip)
                        @php
                            $group       = $tripGroup($trip->status);
                            [$badgeText, $badgeClass] = $statusBadges[$group];
                            $totalSeats  = max(1, $trip->total_seats ?? 1);
                            $availSeats  = $trip->available_seats ?? 0;
                            $occupancy   = round((max(0, $totalSeats - $availSeats) / $totalSeats) * 100);
                            $hasNoDriver = empty($trip->driver_id) && !in_array($group, ['completed', 'cancelled']);
                        @endphp

                        <article x-show="matchTrip($el)" x-transition.opacity.duration.200ms
                                 data-search="{{ mb_strtolower(($trip->title ?? '') . ' ' . ($trip->from_city ?? '') . ' ' . ($trip->to_city ?? '')) }}"
                                 data-type="{{ $trip->transport_type }}"
                                 data-group="{{ $group }}"
                                 class="p-4 bg-slate-50/70 rounded-2xl border border-slate-100 hover:bg-indigo-50/40 hover:border-indigo-100 transition-all duration-200 space-y-3">

                            <div class="flex items-start justify-between gap-3">

                                {{-- العنوان والمسار --}}
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-11 h-11 bg-white rounded-xl border border-slate-100 flex items-center justify-center text-xl shadow-sm shrink-0">
                                        {{ $trip->transport_type == 'flight' ? '✈️' : '🚌' }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-slate-800 leading-snug truncate">{{ $trip->title ?? 'رحلة بدون عنوان' }}</p>
                                        <div class="text-[11px] font-bold text-slate-400 mt-1 flex flex-wrap items-center gap-1.5">
                                            <span class="text-slate-700 font-extrabold">{{ $trip->from_city ?? 'غير محدد' }}</span>
                                            <span class="text-indigo-400 text-xs">⬅</span>
                                            <span class="text-slate-700 font-extrabold">{{ $trip->to_city ?? 'غير محدد' }}</span>
                                            @if ($trip->departure_at)
                                                <span class="text-slate-300">|</span>
                                                <span class="text-slate-500 font-medium">📅 {{ \Carbon\Carbon::parse($trip->departure_at)->format('Y-m-d') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <span class="inline-flex items-center px-3 py-1 border rounded-xl text-[11px] font-bold shrink-0 {{ $badgeClass }}">
                                    {{ $badgeText }}
                                </span>
                            </div>

                            {{-- المقاعد والسعر + شريط الإشغال --}}
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 items-center pt-3 border-t border-slate-200/50">
                                <div>
                                    <p class="text-[10px] text-slate-400 font-bold mb-0.5">المقاعد المتاحة</p>
                                    <span class="text-xs font-black text-indigo-600">{{ $availSeats }} / {{ $trip->total_seats ?? 0 }}</span>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400 font-bold mb-0.5">سعر المقعد الإضافي</p>
                                    <span class="text-sm font-black text-slate-900">
                                        {{ number_format($trip->price ?? 0, 2) }}
                                        <span class="text-[10px] text-slate-500 font-normal">ر.س</span>
                                    </span>
                                </div>
                                <div class="col-span-2 sm:col-span-1">
                                    <p class="text-[10px] font-bold text-slate-400 mb-1">الإشغال: {{ $occupancy }}%</p>
                                    <div class="w-full bg-slate-200/70 h-1.5 rounded-full overflow-hidden">
                                        <div class="bg-indigo-600 h-full rounded-full transition-all duration-500" style="width: {{ $occupancy }}%"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- تنبيه: بدون سائق --}}
                            @if ($hasNoDriver)
                                <div class="w-full flex items-center gap-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-xl">
                                    <span class="text-amber-600 text-sm shrink-0">⚠️</span>
                                    <span class="text-[11px] font-bold text-amber-700">لم يتم تعيين سائق لهذه الرحلة بعد</span>
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="h-full min-h-[240px] flex flex-col items-center justify-center p-10 text-center bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                            <p class="text-4xl mb-3">📭</p>
                            <p class="text-sm font-bold text-slate-400 italic">لا توجد رحلات مسجلة تابعة لهذه الوكالة حالياً.</p>
                        </div>
                    @endforelse
                </div>
            </section>


            {{-- ═════════════ التبويبات: الكباتن / الأسطول / البنوك ═════════════ --}}
            <section class="card lg:col-span-5 p-6 flex flex-col max-h-[720px]">

                {{-- أزرار التبويبات --}}
                <div class="flex items-center gap-2 bg-slate-100 p-1.5 rounded-2xl mb-4 shrink-0">
                    @foreach ([
                        ['drivers', '👨‍✈️ الكباتن', $drivers->count(),  'text-slate-900',   'bg-slate-900'],
                        ['fleet',   '🚀 الأسطول',  $vehicles->count(), 'text-indigo-600',  'bg-indigo-600'],
                        ['banks',   '🏦 البنوك',   $banks->count(),    'text-emerald-600', 'bg-emerald-600'],
                    ] as [$key, $label, $count, $textActive, $badgeActive])
                        <button type="button" @click="activeTab = '{{ $key }}'"
                                :class="activeTab === '{{ $key }}' ? 'bg-white {{ $textActive }} shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                                class="flex-1 py-2.5 rounded-xl text-xs font-black transition-all duration-200 flex items-center justify-center gap-1.5">
                            <span>{{ $label }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded-md transition-colors"
                                  :class="activeTab === '{{ $key }}' ? '{{ $badgeActive }} text-white' : 'bg-slate-200 text-slate-600'">
                                {{ $count }}
                            </span>
                        </button>
                    @endforeach
                </div>

                {{-- حقل بحث واحد يتغير حسب التبويب --}}
                <div class="mb-3 shrink-0">
                    <input type="text" x-model="tabSearch[activeTab]" :placeholder="tabPlaceholders[activeTab]"
                           class="field-input !py-2">
                </div>

                <div class="overflow-y-auto flex-1 min-h-[200px] pr-1 soft-scroll">

                    {{-- 👨‍✈️ الكباتن --}}
                    <div x-show="activeTab === 'drivers'" x-bind="viewFx">
                        <table class="tbl">
                            <thead>
                                <tr><th>الكابتن</th><th>رقم الجوال</th><th class="text-left">الحالة</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($drivers as $driver)
                                    <tr x-show="matchRow($el, 'drivers')"
                                        data-search="{{ mb_strtolower(($driver->name ?? '') . ' ' . ($driver->phone ?? '') . ' ' . ($driver->national_id ?? '')) }}">
                                        <td class="font-black text-slate-800">
                                            <div>{{ $driver->name }}</div>
                                            <span class="text-[9px] text-slate-400 block font-normal">ID: {{ $driver->national_id }}</span>
                                        </td>
                                        <td class="font-mono text-slate-600" dir="ltr">{{ $driver->phone }}</td>
                                        <td class="text-left">
                                            <span class="bg-indigo-50 text-indigo-700 font-bold px-2 py-0.5 rounded text-[9px]">
                                                {{ $driver->status == 'available' ? '✓ جاهز' : 'في رحلة' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="empty-row" colspan="3">لا يوجد كباتن مسجلين حالياً.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- 🚀 الأسطول --}}
                    <div x-show="activeTab === 'fleet'" x-bind="viewFx" x-cloak>
                        <table class="tbl">
                            <thead>
                                <tr><th>النوع والموديل</th><th class="text-center">رقم اللوحة</th><th class="text-left">المقاعد</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($vehicles as $vehicle)
                                    <tr x-show="matchRow($el, 'fleet')"
                                        data-search="{{ mb_strtolower(($vehicle->vehicle_model ?? '') . ' ' . ($vehicle->plate_number ?? '')) }}">
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <span>{{ $vehicle->type == 'flight' ? '✈️' : '🚌' }}</span>
                                                <div>
                                                    <span class="font-black text-slate-800 block">{{ $vehicle->vehicle_model }}</span>
                                                    @if ($vehicle->check_date)
                                                        <span class="text-[9px] text-slate-400 block">فحص: {{ $vehicle->check_date }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center font-mono font-bold text-slate-700">{{ $vehicle->plate_number }}</td>
                                        <td class="text-left font-bold text-slate-900">{{ $vehicle->total_seats }} مقعد</td>
                                    </tr>
                                @empty
                                    <tr><td class="empty-row" colspan="3">لا توجد مركبات في الأسطول حالياً.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- 🏦 البنوك --}}
                    <div x-show="activeTab === 'banks'" x-bind="viewFx" x-cloak>
                        <table class="tbl">
                            <thead>
                                <tr><th>البنك والمستفيد</th><th class="text-center">IBAN</th><th class="text-left">الحالة</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($banks as $bank)
                                    <tr x-show="matchRow($el, 'banks')"
                                        data-search="{{ mb_strtolower(($bank->bank_name ?? '') . ' ' . ($bank->account_name ?? '') . ' ' . ($bank->iban ?? '')) }}">
                                        <td>
                                            <span class="font-black text-slate-800 block">{{ $bank->bank_name }}</span>
                                            <span class="text-[9px] text-slate-400 block font-normal">{{ $bank->account_name }}</span>
                                        </td>
                                        <td class="text-center font-mono text-slate-600" dir="ltr">{{ $bank->iban }}</td>
                                        <td class="text-left">
                                            <span class="bg-emerald-50 text-emerald-700 font-bold px-2 py-0.5 rounded text-[9px]">نشط</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="empty-row" colspan="3">لا توجد حسابات بنكية مسجلة حالياً.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </section>
        </div>


        {{-- ╔══════════════════════════════════════════════════════════════╗
             ║  4) النوافذ المنبثقة (Modals)                                 ║
             ╚══════════════════════════════════════════════════════════════╝ --}}

        {{-- ═════════════ ⚙️ إعدادات الوكالة ═════════════ --}}
        @if ($canManageSettings)
            <div x-show="modal === 'settings'" x-bind="overlayFx" @click.self="close()" class="modal-overlay" x-cloak>
                <div x-show="modal === 'settings'" x-bind="panelFx" class="modal-panel max-w-2xl">

                    <div class="modal-head bg-slate-900">
                        <div>
                            <h2 class="text-xl font-black">إعدادات الوكالة والتراخيص ⚙️</h2>
                            <p class="text-xs mt-1 opacity-80">إدارة معلومات الوكالة، ومتابعة حالة طلبات تحديث السجل التجاري</p>
                        </div>
                        <button type="button" @click="close()" class="modal-close">✕</button>
                    </div>

                    {{-- تنبيه حالة طلب التحديث --}}
                    @if ($updateStatus === 'pending')
                        <div class="bg-amber-50 border-b border-amber-100 p-4 px-6 flex items-center gap-3 text-amber-800 text-xs font-bold">
                            <span class="text-lg">⏳</span>
                            <div>
                                <p class="font-black text-amber-900">طلب تحديث السجل قيد المراجعة</p>
                                <p class="text-[11px] opacity-90 mt-0.5">تم إرسال بيانات السجل التجاري الجديد للإدارة وهي قيد التدقيق حالياً.</p>
                            </div>
                        </div>
                    @elseif ($updateStatus === 'rejected')
                        <div class="bg-rose-50 border-b border-rose-100 p-4 px-6 flex items-start gap-3 text-rose-800 text-xs font-bold">
                            <span class="text-lg shrink-0">❌</span>
                            <div>
                                <p class="font-black text-rose-900">تم رفض طلب تحديث السجل التجاري</p>
                                @if ($rejectionReason)
                                    <p class="text-[11px] text-rose-700 mt-1 bg-white/60 p-2 rounded-lg border border-rose-200">
                                        <span class="font-black">سبب الرفض:</span> {{ $rejectionReason }}
                                    </p>
                                @endif
                                <p class="text-[10px] text-rose-600 mt-1">يرجى تعديل البيانات وإرفاق ملف السجل الصحيح ثم إعادة الطلب.</p>
                            </div>
                        </div>
                    @endif

                    {{-- وضع العرض --}}
                    <div x-show="!editMode" x-bind="viewFx" class="modal-body space-y-6">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="section-title">البيانات الأساسية</h3>
                                <button type="button" @click="editMode = true"
                                        class="btn !py-2 bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">
                                    <span>تعديل البيانات</span><span>✏️</span>
                                </button>
                            </div>
                            <div class="info-grid">
                                @foreach ($basicInfo as [$label, $value, $ltr])
                                    <div>
                                        <span class="block text-[10px] font-bold text-slate-400">{{ $label }}</span>
                                        <span class="text-xs font-black text-slate-800" @if($ltr) dir="ltr" @endif>{{ $value ?? 'غير محدد' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <hr class="border-slate-100">

                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                <h3 class="section-title">السجل التجاري والتراخيص</h3>
                                <div class="flex items-center gap-2">
                                    @if ($updateStatus === 'pending')
                                        <span class="pill bg-amber-100 text-amber-700 border-amber-200">⏳ التحديث قيد المراجعة</span>
                                    @elseif ($updateStatus === 'rejected')
                                        <span class="pill bg-rose-100 text-rose-700 border-rose-200">❌ طلب التحديث مرفوض</span>
                                    @endif
                                    @if ($expiryBadge)
                                        <span class="pill {{ $expiryBadge[1] }}">{{ $expiryBadge[0] }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="info-grid">
                                @foreach ($crInfo as [$label, $value, $ltr])
                                    <div>
                                        <span class="block text-[10px] font-bold text-slate-400">{{ $label }}</span>
                                        <span class="text-xs font-black text-slate-800">{{ $value ?? 'غير محدد' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- وضع التعديل --}}
                    <form x-show="editMode" x-bind="viewFx" x-cloak class="modal-body space-y-6" method="POST"
                          action="{{ route('agency.settings.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 class="section-title">تعديل بيانات الوكالة والسجل</h3>
                            <button type="button" @click="editMode = false"
                                    class="text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">
                                ✕ إلغاء والتراجع
                            </button>
                        </div>

                        <div>
                            <h4 class="text-xs font-bold text-slate-700 mb-3">البيانات الأساسية</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">اسم الوكالة التجاري</label>
                                    <input type="text" name="name" value="{{ old('name', $agency->name ?? '') }}" required class="field-input">
                                </div>
                                <div>
                                    <label class="field-label">اسم صاحب الوكالة / المستفيد</label>
                                    <input type="text" name="commercial_name" value="{{ old('commercial_name', $agency->commercial_name ?? '') }}" class="field-input">
                                </div>
                                <div>
                                    <label class="field-label">البريد الإلكتروني للوكالة</label>
                                    <input type="email" name="email" value="{{ old('email', $agency->email ?? '') }}" required dir="ltr" class="field-input text-left">
                                </div>
                                <div>
                                    <label class="field-label">رقم التواصل / الجوال</label>
                                    <input type="tel" name="phone" value="{{ old('phone', $agency->phone ?? '') }}" dir="ltr" class="field-input text-left">
                                </div>
                            </div>
                        </div>

                        <hr class="border-slate-100">

                        <div>
                            <h4 class="text-xs font-bold text-slate-700 mb-3">السجل التجاري والتراخيص</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">رقم السجل التجاري (CR)</label>
                                    <input type="text" name="commercial_id" value="{{ old('commercial_id', $agency->commercial_id ?? '') }}" required class="field-input">
                                </div>
                                <div>
                                    <label class="field-label">تاريخ انتهاء السجل الجديد</label>
                                    <input type="date" name="cr_expiry_date" required
                                           value="{{ old('cr_expiry_date', $crExpiry ? \Carbon\Carbon::parse($crExpiry)->format('Y-m-d') : '') }}"
                                           class="field-input">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="field-label">إرفاق صورة / ملف السجل المجدد (PDF / Image)</label>
                                    <input type="file" name="cr_document" accept="image/*,application/pdf" class="field-file file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                    <p class="text-[10px] text-slate-400 mt-1">عند تقديم الطلب سيتم إرساله للوحة تحكم الأدمن للتدقيق والموافقة.</p>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 flex gap-3">
                            <button type="submit" class="btn flex-1 justify-center py-3.5 bg-slate-900 hover:bg-indigo-600 text-white">
                                حفظ البيانات وتقديم طلب التحديث
                            </button>
                            <button type="button" @click="editMode = false"
                                    class="btn py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 shadow-none">
                                إلغاء
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif


        {{-- ═════════════ 💳 البيانات البنكية ═════════════ --}}
        <div x-show="modal === 'bank'" x-bind="overlayFx" @click.self="close()" class="modal-overlay" x-cloak>
            <div x-show="modal === 'bank'" x-bind="panelFx" class="modal-panel max-w-2xl">

                <div class="modal-head bg-emerald-600">
                    <div>
                        <h2 class="text-xl font-black">تسجيل البيانات البنكية الدائمة للوكالة 💳</h2>
                        <p class="text-xs mt-1 opacity-80">تثبيت بيانات الحساب لاستقبال المستحقات المالية والتحويلات</p>
                    </div>
                    <button type="button" @click="close()" class="modal-close">✕</button>
                </div>

                <form class="modal-body grid grid-cols-1 md:grid-cols-2 gap-5" method="POST"
                      action="{{ route('agency.bank.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="field-label">اسم البنك</label>
                        <input type="text" name="bank_name" required placeholder="مثال: مصرف الراجحي، البنك الأهلي السعودي..." class="field-input focus:!border-emerald-500 focus:!ring-emerald-500/20">
                    </div>
                    <div>
                        <label class="field-label">رقم الحساب (اختياري)</label>
                        <input type="text" name="account_number" placeholder="1234567890" dir="ltr" class="field-input text-left focus:!border-emerald-500 focus:!ring-emerald-500/20">
                    </div>
                    <div>
                        <label class="field-label">رقم الآيبان (IBAN)</label>
                        <input type="text" name="iban" required placeholder="SA0300000000000000000000" dir="ltr" class="field-input text-left focus:!border-emerald-500 focus:!ring-emerald-500/20">
                    </div>
                    <div class="md:col-span-2">
                        <label class="field-label">رمز QR المالي للبطاقة (اختياري)</label>
                        <input type="file" name="qr_code" accept="image/*" class="field-file file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                    <div class="md:col-span-2 mt-2">
                        <button type="submit" class="btn w-full justify-center py-4 bg-emerald-600 hover:bg-emerald-700 text-white">
                            حفظ وتثبيت الحساب البنكي
                        </button>
                    </div>
                </form>
            </div>
        </div>


        {{-- ═════════════ 🚌 إضافة مركبة ═════════════ --}}
        <div x-show="modal === 'bus'" x-bind="overlayFx" @click.self="close()" class="modal-overlay" x-cloak>
            <div x-show="modal === 'bus'" x-bind="panelFx" class="modal-panel max-w-2xl">

                <div class="modal-head bg-indigo-600">
                    <div>
                        <h2 class="text-xl font-black">تسجيل مركبة جديدة بالأسطول 🚌 ✈️</h2>
                        <p class="text-xs mt-1 opacity-80">توسيع أسطول نقل الوكالة وتحديث البيانات</p>
                    </div>
                    <button type="button" @click="close()" class="modal-close">✕</button>
                </div>

                <form class="modal-body grid grid-cols-1 md:grid-cols-2 gap-5" method="POST" action="{{ route('agency.vehicles.store') }}">
                    @csrf
                    <div>
                        <label class="field-label">نوع المركبة</label>
                        <select name="type" required class="field-input">
                            <option value="bus">حافلة برية 🚌</option>
                            <option value="flight">طائرة نفاثة ✈️</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">رقم اللوحة / رمز الطائرة</label>
                        <input type="text" name="plate_number" required placeholder="أ ب ج 1234 أو HZ-XXX" class="field-input text-center">
                    </div>
                    <div>
                        <label class="field-label">عدد المقاعد الإجمالي</label>
                        <input type="number" name="total_seats" required min="1" placeholder="50" class="field-input">
                    </div>
                    <div>
                        <label class="field-label">موديل المركبة</label>
                        <input type="text" name="vehicle_model" required placeholder="Mercedes-Benz Travego" class="field-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="field-label">تاريخ الفحص الدوري</label>
                        <input type="date" name="check_date" class="field-input">
                    </div>
                    <div class="md:col-span-2 mt-2">
                        <button type="submit" class="btn w-full justify-center py-4 bg-indigo-600 hover:bg-indigo-700 text-white">
                            تسجيل المركبة في النظام
                        </button>
                    </div>
                </form>
            </div>
        </div>


        {{-- ═════════════ 👨‍✈️ تسجيل كابتن ═════════════ --}}
        <div x-show="modal === 'driver'" x-bind="overlayFx" @click.self="close()" class="modal-overlay" x-cloak>
            <div x-show="modal === 'driver'" x-bind="panelFx" class="modal-panel max-w-2xl">

                <div class="modal-head bg-slate-900">
                    <div>
                        <h2 class="text-xl font-black">تسجيل كابتن جديد 👨‍✈️</h2>
                        <p class="text-xs mt-1 opacity-80">إضافة بيانات السائق الجديد في طاقم العمل للرحلات</p>
                    </div>
                    <button type="button" @click="close()" class="modal-close">✕</button>
                </div>

                <form class="modal-body grid grid-cols-1 md:grid-cols-2 gap-5" method="POST" action="{{ route('agency.drivers.store') }}">
                    @csrf
                    <div>
                        <label class="field-label">الاسم الكامل</label>
                        <input type="text" name="name" required placeholder="الاسم الثلاثي" class="field-input">
                    </div>
                    <div>
                        <label class="field-label">رقم الجوال</label>
                        <input type="tel" name="phone" required placeholder="05xxxxxxxx" dir="ltr" class="field-input text-left">
                    </div>
                    <div>
                        <label class="field-label">رقم الرخصة</label>
                        <input type="text" name="license_number" required placeholder="10xxxxxxxx" class="field-input">
                    </div>
                    <div>
                        <label class="field-label">تاريخ انتهاء الرخصة</label>
                        <input type="date" name="license_expiry_date" required class="field-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="field-label">الرقم الوطني / الإقامة</label>
                        <input type="text" name="national_id" required placeholder="10xxxxxxxx" class="field-input">
                    </div>
                    <div class="md:col-span-2 mt-2">
                        <button type="submit" class="btn w-full justify-center py-4 bg-slate-900 hover:bg-slate-800 text-white">
                            حفظ وتثبيت البيانات
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>{{-- /agencyDashboard --}}


    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  5) التنسيقات (Tailwind components + CSS)                     ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    <style type="text/tailwindcss">
        @layer components {
            /* ----- عام ----- */
            .card        { @apply bg-white rounded-3xl border border-slate-100 shadow-sm; }
            .btn         { @apply inline-flex items-center gap-2 px-5 py-3 rounded-xl font-bold text-xs shadow-md transition-all duration-200 active:scale-95; }
            .pill        { @apply inline-flex items-center gap-1 text-[10px] font-extrabold px-2.5 py-1 rounded-md border; }
            .sec-title   { @apply flex items-center gap-2 text-sm font-black text-slate-700 mb-3 mr-1; }
            .section-title { @apply text-xs font-black text-indigo-600 uppercase tracking-wider; }
            .info-grid   { @apply grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100; }

            /* ----- البطاقات الإحصائية ----- */
            .stat-card   { @apply p-6 flex items-center gap-5 transition-shadow duration-200 hover:shadow-md; }
            .stat-icon   { @apply w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shrink-0; }

            /* ----- الجداول ----- */
            .tbl            { @apply w-full text-right text-xs; }
            .tbl thead      { @apply sticky top-0 bg-white z-10; }
            .tbl th         { @apply pb-2 text-slate-400 font-bold border-b border-slate-100; }
            .tbl tbody      { @apply divide-y divide-slate-50; }
            .tbl tbody tr   { @apply transition-colors hover:bg-slate-50/60; }
            .tbl td         { @apply py-3; }
            .tbl .empty-row { @apply py-10 font-black text-slate-400 italic text-center; }

            /* ----- الحقول ----- */
            .field-label { @apply block text-xs font-bold text-slate-500 mb-1.5 mr-1; }
            .field-input { @apply w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 font-bold text-xs text-slate-800 outline-none transition-all duration-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500; }
            .field-file  { @apply w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 font-bold text-xs text-slate-500 cursor-pointer outline-none file:ml-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-black; }

            /* ----- النوافذ ----- */
            .modal-overlay { @apply fixed inset-0 z-[2000] flex bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto overscroll-contain; }
            .modal-panel   { @apply relative bg-white w-full m-auto rounded-[2rem] shadow-2xl overflow-hidden; }
            .modal-head    { @apply p-6 sm:p-8 text-white flex justify-between items-center gap-4; }
            .modal-body    { @apply p-6 sm:p-8; }
            .modal-close   { @apply text-white/70 hover:text-white hover:rotate-90 transition-all duration-300 text-xl font-bold shrink-0; }
        }
    </style>

    <style>
        /* ===== حركات الظهور (نفس منحنى وتوقيت السايد بار) ===== */
        .fx-overlay-active { transition: opacity .3s cubic-bezier(.4, 0, .2, 1); }
        .fx-overlay-from   { opacity: 0; }
        .fx-overlay-to     { opacity: 1; }

        .fx-panel-active   { transition: opacity .3s cubic-bezier(.4, 0, .2, 1), transform .35s cubic-bezier(.4, 0, .2, 1); }
        .fx-panel-from     { opacity: 0; transform: translateY(16px) scale(.97); }
        .fx-panel-to       { opacity: 1; transform: translateY(0) scale(1); }

        .fx-view-active    { transition: opacity .25s ease, transform .25s ease; }
        .fx-view-from      { opacity: 0; transform: translateY(6px); }
        .fx-view-to        { opacity: 1; transform: translateY(0); }

        .animate-fade-in { animation: fadeIn .4s ease-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ===== شريط التمرير الناعم (اسم مختلف حتى لا يتعارض مع السايد بار) ===== */
        .soft-scroll::-webkit-scrollbar { width: 5px; }
        .soft-scroll::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
        .soft-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .soft-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* ===== قصاصات الاحتفال ===== */
        .confetti-piece { position: absolute; top: -30px; animation: fallRibbon linear infinite; }
        @keyframes fallRibbon {
            0%   { top: -30px; transform: translateX(0) rotate(0deg); }
            30%  { transform: translateX(20px) rotate(90deg); }
            65%  { transform: translateX(-20px) rotate(180deg); }
            100% { top: 100vh; transform: translateX(0) rotate(360deg); }
        }
    </style>


    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  6) منطق Alpine.js                                            ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    <script>
        // يبني كائن حركات x-transition لاستخدامه عبر x-bind
        const makeFx = (prefix) => ({
            'x-transition:enter':       `${prefix}-active`,
            'x-transition:enter-start': `${prefix}-from`,
            'x-transition:enter-end':   `${prefix}-to`,
            'x-transition:leave':       `${prefix}-active`,
            'x-transition:leave-start': `${prefix}-to`,
            'x-transition:leave-end':   `${prefix}-from`,
        });

        document.addEventListener('alpine:init', () => {
            Alpine.data('agencyDashboard', ({ isActive }) => ({

                // ----- الحالة -----
                isActive,
                modal: null,          // 'settings' | 'bank' | 'bus' | 'driver' | null
                editMode: false,      // وضع تعديل الإعدادات
                notice: '',           // رسالة تنبيه "غير متاح"
                activeTab: 'drivers',

                tripFilter: { search: '', type: 'all', status: 'all' },
                tabSearch:  { drivers: '', fleet: '', banks: '' },
                tabPlaceholders: {
                    drivers: 'ابحث باسم الكابتن أو رقم الجوال...',
                    fleet:   'ابحث بموديل المركبة أو رقم اللوحة...',
                    banks:   'ابحث باسم البنك أو IBAN...',
                },

                // ----- حركات الظهور -----
                overlayFx: makeFx('fx-overlay'),
                panelFx:   makeFx('fx-panel'),
                viewFx: {   // ظهور فقط (بدون حركة خروج) لتفادي تداخل العناصر عند التبديل
                    'x-transition:enter':       'fx-view-active',
                    'x-transition:enter-start': 'fx-view-from',
                    'x-transition:enter-end':   'fx-view-to',
                },

                // ----- النوافذ -----
                open(name) {
                    this.editMode = false;
                    this.modal = name;
                },
                openIfActive(name, message) {
                    this.isActive ? this.open(name) : (this.notice = message);
                },
                close() {
                    if (this.notice) { this.notice = ''; return; }
                    this.modal = null;
                },

                // ----- التصفية -----
                matchTrip(el) {
                    const { search, type, status } = this.tripFilter;
                    const d = el.dataset;
                    return d.search.includes(search.toLowerCase().trim())
                        && (type === 'all' || d.type === type)
                        && (status === 'all' || d.group === status);
                },
                matchRow(el, tab) {
                    return el.dataset.search.includes(this.tabSearch[tab].toLowerCase().trim());
                },
            }));
        });
    </script>

    @if ($isPending)
        <script>
            // قصاصات الاحتفال للوكالة الجديدة
            document.addEventListener('DOMContentLoaded', () => {
                const box = document.getElementById('confetti-container');
                if (!box) return;
                const colors = ['#f59e0b', '#ef4444', '#3b82f6', '#10b981', '#ec4899', '#8b5cf6', '#ffeb3b'];
                const rand = (min, max) => Math.random() * (max - min) + min;

                for (let i = 0; i < 80; i++) {
                    const piece = document.createElement('div');
                    piece.className = 'confetti-piece';
                    piece.style.cssText = `
                        width: ${rand(4, 10)}px;
                        height: ${rand(10, 26)}px;
                        left: ${rand(0, 100)}vw;
                        opacity: ${rand(.2, 1)};
                        background: ${colors[Math.floor(Math.random() * colors.length)]};
                        animation-duration: ${rand(3, 6)}s;
                        animation-delay: ${rand(0, 4)}s;
                    `;
                    box.appendChild(piece);
                }
            });
        </script>
    @endif

@endsection