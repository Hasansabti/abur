@extends('layouts.client') {{-- أو اسم ملف الهيدر والفوتر الرئيسي عندك، مثلاً client.layout --}}

@section('content')
{{-- قمنا بتهيئة متغير في Alpine.js باسم activeTab للتحكم في التبويب النشط الافتراضي --}}
<div class="container mx-auto px-6 py-10" dir="rtl" x-data="{ activeTab: 'unanswered' }">
    
    {{-- ترويسة الصفحة وعناوينها --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-10">
        <div>
            <h1 class="text-2xl font-black text-slate-800">أرشيف تذاكر الدعم الفني</h1>
            <p class="text-sm text-slate-400 mt-1">تابع حالة طلباتك واستفساراتك المرسلة إلى إدارة عبور.</p>
        </div>
        
        {{-- زر لفتح نفس المودال المنبثق من أي مكان في حال رغب برفع تذكرة جديدة --}}
        <button @click="supportModalOpen = true" 
                class="flex items-center gap-2 px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-xl transition-all shadow-md shadow-blue-600/10 active:scale-95">
            <span>رفع تذكرة جديدة</span> ✨
        </button>
    </div>

    {{-- إشعارات النجاح أو الفشل --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl text-sm font-bold flex items-center gap-2">
            <span>✅</span> {{ session('success') }}
        </div>
    @endif

    {{-- فحص ما إذا كان العميل يملك تذاكر أم لا --}}
    @if($tickets->isEmpty())
        <div class="bg-white border border-slate-100 rounded-[2rem] p-12 text-center shadow-sm">
            <div class="w-16 h-16 bg-slate-50 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">
                📂
            </div>
            <h3 class="text-base font-black text-slate-700">لا توجد تذاكر دعم حالياً</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto leading-relaxed">
                لم تقم بإنشاء أي تذكرة دعم فني سابقاً. إذا كنت تواجه مشكلة، لا تتردد في مراسلتنا.
            </p>
        </div>
    @else

        {{-- 📂 نظام التبويبات الجديد لتصنيف التذاكر --}}
        <div class="flex flex-wrap gap-2 mb-6 bg-slate-100/60 p-1.5 rounded-2xl w-max border border-slate-200/50">
            <button @click="activeTab = 'unanswered'" 
                    :class="activeTab === 'unanswered' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    class="px-4 py-2.5 text-xs font-black rounded-xl transition-all flex items-center gap-2">
                ⏳ بانتظار الرد 
                <span class="bg-blue-50 text-blue-700 text-[10px] px-2 py-0.5 rounded-md">
                    {{ $tickets->whereNull('reply')->whereIn('status', ['open', 'pending'])->count() }}
                </span>
            </button>
            
            <button @click="activeTab = 'answered'" 
                    :class="activeTab === 'answered' ? 'bg-white text-emerald-600 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    class="px-4 py-2.5 text-xs font-black rounded-xl transition-all flex items-center gap-2">
                👑 تم الرد عليها
                <span class="bg-emerald-50 text-emerald-700 text-[10px] px-2 py-0.5 rounded-md">
                    {{ $tickets->whereNotNull('reply')->where('status', '!=', 'closed')->count() }}
                </span>
            </button>

            <button @click="activeTab = 'old'" 
                    :class="activeTab === 'old' ? 'bg-white text-slate-700 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                    class="px-4 py-2.5 text-xs font-black rounded-xl transition-all flex items-center gap-2">
                📁 التذاكر القديمة / المغلقة
                <span class="bg-slate-200 text-slate-700 text-[10px] px-2 py-0.5 rounded-md">
                    {{ $tickets->where('status', 'closed')->count() }}
                </span>
            </button>
        </div>

        {{-- جدول التذاكر الذكي --}}
        <div class="bg-white border border-slate-100 rounded-[2rem] shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-400 text-xs font-black uppercase tracking-wider">
                            <th class="p-5">رقم التذكرة</th>
                            <th class="p-5">العنوان والقسم</th>
                            <th class="p-5">تاريخ الإنشاء</th>
                            <th class="p-5">الحالة</th>
                            <th class="p-5">رد الإدارة 👑</th>
                            <th class="p-5 text-left">التفاصيل</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm font-medium text-slate-600">
                        @foreach($tickets as $ticket)
                            {{-- تحديد تصنيف السطر برمجياً ليتوافق مع أزرار التبويبات بالخارج --}}
                            @php
                                if($ticket->status === 'closed') {
                                    $rowCategory = 'old';
                                } elseif($ticket->reply) {
                                    $rowCategory = 'answered';
                                } else {
                                    $rowCategory = 'unanswered';
                                }
                            @endphp

                            {{-- يتم التحكم في ظهور السطر وإخفائه ديناميكياً بناءً على التبويب المختار --}}
                            <tr x-show="activeTab === '{{ $rowCategory }}'" 
                                class="hover:bg-slate-50/50 transition-colors group"
                                x-transition:enter="transition ease-out duration-200">
                                
                                {{-- رقم التذكرة --}}
                                <td class="p-5 font-bold text-slate-800">
                                    #{{ $ticket->id }}
                                </td>
                                
                                {{-- العنوان والقسم --}}
                                <td class="p-5">
                                    <span class="block font-black text-slate-800 group-hover:text-blue-600 transition-colors">
                                        {{ $ticket->subject }}
                                    </span>
                                    <span class="inline-block text-[10px] bg-slate-100 text-slate-500 font-bold px-2 py-0.5 rounded-md mt-1">
                                        @if($ticket->category == 'general') استفسار عام
                                        @elseif($ticket->category == 'booking') مشكلة حجز ودفع
                                        @elseif($ticket->category == 'technical') عطل تقني
                                        @elseif($ticket->category == 'complaint') شكوى / اقتراح
                                        @endif
                                    </span>
                                </td>
                                
                                {{-- تاريخ الإنشاء --}}
                                <td class="p-5 text-xs font-bold text-slate-400">
                                    {{ $ticket->created_at->format('Y-m-d (h:i A)') }}
                                </td>
                                
                                {{-- حالة التذكرة الملونة ديناميكياً --}}
                                <td class="p-5">
                                    @if($ticket->status === 'open')
                                        <span class="inline-flex items-center gap-1.5 text-[11px] bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg font-black">
                                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span>
                                            مفتوحة
                                        </span>
                                    @elseif($ticket->status === 'pending')
                                        <span class="inline-flex items-center gap-1.5 text-[11px] bg-amber-50 text-amber-700 px-2.5 py-1 rounded-lg font-black">
                                            <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
                                            قيد المعالجة
                                        </span>
                                    @elseif($ticket->status === 'resolved')
                                        <span class="inline-flex items-center gap-1.5 text-[11px] bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-lg font-black">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                            تم الحل 👍
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-[11px] bg-slate-100 text-slate-500 px-2.5 py-1 rounded-lg font-black">
                                            مغلقة
                                        </span>
                                    @endif
                                </td>

                                {{-- 💬 خانة الرد في الجدول --}}
                                <td class="p-5">
                                    @if($ticket->reply)
                                        <div class="max-w-[150px] truncate text-xs text-emerald-700 bg-emerald-50 font-bold px-2.5 py-1 rounded-lg inline-flex items-center gap-1" title="{{ $ticket->reply }}">
                                            <span>💬</span> {{ $ticket->reply }}
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 italic font-normal">بانتظار الرد...</span>
                                    @endif
                                </td>
                                
                                {{-- نص الرسالة المرسلة والرد التفصيلي المنسدل --}}
                                <td class="p-5 text-left">
                                    <div x-data="{ openDetail: false }">
                                        <button @click="openDetail = !openDetail" class="text-xs text-blue-600 font-black hover:underline focus:outline-none">
                                            عرض الرسالة
                                        </button>
                                        
                                        {{-- منبثق داخلي مبسط لقراءة محتوى التذكرة والرد --}}
                                        <div x-show="openDetail" @click.away="openDetail = false" x-cloak
                                             class="p-4 bg-slate-50 border border-slate-100 rounded-xl mt-2 text-right text-xs font-medium text-slate-600 max-w-md space-y-3 shadow-md">
                                            
                                            <div>
                                                <p class="font-bold text-slate-800 mb-1">📩 رسالتك:</p>
                                                <p class="text-slate-500 whitespace-pre-line leading-relaxed">{{ $ticket->message }}</p>
                                            </div>

                                            @if($ticket->reply)
                                                <div class="pt-2 border-t border-slate-200">
                                                    <p class="font-bold text-emerald-700 mb-1">👑 رد الإدارة:</p>
                                                    <p class="text-emerald-600 font-semibold whitespace-pre-line leading-relaxed">{{ $ticket->reply }}</p>
                                                </div>
                                            @endif
                                            
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{-- نظام الترقيم التلقائي المدمج بـ Laravel --}}
            @if($tickets->hasPages())
                <div class="p-4 bg-slate-50 border-t border-slate-100">
                    {{ $tickets->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection