@extends('layouts.client')

@section('content')
    <div class="container mx-auto px-4 py-8 max-w-3xl animate-fade-in">

        {{-- هيدر التحكم والطباعة --}}
        <div class="flex flex-col sm:flex-row justify-between items-center mb-8 gap-4 print:hidden">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <span>{{ $isFlight ? '✈️' : '🚌' }}</span>
                    {{ $isFlight ? 'تذاكر الطيران جاهزة' : 'تذاكر الرحلة جاهزة' }}
                </h1>
                <p class="text-xs text-slate-500 font-medium mt-1">
                    {{ $isFlight ? 'نرجو التواجد في المطار قبل موعد الإقلاع بساعتين' : 'نرجو التواجد في المحطة قبل الموعد بـ 30 دقيقة' }}
                </p>
            </div>
            <button onclick="window.print()"
                class="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white px-6 py-3 rounded-xl text-xs font-bold transition-all shadow-lg flex items-center justify-center gap-2">
                <span>🖨️</span> طباعة جميع التذاكر
            </button>
        </div>

        {{-- قائمة التذاكر (التذاكر المدمجة فقط) --}}
        @foreach ($booking->bookingSeats as $index => $seatRelation)
            <div
                class="bg-white rounded-3xl border border-slate-200/80 shadow-xl overflow-hidden mb-8 ticket-card print:shadow-none print:border-slate-300 print:mb-6 print:rounded-2xl">

                {{-- رأس التذكرة --}}
                <div
                    class="{{ $isFlight ? 'bg-slate-900' : 'bg-indigo-900' }} text-white px-6 py-5 relative overflow-hidden">
                    <div class="flex justify-between items-center relative z-10">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 bg-white/10 backdrop-blur-md rounded-xl flex items-center justify-center text-xl border border-white/10">
                                {{ $isFlight ? '✈️' : '🚌' }}
                            </div>
                            <div>
                                <h2 class="text-base font-extrabold tracking-wide">
                                    {{ $isFlight ? 'عبور للطيران' : 'عبور للنقل البري' }}
                                </h2>
                                <p class="text-[10px] text-white/70 uppercase tracking-widest font-mono">
                                    {{ $isFlight ? 'Flight Boarding Pass' : 'Bus Boarding Pass' }}
                                </p>
                            </div>
                        </div>
                        <div class="text-left">
                            <span
                                class="bg-white/10 border border-white/15 px-3 py-1 rounded-lg text-xs font-mono font-bold tracking-wider">
                                #{{ $booking->id }}-{{ $seatRelation->display_seats }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- جسم التذكرة --}}
                <div class="p-6 space-y-6">

                    {{-- مسار الرحلة --}}
                    <div
                        class="bg-slate-50 p-4 rounded-2xl border border-slate-100 flex justify-between items-center gap-4">
                        <div class="space-y-1">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">
                                {{ $isFlight ? 'مطار الإقلاع' : 'محطة الانطلاق' }}
                            </span>
                            <h3 class="text-lg sm:text-xl font-black text-slate-900">
                                {{ $booking->trip->from_city ?? 'غير محدد' }}
                            </h3>
                        </div>

                        <div class="flex-1 flex items-center justify-center gap-2 px-2">
                            <div class="h-[2px] flex-1 bg-slate-200"></div>
                            <span class="text-slate-400 text-lg transform {{ $isFlight ? '-rotate-45' : '' }}">
                                {{ $isFlight ? '✈️' : '🚌' }}
                            </span>
                            <div class="h-[2px] flex-1 bg-slate-200"></div>
                        </div>

                        <div class="space-y-1 text-left">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">
                                {{ $isFlight ? 'مطار الوصول' : 'الوجهة' }}
                            </span>
                            <h3 class="text-lg sm:text-xl font-black text-slate-900">
                                {{ $booking->trip->to_city ?? 'غير محدد' }}
                            </h3>
                        </div>
                    </div>

                    {{-- شبكة تفاصيل المسافر والرحلة --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">

                        {{-- المسافر --}}
                        <div class="bg-slate-50/50 p-3 rounded-xl border border-slate-100 col-span-2 md:col-span-1">
                            <span class="text-[10px] text-slate-400 font-bold uppercase block mb-1">اسم المسافر</span>
                            <p class="font-bold text-slate-900 text-sm truncate">
                                {{ $seatRelation->passenger_name ?? $booking->customer_name }}
                            </p>
                            <span class="text-[10px] text-slate-500 font-mono block mt-1">
                                ID: {{ $seatRelation->decrypted_national_id }}
                            </span>
                        </div>

                        {{-- المقعد والسكن --}}
                        <div class="bg-slate-50/50 p-3 rounded-xl border border-slate-100">
                            <span class="text-[10px] text-slate-400 font-bold uppercase block mb-1">المقعد / السكن</span>

                            <p class="font-black text-emerald-600 text-sm flex items-start gap-1 break-words">
                                <span class="shrink-0">💺</span>
                                <span>{{ $seatRelation->display_seats }}</span>
                            </p>

                            <span class="text-[10px] text-slate-600 font-semibold block mt-1.5 leading-snug break-words">
                                🏠 {{ $seatRelation->room_name_ar }}
                            </span>
                        </div>

                        {{-- الوجبات --}}
                        <div class="bg-slate-50/50 p-3 rounded-xl border border-slate-100">
                            <span class="text-[10px] text-slate-400 font-bold uppercase block mb-1">الوجبات والمأكولات</span>
                            @if (!empty($seatRelation->package_name))
                                <span
                                    class="inline-block bg-violet-100 text-violet-800 text-[9px] font-bold px-1.5 py-0.5 rounded mb-1">
                                    📦 {{ $seatRelation->package_name }}
                                </span>
                            @endif
                            <p class="font-bold text-slate-700 text-[11px] leading-tight">
                                {{ $seatRelation->meals_summary_ar ?? 'لا يوجد' }}
                            </p>
                        </div>

                        {{-- المواعيد --}}
                        <div
                            class="bg-slate-50/50 p-3 rounded-xl border border-slate-100 col-span-2 md:col-span-1 flex flex-col justify-center gap-1">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] text-indigo-600 font-bold">🛫 الذهاب:</span>
                                <span class="font-mono text-[11px] font-bold text-slate-800">
                                    {{ $booking->trip->departure_at ? \Carbon\Carbon::parse($booking->trip->departure_at)->format('Y-m-d H:i') : '--' }}
                                </span>
                            </div>
                            @if (in_array($booking->trip_type ?? ($booking->trip->trip_type ?? ''), ['round_trip', 'ذهاب وعودة', 'ذهاب وإياب']) ||
                                    !empty($booking->trip->return_at))
                                <div class="flex justify-between items-center pt-1 border-t border-slate-200/60">
                                    <span class="text-[10px] text-amber-600 font-bold">🛬 العودة:</span>
                                    <span class="font-mono text-[11px] font-bold text-slate-800">
                                        {{ $booking->trip->return_at ? \Carbon\Carbon::parse($booking->trip->return_at)->format('Y-m-d H:i') : '--' }}
                                    </span>
                                </div>
                            @endif
                        </div>

                    </div>

                    {{-- الخدمات الإضافية --}}
                    @if (!empty($seatRelation->formatted_services))
                        <div class="pt-2">
                            <span class="text-[10px] text-purple-700 font-bold uppercase tracking-wider block mb-2">✨ Services / الخدمات الإضافية:</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($seatRelation->formatted_services as $service)
                                    <span
                                        class="bg-purple-50 text-purple-900 border border-purple-200/80 px-2.5 py-1 rounded-lg text-[11px] font-bold">
                                        {{ $service['name'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>

                {{-- خط التخريم التجميلي للجزء السفلي --}}
                <div class="relative">
                    <div
                        class="absolute -left-3 -top-3 w-6 h-6 bg-slate-50 border border-slate-200 rounded-full z-10 print:hidden">
                    </div>
                    <div
                        class="absolute -right-3 -top-3 w-6 h-6 bg-slate-50 border border-slate-200 rounded-full z-10 print:hidden">
                    </div>
                    <div class="border-t-2 border-dashed border-slate-200"></div>
                </div>

                {{-- ذيل التذكرة (QR Code ومعلومات التحقق) --}}
                <div class="p-6 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-center sm:text-right space-y-1">
                        <p class="text-[11px] text-slate-500 font-medium leading-relaxed max-w-md">
                            * يرجى إبراز الهوية الوطنية أو جواز السفر المسجل بالمنصة عند بوابة التفتيش والتحقق.
                        </p>
                        <span class="text-[9px] text-slate-400 font-mono block">
                            تاريخ الإصدار: {{ now()->format('Y-m-d') }} | عبور للتنقل
                        </span>
                    </div>

                    {{-- QR Code --}}
                    <div
                        class="flex items-center gap-3 bg-white p-2.5 rounded-2xl border border-slate-200 shadow-sm shrink-0">
                        @if ($seatRelation->qr_code_path)
                            <img src="{{ asset('storage/' . $seatRelation->qr_code_path) }}" alt="QR Code"
                                class="w-16 h-16 rounded-lg">
                        @else
                            <div
                                class="w-16 h-16 bg-slate-100 rounded-lg flex items-center justify-center text-[10px] text-slate-400 text-center font-bold">
                                بلا QR
                            </div>
                        @endif
                        <div class="text-right pl-2">
                            <span class="text-[10px] font-black text-slate-900 block uppercase tracking-wider">ماسح التذكرة</span>
                            <span class="text-[8px] font-mono text-slate-400 block mt-0.5 uppercase">Verified Ticket</span>
                        </div>
                    </div>
                </div>

            </div>
        @endforeach

    </div>

    <style>
        @media print {
            body {
                background: white !important;
                color: black !important;
            }

            .print\:hidden {
                display: none !important;
            }

            .ticket-card {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .container {
                max-width: 100% !important;
                padding: 0 !important;
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endsection