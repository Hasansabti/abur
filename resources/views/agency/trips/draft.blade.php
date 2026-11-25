@extends('layouts.agency')

@section('content')
    <div class="container mx-auto px-4 py-8 max-w-7xl">

        {{-- هيدر الصفحة --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-10">
            <div>
                <h1 class="text-2xl font-black text-slate-850 flex items-center gap-3">
                    <span class="w-10 h-10 bg-slate-100 rounded-2xl flex items-center justify-center text-lg">📁</span>
                    رحلات المسودة
                </h1>
                <p class="text-xs font-bold text-slate-400 mt-1">إليك الرحلات التي قمت بحفظها كمسودة ولم يتم نشرها للمسافرين
                    بعد.</p>
            </div>

        </div>

        {{-- شبكة عرض المسودات --}}
        {{-- شبكة عرض المسودات - أضفنا x-data هنا لحل مشكلة viewType --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" x-data="{ viewType: 'grid' }">

            @forelse($draft as $trip)
                <div
                    class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col justify-between overflow-hidden relative group">

                    {{-- شارة نوع الرحلة والوسيلة --}}
                    <div class="absolute top-4 right-4 z-10 flex gap-2">
                        <span
                            class="px-3 py-1.5 bg-slate-900/80 backdrop-blur-md text-white text-[10px] font-black rounded-xl uppercase tracking-wider">
                            {{ $trip->trip_type == 'one_way' ? 'ذهاب فقط' : 'ذهاب وعودة' }}
                        </span>
                        <span
                            class="px-3 py-1.5 bg-white/90 backdrop-blur-md text-slate-800 text-[10px] font-black rounded-xl shadow-sm">
                            {{ $trip->transport_type == 'bus' ? '🚌 بري' : '✈️ طيران' }}
                        </span>
                    </div>

                    <div>
                        {{-- صورة الغلاف للمسودة - تم إضافة h-48 كطول افتراضي لحمايتها من الاختفاء --}}
                        <div class="relative overflow-hidden transition-all h-48 md:h-64 w-full bg-slate-100"
                            :class="viewType === 'grid' ? 'h-32 sm:h-44 md:h-64' : 'h-48 md:h-64'">

                            <img src="{{ $trip->cover_image }}" alt="{{ $trip->title }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">

                            <div
                                class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/20 to-transparent pointer-events-none">
                            </div>
                        </div>

                        {{-- تفاصيل الرحلة --}}
                        <div class="p-6 space-y-4">
                            <div>
                                <h3
                                    class="text-base font-black text-slate-800 line-clamp-1 group-hover:text-blue-600 transition-colors">
                                    {{ $trip->title }}
                                </h3>
                                <span class="text-[9px] font-bold text-slate-400 block mt-1 uppercase">آخر تحديث:
                                    {{ $trip->updated_at->diffForHumans() }}</span>
                            </div>

                            {{-- مسار الرحلة (من -> إلى) --}}
                            <div
                                class="bg-slate-50/80 p-3 rounded-2xl flex items-center justify-between text-xs font-bold text-slate-700">
                                <div class="flex items-center gap-2">
                                    <span class="text-blue-500">📍</span>
                                    <span>{{ $trip->from_city }}</span>
                                </div>
                                <span class="text-slate-300 text-xs font-light">◀</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-500">🏁</span>
                                    <span>{{ $trip->to_city }}</span>
                                </div>
                            </div>

                            {{-- معلومات التوقيت والمقاعد --}}
                            <div class="grid grid-cols-2 gap-3 text-[11px] font-bold text-slate-500">
                                <div class="flex items-center gap-2">
                                    <span>📅</span>
                                    <span>{{ $trip->departure_at ? date('Y/m/d', strtotime($trip->departure_at)) : 'لم يحدد' }}</span>
                                </div>
                                <div class="flex items-center gap-2 justify-end">
                                    <span>💺</span>
                                    <span>{{ $trip->total_seats }} مقعد متاح</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- شريط التحكم بالمسودة في الأسفل --}}
                    <div class="p-6 pt-0 border-t border-slate-50 mt-4">
                        <div class="flex items-center justify-between pt-4 mb-4">
                            <div class="text-right">
                                <span class="block text-[9px] font-bold text-slate-400 uppercase">السعر الأساسي</span>
                                <span class="text-base font-black text-slate-800">
                                    {{ number_format($trip->price) }} <span
                                        class="text-[10px] font-bold text-slate-400">ريال</span>
                                </span>
                            </div>
                            <span
                                class="px-3 py-1 bg-amber-50 text-amber-700 border border-amber-100 rounded-lg text-[10px] font-black">
                                📝 مسودة
                            </span>
                        </div>

                        {{-- أزرار العمليات السريعة --}}
                        <div class="grid grid-cols-3 gap-2">
                            <a href="{{ route('agency.trips.edit', $trip->id) }}"
                                class="flex items-center justify-center gap-1 p-2.5 bg-slate-50 hover:bg-blue-50 text-slate-700 hover:text-blue-600 rounded-xl font-bold text-xs transition-all active:scale-95"
                                title="تعديل تفاصيل المسودة">
                                ✏️ <span class="hidden sm:inline">تعديل</span>
                            </a>
                        </div>
                    </div>

                </div>
            @empty
                {{-- حالة عدم وجود مسودات --}}
                <div
                    class="col-span-1 md:col-span-2 lg:col-span-3 bg-white border border-dashed border-slate-200 rounded-[2.5rem] p-16 text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-2xl mx-auto mb-4">
                        📂
                    </div>
                    <h3 class="text-base font-black text-slate-700">لا توجد أي مسودات حالياً</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">جميع الرحلات المجدولة مسبقاً تم نشرها للعامة، أو
                        لم تقم بحفظ أي مسودة بعد.</p>
                    <a href="{{ route('agency.trips.create') }}"
                        class="inline-block mt-6 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition-all">
                        + ابدأ بإنشاء رحلة الآن
                    </a>
                </div>
            @endforelse

        </div>
    </div>
@endsection
