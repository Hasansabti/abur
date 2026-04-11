@extends('layouts.agency')

@section('content')
<div class="container mx-auto px-4 py-8 animate-fade-in" x-data="{ range: 'month', showExport: false }">

    {{-- رأس الصفحة--}}
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-10">
        <div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tighter">التقارير والتحصيل المالي</h1>
            <p class="text-slate-500 mt-2 font-medium italic">إدارة شاملة للتدفقات النقدية، الفواتير، وتحليلات الأداء المالي.</p>
        </div>

        <div class="flex items-center gap-3">
            {{-- زر تحميل التقارير مع قائمة منسدلة بسيطة --}}
            <div class="relative" @click.away="showExport = false">
                <button @click="showExport = !showExport" class="bg-indigo-600 text-white px-6 py-4 rounded-2xl font-black text-xs shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center gap-3">
                    <span>تصدير تقرير مالي</span>
                    <span :class="showExport ? 'rotate-180' : ''" class="transition-transform">▼</span>
                </button>

                <div x-show="showExport" class="absolute left-0 mt-3 w-48 bg-white rounded-2xl shadow-2xl border border-slate-50 overflow-hidden z-50 animate-fade-in">
                    <button class="w-full text-right p-4 text-xs font-bold hover:bg-slate-50 flex items-center justify-between border-b border-slate-50">
                        ملف Excel (.xlsx) 📊
                    </button>
                    <button class="w-full text-right p-4 text-xs font-bold hover:bg-slate-50 flex items-center justify-between">
                        ملف PDF (.pdf) 📄
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- الشبكة الرئيسية --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        {{-- الجانب الأيمن: الإحصائيات والرسم البياني --}}
        <div class="lg:col-span-8 space-y-8">

            {{-- كروت الإحصائيات  --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 flex items-center gap-4 shadow-sm">
                    <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-xl font-black">💰</div>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">صافي الإيرادات</p>
                        <p class="text-lg font-black text-slate-800">{{ number_format($stats->total_revenue) }} <small class="text-[10px]">ر.س</small></p>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 flex items-center gap-4 shadow-sm">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl font-black">🎟️</div>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">تذاكر مباعة</p>
                        <p class="text-lg font-black text-slate-800">{{ $stats->total_bookings }}</p>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 flex items-center gap-4 shadow-sm">
                    <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center text-xl font-black">⏳</div>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">قيد التسوية</p>
                        <p class="text-lg font-black text-slate-800">{{ number_format($stats->pending_amount) }} <small class="text-[10px]">ر.س</small></p>
                    </div>
                </div>
            </div>

            {{-- الرسم البياني المطور --}}
            <div class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                <div class="flex justify-between items-center mb-10">
                    <h3 class="font-black text-slate-800 flex items-center gap-3">
                        تحليل المبيعات الأسبوعي
                        <span class="text-[10px] bg-slate-100 text-slate-500 px-3 py-1 rounded-full font-bold">آخر 7 أيام</span>
                    </h3>
                </div>

                {{-- تمثيل بياني تفاعلي بسيط --}}
                <div class="h-64 flex items-end justify-between gap-4 px-2">
                    @foreach([30, 60, 45, 90, 100, 50, 80] as $index => $height)
                    <div class="flex-1 flex flex-col items-center gap-4 group">
                        <div class="w-full bg-slate-50 rounded-2xl relative overflow-hidden h-full flex items-end">
                            <div class="w-full bg-indigo-600 rounded-2xl transition-all duration-1000 group-hover:bg-indigo-400" style="height: {{ $height }}%"></div>
                            {{-- Tooltip --}}
                            <div class="absolute -top-10 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[8px] font-black px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity">
                                {{ $height * 200 }} ر.س
                            </div>
                        </div>
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">يوم {{ $index + 1 }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- الجانب الأيسر: إدارة المحفظة والفواتير --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- المحفظة الرقمية --}}
            <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white shadow-2xl relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-32 h-32 bg-indigo-600/20 rounded-full blur-3xl group-hover:bg-indigo-600/40 transition-colors"></div>
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-6">الرصيد المتاح للسحب</p>
                <h2 class="text-4xl font-black mb-10 tracking-tighter">14,250.00 <small class="text-sm opacity-50">ر.س</small></h2>

                <div class="space-y-3">
                    <button class="w-full py-4 bg-white text-slate-900 rounded-2xl font-black text-xs hover:bg-indigo-500 hover:text-white transition-all shadow-xl shadow-black/20">
                        طلب سحب الأرباح 💳
                    </button>
                    <p class="text-center text-[9px] text-slate-500 font-bold italic">سيتم تحويل المبلغ إلى حساب الآيبان المسجل</p>
                </div>
            </div>

            {{-- فواتير سريعة التحميل --}}
            <div class="bg-white rounded-[2.5rem] p-6 border border-slate-100 shadow-sm">
                <h3 class="text-xs font-black text-slate-800 mb-6 px-2 flex items-center justify-between">
                    أحدث الفواتير
                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                </h3>
                <div class="space-y-4">
                    @foreach(range(1, 3) as $invoice)
                    <div class="flex items-center justify-between p-3 hover:bg-slate-50 rounded-2xl transition-all cursor-pointer group">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-sm group-hover:bg-indigo-100 group-hover:text-indigo-600 transition-colors">📄</div>
                            <div>
                                <p class="text-[10px] font-black text-slate-700">فاتورة #INV-00{{ $invoice }}</p>
                                <p class="text-[8px] text-slate-400 font-bold uppercase">10 أبريل 2026</p>
                            </div>
                        </div>
                        <span class="text-lg opacity-0 group-hover:opacity-100 transition-opacity">⬇️</span>
                    </div>
                    @endforeach
                </div>
                <button class="w-full mt-6 py-3 border-2 border-dashed border-slate-100 rounded-2xl text-[9px] font-black text-slate-400 hover:border-indigo-200 hover:text-indigo-600 transition-all">
                    عرض جميع الفواتير
                </button>
            </div>
        </div>

        {{-- جدول العمليات --}}
        <div class="lg:col-span-12 bg-white rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/20">
                <h3 class="font-black text-slate-800 flex items-center gap-2">
                    سجل المعاملات
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[10px] rounded-lg">تحديث تلقائي</span>
                </h3>
            </div>

            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest bg-slate-50/50">
                        <th class="p-8">المعرف</th>
                        <th class="p-8">الرحلة</th>
                        <th class="p-8">التاريخ</th>
                        <th class="p-8">المبلغ الصافي</th>
                        <th class="p-8">الحالة</th>
                        <th class="p-8"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($recent_transactions as $transaction)
                    <tr class="group hover:bg-slate-50 transition-all">
                        <td class="p-8 text-xs font-black text-slate-400">#{{ rand(500, 999) }}</td>
                        <td class="p-8">
                            <p class="text-sm font-black text-slate-700 mb-0.5">{{ $transaction['type'] }}</p>
                            <p class="text-[10px] text-indigo-500 font-bold italic">تم الحجز بواسطة عميل</p>
                        </td>
                        <td class="p-8">
                            <span class="text-xs font-bold text-slate-500">{{ $transaction['date'] }}</span>
                        </td>
                        <td class="p-8">
                            <span class="text-sm font-black text-slate-900">{{ number_format($transaction['amount']) }} ر.س</span>
                        </td>
                        <td class="p-8">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-600 rounded-lg text-[9px] font-black border border-green-100">
                                <span class="w-1 h-1 bg-green-500 rounded-full"></span>
                                مكتمل
                            </span>
                        </td>
                        <td class="p-8 text-left">
                            <button class="text-slate-300 hover:text-indigo-600 transition-colors">👁️ التفاصيل</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .animate-fade-in { animation: fadeIn 0.7s cubic-bezier(0.16, 1, 0.3, 1); }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endsection
