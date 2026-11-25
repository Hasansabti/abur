@extends('layouts.client')

@section('content')

    <div class="py-8 px-4 mx-auto max-w-7xl" dir="rtl">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800 md:text-3xl">شركاء النجاح (وكالات السفر)</h1>
                <p class="mt-2 text-sm text-slate-500">تواصل مباشرة مع أفضل وكالات السفر المعتمدة لتنسيق رحلاتك وحجوزاتك.</p>
            </div>

            <div class="flex gap-3 w-full md:w-auto">
                <div
                    class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm flex items-center gap-3 min-w-[160px]">
                    <div class="p-2 bg-indigo-50 text-indigo-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-bold">الوكالات المعتمدة</p>
                        <p class="text-lg font-black text-slate-700">{{ $agencies->where('status', 'active')->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        @if ($agencies->isEmpty())
            <div class="bg-white border border-dashed border-slate-200 rounded-[2rem] p-12 text-center shadow-sm">
                <div class="inline-flex p-4 bg-slate-50 text-slate-400 rounded-full mb-4">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                </div>
                <p class="text-slate-600 font-bold text-lg">لا يوجد وكالات سفر متاحة حالياً</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($agencies as $agency)
                    @if ($agency->status === 'active')
                        <div
                            class="bg-white border border-slate-100 rounded-[2rem] p-6 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group">

                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-3">

                                    @if (!empty($agency->iconA))
                                        <img src="{{ asset('storage/' . $agency->iconA) }}" alt="{{ $agency->name }}"
                                            class="w-12 h-12 rounded-2xl object-cover shadow-sm border border-slate-100 bg-slate-50">
                                    @else
                                        <!-- صورة أو شكل بديل في حال عدم توفر اللوجو -->
                                        <div
                                            class="w-12 h-12 rounded-2xl object-cover shadow-sm border border-slate-100 bg-slate-100 flex items-center justify-center text-slate-400 font-bold text-lg">
                                            {{ mb_substr($agency->name, 0, 1) }}
                                        </div>
                                    @endif

                                    <div>
                                        <h3 class="font-black text-slate-800 group-hover:text-indigo-600 transition-colors">
                                            {{ $agency->name }}
                                        </h3>

                                        {{-- تجمع شارات التوثيق والتقييم معاً --}}
                                        <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                            @if ($agency->status === 'active')
                                                <p
                                                    class="text-[10px] bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-lg font-bold flex items-center gap-1">
                                                    <span
                                                        class="w-1.5 h-1.5 bg-emerald-500 rounded-full inline-block animate-pulse"></span>
                                                    وكالة معتمدة
                                                </p>
                                            @else
                                                <p
                                                    class="text-[10px] bg-red-50 text-red-700 px-2 py-0.5 rounded-lg font-bold flex items-center gap-1">
                                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full inline-block"></span>
                                                    غير معتمدة
                                                </p>
                                            @endif

                                            @if ($agency->ratings_count > 0)
                                                <span
                                                    class="text-[10px] bg-amber-50 text-amber-700 px-2 py-0.5 rounded-lg font-black flex items-center gap-0.5">
                                                    ⭐ {{ $agency->average_rating }}
                                                    <span
                                                        class="text-slate-400 font-medium">({{ $agency->ratings_count }})</span>
                                                </span>
                                            @else
                                                <span
                                                    class="text-[10px] bg-slate-50 text-slate-400 px-2 py-0.5 rounded-lg font-bold italic">
                                                    لا توجد تقييمات
                                                </span>
                                            @endif
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <hr class="border-slate-50 my-4" />

                            <div class="space-y-3">
                                <a href="mailto:{{ $agency->email }}"
                                    class="flex items-center gap-3 text-slate-600 hover:text-indigo-600 transition-colors">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                    <span class="text-xs font-medium truncate max-w-[220px]"
                                        dir="ltr">{{ $agency->email }}</span>
                                </a>

                                <a href="tel:{{ $agency->phone }}"
                                    class="flex items-center gap-3 text-slate-600 hover:text-indigo-600 transition-colors">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                        </path>
                                    </svg>
                                    <span class="text-xs font-medium"
                                        dir="ltr">{{ $agency->phone ?? 'غير متوفر' }}</span>
                                </a>
                            </div>

                            <div class="grid grid-cols-2 gap-3 mt-6">
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $agency->phone) }}" target="_blank"
                                    class="py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl text-center flex items-center justify-center gap-1.5 transition-all active:scale-[0.97] shadow-sm shadow-emerald-100">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.513 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.717-1.454L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.37 9.864-9.799.002-2.63-1.023-5.101-2.885-6.965C16.528 1.977 14.07 1.01 11.45 1.01c-5.44 0-9.866 4.372-9.87 9.802 0 1.698.459 3.35 1.33 4.816l-.993 3.63 3.733-.964z" />
                                    </svg>
                                    راسلني واتساب
                                </a>

                                <a href="tel:{{ $agency->phone }}"
                                    class="py-3 px-4 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl text-center flex items-center justify-center gap-1.5 transition-all active:scale-[0.97]">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                        </path>
                                    </svg>
                                    اتصال مباشر
                                </a>
                            </div>

                        </div>
                    @endif
                @endforeach

            </div>
        @endif
    </div>
@endsection
