@extends('layouts.admin')

@section('content')
    <div class="container mx-auto px-4 py-6 animate-fade-in" x-data="{ view: 'all', search: '' }">

        {{-- رأس الصفحة --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    إدارة شركاء النجاح
                    <span class="text-xs bg-indigo-600 text-white px-2.5 py-0.5 rounded-full font-bold">الوكالات</span>
                </h1>
                <p class="text-xs text-slate-500 mt-1">مراقبة أداء الوكالات، اعتماد التحديثات، وإدارة التراخيص بمرونة.</p>
            </div>

            {{-- أزرار الفلترة السريعة عبر AlpineJS --}}
            <div class="flex flex-wrap items-center gap-1.5 bg-white p-1.5 rounded-2xl shadow-sm border border-slate-100">
                <button @click="view = 'all'"
                    :class="view === 'all' ? 'bg-slate-900 text-white shadow' : 'text-slate-500 hover:text-slate-900'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all">
                    الكل
                </button>

                <button @click="view = 'cr_pending'"
                    :class="view === 'cr_pending' ? 'bg-amber-500 text-white shadow' : 'text-slate-500 hover:text-slate-900'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5">
                    <span>طلبات السجل المعلقة</span>
                    @php
                        $crPendingCount = $agencies->where('cr_status', 'pending')->count();
                    @endphp
                    @if($crPendingCount > 0)
                        <span class="bg-white text-amber-600 px-1.5 py-0.2 rounded-full text-[10px] font-black animate-bounce">
                            {{ $crPendingCount }}
                        </span>
                    @endif
                </button>

                <button @click="view = 'pending'"
                    :class="view === 'pending' ? 'bg-orange-500 text-white shadow' : 'text-slate-500 hover:text-slate-900'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5">
                    طلبات الانضمام
                    <span class="w-1.5 h-1.5 bg-white rounded-full" :class="view === 'pending' ? 'animate-pulse' : ''"></span>
                </button>
            </div>
        </div>

        {{-- تنبيهات النظام --}}
        @if (session('success'))
            <div class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-bold flex items-center gap-2 shadow-sm">
                <span>✨</span> {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-bold flex items-center gap-2 shadow-sm">
                <span>⚠️</span> {{ session('error') }}
            </div>
        @endif

        {{-- كروت الإحصائيات --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
                <div class="w-10 h-10 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-lg shrink-0">✅</div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">وكالات موثقة ونشطة</p>
                    <h4 class="text-xl font-black text-slate-800">{{ $activeCount }}</h4>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
                <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-lg shrink-0">📜</div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">تحديثات سجل بانتظار الاعتماد</p>
                    <h4 class="text-xl font-black text-slate-800">{{ $agencies->where('cr_status', 'pending')->count() }}</h4>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
                <div class="w-10 h-10 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center text-lg shrink-0">⏳</div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">بانتظار انضمام الحساب</p>
                    <h4 class="text-xl font-black text-slate-800">{{ $pendingCount }}</h4>
                </div>
            </div>

            <div class="bg-indigo-600 p-4 rounded-2xl shadow-lg shadow-indigo-100 flex items-center gap-4 text-white">
                <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center text-lg shrink-0">📈</div>
                <div class="overflow-hidden">
                    <p class="text-[10px] font-bold text-indigo-200 uppercase tracking-wider">الأكثر نشاطاً بالرحلات</p>
                    <h4 class="text-sm font-black truncate">{{ $topAgencyName }}</h4>
                </div>
            </div>
        </div>

        {{-- شريط البحث --}}
        <div class="bg-white p-2.5 rounded-2xl shadow-sm border border-slate-100 flex flex-col md:flex-row gap-3 mb-6">
            <div class="relative flex-1">
                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔍</span>
                <input type="text" x-model="search" placeholder="ابحث باسم الوكالة، صاحب الوكالة، البريد، أو رقم السجل التجاري..."
                    class="w-full bg-slate-50 border-none rounded-xl pr-10 pl-4 py-2.5 text-xs font-bold focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
            </div>
        </div>

        {{-- عرض بطاقات الوكالات (Grid Layout) بدلاً من الجدول التقليدي --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @forelse($agencies as $agency)
                <div x-show="(view === 'all' || (view === 'cr_pending' && '{{ $agency->cr_status }}' === 'pending') || (view === '{{ $agency->status }}')) && 
                             ('{{ strtolower($agency->name) }}'.includes(search.toLowerCase()) || 
                              '{{ strtolower($agency->email ?? '') }}'.includes(search.toLowerCase()) || 
                              '{{ strtolower($agency->commercial_name ?? '') }}'.includes(search.toLowerCase()) || 
                              '{{ $agency->commercial_id }}'.includes(search) ||
                              '{{ $agency->pending_commercial_id }}'.includes(search))"
                    class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden">
                    
                    {{-- شريط الحالة العلوية للبطاقة --}}
                    <div>
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-4 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-slate-100 text-slate-700 rounded-2xl flex items-center justify-center text-xl shrink-0 font-black">
                                    🏢
                                </div>
                                <div>
                                    <h3 class="font-black text-slate-900 text-base leading-snug">{{ $agency->name }}</h3>
                                    <p class="text-xs text-slate-400 font-bold mt-0.5 flex items-center gap-1">
                                        <span>👤</span> {{ $agency->commercial_name ?? 'صاحب الوكالة غير محدد' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-1.5">
                                {{-- حالة الوكالة العامة --}}
                                @if ($agency->status == 'active')
                                    <span class="inline-flex items-center gap-1 text-emerald-700 text-[10px] bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200/60 font-black">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> نشطة
                                    </span>
                                @elseif($agency->status == 'pending')
                                    <span class="inline-flex items-center gap-1 text-orange-700 text-[10px] bg-orange-50 px-2.5 py-1 rounded-full border border-orange-200/60 font-black">
                                        <span class="w-1.5 h-1.5 bg-orange-500 rounded-full animate-pulse"></span> قيد الانضمام
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-rose-700 text-[10px] bg-rose-50 px-2.5 py-1 rounded-full border border-rose-200/60 font-black">
                                        🚫 محظورة
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- 🔔 تنبيه خاص: وجود طلب تحديث سجل تجاري قيد الانتظار --}}
                        @if ($agency->cr_status === 'pending')
                            <div class="mb-5 p-4 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/80 rounded-2xl space-y-3 shadow-inner">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-black text-amber-800 flex items-center gap-1.5">
                                        <span class="animate-bounce">📑</span> طلب تحديث السجل التجاري (جديد)
                                    </span>
                                    <span class="text-[10px] bg-amber-200/60 text-amber-900 font-bold px-2 py-0.5 rounded-md">
                                        بانتظار الاعتماد
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-xs bg-white/70 p-3 rounded-xl border border-amber-100">
                                    <div>
                                        <span class="block text-[10px] text-slate-400 font-bold">رقم السجل الجديد:</span>
                                        <span class="font-mono font-black text-slate-800">{{ $agency->pending_commercial_id }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] text-slate-400 font-bold">تاريخ الانتهاء الجديد:</span>
                                        <span class="font-mono font-black text-slate-800">{{ $agency->pending_cr_expiry_date }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-2 pt-1">
                                    @if ($agency->pending_cr_document_path)
                                        <a href="{{ Storage::url($agency->pending_cr_document_path) }}" target="_blank"
                                            class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-3 py-1.5 rounded-xl border border-indigo-100 transition-colors">
                                            <span>📄 معاينة وثيقة السجل</span>
                                            <span class="text-[10px]">↗</span>
                                        </a>
                                    @else
                                        <span class="text-[10px] text-slate-400 italic">لم يتم إرفاق ملف جديد</span>
                                    @endif

                                    <div class="flex items-center gap-2">
                                        <form action="{{ route('admin.agencies.approve-cr', $agency->id) }}" method="POST"
                                            onsubmit="return confirm('هل أنت متأكد من اعتماد بيانات السجل التجاري الجديدة؟')">
                                            @csrf
                                            <button type="submit"
                                                class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold shadow-sm transition-all flex items-center gap-1">
                                                <span>اعتماد السجل</span>
                                                <span>✓</span>
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.agencies.reject-cr', $agency->id) }}" method="POST"
                                            onsubmit="return confirm('هل تريد رفض طلب التحديث؟')">
                                            @csrf
                                            <button type="submit"
                                                class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs font-bold border border-rose-200/60 transition-all">
                                                رفض
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- تفاصيل السجل التجاري الحالي --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4 bg-slate-50 p-3.5 rounded-2xl text-xs border border-slate-100">
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400">السجل التجاري الحالي</span>
                                <span class="font-mono font-bold text-slate-700">{{ $agency->commercial_id ?? 'غير مسجل' }}</span>
                            </div>

                            <div>
                                <span class="block text-[10px] font-bold text-slate-400">تاريخ انتهاء السجل</span>
                                @php
                                    $expiryRaw = $agency->cr_expiry_date ?? $agency->commercial_expiry ?? $agency->commercial_end_date;
                                @endphp
                                @if ($expiryRaw)
                                    @php
                                        $expiryDate = \Carbon\Carbon::parse($expiryRaw)->startOfDay();
                                        $diffDays = (int) \Carbon\Carbon::now()->startOfDay()->diffInDays($expiryDate, false);
                                    @endphp
                                    <span class="font-mono font-bold {{ $diffDays < 0 ? 'text-red-600' : ($diffDays <= 30 ? 'text-amber-600' : 'text-emerald-600') }}">
                                        {{ $expiryDate->format('Y-m-d') }}
                                    </span>
                                @else
                                    <span class="text-slate-400 font-bold">غير محدد</span>
                                @endif
                            </div>

                            <div class="col-span-2 sm:col-span-1">
                                <span class="block text-[10px] font-bold text-slate-400">الرحلات والرصيد</span>
                                <span class="font-bold text-slate-800">
                                    {{ $agency->trips_count }} رحلة | {{ number_format($agency->trips_sum_price ?? 0, 2) }} ر.س
                                </span>
                            </div>
                        </div>

                        {{-- البريد والايبان البنكي --}}
                        <div class="space-y-1.5 mb-5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 font-bold">البريد:</span>
                                <span class="font-mono text-slate-700" dir="ltr">{{ $agency->email ?? 'غير محدد' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 font-bold">IBAN:</span>
                                <span class="font-mono text-slate-700 select-all" dir="ltr">{{ $agency->ibanA ? strtoupper($agency->ibanA) : 'لا يوجد' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- إجراءات التحكم السريعة للوكالة --}}
                    <div class="flex items-center justify-between pt-4 border-t border-slate-100 gap-2">
                        <div class="flex items-center gap-2">
                            @if ($agency->status === 'pending')
                                <form action="{{ route('admin.agencies.approve', $agency->id) }}" method="POST"
                                    onsubmit="return confirm('هل أنت متأكد من اعتماد الوكالة للانضمام؟')">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all">
                                        اعتماد الحساب
                                    </button>
                                </form>
                            @endif

                            @if ($agency->cr_document_path && $agency->cr_status !== 'pending')
                                <a href="{{ Storage::url($agency->cr_document_path) }}" target="_blank"
                                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all inline-flex items-center gap-1">
                                    <span>📄 الوثيقة المعتمدة</span>
                                </a>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <form action="{{ route('admin.agencies.block', $agency->id) }}" method="POST"
                                onsubmit="return confirm('هل تريد تغيير حالة حظر الوكالة؟')">
                                @csrf
                                <button type="submit"
                                    class="px-3 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1"
                                    :class="'{{ $agency->status }}' === 'blocked' ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 hover:bg-red-50 hover:text-red-600'"
                                    title="{{ $agency->status === 'blocked' ? 'إلغاء الحظر' : 'حظر الحساب' }}">
                                    <span>{{ $agency->status === 'blocked' ? '🔓 إلغاء الحظر' : '🔒 حظر' }}</span>
                                </button>
                            </form>

                            <form action="{{ route('admin.agencies.destroy', $agency->id) }}" method="POST"
                                onsubmit="return confirm('⚠️ هل أنت متأكد من حذف هذه الوكالة نهائياً؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white rounded-xl text-xs transition-all" title="حذف نهائي">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            @empty
                <div class="col-span-full bg-white rounded-3xl p-12 text-center text-slate-400 border border-slate-100">
                    <p class="text-3xl mb-2">📭</p>
                    <p class="text-xs font-bold">لا توجد أي وكالات مسجلة في النظام حالياً.</p>
                </div>
            @endforelse
        </div>

        {{-- الترقيم السفلي --}}
        <div class="mt-6 p-4 bg-white rounded-2xl shadow-sm border border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs">
            @if ($agencies instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <p class="font-medium text-slate-400">
                    عرض {{ $agencies->firstItem() ?? 0 }}-{{ $agencies->lastItem() ?? 0 }} من أصل {{ $agencies->total() }} وكالة
                </p>
                <div>
                    {{ $agencies->links() }}
                </div>
            @else
                <p class="font-medium text-slate-400">
                    إجمالي الوكالات: {{ $agencies->count() }}
                </p>
            @endif
        </div>

    </div>

    <style>
        .animate-fade-in {
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
@endsection