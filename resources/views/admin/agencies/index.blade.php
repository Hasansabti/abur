@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8 animate-fade-in" x-data="{ view: 'all', search: '' }">

    {{-- رأس الصفحة --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
        <div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tighter flex items-center gap-3">
                إدارة شركاء النجاح
                <span class="text-sm bg-indigo-600 text-white px-3 py-1 rounded-full font-bold tracking-normal">الوكالات</span>
            </h1>
            <p class="text-slate-500 mt-2 font-medium">مراقبة أداء الوكالات، اعتماد المنضمين الجدد، وإدارة الصلاحيات.</p>
        </div>

        <div class="flex items-center gap-3 bg-white p-2 rounded-[2rem] shadow-sm border border-slate-100">
            <button @click="view = 'all'" :class="view === 'all' ? 'bg-slate-900 text-white shadow-lg' : 'text-slate-400'" class="px-6 py-2.5 rounded-full text-xs font-black transition-all">الكل</button>
            <button @click="view = 'pending'" :class="view === 'pending' ? 'bg-orange-500 text-white shadow-lg' : 'text-slate-400'" class="px-6 py-2.5 rounded-full text-xs font-black transition-all flex items-center gap-2">
                طلبات الانضمام
                <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
            </button>
        </div>
    </div>

    {{-- كروت سريعة للأداء --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm flex items-center gap-5">
            <div class="w-14 h-14 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center text-2xl shadow-inner">✅</div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">وكالات موثقة</p>
                <h4 class="text-2xl font-black text-slate-800">18</h4>
            </div>
        </div>
        <div class="bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm flex items-center gap-5">
            <div class="w-14 h-14 bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center text-2xl shadow-inner">⏳</div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">بانتظار المراجعة</p>
                <h4 class="text-2xl font-black text-slate-800">06</h4>
            </div>
        </div>
        <div class="bg-indigo-600 p-6 rounded-[2.5rem] shadow-xl shadow-indigo-100 flex items-center gap-5 text-white">
            <div class="w-14 h-14 bg-white/10 rounded-2xl flex items-center justify-center text-2xl">📈</div>
            <div>
                <p class="text-[10px] font-black text-indigo-200 uppercase tracking-widest">أعلى وكالة مبيعاً</p>
                <h4 class="text-lg font-black italic">وكالة الفارس</h4>
            </div>
        </div>
    </div>

    {{-- أدوات البحث والفلترة --}}
    <div class="bg-white p-4 rounded-[2rem] shadow-sm border border-slate-100 flex flex-col md:flex-row gap-4 mb-6">
        <div class="relative flex-1">
            <span class="absolute right-5 top-1/2 -translate-y-1/2 opacity-30">🔍</span>
            <input type="text" x-model="search" placeholder="ابحث عن اسم الوكالة، السجل التجاري، أو البريد..."
                   class="w-full bg-slate-50 border-none rounded-2xl pr-12 py-4 text-sm font-bold focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
        </div>
        <button class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-xs hover:bg-indigo-600 transition-all shadow-lg shadow-slate-200">
            تصدير بيانات الوكالات (Excel)
        </button>
    </div>

    {{-- جدول البيانات المطور --}}
    <div class="bg-white rounded-[3rem] shadow-xl shadow-slate-100 border border-slate-50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="p-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">الوكالة / السجل التجاري</th>
                        <th class="p-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">مستوى النشاط</th>
                        <th class="p-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">الرصيد المالي</th>
                        <th class="p-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">الحالة</th>
                        <th class="p-8 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($agencies as $agency)
                    <tr class="group hover:bg-indigo-50/30 transition-all">
                        <td class="p-8">
                            <div class="flex items-center gap-5">
                                <div class="w-14 h-14 bg-white border-2 border-slate-100 rounded-2xl flex items-center justify-center text-2xl group-hover:border-indigo-200 transition-colors shadow-sm">
                                    🏢
                                </div>
                                <div>
                                    <p class="font-black text-slate-800 text-lg tracking-tighter">{{ $agency->name }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold mt-1 flex items-center gap-1 uppercase">
                                        <span class="text-indigo-500">CR:</span> {{ $agency->commercial_registration ?? '1010XXXXXX' }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="p-8">
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-black text-slate-700">{{ $agency->trips_count }}</span>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase">رحلة منشورة</span>
                                </div>
                                <div class="w-24 h-1 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="bg-indigo-500 h-full" style="width: 70%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="p-8">
                            <p class="text-sm font-black text-slate-800">12,450 <small class="text-[9px] text-slate-400">ر.س</small></p>
                            <p class="text-[9px] text-green-500 font-bold italic">مستحقات قابلة للسحب</p>
                        </td>
                        <td class="p-8">
                            @if($agency->status == 'active')
                                <span class="flex items-center gap-1.5 text-green-600 font-black text-[10px] bg-green-50 w-fit px-3 py-1 rounded-lg border border-green-100">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> نشط
                                </span>
                            @elseif($agency->status == 'pending')
                                <span class="flex items-center gap-1.5 text-orange-600 font-black text-[10px] bg-orange-50 w-fit px-3 py-1 rounded-lg border border-orange-100">
                                    <span class="w-1.5 h-1.5 bg-orange-500 rounded-full animate-pulse"></span> قيد المراجعة
                                </span>
                            @else
                                <span class="flex items-center gap-1.5 text-red-600 font-black text-[10px] bg-red-50 w-fit px-3 py-1 rounded-lg border border-red-100">
                                    محظور 🚫
                                </span>
                            @endif
                        </td>
                        <td class="p-8">
                            <div class="flex justify-center gap-2">
                                @if($agency->status == 'pending')
                                    <button class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-xs font-black shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">اعتماد</button>
                                @endif
                                <button class="w-10 h-10 bg-slate-100 text-slate-500 rounded-xl flex items-center justify-center hover:bg-indigo-500 hover:text-white transition-all" title="تعديل">
                                    ⚙️
                                </button>
                                <button class="w-10 h-10 bg-slate-50 text-red-300 rounded-xl flex items-center justify-center hover:bg-red-500 hover:text-white transition-all" title="حظر">
                                    🔒
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- الترقيم (Pagination) --}}
        <div class="p-8 bg-slate-50/50 flex justify-between items-center border-t border-slate-100">
            <p class="text-xs font-bold text-slate-400">عرض 1-10 من أصل 24 وكالة</p>
            <div class="flex gap-2">
                <button class="w-8 h-8 rounded-lg border border-slate-200 flex items-center justify-center text-xs opacity-50 cursor-not-allowed">السابق</button>
                <button class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-black shadow-lg shadow-indigo-100">1</button>
                <button class="w-8 h-8 rounded-lg border border-slate-200 flex items-center justify-center text-xs font-bold text-slate-500 hover:bg-white transition-colors">2</button>
                <button class="w-8 h-8 rounded-lg border border-slate-200 flex items-center justify-center text-xs font-bold text-slate-500 hover:bg-white transition-colors">التالي</button>
            </div>
        </div>
    </div>

</div>

<style>
    .animate-fade-in { animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endsection
