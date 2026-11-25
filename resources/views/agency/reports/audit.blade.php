@extends('layouts.agency')

@section('content')
<div dir="rtl" class="min-h-screen bg-slate-50/50 p-4 sm:p-6 lg:p-8 font-sans">
    
    {{-- الهيدر الرئيسي للصفحة --}}
    <div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800">سجل التدقيق العملياتي والمالي</h1>
                <p class="text-xs text-slate-500 mt-1">سجل حي يوثق كافة التعديلات الماليّة، تغيرات المقاعد، وإنشاء أو حذف الرحلات فور حدوثها.</p>
            </div>
        </div>

        {{-- مؤشر تحديث البيانات --}}
        <div class="flex items-center gap-2 self-start lg:self-center bg-slate-100/80 px-3 py-1.5 rounded-xl border border-slate-200/60 text-xs text-slate-600">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>التحديث الفوري مفعّل</span>
        </div>
    </div>

    {{-- شبكة الإحصائيات السريعة (4 كروت) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        
        {{-- إجمالي التغير المالي --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 mb-1">صافي التدفق المالي</p>
                <h3 class="text-2xl font-black font-mono {{ $totalMoneyFlow < 0 ? 'text-rose-600' : ($totalMoneyFlow > 0 ? 'text-emerald-600' : 'text-slate-800') }}">
                    {{ $totalMoneyFlow > 0 ? '+' : '' }}{{ number_format($totalMoneyFlow, 2) }}
                    <span class="text-xs font-sans font-normal text-slate-500">ر.س</span>
                </h3>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        {{-- صافي تغير المقاعد --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 mb-1">صافي تغير المقاعد</p>
                <h3 class="text-2xl font-black font-mono {{ $totalSeatsFlow < 0 ? 'text-rose-600' : ($totalSeatsFlow > 0 ? 'text-sky-600' : 'text-slate-800') }}">
                    {{ $totalSeatsFlow > 0 ? '+'.$totalSeatsFlow : $totalSeatsFlow }}
                    <span class="text-xs font-sans font-normal text-slate-500">مقعد</span>
                </h3>
            </div>
            <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
            </div>
        </div>

        {{-- إجمالي السجلات --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 mb-1">إجمالي الحركات المسجلة</p>
                <h3 class="text-2xl font-black text-slate-800 font-mono">{{ method_exists($logs, 'total') ? $logs->total() : $logs->count() }}</h3>
            </div>
            <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
        </div>

        {{-- عدد النتائج في الصفحة الحالية --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 mb-1">السجلات المعروضة</p>
                <h3 class="text-2xl font-black text-slate-800 font-mono">{{ $logs->count() }}</h3>
            </div>
            <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.447.894l-4 2A1 1 0 017 21v-7.586L2.293 6.707A1 1 0 012 6V4z"/></svg>
            </div>
        </div>

    </div>

    {{-- شريط الفلاتر والبحث --}}
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80 mb-6">
        <form method="GET" action="{{ route('agency.reports.audit') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
            
            {{-- حقل البحث النصي --}}
            <div class="lg:col-span-4">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">ابحث بالرحلة، رقم الحجز أو البيّان</label>
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="مثال: رحلة مكة، أحمد علي..." 
                        class="w-full pr-9 pl-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm outline-none transition bg-slate-50/30">
                </div>
            </div>

            {{-- فلتر التصنيف --}}
            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">تصنيف السجل</label>
                <select name="category" onchange="this.form.submit()" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm outline-none bg-white transition">
                    <option value="">جميع التصنيفات</option>
                    <option value="trips" {{ request('category') == 'trips' ? 'selected' : '' }}>🗺️ إدارة الرحلات</option>
                    <option value="money" {{ request('category') == 'money' ? 'selected' : '' }}>💰 الحركات المالية</option>
                    <option value="bookings" {{ request('category') == 'bookings' ? 'selected' : '' }}>💺 الحجوزات والمقاعد</option>
                </select>
            </div>

            {{-- فلتر نوع الحركة --}}
            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">نوع الحركة</label>
                <select name="action_type" onchange="this.form.submit()" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm outline-none bg-white transition">
                    <option value="">جميع الحركات</option>
                    <option value="create" {{ request('action_type') == 'create' ? 'selected' : '' }}>✨ إنشاء ونشر (+)</option>
                    <option value="increase" {{ request('action_type') == 'increase' ? 'selected' : '' }}>🟢 زيادة (+)</option>
                    <option value="decrease" {{ request('action_type') == 'decrease' ? 'selected' : '' }}>🟡 نقصان (-)</option>
                    <option value="update" {{ request('action_type') == 'update' ? 'selected' : '' }}>🔵 تعديل (🔄)</option>
                    <option value="delete" {{ request('action_type') == 'delete' ? 'selected' : '' }}>🔴 حذف وإلغاء (🗑️)</option>
                </select>
            </div>

            {{-- أزرار التحكم --}}
            <div class="lg:col-span-2 flex items-center gap-2">
                <button type="submit" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl shadow-sm transition-colors text-center">
                    تصفية
                </button>
                @if(request()->filled('category') || request()->filled('action_type') || request()->filled('search'))
                    <a href="{{ route('agency.reports.audit') }}" class="p-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl transition text-center" title="إلغاء الفلاتر">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- جدول السجلات الرئيسي --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">التاريخ والتوقيت</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">نوع الحركة</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">التصنيف</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">التغير المالي</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">تغير المقاعد</th>
                        <th class="py-3.5 px-4 min-w-[280px]">تفاصيل البيان والعملية</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">المسؤول</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            
                            {{-- التاريخ والوقت --}}
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="block text-xs font-bold text-slate-700 font-mono">{{ $log->created_at->format('Y-m-d') }}</span>
                                <span class="block text-[11px] text-slate-400 font-mono mt-0.5">{{ $log->created_at->format('h:i A') }}</span>
                            </td>
                            
                            {{-- نوع الحركة --}}
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($log->action_type == 'create')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200/60 rounded-lg text-xs font-bold">✨ إنشاء جديد</span>
                                @elseif($log->action_type == 'increase')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200/60 rounded-lg text-xs font-bold">🟢 زيادة (+)</span>
                                @elseif($log->action_type == 'decrease')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200/60 rounded-lg text-xs font-bold">🟡 نقصان (-)</span>
                                @elseif($log->action_type == 'delete')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200/60 rounded-lg text-xs font-bold">🔴 حذف (🗑️)</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-sky-50 text-sky-700 border border-sky-200/60 rounded-lg text-xs font-bold">🔵 تحديث (🔄)</span>
                                @endif
                            </td>
                            
                            {{-- تصنيف العملية --}}
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($log->category == 'money')
                                    <span class="inline-block text-xs font-semibold text-emerald-800 bg-emerald-50/80 px-2.5 py-1 rounded-md border border-emerald-100">أموال وعوائد</span>
                                @elseif($log->category == 'bookings')
                                    <span class="inline-block text-xs font-semibold text-sky-800 bg-sky-50/80 px-2.5 py-1 rounded-md border border-sky-100">حجوزات ومقاعد</span>
                                @elseif($log->category == 'trips')
                                    <span class="inline-block text-xs font-semibold text-indigo-800 bg-indigo-50/80 px-2.5 py-1 rounded-md border border-indigo-100">إدارة الرحلات</span>
                                @else
                                    <span class="inline-block text-xs font-semibold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-md">{{ $log->category }}</span>
                                @endif
                            </td>
                            
                            {{-- التغير المالي (مع معالجة القيمة الصفرية) --}}
                            <td class="py-3.5 px-4 text-center font-bold font-mono text-sm whitespace-nowrap">
                                @if($log->amount_changed != 0)
                                    <span class="{{ $log->amount_changed < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $log->amount_changed > 0 ? '+' : '' }}{{ number_format($log->amount_changed, 2) }}
                                        <span class="text-[10px] font-sans font-normal text-slate-400">ر.س</span>
                                    </span>
                                @else
                                    <span class="text-slate-300 font-mono font-normal">—</span>
                                @endif
                            </td>
                            
                            {{-- تغير الحجوزات والمقاعد (مع معالجة القيمة الصفرية) --}}
                            <td class="py-3.5 px-4 text-center font-bold font-mono text-sm whitespace-nowrap">
                                @if($log->seats_changed != 0)
                                    <span class="{{ $log->seats_changed < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $log->seats_changed > 0 ? '+' : '' }}{{ $log->seats_changed }}
                                    </span>
                                @else
                                    <span class="text-slate-300 font-mono font-normal">—</span>
                                @endif
                            </td>
                            
                            {{-- تفاصيل البيان --}}
                            <td class="py-3.5 px-4 text-slate-700 text-xs leading-relaxed">
                                <div class="bg-slate-50/80 p-2.5 rounded-xl border border-slate-200/50 text-slate-800 font-medium">
                                    {{ $log->description }}
                                </div>
                            </td>
                            
                            {{-- اسم الموظف المسؤول --}}
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100/80 rounded-lg text-slate-700 text-xs font-semibold border border-slate-200/50">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span>{{ $log->operator_name }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414a1 1 0 00-.707-.293H4"/></svg>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-500">لا توجد سجلات تدقيق طابقت خيارات البحث.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- الترقيم (Pagination) --}}
        @if(method_exists($logs, 'hasPages') && $logs->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                {{ $logs->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

</div>
@endsection