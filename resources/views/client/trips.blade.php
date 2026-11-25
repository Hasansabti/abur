@extends('layouts.client')

@section('content')
    {{-- الحاوية الرئيسية --}}
    <div class="w-full min-h-screen py-8 main-page-bg" x-data="{
        viewType: 'grid',
        from: '{{ request('from_city', '') }}',
        to: '{{ request('to_city', '') }}',
        date: '{{ request('date', '') }}',
        transportType: '{{ request('type', $transport_type ?? 'bus') }}',
        triptype: '{{ request('triptype', '') }}',
        agency_id: '{{ request('agency_id', '') }}',
        sort_by: '{{ request('sort_by', 'latest') }}',
    }">

        <div class="container mx-auto px-4 max-w-7xl">

            <form action="{{ url()->current() }}" method="GET"
                class="bg-white/95 backdrop-blur-md z-30 rounded-[2.5rem] p-6 md:p-10 mb-8 text-slate-800 border border-slate-200/80 shadow-xl relative overflow-visible animate-fade-in">

                <input type="hidden" name="type" :value="transportType">

                <div class="absolute inset-0 rounded-[2.5rem] overflow-hidden pointer-events-none z-0">
                    <div class="absolute -right-20 -top-20 w-80 h-80 bg-blue-100 rounded-full blur-[100px] opacity-50"></div>
                    <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-purple-100 rounded-full blur-[100px] opacity-50">
                    </div>
                </div>

                <div class="relative z-10 overflow-visible">

                    {{-- 🟢 تبديل وسيلة النقل + قائمة الترتيب --}}
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                        <div
                            class="flex flex-wrap gap-2 bg-slate-50 p-1.5 rounded-2xl w-fit shadow-sm border border-slate-200/50">
                            <button type="button" @click="transportType = 'bus'; $nextTick(() => $el.form.submit())"
                                :class="transportType === 'bus' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' :
                                    'text-slate-500 hover:text-slate-800 hover:bg-white'"
                                class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                                <span>🚌</span> بري (باص)
                            </button>
                            <button type="button" @click="transportType = 'flight'; $nextTick(() => $el.form.submit())"
                                :class="transportType === 'flight' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' :
                                    'text-slate-500 hover:text-slate-800 hover:bg-white'"
                                class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                                <span>✈️</span> طيران
                            </button>
                        </div>

                        {{-- 🟢 قائمة الترتيب --}}
                        <div
                            class="flex items-center gap-2 bg-white/80 p-2 rounded-2xl shadow-sm border border-slate-200/50 w-full sm:w-auto">
                            <label class="text-xs font-bold text-slate-500 whitespace-nowrap pl-2">ترتيب حسب:</label>
                            <select name="sort_by" x-model="sort_by" @change="$nextTick(() => $el.form.submit())"
                                class="bg-transparent border-none text-sm font-bold text-slate-800 outline-none focus:ring-0 cursor-pointer w-full sm:w-auto">
                                <option value="latest">الرحلات الأحدث</option>
                                <option value="top_rated">الأعلى تقييماً ⭐</option>
                            </select>
                        </div>
                    </div>

                    {{-- بار تخصيص الرحلة والبحث --}}
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4 p-2 rounded-3xl mb-6 overflow-visible relative z-20">
                        @php
                            $saudiCities = [
                                'الرياض',
                                'جدة',
                                'مكة المكرمة',
                                'المدينة المنورة',
                                'الدمام',
                                'الخبر',
                                'الظهران',
                                'الهفوف',
                                'الطائف',
                                'تبوك',
                                'أبها',
                                'خميس مشيط',
                                'جازان',
                                'نجران',
                                'حائل',
                                'بريدة',
                                'عنيزة',
                                'سكاكا',
                                'عرعر',
                                'ينبع',
                                'الجبيل',
                                'القطيف',
                                'بيشة',
                                'الباحة',
                                'القريات',
                                'رابغ',
                                'الخرج',
                                'الدوادمي',
                                'المجمعة',
                            ];
                            $agenciesList = $agencies ?? [];
                        @endphp

                        {{-- اختيار وكالة السفر --}}
                        <div class="flex flex-col gap-2 relative z-10">
                            <label class="text-xs text-slate-500 font-bold uppercase tracking-wider pr-1">وكالة
                                السفر</label>
                            <div class="bg-slate-50 rounded-2xl p-3.5 shadow-sm border border-slate-200">
                                <select name="agency_id" x-model="agency_id"
                                    class="w-full bg-transparent border-none text-slate-800 font-bold p-0 focus:ring-0 outline-none text-sm cursor-pointer">
                                    <option value="">كل الوكالات</option>
                                    @foreach ($agenciesList as $agency)
                                        <option value="{{ $agency->id }}">{{ $agency->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- من --}}
                        <div x-data="{ open: false, search: '', selected: '{{ request('from_city', '') }}', cities: @js($saudiCities) }" @click.outside="open = false"
                            class="flex flex-col gap-2 transition-all duration-150"
                            :class="open ? 'relative z-40' : 'relative z-10'">
                            <label class="text-xs text-slate-500 font-bold uppercase tracking-wider pr-1">من</label>
                            <div class="relative">
                                <input type="hidden" name="from_city" :value="selected">
                                <button type="button" @click="open = !open"
                                    class="w-full bg-slate-50 rounded-2xl p-3.5 shadow-sm border border-slate-200 text-right flex justify-between items-center text-sm font-bold transition-all duration-200 hover:border-slate-300 focus:outline-none focus:border-blue-500">
                                    <span x-text="selected ? selected : 'كل المدن'"
                                        :class="!selected ? 'text-slate-400' : 'text-slate-800'"></span>
                                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200"
                                        :class="open ? 'rotate-180 text-blue-500' : ''" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="open" x-transition
                                    class="absolute z-50 mt-2 w-full bg-white rounded-2xl shadow-xl border border-slate-100 p-2 max-h-64 overflow-y-auto top-full right-0"
                                    style="display: none;">
                                    <div class="sticky top-0 bg-white pb-2 z-10">
                                        <input type="text" x-model="search" placeholder="ابحث عن مدينة..."
                                            class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                    </div>
                                    <div class="space-y-0.5">
                                        <button type="button" @click="selected = ''; open = false; search = ''"
                                            class="w-full text-right px-3 py-2 text-xs font-bold rounded-xl hover:bg-slate-50 text-slate-600">كل
                                            المدن</button>
                                        <template x-for="city in cities.filter(c => c.includes(search))"
                                            :key="city">
                                            <button type="button" @click="selected = city; open = false; search = ''"
                                                class="w-full text-right px-3 py-2 text-xs font-bold rounded-xl hover:bg-slate-50 text-slate-600"
                                                :class="selected === city ? 'bg-blue-50 text-blue-600' : 'text-slate-600'"
                                                x-text="city"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- إلى --}}
                        <div x-data="{ open: false, search: '', selected: '{{ request('to_city', '') }}', cities: @js($saudiCities) }" @click.outside="open = false"
                            class="flex flex-col gap-2 transition-all duration-150"
                            :class="open ? 'relative z-40' : 'relative z-10'">
                            <label class="text-xs text-slate-500 font-bold uppercase tracking-wider pr-1">إلى</label>
                            <div class="relative">
                                <input type="hidden" name="to_city" :value="selected">
                                <button type="button" @click="open = !open"
                                    class="w-full bg-slate-50 rounded-2xl p-3.5 shadow-sm border border-slate-200 text-right flex justify-between items-center text-sm font-bold transition-all hover:border-slate-300 focus:outline-none focus:border-blue-500">
                                    <span x-text="selected ? selected : 'كل الوجهات'"
                                        :class="!selected ? 'text-slate-400' : 'text-slate-800'"></span>
                                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200"
                                        :class="open ? 'rotate-180 text-blue-500' : ''" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="open" x-transition
                                    class="absolute z-50 mt-2 w-full bg-white rounded-2xl shadow-xl border border-slate-100 p-2 max-h-64 overflow-y-auto top-full right-0"
                                    style="display: none;">
                                    <div class="sticky top-0 bg-white pb-2 z-10">
                                        <input type="text" x-model="search" placeholder="ابحث عن وجهة..."
                                            class="w-full bg-slate-50 border border-slate-200/80 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10">
                                    </div>
                                    <div class="space-y-0.5">
                                        <button type="button" @click="selected = ''; open = false; search = ''"
                                            class="w-full text-right px-3 py-2 text-xs font-bold rounded-xl hover:bg-slate-50 text-slate-600">كل
                                            الوجهات</button>
                                        <template x-for="city in cities.filter(c => c.includes(search))"
                                            :key="city">
                                            <button type="button" @click="selected = city; open = false; search = ''"
                                                class="w-full text-right px-3 py-2 text-xs font-bold rounded-xl hover:bg-slate-50 text-slate-600"
                                                :class="selected === city ? 'bg-blue-50 text-blue-600' : 'text-slate-600'"
                                                x-text="city"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 relative z-10">
                            <label class="text-xs text-slate-500 font-bold uppercase tracking-wider pr-1">نوع
                                الرحلة</label>
                            <div class="bg-slate-50 rounded-2xl p-3.5 shadow-sm border border-slate-200">
                                <select name="triptype" x-model="triptype"
                                    class="w-full bg-transparent border-none text-slate-800 font-bold p-0 focus:ring-0 outline-none text-sm cursor-pointer">
                                    <option value="">كل الأنواع</option>
                                    <option value="one_way">ذهاب فقط 🚌</option>
                                    <option value="round_trip">ذهاب وعودة 🔄</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 relative z-10">
                            <label class="text-xs text-slate-500 font-bold uppercase tracking-wider pr-1">التاريخ</label>
                            <div class="bg-slate-50 rounded-2xl p-3.5 shadow-sm border border-slate-200">
                                <input type="date" name="date" value="{{ request('date') }}"
                                    class="w-full bg-transparent border-none text-slate-800 font-bold p-0 focus:ring-0 outline-none text-sm cursor-pointer">
                            </div>
                        </div>

                        <div class="flex flex-col justify-end relative z-10">
                            <button type="submit"
                                class="w-full h-[52px] bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-2xl transition-all duration-200 shadow-md shadow-blue-600/10 active:scale-[0.98]">
                                ابحث الآن 🔍
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            {{-- المربع التفاعلي (السلايدر المستعرض للوجهات) --}}
            <div x-data="{
                activeSlide: 0,
                slides: [
                    { src: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80', title: 'اكتشف وجهاتنا الساحلية', desc: 'استمتع بأجمل الشواطئ والاسترخاء أمام البحر' },
                    { src: 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=800&q=80', title: 'خطط لرحلتك القادمة', desc: 'كل ما تحتاجه لتبدأ مغامرتك معنا اليوم' },
                    { src: 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=800&q=80', title: 'عروض حصرية ومميزة', desc: 'أفضل الأسعار لرحلات لا تُنسى' }
                ],
                next() { this.activeSlide = (this.activeSlide + 1) % this.slides.length },
                prev() { this.activeSlide = (this.activeSlide - 1 + this.slides.length) % this.slides.length }
            }" x-init="setInterval(() => next(), 5000)"
                class="relative w-full max-w-5xl mx-auto h-[250px] md:h-[380px] rounded-[2rem] md:rounded-[2.5rem] overflow-hidden shadow-xl bg-slate-100 group mb-12 md:mb-20">
                <div class="relative w-full h-full flex transition-transform duration-700 ease-in-out"
                    :style="`transform: translateX(${activeSlide * 100}%)`" dir="rtl">
                    <template x-for="(slide, index) in slides" :key="index">
                        <div class="w-full h-full flex-shrink-0 relative">
                            <img :src="slide.src" class="w-full h-full object-cover">
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/20 to-transparent flex flex-col justify-end p-6 md:p-10 text-right">
                                <h2 class="text-white text-xl md:text-3xl font-black mb-2" x-text="slide.title"></h2>
                                <p class="text-slate-100 text-xs md:text-base font-medium opacity-90" x-text="slide.desc">
                                </p>
                            </div>
                        </div>
                    </template>
                </div>
                <button @click="prev()"
                    class="absolute right-4 top-1/2 -translate-y-1/2 w-9 h-9 md:w-11 md:h-11 bg-white/80 hover:bg-blue-600 text-slate-700 hover:text-white rounded-full flex items-center justify-center transition-all opacity-0 group-hover:opacity-100 backdrop-blur-md z-10 border border-slate-200 shadow-sm">➔</button>
                <button @click="next()"
                    class="absolute left-4 top-1/2 -translate-y-1/2 w-9 h-9 md:w-11 md:h-11 bg-white/80 hover:bg-blue-600 text-slate-700 hover:text-white rounded-full flex items-center justify-center transition-all opacity-0 group-hover:opacity-100 backdrop-blur-md z-10 border border-slate-200 shadow-sm">⬅</button>
            </div>

            @php
                $conditionalTrips = $availableTrips->filter(function ($trip) {
                    return !in_array($trip->status, ['pending', 'rejected', 'draft', 'published']) &&
                        $trip->is_conditional;
                });

                $standardTrips = $availableTrips->filter(function ($trip) {
                    return !in_array($trip->status, ['pending', 'rejected', 'draft', 'published']) &&
                        !$trip->is_conditional;
                });
            @endphp

            @if ($availableTrips->isEmpty() || ($conditionalTrips->isEmpty() && $standardTrips->isEmpty()))
                <div class="text-center py-12 bg-white rounded-3xl border border-slate-200 shadow-sm">
                    <p class="text-slate-500 font-bold">لا توجد رحلات متاحة حالياً تطابق خيارات البحث.</p>
                </div>
            @else
                {{-- 🚨 1. قسم الرحلات المشروطة (تجميع المسافرين) 🚨 --}}
                @if ($conditionalTrips->isNotEmpty())
                    <div class="mb-16">
                        <div
                            class="flex items-center gap-3 mb-6 bg-gradient-to-r from-amber-500/10 via-orange-500/5 to-transparent p-4 rounded-3xl border-r-4 border-amber-500">
                            <span class="text-3xl">⏳</span>
                            <div>
                                <h2 class="text-xl md:text-3xl font-black text-slate-800 tracking-tight">رحلات تجميع
                                    المسافرين (رحلات مشروطة)</h2>
                                <p class="text-xs md:text-sm text-amber-700 font-bold mt-0.5">رحلات تنطلق فور اكتمال الحد
                                    الأدنى الموضح من المقاعد قبل مهلة التأكيد</p>
                            </div>
                        </div>

                        <div class="grid gap-4 md:gap-8 transition-all duration-300"
                            :class="viewType === 'grid' ? 'grid-cols-1 md:grid-cols-2' : 'grid-cols-1 md:grid-cols-2'">
                            @foreach ($conditionalTrips as $trip)
                                @php
                                    $bookedSeats = max(0, ($trip->total_seats ?? 0) - ($trip->available_seats ?? 0));
                                    $minRequired = $trip->min_seats_required ?? 1;
                                    $progressPercent = min(100, round(($bookedSeats / $minRequired) * 100));
                                    $detailsUrl = auth()->check()
                                        ? route('client.select_seat', $trip->id)
                                        : route('client.auth.login');
                                @endphp

                                <div @click="window.location.href = '{{ $detailsUrl }}'"
                                    x-transition:enter="transition ease-out duration-300"
                                    class="cursor-pointer relative bg-gradient-to-br from-amber-50 to-orange-50/60 border-2 border-dashed border-amber-400 hover:border-solid hover:border-orange-500 shadow-md hover:shadow-2xl hover:shadow-orange-500/20 transform hover:-translate-y-1 transition-all duration-500 group flex flex-col overflow-hidden"
                                    :class="viewType === 'grid' ? 'rounded-[1.5rem] md:rounded-[2.5rem]' : 'rounded-[2rem]'">

                                    <div
                                        class="absolute top-4 right-4 z-20 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-black text-[10px] md:text-xs px-3 py-1.5 rounded-full shadow-lg flex items-center gap-1.5 border border-amber-400/50">
                                        <span class="animate-pulse">🤝</span>
                                        <span>رحلة مشروطة</span>
                                        <span
                                            class="bg-white/20 backdrop-blur-sm text-white px-2 py-0.5 rounded-full text-[10px] md:text-[11px] font-bold dir-ltr shadow-inner border border-white/20">
                                            {{ $bookedSeats }}/{{ $minRequired }}
                                        </span>
                                    </div>

                                    <div class="relative overflow-hidden transition-all p-2 md:p-3 pb-0"
                                        :class="viewType === 'grid' ? 'h-40 sm:h-52 md:h-64' : 'h-48 md:h-64'">

                                        <div
                                            class="relative w-full h-full rounded-[1.2rem] md:rounded-[1.8rem] overflow-hidden shadow-inner border border-amber-200/50">
                                            <div
                                                class="absolute top-3 left-3 z-10 flex items-center gap-2 bg-white/90 backdrop-blur-md p-1.5 rounded-xl shadow-sm border border-white/50">
                                                @if (!empty($trip->agency_logo))
                                                    <img src="{{ $trip->agency_logo }}"
                                                        class="w-5 h-5 md:w-7 md:h-7 rounded-full object-cover border border-slate-100 shadow-sm shrink-0">
                                                @elseif (!empty($trip->agency?->iconA))
                                                    <img src="{{ str_starts_with($trip->agency->iconA, 'http') ? $trip->agency->iconA : asset(str_starts_with($trip->agency->iconA, 'storage/') ? $trip->agency->iconA : 'storage/' . $trip->agency->iconA) }}"
                                                        class="w-5 h-5 md:w-7 md:h-7 rounded-full object-cover border border-slate-100 shadow-sm shrink-0">
                                                @else
                                                    <div
                                                        class="w-5 h-5 md:w-7 md:h-7 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200 shrink-0">
                                                        <span
                                                            class="text-[9px] md:text-xs text-slate-400 font-bold">🏢</span>
                                                    </div>
                                                @endif
                                                <p
                                                    class="text-[8px] md:text-[10px] text-slate-700 font-black uppercase tracking-wide truncate max-w-[80px] md:max-w-[120px]">
                                                    {{ $trip->agency_name ?? 'وكالة سفر' }}
                                                </p>
                                            </div>

                                            <img src="{{ $trip->cover_image }}" alt="{{ $trip->title }}"
                                                class="w-full h-full object-cover group-hover:scale-110 group-hover:rotate-1 transition-all duration-700">

                                            <div
                                                class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/30 to-transparent pointer-events-none">
                                            </div>

                                            <div
                                                class="absolute bottom-3 right-3 md:bottom-5 md:right-5 pointer-events-none">
                                                <h3 class="font-black text-white drop-shadow-md"
                                                    :class="viewType === 'grid' ? 'text-base md:text-2xl' :
                                                        'text-lg md:text-2xl'">
                                                    {{ $trip->title }}
                                                </h3>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-4 md:px-6 pt-4 pb-1">
                                        <div class="flex justify-between items-end mb-1.5">
                                            <span
                                                class="text-[10px] md:text-xs font-extrabold text-amber-800 bg-amber-200/50 px-2 py-0.5 rounded-md border border-amber-300/50">اكتمل:
                                                {{ $progressPercent }}%</span>
                                            @if ($minRequired - $bookedSeats > 0)
                                                <span
                                                    class="text-[10px] md:text-xs font-bold text-orange-600 animate-pulse">متبقي
                                                    {{ $minRequired - $bookedSeats }} مقاعد للتأكيد</span>
                                            @else
                                                <span class="text-[10px] md:text-xs font-bold text-emerald-600">اكتمل الحد
                                                    الأدنى! 🎉</span>
                                            @endif
                                        </div>
                                        <div
                                            class="w-full bg-amber-200/60 rounded-full h-2 md:h-2.5 overflow-hidden border border-amber-300/50 shadow-inner">
                                            <div class="bg-gradient-to-r from-amber-400 to-orange-500 h-full rounded-full relative transition-all duration-1000 ease-out"
                                                style="width: {{ $progressPercent }}%">
                                                <div
                                                    class="absolute inset-0 bg-white/30 w-full animate-[shimmer_2s_infinite]">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex-grow flex flex-col justify-between"
                                        :class="viewType === 'grid' ? 'p-4 md:p-6' : 'p-5 md:p-6'">
                                        <div
                                            class="flex flex-col md:flex-row justify-between items-start mb-2 md:mb-4 gap-4 w-full">
                                            <div class="flex-grow space-y-1 md:space-y-2 w-full">
                                                <div class="flex items-center gap-2 mb-1">
                                                    @if (!empty($trip->agency_logo))
                                                        <img src="{{ $trip->agency_logo }}"
                                                            class="w-6 h-6 md:w-8 md:h-8 rounded-full object-cover border border-amber-200 shadow-sm shrink-0">
                                                    @elseif (!empty($trip->agency?->iconA))
                                                        <img src="{{ str_starts_with($trip->agency->iconA, 'http') ? $trip->agency->iconA : asset(str_starts_with($trip->agency->iconA, 'storage/') ? $trip->agency->iconA : 'storage/' . $trip->agency->iconA) }}"
                                                            class="w-6 h-6 md:w-8 md:h-8 rounded-full object-cover border border-amber-200 shadow-sm shrink-0">
                                                    @else
                                                        <div
                                                            class="w-6 h-6 md:w-8 md:h-8 rounded-full bg-amber-100 flex items-center justify-center border border-amber-200 shrink-0">
                                                            <span
                                                                class="text-[10px] md:text-xs text-amber-500 font-bold">🏢</span>
                                                        </div>
                                                    @endif

                                                    <p
                                                        class="text-[9px] md:text-[11px] text-amber-700/80 font-bold uppercase tracking-wide">
                                                        {{ $trip->agency_name ?? ($trip->agency->name ?? 'وكالة سفر') }}
                                                    </p>
                                                </div>
                                                <h4 class="text-slate-800 font-extrabold"
                                                    :class="viewType === 'grid' ? 'text-xs md:text-xl' :
                                                        'text-base md:text-xl'">
                                                    {{ $trip->route_type }}
                                                </h4>
                                                <h4 class="text-slate-800 font-extrabold"
                                                    :class="viewType === 'grid' ? 'text-xs md:text-xl' :
                                                        'text-base md:text-xl'">
                                                    {{ $trip->route }}
                                                </h4>
                                            </div>

                                            <div
                                                class="bg-white/80 backdrop-blur-sm px-3 py-2 md:px-5 md:py-3 rounded-xl md:rounded-2xl border border-amber-200/80 w-full md:w-auto flex md:flex-col justify-between items-center md:items-end shrink-0 shadow-sm">
                                                <p class="text-[9px] md:text-[11px] text-amber-800 font-bold md:mb-0.5">
                                                    تبدأ من</p>
                                                <p class="font-black text-amber-900"
                                                    :class="viewType === 'grid' ? 'text-sm md:text-2xl' :
                                                        'text-base md:text-2xl'">
                                                    {{ $trip->prices }}
                                                    <small
                                                        class="text-[9px] md:text-xl font-bold text-amber-700">ر.س</small>
                                                </p>
                                            </div>
                                        </div>

                                        <a href="{{ $detailsUrl }}" @click.stop
                                            class="block w-full text-center bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white font-bold transition-all transform hover:scale-[1.01] active:scale-[0.97] shadow-lg shadow-orange-500/30 flex justify-center items-center gap-2"
                                            :class="viewType === 'grid' ? 'py-2.5 rounded-xl text-xs md:text-base' :
                                                'py-3 rounded-xl text-sm md:text-base'">
                                            <span>أكمل العدد واحجز مقعدك</span>
                                            <span
                                                class="text-lg md:text-xl group-hover:translate-x-[-4px] transition-transform">🚀</span>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- 🚌 2. قسم الرحلات العامة المتاحة 🚌 --}}
                @if ($standardTrips->isNotEmpty())
                    <div>
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between mb-8 border-b border-slate-200 pb-4 gap-4">
                            <div>
                                <h2 class="text-xl md:text-3xl font-black text-slate-800 tracking-tight">الرحلات المتاحة
                                </h2>
                                <p class="text-xs text-slate-400 mt-1 font-bold md:hidden">
                                    يعرض رحلات الـ <span class="text-blue-600"
                                        x-text="transportType === 'bus' ? 'باص' : 'طيران'"></span>
                                </p>
                            </div>
                            <div class="flex items-center justify-between sm:justify-end gap-3">
                                <div
                                    class="flex md:hidden bg-slate-200/60 p-1 rounded-xl items-center border border-slate-300/30">
                                    <button @click="viewType = 'grid'"
                                        :class="viewType === 'grid' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500'"
                                        class="p-2 rounded-lg font-black text-xs transition-all flex items-center gap-1">📊
                                        مربعات صغيرة</button>
                                    <button @click="viewType = 'list'"
                                        :class="viewType === 'list' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500'"
                                        class="p-2 rounded-lg font-black text-xs transition-all flex items-center gap-1">📱
                                        بطاقة كاملة</button>
                                </div>
                                <span
                                    class="hidden md:inline-block text-xs font-bold text-slate-600 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-full border border-slate-200">
                                    يتم عرض رحلات الـ <span class="text-blue-600"
                                        x-text="transportType === 'bus' ? 'باص' : 'طيران'"></span>
                                </span>
                            </div>
                        </div>

                        <div class="grid mb-16 gap-4 md:gap-8 transition-all duration-300"
                            :class="viewType === 'grid' ? 'grid-cols-2 md:grid-cols-2' : 'grid-cols-1 md:grid-cols-2'">
                            @foreach ($standardTrips as $trip)
                                @php
                                    $detailsUrl = auth()->check()
                                        ? route('client.select_seat', $trip->id)
                                        : route('client.auth.login');
                                @endphp

                                <div @click="window.location.href = '{{ $detailsUrl }}'"
                                    x-transition:enter="transition ease-out duration-300"
                                    class="cursor-pointer bg-white border border-slate-200/80 shadow-sm hover:shadow-xl transition-all duration-300 group flex flex-col"
                                    :class="viewType === 'grid' ? 'rounded-[1.5rem] md:rounded-[2.5rem] overflow-hidden' :
                                        'rounded-[2rem] overflow-hidden'">

                                    <div class="relative overflow-hidden transition-all"
                                        :class="viewType === 'grid' ? 'h-32 sm:h-44 md:h-64' : 'h-48 md:h-64'">

                                        <div
                                            class="absolute top-3 left-3 z-10 flex items-center gap-2 bg-white/80 backdrop-blur-sm p-1.5 rounded-xl shadow-sm">
                                            @if (!empty($trip->agency_logo))
                                                <img src="{{ $trip->agency_logo }}"
                                                    class="w-5 h-5 md:w-7 md:h-7 rounded-full object-cover border border-slate-100 shadow-sm shrink-0">
                                            @elseif (!empty($trip->agency?->iconA))
                                                <img src="{{ str_starts_with($trip->agency->iconA, 'http') ? $trip->agency->iconA : asset(str_starts_with($trip->agency->iconA, 'storage/') ? $trip->agency->iconA : 'storage/' . $trip->agency->iconA) }}"
                                                    class="w-5 h-5 md:w-7 md:h-7 rounded-full object-cover border border-slate-100 shadow-sm shrink-0">
                                            @else
                                                <div
                                                    class="w-5 h-5 md:w-7 md:h-7 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200 shrink-0">
                                                    <span class="text-[9px] md:text-xs text-slate-400 font-bold">🏢</span>
                                                </div>
                                            @endif

                                            <p
                                                class="text-[8px] md:text-[10px] text-slate-700 font-black uppercase tracking-wide truncate max-w-[80px] md:max-w-[120px]">
                                                {{ $trip->agency_name ?? ($trip->agency->name ?? 'وكالة سفر') }}
                                            </p>
                                        </div>

                                        <img src="{{ $trip->cover_image }}" alt="{{ $trip->title }}"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">

                                        <div
                                            class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/20 to-transparent pointer-events-none">
                                        </div>

                                        <div class="absolute bottom-3 right-3 md:bottom-6 md:right-6 pointer-events-none">
                                            <h3 class="font-black text-white"
                                                :class="viewType === 'grid' ? 'text-sm md:text-2xl' : 'text-lg md:text-2xl'">
                                                {{ $trip->title }}
                                            </h3>
                                        </div>
                                    </div>

                                    <div class="flex-grow flex flex-col justify-between bg-white"
                                        :class="viewType === 'grid' ? 'p-3 md:p-8' : 'p-5 md:p-8'">
                                        <div
                                            class="flex flex-col md:flex-row justify-between items-start mb-4 md:mb-6 gap-4 w-full">
                                            <div class="flex-grow space-y-1 md:space-y-2">
                                                <div class="flex items-center gap-2 mb-1">
                                                    @if (!empty($trip->agency_logo))
                                                        <img src="{{ $trip->agency_logo }}"
                                                            class="w-6 h-6 md:w-8 md:h-8 rounded-full object-cover border border-slate-100 shadow-sm shrink-0">
                                                    @elseif (!empty($trip->agency?->iconA))
                                                        <img src="{{ str_starts_with($trip->agency->iconA, 'http') ? $trip->agency->iconA : asset(str_starts_with($trip->agency->iconA, 'storage/') ? $trip->agency->iconA : 'storage/' . $trip->agency->iconA) }}"
                                                            class="w-6 h-6 md:w-8 md:h-8 rounded-full object-cover border border-slate-100 shadow-sm shrink-0">
                                                    @else
                                                        <div
                                                            class="w-6 h-6 md:w-8 md:h-8 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200 shrink-0">
                                                            <span
                                                                class="text-[10px] md:text-xs text-slate-400 font-bold">🏢</span>
                                                        </div>
                                                    @endif

                                                    <p
                                                        class="text-[9px] md:text-[11px] text-slate-400 font-bold uppercase tracking-wide">
                                                        {{ $trip->agency_name ?? ($trip->agency->name ?? 'وكالة سفر') }}
                                                    </p>
                                                </div>
                                                <h4 class="text-slate-800 font-extrabold"
                                                    :class="viewType === 'grid' ? 'text-xs md:text-xl' :
                                                        'text-base md:text-xl'">
                                                    {{ $trip->route_type }}
                                                </h4>
                                                <h4 class="text-slate-800 font-extrabold"
                                                    :class="viewType === 'grid' ? 'text-xs md:text-xl' :
                                                        'text-base md:text-xl'">
                                                    {{ $trip->route }}
                                                </h4>
                                                <div class="flex items-center gap-1.5 mt-1 mb-2">
                                                    @if (isset($avgRating) && $avgRating)
                                                        <span class="text-amber-500 text-xs md:text-base">⭐</span>
                                                        <span
                                                            class="text-xs md:text-sm font-black text-slate-700">{{ $avgRating }}</span>
                                                        <span class="text-[10px] md:text-xs text-slate-400 font-bold">/
                                                            5</span>
                                                        <span
                                                            class="text-[9px] md:text-[11px] text-slate-400 font-medium">({{ $ratingsCount ?? 0 }}
                                                            {{ ($ratingsCount ?? 0) == 1 ? 'تقييم' : 'تقييمات' }})</span>
                                                    @elseif (isset($trip->rating) && $trip->rating > 0)
                                                        <span class="text-amber-500 text-xs md:text-base">⭐</span>
                                                        <span
                                                            class="text-xs md:text-sm font-black text-slate-700">{{ $trip->rating }}</span>
                                                        <span class="text-[10px] md:text-xs text-slate-400 font-bold">/
                                                            5</span>
                                                        <span
                                                            class="text-[9px] md:text-[11px] text-slate-400 font-medium">({{ $trip->ratings_count ?? 0 }}
                                                            {{ ($trip->ratings_count ?? 0) == 1 ? 'تقييم' : 'تقييمات' }})</span>
                                                    @else
                                                        <span class="text-slate-300 text-xs md:text-base">⭐</span>
                                                        <span class="text-[10px] md:text-xs font-bold text-slate-400">وكالة
                                                            جديدة</span>
                                                    @endif
                                                </div>
                                                <div class="flex flex-wrap gap-1.5 pt-1">
                                                    <span
                                                        class="inline-flex items-center gap-1 bg-slate-50 px-2 py-1 rounded-lg text-[10px] md:text-xs text-slate-600 font-bold border border-slate-200/60">📅
                                                        الذهاب: <span
                                                            class="truncate max-w-[80px] md:max-w-none">{{ $trip->date ?? \Carbon\Carbon::parse($trip->departure_at)->format('Y-m-d') }}</span></span>

                                                    <span
                                                        class="inline-flex items-center gap-1 bg-blue-50/60 px-2 py-1 rounded-lg text-[10px] md:text-xs text-blue-600 font-bold border border-blue-100">{{ $trip->transport_type === 'flight' ? '✈️' : '🚌' }}
                                                        <span>{{ in_array($trip->trip_type, ['round_trip', 'ذهاب وعودة']) ? 'ذهاب وعودة' : 'ذهاب فقط' }}</span></span>

                                                    @if (in_array($trip->trip_type, ['round_trip', 'ذهاب وعودة', 'ذهاب وإياب']) &&
                                                            (!empty($trip->return_date) || !empty($trip->return_at)))
                                                        <span
                                                            class="inline-flex items-center gap-1 bg-amber-50 px-2 py-1 rounded-lg text-[10px] md:text-xs text-amber-700 font-bold border border-amber-200/60 animate-fade-in">🔄
                                                            العودة: <span
                                                                class="truncate max-w-[80px] md:max-w-none">{{ $trip->return_date ?? \Carbon\Carbon::parse($trip->return_at)->format('Y-m-d') }}</span></span>
                                                    @endif

                                                    {{-- شارة عدد الأيام والليالي --}}
                                                    @if (!empty($trip->duration_days) || !empty($trip->duration_nights))
                                                        <span
                                                            class="inline-flex items-center gap-1 bg-indigo-50 px-2 py-1 rounded-lg text-[10px] md:text-xs text-indigo-700 font-bold border border-indigo-200/60 animate-fade-in">⏳
                                                            المدة: <span>{{ $trip->duration_days ?? 0 }} أيام /
                                                                {{ $trip->duration_days - 1 ?? 0 }} ليالٍ</span></span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div
                                                class="bg-slate-50 px-3 py-2 md:px-5 md:py-3 rounded-xl md:rounded-2xl border border-slate-200/60 w-full md:w-auto flex md:flex-col justify-between items-center md:items-end shrink-0">
                                                <p class="text-[9px] md:text-[11px] text-slate-400 font-bold md:mb-0.5">
                                                    تبدأ من</p>
                                                <p class="font-black text-slate-800"
                                                    :class="viewType === 'grid' ? 'text-sm md:text-2xl' :
                                                        'text-base md:text-2xl'">
                                                    {{ $trip->prices }}
                                                    <small
                                                        class="text-[9px] md:text-xl font-bold text-slate-500">ر.س</small>
                                                </p>
                                            </div>
                                        </div>

                                        <a href="{{ $detailsUrl }}" @click.stop
                                            class="block w-full text-center bg-slate-800 hover:bg-blue-600 text-white font-bold transition-all active:scale-[0.97]"
                                            :class="viewType === 'grid' ?
                                                'py-2 rounded-lg md:py-4 md:rounded-xl text-xs md:text-base' :
                                                'py-3 rounded-xl md:py-4 text-sm md:text-base'">
                                            عرض التفاصيل
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            @endif

        </div>

    </div>

    {{-- التنسيقات المخصصة --}}
    <style>
        .main-page-bg {
            background-image: linear-gradient(to bottom, rgba(248, 250, 252, 0.88), rgba(241, 245, 249, 0.95)), url("{{ asset('images/main.jpeg') }}") !important;
            background-size: cover !important;
            background-position: center center !important;
            background-repeat: no-repeat !important;
            background-attachment: fixed !important;
        }

        @media (min-width: 768px) {
            .main-page-bg {
                background-image: linear-gradient(to bottom, rgba(248, 250, 252, 0.85), rgba(241, 245, 249, 0.93)), url("{{ asset('images/main.jpeg') }}") !important;
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }
    </style>
@endsection