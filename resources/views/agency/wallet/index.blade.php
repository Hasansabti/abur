@extends('layouts.agency')

@section('content')
@php
    // الوكالة الحالية (لعرض نظام العمولة الجديد). عدّل الـ guard إذا كان اسمه مختلف.
    $agencyUser = $agency ?? auth('agency')->user() ?? auth()->user();

    $perPassenger = (float) ($agencyUser->commission_per_passenger ?? 0);
    $minCommission = $agencyUser?->min_booking_commission !== null ? (float) $agencyUser->min_booking_commission : null;
    $maxCommission = $agencyUser?->max_booking_commission !== null ? (float) $agencyUser->max_booking_commission : null;

    // أمثلة توضيحية للعمولة حسب عدد المسافرين
    $commissionExamples = [];
    if ($agencyUser && method_exists($agencyUser, 'calculateBookingCommission')) {
        foreach ([1, 3, 5, 10] as $n) {
            $commissionExamples[$n] = $agencyUser->calculateBookingCommission($n);
        }
    }
@endphp

<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6" dir="rtl" 
     x-data="{ 
         showPayoutModal: false,
         showDetailModal: false,
         showCommissionInfo: false,
         selectedTrx: null,
         maxBalance: {{ $availableBalance }},
         payoutAmount: '',
         activeTab: '{{ request('type', 'all') }}'
     }">
    
    {{-- تنبيهات النظام --}}
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-200/80 text-sm font-medium flex items-center justify-between shadow-sm"
             x-data="{ show: true }" x-show="show">
            <div class="flex items-center gap-2.5">
                <div class="p-1 bg-emerald-100 rounded-lg">
                    <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <span>{{ session('success') }}</span>
            </div>
            <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 text-rose-700 border border-rose-200/80 text-sm font-medium flex items-center justify-between shadow-sm"
             x-data="{ show: true }" x-show="show">
            <div class="flex items-center gap-2.5">
                <div class="p-1 bg-rose-100 rounded-lg">
                    <svg class="w-4 h-4 text-rose-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span>{{ session('error') }}</span>
            </div>
            <button @click="show = false" class="text-rose-500 hover:text-rose-700 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 text-rose-700 border border-rose-200/80 text-sm font-medium shadow-sm">
            <ul class="list-disc pr-5 text-xs space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- الهيدر الرئيسي والإجراءات السريعة -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h3 class="text-2xl font-bold text-slate-900 tracking-tight">الإدارة المالية والمحفظة</h3>

                {{-- شارة نظام العمولة الجديد --}}
                <button type="button" @click="showCommissionInfo = !showCommissionInfo"
                        class="inline-flex flex-wrap items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100/80 hover:bg-indigo-100 transition-colors">
                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                    <span>عمولة المنصة: {{ number_format($perPassenger, 2) }} ر.س / مسافر</span>
                    @if($minCommission !== null)
                        <span class="text-indigo-400">•</span>
                        <span>أدنى {{ number_format($minCommission, 2) }}</span>
                    @endif
                    @if($maxCommission !== null)
                        <span class="text-indigo-400">•</span>
                        <span>أعلى {{ number_format($maxCommission, 2) }}</span>
                    @endif
                    <svg class="w-3 h-3 transition-transform" :class="showCommissionInfo ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>
            <p class="text-xs text-slate-500 mt-1">تتبع الأرباح المتاحة، الحركات المالية المباشرة، والعمولات المقتطعة عن كل حجز.</p>
        </div>
        
        <div class="flex items-center gap-2.5">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl font-semibold text-xs shadow-sm transition-all">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>تصدير كشف حساب (CSV)</span>
            </a>

            @if(!$hasPendingPayout && $availableBalance >= 100)
                <button @click="showPayoutModal = true" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold text-xs shadow-sm shadow-indigo-200 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <span>طلب سحب أرباح</span>
                </button>
            @endif
        </div>
    </div>

    {{-- شرح طريقة احتساب العمولة (قابل للطي) --}}
    <div x-show="showCommissionInfo" x-cloak x-transition
         class="p-4 rounded-2xl bg-indigo-50/60 border border-indigo-100 text-xs text-indigo-900 space-y-3">
        <div class="font-bold text-sm text-indigo-900">كيف تُحسب عمولة المنصة؟</div>
        <p class="leading-relaxed text-indigo-800">
            تُحسب العمولة على كل حجز بضرب عدد المسافرين في
            <strong>{{ number_format($perPassenger, 2) }} ر.س</strong>،
            @if($minCommission !== null)
                وبحد أدنى <strong>{{ number_format($minCommission, 2) }} ر.س</strong> للحجز الواحد
            @endif
            @if($minCommission !== null && $maxCommission !== null)
                وبحد أعلى
            @elseif($maxCommission !== null)
                وبحد أعلى
            @endif
            @if($maxCommission !== null)
                <strong>{{ number_format($maxCommission, 2) }} ر.س</strong> للحجز الواحد.
            @else
                {{ $minCommission === null ? 'بدون حد أدنى أو أعلى.' : 'وبدون حد أعلى.' }}
            @endif
            ويُضاف الباقي لمحفظتك كصافي ربح.
        </p>

        @if(!empty($commissionExamples))
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                @foreach($commissionExamples as $n => $value)
                    <div class="p-2.5 bg-white rounded-xl border border-indigo-100 text-center">
                        <div class="text-[10px] text-slate-400">حجز {{ $n }} {{ $n === 1 ? 'مسافر' : 'مسافرين' }}</div>
                        <div class="font-bold font-mono text-indigo-700 mt-0.5">{{ number_format($value, 2) }} <span class="text-[10px] font-normal text-slate-400">ر.س</span></div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- كروت الملخص المالي (4 كروت مرتبة) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- إجمالي الأرباح الصافية -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-sm">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 text-xs font-medium">إجمالي الأرباح الصافية</span>
                    <span class="inline-flex p-2 bg-emerald-50 rounded-xl text-emerald-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                </div>
                <div class="text-2xl font-bold text-slate-800 mt-3 tracking-tight">
                    {{ number_format($totalApprovedPayouts ?? 0, 2) }} <span class="text-xs font-normal text-slate-400">ر.س</span>
                </div>
            </div>
        </div>

        <!-- الرصيد المتاح للسحب -->
        <div class="bg-slate-900 text-white rounded-2xl p-5 shadow-sm relative overflow-hidden flex flex-col justify-between group">
            <div class="absolute -left-10 -bottom-10 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl group-hover:bg-indigo-500/20 transition-all"></div>
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 text-xs font-medium">الرصيد المتاح للسحب</span>
                    <span class="inline-flex p-2 bg-white/10 rounded-xl backdrop-blur-md">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </span>
                </div>
                <div class="text-2xl font-bold mt-3 tracking-tight">
                    {{ number_format($availableBalance, 2) }} <span class="text-xs font-normal text-slate-400">ر.س</span>
                </div>
            </div>
            
            <div class="mt-4">
                @if($hasPendingPayout)
                    <div class="w-full py-1.5 px-2 bg-amber-500/10 border border-amber-500/20 text-amber-300 rounded-xl text-[11px] flex items-center justify-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-ping"></span>
                        <span>طلب سحب قيد المعالجة</span>
                    </div>
                @else
                    <button @click="showPayoutModal = true" 
                            {{ $availableBalance < 100 ? 'disabled' : '' }}
                            class="w-full py-2 px-3 bg-indigo-600 hover:bg-indigo-500 text-white transition-colors rounded-xl font-semibold text-xs shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                        {{ $availableBalance < 100 ? 'الحد الأدنى 100 ر.س' : 'طلب سحب الآن' }}
                    </button>
                @endif
            </div>
        </div>

        <!-- الرصيد المعلق -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-sm relative">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 text-xs font-medium">الرصيد المعلق (رحلات قادمة)</span>
                    <span class="inline-flex p-2 bg-amber-50 rounded-xl text-amber-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <div class="text-2xl font-bold text-slate-800 mt-3 tracking-tight">
                    {{ number_format($pendingBalance, 2) }} <span class="text-xs font-normal text-slate-400">ر.س</span>
                </div>
            </div>
            <div class="mt-4 text-[11px] text-slate-400 flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>يتحول تلقائياً لاتاحة السحب بعد اكتمال الرحلة</span>
            </div>
        </div>

        <!-- استهلاك الواتساب -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-sm">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 text-xs font-medium">إشعارات الواتساب (الشهر)</span>
                    <span class="inline-flex p-2 bg-slate-50 rounded-xl text-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </span>
                </div>
                <div class="text-2xl font-bold text-slate-800 mt-3">
                    {{ $whatsappUsage['sent_count'] }} <span class="text-slate-400 font-normal text-xs">/ {{ $whatsappUsage['limit'] }} رسالة</span>
                </div>
                
                @php
                    $percentage = min(100, round(($whatsappUsage['sent_count'] / max(1, $whatsappUsage['limit'])) * 100));
                @endphp
                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2.5 overflow-hidden">
                    <div class="h-full {{ $whatsappUsage['extra_messages'] > 0 ? 'bg-rose-500' : 'bg-indigo-600' }} transition-all duration-500" style="width: {{ $percentage }}%"></div>
                </div>
            </div>
            
            <div class="mt-3">
                @if($whatsappUsage['extra_messages'] > 0)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-medium bg-rose-50 text-rose-700 border border-rose-100">
                        تجاوز: {{ $whatsappUsage['extra_messages'] }} (+{{ number_format($whatsappUsage['extra_fee'], 2) }} ر.س)
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">
                        ضمن الاستخدام العادل
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- قسم الجداول والعمليات --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- جدول كشف الحساب السريع -->
        <div class="lg:col-span-8 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col justify-between">
            <div>
                <!-- رأس الجدول المدمج مع البحث والفلاتر -->
                <div class="p-4 border-b border-slate-100 bg-slate-50/50 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <h4 class="font-bold text-slate-800 text-sm">سجل الحركات المالية (كشف الحساب)</h4>
                            <span class="px-2 py-0.5 rounded-full bg-slate-200/70 text-slate-600 text-[11px] font-semibold">
                                {{ $transactions->total() }}
                            </span>
                        </div>

                        <!-- نموذج البحث والفلترة -->
                        <form method="GET" action="{{ url()->current() }}" class="flex items-center gap-2 w-full sm:w-auto">
                            <input type="hidden" name="type" value="{{ request('type', 'all') }}">
                            <div class="relative w-full sm:w-64">
                                <input type="text" name="search" value="{{ request('search') }}" 
                                       placeholder="بحث بالوصف..." 
                                       class="w-full pr-9 pl-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                            </div>

                            <select name="date_range" onchange="this.form.submit()" class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-600 focus:ring-2 focus:ring-indigo-500/20 outline-none">
                                <option value="all" {{ request('date_range') == 'all' ? 'selected' : '' }}>كل الأوقات</option>
                                <option value="this_month" {{ request('date_range') == 'this_month' ? 'selected' : '' }}>هذا الشهر</option>
                                <option value="last_month" {{ request('date_range') == 'last_month' ? 'selected' : '' }}>الشهر الماضي</option>
                            </select>
                        </form>
                    </div>

                    <!-- التبويبات السريعة للفلترة -->
                    <div class="flex items-center gap-1.5 border-t border-slate-200/60 pt-2.5 overflow-x-auto">
                        <a href="{{ request()->fullUrlWithQuery(['type' => 'all', 'transactions_page' => 1]) }}" 
                           class="px-3 py-1 rounded-lg text-xs font-medium transition-colors shrink-0 {{ request('type', 'all') == 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/60' }}">
                            الكل
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['type' => 'booking_credit', 'transactions_page' => 1]) }}" 
                           class="px-3 py-1 rounded-lg text-xs font-medium transition-colors shrink-0 {{ request('type') == 'booking_credit' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/60' }}">
                            إيرادات الحجز
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['type' => 'payout', 'transactions_page' => 1]) }}" 
                           class="px-3 py-1 rounded-lg text-xs font-medium transition-colors shrink-0 {{ request('type') == 'payout' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/60' }}">
                            طلب سحب
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['type' => 'whatsapp_fee', 'transactions_page' => 1]) }}" 
                           class="px-3 py-1 rounded-lg text-xs font-medium transition-colors shrink-0 {{ request('type') == 'whatsapp_fee' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/60' }}">
                            رسوم الواتساب
                        </a>
                    </div>
                </div>

                <!-- الجدول -->
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="px-5 py-3">التاريخ</th>
                                <th class="px-5 py-3">النوع</th>
                                <th class="px-5 py-3">الوصف</th>
                                <th class="px-5 py-3">المبلغ الصافي</th>
                                <th class="px-5 py-3">الحالة</th>
                                <th class="px-5 py-3 text-center">التفاصيل</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($transactions as $trx)
                                @php
                                    // ===== تفصيل العمولة للحركة =====
                                    // الأولوية: القيم المخزنة على الحركة نفسها (الأدق، لأن العمولة تختلف من حجز لحجز)
                                    $netAmount        = (float) $trx->amount;
                                    $commissionAmount = isset($trx->commission_amount) ? (float) $trx->commission_amount : null;
                                    $grossAmount      = isset($trx->gross_amount) ? (float) $trx->gross_amount : null;
                                    $passengersCount  = $trx->passengers_count ?? null;
                                    $legacyRate       = null;

                                    if ($grossAmount === null && $commissionAmount !== null) {
                                        $grossAmount = $netAmount + $commissionAmount;
                                    }

                                    // حركات قديمة (قبل التحويل لنظام لكل مسافر) كانت تخزن نسبة مئوية
                                    if ($grossAmount === null && !empty($trx->commission_percentage)) {
                                        $legacyRate  = (float) $trx->commission_percentage;
                                        $grossAmount = $netAmount / max(0.01, (1 - ($legacyRate / 100)));
                                    }

                                    if ($commissionAmount === null && $grossAmount !== null) {
                                        $commissionAmount = $grossAmount - $netAmount;
                                    }

                                    $hasBreakdown = $trx->type === 'booking_credit' && $grossAmount !== null;

                                    $trxPayload = [
                                        'id'                => (string) $trx->id,
                                        'date'              => $trx->created_at->format('Y-m-d H:i:s'),
                                        'type'              => $trx->type,
                                        'description'       => (string) $trx->description,
                                        'amount'            => number_format($netAmount, 2),
                                        'has_breakdown'     => $hasBreakdown,
                                        'gross_amount'      => $grossAmount !== null ? number_format($grossAmount, 2) : null,
                                        'commission_amount' => $commissionAmount !== null ? number_format($commissionAmount, 2) : null,
                                        'passengers_count'  => $passengersCount,
                                        'legacy_rate'       => $legacyRate,
                                        'status'            => $trx->status,
                                    ];
                                @endphp
                                <tr class="hover:bg-indigo-50/30 transition-colors cursor-pointer group"
                                    @click="selectedTrx = {{ json_encode($trxPayload, JSON_UNESCAPED_UNICODE) }}; showDetailModal = true">
                                    <td class="px-5 py-3.5 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                        {{ $trx->created_at->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        @if($trx->type === 'booking_credit')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                إيراد حجز
                                            </span>
                                        @elseif($trx->type === 'payout')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-sky-50 text-sky-700 border border-sky-100">
                                                سحب أرباح
                                            </span>
                                        @elseif($trx->type === 'whatsapp_fee')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-100">
                                                رسوم واتساب
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700">{{ $trx->type }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-700 max-w-xs truncate" title="{{ $trx->description }}">
                                        {{ $trx->description }}
                                    </td>
                                    <td class="px-5 py-3.5 font-bold text-xs whitespace-nowrap {{ $trx->amount < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $trx->amount > 0 ? '+' : '' }}{{ number_format($trx->amount, 2) }} <span class="font-normal text-[10px] text-slate-400">ر.س</span>
                                        @if($hasBreakdown && $commissionAmount > 0)
                                            <span class="block font-normal text-[10px] text-rose-400">عمولة المنصة: -{{ number_format($commissionAmount, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        @if($trx->status === 'pending')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-100">معلق</span>
                                        @elseif($trx->status === 'available' || $trx->status === 'completed')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">مكتمل</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700">{{ $trx->status }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                        <button type="button" class="text-slate-400 group-hover:text-indigo-600 p-1 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span class="text-xs">لا توجد حركات مالية مسجلة لهذه الفلاتر.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                {{ $transactions->appends(request()->query())->links() }}
            </div>
        </div>

        <!-- طلبات السحب الأخيرة -->
        <div class="lg:col-span-4 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h4 class="font-bold text-slate-800 text-sm">طلبات التحويل البنكي</h4>
                    <span class="text-[11px] text-slate-400">سجل السحوبات</span>
                </div>

                <ul class="divide-y divide-slate-100">
                    @forelse($payoutRequests as $payout)
                        <li class="px-5 py-3.5 flex items-center justify-between hover:bg-slate-50/60 transition-colors">
                            <div>
                                <span class="block font-bold text-slate-800 text-xs">
                                    {{ number_format($payout->requested_amount, 2) }} <small class="text-slate-400 font-normal">ر.س</small>
                                </span>
                                @if($payout->status === 'approved' && $payout->net_payout_amount !== null && (float) $payout->whatsapp_fee_deducted > 0)
                                    <span class="text-[10px] text-slate-400 block mt-0.5">
                                        الصافي المحوّل: {{ number_format($payout->net_payout_amount, 2) }} ر.س
                                        (خصم واتساب {{ number_format($payout->whatsapp_fee_deducted, 2) }})
                                    </span>
                                @endif
                                <span class="text-[10px] text-slate-400 font-mono mt-0.5 block">{{ $payout->created_at->format('Y-m-d') }}</span>
                            </div>
                            <div>
                                @if($payout->status === 'pending')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-100">قيد المراجعة</span>
                                @elseif($payout->status === 'approved')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">تم التحويل</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-rose-50 text-rose-700 border border-rose-100">مرفوض</span>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-slate-400 text-xs">
                            لا توجد طلبات سحب سابقة.
                        </li>
                    @endforelse
                </ul>
            </div>

            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                {{ $payoutRequests->appends(request()->query())->links() }}
            </div>
        </div>
    </div>

    {{-- Modal تفاصيل الحركة المالية --}}
    <div x-show="showDetailModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="showDetailModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block w-full max-w-md p-6 my-8 text-right align-middle transition-all transform bg-white shadow-xl rounded-2xl relative z-10 border border-slate-100"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                
                <template x-if="selectedTrx">
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-sm font-bold text-slate-800">تفاصيل الحركة المالية</h3>
                            <button @click="showDetailModal = false" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="py-4 space-y-3 text-xs">
                            <div class="flex justify-between py-1.5 border-b border-slate-50">
                                <span class="text-slate-400">رقم المرجع:</span>
                                <span class="font-mono font-semibold text-slate-700" x-text="'#TRX-' + selectedTrx.id"></span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-50">
                                <span class="text-slate-400">التاريخ والوقت:</span>
                                <span class="font-mono text-slate-700" x-text="selectedTrx.date"></span>
                            </div>

                            {{-- إيراد حجز مع تفصيل العمولة --}}
                            <template x-if="selectedTrx.type === 'booking_credit' && selectedTrx.has_breakdown">
                                <div class="bg-indigo-50/50 p-3 rounded-xl space-y-2 border border-indigo-100/80">
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">سعر الحجز الإجمالي:</span>
                                        <span class="font-semibold text-slate-800" x-text="selectedTrx.gross_amount + ' ر.س'"></span>
                                    </div>
                                    <div class="flex justify-between text-rose-600">
                                        <span class="flex items-center gap-1 flex-wrap">
                                            <span>خصم عمولة المنصة</span>
                                            <template x-if="selectedTrx.passengers_count">
                                                <span class="px-1.5 py-0.5 rounded bg-rose-100 text-[10px] font-bold"
                                                      x-text="selectedTrx.passengers_count + (selectedTrx.passengers_count == 1 ? ' مسافر' : ' مسافرين')"></span>
                                            </template>
                                            <template x-if="selectedTrx.legacy_rate">
                                                <span class="px-1.5 py-0.5 rounded bg-rose-100 text-[10px] font-bold"
                                                      x-text="selectedTrx.legacy_rate + '% (نظام سابق)'"></span>
                                            </template>
                                        </span>
                                        <span class="font-semibold" x-text="'-' + selectedTrx.commission_amount + ' ر.س'"></span>
                                    </div>
                                    <div class="pt-2 border-t border-indigo-100 flex justify-between font-bold text-emerald-700 text-sm">
                                        <span>الصافي للمحفظة:</span>
                                        <span x-text="'+' + selectedTrx.amount + ' ر.س'"></span>
                                    </div>
                                </div>
                            </template>

                            {{-- إيراد حجز بدون تفصيل مخزّن --}}
                            <template x-if="selectedTrx.type === 'booking_credit' && !selectedTrx.has_breakdown">
                                <div class="bg-indigo-50/50 p-3 rounded-xl space-y-2 border border-indigo-100/80">
                                    <div class="pt-0 flex justify-between font-bold text-emerald-700 text-sm">
                                        <span>الصافي للمحفظة (بعد خصم العمولة):</span>
                                        <span x-text="'+' + selectedTrx.amount + ' ر.س'"></span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 leading-relaxed">
                                        تفصيل العمولة غير متوفر لهذه الحركة.
                                    </p>
                                </div>
                            </template>

                            <template x-if="selectedTrx.type !== 'booking_credit'">
                                <div class="flex justify-between py-1.5 border-b border-slate-50">
                                    <span class="text-slate-400">المبلغ:</span>
                                    <span class="font-bold text-sm text-indigo-600" x-text="selectedTrx.amount + ' ر.س'"></span>
                                </div>
                            </template>

                            <div class="py-1.5">
                                <span class="text-slate-400 block mb-1">الوصف الكامل:</span>
                                <p class="p-2.5 bg-slate-50 rounded-xl text-slate-700 leading-relaxed" x-text="selectedTrx.description"></p>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex justify-end">
                            <button @click="showDetailModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold hover:bg-slate-200">
                                إغلاق
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Modal طلب السحب --}}
    <div x-show="showPayoutModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showPayoutModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block w-full max-w-md p-6 my-8 text-right align-middle transition-all transform bg-white shadow-xl rounded-2xl relative z-10 border border-slate-100">
                <form action="{{ route('agency.wallet.request-payout') }}" method="POST">
                    @csrf
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-800">تقديم طلب سحب الأرباح</h3>
                        <button type="button" @click="showPayoutModal = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="py-4 space-y-4">
                        <p class="text-xs text-slate-500 leading-relaxed bg-indigo-50/50 p-3 rounded-xl border border-indigo-100">
                            سيتم إرسال الطلب للفريق المالي بالمنصة، وتطبيق أي خصومات مستحقة لرسائل الواتساب الزائدة إن وجد.
                            عمولة المنصة تم خصمها مسبقاً من كل حجز، فلن تُخصم مرة ثانية عند السحب.
                        </p>

                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-xs font-semibold text-slate-700">المبلغ المطلوب (ر.س)</label>
                                <span class="text-[11px] text-slate-400">المتاح: {{ number_format($availableBalance, 2) }} ر.س</span>
                            </div>
                            <input type="number" step="0.01" min="100" max="{{ $availableBalance }}" 
                                   name="requested_amount" x-model="payoutAmount"
                                   class="w-full px-3.5 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none text-xs text-slate-800" 
                                   placeholder="مثال: 500" required>

                            <div class="flex gap-1.5 mt-2">
                                <button type="button" @click="payoutAmount = (maxBalance * 0.25).toFixed(2)" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-[10px]">25%</button>
                                <button type="button" @click="payoutAmount = (maxBalance * 0.50).toFixed(2)" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-[10px]">50%</button>
                                <button type="button" @click="payoutAmount = (maxBalance * 0.75).toFixed(2)" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-[10px]">75%</button>
                                <button type="button" @click="payoutAmount = maxBalance.toFixed(2)" class="px-2 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-[10px] font-semibold">الرصيد كامل</button>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="showPayoutModal = false" class="px-3.5 py-2 border border-slate-300 text-slate-600 rounded-xl text-xs font-medium hover:bg-slate-50">إلغاء</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-semibold hover:bg-indigo-700 shadow-sm">تأكيد الطلب</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection