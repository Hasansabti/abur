@extends('layouts.client')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl" x-data="{ from: '', to: '', date: '', transportType: 'bus' }">

    {{-- 1. قسم الهيدر --}}
    <div class="bg-gray-950 rounded-[3rem] p-12 mb-16 text-white shadow-3xl relative overflow-hidden ring-4 ring-gray-900/50 animate-fade-in">
        <div class="absolute -right-20 -top-20 w-80 h-80 bg-blue-600 rounded-full blur-[100px] opacity-30"></div>
        <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-purple-600 rounded-full blur-[100px] opacity-30"></div>

        <div class="relative z-10">
            <h1 class="text-5xl font-black mb-6 tracking-tighter leading-tight">
                ابدأ رحلتك <br> بضغطة زر.
            </h1>

            {{-- تبديل وسيلة النقل --}}
            <div class="flex gap-2 mb-8 bg-gray-900/80 p-1.5 rounded-2xl w-fit border border-gray-800">
                <button @click="transportType = 'bus'"
                        :class="transportType === 'bus' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-400 hover:text-white'"
                        class="px-6 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2">
                    <span>🚌</span> بري (باص)
                </button>
                <button @click="transportType = 'flight'"
                        :class="transportType === 'flight' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-400 hover:text-white'"
                        class="px-6 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2">
                    <span>✈️</span> طيران
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-gray-900/50 p-5 rounded-3xl border border-gray-800 backdrop-blur-sm">
                <div class="bg-gray-800 rounded-2xl p-4 border border-gray-700">
                    <label class="block text-[10px] text-gray-500 font-bold uppercase mb-1.5 tracking-widest">مدينة المغادرة</label>
                    <select x-model="from" class="w-full bg-transparent border-none text-white font-bold p-0 focus:ring-0 outline-none text-sm appearance-none">
                        <option value="" class="bg-gray-900">اختر المدينة</option>
                        <option class="bg-gray-900">الرياض</option>
                        <option class="bg-gray-900">جدة</option>
                    </select>
                </div>
                <div class="bg-gray-800 rounded-2xl p-4 border border-gray-700">
                    <label class="block text-[10px] text-gray-500 font-bold uppercase mb-1.5 tracking-widest">وجهة الوصول</label>
                    <select x-model="to" class="w-full bg-transparent border-none text-white font-bold p-0 focus:ring-0 outline-none text-sm appearance-none">
                        <option value="" class="bg-gray-900">اختر المدينة</option>
                        <option class="bg-gray-900">مكة المكرمة</option>
                        <option class="bg-gray-900">المدينة المنورة</option>
                    </select>
                </div>
                <div class="bg-gray-800 rounded-2xl p-4 border border-gray-700">
                    <label class="block text-[10px] text-gray-500 font-bold uppercase mb-1.5 tracking-widest">تاريخ السفر</label>
                    <input type="date" x-model="date" class="w-full bg-transparent border-none text-white font-bold p-0 focus:ring-0 outline-none text-sm cursor-pointer">
                </div>
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-black rounded-2xl transition-all shadow-xl shadow-blue-600/20 active:scale-95 disabled:bg-gray-700 disabled:opacity-50"
                        :disabled="!from || !to || !date">
                    ابحث الآن 🔍
                </button>
            </div>
        </div>
    </div>

    {{-- 2. قائمة الرحلات المتاحة --}}
    <div class="flex items-center justify-between mb-10">
        <h2 class="text-3xl font-black text-gray-950 tracking-tight">الرحلات المتاحة</h2>
        <div class="flex gap-2">
            <span class="text-xs font-bold text-gray-400 bg-gray-50 px-4 py-2 rounded-full border">
                يتم عرض رحلات الـ <span class="text-blue-600" x-text="transportType === 'bus' ? 'باص' : 'طيران'"></span>
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        @foreach($availableTrips as $trip)
        <div x-show="transportType === '{{ $trip->transport_type }}'"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             class="bg-white rounded-[2.5rem] overflow-hidden border border-gray-100 shadow-sm hover:shadow-2xl hover:border-blue-100 transition-all duration-300 group flex flex-col">

            {{-- قسم الصورة والعنوان العائم --}}
            <div class="relative h-72 overflow-hidden">
                <img src="{{ $trip->image }}" alt="{{ $trip->route }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">

                <div class="absolute inset-0 bg-gradient-to-t from-gray-950/90 via-gray-900/20 to-transparent"></div>

                {{-- اسم الرحلة فوق الصورة --}}
                <div class="absolute bottom-14 left-8 right-8">
                    <h3 class="text-3xl font-black text-white drop-shadow-2xl tracking-tight leading-tight">
                        {{ $trip->name }}
                    </h3>
                </div>

                {{-- شارة نوع النقل --}}
                <div class="absolute top-6 left-6 bg-gray-950 text-white px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest flex items-center gap-2 border border-white/10 shadow-2xl">
                    @if($trip->transport_type == 'bus') 🚌 بري @else ✈️ طيران @endif
                </div>

                {{-- شارة ذهاب/عودة --}}
                <div class="absolute top-6 right-6 bg-white/95 backdrop-blur-md px-4 py-2 rounded-full text-[11px] font-black text-blue-700 shadow-lg">
                    {!! $trip->type == 'one_way' ? '📍 ذهاب فقط' : '🔄 ذهاب وعودة' !!}
                </div>

                {{-- التقييم --}}
                <div class="absolute bottom-6 left-8 bg-blue-600 px-4 py-1.5 rounded-full text-white text-xs font-black shadow-lg ring-4 ring-white/10">
                    ⭐ {{ $trip->rating }}.0
                </div>
            </div>

            {{-- قسم التفاصيل السفلية --}}
            <div class="p-8 flex-grow flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">{{ $trip->agency }}</p>
                            <h1 class="text-xl text-blue-700 font-bold ">{{ $trip->route }}</h1>
                            <p class="text-sm text-gray-500 font-bold italic">{{ $trip->vehicle_model }}</p>
                            <div class="flex items-center gap-4 mt-3">
                                <span class="text-xs text-blue-600 font-black flex items-center gap-1.5 bg-blue-50 px-3 py-1 rounded-lg">
                                    <span>📅</span> {{ $trip->date }}
                                </span>
                            </div>
                        </div>
                        <div class="text-left bg-slate-50 px-5 py-3 rounded-2xl border border-slate-100 group-hover:bg-blue-50 group-hover:border-blue-100 transition-colors">
                            <p class="text-[10px] text-gray-400 font-bold mb-1">يبدأ من</p>
                            <p class="text-3xl font-black text-gray-900 group-hover:text-blue-600">{{ $trip->price }} <small class="text-[10px] font-bold">ر.س</small></p>
                        </div>
                    </div>

                    <div class="flex gap-2 mb-8 flex-wrap">
                        @foreach($trip->amenities as $amenity)
                            <span class="text-[9px] bg-slate-50 text-slate-500 px-3 py-1.5 rounded-lg font-black border border-slate-100 uppercase tracking-tighter">
                                {{ $amenity }}
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- زر الحجز التفاعلي --}}
                <a href="{{ route('client.select_seat', $trip->id) }}"
                   class="block w-full text-center py-5 bg-gray-950 hover:bg-blue-600 text-white rounded-2xl font-black text-lg transition-all shadow-xl shadow-gray-200 active:scale-95 group-hover:shadow-blue-600/30 group-hover:-translate-y-1">
                   @if($trip->transport_type == 'bus') اختيار المقعد @else استكمال الحجز @endif
                </a>
            </div>
        </div>
        @endforeach
    </div>
</div>

<style>
    .animate-fade-in {
        animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #f8fafc; }
    ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>
@endsection
