@extends('layouts.client')

@section('content')
<div class="container mx-auto px-4 py-12 max-w-5xl" x-data="{ tab: 'all' }">

    <div class="mb-12">
        <h1 class="text-4xl font-black text-slate-800 tracking-tighter">حجوزاتي ورحلاتي</h1>
        <p class="text-slate-500 mt-2 font-medium">تابع حالة طلباتك وحمل تذاكرك الإلكترونية هنا.</p>
    </div>

    <div class="flex gap-4 mb-10 overflow-x-auto pb-2">
        <button @click="tab = 'all'" :class="tab === 'all' ? 'bg-blue-600 text-white shadow-xl shadow-blue-100' : 'bg-white text-slate-400 border-slate-100'" class="px-8 py-3 rounded-2xl font-black text-xs transition-all border shrink-0">الكل</button>
        <button @click="tab = 'confirmed'" :class="tab === 'confirmed' ? 'bg-green-500 text-white shadow-xl shadow-green-100' : 'bg-white text-slate-400 border-slate-100'" class="px-8 py-3 rounded-2xl font-black text-xs transition-all border shrink-0">الرحلات المؤكدة</button>
        <button @click="tab = 'pending'" :class="tab === 'pending' ? 'bg-amber-500 text-white shadow-xl shadow-amber-100' : 'bg-white text-slate-400 border-slate-100'" class="px-8 py-3 rounded-2xl font-black text-xs transition-all border shrink-0">تحت المراجعة</button>
    </div>

    <div class="space-y-6">
        @foreach($bookings as $booking)
        <div x-show="tab === 'all' || tab === '{{ $booking->status }}'"
             class="bg-white rounded-[2.5rem] p-6 border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col md:flex-row gap-8 items-center animate-fade-in">

            <div class="w-full md:w-48 h-32 rounded-3xl overflow-hidden shrink-0 shadow-inner">
                <img src="{{ $booking->image }}" class="w-full h-full object-cover grayscale-[0.2] hover:grayscale-0 transition-all">
            </div>

            <div class="flex-grow text-right space-y-2">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-xl font-black text-slate-800">{{ $booking->trip_title }}</h3>
                        <p class="text-xs text-slate-400 font-bold tracking-wide">{{ $booking->agency }}</p>
                    </div>
                    <div class="text-left">
                        @if($booking->status == 'confirmed')
                            <span class="px-4 py-1.5 bg-green-100 text-green-700 rounded-full text-[10px] font-black uppercase">✅ مؤكد</span>
                        @else
                            <span class="px-4 py-1.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-black uppercase">⏳ تحت المراجعة</span>
                        @endif
                    </div>
                </div>

                <div class="flex gap-6 pt-4 border-t border-slate-50">
                    <div>
                        <p class="text-[10px] text-slate-400 font-black uppercase mb-1">تاريخ السفر</p>
                        <p class="text-sm font-black text-slate-700">{{ $booking->date }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 font-black uppercase mb-1">المقاعد</p>
                        <p class="text-sm font-black text-blue-600 tracking-widest">
                            @foreach($booking->seats as $seat) #{{ $seat }} @endforeach
                        </p>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-auto flex flex-row md:flex-col gap-2 shrink-0">
                @if($booking->status == 'confirmed')
                    <a href="{{ route('client.success') }}" class="flex-1 md:w-32 py-3 bg-blue-600 text-white rounded-2xl font-black text-[10px] text-center hover:bg-blue-700 shadow-lg shadow-blue-100 transition-all">
                        📄 عرض التذكرة
                    </a>
                @else
                    <button disabled class="flex-1 md:w-32 py-3 bg-slate-100 text-slate-400 rounded-2xl font-black text-[10px] cursor-not-allowed">
                        ⚙️ مراجعة الدفع
                    </button>
                @endif
                <button class="px-4 py-3 bg-slate-50 text-slate-600 rounded-2xl font-black text-[10px] hover:bg-slate-100 transition-all">
                    تفاصيل
                </button>
            </div>
        </div>
        @endforeach

        @if(count($bookings) == 0)
            <div class="text-center py-20 bg-slate-50 rounded-[3rem] border-2 border-dashed border-slate-200">
                <span class="text-6xl block mb-4">🎫</span>
                <h3 class="font-black text-slate-800">لا توجد حجوزات حالياً</h3>
                <p class="text-slate-400 text-sm mt-2">ابدأ بحجز رحلتك الأولى الآن!</p>
            </div>
        @endif
    </div>
</div>

<style>
@keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fade-in 0.4s ease-out; }
</style>
@endsection
