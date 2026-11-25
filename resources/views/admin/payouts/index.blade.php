@extends('layouts.admin')

@section('content')
    @php
        $totalPlatformCommission = \App\Models\Booking::whereIn('payment_status', ['paid', 'paidof'])->sum(
            'platform_commission',
        );
        $rawAgencies = $agencies ?? \App\Models\Agency::latest()->get();

        // تحضير بيانات الوكالات بصيغة خفيفة لـ Alpine.js
        $agenciesJson = $rawAgencies
            ->map(function ($a) use ($agencySubs) {
                $sub = ($agencySubs ?? collect())->get($a->id);
                return [
                    'id' => $a->id,
                    'name' => $a->name,
                    'per_passenger' => (float) ($a->commission_per_passenger ?? 0),
                    'min' => $a->min_booking_commission !== null ? (float) $a->min_booking_commission : null,
                    'max' => $a->max_booking_commission !== null ? (float) $a->max_booking_commission : null,
                    'bank' => $a->bank_name ?? '',
                    'iban' => $a->ibanA ?? ($a->iban ?? ''),
                    'plan' => $sub?->plan?->name,
                    'plan_ends' => $sub?->ends_at?->format('Y-m-d'),
                ];
            })
            ->values();
    @endphp

    <div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6" dir="rtl" x-data="{
        activeApproveModal: null,
        activeRejectModal: null,
        activeGlobalCommissionModal: false,
    
        // إدارة الوكالات والعمولات ديناميكياً
        agenciesList: {{ json_encode($agenciesJson) }},
        activeAgencySearch: '',
        agencyPage: 1,
        agencyPerPage: 10,
    
        // بيانات مودال تعديل العمولة الفردية
        editingAgency: null,
    
        // كشف الحساب التفصيلي
        ledger: { open: false, loading: false, error: '', agencyName: '', data: null, tab: 'trips', q: '', tripFilter: null, payoutId: null, payoutAmount: null },
        async openLedger(id, name, payoutAmount = null, payoutId = null) {
            this.ledger = { open: true, loading: true, error: '', agencyName: name, data: null, tab: 'trips', q: '', tripFilter: null, payoutId: payoutId, payoutAmount: payoutAmount };
            try {
                const r = await fetch('{{ url('admin/agencies') }}/' + id + '/ledger', { headers: { 'Accept': 'application/json' } });
                if (!r.ok) throw new Error();
                this.ledger.data = await r.json();
            } catch (e) {
                this.ledger.error = 'تعذّر تحميل كشف الحساب';
            }
            this.ledger.loading = false;
        },
        showTripBookings(tripId) {
            this.ledger.tripFilter = tripId;
            this.ledger.tab = 'bookings';
        },
        get ledgerBookings() {
            if (!this.ledger.data) return [];
            let list = this.ledger.data.bookings;
            if (this.ledger.tripFilter !== null) list = list.filter(b => b.trip_id === this.ledger.tripFilter);
            let q = (this.ledger.q || '').trim().toLowerCase();
            if (q) {
                list = list.filter(b =>
                    ('bk-' + b.id).includes(q) ||
                    (b.trip || '').toLowerCase().includes(q) ||
                    (b.customer || '').toLowerCase().includes(q) ||
                    (b.travelers || []).some(t => (t || '').toLowerCase().includes(q))
                );
            }
            return list;
        },
        get ledgerFilterTripName() {
            if (!this.ledger.data || this.ledger.tripFilter === null) return '';
            let t = this.ledger.data.trips.find(x => x.trip_id === this.ledger.tripFilter);
            return t ? t.trip : '';
        },
        money(v) {
            return Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        txLabel(t) {
            return { payout: 'سحب أرباح', whatsapp_fee: 'رسوم واتساب', booking: 'إيراد حجز', commission: 'عمولة منصة', refund: 'استرجاع' } [t] || t;
        },
    
        get filteredAgencies() {
            if (!this.activeAgencySearch.trim()) return this.agenciesList;
            let q = this.activeAgencySearch.toLowerCase();
            return this.agenciesList.filter(a =>
                a.name.toLowerCase().includes(q) ||
                a.bank.toLowerCase().includes(q) ||
                a.iban.toLowerCase().includes(q)
            );
        },
        get paginatedAgencies() {
            let start = (this.agencyPage - 1) * this.agencyPerPage;
            return this.filteredAgencies.slice(start, start + this.agencyPerPage);
        },
        get totalAgencyPages() {
            return Math.ceil(this.filteredAgencies.length / this.agencyPerPage) || 1;
        },
        openEditModal(agency) {
            this.editingAgency = { ...agency };
        },
        num(v) {
            if (v === null || v === undefined || v === '') return null;
            let n = parseFloat(v);
            return isNaN(n) ? null : n;
        },
        fmt(v) {
            return Number(v).toFixed(2).replace(/\.00$/, '');
        },
        // نص مختصر للعمولة في الكروت
        commissionLabel(a) {
            let t = this.fmt(this.num(a.per_passenger) ?? 0) + ' ر.س / مسافر';
            let min = this.num(a.min),
                max = this.num(a.max);
            if (min !== null) t += ' • أدنى ' + this.fmt(min);
            if (max !== null) t += ' • أعلى ' + this.fmt(max);
            return t;
        },
        // حساب مثال حي لعمولة حجز بعدد مسافرين معين
        calcCommission(a, n) {
            if (!a) return 0;
            let c = n * (this.num(a.per_passenger) ?? 0);
            let min = this.num(a.min),
                max = this.num(a.max);
            if (min !== null && c < min) c = min;
            if (max !== null && c > max) c = max;
            return c;
        }
    }">

        {{-- 1. الهيدر الرئيسي والأزرار الإجرائية --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2 border-b border-slate-200/60">
            <div>
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">إدارة طلبات سحب الأرباح والمالية</h3>
                <p class="text-xs text-slate-500 mt-1">مراجعة واعتماد التحويلات البنكية للوكالات، متابعة أرباح المنصة من
                    الحجوزات، وتطبيق استقطاعات الواتساب.</p>
            </div>

            <div class="flex items-center gap-2 self-start md:self-auto shrink-0">
                <button type="button" @click="activeGlobalCommissionModal = true"
                    class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition-all flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                    <span>تعديل عمولة جميع الوكالات</span>
                </button>

                <button onclick="window.print()"
                    class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>طباعة التقرير</span>
                </button>
            </div>
        </div>

        {{-- 2. كروت الملخص المالي (KPI Cards) --}}
        <a href="#commissions-table"
            class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/80 shadow-sm block hover:border-amber-300 transition-all">
            <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/80 shadow-sm">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[11px] font-bold text-amber-800">إجمالي أرباح المنصة (من الحجوزات)</span>
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                </div>
                <div class="flex items-baseline justify-between">
                    <span class="text-xl font-bold text-amber-700 font-mono">
                        +{{ number_format($totalPlatformCommission, 2) }}
                    </span>
                    <span class="text-xs font-semibold text-amber-600">ر.س</span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
                <span class="text-[11px] font-semibold text-slate-400 block mb-1">طلبات السحب المعلقة</span>
                <div class="flex items-baseline justify-between">
                    <span class="text-xl font-bold text-slate-800 font-mono">
                        {{ number_format($pendingTotal ?? $payoutRequests->where('status', 'pending')->sum('requested_amount'), 2) }}
                    </span>
                    <span class="text-xs text-slate-400">ر.س</span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
                <span class="text-[11px] font-semibold text-slate-400 block mb-1">المبالغ المحولة (المعتمدة)</span>
                <div class="flex items-baseline justify-between">
                    <span class="text-xl font-bold text-emerald-600 font-mono">
                        {{ number_format($approvedTotal ?? $payoutRequests->where('status', 'approved')->sum('requested_amount'), 2) }}
                    </span>
                    <span class="text-xs text-slate-400">ر.س</span>
                </div>
                <span class="text-[10px] text-amber-600 mt-1 block">اضغط لعرض تفاصيل المصدر ↓</span>
        </a>

        {{-- تنبيهات النظام --}}
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-200/80 text-sm font-medium flex items-center justify-between shadow-sm"
                x-data="{ show: true }" x-show="show">
                <div class="flex items-center gap-2.5">
                    <div class="p-1 bg-emerald-100 rounded-lg">
                        <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 rounded-2xl bg-rose-50 text-rose-700 border border-rose-200/80 text-sm font-medium shadow-sm"
                x-data="{ show: true }" x-show="show">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-rose-50 text-rose-700 border border-rose-200/80 text-sm font-medium shadow-sm"
                x-data="{ show: true }" x-show="show">
                <div class="flex items-center justify-between mb-1">
                    <span class="font-bold">يرجى تصحيح الأخطاء التالية:</span>
                    <button @click="show = false" class="text-rose-500 hover:text-rose-700 p-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <ul class="list-disc pr-5 text-xs space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- 3. قسم كافة الوكالات المُنظّم --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden" x-data="{ openSection: true }">
            <div class="px-5 py-3.5 bg-slate-900 text-white flex items-center justify-between cursor-pointer select-none"
                @click="openSection = !openSection">
                <div class="flex items-center gap-2.5">
                    <div class="p-1 bg-indigo-500/20 rounded-lg border border-indigo-400/30">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h5m0 0v-5a2 2 0 00-2-2h-1a2 2 0 00-2 2v5" />
                        </svg>
                    </div>
                    <h4 class="font-bold text-xs tracking-wide">التحكم بعمولات جميع الوكالات المسجلة</h4>
                    <span
                        class="px-2 py-0.5 rounded-full bg-slate-800 text-indigo-300 text-[10px] font-semibold border border-slate-700"
                        x-text="agenciesList.length + ' وكالة'"></span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-slate-400" x-text="openSection ? 'طي القسم' : 'توسيع'"></span>
                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200"
                        :class="openSection ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <div x-show="openSection" x-transition class="p-4 space-y-4 bg-slate-50/40">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="relative w-full sm:w-80">
                        <input type="text" x-model="activeAgencySearch" @input="agencyPage = 1"
                            placeholder="بحث باسم الوكالة أو البنك أو الآيبان..."
                            class="w-full pl-3 pr-8 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-3" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <div class="text-[11px] text-slate-500 flex items-center gap-1.5 self-end sm:self-auto">
                        <span>إجمالي النتائج:</span>
                        <strong class="text-slate-800" x-text="filteredAgencies.length"></strong>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                    <template x-for="agency in paginatedAgencies" :key="agency.id">
                        <div
                            class="p-3 bg-white border border-slate-200/80 rounded-xl flex flex-col justify-between hover:border-indigo-300 hover:shadow-sm transition-all group">
                            <div>
                                <h5 class="font-bold text-xs text-slate-800 truncate" x-text="agency.name"
                                    :title="agency.name"></h5>
                                <p class="text-[10px] text-slate-400 mt-0.5 truncate" x-text="agency.bank || 'بدون بنك'">
                                </p>
                            </div>

                            <div class="mt-3 pt-2.5 border-t border-slate-100 space-y-2">
                                <template x-if="agency.plan">
                                    <div
                                        class="flex items-center justify-between gap-1 px-2 py-1 rounded-lg bg-violet-50 border border-violet-100 text-[10px] font-bold text-violet-700">
                                        <span x-text="'📦 باقة: ' + agency.plan"></span>
                                        <span class="font-normal text-violet-500"
                                            x-text="'حتى ' + agency.plan_ends"></span>
                                    </div>
                                </template>
                                <template x-if="!agency.plan">
                                    <div
                                        class="px-2 py-1 rounded-lg bg-slate-50 border border-slate-100 text-[10px] text-slate-400">
                                        بدون اشتراك نشط</div>
                                </template>

                                <div class="flex items-center justify-between gap-2">
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100/80 leading-snug"
                                        x-text="commissionLabel(agency)"></span>
                                    <div class="flex gap-1 shrink-0">
                                        <button type="button" @click="openLedger(agency.id, agency.name)"
                                            class="px-2 py-1 bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-700 rounded-lg text-[11px] font-semibold transition-all">كشف</button>
                                        <button type="button" @click="openEditModal(agency)"
                                            class="px-2 py-1 bg-slate-100 hover:bg-indigo-600 hover:text-white text-slate-700 rounded-lg text-[11px] font-semibold transition-all">تعديل</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div x-show="filteredAgencies.length === 0"
                        class="col-span-full py-8 text-center text-slate-400 text-xs bg-white rounded-xl border border-dashed border-slate-200">
                        لا توجد وكالات مطابقة للبحث.
                    </div>
                </div>

                <div x-show="totalAgencyPages > 1"
                    class="flex items-center justify-between pt-2 border-t border-slate-200/60 text-xs">
                    <span class="text-[11px] text-slate-500">
                        الصفحة <strong x-text="agencyPage"></strong> من <strong x-text="totalAgencyPages"></strong>
                    </span>

                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="agencyPage--" :disabled="agencyPage <= 1"
                            class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 rounded-lg disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 font-medium transition-all">
                            السابق
                        </button>

                        <div class="flex gap-1">
                            <template x-for="p in totalAgencyPages" :key="p">
                                <button type="button" @click="agencyPage = p"
                                    class="w-7 h-7 rounded-lg text-xs font-semibold transition-all"
                                    :class="agencyPage === p ? 'bg-indigo-600 text-white' :
                                        'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                                    x-text="p">
                                </button>
                            </template>
                        </div>

                        <button type="button" @click="agencyPage++" :disabled="agencyPage >= totalAgencyPages"
                            class="px-2.5 py-1 bg-white border border-slate-200 text-slate-700 rounded-lg disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 font-medium transition-all">
                            التالي
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. الجدول الرئيسي لطلبات السحب --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div
                class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <h4 class="font-bold text-slate-800 text-sm">قائمة طلبات السحب</h4>
                    <span class="px-2.5 py-0.5 rounded-full bg-slate-200/70 text-slate-600 text-xs font-semibold">
                        {{ method_exists($payoutRequests, 'total') ? $payoutRequests->total() : count($payoutRequests) }}
                    </span>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="inline-flex p-1 bg-slate-200/60 rounded-xl overflow-x-auto max-w-full shrink-0">
                        <a href="{{ route('admin.payouts', ['status' => 'pending']) }}"
                            class="px-3 py-1 rounded-lg text-xs font-semibold transition-all whitespace-nowrap {{ $status === 'pending' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                            قيد الانتظار
                        </a>
                        <a href="{{ route('admin.payouts', ['status' => 'approved']) }}"
                            class="px-3 py-1 rounded-lg text-xs font-semibold transition-all whitespace-nowrap {{ $status === 'approved' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                            المعتمدة
                        </a>
                        <a href="{{ route('admin.payouts', ['status' => 'rejected']) }}"
                            class="px-3 py-1 rounded-lg text-xs font-semibold transition-all whitespace-nowrap {{ $status === 'rejected' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                            المرفوضة
                        </a>
                        <a href="{{ route('admin.payouts', ['status' => 'all']) }}"
                            class="px-3 py-1 rounded-lg text-xs font-semibold transition-all whitespace-nowrap {{ $status === 'all' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                            الكل
                        </a>
                    </div>

                    <form method="GET" action="{{ route('admin.payouts') }}" class="flex items-center gap-2">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <div class="relative w-full sm:w-56">
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="بحث باسم الوكالة أو IBAN..."
                                class="w-full pl-3 pr-8 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <button type="submit"
                            class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold transition-all shrink-0">بحث</button>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs text-slate-600">
                    <thead
                        class="bg-slate-50 text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3.5">رقم الطلب</th>
                            <th class="px-4 py-3.5">الوكالة</th>
                            <th class="px-4 py-3.5">الآيبان البنكي</th>
                            <th class="px-4 py-3.5">المبلغ المطلوب سحبه</th>
                            <th class="px-4 py-3.5 bg-emerald-50/50 text-emerald-800">الصافي للتحويل البنكي</th>
                            <th class="px-4 py-3.5">التاريخ</th>
                            <th class="px-4 py-3.5">الحالة</th>
                            <th class="px-4 py-3.5 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($payoutRequests as $payout)
                            @php
                                $agency = $payout->agency;
                                $waData = $agency
                                    ? $agency->calculateCurrentMonthWhatsappFee()
                                    : ['extra_fee' => 0, 'extra_messages' => 0];
                                $requestedAmount = $payout->requested_amount;
                                $whatsappFee =
                                    $payout->status === 'approved'
                                        ? $payout->whatsapp_fee_deducted ?? 0
                                        : $waData['extra_fee'];
                                $netAmount = max(0, $requestedAmount - $whatsappFee);
                            @endphp

                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                                    #{{ $payout->id }}</td>

                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span
                                        class="font-bold text-slate-900 block text-xs">{{ $agency->name ?? 'غير محدد' }}</span>
                                    @if ($agency)
                                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                            <span
                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                                {{ number_format($agency->commission_per_passenger ?? 0, 2) }} ر.س/مسافر
                                                @if ($agency->min_booking_commission !== null)
                                                    • أدنى {{ number_format($agency->min_booking_commission, 2) }}
                                                @endif
                                                @if ($agency->max_booking_commission !== null)
                                                    • أعلى {{ number_format($agency->max_booking_commission, 2) }}
                                                @endif
                                            </span>
                                            <button type="button"
                                                @click="openEditModal({{ json_encode([
                                                    'id' => $agency->id,
                                                    'name' => $agency->name,
                                                    'per_passenger' => (float) ($agency->commission_per_passenger ?? 0),
                                                    'min' => $agency->min_booking_commission !== null ? (float) $agency->min_booking_commission : null,
                                                    'max' => $agency->max_booking_commission !== null ? (float) $agency->max_booking_commission : null,
                                                ]) }})"
                                                class="text-[10px] text-indigo-600 hover:underline font-medium">
                                                تعديل
                                            </button>
                                            @php $rowSub = ($agencySubs ?? collect())->get($agency->id); @endphp
                                            @if ($rowSub)
                                                <span
                                                    class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-violet-50 text-violet-700 border border-violet-100">
                                                    📦 {{ $rowSub->plan->name ?? 'باقة' }}
                                                </span>
                                            @endif
                                            <button type="button"
                                                @click="openLedger({{ $agency->id }}, @js($agency->name), {{ $payout->status === 'pending' ? (float) $requestedAmount : 'null' }}, {{ $payout->id }})"
                                                class="text-[10px] text-emerald-600 hover:underline font-medium">
                                                من أين جاء المبلغ؟
                                            </button>
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap">
                                    <code
                                        class="font-mono bg-slate-100 text-slate-800 px-2 py-0.5 rounded text-[11px] border border-slate-200 dir-ltr">
                                        {{ $agency->ibanA ?? ($agency->iban ?? 'غير مدخل') }}
                                    </code>
                                    <span
                                        class="text-[10px] text-slate-400 block mt-0.5">{{ $agency->bank_name ?? 'غير محدد' }}</span>
                                </td>

                                <td class="px-4 py-4 font-bold text-slate-800 whitespace-nowrap text-xs">
                                    {{ number_format($requestedAmount, 2) }} <span
                                        class="font-normal text-[10px] text-slate-400">ر.س</span>
                                </td>

                                <td
                                    class="px-4 py-4 font-bold text-emerald-600 whitespace-nowrap text-sm bg-emerald-50/30">
                                    {{ number_format($netAmount, 2) }} <span
                                        class="font-normal text-[10px] text-slate-400">ر.س</span>
                                </td>

                                <td class="px-4 py-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                    {{ $payout->created_at->format('Y-m-d H:i') }}
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap">
                                    @if ($payout->status === 'pending')
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200/60">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            معلق
                                        </span>
                                    @elseif($payout->status === 'approved')
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60">تم
                                            التحويل</span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200/60">مرفوض</span>
                                    @endif
                                </td>

                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    @if ($payout->status === 'pending')
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" @click="activeApproveModal = {{ $payout->id }}"
                                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold text-xs transition-all shadow-sm">
                                                اعتماد وتحويل
                                            </button>
                                            <button type="button" @click="activeRejectModal = {{ $payout->id }}"
                                                class="px-3 py-1.5 border border-rose-200 text-rose-600 hover:bg-rose-50 rounded-xl font-semibold text-xs transition-all">
                                                رفض
                                            </button>
                                        </div>
                                    @else
                                        @if ($payout->bank_receipt)
                                            <a href="{{ Storage::url($payout->bank_receipt) }}" target="_blank"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-medium transition-all border border-slate-200">
                                                <span>عرض الإيصال</span>
                                            </a>
                                        @else
                                            <span class="text-slate-400 text-[11px]">لا يوجد إيصال</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="text-xs">لا توجد طلبات سحب حالية.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                {{ $payoutRequests->appends(request()->query())->links() }}
            </div>
        </div>
        {{-- 4-ب. تفاصيل عمولات المنصة من الحجوزات --}}
        <div id="commissions-table"
            class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden scroll-mt-4">
            <div
                class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h4 class="font-bold text-slate-800 text-sm">تفاصيل عمولات المنصة من الحجوزات</h4>
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">
                            {{ $commissionBookings->total() }} حجز
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">كل حجز مدفوع ساهم في إجمالي أرباح المنصة، مع طريقة حساب
                        العمولة.</p>
                </div>

                <form method="GET" action="{{ route('admin.payouts') }}#commissions-table"
                    class="flex items-center gap-2">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <div class="relative w-full sm:w-64">
                        <input type="text" name="csearch" value="{{ $commissionSearch }}"
                            placeholder="رقم الحجز / العميل / الرحلة / الوكالة..."
                            class="w-full pl-3 pr-8 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <button type="submit"
                        class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold shrink-0">بحث</button>
                    @if ($commissionSearch !== '')
                        <a href="{{ route('admin.payouts', ['status' => $status, 'search' => request('search')]) }}#commissions-table"
                            class="text-[11px] text-slate-500 hover:underline shrink-0">مسح</a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[11px] font-semibold text-slate-400 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3.5">رقم الحجز</th>
                            <th class="px-4 py-3.5">التاريخ</th>
                            <th class="px-4 py-3.5">الوكالة</th>
                            <th class="px-4 py-3.5">الرحلة</th>
                            <th class="px-4 py-3.5">العميل</th>
                            <th class="px-4 py-3.5">المسافرون</th>
                            <th class="px-4 py-3.5">إجمالي الحجز</th>
                            <th class="px-4 py-3.5 bg-amber-50/60 text-amber-800">عمولة المنصة</th>
                            <th class="px-4 py-3.5">كيف حُسبت</th>
                            <th class="px-4 py-3.5">الدفع</th>
                            <th class="px-4 py-3.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($commissionBookings as $b)
                            @php
                                $ag = $b->trip?->agency;
                                $n = (int) $b->booking_seats_count;
                                $rate = (float) ($ag->commission_per_passenger ?? 0);
                                $min =
                                    $ag?->min_booking_commission !== null ? (float) $ag->min_booking_commission : null;
                                $max =
                                    $ag?->max_booking_commission !== null ? (float) $ag->max_booking_commission : null;
                                $calc = $n * $rate;
                                $note = $n . ' × ' . number_format($rate, 2);
                                if ($min !== null && $calc < $min) {
                                    $calc = $min;
                                    $note .= ' (حد أدنى)';
                                }
                                if ($max !== null && $calc > $max) {
                                    $calc = $max;
                                    $note .= ' (حد أعلى)';
                                }
                                $stored = (float) $b->platform_commission;
                                $changed = abs($calc - $stored) > 0.01;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-3.5 font-mono font-bold text-slate-800 whitespace-nowrap">
                                    BK-{{ $b->id }}</td>
                                <td class="px-4 py-3.5 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                    {{ $b->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3.5 font-semibold text-slate-800 whitespace-nowrap">
                                    {{ $ag->name ?? 'غير محدد' }}</td>
                                <td class="px-4 py-3.5 text-slate-700">{{ $b->trip->title ?? 'رحلة محذوفة' }}</td>
                                <td class="px-4 py-3.5 whitespace-nowrap">{{ $b->customer_name ?? '—' }}</td>
                                <td class="px-4 py-3.5 font-mono whitespace-nowrap">{{ $n }}</td>
                                <td class="px-4 py-3.5 font-mono whitespace-nowrap">
                                    {{ number_format((float) $b->total_amount, 2) }}</td>
                                <td
                                    class="px-4 py-3.5 font-mono font-bold text-amber-700 bg-amber-50/30 whitespace-nowrap">
                                    +{{ number_format($stored, 2) }} <span
                                        class="font-normal text-[10px] text-slate-400">ر.س</span>
                                </td>
                                <td class="px-4 py-3.5 text-[11px] whitespace-nowrap">
                                    <div class="text-slate-500">{{ $note }} = {{ number_format($calc, 2) }}</div>
                                    @if ($changed)
                                        <div class="text-[10px] text-rose-500">تختلف عن النسبة الحالية (تغيّرت لاحقاً)
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                        {{ $b->payment_status === 'paidof' ? 'مدفوع (نقداً/جزئي)' : 'مدفوع' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if ($ag)
                                        <button type="button"
                                            @click="openLedger({{ $ag->id }}, @js($ag->name))"
                                            class="text-[10px] text-emerald-600 hover:underline font-medium">كشف
                                            الوكالة</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-12 text-center text-slate-400 text-xs">لا توجد عمولات
                                    مطابقة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($commissionBookings->count())
                        <tfoot class="bg-slate-50 border-t border-slate-200">
                            <tr>
                                <td colspan="7" class="px-4 py-3 font-bold text-slate-700 text-left">مجموع الصفحة
                                    الحالية</td>
                                <td class="px-4 py-3 font-mono font-bold text-amber-700">
                                    +{{ number_format($commissionBookings->sum('platform_commission'), 2) }}
                                </td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                {{ $commissionBookings->links() }}
            </div>
        </div>

        {{-- Modal كشف حساب الوكالة (تفصيلي: رصيد + رحلات + حجوزات + حركات) --}}
        <div x-show="ledger.open" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true"
            @keydown.escape.window="ledger.open = false">
            <div class="flex items-start justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="ledger.open = false"></div>

                <div
                    class="relative z-10 w-full max-w-6xl bg-white rounded-2xl shadow-xl border border-slate-100 text-right">
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-800">
                            كشف حساب: <span x-text="ledger.agencyName"></span>
                            <span x-show="ledger.payoutId" class="text-slate-400 font-normal text-xs"
                                x-text="' — طلب السحب #' + ledger.payoutId"></span>
                        </h3>
                        <button type="button" @click="ledger.open = false"
                            class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="p-5 space-y-4">
                        <div x-show="ledger.loading" class="py-10 text-center text-xs text-slate-400">جاري التحميل...
                        </div>
                        <div x-show="ledger.error" class="py-6 text-center text-xs text-rose-600" x-text="ledger.error">
                        </div>

                        <template x-if="ledger.data">
                            <div class="space-y-4">

                                {{-- مطابقة طلب السحب مع الرصيد المتاح --}}
                                <template x-if="ledger.payoutAmount !== null">
                                    <div class="p-3 rounded-xl border text-xs flex flex-wrap items-center justify-between gap-2"
                                        :class="ledger.payoutAmount > (ledger.data.balance.available + ledger.payoutAmount) +
                                            0.01 ?
                                            'bg-rose-50 border-rose-200 text-rose-800' :
                                            'bg-emerald-50 border-emerald-200 text-emerald-800'">
                                        <div>
                                            <div class="font-bold">
                                                المبلغ المطلوب سحبه: <span class="font-mono"
                                                    x-text="money(ledger.payoutAmount)"></span> ر.س
                                            </div>
                                            <div class="text-[11px] mt-0.5">
                                                الرصيد المتاح قبل هذا الطلب:
                                                <b class="font-mono"
                                                    x-text="money(ledger.data.balance.available + ledger.payoutAmount)"></b>
                                                ر.س
                                            </div>
                                        </div>
                                        <div class="font-bold text-[11px]"
                                            x-text="ledger.payoutAmount > (ledger.data.balance.available + ledger.payoutAmount) + 0.01
                                                ? '⚠️ الطلب أكبر من الرصيد المتاح حسب الحجوزات'
                                                : '✅ الطلب ضمن الرصيد المتاح'">
                                        </div>
                                    </div>
                                </template>

                                {{-- الباقة والعمولة --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="p-3 rounded-xl border text-xs"
                                        :class="ledger.data.plan ? 'bg-violet-50 border-violet-100 text-violet-800' :
                                            'bg-slate-50 border-slate-200 text-slate-500'">
                                        <template x-if="ledger.data.plan">
                                            <div>
                                                <div class="font-bold">📦 مشتركة في باقة: <span
                                                        x-text="ledger.data.plan.name"></span>
                                                    (<span x-text="ledger.data.plan.cycle"></span>)</div>
                                                <div class="mt-1 text-[11px]">
                                                    عمولة الباقة: <b x-text="money(ledger.data.plan.per_pass)"></b> ر.س /
                                                    مسافر
                                                    <span x-show="ledger.data.plan.min !== null"> • أدنى <b
                                                            x-text="money(ledger.data.plan.min)"></b></span>
                                                    <span x-show="ledger.data.plan.max !== null"> • أعلى <b
                                                            x-text="money(ledger.data.plan.max)"></b></span>
                                                    • تنتهي <span x-text="ledger.data.plan.ends_at"></span>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!ledger.data.plan">
                                            <div>لا يوجد اشتراك نشط (عمولة يدوية).</div>
                                        </template>
                                    </div>
                                    <div
                                        class="p-3 rounded-xl border border-indigo-100 bg-indigo-50/50 text-xs text-indigo-900">
                                        <div class="font-bold">العمولة المطبقة حالياً على الوكالة</div>
                                        <div class="mt-1 text-[11px]">
                                            <b x-text="money(ledger.data.rates.per_passenger)"></b> ر.س / مسافر
                                            <span x-show="ledger.data.rates.min !== null"> • أدنى <b
                                                    x-text="money(ledger.data.rates.min)"></b></span>
                                            <span x-show="ledger.data.rates.max !== null"> • أعلى <b
                                                    x-text="money(ledger.data.rates.max)"></b></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- ملخص الأرباح: من الإجمالي إلى الصافي --}}
                                <div class="grid grid-cols-2 md:grid-cols-6 gap-3 text-center">
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                        <div class="text-[10px] text-slate-400">الحجوزات / المسافرون</div>
                                        <div class="font-bold font-mono text-sm"
                                            x-text="ledger.data.totals.bookings_count + ' / ' + ledger.data.totals.travelers">
                                        </div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                        <div class="text-[10px] text-slate-400">إجمالي المبيعات</div>
                                        <div class="font-bold font-mono text-sm" x-text="money(ledger.data.totals.gross)">
                                        </div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-amber-50 border border-amber-100">
                                        <div class="text-[10px] text-amber-600">− عمولة المنصة</div>
                                        <div class="font-bold font-mono text-sm text-amber-700"
                                            x-text="money(ledger.data.totals.commission)"></div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                        <div class="text-[10px] text-slate-400">− مدفوع نقداً للوكالة</div>
                                        <div class="font-bold font-mono text-sm" x-text="money(ledger.data.totals.cash)">
                                        </div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                        <div class="text-[10px] text-slate-400">ضريبة القيمة المضافة</div>
                                        <div class="font-bold font-mono text-sm" x-text="money(ledger.data.totals.vat)">
                                        </div>
                                    </div>
                                    <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-100">
                                        <div class="text-[10px] text-emerald-600">= صافي أرباح الوكالة</div>
                                        <div class="font-bold font-mono text-sm text-emerald-700"
                                            x-text="money(ledger.data.totals.net)"></div>
                                    </div>
                                </div>

                                {{-- رصيد الوكالة --}}
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center">
                                    <div class="p-3 rounded-xl border border-emerald-100 bg-white">
                                        <div class="text-[10px] text-slate-400">إجمالي الأرباح المستحقة</div>
                                        <div class="font-bold font-mono text-sm text-emerald-700"
                                            x-text="money(ledger.data.balance.earned)"></div>
                                    </div>
                                    <div class="p-3 rounded-xl border border-slate-100 bg-white">
                                        <div class="text-[10px] text-slate-400">− تم تحويله سابقاً</div>
                                        <div class="font-bold font-mono text-sm"
                                            x-text="money(ledger.data.balance.paid_out)"></div>
                                    </div>
                                    <div class="p-3 rounded-xl border border-amber-100 bg-white">
                                        <div class="text-[10px] text-slate-400">− طلبات سحب معلقة</div>
                                        <div class="font-bold font-mono text-sm text-amber-700"
                                            x-text="money(ledger.data.balance.pending)"></div>
                                    </div>
                                    <div class="p-3 rounded-xl border border-indigo-100 bg-indigo-50/50">
                                        <div class="text-[10px] text-indigo-600">= الرصيد المتاح للسحب</div>
                                        <div class="font-bold font-mono text-sm text-indigo-700"
                                            x-text="money(ledger.data.balance.available)"></div>
                                    </div>
                                </div>

                                {{-- التبويبات --}}
                                <div class="inline-flex p-1 bg-slate-100 rounded-xl text-xs font-semibold">
                                    <button type="button" @click="ledger.tab = 'trips'"
                                        :class="ledger.tab === 'trips' ? 'bg-white text-indigo-600 shadow-sm' :
                                            'text-slate-500'"
                                        class="px-3 py-1 rounded-lg">حسب الرحلة</button>
                                    <button type="button" @click="ledger.tab = 'bookings'"
                                        :class="ledger.tab === 'bookings' ? 'bg-white text-indigo-600 shadow-sm' :
                                            'text-slate-500'"
                                        class="px-3 py-1 rounded-lg">تفاصيل الحجوزات</button>
                                    <button type="button" @click="ledger.tab = 'transactions'"
                                        :class="ledger.tab === 'transactions' ? 'bg-white text-indigo-600 shadow-sm' :
                                            'text-slate-500'"
                                        class="px-3 py-1 rounded-lg">الحركات المالية</button>
                                </div>

                                {{-- حسب الرحلة --}}
                                <div x-show="ledger.tab === 'trips'"
                                    class="overflow-x-auto border border-slate-100 rounded-xl">
                                    <table class="w-full text-right text-[11px] text-slate-600">
                                        <thead class="bg-slate-50 text-slate-400 font-semibold">
                                            <tr>
                                                <th class="px-3 py-2">الرحلة</th>
                                                <th class="px-3 py-2">الحجوزات</th>
                                                <th class="px-3 py-2">المسافرون</th>
                                                <th class="px-3 py-2">الإجمالي</th>
                                                <th class="px-3 py-2">عمولة المنصة</th>
                                                <th class="px-3 py-2">نقداً</th>
                                                <th class="px-3 py-2">الصافي للوكالة</th>
                                                <th class="px-3 py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <template x-for="t in ledger.data.trips" :key="t.trip_id">
                                                <tr>
                                                    <td class="px-3 py-2.5 font-semibold text-slate-700" x-text="t.trip">
                                                    </td>
                                                    <td class="px-3 py-2.5 font-mono" x-text="t.bookings"></td>
                                                    <td class="px-3 py-2.5 font-mono" x-text="t.travelers"></td>
                                                    <td class="px-3 py-2.5 font-mono" x-text="money(t.gross)"></td>
                                                    <td class="px-3 py-2.5 font-mono font-bold text-amber-700"
                                                        x-text="money(t.commission)"></td>
                                                    <td class="px-3 py-2.5 font-mono" x-text="money(t.cash)"></td>
                                                    <td class="px-3 py-2.5 font-mono font-bold text-emerald-700"
                                                        x-text="money(t.net)"></td>
                                                    <td class="px-3 py-2.5">
                                                        <button type="button" @click="showTripBookings(t.trip_id)"
                                                            class="text-indigo-600 hover:underline font-medium whitespace-nowrap">عرض
                                                            حجوزاتها</button>
                                                    </td>
                                                </tr>
                                            </template>
                                            <tr x-show="ledger.data.trips.length === 0">
                                                <td colspan="8" class="px-3 py-8 text-center text-slate-400">لا توجد
                                                    رحلات بحجوزات مدفوعة بعد.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                {{-- الحجوزات --}}
                                <div x-show="ledger.tab === 'bookings'" class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input type="text" x-model="ledger.q"
                                            placeholder="بحث برقم الحجز أو الرحلة أو العميل أو اسم المسافر..."
                                            class="w-full sm:w-80 px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs outline-none focus:border-indigo-500">
                                        <template x-if="ledger.tripFilter !== null">
                                            <button type="button" @click="ledger.tripFilter = null"
                                                class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100 text-[11px] font-semibold">
                                                الرحلة: <span x-text="ledgerFilterTripName"></span> ✕
                                            </button>
                                        </template>
                                        <span class="text-[11px] text-slate-400"
                                            x-text="'عرض ' + ledgerBookings.length + ' من ' + ledger.data.bookings_total + ' حجز'"></span>
                                    </div>

                                    <div class="overflow-x-auto border border-slate-100 rounded-xl">
                                        <table class="w-full text-right text-[11px] text-slate-600">
                                            <thead class="bg-slate-50 text-slate-400 font-semibold">
                                                <tr>
                                                    <th class="px-3 py-2">الحجز</th>
                                                    <th class="px-3 py-2">الرحلة</th>
                                                    <th class="px-3 py-2">المسافرون</th>
                                                    <th class="px-3 py-2">الإجمالي</th>
                                                    <th class="px-3 py-2">عمولة المنصة (كيف حُسبت)</th>
                                                    <th class="px-3 py-2">نقداً</th>
                                                    <th class="px-3 py-2">الصافي للوكالة</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                <template x-for="b in ledgerBookings" :key="b.id">
                                                    <tr class="align-top">
                                                        <td class="px-3 py-2.5">
                                                            <div class="font-bold text-slate-800" x-text="'BK-' + b.id">
                                                            </div>
                                                            <div class="text-slate-400" x-text="b.customer"></div>
                                                            <div class="text-slate-400 font-mono" x-text="b.date"></div>
                                                        </td>
                                                        <td class="px-3 py-2.5 font-semibold text-slate-700"
                                                            x-text="b.trip"></td>
                                                        <td class="px-3 py-2.5">
                                                            <div class="font-semibold text-slate-500"
                                                                x-text="b.seats_count + ' مسافر'"></div>
                                                            <template x-for="n in b.travelers">
                                                                <div x-text="'• ' + n"></div>
                                                            </template>
                                                        </td>
                                                        <td class="px-3 py-2.5">
                                                            <div class="font-mono" x-text="money(b.gross)"></div>
                                                            <div x-show="b.vat > 0" class="text-[10px] text-slate-400"
                                                                x-text="'ضريبة: ' + money(b.vat)"></div>
                                                        </td>
                                                        <td class="px-3 py-2.5">
                                                            <div class="font-mono font-bold text-amber-700"
                                                                x-text="money(b.commission)"></div>
                                                            <div class="text-[10px] text-slate-400"
                                                                x-text="b.commission_note"></div>
                                                            <div x-show="b.rates_changed"
                                                                class="text-[10px] text-rose-500">
                                                                تختلف عن النسبة الحالية (تغيّرت لاحقاً)</div>
                                                        </td>
                                                        <td class="px-3 py-2.5 font-mono" x-text="money(b.cash)"></td>
                                                        <td class="px-3 py-2.5">
                                                            <div class="font-mono font-bold text-emerald-700"
                                                                x-text="money(b.net)"></div>
                                                            <div class="text-[10px] text-slate-400"
                                                                x-text="money(b.gross) + ' − ' + money(b.commission) + ' − ' + money(b.cash) + ' = ' + money(b.net_check)">
                                                            </div>
                                                            <div x-show="b.net_mismatch"
                                                                class="text-[10px] text-rose-500">
                                                                الصافي المخزّن يختلف عن المعادلة</div>
                                                        </td>
                                                    </tr>
                                                </template>
                                                <tr x-show="ledgerBookings.length === 0">
                                                    <td colspan="7" class="px-3 py-8 text-center text-slate-400">لا
                                                        توجد
                                                        حجوزات مطابقة.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p x-show="ledger.data.bookings_total > ledger.data.bookings.length"
                                        class="text-[10px] text-slate-400">يتم عرض آخر
                                        <span x-text="ledger.data.bookings.length"></span> حجز فقط، لكن الإجماليات أعلاه
                                        تشمل كل الحجوزات.
                                    </p>
                                </div>

                                {{-- الحركات --}}
                                <div x-show="ledger.tab === 'transactions'"
                                    class="overflow-x-auto border border-slate-100 rounded-xl">
                                    <table class="w-full text-right text-[11px] text-slate-600">
                                        <thead class="bg-slate-50 text-slate-400 font-semibold">
                                            <tr>
                                                <th class="px-3 py-2">التاريخ</th>
                                                <th class="px-3 py-2">النوع</th>
                                                <th class="px-3 py-2">المبلغ</th>
                                                <th class="px-3 py-2">الوصف</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <template x-for="t in ledger.data.transactions" :key="t.id">
                                                <tr>
                                                    <td class="px-3 py-2 font-mono text-slate-400" x-text="t.date"></td>
                                                    <td class="px-3 py-2 font-semibold" x-text="txLabel(t.type)"></td>
                                                    <td class="px-3 py-2 font-mono font-bold"
                                                        :class="t.amount < 0 ? 'text-rose-600' : 'text-emerald-600'"
                                                        x-text="money(t.amount)"></td>
                                                    <td class="px-3 py-2" x-text="t.description"></td>
                                                </tr>
                                            </template>
                                            <tr x-show="ledger.data.transactions.length === 0">
                                                <td colspan="4" class="px-3 py-8 text-center text-slate-400">لا توجد
                                                    حركات.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- 5. Modal تعديل عمولة الوكالة الفردية --}}
        <div x-show="editingAgency !== null" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                    @click="editingAgency = null"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div
                    class="inline-block w-full max-w-md p-6 my-8 text-right align-middle transition-all transform bg-white shadow-xl rounded-2xl relative z-10 border border-slate-100">
                    <form
                        :action="'{{ url('admin/agencies') }}/' + (editingAgency ? editingAgency.id : '') + '/update-commission'"
                        method="POST">
                        @csrf
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-sm font-bold text-slate-800">تعديل عمولة المنصة للوكالة</h3>
                            <button type="button" @click="editingAgency = null"
                                class="text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <template x-if="editingAgency">
                            <div class="py-4 space-y-3">
                                <div
                                    class="p-3 bg-indigo-50/60 rounded-xl border border-indigo-100 text-xs text-indigo-900">
                                    اسم الوكالة: <strong x-text="editingAgency.name"></strong>
                                </div>
                                <div x-show="editingAgency.plan"
                                    class="p-2.5 bg-violet-50 rounded-xl border border-violet-100 text-[11px] text-violet-800">
                                    هذه الوكالة مشتركة في باقة <strong x-text="editingAgency.plan"></strong>. أي تعديل يدوي
                                    هنا سيُستبدل عند تحديث الباقة.
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 text-xs mb-1.5">عمولة المسافر الواحد
                                        (ر.س)</label>
                                    <input type="number" step="0.01" min="0" name="commission_per_passenger"
                                        required x-model="editingAgency.per_passenger"
                                        class="w-full p-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold font-mono text-slate-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 text-xs mb-1.5">الحد الأدنى للحجز
                                            (ر.س)</label>
                                        <input type="number" step="0.01" min="0"
                                            name="min_booking_commission" x-model="editingAgency.min" placeholder="بدون"
                                            class="w-full p-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold font-mono text-slate-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 text-xs mb-1.5">الحد الأعلى للحجز
                                            (ر.س)</label>
                                        <input type="number" step="0.01" min="0"
                                            name="max_booking_commission" x-model="editingAgency.max" placeholder="بدون"
                                            class="w-full p-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold font-mono text-slate-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                                    </div>
                                </div>
                                <p class="text-[10px] text-slate-400">اترك الحد الأدنى أو الأعلى فارغاً إذا ما تبي حد.</p>

                                {{-- معاينة حية --}}
                                <div
                                    class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-[11px] text-slate-600 space-y-1">
                                    <div class="font-semibold text-slate-700 mb-1">معاينة العمولة:</div>
                                    <template x-for="n in [1, 3, 5, 10]" :key="n">
                                        <div class="flex justify-between">
                                            <span x-text="'حجز ' + n + (n === 1 ? ' مسافر' : ' مسافرين')"></span>
                                            <strong class="font-mono text-indigo-700"
                                                x-text="fmt(calcCommission(editingAgency, n)) + ' ر.س'"></strong>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="editingAgency = null"
                                class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-xs font-medium hover:bg-slate-50">إلغاء</button>
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm">حفظ
                                التغييرات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- 6. Modal التعديل للجميع --}}
        <div x-show="activeGlobalCommissionModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                    @click="activeGlobalCommissionModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div
                    class="inline-block w-full max-w-md p-6 my-8 text-right align-middle transition-all transform bg-white shadow-xl rounded-2xl relative z-10 border border-slate-100">
                    <form action="{{ route('admin.agencies.update-all-commissions') }}" method="POST">
                        @csrf
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-sm font-bold text-slate-800">تحديث عمولة كافة الوكالات</h3>
                            <button type="button" @click="activeGlobalCommissionModal = false"
                                class="text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="py-4 space-y-3">
                            <p class="text-xs text-amber-700 bg-amber-50 p-2.5 rounded-xl border border-amber-200">
                                تنبيه: هذا الإجراء سيستبدل عمولة (المسافر والحد الأدنى والحد الأعلى) لجميع الوكالات
                                المسجلة حالياً في النظام.
                            </p>
                            <div>
                                <label class="block font-semibold text-slate-700 text-xs mb-1.5">عمولة المسافر الواحد
                                    (ر.س)</label>
                                <input type="number" step="0.01" min="0" name="commission_per_passenger"
                                    required placeholder="مثال: 5"
                                    class="w-full p-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold font-mono text-slate-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 text-xs mb-1.5">الحد الأدنى للحجز
                                        (ر.س)</label>
                                    <input type="number" step="0.01" min="0" name="min_booking_commission"
                                        placeholder="بدون"
                                        class="w-full p-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold font-mono text-slate-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 text-xs mb-1.5">الحد الأعلى للحجز
                                        (ر.س)</label>
                                    <input type="number" step="0.01" min="0" name="max_booking_commission"
                                        placeholder="مثال: 30"
                                        class="w-full p-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold font-mono text-slate-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-400">اترك الحد الأدنى أو الأعلى فارغاً إذا ما تبي حد.</p>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="activeGlobalCommissionModal = false"
                                class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-xs font-medium hover:bg-slate-50">إلغاء</button>
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm">تطبيق
                                على الجميع</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- 7. Modal الاعتماد والتحويل --}}
        <div x-show="activeApproveModal !== null" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                    @click="activeApproveModal = null"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div
                    class="inline-block w-full max-w-md p-6 my-8 text-right align-middle transition-all transform bg-white shadow-xl rounded-2xl relative z-10 border border-slate-100">
                    <form :action="'{{ url('admin/payouts') }}/' + activeApproveModal + '/approve'" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-sm font-bold text-slate-800">اعتماد طلب السحب وتحويل المبلغ</h3>
                            <button type="button" @click="activeApproveModal = null"
                                class="text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="py-4 space-y-3">
                            <p class="text-xs text-slate-600">هل تأكد اعتماد الطلب رقم <strong
                                    class="font-mono text-slate-900" x-text="'#' + activeApproveModal"></strong>؟</p>

                            <div>
                                <label class="block font-semibold text-slate-700 text-xs mb-1.5">إرفاق إيصال التحويل
                                    البنكي (مطلوب)</label>
                                <input type="file" name="bank_receipt" accept="image/*,application/pdf" required
                                    class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="activeApproveModal = null"
                                class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-xs font-medium hover:bg-slate-50">إلغاء</button>
                            <button type="submit"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm">تأكيد
                                واعتماد</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- 8. Modal الرفض --}}
        <div x-show="activeRejectModal !== null" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                    @click="activeRejectModal = null"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div
                    class="inline-block w-full max-w-md p-6 my-8 text-right align-middle transition-all transform bg-white shadow-xl rounded-2xl relative z-10 border border-slate-100">
                    <form :action="'{{ url('admin/payouts') }}/' + activeRejectModal + '/reject'" method="POST">
                        @csrf
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-sm font-bold text-slate-800">رفض طلب السحب</h3>
                            <button type="button" @click="activeRejectModal = null"
                                class="text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="py-4 space-y-3">
                            <p class="text-xs text-slate-600">هل تأكد رفض الطلب رقم <strong
                                    class="font-mono text-slate-900" x-text="'#' + activeRejectModal"></strong>؟</p>

                            <div>
                                <label class="block font-semibold text-slate-700 text-xs mb-1.5">سبب الرفض
                                    (مطلوب)</label>
                                <textarea name="admin_notes" rows="3" placeholder="اكتب سبب الرفض هنا..." required
                                    class="w-full p-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 outline-none"></textarea>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="activeRejectModal = null"
                                class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-xs font-medium hover:bg-slate-50">إلغاء</button>
                            <button type="submit"
                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold shadow-sm">تأكيد
                                الرفض</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection
