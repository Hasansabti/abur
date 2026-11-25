@extends('layouts.client')

@section('content')
    <div class="container mx-auto px-4 py-10 max-w-6xl" x-data="tripBookingApp({
        showRoomModal: false,
        maxSeats: 100,
        isAutoSplit: @js((bool) $trip->auto_split),
        splitBoundary: parseInt(@js($trip->split_boundary ?? 28)) || 28,
        passengerSeats: {}, // تخزين المقاعد للمسافرين: { 'uid_1': [2, 3], 'uid_2': [5] }
        showAddSeatSelect: {}, // حالة إظهار/إخفاء القائمة المنسدلة لكل مسافر
        addedCompanionKeys: [],
        companionUidLookup: {},
        selfAdded: false,
        selfUid: null,
        sharedRoomTypes: {},
        allowExtraSeat: @js((bool) ($trip->allow_to_add_seat ?? false)),
    
        bedTypeOptions: @js($bedTypeOptions ?? (object) []),
        prefilledRoomBedTypes: @js($prefilledRoomBedTypes ?? (object) []),
        prefilledAgencyNote: @js($prefilledAgencyNote ?? ''),
    
        // --- الحقول الصحيحة المطابقة للموديل ---
        isConditional: @js((bool) $trip->is_conditional),
        minSeatsRequired: parseInt(@js($trip->min_seats_required ?? 0)) || 0,
        conditionalDeadline: @js($trip->threshold_deadline_at),
        bookedSeats: @js($booked_seats ?? []),
        // -------------------------------------
    
        draftExpiresAt: null,
        timeLeftFormatted: '',
        draftTimer: null,
    
        roomOptionsRaw: @js($trip->room_options ?? []),
        mealOptionsRaw: @js($trip->meal_options ?? []),
    
        serviceOptionsRaw: @js($trip->extra_services ?? []),
        selectedServices: [],
    
        companions: @js($companions ?? []),
        extra_services: {{ json_encode($trip->extra_services) }},
        roomPrices: @js($trip->room_prices ?? []),
        seatPrice: parseFloat(@js($trip->price)) || 0,
        isLoggedIn: @js(Auth::check()),
        authUserName: @js(optional(Auth::user())->name ?? ''),
        authUserNationalId: @js(optional(Auth::user())->national_id ?? (optional(Auth::user())->id_number ?? '')),
        authUserGender: @js(optional(Auth::user())->gender ?? ''),
        checkoutRoute: '{{ route('client.checkout') }}',
        csrfToken: '{{ csrf_token() }}',
        tripId: '{{ $trip->id }}',
        accommodationOnlyDiscount: parseFloat(@js($trip->accommodation_only_discount)) || 0,
        totalSeats: parseInt(@js($trip->total_seats)) || 49,
    
        
    
        // 🟢 البيانات المحملة مسبقاً للحجز السابق
        existingBookingId: @js($existingBooking->id ?? null),
        prefilledSeats: @js($prefilledSeats ?? []),
        prefilledPassengers: @js($prefilledPassengers ?? (object) []),
        prefilledRoomQuantities: @js($prefilledRoomQuantities ?? []),
        {{-- ✅ إضافة --}}
        prefilledRoomAssignments: @js($prefilledRoomAssignments ?? [])
    })"
        :class="{ 'overflow-hidden h-screen': showLoginModal }">
        <!-- ⏱️ كارت عرض العداد التنازلي للمسودة -->
        <template x-if="draftExpiresAt && timeLeftFormatted">
            <div class="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-2xl flex items-center justify-between text-xs font-bold shadow-sm animate-fade-in"
                dir="rtl">
                <div class="flex items-center gap-2">
                    <span class="text-base animate-pulse">⏳</span>
                    <span>متبقي على صلاحية بيانات الحجز المؤقتة:</span>
                </div>
                <div
                    class="bg-amber-200/80 px-3 py-1.5 rounded-xl text-sm font-black font-mono text-amber-950 tracking-wider">
                    <span x-text="timeLeftFormatted"></span>
                </div>
            </div>
        </template>

        {{-- شرط اذا كان المستخدم غير مسجل دخوله --}}
        @if (Auth::check() && Auth::user()->is_verified !== 1)
            <div
                class="relative p-4 md:p-5 bg-amber-50 border-2 border-amber-400/60 rounded-2xl shadow-lg shadow-amber-500/10 my-4 transition-all">
                <div class="flex items-start gap-4">

                    <!-- أيقونة تنبيه بنبض متحرك -->
                    <div class="relative flex-shrink-0 mt-0.5">
                        <span
                            class="absolute inline-flex h-full w-full rounded-xl bg-amber-400 opacity-75 animate-ping"></span>
                        <div class="relative p-2.5 bg-amber-500 text-white rounded-xl shadow-md">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>

                    <!-- محتوى الرسالة والزر -->
                    <div class="flex-1">
                        <h3 class="text-base font-black text-amber-950 flex items-center gap-2">
                            توثيق الحساب مطلوب ⚠️
                        </h3>
                        <p class="mt-1 text-sm font-bold text-amber-900/80 leading-relaxed">
                            لا يمكنك إتمام عملية الحجز والدفع حتى تقوم بتوثيق حسابك الشخصي.
                        </p>

                        <div class="mt-3">
                            <a href="{{ route('client.profile') }}"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-600 hover:bg-amber-700 active:scale-95 text-white text-xs font-black rounded-xl transition-all shadow-md shadow-amber-600/20">
                                <span>انتقل لتوثيق الحساب الآن</span>
                                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        @endif


        {{-- ========================================== --}}
        {{-- شريط التنقل بين الأقسام (مسار الخطوات التفاعلي) --}}
        {{-- ========================================== --}}

        <!-- تصميم الشاشات الكبيرة -->
        <div class="hidden md:block mb-10 bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 relative z-10">
            <div class="relative flex justify-between items-start">
                <div class="absolute left-12 right-12 top-6 h-1.5 bg-slate-100 rounded-full -z-10"></div>
                <div class="absolute right-12 top-6 h-1.5 bg-blue-600 rounded-full -z-10 transition-all duration-500 ease-in-out"
                    :style="'width: calc(' + ((step - 1) / 3 * 100) + '% - ' + (step === 1 ? '0px' : '3rem') + ');'"></div>

                <!-- الخطوة 1 -->
                <div class="flex flex-col items-center w-1/4 relative cursor-pointer group" @click="step = 1">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-xl transition-all duration-300 shadow-sm"
                        :class="step >= 1 ? 'bg-blue-600 text-white shadow-blue-500/30 scale-110' :
                            'bg-slate-50 text-slate-400 border border-slate-200 group-hover:border-blue-300 group-hover:text-blue-500'">
                        🏨
                    </div>
                    <div class="mt-4 text-center">
                        <h4 class="text-sm font-black transition-colors"
                            :class="step >= 1 ? 'text-blue-900' : 'text-slate-500'">1. اختيار الغرف</h4>
                        <div
                            class="mt-2 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-100 text-[10px] font-bold text-slate-500 min-h-[32px] flex items-center justify-center line-clamp-2 transition-all">
                            <span
                                x-text="Object.values(roomQuantities || {}).reduce((a, b) => a + b, 0) > 0 ? (Object.values(roomQuantities).reduce((a, b) => a + b, 0) + ' غرفة خاصة') : 'تسكين أساسي'"></span>
                        </div>
                    </div>
                </div>

                <!-- الخطوة 2 -->
                <div class="flex flex-col items-center w-1/4 relative cursor-pointer group"
                    @click="if(step > 2) step = 2; else step = 2;">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-xl transition-all duration-300 shadow-sm"
                        :class="step >= 2 ? 'bg-blue-600 text-white shadow-blue-500/30 scale-110' :
                            'bg-slate-50 text-slate-400 border border-slate-200 group-hover:border-blue-300 group-hover:text-blue-500'">
                        👥
                    </div>
                    <div class="mt-4 text-center">
                        <h4 class="text-sm font-black transition-colors"
                            :class="step >= 2 ? 'text-blue-900' : 'text-slate-500'">2. بيانات الركاب</h4>
                        <div
                            class="mt-2 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-100 text-[10px] font-bold text-slate-500 min-h-[32px] flex items-center justify-center line-clamp-2 transition-all">
                            <span
                                x-text="selectedSeats.length > 0 ? (selectedSeats.length + ' مسافرين') : 'لم يتم الإضافة'"></span>
                        </div>
                    </div>
                </div>

                <!-- الخطوة 3 -->
                <div class="flex flex-col items-center w-1/4 relative cursor-pointer group"
                    @click="if(selectedSeats.length > 0 && selectedSeats.length <= 100) step = 3;">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-xl transition-all duration-300 shadow-sm"
                        :class="step >= 3 ? 'bg-blue-600 text-white shadow-blue-500/30 scale-110' :
                            'bg-slate-50 text-slate-400 border border-slate-200 group-hover:border-blue-300 group-hover:text-blue-500'">
                        💺
                    </div>
                    <div class="mt-4 text-center">
                        <h4 class="text-sm font-black transition-colors"
                            :class="step >= 3 ? 'text-blue-900' : 'text-slate-500'">3. المقاعد</h4>
                        <div
                            class="mt-2 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-100 text-[10px] font-bold text-slate-500 min-h-[32px] flex items-center justify-center line-clamp-2 transition-all">
                            <span
                                x-text="selectedSeats.filter(s => typeof s === 'number' && s > 0).length > 0 ? 'تم التوزيع' : 'بانتظار التوزيع'"></span>
                        </div>
                    </div>
                </div>

                <!-- الخطوة 4 -->
                <div class="flex flex-col items-center w-1/4 relative cursor-pointer group"
                    @click="if(selectedSeats.length > 0 && getUnassignedPassengers().length === 0) step = 4;">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-xl transition-all duration-300 shadow-sm"
                        :class="step >= 4 ? 'bg-emerald-500 text-white shadow-emerald-500/30 scale-110' :
                            'bg-slate-50 text-slate-400 border border-slate-200 group-hover:border-emerald-300 group-hover:text-emerald-500'">
                        💳
                    </div>
                    <div class="mt-4 text-center">
                        <h4 class="text-sm font-black transition-colors"
                            :class="step >= 4 ? 'text-emerald-700' : 'text-slate-500'">4. الحساب والدفع</h4>
                        <div
                            class="mt-2 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-100 text-[10px] font-bold text-slate-500 min-h-[32px] flex items-center justify-center line-clamp-2 transition-all">
                            <span
                                x-text="step === 4 && typeof totalPrice !== 'undefined' ? totalPrice + ' ر.س' : 'المراجعة النهائية'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- تصميم الشاشات الصغيرة (الجوال) -->
        <div class="md:hidden mb-6 bg-white p-4 rounded-3xl shadow-sm border border-slate-100 relative z-10">
            <div class="relative flex justify-between items-center w-full mb-3 px-2">
                <div class="absolute left-6 right-6 top-1/2 -translate-y-1/2 h-1 bg-slate-100 -z-10 rounded-full"></div>
                <div class="absolute right-6 top-1/2 -translate-y-1/2 h-1 bg-blue-600 -z-10 rounded-full transition-all duration-500"
                    :style="'width: calc(' + ((step - 1) / 3 * 100) + '% - ' + (step === 1 ? '0px' : '1.5rem') + ');'">
                </div>

                <template x-for="i in 4" :key="i">
                    <button @click="if(i <= step + 1) step = i"
                        class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm transition-all shadow-sm"
                        :class="step >= i ? (i === 4 ? 'bg-emerald-500 text-white' : 'bg-blue-600 text-white') :
                            'bg-slate-50 text-slate-400 border border-slate-200'">
                        <span x-show="i === 1">🏨</span>
                        <span x-show="i === 2">👥</span>
                        <span x-show="i === 3">💺</span>
                        <span x-show="i === 4">💳</span>
                    </button>
                </template>
            </div>

            <div class="text-center p-3 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col gap-1">
                <h4 class="text-sm font-black text-blue-950"
                    x-text="step === 1 ? '1. تفاصيل الرحلة واختيار الغرف' : (step === 2 ? '2. إضافة المسافرين وبياناتهم' : (step === 3 ? '3. توزيع المقاعد وتسكين الركاب' : '4. ملخص الحساب وإتمام الدفع'))">
                </h4>
                <p class="text-[11px] font-bold text-slate-500"
                    x-text="step === 1 ? (Object.values(roomQuantities || {}).reduce((a, b) => a + b, 0) > 0 ? (Object.values(roomQuantities).reduce((a, b) => a + b, 0) + ' غرفة محددة') : 'تسكين أساسي') : (step === 2 ? (selectedSeats.length > 0 ? selectedSeats.length + ' مسافرين' : 'بانتظار الإضافة') : (step === 3 ? (selectedSeats.filter(s => typeof s === 'number' && s > 0).length > 0 ? 'تم توزيع المقاعد' : 'تحديد المقاعد مطلوب') : 'المبلغ الإجمالي: ' + (typeof totalPrice !== 'undefined' ? totalPrice + ' ر.س' : '0')))">
                </p>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- الخطوة 1: تفاصيل الرحلة واختيار الغرف --}}
        {{-- ========================================== --}}

        <div x-show="step === 1" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
            class="space-y-6 text-right dir-rtl">

            <div class="bg-white rounded-2xl border border-slate-200/70 shadow-sm overflow-hidden">
                <div class="p-5 md:p-7 space-y-6">
                    <h1 class="text-xl md:text-2xl font-extrabold text-slate-800">تفاصيل الرحلة</h1>
                    @if (isset($trip->is_conditional) && $trip->is_conditional)
                        <div class="p-4 rounded-2xl bg-amber-50/80 border-2 border-amber-300/70 shadow-sm space-y-2">
                            <div class="flex items-center gap-2 text-amber-950 font-black text-sm">
                                <span class="text-lg">⚠️</span>
                                <h3>تنبيه: هذه رحلة مشروطة</h3>
                            </div>
                            <p class="text-xs text-amber-900 font-bold leading-relaxed">
                                انطلاق هذه الرحلة يعتمد على الوصول للحد الأدنى المطلوب
                                <span
                                    class="underline underline-offset-4 font-black">({{ $trip->min_seats_required ?? ($trip->min_seats_required ?? 'المحدد') }}
                                    مقعد)</span>.
                                @if (!empty($trip->confirmation_deadline))
                                    آخر موعد لتأكيد الرحلة هو:
                                    <span dir="ltr"
                                        class="font-black inline-block bg-amber-100 px-2 py-0.5 rounded border border-amber-300 text-amber-950">
                                        {{ \Carbon\Carbon::parse($trip->confirmation_deadline)->format('Y-m-d g:i A') }}
                                    </span>.
                                @endif
                                في حال عدم اكتمال العدد قبل المهلة، سيتم إلغاء الحجز وإعادة كامل المبلغ لحسابك تلقائياً.
                            </p>
                        </div>
                    @endif

                    <div
                        class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-6 border-b border-slate-100">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-14 h-14 shrink-0 rounded-2xl bg-indigo-50 border border-indigo-100/80 text-indigo-600 flex items-center justify-center text-2xl shadow-sm">
                                {{ $trip->transport_type == 'bus' ? '🚌' : '✈️' }}
                            </div>

                            <div class="space-y-1">
                                <h1 class="text-xl md:text-2xl font-extrabold text-slate-800 tracking-tight">
                                    {{ $trip->title ?? $trip->from_city . ' ⟵ ' . $trip->to_city }}
                                </h1>
                                <p class="text-xs font-medium text-slate-500">
                                    وسيلة النقل:
                                    <span class="text-slate-700 font-bold">
                                        {{ $trip->vehicle_model ?? ($trip->transport_type === 'flight' ? 'طيران ممتد' : 'حافلة') }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            {{-- شارة الرحلة المشروطة --}}
                            @if (isset($trip->is_conditional) && $trip->is_conditional)
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-50 border border-amber-300 text-amber-800 shadow-2xs">
                                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                                    ⏳ رحلة مشروطة باكتفاء العدد
                                </span>
                            @endif

                            @if (isset($trip->auto_split) && $trip->auto_split)
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 border border-emerald-200/60 text-emerald-700">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    تقسيم نشط للرجال والسيدات
                                </span>
                            @endif


                            @if (isset($trip->is_security_approved) && $trip->is_security_approved)
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-100 border border-slate-200 text-slate-700">
                                    🔒 معتمد ومثبت أمنياً
                                </span>
                            @endif
                        </div>
                    </div>

                    @if (!empty($features) && count($features) > 0)
                        <div class="space-y-2">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">مميزات
                                الرحلة</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($features as $feature)
                                    <span
                                        class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-semibold bg-slate-50 border border-slate-200/60 text-slate-700">
                                        <span class="text-indigo-500">✦</span> {{ $feature }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">تاريخ الرحلة
                    </span>


                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @if (!empty($trip->date) || !empty($trip->departure_at))
                            <div
                                class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-9 h-9 rounded-lg bg-white border border-slate-200/80 flex items-center justify-center text-base shadow-2xs">
                                        📅
                                    </div>
                                    <div>
                                        <span class="block text-[11px] font-bold text-slate-400">موعد الذهاب</span>
                                        <span class="text-xs font-bold text-slate-800" dir="ltr">
                                            {{ $trip->departure_at ? \Carbon\Carbon::parse($trip->departure_at)->format('Y-m-d g:i A') : $trip->date }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- إضافة خانة مدة الرحلة (أيام وليالي) --}}
                        @if (!empty($trip->duration_days) || !empty($trip->duration_nights))
                            <div
                                class="p-3.5 rounded-xl bg-indigo-50/50 border border-indigo-200/60 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-9 h-9 rounded-lg bg-white border border-indigo-200/80 flex items-center justify-center text-base shadow-2xs">
                                        ⏳
                                    </div>
                                    <div>
                                        <span class="block text-[11px] font-bold text-indigo-700/80">مدة الرحلة</span>
                                        <span class="text-xs font-bold text-indigo-950">
                                            {{ $trip->duration_days ?? 0 }} أيام / {{ $trip->duration_days - 1 ?? 0 }}
                                            ليالي
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (in_array($trip->trip_type, ['round_trip', 'ذهاب وعودة', 'ذهاب وإياب']) &&
                                (!empty($trip->return_date) || !empty($trip->return_at)))
                            <div
                                class="p-3.5 rounded-xl bg-amber-50/50 border border-amber-200/60 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-9 h-9 rounded-lg bg-white border border-amber-200/80 flex items-center justify-center text-base shadow-2xs">
                                        🔄
                                    </div>
                                    <div>
                                        <span class="block text-[11px] font-bold text-amber-700/80">موعد العودة</span>
                                        <span class="text-xs font-bold text-amber-950" dir="ltr">
                                            {{ $trip->return_at ? \Carbon\Carbon::parse($trip->return_at)->format('Y-m-d g:i A') : $trip->return_date }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    @if ($trip->is_conditional)
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">إشغال الحجوزات
                        </span>

                        @php
                            $bookedSeats = max(0, ($trip->total_seats ?? 0) - ($trip->available_seats ?? 0));
                            $minRequired = max(1, (int) ($trip->min_seats_required ?? 1));
                        $progressPercent = min(100, round(($bookedSeats / $minRequired) * 100)); @endphp
                        {{-- شريط اكتمال العدد والمهلة المتبقية --}}
                        <div class="bg-amber-50/80 border border-amber-200/80 rounded-2xl p-3 my-2">

                            <div class="flex justify-between items-center text-xs font-bold text-amber-900 mb-1.5">
                                <span>🎯 تم حجز: <strong class="text-amber-600">{{ $bookedSeats }}</strong> من
                                    أصل
                                    <strong class="text-amber-600">{{ $minRequired }}</strong>
                                    مقعد مطلوب</span>
                                <span
                                    class="font-black bg-amber-200 text-amber-800 px-2 py-0.5 rounded-md text-[11px]">{{ $progressPercent }}%</span>
                            </div>
                            <div class="w-full bg-amber-200/50 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-gradient-to-r from-amber-500 to-orange-500 h-2.5 rounded-full transition-all duration-500"
                                    style="width: {{ $progressPercent }}%"></div>
                            </div>
                            @if (!empty($trip->threshold_deadline_at))
                                <p class="text-[10px] md:text-xs text-amber-800 font-bold mt-2 flex items-center gap-1">
                                    <span>⏰</span> مهلة اكتمال العدد: <span
                                        class="underline">{{ \Carbon\Carbon::parse($trip->threshold_deadline_at)->format('Y-m-d H:i') }}</span>
                                </p>
                            @endif
                        </div>
                    @endif

                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block"> ملاحظات الوكالة
                    </span>


                    @if (!empty($trip->notes))
                        <div class="p-4 rounded-xl bg-amber-50/60 border border-amber-200/60 flex items-start gap-3">
                            <span class="text-base shrink-0 mt-0.5">📝</span>
                            <div class="space-y-1">
                                <span class="text-xs font-bold text-amber-900 block">ملاحظات هامة من الوكالة:</span>
                                <p class="text-xs text-amber-800 leading-relaxed font-medium">{{ $trip->notes }}</p>
                            </div>
                        </div>
                    @endif

                </div>

            </div>


            <div class="bg-white rounded-3xl p-6 md:p-8 border border-slate-200/80 shadow-sm space-y-5">
                <div
                    class="border-b border-slate-100 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-black text-slate-800 flex items-center gap-2">
                            <span>🛏️</span> حدد الغرف الخاصة المطلوبة
                        </h2>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">اختر عدد ونوعية الغرف المناسبة لرحلتك</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <template x-for="(room, type) in roomOptions" :key="type">
                        <div x-show="room && room.price > 0"
                            :class="(roomQuantities[type] || 0) > 0 ? 'border-blue-600 bg-blue-50/20 ring-2 ring-blue-600/10' :
                                'border-slate-200 bg-white hover:border-slate-300'"
                            class="p-4 rounded-2xl border transition-all duration-200 flex flex-col justify-between gap-4 shadow-sm relative">

                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-black text-slate-800" x-text="getTypeName(type)"></span>

                                    <template x-if="room.count > 0">
                                        <span
                                            class="text-[10px] text-emerald-700 font-bold bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 rounded-md">
                                            متاح: <span x-text="room.count"></span>
                                        </span>
                                    </template>
                                </div>

                                <div class="text-lg font-black text-blue-600 flex items-baseline gap-1">
                                    <span x-text="roomPrices[type] || room.price"></span>
                                    <span class="text-xs font-bold text-slate-500">ر.س</span>
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-between bg-slate-50 border border-slate-200/80 rounded-xl p-1.5 mt-2">
                                <button type="button" @click="decreaseRoom(type)"
                                    class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center font-black text-slate-700 hover:bg-slate-100 active:scale-95 text-sm transition-all shadow-sm">
                                    -
                                </button>

                                <span class="text-sm font-black text-slate-800 select-none px-2"
                                    x-text="roomQuantities[type] || 0"></span>

                                {{-- ✅ تم التعديل: بعد إضافة الغرفة تُفتح نافذة اختيار نوع السرير للغرفة الجديدة --}}
                                <button type="button"
                                    @click="
                                        if ((roomQuantities[type] || 0) < room.count) {
                                            const uidsBefore = bookedRoomsList.map(r => r.uid);
                                            increaseRoom(type);
                                            $nextTick(() => {
                                                const added = bookedRoomsList.find(r => !uidsBefore.includes(r.uid));
                                                if (added && type !== 'single' && getBedOptions(added.uid).length > 0) {
                                                    $dispatch('open-bed-type-modal', { uid: added.uid });
                                                }
                                            });
                                        }
                                    "
                                    :disabled="(roomQuantities[type] || 0) >= room.count"
                                    :class="(roomQuantities[type] || 0) >= room.count ?
                                        'bg-slate-200 text-slate-400 cursor-not-allowed border-transparent' :
                                        'bg-blue-600 text-white hover:bg-blue-700 active:scale-95 shadow-sm shadow-blue-500/30'"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-sm transition-all">
                                    +
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
                <template x-if="bookedRoomsList.length > 0">
                    <div class="pt-5 mt-2 border-t border-slate-100 space-y-3">
                        <div>
                            <h3 class="text-sm font-black text-slate-800 flex items-center gap-2"><span>🛌</span> نوع
                                الأسرّة
                                في كل غرفة</h3>
                            <p class="text-[11px] text-slate-400 font-medium mt-0.5">اختر ترتيب الأسرّة المناسب لكل غرفة
                                حجزتها
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <template x-for="rm in bookedRoomsList" :key="rm.uid">
                                <div class="p-3.5 rounded-2xl border border-slate-200 bg-slate-50/60 space-y-2">
                                    <span class="text-xs font-black text-slate-800"
                                        x-text="rm.name + (getBedLabel(rm.uid) ? ' — ' + getBedLabel(rm.uid) : '')"></span>
                                    <div class="flex flex-col gap-1.5">
                                        <template x-for="opt in getBedOptions(rm.uid)" :key="opt.id">
                                            <button type="button" @click="setBedType(rm.uid, opt.id)"
                                                :class="roomBedTypes[rm.uid] === opt.id ?
                                                    'border-indigo-600 bg-indigo-50 text-indigo-800 ring-1 ring-indigo-500' :
                                                    'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                                                class="flex items-center justify-between border rounded-xl px-3 py-2 text-xs font-bold transition-all">
                                                <span x-text="opt.label"></span>
                                                <span x-show="roomBedTypes[rm.uid] === opt.id">✓</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

            </div>


            <div class="flex justify-end pt-2">
                <button @click="step = 2; window.scrollTo({ top: 0, behavior: 'smooth' });"
                    class="bg-slate-900 hover:bg-slate-800 text-white px-8 py-4 rounded-2xl font-black transition-all shadow-lg shadow-slate-900/10 active:scale-95 flex items-center gap-3">
                    <span>التالي: بيانات المسافرين</span>
                    <span class="text-lg">⬅️</span>
                </button>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- 🛌 نافذة اختيار نوع السرير للغرفة المضافة حديثاً --}}
        {{-- (خارج div الخطوة 1 عمداً حتى لا يتأثر موضعها بالـ transform) --}}
        {{-- ========================================== --}}
        <div x-data="{ bedModalOpen: false, bedModalUid: null }"
            @open-bed-type-modal.window="bedModalUid = $event.detail.uid; bedModalOpen = true"
            @keydown.escape.window="bedModalOpen = false"
            x-show="bedModalOpen" x-cloak style="display: none;"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-md"
            @click="bedModalOpen = false">

            <div class="bg-white border border-slate-200 shadow-2xl rounded-[2rem] p-6 max-w-md w-full space-y-4 text-right text-slate-800"
                @click.stop dir="rtl">

                {{-- العنوان --}}
                <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 flex items-center justify-center bg-indigo-50 text-indigo-600 rounded-2xl text-lg">
                            🛌</div>
                        <div>
                            <h3 class="text-base font-black text-slate-900">اختر نوع السرير</h3>
                            <p class="text-[11px] font-bold text-slate-400 mt-0.5"
                                x-text="(bookedRoomsList.find(r => r.uid === bedModalUid)?.name || 'الغرفة الجديدة')"></p>
                        </div>
                    </div>
                    <button type="button" @click="bedModalOpen = false"
                        class="w-9 h-9 flex items-center justify-center rounded-2xl bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 transition-all text-xs font-black">✕</button>
                </div>

                {{-- الخيارات --}}
                <div class="flex flex-col gap-2 max-h-[50vh] overflow-y-auto">
                    <template x-for="opt in (bedModalUid ? getBedOptions(bedModalUid) : [])" :key="opt.id">
                        <button type="button" @click="setBedType(bedModalUid, opt.id)"
                            :class="roomBedTypes[bedModalUid] === opt.id ?
                                'border-indigo-600 bg-indigo-50 text-indigo-800 ring-2 ring-indigo-500/30' :
                                'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                            class="flex items-center justify-between border rounded-xl px-4 py-3 text-sm font-bold transition-all">
                            <span x-text="opt.label"></span>
                            <span x-show="roomBedTypes[bedModalUid] === opt.id"
                                class="w-5 h-5 rounded-full bg-indigo-600 text-white text-[10px] flex items-center justify-center">✓</span>
                        </button>
                    </template>
                </div>

                {{-- الأزرار --}}
                <div class="grid grid-cols-2 gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="bedModalOpen = false" :disabled="!roomBedTypes[bedModalUid]"
                        :class="roomBedTypes[bedModalUid] ? 'bg-slate-900 hover:bg-slate-800 text-white' :
                            'bg-slate-200 text-slate-400 cursor-not-allowed'"
                        class="py-3 rounded-xl font-black text-xs transition-all active:scale-95">
                        تأكيد ✓
                    </button>
                    <button type="button" @click="bedModalOpen = false"
                        class="py-3 rounded-xl bg-slate-100 text-slate-600 font-black text-xs hover:bg-slate-200 transition-all active:scale-95">
                        لاحقاً
                    </button>
                </div>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- الخطوة 2: إضافة المسافرين وبياناتهم والوجبات --}}
        {{-- ========================================== --}}
        <div x-show="step === 2" style="display: none;" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="max-w-4xl mx-auto mb-6 bg-white rounded-[2.5rem] p-8 shadow-xl border border-slate-100">
                <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-5">
                    <h3 class="text-xl font-black flex items-center gap-2 text-slate-900">
                        <span class="p-2 bg-blue-50 text-blue-600 rounded-xl text-sm">👥</span> قائمة المسافرين
                    </h3>
                    <div class="flex gap-2 flex-wrap justify-end">
                        <button type="button"
                            @click="isAdditionalSeatModal = false; if (selectedSeats.length < 100) { addTransportPassenger(); } else { alert('عذراً، وصلت للحد الأقصى (100 مسافرين)'); }"
                            :disabled="selectedSeats.length >= 100"
                            :class="selectedSeats.length >= 100 ?
                                'opacity-50 cursor-not-allowed bg-slate-400 pointer-events-none' :
                                'bg-blue-600 hover:bg-blue-700 shadow-blue-500/20'"
                            class="text-xs font-black text-white px-4 py-2.5 rounded-xl transition-all flex items-center gap-1.5 shadow-md">
                            🚌 إضافة مسافر (نقل وسكن)
                        </button>

                        @if ($trip->allow_accommodation_only ?? true)
                            <button type="button"
                                @click="if (selectedSeats.length < 100) { addAccommodationOnlyPassenger(); } else { alert('عذراً، وصلت للحد الأقصى (100 مسافرين)'); }"
                                :disabled="selectedSeats.length >= 100"
                                :class="selectedSeats.length >= 100 ? 'opacity-50 cursor-not-allowed pointer-events-none' :
                                    'hover:bg-indigo-100'"
                                class="text-xs font-black text-indigo-700 bg-indigo-50 border border-indigo-200 px-4 py-2.5 rounded-xl transition-all flex items-center gap-1.5">
                                🏨 إضافة مسافر (سكن فقط)
                            </button>
                        @endif
                    </div>
                </div>



                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-4">
                    @auth
                        @php
                            $authUserUidName = auth()->user()->name ?? auth()->user()->full_name;
                            $authUserNationalId = auth()->user()->national_id ?? (auth()->user()->id_number ?? '');
                        @endphp
                        <button type="button" x-show="!selfAdded" @click="addSelfPassenger()"
                            :disabled="selectedSeats.length >= 100"
                            :class="selectedSeats.length >= 100 ?
                                'opacity-40 cursor-not-allowed pointer-events-none bg-slate-100 border-slate-200' :
                                'hover:bg-blue-50/80 hover:border-blue-400 bg-blue-50/30 border-blue-200'"
                            class="p-3 rounded-xl border transition-all flex items-center justify-between text-right group shadow-sm">

                            <div class="truncate pl-2">
                                <p class="text-xs font-bold text-blue-950 truncate flex items-center gap-1.5">
                                    <span>{{ $authUserUidName }}</span>
                                    <span
                                        class="text-[9px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded-md font-extrabold">حسابي</span>
                                </p>
                                <p class="text-[10px] text-blue-600/80 font-medium">
                                    {{ $authUserNationalId ? 'هوية: ' . $authUserNationalId : (auth()->user()->gender === 'female' ? 'أنثى' : 'ذكر') }}
                                </p>
                            </div>
                            <span
                                class="text-[10px] font-black bg-blue-600 text-white px-2 py-1 rounded-lg transition-colors shrink-0">
                                + إضافة
                            </span>
                        </button>
                    @endauth

                    <template x-for="(companion, cIndex) in companions" :key="companion.id ?? cIndex">
                        <button type="button" x-show="!isCompanionAdded(companion, cIndex)"
                            @click="addCompanionPassenger(companion, cIndex)" :disabled="selectedSeats.length >= 100"
                            :class="selectedSeats.length >= 100 ?
                                'opacity-40 cursor-not-allowed pointer-events-none bg-slate-100 border-slate-200' :
                                'hover:bg-indigo-50/50 hover:border-indigo-400 bg-white border-indigo-200/80'"
                            class="p-3 rounded-xl border transition-all flex items-center justify-between text-right group shadow-sm">

                            <div class="truncate pl-2">
                                <p class="text-xs font-bold text-slate-800 truncate"
                                    x-text="companion.name || companion.full_name"></p>
                                <p class="text-[10px] text-slate-400 font-medium"
                                    x-text="companion.national_id ? 'هوية: ' + companion.national_id : (companion.gender === 'female' ? 'أنثى' : 'ذكر')">
                                </p>
                            </div>
                            <span
                                class="text-[10px] font-black bg-indigo-100 text-indigo-700 group-hover:bg-indigo-600 group-hover:text-white px-2 py-1 rounded-lg transition-colors shrink-0">
                                + إضافة
                            </span>
                        </button>
                    </template>
                </div>
                <!-- تلميح تفاعلي متحرك لفتح بيانات المسافر -->
                <div x-show="getPassengerUids().length > 0" x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    class="mb-4 bg-gradient-to-r from-blue-50 via-indigo-50/50 to-blue-50 border border-blue-200/60 rounded-2xl p-3.5 flex items-center gap-3.5 shadow-sm relative overflow-hidden group cursor-default">

                    <!-- تأثير النبض التفاعلي للأيقونة -->
                    <div class="relative flex h-10 w-10 shrink-0 items-center justify-center">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-20"></span>
                        <span
                            class="relative inline-flex rounded-full h-9 w-9 bg-white text-blue-600 items-center justify-center text-lg shadow-sm border border-blue-100 animate-bounce group-hover:animate-none transition-all">
                            👇
                        </span>
                    </div>

                    <div class="flex-1">
                        <h4 class="text-sm font-black text-blue-900 mb-0.5 flex items-center gap-1.5">
                            أكمل بيانات المسافرين! <span
                                class="flex h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                        </h4>
                        <p class="text-[11px] sm:text-xs font-bold text-blue-700/80 leading-relaxed">
                            اضغط على <span
                                class="bg-blue-100/80 px-1.5 py-0.5 rounded-md text-blue-800 border border-blue-200/50">مربع
                                اسم المسافر</span> أدناه لفتح النافذة لتعديل بيانات اوتحديد الهوية، اختيار الوجبات، وإضافة
                            الخدمات ⚙️
                        </p>
                    </div>
                </div>

                <div class="space-y-3 max-h-[500px] overflow-y-auto pr-2">
                    <template x-if="selectedSeats.length === 0">
                        <div class="text-center py-12 bg-slate-50 border border-dashed border-slate-200 rounded-3xl">
                            <span class="text-4xl block mb-3 opacity-50">📝</span>
                            <h4 class="font-bold text-slate-700 mb-1">لم يتم إضافة أي مسافرين بعد</h4>
                            <p class="text-xs text-slate-400 font-medium">استخدم الأزرار بالأعلى أو اضغط على أحد المرافقين
                                لإضافة الركاب قبل اختيار المقاعد.</p>
                        </div>
                    </template>


                    <template x-for="seat in getPassengerUids()" :key="seat">
                        <div @click="activeModalSeat = seat; isAdditionalSeatModal = (seatRoomAssignments[seat] === 'additional'); openPassengerModal = true;"
                            class="bg-slate-50/60 hover:bg-slate-50 p-3.5 rounded-2xl border border-slate-200 flex items-center justify-between cursor-pointer transition-all hover:scale-[1.01] hover:border-blue-300 hover:shadow-sm group">
                            <div class="flex items-center gap-4 w-full justify-between">
                                <div class="flex items-center gap-3">
                                    <!-- الأيقونة: رمز السكن أو المقعد -->
                                    <span class="w-10 h-10 rounded-2xl flex items-center justify-center font-black text-sm"
                                        :class="String(seat).startsWith('H') ?
                                            'bg-amber-50 text-amber-600 border border-amber-100' :
                                            ((passengerSeats[seat] && passengerSeats[seat].length > 0) ?
                                                'bg-emerald-50 text-emerald-600 border border-emerald-100' :
                                                'bg-slate-200/50 text-slate-500 border border-slate-200')"
                                        x-text="String(seat).startsWith('H') ? '🏨' : ((passengerSeats[seat] && passengerSeats[seat].length > 0) ? '💺' : '👤')"></span>

                                    <div class="text-right">
                                        <!-- اسم المسافر -->
                                        <p class="text-sm font-bold text-slate-800">
                                            <span
                                                x-text="passengerNames[seat] ? passengerNames[seat] : (passengerIds[seat] ? 'هوية: ' + passengerIds[seat] : '⚠️ مسافر جديد - اضغط لإدخال البيانات')"></span>
                                        </p>

                                        <!-- الجنس والمقاعد المخصصة -->
                                        <p class="text-xs text-slate-500 mt-1 font-medium">
                                            <span
                                                x-text="passengerGenders[seat] === 'male' ? '👨 ذكر' : (passengerGenders[seat] === 'female' ? '👩 أنثى' : 'الجنس غير محدد')"></span>
                                            • <span
                                                x-text="String(seat).startsWith('H') ? 'سكن فقط' : ((passengerSeats[seat] && passengerSeats[seat].length > 0) ? 'المقاعد: ' + passengerSeats[seat].join('، ') : 'بانتظار تخصيص مقعد')"></span>
                                        </p>

                                        <!-- نوع الغرفة/السكن المخصص -->
                                        <div class="mt-1.5" x-show="getPassengerRoomLabel(seat)">
                                            <span
                                                class="inline-flex items-center gap-1 bg-slate-100 text-slate-700 border border-slate-200 px-2 py-0.5 rounded-md text-[10px] font-bold">
                                                🛏️ <span x-text="getPassengerRoomLabel(seat)"></span>
                                            </span>
                                        </div>

                                        <!-- قسم الوجبات المختارة -->
                                        <div class="flex flex-col gap-1 mt-1.5"
                                            x-show="passengerMeals[seat] && passengerMeals[seat].wants_meal">
                                            <template x-if="passengerMeals[seat]?.package_name">
                                                <span
                                                    class="bg-indigo-50 text-indigo-700 border border-indigo-100 px-2 py-0.5 rounded-md text-[10px] font-black w-max flex items-center gap-1">
                                                    📦 باقة: <span x-text="passengerMeals[seat].package_name"></span>
                                                </span>
                                            </template>
                                            <template
                                                x-if="!passengerMeals[seat]?.package_name && ((passengerMeals[seat]?.breakfast && passengerMeals[seat]?.lunch) || (passengerMeals[seat]?.lunch && passengerMeals[seat]?.dinner) || (passengerMeals[seat]?.breakfast && passengerMeals[seat]?.dinner))">
                                                <span
                                                    class="bg-emerald-50 text-emerald-700 border border-emerald-100 px-2 py-0.5 rounded-md text-[10px] font-black w-max flex items-center gap-1">
                                                    ✨ باقة دمج وجبات
                                                </span>
                                            </template>
                                            <div class="flex flex-wrap gap-1 mt-0.5">
                                                <template x-if="passengerMeals[seat]?.breakfast">
                                                    <span
                                                        class="bg-amber-50 text-amber-800 border border-amber-200/70 px-1.5 py-0.5 rounded text-[10px] font-bold">🍳
                                                        إفطار</span>
                                                </template>
                                                <template x-if="passengerMeals[seat]?.lunch">
                                                    <span
                                                        class="bg-orange-50 text-orange-800 border border-orange-200/70 px-1.5 py-0.5 rounded text-[10px] font-bold">🍔
                                                        غداء</span>
                                                </template>
                                                <template x-if="passengerMeals[seat]?.dinner">
                                                    <span
                                                        class="bg-purple-50 text-purple-800 border border-purple-200/70 px-1.5 py-0.5 rounded text-[10px] font-bold">🥩
                                                        عشاء</span>
                                                </template>
                                            </div>
                                        </div>

                                        <!-- الخدمات الإضافية المختارة -->
                                        <div class="flex flex-wrap gap-1 mt-1.5"
                                            x-show="getPassengerServicesDetails(seat).length > 0">
                                            <template x-for="service in getPassengerServicesDetails(seat)"
                                                :key="service.id || service.name">
                                                <span
                                                    class="bg-blue-50 text-blue-700 border border-blue-200/70 px-1.5 py-0.5 rounded text-[10px] font-bold flex items-center gap-1">
                                                    <span>✨</span>
                                                    <span x-text="service.name"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- شارة حالة التسكين على اليسار -->
                                <div class="text-left shrink-0">
                                    <template x-if="String(seat).startsWith('H')">
                                        <span
                                            class="bg-amber-100 text-amber-800 border border-amber-200 px-2.5 py-1 rounded-lg font-bold text-[10px] whitespace-nowrap shadow-sm">
                                            سكن فقط 🏨
                                        </span>
                                    </template>
                                    <template x-if="!String(seat).startsWith('H')">
                                        <div>
                                            <span x-show="!passengerSeats[seat] || passengerSeats[seat].length === 0"
                                                class="bg-slate-200 text-slate-700 border border-slate-300 px-2.5 py-1 rounded-lg font-bold text-[10px] whitespace-nowrap shadow-sm">
                                                بانتظار المقعد ⏳
                                            </span>
                                            <span x-show="passengerSeats[seat] && passengerSeats[seat].length > 0"
                                                class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-2.5 py-1 rounded-lg font-bold text-[10px] whitespace-nowrap shadow-sm flex items-center gap-1"
                                                x-text="'تم التسكين (' + passengerSeats[seat].join('، ') + ') ✓'">
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex justify-between max-w-4xl mx-auto">
                {{-- زر السابق --}}
                <button @click="step = 1; window.scrollTo({ top: 0, behavior: 'smooth' });"
                    class="bg-slate-100 text-slate-700 px-6 py-3.5 rounded-2xl font-black hover:bg-slate-200 transition-all active:scale-95 flex items-center gap-2">
                    ➡️ السابق
                </button>

                {{-- زر التالي --}}
                <button
                    @click="
            if(getPassengerUids().length > 100) { 
                alert('الرجاء تقليل عدد المسافرين إلى 100 كحد أقصى للمتابعة.'); 
            } else if(getPassengerUids().length === 0) { 
                alert('يرجى إضافة مسافر واحد على الأقل للمتابعة.'); 
            } else { 
                step = 3; 
                window.scrollTo({ top: 0, behavior: 'smooth' }); 
            }
         "
                    :disabled="getPassengerUids().length > 100 || getPassengerUids().length === 0"
                    :class="(getPassengerUids().length > 100 || getPassengerUids().length === 0) ?
                    'opacity-50 cursor-not-allowed bg-slate-400' :
                    'bg-slate-900 hover:bg-slate-800'"
                    class="text-white px-8 py-3.5 rounded-2xl font-black transition-all shadow-lg active:scale-95 flex items-center gap-2">
                    التالي: توزيع المقاعد ⬅️
                </button>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- الخطوة 3: توزيع المسافرين على المقاعد --}}
        {{-- ========================================== --}}
        <div x-show="step === 3" style="display: none;" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">

            <div class="max-w-6xl mx-auto mb-6 bg-white rounded-[3rem] p-8 shadow-sm border border-slate-100">

                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <h3 class="font-black text-slate-800 text-xl tracking-tighter">
                            {{ $trip->transport_type == 'bus' ? 'مخطط الحافلة والتسكين 🚌' : 'مخطط الطائرة والتسكين ✈️' }}
                        </h3>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-x-4 gap-y-2">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-white rounded-sm border border-slate-300"></span>
                            <span class="text-[10px] font-bold text-slate-500">متاح</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-amber-400 rounded-sm"></span>
                            <span class="text-[10px] font-bold text-amber-600">محدد</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-pink-500 rounded-sm shadow-sm"></span>
                            <span class="text-[10px] font-bold text-pink-600">محجوز (أنثى)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-blue-800 rounded-sm shadow-sm"></span>
                            <span class="text-[10px] font-bold text-blue-900">محجوز (ذكر)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                class="w-3 h-3 bg-slate-300 rounded-sm border flex items-center justify-center text-[8px] font-black text-slate-600">-</span>
                            <span class="text-[10px] font-bold text-slate-500">مقفل/كادر</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <div class="lg:col-span-4 flex flex-col gap-6">
                        <div class="bg-slate-50 p-5 rounded-3xl shadow-inner border border-slate-200 sticky top-4">
                            <div class="mb-5">
                                <h4 class="font-black text-slate-800 text-[13px] mb-3 flex items-center gap-2">
                                    <span
                                        class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-black">1</span>
                                    حدد المسافر من القائمة للتسكين:
                                </h4>
                                <div class="flex flex-col gap-2.5">
                                    <template x-for="uid in getPassengerUids().filter(u => !String(u).startsWith('H'))"
                                        :key="uid">
                                        <div
                                            class="flex flex-col gap-2 bg-white p-3 rounded-2xl border border-slate-200 shadow-sm">

                                            <!-- رأس المسافر و كارت الاختيار الرئيسي -->
                                            <div class="flex items-center gap-2 w-full">
                                                <button type="button"
                                                    @click="selectedUnassigned = selectedUnassigned === uid ? null : uid"
                                                    :class="selectedUnassigned === uid ?
                                                        'bg-blue-600 text-white ring-2 ring-blue-300 border-blue-600' :
                                                        'bg-slate-50 text-slate-700 hover:bg-slate-100 border-slate-200'"
                                                    class="px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between flex-1 border">
                                                    <span x-text="passengerNames[uid]"></span>
                                                    <span x-show="selectedUnassigned === uid"
                                                        class="w-2.5 h-2.5 bg-emerald-500 rounded-full border-2 border-white animate-pulse"></span>
                                                </button>

                                                <!-- زر (+) لإضافة رقم مقعد إضافي بواقع مقعد واحد إضافي فقط لكل مسافر -->
                                                @if ($trip->allow_to_add_seat == 1)
                                                    <template
                                                        x-if="!(passengerSeats[uid] && passengerSeats[uid].length >= 2)">
                                                        <button type="button"
                                                            @click="showAddSeatSelect[uid] = !showAddSeatSelect[uid]"
                                                            title="إضافة رقم مقعد إضافي للمسافر"
                                                            class="px-3 h-9 rounded-xl font-black text-xs bg-emerald-50 text-emerald-600 border border-emerald-200 hover:bg-emerald-600 hover:text-white transition-all flex items-center justify-center gap-1.5 shrink-0 whitespace-nowrap">
                                                            <span class="text-base leading-none">+</span>
                                                            <span>مقعد إضافي (+{{ $trip->price }} ر.س)</span> </button>
                                                    </template>
                                                    <template
                                                        x-if="passengerSeats[uid] && passengerSeats[uid].length >= 2">
                                                        <span title="تم إضافة مقعد إضافي لهذا المسافر بالفعل"
                                                            class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-1.5 rounded-xl border border-amber-200 shrink-0">
                                                            مكتمل
                                                        </span>
                                                    </template>
                                                @endif

                                            </div>

                                            <!-- قائمة سريعة (Select) لاختيار المقعد الإضافي -->
                                            <div x-show="showAddSeatSelect[uid] && !(passengerSeats[uid] && passengerSeats[uid].length >= 2)"
                                                x-collapse class="pt-2 border-t border-slate-100">
                                                <label class="block text-[10px] font-bold text-slate-500 mb-1">اختر مقعد
                                                    إضافي (مسموح بمقعد واحد فقط):</label>
                                                <select
                                                    @change="
                                                if (passengerSeats[uid] && passengerSeats[uid].length >= 2) {
                                                    alert('⚠️ مسموح بمقعد إضافي واحد فقط لكل مسافر.');
                                                } else {
                                                    addExtraSeatToPassenger(uid, $event.target.value);
                                                }
                                                $event.target.value = '';
                                                showAddSeatSelect[uid] = false;
                                            "
                                                    class="w-full text-xs font-bold bg-slate-50 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                                                    <option value="">-- اختر رقم المقعد --</option>
                                                    <template x-for="seatNum in getAvailableSeats()"
                                                        :key="seatNum">
                                                        <option :value="seatNum" x-text="'مقعد رقم ' + seatNum">
                                                        </option>
                                                    </template>
                                                </select>
                                            </div>

                                            <!-- عرض المقاعد المخصصة لهذا المسافر حالياً -->
                                            <div x-show="passengerSeats[uid] && passengerSeats[uid].length > 0"
                                                class="flex flex-wrap gap-1.5 pt-1">
                                                <template x-for="(seatNum, idx) in passengerSeats[uid]"
                                                    :key="seatNum">
                                                    <span
                                                        class="inline-flex items-center gap-1 border text-[10px] font-extrabold px-2 py-1 rounded-md"
                                                        :class="idx === 0 ? 'bg-amber-100 text-amber-900 border-amber-300' :
                                                            'bg-purple-100 text-purple-900 border-purple-300'">
                                                        <span
                                                            x-text="(idx === 0 ? '💺 ' : '➕ مقعد إضافي: ') + seatNum"></span>
                                                        <button type="button"
                                                            @click="removeExtraSeatFromPassenger(uid, seatNum)"
                                                            class="text-slate-600 hover:text-red-600 font-bold ml-1">×</button>
                                                    </span>
                                                </template>
                                            </div>

                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div x-show="selectedSeats.filter(s => typeof s === 'number' && s > 0).length > 0"
                                class="pt-5 border-t border-slate-200/60">
                                <h4 class="font-black text-slate-800 text-[13px] mb-3 flex items-center gap-2">
                                    <span
                                        class="w-6 h-6 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-black">2</span>
                                    المقاعد المخصصة (اضغط للإلغاء):
                                </h4>
                                <div class="flex flex-col gap-2">
                                    <template x-for="sid in selectedSeats.filter(s => typeof s === 'number' && s > 0)"
                                        :key="sid">
                                        <button type="button" @click="unseatPassenger(sid)" title="إلغاء المقعد"
                                            class="px-3 py-2.5 rounded-xl text-xs font-bold bg-white border border-slate-300 hover:bg-red-50 hover:text-red-700 hover:border-red-300 transition-all flex items-center justify-between group shadow-sm">
                                            <div class="flex items-center gap-2">
                                                <span class="text-slate-400 group-hover:text-red-400">💺</span>
                                                <span>
                                                    <strong x-text="'مقعد ' + sid"></strong>
                                                    <span class="text-slate-500 font-normal"
                                                        x-text="'(' + getPassengerNameBySeat(sid) + ')'"></span>
                                                </span>
                                            </div>
                                            <span
                                                class="text-red-500 opacity-0 group-hover:opacity-100 text-[9px] bg-red-100 rounded-full w-5 h-5 flex items-center justify-center transition-all">✖</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-8 flex justify-center border-r border-slate-100 pr-8">
                        @if ($trip->transport_type == 'bus')
                            @php
                                $maxAllowedSeats = $trip->total_seats;
                                $totalSeats = isset($trip->total_seats)
                                    ? min($trip->total_seats, $maxAllowedSeats)
                                    : $maxAllowedSeats;

                                $halfSeats =
                                    isset($trip->split_boundary) && $trip->split_boundary > 0
                                        ? intval($trip->split_boundary)
                                        : 28;

                                $isAutoSplit = isset($trip->auto_split) && $trip->auto_split == 1;

                                // تحديد صورة الخلفية ديناميكياً حسب عدد المقاعد
                                $busUrlImage = match ((int) $totalSeats) {
                                    49 => asset('images/BD.jpeg'), // حافلة كبيرة
                                    30 => asset('images/bus_30.jpeg'), // حافلة متوسطة
                                    15 => asset('images/van_15.jpeg'), // ميني باص / فان
                                    7 => asset('images/suv_7.jpeg'), // سيارة عائلية
                                    4 => asset('images/sedan_4.jpeg'), // سيارة سيدان
                                    default => asset('images/BD.jpeg'), // الصورة الافتراضية
                                };

                                $layout_config = [
                                    '...SS',
                                    'SS.SS',
                                    'SS.SS',
                                    'SS.SS',
                                    'SS.SS',
                                    'SS.SS',
                                    'SS.SS',
                                    'SS...',
                                    'SS...',
                                    'SS...',
                                    'SS.SS',
                                    'SS.SS',
                                    'SS.SS',
                                    'SSSSS',
                                ];
                                $seatCounter = 1;
                            @endphp

                            <div class="relative w-full max-w-[380px] pb-10 font-sans" dir="ltr" x-cloak>
                                <div class="relative z-20 mt-16 mb-6 px-6 dashboard-separator pb-10">
                                    <div class="text-center text-[10px] font-bold p-3 rounded-xl border shadow-sm tracking-wide {{ $isAutoSplit ? 'bg-indigo-50/90 text-indigo-950 border-indigo-200' : 'bg-white/90 text-slate-800 border-slate-300' }} backdrop-blur-sm"
                                        dir="rtl">
                                        @if ($isAutoSplit)
                                            <div class="flex items-center justify-center gap-2.5">
                                                <span class="flex items-center gap-1.5">
                                                    <span
                                                        class="w-2.5 h-2.5 bg-blue-600 rounded-sm male-indicator"></span>مقدمة
                                                    للرجال
                                                </span>
                                                <span class="flex items-center gap-1.5">
                                                    <span
                                                        class="w-2.5 h-2.5 bg-pink-500 rounded-sm mr-1 female-indicator"></span>خلفية
                                                    للسيدات
                                                </span>
                                            </div>
                                        @else
                                            <span>توزيع المقاعد متاح حسب الأولوية</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="relative shadow-2xl overflow-hidden z-10 bg-zinc-100 rounded-[2.5rem]"
                                    style="background-image: url('{{ $busUrlImage }}'); background-size: 100% 100%; background-position: top center; background-repeat: no-repeat;">
                                    @php $bookedSeatsMap = array_flip($booked_seats ?? []); @endphp
                                    <div
                                        class="grid grid-cols-5 gap-y-4 gap-x-2 relative z-10 px-7 pr-[3.75rem] pt-[12.25rem] pb-10 seat-grid">
                                        @foreach ($layout_config as $rowString)
                                            @php $cells = str_split($rowString); @endphp
                                            @foreach ($cells as $cellType)
                                                @if (($cellType == 'S' || $cellType == 'X') && $seatCounter <= $totalSeats)
                                                    @php
                                                        $i = $seatCounter++;
                                                        $isStaffSeat = $cellType == 'X';
                                                        $isBooked = $isStaffSeat ? false : isset($bookedSeatsMap[$i]);
                                                        $seatDetail = ($booked_seats_details ?? [])[$i] ?? null;
                                                        $roomType = is_array($seatDetail)
                                                            ? $seatDetail['room_type'] ?? null
                                                            : $seatDetail->room_type ?? null;
                                                        $bookedGender = is_array($seatDetail)
                                                            ? strtolower(trim($seatDetail['gender'] ?? ''))
                                                            : strtolower(trim($seatDetail->gender ?? ''));
                                                        $isFemaleZone = $isAutoSplit && $i > $halfSeats;
                                                        $btnClass = '';
                                                        $iconClass = '';
                                                        $seatShape = $isFemaleZone ? 'female' : 'male';

                                                        if ($isBooked) {
                                                            $isAdditional =
                                                                $roomType === 'additional' ||
                                                                $bookedGender === 'null' ||
                                                                $bookedGender === '';

                                                            if ($isAdditional) {
                                                                $seatShape = 'additional';

                                                                if ($bookedGender === 'female') {
                                                                    $btnClass =
                                                                        'bg-gradient-to-b from-pink-100 to-pink-200 border-pink-400 text-pink-800 cursor-not-allowed';
                                                                    $iconClass = 'text-pink-500';
                                                                } elseif ($bookedGender === 'male') {
                                                                    $btnClass =
                                                                        'bg-gradient-to-b from-blue-100 to-blue-200 border-blue-400 text-blue-800 cursor-not-allowed';
                                                                    $iconClass = 'text-blue-600';
                                                                } else {
                                                                    $btnClass =
                                                                        'bg-gradient-to-b from-slate-100 to-slate-200 border-slate-400 text-slate-800 cursor-not-allowed';
                                                                    $iconClass = 'text-slate-600';
                                                                }
                                                            } elseif (
                                                                $bookedGender === 'female' ||
                                                                ($isFemaleZone && !$bookedGender)
                                                            ) {
                                                                $btnClass =
                                                                    'bg-gradient-to-b from-pink-100 to-pink-200 border-pink-400 text-pink-800 cursor-not-allowed';
                                                                $iconClass = 'text-pink-500';
                                                                $seatShape = 'female';
                                                            } else {
                                                                $btnClass =
                                                                    'bg-gradient-to-b from-blue-100 to-blue-200 border-blue-400 text-blue-800 cursor-not-allowed';
                                                                $iconClass = 'text-blue-600';
                                                                $seatShape = 'male';
                                                            }
                                                        } else {
                                                            $btnClass = $isFemaleZone
                                                                ? 'bg-white border-pink-200 text-pink-700 hover:bg-pink-50'
                                                                : 'bg-white border-blue-200 text-blue-700 hover:bg-blue-50';
                                                            $iconClass = $isFemaleZone
                                                                ? 'text-pink-300'
                                                                : 'text-blue-300';
                                                        }
                                                    @endphp

                                                    @if ($isStaffSeat)
                                                        <button type="button" @click="showBookedError = true"
                                                            class="relative w-full h-12 rounded-t-xl rounded-b-lg bg-slate-200 border-2 border-slate-300 flex flex-col items-center justify-center text-[10px] font-black text-slate-500 shadow-sm">
                                                            <span
                                                                class="text-[9px] text-slate-400 font-bold">{{ $i }}</span>
                                                            <span>كادر</span>
                                                        </button>
                                                    @else
                                                        <button type="button"
                                                            class="relative w-full h-12 rounded-t-xl rounded-b-lg transition-colors duration-150 flex flex-col items-center justify-center border-2 shadow-sm seat-button {{ $isBooked ? $btnClass : '' }}"
                                                            @if ($isBooked) @click="showBookedError = true"
                                                    @else
                                                        @click="
                                                    if (selectedUnassigned) {
                                                        let passGender = passengerGenders[selectedUnassigned];
                                                        let isAuto = {{ $isAutoSplit ? 'true' : 'false' }};
                                                        let isFemZone = {{ $isFemaleZone ? 'true' : 'false' }};       
                                                    
                                                        if (isAuto && passGender === 'male' && isFemZone) {
                                                            alertMessage = '⚠️ عذراً، لا يمكن تسكين مسافر (ذكر) في القسم المخصص للسيدات.';
                                                            showAlertModal = true;
                                                        } else if (isAuto && passGender === 'female' && !isFemZone) {
                                                            alertMessage = '⚠️ عذراً، لا يمكن تسكين مسافرة (أنثى) في القسم المخصص للرجال.';
                                                            showAlertModal = true;
                                                        } else if (passengerSeats[selectedUnassigned] && passengerSeats[selectedUnassigned].length >= 1 && !allowExtraSeat && !passengerSeats[selectedUnassigned].includes({{ $i }})) {
    alertMessage = '⚠️ هذا المسافر لديه مقعد بالفعل، ولا يمكن إضافة مقعد إضافي لأن الميزة غير مفعّلة في هذه الرحلة.';
    showAlertModal = true;
} else if (passengerSeats[selectedUnassigned] && passengerSeats[selectedUnassigned].length >= 2 && !passengerSeats[selectedUnassigned].includes({{ $i }})) {
    alertMessage = '⚠️ هذا المسافر لديه مقعد أساسي ومقعد إضافي بالفعل.';
    showAlertModal = true;
} else {
    assignSeatToSelectedPassenger({{ $i }});
}
                                                    } else {
                                                        alertMessage = '⚠️ يرجى تحديد المسافر من القائمة أولاً لتسكينه في هذا المقعد.';
                                                        showAlertModal = true;
                                                    }
                                                        " @endif
                                                            @if (!$isBooked) :class="isSelected({{ $i }}) ? 'bg-gradient-to-b from-amber-300 to-amber-500 border-amber-600 text-amber-950 shadow-md' : '{{ $btnClass }}'" @endif>
                                                            <div
                                                                class="absolute left-[2px] top-2 bottom-2 w-1 bg-black/10 rounded-full">
                                                            </div>
                                                            <div
                                                                class="absolute right-[2px] top-2 bottom-2 w-1 bg-black/10 rounded-full">
                                                            </div>
                                                            <div
                                                                class="absolute top-[2px] w-5 h-1.5 bg-black/10 rounded-full">
                                                            </div>
                                                            <span
                                                                class="text-[11px] font-black z-10 mb-0.5">{{ $i }}</span>

                                                            <div class="flex flex-col items-center scale-90"
                                                                :class="isSelected({{ $i }}) ? 'text-amber-100' :
                                                                    '{{ $iconClass }}'">

                                                                @if ($seatShape === 'additional')
                                                                    <div
                                                                        class="relative w-4 h-4 flex items-center justify-center my-0.5">
                                                                        <div
                                                                            class="absolute w-4 h-1 bg-current rounded-full">
                                                                        </div>
                                                                        <div
                                                                            class="absolute h-4 w-1 bg-current rounded-full">
                                                                        </div>
                                                                    </div>
                                                                @elseif ($seatShape === 'female')
                                                                    <div class="w-3 h-3 bg-current rounded-full"></div>
                                                                    <div
                                                                        class="w-[18px] h-2 bg-current rounded-t-full -mt-1">
                                                                    </div>
                                                                @else
                                                                    <div class="w-2.5 h-2.5 bg-current rounded-sm"></div>
                                                                    <div class="w-4 h-2 bg-current rounded-t-md -mt-0.5">
                                                                    </div>
                                                                @endif

                                                            </div>
                                                        </button>
                                                    @endif
                                                @elseif ($cellType == '.')
                                                    <div class="w-full h-12"></div>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <div
                                class="w-full max-w-xl mx-auto border-x-[12px] border-t-[40px] border-slate-100 rounded-t-[10rem] p-8 relative shadow-inner bg-white">
                                <div class="grid grid-cols-7 gap-y-4 items-center justify-items-center">
                                    @php $bookedSeatsMap = array_flip($booked_seats ?? []); @endphp
                                    @for ($i = 1; $i <= 90; $i++)
                                        @php
                                            $isBooked = isset($bookedSeatsMap[$i]);
                                            $colorClass = $isBooked
                                                ? 'bg-slate-400 border-slate-500 text-white cursor-not-allowed'
                                                : '';
                                        @endphp
                                        <button type="button"
                                            @if ($isBooked) @click="showBookedError = true" 
                                    @else 
                                        @click="
                                            if (selectedUnassigned) {
                                                if (passengerSeats[selectedUnassigned] && passengerSeats[selectedUnassigned].length >= 2 && !passengerSeats[selectedUnassigned].includes({{ $i }})) {
                                                    alertMessage = '⚠️ هذا المسافر لديه مقعد أساسي ومقعد إضافي بالفعل.';
                                                    showAlertModal = true;
                                                } else {
                                                    assignSeatToSelectedPassenger({{ $i }});
                                                }
                                            } else {
                                                alertMessage = '⚠️ يرجى تحديد المسافر من القائمة أولاً لتسكينه في هذا المقعد.';
                                                showAlertModal = true;
                                            }
                                        " @endif
                                            class="w-11 h-11 rounded-xl font-bold text-xs transition-all duration-150 flex flex-col items-center justify-center border-2 relative {{ $isBooked ? $colorClass : '' }}"
                                            @if (!$isBooked) :class="isSelected({{ $i }}) ? 'bg-gradient-to-b from-amber-300 to-amber-500 border-amber-600 text-amber-950 shadow-md scale-95' : 'bg-slate-50 border-slate-200/60 text-slate-700 hover:bg-slate-100'" @endif>
                                            <span>{{ $i }}</span>
                                        </button>
                                        @if ($i % 6 == 3)
                                            <div
                                                class="text-center text-[9px] font-black text-slate-200 uppercase rotate-90 select-none">
                                                ممر</div>
                                        @endif
                                    @endfor
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex justify-between mx-auto mt-12 pt-6 border-t border-slate-100">
                    <button @click="step = 2; window.scrollTo({ top: 0, behavior: 'smooth' });"
                        class="bg-slate-100 text-slate-700 px-6 py-3.5 rounded-2xl font-black hover:bg-slate-200 transition-all active:scale-95 flex items-center gap-2">
                        ➡️ السابق
                    </button>
                    <button @click="step = 4; window.scrollTo({ top: 0, behavior: 'smooth' });"
                        class="bg-slate-900 text-white px-8 py-3.5 rounded-2xl font-black hover:bg-slate-800 transition-all shadow-lg active:scale-95 flex items-center gap-2">
                        التالي: الحساب والدفع ⬅️
                    </button>
                </div>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- الخطوة 4: ملخص الحساب والدفع --}}
        {{-- ========================================== --}}
        <div x-show="step === 4" style="display: none;" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">

            <!-- مدخل خفي لحفظ بيانات المسافرين النهائية عند الإرسال -->
            <input type="hidden" name="passengers_payload" id="passengers_payload">
            <input type="hidden" name="booking_id" :value="existingBookingId">

            <div class="max-w-3xl mx-auto bg-white rounded-[2.5rem] p-8 text-slate-800 shadow-xl border border-slate-100">
                <h3 class="text-xl font-black mb-6 flex items-center gap-2 text-slate-900">
                    <span class="p-2 bg-emerald-50 text-emerald-600 rounded-xl text-sm">💳</span> ملخص الحساب والدفع
                </h3>

                <!-- قسم ملخص الحساب -->
                <div class="space-y-4 bg-slate-50/70 p-6 rounded-3xl mb-6 border border-slate-200/60">
                    <!-- المقاعد المختارة/المسافرين -->
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500 font-bold tracking-widest uppercase">المقاعد المختارة/المسافرين:</span>
                        <span class="font-black text-slate-900"
                            x-text="(selectedSeats.filter(s => Number(s) > 0).length > 0) ? selectedSeats.filter(s => Number(s) > 0).sort((a,b)=>a-b).join('، ') : 'لم يتم الاختيار'"></span>
                    </div>

                    <div class="flex justify-between items-start text-xs flex-col gap-1">
                        <span class="text-slate-500 font-bold tracking-widest uppercase">الغرف المحجوزة:</span>
                        <div class="flex flex-wrap gap-1 mt-1">
                            <template x-for="rm in bookedRoomsList" :key="rm.uid">
                                <span
                                    class="bg-blue-50 text-blue-600 border border-blue-100/70 px-2 py-0.5 rounded-md font-black text-[10px]"
                                    x-text="rm.name"></span>
                            </template>
                            <template x-if="bookedRoomsList.length === 0">
                                <span
                                    class="text-amber-600 font-bold bg-amber-50 px-2 py-0.5 rounded-md border border-amber-100 text-[10px]">توزيع
                                    مشترك / تسكين أساسي</span>
                            </template>
                        </div>
                    </div>

                    <!-- المقاعد الزائدة عن السكن -->
                    <div class="flex justify-between items-center text-xs pt-2 border-t border-slate-200/60">
                        <span class="text-slate-500 font-bold tracking-widest uppercase">تكلفة السكن:</span>
                        <span class="font-black text-slate-900" x-text="priceBreakdown.roomsTotal + ' ⃁'"></span>
                    </div>
                    <div class="flex justify-between items-center text-xs pt-2 border-t border-slate-200/60">
                        <span class="text-slate-500 font-bold tracking-widest uppercase">المقاعد الإضافية / بدون
                            سكن:</span>
                        <span class="font-black text-slate-900"
                            x-text="(priceBreakdown.extraSeatCount * seatPrice) + ' ⃁'"></span>
                    </div>

                    <div class="flex justify-between items-center text-xs pt-2 border-t border-slate-200/60"
                        x-show="totalMealsPrice > 0">
                        <span class="text-slate-500 font-bold tracking-widest uppercase">إجمالي تكلفة الوجبات:</span>
                        <span class="font-black text-slate-900" x-text="totalMealsPrice + ' ⃁'"></span>
                    </div>

                    <!-- قسم عرض الخدمات الإضافية التراكمي في ملخص الحساب -->
                    <div class="pt-2 border-t border-slate-200/60 space-y-2"
                        x-show="getPassengerUids().some(uid => getPassengerServicesDetails(uid).length > 0)">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-500 font-bold tracking-widest uppercase flex items-center gap-1">
                                <span>✨</span> الخدمات الإضافية:
                            </span>
                            <span class="font-black text-blue-600"
                                x-text="getPassengerUids().reduce((total, uid) => total + getPassengerServicesDetails(uid).reduce((sub, s) => sub + (Number(s.price) || 0), 0), 0) + ' ⃁'"></span>
                        </div>

                        <!-- قائمة الشارات لكل خدمة مع سعرها -->
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="uid in getPassengerUids()" :key="'summary-uid-' + uid">
                                <template x-for="service in getPassengerServicesDetails(uid)"
                                    :key="'summary-svc-' + uid + '-' + (service.id || service.name)">
                                    <span
                                        class="bg-blue-50 text-blue-700 border border-blue-200/70 px-2 py-0.5 rounded-md text-[10px] font-bold flex items-center gap-1.5">
                                        <span x-text="service.name"></span>
                                        <span class="text-blue-400 font-normal">|</span>
                                        <span class="font-black"
                                            x-text="service.description || (service.price + ' ⃁')"></span>
                                    </span>
                                </template>
                            </template>
                        </div>
                    </div>

                    <div class="flex justify-between items-center text-xs pt-2 border-t border-dashed border-emerald-200"
                        x-show="totalSavings() > 0">
                        <span class="text-emerald-700 font-bold flex items-center gap-1">✨ وفرت من باقة الطعام :</span>
                        <span
                            class="font-black text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100"
                            x-text="'-' + totalSavings() + ' ⃁'"></span>
                    </div>

                    <!-- خصم حجز السكن فقط -->
                    <div class="flex justify-between items-center text-xs pt-2 border-t border-dashed border-emerald-200"
                        x-show="getPassengerUids().filter(s => String(s).startsWith('H')).length > 0 && accommodationOnlyDiscount > 0"
                        <span class="text-emerald-700 font-bold flex items-center gap-1">🏨 خصم حجز السكن فقط:</span>
                        <span
                            class="font-black text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100"
                            x-text="'-' + (getPassengerUids().filter(s => String(s).startsWith('H')).length * accommodationOnlyDiscount) + ' ⃁'"></span>
                    </div>

                    <div class="pt-4 border-t border-slate-200 flex justify-between items-end">
                        <span class="text-slate-500 text-xs font-bold">المبلغ الإجمالي:</span>
                        <div class="text-left">
                            <p class="text-3xl font-black text-slate-900 flex items-center gap-1 justify-end">
                                <span x-text="totalPrice"></span><span class="text-xl font-normal text-slate-500">⃁</span>
                            </p>
                            <p class="text-[10px] text-blue-600 font-bold tracking-widest uppercase mt-0.5">شامل الغرف،
                                المقاعد، الوجبات، والخدمات</p>
                        </div>
                    </div>
                </div>

                <!-- تفاصيل المسافرين، الوجبات، والخدمات الإضافية لكل مسافر -->
                <div class="bg-slate-50/70 p-6 rounded-3xl mb-6 border border-slate-200/60">
                    <h4 class="text-sm font-black text-slate-800 mb-4 flex items-center gap-2">
                        <span class="p-1.5 bg-blue-50 text-blue-600 rounded-lg text-xs">👥</span> تفاصيل المسافرين والخدمات
                        المختارة
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                        <template x-for="uid in getPassengerUids()" :key="uid">
                            <div
                                class="bg-white border border-slate-200 p-3.5 rounded-2xl flex flex-col gap-2 shadow-sm transition-all hover:shadow-md">
                                <div class="flex justify-between items-start border-b border-slate-100 pb-2">
                                    <div>
                                        <!-- اسم المسافر وهويته -->
                                        <span class="font-bold text-sm text-slate-800 block"
                                            x-text="passengerNames[uid] || 'مسافر'"></span>
                                        <span class="text-[10px] text-slate-500 font-medium mt-0.5 block"
                                            x-text="passengerIds[uid] ? 'هوية: ' + passengerIds[uid] : 'لم يتم إدخال الهوية'"></span>

                                        <!-- 🛏️ إضافة شارة نوع الغرفة/السكن المخصص لكل مسافر -->
                                        <div class="mt-1.5" x-show="getPassengerRoomLabel(uid)">
                                            <span
                                                class="inline-flex items-center gap-1 bg-slate-100 text-slate-700 border border-slate-200 px-2 py-0.5 rounded-md text-[10px] font-bold">
                                                🛏️ <span x-text="getPassengerRoomLabel(uid)"></span>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- عرض أرقام المقاعد الخاص بالمسافر -->
                                    <div class="flex flex-wrap gap-1 justify-end max-w-[50%]">
                                        <template x-if="passengerSeats[uid] && passengerSeats[uid].length > 0">
                                            <template x-for="seatNum in passengerSeats[uid]"
                                                :key="'p-seat-' + uid + '-' + seatNum">
                                                <span
                                                    class="text-[10px] font-black px-2 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200"
                                                    x-text="String(seatNum).startsWith('H') ? 'سكن فقط 🏨' : 'مقعد ' + seatNum + ' 💺'"></span>
                                            </template>
                                        </template>

                                        <template x-if="!passengerSeats[uid] || passengerSeats[uid].length === 0">
                                            <span
                                                class="text-[10px] font-black px-2 py-1 rounded-lg bg-slate-100 text-slate-600">بانتظار
                                                المقعد ⏳</span>
                                        </template>
                                    </div>
                                </div>

                                <!-- قسم الوجبات مرتبط بـ uid المسافر -->
                                <div class="flex flex-col gap-1.5 pt-1">
                                    <span
                                        class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">الوجبات:</span>
                                    <div x-show="passengerMeals[uid] && passengerMeals[uid].wants_meal"
                                        class="flex flex-wrap gap-1.5">
                                        <template x-if="passengerMeals[uid]?.package_name">
                                            <span
                                                class="bg-indigo-50 text-indigo-700 border border-indigo-100 px-2 py-0.5 rounded-md text-[10px] font-black flex items-center gap-1">📦
                                                <span x-text="passengerMeals[uid].package_name"></span>
                                            </span>
                                        </template>
                                        <template
                                            x-if="!passengerMeals[uid]?.package_name && ((passengerMeals[uid]?.breakfast && passengerMeals[uid]?.lunch) || (passengerMeals[uid]?.lunch && passengerMeals[uid]?.dinner) || (passengerMeals[uid]?.breakfast && passengerMeals[uid]?.dinner))">
                                            <span
                                                class="bg-emerald-50 text-emerald-700 border border-emerald-100 px-2 py-0.5 rounded-md text-[10px] font-black flex items-center gap-1">✨
                                                باقة مدمجة</span>
                                        </template>
                                        <div class="flex flex-wrap gap-1">
                                            <template x-if="passengerMeals[uid]?.breakfast"><span
                                                    class="bg-amber-50 text-amber-800 border border-amber-200/70 px-1.5 py-0.5 rounded text-[10px] font-bold">🍳
                                                    إفطار</span></template>
                                            <template x-if="passengerMeals[uid]?.lunch"><span
                                                    class="bg-orange-50 text-orange-800 border border-orange-200/70 px-1.5 py-0.5 rounded text-[10px] font-bold">🍔
                                                    غداء</span></template>
                                            <template x-if="passengerMeals[uid]?.dinner"><span
                                                    class="bg-purple-50 text-purple-800 border border-purple-200/70 px-1.5 py-0.5 rounded text-[10px] font-bold">🥩
                                                    عشاء</span></template>
                                        </div>
                                    </div>
                                    <div x-show="!passengerMeals[uid] || !passengerMeals[uid].wants_meal">
                                        <span
                                            class="text-[10px] text-slate-400 font-bold bg-slate-50 px-2 py-0.5 rounded border border-slate-100">بدون
                                            وجبات إضافية ❌</span>
                                    </div>
                                </div>

                                <!-- قسم الخدمات الإضافية للمسافر -->
                                <div class="flex flex-col gap-1.5 pt-2 border-t border-slate-100"
                                    x-show="getPassengerServicesDetails(uid).length > 0">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">الخدمات
                                        الإضافية:</span>
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="service in getPassengerServicesDetails(uid)"
                                            :key="service.id || service.name">
                                            <span
                                                class="bg-blue-50 text-blue-700 border border-blue-200/70 px-1.5 py-0.5 rounded text-[10px] font-bold flex items-center gap-1">
                                                <span>✨</span>
                                                <span x-text="service.name"></span>
                                                <span class="text-blue-400 font-normal">|</span>
                                                <span class="font-black" x-text="service.price + ' ⃁'"></span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="bg-slate-50/70 p-6 rounded-3xl mb-6 border border-slate-200/60 space-y-3">
                    <h4 class="text-sm font-black text-slate-800 flex items-center gap-2">
                        <span class="p-1.5 bg-amber-50 text-amber-600 rounded-lg text-xs">📝</span> ملاحظات للوكالة
                        (اختياري)
                    </h4>
                    <textarea x-model="agencyNote" maxlength="500" rows="3"
                        placeholder="مثال: أحتاج سرير إضافي في الغرفة الثلاثية، أو غرفة خماسية..."
                        class="w-full border border-slate-200 bg-white rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10"></textarea>
                    <div class="flex justify-between items-start gap-3">
                        <p class="text-[10px] font-bold text-slate-400 leading-relaxed">
                            الملاحظة لا تغيّر مبلغ الحجز الآن. ستراجعها الوكالة، وإن لزم فرق سعر سترسل لك رابط دفع إضافي.
                        </p>
                        <span class="text-[10px] font-mono text-slate-400 shrink-0"
                            x-text="(agencyNote || '').length + '/500'"></span>
                    </div>
                </div>

                <!-- تنبيه المسافرين بدون مقاعد -->
                <div x-show="getUnassignedPassengers().length > 0"
                    class="mb-6 p-4 bg-red-50 border border-red-200 rounded-2xl text-red-700 flex items-start gap-3">
                    <span class="text-xl">⚠️</span>
                    <div>
                        <h4 class="font-bold text-sm">تنبية: يوجد مسافرين لم يتم تخصيص مقاعد لهم!</h4>
                        <p class="text-xs mt-1">الرجاء العودة للخطوة السابقة وتحديد مقاعد لجميع المسافرين المضافين قبل
                            المتابعة للدفع.</p>
                    </div>
                </div>

                <!-- زر تأكيد الحجز -->
                @if (Auth::check() && Auth::user()->is_verified === 1)
                    <button type="button" @click="proceed()"
                        :disabled="getPassengerUids().length === 0 || getUnassignedPassengers().length > 0"
                        :class="(getPassengerUids().length === 0 || getUnassignedPassengers().length > 0) ?
                        'opacity-50 cursor-not-allowed bg-slate-400' :
                        'bg-blue-600 hover:bg-blue-700 shadow-blue-600/10 active:scale-95'"
                        class="w-full py-4 text-white rounded-2xl font-black text-base transition-all shadow-lg">
                        {{ isset($trip->is_conditional) && $trip->is_conditional
                            ? 'تأكيد طلب الحجز المشروط 💳'
                            : 'تأكيد الحجز والدفع 💳' }}
                    </button>
                @else
                    <a href="{{ route('client.profile') }}"
                        class="w-full block text-center py-4 bg-amber-500 hover:bg-amber-600 text-white rounded-2xl font-black text-base transition-all shadow-lg shadow-amber-500/10 active:scale-95">
                        {{ Auth::check() ? 'توثيق الحساب لتأكيد الحجز' : 'تسجيل الدخول لتأكيد الحجز' }}
                    </a>
                @endif
            </div>

            <div class="flex justify-start max-w-3xl mx-auto mt-6">
                <button type="button" @click="step = 3"
                    class="bg-slate-100 text-slate-700 px-6 py-3.5 rounded-2xl font-black hover:bg-slate-200 transition-all active:scale-95 flex items-center gap-2">
                    ➡️ السابق: توزيع المقاعد
                </button>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- النوافذ المنبثقة (Modals) --}}
        {{-- ========================================== --}}

        {{-- 1️⃣ نافذة إدخال بيانات المسافر --}}
        <div x-show="openPassengerModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 backdrop-blur-0" x-transition:enter-end="opacity-100 backdrop-blur-md"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 backdrop-blur-md"
            x-transition:leave-end="opacity-0 backdrop-blur-0"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/60"
            @click="openPassengerModal = false" x-cloak>

            <div x-show="openPassengerModal" x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-90 translate-y-6"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-90 translate-y-6"
                class="bg-white/95 border border-slate-100 shadow-2xl rounded-[2.5rem] p-6 md:p-8 max-w-[520px] w-full max-h-[88vh] flex flex-col text-right text-slate-800 relative overflow-hidden backdrop-blur-xl"
                @click.stop dir="rtl">

                <div
                    class="absolute -top-16 -right-16 w-40 h-40 bg-gradient-to-br from-blue-400/20 to-indigo-500/20 rounded-full blur-3xl pointer-events-none">
                </div>
                <div
                    class="absolute -bottom-16 -left-16 w-40 h-40 bg-gradient-to-tr from-indigo-400/20 to-pink-500/20 rounded-full blur-3xl pointer-events-none">
                </div>

                <template x-if="activeModalSeat !== null">
                    <div class="flex flex-col flex-1 overflow-hidden relative z-10" x-data="{ showValidation: false }"
                        x-effect="if (activeModalSeat !== null) { 
            showValidation = false;
            if (typeof passengerMeals === 'undefined' || !passengerMeals) passengerMeals = {};
            if (typeof passengerNames === 'undefined' || !passengerNames) passengerNames = {};
            if (typeof passengerIds === 'undefined' || !passengerIds) passengerIds = {};
            if (typeof passengerGenders === 'undefined' || !passengerGenders) passengerGenders = {};
            if (typeof seatRoomAssignments === 'undefined' || !seatRoomAssignments) seatRoomAssignments = {};
            if (typeof sharedRoomTypes === 'undefined' || !sharedRoomTypes) sharedRoomTypes = {};

            if (!(activeModalSeat in passengerMeals)) passengerMeals = { ...passengerMeals, [activeModalSeat]: { wants_meal: false, breakfast: false, lunch: false, dinner: false, package_name: null, package_price: 0 } }; 
            if (!(activeModalSeat in passengerNames)) passengerNames = { ...passengerNames, [activeModalSeat]: '' }; 
            if (!(activeModalSeat in passengerIds)) passengerIds = { ...passengerIds, [activeModalSeat]: '' }; 
            if (!(activeModalSeat in passengerGenders)) passengerGenders = { ...passengerGenders, [activeModalSeat]: '' }; 
            if (!(activeModalSeat in seatRoomAssignments)) seatRoomAssignments = { ...seatRoomAssignments, [activeModalSeat]: '' }; 
            if (!(activeModalSeat in sharedRoomTypes)) sharedRoomTypes = { ...sharedRoomTypes, [activeModalSeat]: '' }; 
         }">

                        <div
                            class="flex justify-between items-center border-b border-slate-100/80 pb-4 mb-4 flex-shrink-0">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-11 h-11 flex items-center justify-center bg-gradient-to-tr from-blue-600 to-indigo-600 text-white rounded-2xl text-lg shadow-md shadow-blue-500/20 font-black">
                                    💺
                                </div>
                                <div>
                                    <h3 class="text-base font-black text-slate-900 leading-snug">
                                        بيانات <span
                                            x-text="activeModalSeat && String(activeModalSeat).startsWith('H') ? 'مسافر السكن فقط' : 'المسافر'"
                                            class="text-blue-600"></span>
                                    </h3>
                                    <p class="text-[11px] font-bold text-slate-400 mt-0.5">
                                        رمز الموائم: <span class="text-indigo-600 font-extrabold"
                                            x-text="activeModalSeat && String(activeModalSeat).startsWith('H') ? 'سكن فقط 🏨' : (activeModalSeat < 0 ? 'بانتظار المقعد ⏳' : 'مقعد رقم ' + activeModalSeat)"></span>
                                    </p>
                                </div>
                            </div>
                            <button type="button" @click="openPassengerModal = false"
                                class="w-9 h-9 flex items-center justify-center rounded-2xl bg-slate-100/80 text-slate-400 hover:text-slate-700 hover:bg-slate-200/80 transition-all text-xs font-black shadow-2xs">
                                ✕
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto space-y-5 pl-1.5 pr-0.5 pb-2 min-h-0 custom-scrollbar">

                            <div class="p-3.5 rounded-2xl border text-xs flex items-center gap-3 transition-all shadow-2xs"
                                :class="activeModalSeat && String(activeModalSeat).startsWith('H') ?
                                    'bg-amber-50/80 border-amber-200/70 text-amber-900' : (
                                        typeof checkIncluded === 'function' && checkIncluded() ?
                                        'bg-emerald-50/80 border-emerald-200/70 text-emerald-900' :
                                        'bg-blue-50/80 border-blue-200/70 text-blue-900')">
                                <span class="text-base"
                                    x-text="activeModalSeat && String(activeModalSeat).startsWith('H') ? '🏨' : (typeof checkIncluded === 'function' && checkIncluded() ? '✨' : '➕')"></span>
                                <div class="font-bold text-[11px] leading-relaxed">
                                    حالة المقعد / التكلفة:
                                    <span class="font-extrabold text-indigo-700"
                                        x-text="typeof getSeatStatusText === 'function' ? (getSeatStatusText() || 'مشمول في الحجز الأساسي') : 'مشمول في الحجز الأساسي'"></span>
                                </div>
                            </div>

                            <div class="space-y-4" x-show="!isEmptySeat?.[activeModalSeat]">
                                <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200/60 space-y-3.5">
                                    <h4 class="text-xs font-black text-slate-700 flex items-center gap-1.5">
                                        <span>👤</span> البيانات الشخصية للمسافر
                                    </h4>

                                    <div>
                                        <label class="text-[11px] text-slate-500 mb-1 block font-bold">
                                            الاسم الكامل: <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" x-model="passengerNames[activeModalSeat]"
                                            placeholder="اكتب الاسم كما في بطاقة الهوية"
                                            class="w-full border rounded-xl px-3.5 py-2.5 text-xs transition-all outline-none font-bold text-slate-800 placeholder-slate-400"
                                            :class="showValidation && !(passengerNames[activeModalSeat] || '').trim() ?
                                                'bg-red-50 border-red-400 focus:border-red-500' :
                                                'bg-white border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10'">
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="text-[11px] text-slate-500 mb-1 block font-bold">
                                                رقم الهوية / الإقامة: <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" x-model="passengerIds[activeModalSeat]" maxlength="10"
                                                @input="passengerIds[activeModalSeat] = passengerIds[activeModalSeat].replace(/\D/g, '')"
                                                placeholder="10 أرقام"
                                                class="w-full border rounded-xl px-3.5 py-2.5 text-xs transition-all outline-none font-bold text-slate-800 tracking-wider placeholder-slate-400"
                                                :class="showValidation && (passengerIds[activeModalSeat] || '').trim()
                                                    .length !== 10 ?
                                                    'bg-red-50 border-red-400 focus:border-red-500' :
                                                    'bg-white border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10'">
                                        </div>

                                        <div
                                            x-effect="if (activeModalSeatZone === 'male') passengerGenders = { ...passengerGenders, [activeModalSeat]: 'male' }; if (activeModalSeatZone === 'female') passengerGenders = { ...passengerGenders, [activeModalSeat]: 'female' };">
                                            <label class="text-[11px] text-slate-500 mb-1 block font-bold">
                                                الجنس: <span class="text-red-500">*</span>
                                            </label>
                                            <div class="grid grid-cols-2 gap-2 rounded-xl transition-all"
                                                :class="showValidation && !(passengerGenders[activeModalSeat] || '').trim() ?
                                                    'ring-2 ring-red-400 p-0.5 rounded-xl' : ''">
                                                <button type="button"
                                                    @click="passengerGenders = { ...passengerGenders, [activeModalSeat]: 'male' }"
                                                    :disabled="activeModalSeatZone === 'female'"
                                                    class="flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs font-bold transition-all border select-none"
                                                    :class="passengerGenders[activeModalSeat] === 'male' ?
                                                        'bg-blue-600 border-blue-600 text-white shadow-sm' :
                                                        'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">
                                                    <span>ذكر 👨</span>
                                                </button>

                                                <button type="button"
                                                    @click="passengerGenders = { ...passengerGenders, [activeModalSeat]: 'female' }"
                                                    :disabled="activeModalSeatZone === 'male'"
                                                    class="flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs font-bold transition-all border select-none"
                                                    :class="passengerGenders[activeModalSeat] === 'female' ?
                                                        'bg-pink-500 border-pink-500 text-white shadow-sm' :
                                                        'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">
                                                    <span>أنثى 👩</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- 🚌/🏨 نوع الحجز للمسافر --}}
                                <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200/60 space-y-3"
                                    x-show="!isAdditionalPassenger(activeModalSeat)">
                                    <h4 class="text-xs font-black text-slate-700 flex items-center gap-1.5">
                                        <span>🧭</span> نوع الحجز لهذا المسافر
                                    </h4>

                                    <div class="grid grid-cols-2 gap-2">
                                        <button type="button" @click="convertPassengerType(activeModalSeat, false)"
                                            :class="!String(activeModalSeat).startsWith('H') ?
                                                'border-blue-600 bg-blue-600 text-white shadow-sm' :
                                                'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                                            class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl border text-xs font-bold transition-all">
                                            🚌 نقل وسكن
                                        </button>

                                        @if ($trip->allow_accommodation_only ?? true)
                                            <button type="button" @click="convertPassengerType(activeModalSeat, true)"
                                                :class="String(activeModalSeat).startsWith('H') ?
                                                    'border-amber-500 bg-amber-500 text-white shadow-sm' :
                                                    'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                                                class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl border text-xs font-bold transition-all">
                                                🏨 سكن فقط
                                            </button>
                                        @endif
                                    </div>

                                    <p class="text-[10px] font-bold text-slate-400 leading-relaxed"
                                        x-text="String(activeModalSeat).startsWith('H')
            ? 'المسافر لن يركب الحافلة ولن يُحجز له مقعد، ويُسكَّن في غرفة خاصة فقط.'
            : 'المسافر يركب الحافلة ويُخصص له مقعد في الخطوة التالية.'">
                                    </p>
                                </div>

                                <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200/60 space-y-3">
                                    <h4 class="text-xs font-black text-slate-700 flex items-center gap-1.5">
                                        <span>🏨</span> خيار التسكين والغرفة <span class="text-red-500">*</span>
                                    </h4>

                                    <div x-show="!seatRoomAssignments?.[activeModalSeat] || seatRoomAssignments[activeModalSeat] === ''"
                                        class="mb-3 p-3 bg-red-50 border-2 border-dashed border-red-300 rounded-xl flex items-center justify-center gap-3 animate-pulse">
                                        <span class="text-xl">👈</span>
                                        <span class="text-[11px] font-black text-red-700">يرجى تحديد خيار السكن من الأسفل
                                            لإتمام تسجيل المسافر (إجباري)</span>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 rounded-xl transition-all"
                                        :class="showValidation && (!seatRoomAssignments?.[activeModalSeat] ||
                                                seatRoomAssignments[activeModalSeat] === '') ?
                                            'ring-2 ring-red-400 p-1.5 rounded-xl bg-red-50/30' : ''">

                                        @if ($trip->allows_shared_rooms)
                                            <button type="button"
                                                @click="seatRoomAssignments = { ...seatRoomAssignments, [activeModalSeat]: seatRoomAssignments?.[activeModalSeat] === 'shared' ? null : 'shared' }"
                                                :class="seatRoomAssignments?.[activeModalSeat] === 'shared' ?
                                                    'border-indigo-600 bg-indigo-50/80 text-indigo-700 shadow-sm' :
                                                    'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                                                class="flex items-center justify-between border rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all">
                                                <span>مشترك مع العموم 🎲</span>
                                            </button>

                                            <div x-show="seatRoomAssignments?.[activeModalSeat] === 'shared'" x-transition
                                                class="col-span-1 sm:col-span-2 mt-2 p-3 bg-indigo-50/50 border border-indigo-100 rounded-xl space-y-2">

                                                <label class="text-[11px] text-slate-600 font-bold block">
                                                    حدد نوع الغرفة المشتركة: <span class="text-red-500">*</span>
                                                </label>

                                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                    <template x-for="type in getAvailableSharedRoomTypes()"
                                                        :key="type.id">
                                                        <button type="button"
                                                            @click="sharedRoomTypes = { ...sharedRoomTypes, [activeModalSeat]: type.id }"
                                                            :class="sharedRoomTypes?.[activeModalSeat] === type.id ?
                                                                'bg-indigo-600 text-white border-indigo-600 shadow-sm' :
                                                                'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                                            class="py-2 border rounded-xl text-xs font-bold transition-all"
                                                            x-text="type.name">
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        @endif

                                        <template x-for="rm in bookedRoomsList" :key="rm.uid">
                                            <button type="button"
                                                @click="
                                           let cap = rm.capacity ? parseInt(rm.capacity) : (rm.uid.includes('single') ? 1 : rm.uid.includes('triple') ? 3 : rm.uid.includes('quad') ? 4 : 2);
                                           let currentCount = Object.values(seatRoomAssignments || {}).filter(v => v === rm.uid).length;
                                           if (seatRoomAssignments?.[activeModalSeat] === rm.uid) {
                                                seatRoomAssignments = { ...seatRoomAssignments, [activeModalSeat]: null };
                                           } else if (currentCount < cap) {
                                                seatRoomAssignments = { ...seatRoomAssignments, [activeModalSeat]: rm.uid };
                                           }
                                        "
                                                :disabled="(Object.values(seatRoomAssignments || {}).filter(v => v === rm.uid)
                                                    .length >=
                                                    (rm.capacity ? parseInt(rm.capacity) : (rm.uid.includes('single') ?
                                                        1 : rm.uid.includes('triple') ? 3 : rm.uid.includes(
                                                            'quad') ? 4 : 2))) && seatRoomAssignments?.[
                                                    activeModalSeat
                                                ] !== rm.uid"
                                                :class="{
                                                    'border-indigo-600 bg-indigo-50/80 text-indigo-700 shadow-sm': seatRoomAssignments
                                                        ?.[activeModalSeat] === rm.uid,
                                                    'border-slate-200 bg-white hover:bg-slate-50': seatRoomAssignments
                                                        ?.[activeModalSeat] !== rm.uid && Object.values(
                                                            seatRoomAssignments || {}).filter(v => v === rm.uid)
                                                        .length < (rm.capacity ? parseInt(rm.capacity) : (rm.uid
                                                            .includes('single') ? 1 : rm.uid.includes('triple') ?
                                                            3 : rm.uid.includes('quad') ? 4 : 2)),
                                                    'border-slate-100 bg-slate-100 text-slate-400 opacity-60 cursor-not-allowed': seatRoomAssignments
                                                        ?.[activeModalSeat] !== rm.uid && Object.values(
                                                            seatRoomAssignments || {}).filter(v => v === rm.uid)
                                                        .length >= (rm.capacity ? parseInt(rm.capacity) : (rm.uid
                                                            .includes('single') ? 1 : rm.uid.includes('triple') ?
                                                            3 : rm.uid.includes('quad') ? 4 : 2))
                                                }"
                                                class="flex items-center justify-between border rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all">
                                                <div class="flex items-center gap-1.5"><span x-text="rm.name"></span>
                                                </div>
                                                <span
                                                    :class="seatRoomAssignments?.[activeModalSeat] === rm.uid ?
                                                        'bg-indigo-200/70 text-indigo-900' :
                                                        'bg-slate-100 text-slate-500'"
                                                    class="text-[10px] px-2 py-0.5 rounded-lg font-mono"
                                                    x-text="Object.values(seatRoomAssignments || {}).filter(v => v === rm.uid).length + '/' + (rm.capacity ? parseInt(rm.capacity) : (rm.uid.includes('single') ? 1 : rm.uid.includes('triple') ? 3 : rm.uid.includes('quad') ? 4 : 2))"></span>
                                            </button>
                                        </template>

                                        <!-- زر الخيار في حال كانت القائمة فارغة -->
                                        <template
                                            x-if="{{ !$trip->allows_shared_rooms ? 'true' : 'false' }} && bookedRoomsList.length === 0">
                                            <button type="button" @click="$dispatch('open-quick-room-modal')"
                                                class="flex items-center justify-between border rounded-xl px-3.5 py-2.5 text-xs font-black transition-all w-full col-span-1 sm:col-span-2 border-red-300 bg-red-50 text-red-700 hover:bg-red-100 ring-2 ring-red-200 shadow-sm">
                                                <span class="flex items-center gap-1.5">
                                                    <span class="text-base">⚠️</span>
                                                    لم تقم بتحديد غرف! (اضغط هنا لاختيار الغرف)
                                                </span>
                                                <span class="text-lg animate-pulse">🛏️</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <!-- ========================================== -->
                                <!-- 🍽️ قسم الوجبات والوجبات الخفيفة             -->
                                <!-- ========================================== -->
                                <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200/60 space-y-3"
                                    x-show="activeModalSeat !== null">
                                    <h4 class="text-xs font-black text-slate-700 flex items-center gap-1.5">
                                        <span>🍽️</span> الوجبات والوجبات الخفيفة
                                    </h4>

                                    <div class="grid grid-cols-2 gap-2">
                                        <button type="button"
                                            @click="passengerMeals = { ...passengerMeals, [activeModalSeat]: { wants_meal: false, breakfast: false, lunch: false, dinner: false, package_name: null, package_price: 0 } }"
                                            class="flex items-center justify-center py-2 px-3 border rounded-xl transition-all text-xs font-bold"
                                            :class="passengerMeals[activeModalSeat]?.wants_meal === false ?
                                                'border-slate-600 bg-slate-800 text-white shadow-sm' :
                                                'border-slate-200 bg-white text-slate-500 hover:bg-slate-50'">
                                            ❌ بدون وجبات
                                        </button>
                                        <button type="button"
                                            @click="passengerMeals = { ...passengerMeals, [activeModalSeat]: { ...(passengerMeals[activeModalSeat] || {}), wants_meal: true } }"
                                            class="flex items-center justify-center py-2 px-3 border rounded-xl transition-all text-xs font-bold"
                                            :class="passengerMeals[activeModalSeat]?.wants_meal === true ?
                                                'border-indigo-600 bg-indigo-600 text-white shadow-sm' :
                                                'border-slate-200 bg-white text-slate-500 hover:bg-slate-50'">
                                            🍽️ إضافة وجبات
                                        </button>
                                    </div>

                                    <div x-show="passengerMeals[activeModalSeat]?.wants_meal" x-transition
                                        class="mt-3 pt-3 border-t border-dashed border-slate-200 space-y-3">
                                        <div>
                                            <label
                                                class="text-[10px] text-slate-400 mb-2 block font-extrabold uppercase tracking-wider">
                                                الوجبات الفردية:
                                            </label>
                                            <div class="grid grid-cols-3 gap-2">
                                                <!-- إفطار -->
                                                <button type="button"
                                                    x-show="baseMealPrices?.breakfast_active || (typeof getMealPrice === 'function' && getMealPrice(activeModalSeat, 'breakfast') > 0) || (baseMealPrices?.breakfast > 0) || (mealOptionsRaw?.breakfast > 0) || (mealOptionsRaw?.breakfast_price > 0)"
                                                    @click="
                                                let current = passengerMeals[activeModalSeat] || {};
                                                let newBreakfast = !current.breakfast;
                                                passengerMeals = { ...passengerMeals, [activeModalSeat]: { ...current, wants_meal: !!(newBreakfast || current.lunch || current.dinner), package_name: null, package_price: 0, breakfast: newBreakfast } };
                                            "
                                                    class="flex flex-col items-center justify-center p-2.5 border rounded-xl transition-all"
                                                    :class="passengerMeals[activeModalSeat]?.breakfast && !passengerMeals[
                                                            activeModalSeat]?.package_name ?
                                                        'border-emerald-600 bg-emerald-50 text-emerald-950 shadow-2xs ring-1 ring-emerald-500' :
                                                        'border-slate-200 bg-white hover:bg-slate-50'">
                                                    <span class="text-base">🍳</span>
                                                    <span class="text-xs font-bold mt-1">إفطار</span>
                                                    <span class="text-[10px] font-black text-emerald-700 mt-0.5"
                                                        x-text="((baseMealPrices?.breakfast > 0) ? baseMealPrices.breakfast : ((mealOptionsRaw?.breakfast > 0) ? mealOptionsRaw.breakfast : ((mealOptionsRaw?.breakfast_price > 0) ? mealOptionsRaw.breakfast_price : ((typeof getMealPrice === 'function' && getMealPrice(activeModalSeat, 'breakfast') > 0) ? getMealPrice(activeModalSeat, 'breakfast') : (baseMealPrices?.breakfast || 0))))) + ' ر.س'"></span>
                                                </button>

                                                <!-- غداء -->
                                                <button type="button"
                                                    x-show="baseMealPrices?.lunch_active || (typeof getMealPrice === 'function' && getMealPrice(activeModalSeat, 'lunch') > 0) || (baseMealPrices?.lunch > 0) || (mealOptionsRaw?.lunch > 0) || (mealOptionsRaw?.lunch_price > 0)"
                                                    @click="
                                                let current = passengerMeals[activeModalSeat] || {};
                                                let newLunch = !current.lunch;
                                                passengerMeals = { ...passengerMeals, [activeModalSeat]: { ...current, wants_meal: !!(current.breakfast || newLunch || current.dinner), package_name: null, package_price: 0, lunch: newLunch } };
                                            "
                                                    class="flex flex-col items-center justify-center p-2.5 border rounded-xl transition-all"
                                                    :class="passengerMeals[activeModalSeat]?.lunch && !passengerMeals[
                                                            activeModalSeat]?.package_name ?
                                                        'border-amber-600 bg-amber-50 text-amber-950 shadow-2xs ring-1 ring-amber-500' :
                                                        'border-slate-200 bg-white hover:bg-slate-50'">
                                                    <span class="text-base">🍔</span>
                                                    <span class="text-xs font-bold mt-1">غداء</span>
                                                    <span class="text-[10px] font-black text-amber-700 mt-0.5"
                                                        x-text="((baseMealPrices?.lunch > 0) ? baseMealPrices.lunch : ((mealOptionsRaw?.lunch > 0) ? mealOptionsRaw.lunch : ((mealOptionsRaw?.lunch_price > 0) ? mealOptionsRaw.lunch_price : ((typeof getMealPrice === 'function' && getMealPrice(activeModalSeat, 'lunch') > 0) ? getMealPrice(activeModalSeat, 'lunch') : (baseMealPrices?.lunch || 0))))) + ' ر.س'"></span>
                                                </button>

                                                <!-- عشاء -->
                                                <button type="button"
                                                    x-show="baseMealPrices?.dinner_active || (typeof getMealPrice === 'function' && getMealPrice(activeModalSeat, 'dinner') > 0) || (baseMealPrices?.dinner > 0) || (mealOptionsRaw?.dinner > 0) || (mealOptionsRaw?.dinner_price > 0)"
                                                    @click="
                                                let current = passengerMeals[activeModalSeat] || {};
                                                let newDinner = !current.dinner;
                                                passengerMeals = { ...passengerMeals, [activeModalSeat]: { ...current, wants_meal: !!(current.breakfast || current.lunch || newDinner), package_name: null, package_price: 0, dinner: newDinner } };
                                            "
                                                    class="flex flex-col items-center justify-center p-2.5 border rounded-xl transition-all"
                                                    :class="passengerMeals[activeModalSeat]?.dinner && !passengerMeals[
                                                            activeModalSeat]?.package_name ?
                                                        'border-indigo-600 bg-indigo-50 text-indigo-950 shadow-2xs ring-1 ring-indigo-500' :
                                                        'border-slate-200 bg-white hover:bg-slate-50'">
                                                    <span class="text-base">🥩</span>
                                                    <span class="text-xs font-bold mt-1">عشاء</span>
                                                    <span class="text-[10px] font-black text-indigo-700 mt-0.5"
                                                        x-text="((baseMealPrices?.dinner > 0) ? baseMealPrices.dinner : ((mealOptionsRaw?.dinner > 0) ? mealOptionsRaw.dinner : ((mealOptionsRaw?.dinner_price > 0) ? mealOptionsRaw.dinner_price : ((typeof getMealPrice === 'function' && getMealPrice(activeModalSeat, 'dinner') > 0) ? getMealPrice(activeModalSeat, 'dinner') : (baseMealPrices?.dinner || 0))))) + ' ر.س'"></span>
                                                </button>
                                            </div>
                                        </div>

                                        <div
                                            x-show="typeof getActiveSeatPackages === 'function' && getActiveSeatPackages().length > 0">
                                            <label
                                                class="text-[10px] text-slate-400 mb-1.5 block font-extrabold uppercase tracking-wider">
                                                الباقات المتاحة:
                                            </label>
                                            <div class="space-y-2">
                                                <template x-for="(pkg, index) in getActiveSeatPackages()"
                                                    :key="index">
                                                    <div @click="
                                                passengerMeals = { ...passengerMeals, [activeModalSeat]: { wants_meal: true, package_name: pkg.name, package_price: parseFloat(pkg.price) || 0, breakfast: !!(pkg.meals && pkg.meals.breakfast), lunch: !!(pkg.meals && pkg.meals.lunch), dinner: !!(pkg.meals && pkg.meals.dinner) } };
                                            "
                                                        class="flex items-center justify-between p-3 border rounded-xl cursor-pointer transition-all text-right"
                                                        :class="passengerMeals[activeModalSeat]?.package_name === pkg.name ?
                                                            'border-indigo-600 bg-indigo-50/50 ring-1 ring-indigo-500 shadow-2xs' :
                                                            'border-slate-200 bg-white hover:bg-slate-50'">
                                                        <span class="text-xs font-bold text-slate-800"
                                                            x-text="pkg.name"></span>
                                                        <span class="text-xs font-black text-indigo-600"
                                                            x-text="pkg.price > 0 ? '+' + pkg.price + ' ر.س' : 'مجاناً'"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ========================================== -->
                                <!-- ✨ قسم الخدمات الإضافية المتاحة (داخل Modal) -->
                                <!-- ========================================== -->
                                <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200/60 space-y-3 mt-3"
                                    x-show="activeModalSeat !== null && serviceOptionsRaw && serviceOptionsRaw.length > 0">

                                    <h4 class="text-xs font-black text-slate-700 flex items-center gap-1.5">
                                        <span>✨</span> الخدمات الإضافية المتاحة لهذا المسافر
                                    </h4>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <template x-for="(service, index) in serviceOptionsRaw"
                                            :key="service.id || service.name || index">

                                            <label
                                                @click.prevent="toggleService(activeModalSeat, service.id || service.name)"
                                                :class="hasService(activeModalSeat, service.id || service.name) ?
                                                    'border-blue-600 bg-blue-50/50 text-blue-950 ring-1 ring-blue-500 shadow-2xs' :
                                                    'border-slate-200 bg-white hover:bg-slate-50 text-slate-700'"
                                                class="p-3 rounded-xl border transition-all duration-200 cursor-pointer flex items-center justify-between gap-2 relative select-none">

                                                <div class="flex items-center gap-2.5">
                                                    <input type="checkbox"
                                                        :checked="hasService(activeModalSeat, service.id || service.name)"
                                                        class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500 pointer-events-none">
                                                    <div>
                                                        <span class="text-xs font-bold block"
                                                            x-text="service.name"></span>
                                                        <span class="text-[10px] text-slate-400 font-medium block"
                                                            x-text="service.description"
                                                            x-show="service.description"></span>
                                                    </div>
                                                </div>

                                                <div class="text-xs font-black text-blue-600 shrink-0">
                                                    <span x-text="service.price"></span>
                                                    <span class="text-[10px] font-bold text-slate-400">ر.س</span>
                                                </div>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div x-show="showValidation && (
                    !(passengerNames[activeModalSeat] || '').trim() || 
                    ((passengerIds[activeModalSeat] || '').trim().length !== 10) || 
                    !(passengerGenders[activeModalSeat] || '').trim() || 
                    (!seatRoomAssignments[activeModalSeat] || seatRoomAssignments[activeModalSeat] === '') ||
                    (seatRoomAssignments[activeModalSeat] === 'shared' && !(typeof sharedRoomTypes !== 'undefined' && sharedRoomTypes && sharedRoomTypes[activeModalSeat]))
                 )"
                            x-transition
                            class="mx-1 mb-3 px-3.5 py-2.5 bg-red-50 text-red-600 text-xs font-bold rounded-xl border border-red-200 flex items-center justify-center gap-2 shadow-2xs">
                            <span>⚠️ يرجى استكمال جميع الحقول الإلزامية، بما في ذلك نوع الغرفة المشتركة.</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-100/80 mt-1 flex-shrink-0">
                            <button type="button" @click="savePassengerModal()"
                                class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-black text-xs py-3 rounded-xl active:scale-[0.98] transition-all shadow-md shadow-blue-500/20">
                                حفظ البيانات وإغلاق ✓
                            </button>

                            <button type="button" @click="removePassenger(activeModalSeat)"
                                class="w-full bg-red-50/80 text-red-600 border border-red-200/80 font-extrabold text-xs py-3 rounded-xl hover:bg-red-100/80 transition-all active:scale-[0.98]">
                                حذف المسافر 🗑
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- 2️⃣ تنبيه المقعد غير المتاح --}}
        <div x-show="showBookedError"
            class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-[2px]"
            @click="showBookedError = false" style="display: none;" x-cloak>
            <div class="bg-white border border-slate-200 shadow-2xl rounded-3xl p-6 max-w-sm w-full text-center space-y-4"
                @click.stop dir="rtl">
                <div
                    class="w-12 h-12 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mx-auto text-xl font-bold">
                    🚫
                </div>
                <h3 class="text-base font-black text-slate-800">المقعد غير متاح</h3>
                <p class="text-xs text-slate-500 leading-relaxed font-medium">
                    عذراً، هذا المقعد محجوز مسبقاً أو مخصص للكادر التنظيمي ولا يمكنك تحديده.
                </p>
                <button type="button" @click="showBookedError = false"
                    class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition-all shadow-md active:scale-95">
                    حسناً، فهمت
                </button>
            </div>
        </div>

        {{-- 3️⃣ نافذة التنبيهات العامة (الرسائل المخصصة للجنسين والأخطاء) --}}
        <div x-show="showAlertModal"
            class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-[2px]"
            @click="showAlertModal = false" style="display: none;" x-cloak>
            <div class="bg-white border border-slate-200 shadow-2xl rounded-3xl p-6 max-w-md w-full text-center space-y-4"
                @click.stop dir="rtl">
                <div
                    class="w-12 h-12 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mx-auto text-xl font-bold">
                    ⚠️
                </div>
                <h3 class="text-base font-black text-slate-800">تنبيه هام</h3>
                <p class="text-xs text-slate-600 leading-relaxed font-bold" x-text="alertMessage"></p>
                <button type="button" @click="showAlertModal = false"
                    class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition-all shadow-md active:scale-95">
                    موافق
                </button>
            </div>
        </div>

        {{-- 4️⃣ نافذة المطالبة بتسجيل الدخول --}}
        <div x-show="showLoginModal"
            class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
            @click="showLoginModal = false" style="display: none;" x-cloak>
            <div class="bg-white border border-slate-200 shadow-2xl rounded-3xl p-6 max-w-sm w-full text-center space-y-4"
                @click.stop dir="rtl">
                <div
                    class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-2xl mx-auto">
                    🔒
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">تسجيل الدخول مطلوب</h3>
                    <p class="text-xs text-slate-500 mt-1 font-medium">يرجى تسجيل الدخول للحساب لمتابعة إجراءات الحجز وحفظ
                        البيانات.</p>
                </div>
                <div class="flex gap-2 pt-2">
                    <a href="{{ route('login') }}"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-3 rounded-xl transition-all shadow-md shadow-blue-500/20 text-center flex items-center justify-center">
                        تسجيل الدخول
                    </a>
                    <button type="button" @click="showLoginModal = false"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs py-3 rounded-xl transition-all">
                        إلغاء
                    </button>
                </div>
            </div>
        </div>

        {{-- 5️⃣ نافذة اختيار الغرف السريعة --}}
        <div x-data="{ isQuickRoomModalOpen: false }" @open-quick-room-modal.window="isQuickRoomModalOpen = true"
            x-show="isQuickRoomModalOpen" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 backdrop-blur-0" x-transition:enter-end="opacity-100 backdrop-blur-md"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 backdrop-blur-md"
            x-transition:leave-end="opacity-0 backdrop-blur-0"
            class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-md"
            @click="isQuickRoomModalOpen = false" x-cloak>

            <div class="bg-white border border-slate-200 shadow-2xl rounded-[2.5rem] p-6 md:p-8 max-w-2xl w-full space-y-5 relative text-right text-slate-800"
                @click.stop dir="rtl">

                <!-- عنوان النافذة زر الإغلاق -->
                <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                    <h3 class="text-lg font-black text-slate-900 flex items-center gap-2">
                        <span class="p-2 bg-blue-50 text-blue-600 rounded-xl text-sm">🛏️</span>
                        اختر الغرف الخاصة المطلوبة
                    </h3>
                    <button type="button" @click="isQuickRoomModalOpen = false"
                        class="w-9 h-9 flex items-center justify-center rounded-2xl bg-slate-100 text-slate-400 hover:text-slate-700 hover:bg-slate-200 transition-all font-black text-xs">
                        ✕
                    </button>
                </div>

                <!-- رسالة تنبيهية -->
                <div
                    class="bg-amber-50 border border-amber-200/80 text-amber-900 p-3.5 rounded-2xl text-xs font-bold flex gap-2.5 items-center">
                    <span class="text-xl">⚠️</span>
                    <span class="leading-relaxed">يرجى تحديد الغرف والكميات المناسبة أولاً ليتم تخصيص وتسكين المسافرين
                        عليها.</span>
                </div>

                <!-- قائمة الغرف المتاحة -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[50vh] overflow-y-auto custom-scrollbar p-1">
                    <template x-for="(room, type) in roomOptions" :key="type">
                        <div x-show="room && room.price > 0"
                            :class="(roomQuantities[type] || 0) > 0 ? 'border-blue-600 bg-blue-50/20 ring-2 ring-blue-600/10' :
                                'border-slate-200 bg-white hover:border-slate-300'"
                            class="p-4 rounded-2xl border transition-all duration-200 flex flex-col justify-between gap-4 shadow-sm relative">

                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-black text-slate-800" x-text="getTypeName(type)"></span>
                                    <template x-if="room.count > 0">
                                        <span
                                            class="text-[10px] text-emerald-700 font-bold bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 rounded-md">
                                            متاح: <span x-text="room.count"></span>
                                        </span>
                                    </template>
                                </div>

                                <div class="text-lg font-black text-blue-600 flex items-baseline gap-1">
                                    <span x-text="roomPrices[type] || room.price"></span>
                                    <span class="text-xs font-bold text-slate-500">ر.س</span>
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-between bg-slate-50 border border-slate-200/80 rounded-xl p-1.5 mt-2">
                                <button type="button" @click="decreaseRoom(type)"
                                    class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center font-black text-slate-700 hover:bg-slate-100 active:scale-95 text-sm transition-all shadow-sm">
                                    -
                                </button>

                                <span class="text-sm font-black text-slate-800 select-none px-2"
                                    x-text="roomQuantities[type] || 0"></span>

                                <button type="button"
                                    @click="if((roomQuantities[type] || 0) < room.count) increaseRoom(type)"
                                    :disabled="(roomQuantities[type] || 0) >= room.count"
                                    :class="(roomQuantities[type] || 0) >= room.count ?
                                        'bg-slate-200 text-slate-400 cursor-not-allowed border-transparent' :
                                        'bg-blue-600 text-white hover:bg-blue-700 active:scale-95 shadow-sm shadow-blue-500/30'"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-sm transition-all">
                                    +
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- أزرار الإغلاق والتأكيد -->
                <div class="pt-3 border-t border-slate-100 flex justify-end">
                    <button type="button" @click="isQuickRoomModalOpen = false"
                        class="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white font-black text-xs px-6 py-3 rounded-xl transition-all shadow-md active:scale-95">
                        تم وإغلاق النافذة ✓
                    </button>
                </div>

            </div>
        </div>

    </div>
@endsection
