@extends('layouts.client')

@section('content')
<div class="container mx-auto px-4 py-10 max-w-4xl animate-fade-in">

    {{-- هيدر التحكم --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-4 print:hidden">
        <div>
            <h1 class="text-3xl font-black text-slate-900 italic tracking-tighter">تذاكر الحافلة جاهزة 🚌</h1>
            <p class="text-[10px] text-slate-400 font-black uppercase tracking-[0.2em] mt-1 italic">نرجو التواجد في المحطة قبل الموعد بـ 30 دقيقة</p>
        </div>
        <div class="flex gap-3">
            <button onclick="window.print()" class="bg-indigo-600 text-white px-8 py-4 rounded-2xl text-xs font-black italic shadow-xl shadow-indigo-100 hover:bg-slate-900 transition-all flex items-center gap-2">
                طباعة جميع التذاكر 🖨️
            </button>
        </div>
    </div>

    {{-- تكرار التذاكر بعدد الركاب --}}
    @foreach($booking->seats as $index => $seat)
    <div class="bg-white rounded-[3rem] overflow-hidden shadow-2xl border border-slate-100 relative mb-12 page-break print:mb-0 print:shadow-none print:border-slate-300">

        {{-- الجزء العلوي: هيدر الرحلة البرية --}}
        <div class="bg-indigo-700 p-8 text-white relative overflow-hidden">
            <div class="absolute right-0 top-0 w-full h-full opacity-10 pointer-events-none">
                <svg viewBox="0 0 100 100" class="h-full w-full fill-white">
                    <path d="M0 50 Q 25 0 50 50 T 100 50" fill="none" stroke="currentColor" stroke-width="2"/>
                </svg>
            </div>

            <div class="relative z-10 flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-2xl">🚌</div>
                    <div>
                        <h2 class="text-xl font-black italic tracking-tight">عبور للنقل البري</h2>
                        <p class="text-[9px] text-indigo-200 font-black uppercase tracking-widest">Bus Boarding Pass</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-[10px] font-black italic">ID: {{ $booking->id }}-{{ $seat }}</span>
                </div>
            </div>
        </div>

        {{-- محتوى التذكرة --}}
        <div class="p-8 md:p-12 relative">

            {{-- مسار الحافلة --}}
            <div class="flex flex-col md:flex-row justify-between items-center gap-6 mb-12">
                <div class="text-center md:text-right">
                    <p class="text-[9px] text-slate-400 font-black uppercase mb-1">من (المحطة)</p>
                    <h3 class="text-3xl font-black text-slate-950 italic">الرياض</h3>
                </div>

                <div class="flex-grow flex items-center justify-center gap-4 group">
                    <div class="h-[2px] flex-grow bg-slate-100 relative">
                        <div class="absolute inset-0 bg-indigo-500 w-0 group-hover:w-full transition-all duration-1000"></div>
                    </div>
                    <span class="text-xl">📍</span>
                    <div class="h-[2px] flex-grow bg-slate-100"></div>
                </div>

                <div class="text-center md:text-left">
                    <p class="text-[9px] text-slate-400 font-black uppercase mb-1">إلى (الوجهة)</p>
                    <h3 class="text-3xl font-black text-slate-950 italic">مكة المكرمة</h3>
                </div>
            </div>

            {{-- تفاصيل المقعد والركاب --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 py-8 border-t border-b border-slate-100 border-dashed bg-slate-50/30 -mx-12 px-12">
                <div>
                    <p class="text-[9px] text-slate-400 font-black uppercase mb-1 italic">اسم المسافر</p>
                    <p class="font-black text-slate-900 italic">{{ $booking->customer_name }}</p>
                </div>
                <div>
                    <p class="text-[9px] text-slate-400 font-black uppercase mb-1 italic">رقم المقعد</p>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl font-black text-indigo-600 italic leading-none">{{ $seat }}</span>
                        <span class="text-[8px] bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded font-black italic">WINDOW</span>
                    </div>
                </div>
                <div>
                    <p class="text-[9px] text-slate-400 font-black uppercase mb-1 italic">وقت الانطلاق</p>
                    <p class="font-black text-slate-900 italic">{{ $booking->time }}</p>
                </div>
                <div>
                    <p class="text-[9px] text-slate-400 font-black uppercase mb-1 italic text-amber-600">رقم الرصيف (Gate)</p>
                    <p class="font-black text-slate-900 italic">Gate 04</p>
                </div>
            </div>

            {{-- معلومات إضافية والباركود --}}
            <div class="mt-10 flex flex-col md:flex-row justify-between items-end gap-6">
                <div class="space-y-4">
                    <div class="flex gap-4">
                        <div class="text-[10px] font-black italic">
                            <span class="text-slate-400 block uppercase">التاريخ</span>
                            <span class="text-slate-900">{{ $booking->date }}</span>
                        </div>
                        <div class="text-[10px] font-black italic border-r pr-4 border-slate-200">
                            <span class="text-slate-400 block uppercase">نوع الحافلة</span>
                            <span class="text-slate-900">VIP Business Class</span>
                        </div>
                    </div>
                    <p class="text-[9px] text-slate-400 max-w-xs italic leading-relaxed">
                        * يرجى إبراز الهوية الوطنية عند طلب الموظف. التذكرة صالحة لهذا الموعد فقط.
                    </p>
                </div>

                <div class="flex flex-col items-center gap-2">
                    {{-- QR Code مخصص لكل تذكرة --}}
                    <div class="bg-white border-4 border-slate-50 p-2 rounded-2xl shadow-sm">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=BUS-{{ $booking->id }}-{{ $seat }}"
                             class="w-20 h-20 mix-blend-multiply">
                    </div>
                    <p class="text-[7px] font-black text-slate-400 tracking-widest uppercase">Verified by Obour</p>
                </div>
            </div>
        </div>

        {{-- الجزء المخصص للقص (Perforated Stub) --}}
        <div class="bg-slate-950 p-4 border-t-2 border-dashed border-white/20 flex justify-between items-center">
            <div class="flex gap-1 overflow-hidden opacity-30">
                @for($i=0; $i<20; $i++) <div class="w-4 h-1 bg-white rounded-full"></div> @endfor
            </div>
            <span class="text-[8px] text-indigo-400 font-black italic uppercase tracking-widest">تذكرة صعود الحافلة الإلكترونية</span>
        </div>
    </div>
    @endforeach

</div>

<style>
    @media print {
        body { background: white !important; }
        .print\:hidden { display: none !important; }
        .container { max-width: 100% !important; padding: 0 !important; }
        .page-break { page-break-after: always; margin-bottom: 2rem !important; box-shadow: none !important; border: 1px solid #eee !important; }
    }

    .animate-fade-in { animation: fadeIn 0.6s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endsection
