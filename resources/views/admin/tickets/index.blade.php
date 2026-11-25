@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-6 py-8" dir="rtl" x-data="{ activeModal: null }">
    
    {{-- الهيدر الرئيسي --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-800">إدارة تذاكر الدعم الفني 🛠️</h1>
            <p class="text-sm text-slate-400 mt-1">عرض طلبات المستخدمين، المشاكل التقنية، والرد عليها مباشرة.</p>
        </div>
        
        {{-- 🔍 بار البحث الذكي --}}
        <form method="GET" action="{{ route('admin.tickets.index') }}" class="w-full md:w-96 flex items-center gap-2">
            {{-- الاحتفاظ بالتصنيف والحالة الحالية عند البحث --}}
            @if(request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            <div class="relative w-full">
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="ابحث باسم العميل، البريد، أو عنوان التذكرة..." 
                       class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition-all shadow-sm">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔍</span>
            </div>
            @if(request('search'))
                <a href="{{ route('admin.tickets.index', request()->only('category', 'status')) }}" class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition">
                    إلغاء
                </a>
            @endif
        </form>
    </div>

    {{-- 🎛️ قسم الفلاتر (الحالة والتصنيفات) --}}
    @php
        $currentCategory = request('category');
        $currentStatus = request('status');

        $statuses = [
            '' => 'جميع الحالات',
            'open' => 'مفتوحة',
            'pending' => 'قيد المعالجة',
            'resolved' => 'تم الحل',
            'closed' => 'مغلقة',
        ];

        $categories = [
            '' => 'كل الأقسام',
            'general' => 'استفسار عام',
            'booking' => 'مشكلة حجز ودفع',
            'technical' => 'عطل تقني',
            'complaint' => 'شكوى / اقتراح',
        ];
    @endphp

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6 bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
        
        {{-- فلاتر الحالة --}}
        <div class="flex items-center gap-1 overflow-x-auto pb-1 lg:pb-0 scrollbar-none">
            <span class="text-xs font-black text-slate-400 ml-2 whitespace-nowrap">الحالة:</span>
            @foreach($statuses as $key => $label)
                @php $isActive = ($currentStatus === $key) || (empty($currentStatus) && $key === ''); @endphp
                <a href="{{ route('admin.tickets.index', array_merge(request()->except('status', 'page'), array_filter(['status' => $key]))) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-black transition-all whitespace-nowrap
                          {{ $isActive ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="h-px lg:h-6 bg-slate-100 w-full lg:w-px"></div>

        {{-- فلاتر القسم / التصنيف --}}
        <div class="flex items-center gap-1 overflow-x-auto pb-1 lg:pb-0 scrollbar-none">
            <span class="text-xs font-black text-slate-400 ml-2 whitespace-nowrap">القسم:</span>
            @foreach($categories as $key => $label)
                @php $isActive = ($currentCategory === $key) || (empty($currentCategory) && $key === ''); @endphp
                <a href="{{ route('admin.tickets.index', array_merge(request()->except('category', 'page'), array_filter(['category' => $key]))) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-black transition-all whitespace-nowrap border
                          {{ $isActive ? 'bg-slate-800 text-white border-slate-800 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl text-sm font-bold flex items-center gap-2">
            <span>✅</span> {{ session('success') }}
        </div>
    @endif

    {{-- الجدول --}}
    <div class="bg-white border border-slate-100 rounded-[2rem] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-400 text-xs font-black uppercase tracking-wider">
                        <th class="p-5">صاحب التذكرة</th>
                        <th class="p-5">العنوان والقسم</th>
                        <th class="p-5">الحالة</th>
                        <th class="p-5">التاريخ</th>
                        <th class="p-5 text-left">الإجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm font-medium text-slate-600">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-slate-50/40 transition-colors group">
                            {{-- بيانات العميل --}}
                            <td class="p-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-slate-100 text-slate-600 font-black rounded-xl flex items-center justify-center text-sm shadow-sm group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors">
                                        {{ mb_substr($ticket->user->name ?? 'م', 0, 1) }}
                                    </div>
                                    <div>
                                        <span class="block font-black text-slate-800">{{ $ticket->user->name ?? 'مستخدم محذوف' }}</span>
                                        <span class="block text-xs text-slate-400 mt-0.5">{{ $ticket->user->email ?? '' }}</span>
                                    </div>
                                </div>
                            </td>
                            
                            {{-- العنوان والقسم --}}
                            <td class="p-5">
                                <span class="font-black text-slate-700 block truncate max-w-xs">{{ $ticket->subject }}</span>
                                <span class="inline-block text-[10px] bg-slate-100 text-slate-500 font-bold px-2 py-0.5 rounded-md mt-1">
                                    @if($ticket->category == 'general') استفسار عام
                                    @elseif($ticket->category == 'booking') مشكلة حجز ودفع
                                    @elseif($ticket->category == 'technical') عطل تقني
                                    @elseif($ticket->category == 'complaint') شكوى / اقتراح
                                    @else {{ $ticket->category }}
                                    @endif
                                </span>
                            </td>
                            
                            {{-- الحالة --}}
                            <td class="p-5">
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-black px-2.5 py-1 rounded-lg
                                    {{ $ticket->status === 'open' ? 'bg-blue-50 text-blue-700' : '' }}
                                    {{ $ticket->status === 'pending' ? 'bg-amber-50 text-amber-700' : '' }}
                                    {{ $ticket->status === 'resolved' ? 'bg-emerald-50 text-emerald-700' : '' }}
                                    {{ $ticket->status === 'closed' ? 'bg-slate-100 text-slate-500' : '' }}">
                                    <span class="w-1.5 h-1.5 rounded-full 
                                        {{ $ticket->status === 'open' ? 'bg-blue-500 animate-pulse' : '' }}
                                        {{ $ticket->status === 'pending' ? 'bg-amber-500' : '' }}
                                        {{ $ticket->status === 'resolved' ? 'bg-emerald-500' : '' }}
                                        {{ $ticket->status === 'closed' ? 'bg-slate-400' : '' }}">
                                    </span>
                                    {{ $ticket->status === 'open' ? 'مفتوحة' : '' }}
                                    {{ $ticket->status === 'pending' ? 'قيد المعالجة' : '' }}
                                    {{ $ticket->status === 'resolved' ? 'تم الحل 👍' : '' }}
                                    {{ $ticket->status === 'closed' ? 'مغلقة' : '' }}
                                </span>
                            </td>

                            {{-- التاريخ --}}
                            <td class="p-5 text-xs text-slate-400 font-bold">
                                {{ $ticket->created_at->diffForHumans() }}
                            </td>

                            {{-- الإجراءات --}}
                            <td class="p-5 text-left" x-data="{ openModal: false }">
                                <button @click="openModal = true"
                                        class="px-4 py-2 text-xs font-black text-white bg-slate-800 hover:bg-slate-900 rounded-xl transition-all shadow-sm active:scale-95">
                                    معاينة / رد 💬
                                </button>

                                <div x-show="openModal" 
                                     style="display: none;"
                                     x-transition:enter="transition ease-out duration-300"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100"
                                     x-transition:leave="transition ease-in duration-200"
                                     x-transition:leave-start="opacity-100"
                                     x-transition:leave-end="opacity-0"
                                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm text-right">
                                    
                                    <div @click.away="openModal = false" 
                                         class="bg-white w-full max-w-3xl rounded-[2rem] shadow-xl border border-slate-100 overflow-hidden flex flex-col max-h-[90vh]">
                                        
                                        {{-- هيدر المودال --}}
                                        <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                                            <div>
                                                <h3 class="font-black text-slate-800 text-sm">تفاصيل تذكرة الدعم الفني #{{ $ticket->id }}</h3>
                                                <p class="text-[11px] text-slate-400 mt-0.5">المرسلة بواسطة: {{ $ticket->user->name ?? '' }}</p>
                                            </div>
                                            <button type="button" @click="openModal = false" class="w-8 h-8 flex items-center justify-center bg-slate-200/60 hover:bg-slate-200 text-slate-600 rounded-full text-xs">
                                                ❌
                                            </button>
                                        </div>

                                        {{-- محتوى المودال --}}
                                        <div class="p-6 overflow-y-auto space-y-6 flex-grow">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                
                                                {{-- تفاصيل رسالة العميل --}}
                                                <div class="space-y-4 text-right">
                                                    <div>
                                                        <span class="text-[11px] font-black text-slate-400 uppercase block mb-1">موضوع التذكرة:</span>
                                                        <p class="text-xs font-black text-slate-700 bg-slate-50 p-3 rounded-xl border border-slate-100">{{ $ticket->subject }}</p>
                                                    </div>

                                                    <div>
                                                        <span class="text-[11px] font-black text-slate-400 uppercase block mb-1">📩 محتوى الرسالة:</span>
                                                        <div class="text-xs text-slate-600 bg-slate-50 p-4 rounded-xl border border-slate-100 leading-relaxed font-medium min-h-[100px] whitespace-pre-line">
                                                            {{ $ticket->message }}
                                                        </div>
                                                    </div>
                                                    
                                                    @if($ticket->reply)
                                                        <div class="p-3 bg-emerald-50 border border-emerald-100 rounded-xl">
                                                            <span class="block text-[11px] font-black text-emerald-700 mb-1">الرد المسجل حالياً:</span>
                                                            <p class="text-xs text-emerald-800 font-medium leading-relaxed">{{ $ticket->reply }}</p>
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- فورم الرد --}}
                                                <div class="border-t md:border-t-0 md:border-r border-slate-100 pt-4 md:pt-0 md:pr-6 text-right">
                                                    <h4 class="font-black text-slate-800 flex items-center gap-1.5 mb-3 text-xs">
                                                        <span>✍️</span> كتابة رد أو تحديث الحالة:
                                                    </h4>
                                                    
                                                    <form action="{{ route('admin.tickets.reply', $ticket->id) }}" method="POST" class="space-y-4">
                                                        @csrf
                                                        <div>
                                                            <textarea name="reply" rows="5" required placeholder="اكتب ردك الرسمي للعميل هنا..." 
                                                                      class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:border-blue-500 focus:bg-white transition-all resize-none shadow-inner leading-relaxed">{{ $ticket->reply }}</textarea>
                                                        </div>

                                                        <div>
                                                            <label class="block text-[11px] font-black text-slate-400 mb-1">تحديث حالة التذكرة:</label>
                                                            <select name="status" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:border-blue-500 focus:bg-white transition-all">
                                                                <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>مفتوحة</option>
                                                                <option value="pending" {{ $ticket->status == 'pending' ? 'selected' : '' }}>قيد المعالجة</option>
                                                                <option value="resolved" {{ $ticket->status == 'resolved' ? 'selected' : '' }}>تم الحل</option>
                                                                <option value="closed" {{ $ticket->status == 'closed' ? 'selected' : '' }}>إغلاق التذكرة نهائياً</option>
                                                            </select>
                                                        </div>

                                                        <div class="pt-2 flex justify-end gap-2">
                                                            <button type="button" @click="openModal = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition">
                                                                إلغاء
                                                            </button>
                                                            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-xl transition-all shadow-md">
                                                                إرسال الرد وحفظ الحالة 🚀
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-10 text-center text-slate-400 font-bold">
                                📂 لا توجد تذاكر دعم تطابق معايير التصفية والبحث الحالية.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- رابط الترقيم --}}
    <div class="mt-6">
        {{ $tickets->links() }}
    </div>

</div>
@endsection