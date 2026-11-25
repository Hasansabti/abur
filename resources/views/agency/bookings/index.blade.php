@extends('layouts.agency')

@section('content')
    @php
        /*
        |--------------------------------------------------------------------------
        | تجهيز البيانات المشتركة (مرة واحدة لكل الصفحة)
        |--------------------------------------------------------------------------
        */
        $bookingsList = method_exists($bookings, 'getCollection') ? $bookings->getCollection() : collect($bookings);

        $roomNamesAr = [
            'single' => 'فردية',
            'double' => 'ثنائية',
            'triple' => 'ثلاثية',
            'quad' => 'رباعية',
            'shared' => 'غرفة مشتركة',
            'additional' => 'مقعد إضافي',
        ];
        $roomIcons = ['single' => '👤', 'double' => '👥', 'triple' => '👨‍👩‍👦', 'quad' => '👨‍👩‍👧‍👦'];

        // مساعدات صغيرة
        $decode = fn($v) => is_string($v) ? json_decode($v, true) ?? [] : $v ?? [];
        $cleanRoom = function ($v) {
            $v = strtolower(trim((string) $v));
            return str_contains($v, '_') ? explode('_', $v)[0] : $v;
        };
        $seatLabel = function ($seat) use ($decode) {
            $n = $seat->seat_number;
            if (is_string($n) && str_starts_with($n, '[')) {
                $n = $decode($n);
            }
            return is_array($n) ? implode(', ', $n) : $n;
        };

        // التاريخ الهجري (يتطلب إضافة intl في PHP، وإذا غير متوفرة يُترك فارغاً)
        $hijri = function ($date) {
            try {
                if (!$date || !class_exists(\IntlDateFormatter::class)) {
                    return '';
                }
                $f = new \IntlDateFormatter(
                    'ar-SA-u-ca-islamic-umalqura-nu-latn',
                    \IntlDateFormatter::NONE,
                    \IntlDateFormatter::NONE,
                    'Asia/Riyadh',
                    null,
                    'EEEE، d MMMM، y',
                );
                return $f->format(\Carbon\Carbon::parse($date)->setTimezone('Asia/Riyadh'));
            } catch (\Throwable $e) {
                return '';
            }
        };

        // اسم طريقة الدفع بالعربي
        $payLabel = fn($m) => match (strtolower((string) $m)) {
            'mada', 'مدى' => 'مدى',
            'balance', 'wallet', 'رصيد', 'المحفظة' => 'رصيد',
            'moyasar', 'card', 'بطاقة' => 'بطاقة',
            'cash', 'نقد', 'نقدي' => 'نقد',
            'bank', 'transfer', 'تحويل' => 'تحويل بنكي',
            '' => '-',
            default => (string) $m,
        };

        // ملاحظات  فاتورة (عدّلها حسب سياسة وكالتك)
        $receiptNotes = [
            'أي قرارات أو أنظمة جديدة في بلد الوجهة مسؤولية المسافر.',
            'الحجز غير قابل للإلغاء أو الاسترداد.',
            'الحجز قابل للتعديل حسب شروط الفنادق والطيران.',
            'تأخير أو إلغاء أو تعديل بعض الرحلات الجوية مسؤولية الطيران وليس مسؤولية الوكالة.',
            'السعر لا يشمل الفيزا وضرائب المطار.',
            'رحلاتنا لا تشمل التوصيل إلى المطار.',
            'عند الظروف الطارئة والخارجة عن الإرادة من الممكن تغيير البرنامج.',
            'الجدول قابل للتغيير في بعض الفقرات حسب الظروف.',
            'الاستقبال والتوديع في مطار الوجهة فقط.',
            'في حالة الإصابة بفيروس كورونا أو أي مرض آخر لا سمح الله فإن الوكالة غير مسؤولة عن تكاليف الحجر أو العلاج أو الحجز أو تذاكر العودة.',
            'حجزك غرفة ثنائية لا يعني سرير كبير مزدوج (على حسب المتوفر).',
            'مجرد استلامكم الفاتورة وعدم اعتراضكم على الشروط هذا يعني موافقتكم على جميع الشروط والأحكام.',
        ];
        $roomPricePerPerson = true;
        // المرافقون: مفتاحهم user_id_national_id
        if (!isset($companionDocs)) {
            $companionDocs = \App\Models\Companion::whereIn(
                'user_id',
                $bookingsList->pluck('user_id')->filter()->unique(),
            )
                ->whereIn(
                    'national_id',
                    $bookingsList
                        ->flatMap(fn($b) => $b->bookingSeats->pluck('passenger_national_id'))
                        ->filter()
                        ->unique(),
                )
                ->get()
                ->keyBy(fn($c) => $c->user_id . '_' . $c->national_id);
        }

        // المرافقون غير الموثقين لكل حجز
        $unverifiedByBooking = [];
        foreach ($bookingsList as $b) {
            $found = [];
            foreach ($b->bookingSeats as $s) {
                $nid = trim((string) $s->passenger_national_id);
                if (
                    $nid === '' ||
                    $nid === '0000000000' ||
                    str_starts_with($nid, '99000000') ||
                    $cleanRoom($s->room_type) === 'additional' ||
                    !empty($s->parent_seat_id)
                ) {
                    continue;
                }
                $comp = $companionDocs->get($b->user_id . '_' . $nid);
                if ($comp && empty($comp->id_document_path) && empty($comp->id_image)) {
                    $found[$nid] = ['name' => $s->passenger_name, 'national_id' => $nid];
                }
            }
            if ($found) {
                $unverifiedByBooking[$b->id] = array_values($found);
            }
        }
        $totalUnverified = collect($unverifiedByBooking)->flatten(1)->unique('national_id')->count();
        $bookingsWithUnverified = count($unverifiedByBooking);
    @endphp

    <div class="space-y-8" x-data="{
        filterStatus: 'all',
        searchQuery: @js(request('search') ?? request('x_search', ''))
    }">

        {{-- تنبيهات النجاح والخطأ --}}
        @if (session('success') || session('error'))
            <div id="statusModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 transition-opacity duration-300"
                dir="rtl">
                <div
                    class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 text-center transform transition-all scale-100 border border-slate-100">
                    @if (session('success'))
                        <div
                            class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-emerald-200">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-base font-black text-slate-800 mb-2">تمت العملية بنجاح</h3>
                        <p class="text-xs font-bold text-slate-600 mb-6 leading-relaxed">{{ session('success') }}</p>
                        <button onclick="closeStatusModal()"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition duration-200 shadow-md shadow-emerald-600/20">
                            موافق
                        </button>
                    @endif

                    @if (session('error'))
                        <div
                            class="w-14 h-14 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-rose-200">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <h3 class="text-base font-black text-slate-800 mb-2">تنبيه / خطأ</h3>
                        <p class="text-xs font-bold text-slate-600 mb-6 leading-relaxed">{{ session('error') }}</p>
                        <button onclick="closeStatusModal()"
                            class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition duration-200 shadow-md shadow-rose-600/20">
                            إغلاق
                        </button>
                    @endif
                </div>
            </div>

            <script>
                function closeStatusModal() {
                    const modal = document.getElementById('statusModal');
                    if (modal) {
                        modal.classList.add('opacity-0');
                        setTimeout(() => modal.remove(), 200);
                    }
                }
            </script>
        @endif

        {{-- رأس الصفحة والإحصائيات --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 text-right" dir="rtl">
            <div>
                <h1 class="text-3xl font-black text-gray-800 tracking-tighter">إدارة الحجوزات</h1>
                <p class="text-gray-500 font-medium">متابعة طلبات المسافرين وحالة الدفع والفواتير الفورية النافذية.</p>
            </div>

            <div class="flex gap-3 flex-wrap">
                <div class="bg-green-50 px-4 py-2 rounded-2xl border border-green-100 flex items-center gap-2">
                    <span class="text-green-600 font-black text-sm">{{ $confirmedCount ?? 0 }} مؤكد</span>
                </div>
                <div class="bg-amber-50 px-4 py-2 rounded-2xl border border-amber-100 flex items-center gap-2">
                    <span class="text-amber-600 font-black text-sm">{{ $pendingCount ?? 0 }} معلق</span>
                </div>
                @if ($totalUnverified > 0)
                    <div class="bg-rose-50 px-4 py-2 rounded-2xl border border-rose-200 flex items-center gap-2">
                        <span class="text-rose-600 font-black text-sm">{{ $totalUnverified }} مرافق غير موثق</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- بانر المرافقين غير الموثقين --}}
        @if ($totalUnverified > 0)
            <div class="bg-rose-50 border-2 border-rose-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-right"
                dir="rtl">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">⚠️</span>
                    <div>
                        <h4 class="text-sm font-black text-rose-900">
                            يوجد {{ $totalUnverified }}
                            {{ $totalUnverified === 1 ? 'مرافق غير موثق' : 'مرافقين غير موثقين' }}
                            في {{ $bookingsWithUnverified }}
                            {{ $bookingsWithUnverified === 1 ? 'حجز' : 'حجوزات' }}
                        </h4>
                        <p class="text-xs font-bold text-rose-700/80 mt-0.5">
                            لم يرفع أصحاب هذه الحجوزات صورة الهوية لمرافقيهم بعد.
                        </p>
                    </div>
                </div>
                <button type="button" @click="filterStatus = 'unverified'"
                    class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-black transition-all active:scale-95 shrink-0">
                    عرض الحجوزات المتأثرة
                </button>
            </div>
        @endif

        {{-- صندوق البحث والفلترة السريعة --}}
        <form method="GET" action="{{ url()->current() }}" id="mainFilterForm" x-ref="mainFilterForm"
            class="bg-white p-4 rounded-[2rem] shadow-sm border border-gray-100 flex flex-wrap gap-4 items-center text-right"
            dir="rtl">

            <div class="flex-1 min-w-[240px] relative">
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                <input type="text" id="mainSearchInput" name="search" x-model="searchQuery"
                    @keydown.enter.prevent="$refs.mainFilterForm.submit()"
                    placeholder="ابحث برقم الحجز، اسم العميل أو رقم الهاتف..."
                    class="w-full bg-gray-50 border-none rounded-xl pr-10 pl-16 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">

                <button type="submit"
                    class="absolute left-2 top-1/2 -translate-y-1/2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">
                    بحث
                </button>
            </div>

            {{-- قائمة الرحلات --}}
            <div class="min-w-[240px] relative" x-data="{
                open: false,
                searchTrip: '',
                selectedId: @js((string) request('trip_id', '')),
                trips: @js(
    collect([['id' => '', 'title' => 'كل الرحلات']])
        ->concat($trips->map(fn($t) => ['id' => (string) $t->id, 'title' => $t->title]))
        ->values(),
),
                get selectedTitle() {
                    return (this.trips.find(t => t.id === this.selectedId) || this.trips[0]).title;
                },
                get filteredTrips() {
                    const q = this.searchTrip.toLowerCase();
                    return q === '' ? this.trips : this.trips.filter(t => (t.title || '').toLowerCase().includes(q));
                }
            }" @click.away="open = false">

                <input type="hidden" name="trip_id" x-ref="hiddenInput" :value="selectedId">

                <button type="button" @click="open = !open"
                    class="w-full bg-gray-50 text-right font-bold text-gray-700 rounded-xl px-4 py-3 text-sm flex justify-between items-center hover:bg-gray-100 transition-all focus:ring-2 focus:ring-blue-500 outline-none">
                    <span x-text="selectedTitle"></span>
                    <span class="text-gray-400 mr-2 transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''">🔽</span>
                </button>

                <div x-show="open" x-transition style="display: none;"
                    class="absolute right-0 z-50 mt-2 w-full bg-white border border-gray-100 rounded-2xl shadow-xl flex flex-col max-h-64 outline-none overflow-hidden">

                    <div class="sticky top-0 bg-white p-2 border-b border-gray-50 z-10">
                        <div class="relative">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">🔍</span>
                            <input type="text" x-model="searchTrip" placeholder="ابحث عن رحلة..." @click.stop
                                @keydown.enter.prevent
                                class="w-full bg-gray-50 border-none rounded-xl pr-8 pl-3 py-2 text-xs focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                    </div>

                    <div class="p-2 overflow-y-auto space-y-1 flex-1 custom-scrollbar">
                        <template x-for="trip in filteredTrips" :key="trip.id">
                            <button type="button"
                                @click="selectedId = trip.id; open = false; $nextTick(() => $refs.mainFilterForm.submit())"
                                class="w-full text-right px-3 py-2 text-sm rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors flex items-center justify-between"
                                :class="selectedId === trip.id ? 'bg-blue-50 text-blue-600 font-bold' : ''">
                                <span x-text="trip.title"></span>
                                <span x-show="selectedId === trip.id" class="text-blue-500 text-xs">✓</span>
                            </button>
                        </template>
                        <div x-show="filteredTrips.length === 0" class="text-center text-xs text-gray-400 py-3">
                            لا توجد رحلات مطابقة للبحث
                        </div>
                    </div>
                </div>
            </div>

            {{-- فلتر الحالة --}}
            <div class="flex bg-gray-50 p-1 rounded-xl flex-wrap">
                <button type="button" @click="filterStatus = 'all'"
                    :class="filterStatus === 'all' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-400'"
                    class="px-4 py-2 rounded-lg text-xs font-black transition-all">الكل</button>

                <button type="button" @click="filterStatus = 'confirmed'"
                    :class="filterStatus === 'confirmed' ? 'bg-white shadow-sm text-green-600' : 'text-gray-400'"
                    class="px-4 py-2 rounded-lg text-xs font-black transition-all">المؤكدة</button>

                <button type="button" @click="filterStatus = 'pending'"
                    :class="filterStatus === 'pending' ? 'bg-white shadow-sm text-amber-600' : 'text-gray-400'"
                    class="px-4 py-2 rounded-lg text-xs font-black transition-all">بانتظار الدفع</button>

                <button type="button" @click="filterStatus = 'requestC'"
                    :class="filterStatus === 'requestC' ? 'bg-red-600 text-white shadow-sm' : 'text-red-500 font-black'"
                    class="px-4 py-2 rounded-lg text-xs font-black transition-all">⚠️ طلبات الإلغاء</button>

                <button type="button" @click="filterStatus = 'unverified'"
                    :class="filterStatus === 'unverified' ? 'bg-white shadow-sm text-rose-600' : 'text-gray-400'"
                    class="px-4 py-2 rounded-lg text-xs font-black transition-all flex items-center gap-1.5">
                    فيها غير موثقين
                    @if ($bookingsWithUnverified > 0)
                        <span
                            class="min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] flex items-center justify-center">{{ $bookingsWithUnverified }}</span>
                    @endif
                </button>
            </div>
        </form>

        {{-- الجدول الرئيسي --}}
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden text-right" dir="rtl">
            <table class="w-full border-collapse">
                <thead>
                    <tr
                        class="bg-gray-50/50 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="p-6">رقم الحجز / العميل</th>
                        <th class="p-6">الرحلة</th>
                        <th class="p-6">طلب التعديل</th>
                        <th class="p-6">المقاعد</th>
                        <th class="p-6">نوع الغرفة</th>
                        <th class="p-6">المبلغ</th>
                        <th class="p-6 text-center">الحالة</th>
                        <th class="p-6 text-center">الدفع</th>
                        <th class="p-6 text-center">التحكم</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($bookings as $booking)
                        @php
                            /*
                            |------------------------------------------------------------------
                            | بيانات الصف
                            |------------------------------------------------------------------
                            */
                            $trip = $booking->trip;
                            $tripPast = $trip?->departure_at && \Carbon\Carbon::parse($trip->departure_at)->isPast();

                            $filterKey = in_array($booking->status, ['confirmed', 'approved'])
                                ? 'confirmed'
                                : ($booking->status === 'requestC'
                                    ? 'requestC'
                                    : $booking->status);

                            $unverified = $unverifiedByBooking[$booking->id] ?? [];

                            // المقاعد
                            $allSeatNumbers = $booking->bookingSeats
                                ->map(fn($s) => $seatLabel($s))
                                ->filter()
                                ->values()
                                ->all();
                            $totalSeatsCount = count($allSeatNumbers);

                            // الغرف
                            $allRoomsList = [];
                            foreach ($booking->bookingSeats as $i => $seat) {
                                $type = $cleanRoom($seat->room_type);
                                if ($type === '' && !empty($booking->room_type)) {
                                    $parts = explode(',', str_replace(['/', '-'], ',', $booking->room_type));
                                    $type = strtolower(trim($parts[$i] ?? ($parts[0] ?? '')));
                                }
                                $allRoomsList[] = [
                                    'type' => $type,
                                    'label' => $roomNamesAr[$type] ?? ($type ?: 'بدون غرفة'),
                                ];
                            }
                            $totalRoomsCount = count($allRoomsList);

                            // خيارات الرحلة
                            $rawRoomOptions = $decode($trip?->room_options);
                            $roomOptions = array_filter(
                                $rawRoomOptions,
                                fn($d) => is_array($d) && (float) ($d['price'] ?? 0) > 0,
                            );
                            $mealOptions = $decode($trip?->meal_options);
                            $mealPackages = $mealOptions['packages'] ?? [];
                            $indiv = $mealOptions['individual'] ?? [];
                            $bfPrice = !empty($indiv['breakfast']['active'])
                                ? (float) ($indiv['breakfast']['price'] ?? 0)
                                : 0;
                            $lnPrice = !empty($indiv['lunch']['active']) ? (float) ($indiv['lunch']['price'] ?? 0) : 0;
                            $dnPrice = !empty($indiv['dinner']['active'])
                                ? (float) ($indiv['dinner']['price'] ?? 0)
                                : 0;
                            $ip = ['bf' => $bfPrice, 'ln' => $lnPrice, 'dn' => $dnPrice];

                            $tripExtraServices = $decode($trip?->extra_services);

                            // بيانات المقاعد للنافذة (Alpine)
                            $seatsData = $booking->bookingSeats
                                ->map(function ($seat) use ($tripExtraServices, $decode, $cleanRoom) {
                                    $roomOpts = $decode($seat->room_options);
                                    $mealOpts = $decode($seat->meal_options);
                                    $savedServices = $decode($seat->extra_services);

                                    $pkgName =
                                        $roomOpts['package_name'] ??
                                        ($roomOpts['package']['name'] ?? ($roomOpts['package'] ?? ''));
                                    $mealPkgName = $mealOpts['package_key'] ?? ($mealOpts['package_name'] ?? '');

                                    $rawRoom = strtolower(trim($seat->room_type ?? ''));
                                    $isShared =
                                        !empty($roomOpts['is_shared']) ||
                                        !empty($roomOpts['is_room_shared']) ||
                                        str_contains($rawRoom, 'shared') ||
                                        str_contains($rawRoom, 'مشتركة');

                                    $matchedRoomKey = 'double';
                                    if (str_contains($rawRoom, 'single') || str_contains($rawRoom, 'فردية')) {
                                        $matchedRoomKey = 'single';
                                    } elseif (str_contains($rawRoom, 'double') || str_contains($rawRoom, 'ثنائية')) {
                                        $matchedRoomKey = 'double';
                                    } elseif (str_contains($rawRoom, 'triple') || str_contains($rawRoom, 'ثلاثية')) {
                                        $matchedRoomKey = 'triple';
                                    } elseif (str_contains($rawRoom, 'quad') || str_contains($rawRoom, 'رباعية')) {
                                        $matchedRoomKey = 'quad';
                                    } elseif (str_contains($rawRoom, 'additional') || str_contains($rawRoom, 'إضافي')) {
                                        $matchedRoomKey = 'additional';
                                    } elseif (
                                        in_array($cleanRoom($rawRoom), [
                                            'single',
                                            'double',
                                            'triple',
                                            'quad',
                                            'additional',
                                        ])
                                    ) {
                                        $matchedRoomKey = $cleanRoom($rawRoom);
                                    }

                                    $available = !empty($tripExtraServices) ? $tripExtraServices : $savedServices;
                                    $services = [];
                                    foreach ($available as $srv) {
                                        $name = trim($srv['name'] ?? '');
                                        $selected = collect($savedServices)->contains(
                                            fn($saved) => isset($saved['name']) && trim($saved['name']) === $name,
                                        );
                                        $services[] = [
                                            'name' => $name,
                                            'selected' => $selected,
                                            'price' => (float) ($srv['price'] ?? 0),
                                        ];
                                    }

                                    return [
                                        'id' => $seat->id,
                                        'room_type' => $matchedRoomKey,
                                        'room_package' => trim($pkgName),
                                        'is_shared' => (bool) $isShared,
                                        'meal_pkg' => trim($mealPkgName),
                                        'bf' => (bool) $seat->with_breakfast,
                                        'ln' => (bool) $seat->with_lunch,
                                        'dn' => (bool) $seat->with_dinner,
                                        'services' => $services,
                                    ];
                                })
                                ->values()
                                ->toArray();

                            // أسعار الغرف وباقاتها
                            $rp = [];
                            $rpd = [];
                            foreach ($roomOptions as $roomKey => $roomDetails) {
                                $rk = strtolower(trim($roomKey));
                                $rp[$rk] = (float) ($roomDetails['price'] ?? 0);
                                $rpd[$rk] = collect($roomDetails['packages'] ?? [])
                                    ->map(
                                        fn($p) => [
                                            'name' => trim($p['name'] ?? ''),
                                            'price' => (float) ($p['price'] ?? 0),
                                            'meals' => [
                                                'breakfast' => (bool) ($p['meals']['breakfast'] ?? false),
                                                'lunch' => (bool) ($p['meals']['lunch'] ?? false),
                                                'dinner' => (bool) ($p['meals']['dinner'] ?? false),
                                            ],
                                        ],
                                    )
                                    ->values()
                                    ->all();
                            }

                            // أسعار باقات الوجبات
                            $mp = ['' => 0];
                            if (is_array($mealPackages)) {
                                foreach ($mealPackages as $key => $val) {
                                    if (is_array($val)) {
                                        $mp[trim($val['name'] ?? $key)] = (float) ($val['price'] ?? 0);
                                    } else {
                                        $mp[trim($key)] = (float) $val;
                                    }
                                }
                            }
                            foreach ($booking->bookingSeats as $s) {
                                $mOpts = $decode($s->meal_options);
                                $mName = trim($mOpts['package_key'] ?? ($mOpts['package_name'] ?? ''));
                                if ($mName !== '' && !isset($mp[$mName])) {
                                    $mp[$mName] = (float) ($mOpts['package_price'] ?? ($mOpts['price'] ?? 0));
                                }
                            }

                            $packageLabels = [
                                'bf' => '🥐 باقة الفطور',
                                'ln' => '🍲 باقة الغداء',
                                'dn' => '🍖 باقة العشاء',
                                'bf_dn' => '🥐🍖 فطور وعشاء',
                                'bf_ln' => '🥐🍲 فطور وغداء',
                                'ln_dn' => '🍲🍖 غداء وعشاء',
                                'full' => '✨ وجبات كاملة',
                                'all' => '✨ وجبات كاملة',
                                'bf_ln_dn' => '✨ وجبات كاملة',
                            ];
                            $uiMealPackages = array_filter($mp, fn($k) => $k !== '', ARRAY_FILTER_USE_KEY);

                            // تجميع المقاعد الإضافية تحت صاحبها
                            $groupedSeats = [];
                            $lastMainIndex = null;
                            foreach ($booking->bookingSeats as $index => $seat) {
                                $isAdditional = in_array($cleanRoom($seat->room_type), [
                                    'additional',
                                    'مقعد إضافي',
                                    'إضافي',
                                ]);
                                if (!$isAdditional || $lastMainIndex === null) {
                                    $groupedSeats[$index] = [
                                        'main' => ['seat' => $seat, 'index' => $index],
                                        'additional' => [],
                                    ];
                                    $lastMainIndex = $index;
                                } else {
                                    $groupedSeats[$lastMainIndex]['additional'][] = [
                                        'seat' => $seat,
                                        'index' => $index,
                                    ];
                                }
                            }

                            /*
                            |------------------------------------------------------------------
                            | حساب السعر لكل مقعد (نفس منطق الحساب الحي في النافذة)
                            | - المقعد الإضافي والغرفة المشتركة: يدفع سعر المقعد المنفرد
                            | - الغرف العادية: (عدد الغرف المطلوبة × سعر الغرفة) موزعة على ركابها
                            | - باقة الغرفة + الوجبات + الخدمات الإضافية المختارة
                            |------------------------------------------------------------------
                            */
                            $capacities = ['single' => 1, 'double' => 2, 'triple' => 3, 'quad' => 4];
                            $standalone = (float) ($trip->price ?? 0);
                            $indivMap = [
                                'bf' => ['breakfast', $bfPrice, 'فطور'],
                                'ln' => ['lunch', $lnPrice, 'غداء'],
                                'dn' => ['dinner', $dnPrice, 'عشاء'],
                            ];
                            $seatLines = [];
                            $roomGroups = [];

                            foreach ($seatsData as $i => $sd) {
                                $rType = $sd['room_type'];
                                $solo = $rType === 'additional' || $sd['is_shared'];
                                $line = ['base' => 0.0, 'room' => 0.0, 'extras' => []];

                                if ($solo) {
                                    $line['base'] += $standalone;
                                } elseif (isset($capacities[$rType])) {
                                    $roomGroups[$rType][] = $i;
                                }

                                if ($rType !== 'additional') {
                                    $pkg =
                                        $sd['room_package'] !== ''
                                            ? collect($rpd[$rType] ?? [])->firstWhere('name', $sd['room_package'])
                                            : null;
                                    if ($pkg) {
                                        $line['base'] += (float) $pkg['price'];
                                    }
                                    $pkgMeals = $pkg['meals'] ?? [];

                                    if ($sd['meal_pkg'] !== '' && isset($mp[$sd['meal_pkg']])) {
                                        $line['base'] += (float) $mp[$sd['meal_pkg']];
                                    } else {
                                        foreach ($indivMap as $k => [$mk, $mPrice, $mLbl]) {
                                            if ($sd[$k] && empty($pkgMeals[$mk])) {
                                                $line['base'] += $mPrice;
                                            }
                                        }
                                    }

                                    foreach ($sd['services'] as $srv) {
                                        if (!empty($srv['selected'])) {
                                            $line['base'] += (float) $srv['price'];
                                            $line['extras'][] =
                                                'مع ' .
                                                $srv['name'] .
                                                ' (' .
                                                number_format((float) $srv['price'], 2) .
                                                ' ريال)';
                                        }
                                    }
                                }
                                $seatLines[$i] = $line;
                            }

                            foreach ($roomGroups as $type => $idxs) {
                                $units = $roomPricePerPerson ? count($idxs) : ceil(count($idxs) / $capacities[$type]);
                                $roomTotal = $units * ($rp[$type] ?? 0);
                                foreach ($idxs as $i) {
                                    $seatLines[$i]['room'] = $roomTotal / count($idxs);
                                }
                            }

                            // المبلغ المحفوظ في الحجز هو المرجع؛ الحساب يُستخدم فقط لتوزيعه على المقاعد
                            $commission = (float) ($booking->platform_commission ?? 0);
                            $agencyAmt = (float) ($booking->agency_amount ?? 0);

                            // agency_amount مخزّن بعد خصم العمولة، فنرجّعها للحصول على سعر العميل الأصلي
                            $savedGross =
                                $agencyAmt > 0
                                    ? $agencyAmt + $commission
                                    : (float) ($booking->net_amount ?? 0) +
                                        $commission +
                                        (float) ($booking->cash_paid ?? 0);
                            $computedGross = collect($seatLines)->sum(fn($l) => $l['base'] + $l['room']);
                            $grossTotal = $savedGross > 0 ? $savedGross : $computedGross;
                            if ($savedGross > 0 && abs($savedGross - $computedGross) > 0.005 && count($seatLines) > 0) {
                                $cnt = count($seatLines);
                                foreach ($seatLines as $i => $l) {
                                    $share = $computedGross > 0 ? ($l['base'] + $l['room']) / $computedGross : 1 / $cnt;
                                    $seatLines[$i]['base'] = 0.0;
                                    $seatLines[$i]['room'] = $savedGross * $share;
                                }
                            }

                            // الأرصدة (المدفوعات)
                            $vatAmount = (float) ($booking->vat_amount ?? 0);

                            // المدفوعات الإضافية المسددة (فواتير ميسر الإضافية)
                            $paidExtras = ($booking->extraCharges ?? collect())->where('status', 'paid');
                            $extraPaidTotal = (float) $paidExtras->sum('amount');

                            $totalWithVat = $grossTotal + $extraPaidTotal + $vatAmount;
                            $paymentRows = [];
                            $payments = method_exists($booking, 'payments') ? $booking->payments : collect();
                            if ($payments->count() > 0) {
                                foreach ($payments as $p) {
                                    $paymentRows[] = [
                                        'id' => $p->id,
                                        'by' => $p->created_by_name ?? 'المدير',
                                        'date' => $p->created_at,
                                        'notes' => $p->notes ?? '',
                                        'method' => $payLabel($p->method ?? ($p->payment_method ?? '')),
                                        'amount' => (float) $p->amount,
                                    ];
                                }
                            } elseif (in_array($booking->payment_status, ['paid', 'paidof'])) {
                                $paymentRows[] = [
                                    'id' => $booking->id,
                                    'by' => 'المدير',
                                    'date' => $booking->updated_at ?? $booking->created_at,
                                    'notes' => $booking->payment_status === 'paidof' ? 'دفع وكالة' : '',
                                    'method' => $payLabel($booking->payment_method),
                                    'amount' => $totalWithVat - $extraPaidTotal,
                                ];
                            } elseif ((float) ($booking->cash_paid ?? 0) > 0) {
                                $paymentRows[] = [
                                    'id' => $booking->id,
                                    'by' => 'المدير',
                                    'date' => $booking->updated_at ?? $booking->created_at,
                                    'notes' => '',
                                    'method' => 'نقد',
                                    'amount' => (float) $booking->cash_paid,
                                ];
                            }
                            foreach ($paidExtras as $ex) {
                                $paymentRows[] = [
                                    'id' => 'E' . $ex->id,
                                    'by' => 'دفع إلكتروني',
                                    'date' => $ex->paid_at ?? $ex->updated_at,
                                    'notes' => 'دفعة إضافية: ' . $ex->description,
                                    'method' => 'بطاقة',
                                    'amount' => (float) $ex->amount,
                                ];
                            }
                            $paidAmount = collect($paymentRows)->sum('amount');
                            $remainingAmount = max(0, $totalWithVat - $paidAmount);

                            // بيانات الرحلة لسند القبض (عدّل أسماء الأعمدة حسب جدول الرحلات عندك)
                            $dep = $trip?->departure_at ? \Carbon\Carbon::parse($trip->departure_at) : null;
                            $retRaw = $trip->return_at ?? ($trip->return_date ?? null);
                            $ret = $retRaw ? \Carbon\Carbon::parse($retRaw) : null;
                            $timeOf = fn($d) => $d && $d->format('H:i') !== '00:00' ? $d->format('H:i') : '-';
                            $tripDays = $trip->duration_days ?? null;
                            $tripNights = $trip->duration_nights ?? ($tripDays ? max(0, $tripDays - 1) : null);

                            $mealFlags = ['bf' => false, 'ln' => false, 'dn' => false];
                            foreach ($seatsData as $sd) {
                                foreach (['bf', 'ln', 'dn'] as $k) {
                                    if ($sd[$k]) {
                                        $mealFlags[$k] = true;
                                    }
                                }
                                $pk = strtolower($sd['meal_pkg']);
                                if (in_array($pk, ['full', 'all'])) {
                                    $mealFlags = ['bf' => true, 'ln' => true, 'dn' => true];
                                } else {
                                    foreach (explode('_', $pk) as $part) {
                                        if (isset($mealFlags[$part])) {
                                            $mealFlags[$part] = true;
                                        }
                                    }
                                }
                            }
                            $mealNames = ['bf' => 'فطور', 'ln' => 'غداء', 'dn' => 'عشاء'];
                            $mealsOn = array_keys(array_filter($mealFlags));
                            $mealText =
                                count($mealsOn) === 3
                                    ? 'شامل'
                                    : (count($mealsOn)
                                        ? implode('، ', array_map(fn($k) => $mealNames[$k], $mealsOn))
                                        : 'بدون');

                            $isMale = fn($s) => in_array(
                                strtolower((string) ($s->passenger_gender ?? ($s->gender ?? ''))),
                                ['male', 'm', 'ذكر'],
                            );
                            $isFemale = fn($s) => in_array(
                                strtolower((string) ($s->passenger_gender ?? ($s->gender ?? ''))),
                                ['female', 'f', 'أنثى', 'انثى'],
                            );
                            $menCount = $booking->bookingSeats->filter($isMale)->count();
                            $womenCount = $booking->bookingSeats->filter($isFemale)->count();
                            $mainPassengers = collect($seatsData)->where('room_type', '!=', 'additional')->count();
                            $hasSharedSeat = collect($seatsData)->contains('is_shared', true);

                            $agencyObj = $trip?->agency;
                            $agencyLogo = null;
                            if (!empty($agencyObj?->iconA)) {
                                $agencyLogo = str_starts_with($agencyObj->iconA, 'http')
                                    ? $agencyObj->iconA
                                    : asset(
                                        str_starts_with($agencyObj->iconA, 'storage/')
                                            ? $agencyObj->iconA
                                            : 'storage/' . $agencyObj->iconA,
                                    );
                            }
                        @endphp

                        {{-- إعطاء لون خلفية خفيف باللون الأحمر للصف عندما تكون الحالة طلب إلغاء --}}
                        <tr class="transition-colors {{ $booking->status === 'requestC' ? 'bg-red-50/70 hover:bg-red-100/70 border-r-4 border-r-red-500' : 'hover:bg-gray-50/30' }}"
                            x-show="visible"                             x-data="{
                                showEditModal: false,
                                showInvoiceModal: false,
                                showUnverified: false,
                                showActions: false,
                                actionModal: null,
                                actionBusy: false,
                                smsBody: '',
                                waBody: '',
                                creditAmount: 0,
                                pointsAmount: 0,
                                customerLink: '',
                                actionMsg: '',
                                status: @js($booking->status),
                                filterKey: @js($filterKey),
                                hasUnverified: @js(count($unverified) > 0),
                                haystack: @js(strtolower($booking->id . ' ' . ($booking->customer_name ?? '') . ' ' . ($booking->customer_phone ?? ''))),
                                get visible() {
                                    if (this.status === 'locked') return false;
                                    const statusOk = filterStatus === 'all' ||
                                        (filterStatus === 'unverified' ? this.hasUnverified : filterStatus === this.filterKey);
                                    const q = (searchQuery || '').toLowerCase();
                                    return statusOk && (!q || this.haystack.includes(q));
                                }
                            }">

                            {{-- رقم الحجز والعميل + المرافقون غير الموثقين --}}
                            <td class="p-6">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 {{ $booking->status === 'requestC' ? 'bg-red-200 text-red-700' : 'bg-blue-50 text-blue-600' }} rounded-xl flex items-center justify-center font-bold text-xs">
                                        #</div>
                                    <div>
                                        <p class="font-black text-gray-800 text-sm">BK-{{ $booking->id }}</p>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase">
                                            {{ $booking->customer_name }}</p>
                                    </div>
                                </div>

                                {{-- شارة تنبيه بارزة إذا كان هناك طلب إلغاء --}}
                                @if ($booking->status === 'requestC')
                                    <div class="mt-2">
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-black bg-red-600 text-white shadow-sm animate-pulse">
                                            ⚠️ مطلوب إلغاء هذا الحجز
                                        </span>
                                    </div>
                                @endif

                                @if (count($unverified) > 0)
                                    <div class="mt-3 max-w-[220px]">
                                        <button type="button" @click="showUnverified = !showUnverified"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-[10px] font-black transition">
                                            ⚠️ {{ count($unverified) }}
                                            {{ count($unverified) === 1 ? 'مرافق غير موثق' : 'مرافقين غير موثقين' }}
                                            <span class="transition-transform"
                                                :class="showUnverified ? 'rotate-180' : ''">▾</span>
                                        </button>
                                        <ul x-show="showUnverified" x-transition style="display: none;"
                                            class="mt-2 space-y-1">
                                            @foreach ($unverified as $u)
                                                <li
                                                    class="flex items-center justify-between gap-2 bg-rose-50/60 border border-rose-100 rounded-lg px-2.5 py-1.5">
                                                    <span
                                                        class="text-[11px] font-bold text-rose-900 truncate">{{ $u['name'] }}</span>
                                                    <span class="text-[10px] font-mono text-rose-600"
                                                        dir="ltr">{{ $u['national_id'] }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </td>

                            {{-- الرحلة --}}
                            <td class="p-6">
                                <p class="text-xs font-bold text-gray-700">{{ $trip->title ?? 'رحلة غير محددة' }}</p>
                                <p class="text-[10px] text-gray-400 italic">
                                    بتاريخ: {{ $booking->created_at?->format('Y-m-d') ?? 'غير متوفر' }}
                                </p>
                            </td>

                            {{-- طلب التعديل --}}
                            <td class="p-6">
                                @if (!empty($booking->edit_notes))
                                    <div
                                        class="flex items-center justify-between gap-3 bg-amber-50/80 p-3 rounded-2xl border border-amber-200/70">
                                        <p class="flex-1 text-right text-xs font-bold text-slate-800 leading-relaxed">
                                            {{ $booking->edit_notes }}</p>
                                        <form action="{{ route('agency.booking.confirm-edit', $booking->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('هل تم تنفّيذ طلب التعديل بنجاح وإغلاق الطلب؟');"
                                            class="shrink-0">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" title="تأكيد وتنفيذ التعديل"
                                                class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition-all shadow-sm shadow-emerald-600/20 active:scale-95 flex items-center gap-1.5">
                                                <span>✅</span><span>تم التعديل</span>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1 text-xs font-bold text-slate-400 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100">
                                        لا يوجد طلب
                                    </span>
                                @endif
                            </td>

                            {{-- المقاعد --}}
                            <td class="p-6">
                                @if ($totalSeatsCount > 0)
                                    <div class="flex gap-1 flex-wrap max-w-[140px] items-center" x-data="{ openSeats: false }">
                                        @foreach (array_slice($allSeatNumbers, 0, 3) as $sNum)
                                            <span
                                                class="px-2 py-0.5 bg-blue-600 text-white rounded text-[10px] font-black shadow-sm">{{ $sNum }}</span>
                                        @endforeach

                                        @if ($totalSeatsCount > 3)
                                            <div class="relative inline-block">
                                                <button type="button" @click="openSeats = !openSeats"
                                                    @click.away="openSeats = false"
                                                    class="px-2 py-0.5 bg-blue-100 text-blue-700 hover:bg-blue-200 rounded text-[10px] font-black transition cursor-pointer">
                                                    +{{ $totalSeatsCount - 3 }} المزيد
                                                </button>
                                                <div x-show="openSeats" x-transition style="display: none;"
                                                    class="absolute right-0 bottom-full mb-2 z-30 w-48 p-3 bg-white border border-gray-100 rounded-xl shadow-xl flex flex-wrap gap-1 text-right">
                                                    <p
                                                        class="w-full text-[10px] text-gray-400 font-bold mb-1 border-b pb-1">
                                                        كافة المقاعد ({{ $totalSeatsCount }}):</p>
                                                    @foreach ($allSeatNumbers as $sNum)
                                                        <span
                                                            class="px-2 py-0.5 bg-blue-600 text-white rounded text-[10px] font-black">{{ $sNum }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-red-400 font-normal italic">ملغي</span>
                                @endif
                            </td>

                            {{-- نوع الغرفة --}}
                            <td class="p-6">
                                <div class="flex flex-col gap-1 max-w-[140px]" x-data="{ openRooms: false }">
                                    @if ($totalRoomsCount > 0)
                                        @foreach (array_slice($allRoomsList, 0, 2) as $r)
                                            <span
                                                class="px-2 py-0.5 border rounded-md text-[11px] font-black shadow-sm text-center {{ $r['type'] === 'additional' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-blue-50 text-blue-700 border-blue-200/80' }}">
                                                {{ $r['label'] }}
                                            </span>
                                        @endforeach

                                        @if ($totalRoomsCount > 2)
                                            <div class="relative inline-block text-center mt-0.5">
                                                <button type="button" @click="openRooms = !openRooms"
                                                    @click.away="openRooms = false"
                                                    class="w-full px-2 py-0.5 bg-gray-100 hover:bg-gray-200 border border-gray-200 text-gray-700 rounded text-[10px] font-black transition cursor-pointer">
                                                    +{{ $totalRoomsCount - 2 }} باقي الغرف
                                                </button>
                                                <div x-show="openRooms" x-transition style="display: none;"
                                                    class="absolute right-0 bottom-full mb-2 z-30 w-48 p-3 bg-white border border-gray-100 rounded-xl shadow-xl flex flex-col gap-1 text-right">
                                                    <p
                                                        class="w-full text-[10px] text-gray-400 font-bold mb-1 border-b pb-1">
                                                        تفاصيل الغرف ({{ $totalRoomsCount }}):</p>
                                                    @foreach ($allRoomsList as $r)
                                                        <span
                                                            class="px-2 py-0.5 border rounded-md text-[10px] font-black text-center {{ $r['type'] === 'additional' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-blue-50 text-blue-700 border-blue-200/80' }}">
                                                            {{ $r['label'] }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        @php $fallbackType = strtolower(trim($booking->room_type ?? '')); @endphp
                                        <span
                                            class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded-md text-[11px] font-black text-center">
                                            {{ $roomNamesAr[$fallbackType] ?? ($booking->room_type ?: 'غير محدد') }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- المبلغ --}}
                            <td class="p-6">
                                @php
                                    $baseAmount =
                                        (float) ($booking->net_amount > 0
                                            ? $booking->net_amount
                                            : $booking->agency_amount);
                                @endphp
                                <p class="font-black text-gray-800">
                                    {{ number_format($baseAmount + $extraPaidTotal, 2) }}
                                    <small class="text-[10px] text-gray-400">ر.س</small>
                                </p>
                                @if ($extraPaidTotal > 0)
                                    <div
                                        class="mt-1 text-[10px] font-bold text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-2 py-1 w-fit leading-relaxed">
                                        <span class="block">قيمة الحجز: {{ number_format($baseAmount, 2) }}</span>
                                        <span class="block">+ مدفوعات إضافية:
                                            {{ number_format($extraPaidTotal, 2) }}</span>
                                    </div>
                                @endif
                            </td>

                            {{-- حالة الطلب --}}
                            <td class="p-6 text-center">
                                @if (in_array($booking->status, ['confirmed', 'approved']))
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-100 text-green-700 rounded-lg text-[10px] font-black"><span
                                            class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> تم التأكيد</span>
                                @elseif($booking->status == 'cancelled')
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-[10px] font-black"><span
                                            class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> حجز ملغي</span>
                                @elseif($booking->status == 'completed' || $tripPast)
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-lg text-[10px] font-black"><span
                                            class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> تمت الرحلة</span>
                                @elseif($booking->status == 'requestC')
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 text-white rounded-lg text-[10px] font-black shadow-sm ring-2 ring-red-300 animate-pulse">
                                        <span class="w-1.5 h-1.5 bg-white rounded-full"></span> ⚠️ طلب إلغاء
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-100 text-amber-700 rounded-lg text-[10px] font-black"><span
                                            class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span> قيد
                                        المراجعة</span>
                                @endif
                            </td>

                            {{-- حالة الدفع (تم إصلاح خطأ: كانت الرحلات الماضية تظهر "تم الرد" تلقائياً) --}}
                            <td class="p-6 text-center">
                                @if ($booking->payment_status === 'paid')
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-100 text-green-700 rounded-lg text-[10px] font-black"><span
                                            class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> تم الدفع</span>
                                @elseif($booking->payment_status === 'paidof')
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-100 text-amber-700 rounded-lg text-[10px] font-black"><span
                                            class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span> دفع وكالة</span>
                                @elseif($booking->payment_status === 'failed')
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-[10px] font-black"><span
                                            class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> فشلت</span>
                                @elseif($booking->payment_status === 'refunded')
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-lg text-[10px] font-black"><span
                                            class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> تم الرد</span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-100 text-amber-700 rounded-lg text-[10px] font-black"><span
                                            class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span>
                                        مراجعة</span>
                                @endif
                            </td>

                            {{-- أزرار التحكم + نافذة التعديل +  الفاتورة --}}
                            <td class="p-6 text-center">
                                <div class="flex justify-center gap-2 relative">
                                    <div class="relative">
                                        <button type="button" @click="showActions = !showActions" class="p-2 bg-slate-800 text-white rounded-xl text-[10px] font-black">إجراءات ▾</button>
                                        <div x-show="showActions" @click.outside="showActions=false" class="absolute left-0 z-40 mt-1 w-52 bg-white border rounded-xl shadow-lg text-right text-xs font-bold overflow-hidden" style="display:none">
                                            <button type="button" class="block w-full px-3 py-2 hover:bg-slate-50" @click="showEditModal=true; showActions=false">تعديل الحجز</button>
                                            <button type="button" class="block w-full px-3 py-2 hover:bg-slate-50" @click="showInvoiceModal=true; showActions=false">سند قبض / طباعة</button>
                                            <button type="button" class="block w-full px-3 py-2 hover:bg-slate-50" @click="actionModal='invoice'; showActions=false">إصدار / طباعة فاتورة</button>
                                            <button type="button" class="block w-full px-3 py-2 hover:bg-slate-50" @click="actionModal='sms'; showActions=false">إرسال SMS</button>
                                            <button type="button" class="block w-full px-3 py-2 hover:bg-slate-50" @click="actionModal='whatsapp'; showActions=false">شارك عبر واتساب</button>
                                            <button type="button" class="block w-full px-3 py-2 hover:bg-slate-50" @click="actionModal='link'; showActions=false">رابط الزبون</button>
                                            <button type="button" class="block w-full px-3 py-2 hover:bg-slate-50" @click="actionModal='credit'; showActions=false">إضافة رصيد</button>
                                            <button type="button" class="block w-full px-3 py-2 hover:bg-slate-50" @click="actionModal='points'; showActions=false">صرف نقاط</button>
                                        </div>
                                    </div>
                                    <button type="button" @click="showEditModal = true"
                                        class="inline-flex items-center justify-center p-2 text-blue-600 bg-blue-50 hover:bg-blue-600 hover:text-white rounded-xl transition-all duration-200 shadow-sm hover:shadow-md hover:shadow-blue-200"
                                        title="تعديل الحجز">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </button>

                                    <form action="{{ route('agency.bookings.cancel', $booking->id) }}" method="POST"
                                        class="inline-block"
                                        onsubmit="return confirm('هل أنت تأكد من رغبتك في إلغاء هذا الحجز؟');">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex items-center justify-center p-2 text-red-600 bg-red-50 hover:bg-red-600 hover:text-white rounded-xl transition-all duration-200 shadow-sm hover:shadow-md hover:shadow-red-200"
                                            title="إلغاء الحجز">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </form>

                                    <button type="button" @click="showInvoiceModal = true"
                                        class="p-2 bg-white border border-gray-100 text-gray-400 hover:text-gray-700 rounded-xl hover:bg-gray-50 transition"
                                        title=" فاتورة / طباعة">🖨️</button>
                                </div>

                                <template x-if="actionModal">
                                    <div class="fixed inset-0 z-[70] bg-slate-900/50 flex items-center justify-center p-4" @click.self="actionModal=null">
                                        <div class="bg-white rounded-2xl p-5 w-full max-w-md text-right space-y-3" @click.stop>
                                            <h4 class="font-black text-sm" x-text="{
                                                invoice: 'إصدار الفاتورة',
                                                sms: 'إرسال SMS',
                                                whatsapp: 'مشاركة واتساب',
                                                link: 'رابط الزبون',
                                                credit: 'إضافة رصيد للوكالة',
                                                points: 'صرف نقاط'
                                            }[actionModal]"></h4>
                                            <p class="text-xs text-slate-500">{{ $booking->customer_name }} — {{ $booking->customer_phone }}</p>
                                            <p class="text-xs text-rose-600" x-text="actionMsg"></p>
                                            <template x-if="actionModal==='sms'">
                                                <textarea x-model="smsBody" class="w-full border rounded-xl p-2 text-xs" rows="3" placeholder="نص الرسالة"></textarea>
                                            </template>
                                            <template x-if="actionModal==='whatsapp'">
                                                <textarea x-model="waBody" class="w-full border rounded-xl p-2 text-xs" rows="3" placeholder="تفاصيل الحجز + رابط الزبون"></textarea>
                                            </template>
                                            <template x-if="actionModal==='credit'">
                                                <input type="number" min="0.01" step="0.01" x-model.number="creditAmount" class="w-full border rounded-xl p-2 text-xs" placeholder="المبلغ">
                                            </template>
                                            <template x-if="actionModal==='points'">
                                                <input type="number" min="1" x-model.number="pointsAmount" class="w-full border rounded-xl p-2 text-xs" placeholder="عدد النقاط">
                                            </template>
                                            <p x-show="customerLink" class="text-[10px] break-all bg-slate-50 p-2 rounded" x-text="customerLink"></p>
                                            <div class="flex gap-2">
                                                <button type="button" :disabled="actionBusy" class="flex-1 py-2 bg-indigo-600 text-white rounded-xl text-xs font-black"
                                                    @click="runBookingAction({{ $booking->id }}, '{{ $booking->customer_phone }}', @js($booking->customer_name), $event)">تنفيذ</button>
                                                <button type="button" class="px-4 py-2 bg-slate-100 rounded-xl text-xs font-black" @click="actionModal=null">إغلاق</button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                {{-- ===================== نافذة الفاتورة  ===================== --}}
                                <template x-if="showInvoiceModal">
                                    <div @keydown.escape.window="showInvoiceModal = false"
                                        @click.self="showInvoiceModal = false"
                                        class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm overflow-y-auto p-4 text-right"
                                        dir="rtl">
                                        <div class="max-w-4xl mx-auto">
                                            <div class="flex justify-between items-center mb-3 gap-3">
                                                <h3 class="text-white font-black text-sm"> سند قبض رقم R{{ $booking->id }}
                                                </h3>
                                                <div class="flex gap-2">
                                                    <button type="button"
                                                        onclick="printReceipt('receipt-{{ $booking->id }}')"
                                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-xl shadow-md">
                                                        🖨️ طباعة
                                                    </button>
                                                    <button type="button" @click="showInvoiceModal = false"
                                                        class="px-4 py-2 bg-white/90 hover:bg-white text-gray-700 text-xs font-black rounded-xl">
                                                        إغلاق ✕
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="rc" id="receipt-{{ $booking->id }}">
                                                {{-- رأس السند --}}
                                                <table class="rc-head">
                                                    <tr>
                                                        <td class="rc-head-contact">
                                                            @if (!empty($agencyObj?->phone))
                                                                <div>أرقام التواصل: <span
                                                                        dir="ltr">{{ $agencyObj->phone }}</span>
                                                                </div>
                                                            @endif
                                                            @if (!empty($agencyObj?->email))
                                                                <div dir="ltr">{{ $agencyObj->email }}</div>
                                                            @endif
                                                            @if (!empty($agencyObj?->address))
                                                                <div>{{ $agencyObj->address }}</div>
                                                            @endif
                                                        </td>
                                                        <td class="rc-head-agency">
                                                            @if ($agencyLogo)
                                                                <img src="{{ $agencyLogo }}" alt=""
                                                                    class="rc-logo">
                                                            @endif
                                                            <div class="rc-agency-name">
                                                                {{ $agencyObj->name ?? ($trip->agency_name ?? 'وكالة السفر') }}
                                                            </div>
                                                            @if (!empty($agencyObj?->commercial_register))
                                                                <div class="rc-small">السجل التجاري:
                                                                    {{ $agencyObj->commercial_register }}</div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                </table>

                                                <div class="rc-title"> الفاتورة</div>

                                                <table class="rc-meta">
                                                    <tr>
                                                        <td style="text-align:right">
                                                            رقم الفاتورة <b>R{{ $booking->id }}</b>
                                                        </td>
                                                        <td style="text-align:center">أنشئ بواسطة المدير</td>
                                                        <td style="text-align:left">
                                                            تاريخ الإنشاء
                                                            <b>{{ $booking->created_at?->format('d/m/Y') }}</b>
                                                            @if ($hijri($booking->created_at))
                                                                <br>{{ $hijri($booking->created_at) }}
                                                            @endif
                                                        </td>
                                                    </tr>
                                                </table>

                                                {{-- بيانات العميل والرحلة --}}
                                                <table>
                                                    <tr>
                                                        <th style="width:14%">السيد/السيدة</th>
                                                        <td colspan="3">{{ $booking->customer_name }}</td>
                                                        <th style="width:12%">الهاتف</th>
                                                        <td colspan="2" dir="ltr">
                                                            {{ $booking->customer_phone ?: '-' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="2">وذلك عن الرحلة</th>
                                                        <th>المدينة</th>
                                                        <th>الفندق</th>
                                                        <th>الوجبات</th>
                                                        <th>شركة الطيران</th>
                                                        <th>الترانزيت</th>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2">{{ $trip->title ?? '-' }}</td>
                                                        <td>{{ $trip->to_city ?? ($trip->destination ?? '-') }}</td>
                                                        <td>{{ $trip->hotel_name ?? ($trip->hotel ?? '-') }}</td>
                                                        <td>{{ $mealText }}</td>
                                                        <td>{{ $trip->airline ?? ($trip->airline_name ?? '-') }}</td>
                                                        <td>{{ $trip->transit ?? '-' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="2">تاريخ السفر</th>
                                                        <th>وقت السفر</th>
                                                        <th>تاريخ الرجوع</th>
                                                        <th>وقت الرجوع</th>
                                                        <th>عدد الأيام</th>
                                                        <th>الليالي</th>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2">{{ $dep?->format('Y-m-d') ?? '-' }}</td>
                                                        <td>{{ $timeOf($dep) }}</td>
                                                        <td>{{ $ret?->format('Y-m-d') ?? '-' }}</td>
                                                        <td>{{ $timeOf($ret) }}</td>
                                                        <td>{{ $tripDays ?? '-' }}</td>
                                                        <td>{{ $tripNights ?? '-' }}</td>
                                                    </tr>
                                                    @if (!empty($trip->description))
                                                        <tr>
                                                            <td colspan="7">
                                                                {{ \Illuminate\Support\Str::limit(strip_tags($trip->description), 220) }}
                                                            </td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <th colspan="2">عدد المسافرين</th>
                                                        <th>عدد الرجال</th>
                                                        <th>عدد النساء</th>
                                                        <th>عدد المقاعد</th>
                                                        <th>عدد الأسرة</th>
                                                        <th>نوع الفرز</th>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2">{{ $mainPassengers }}</td>
                                                        <td>{{ $menCount ?: '-' }}</td>
                                                        <td>{{ $womenCount ?: '-' }}</td>
                                                        <td>{{ $booking->bookingSeats->count() }}</td>
                                                        <td>{{ $mainPassengers }}</td>
                                                        <td>{{ $hasSharedSeat ? 'مشتركة' : 'خاصة' }}</td>
                                                    </tr>
                                                </table>

                                                {{-- المسافرون والأسعار --}}
                                                <table>
                                                    <tr>
                                                        <th style="width:5%">#</th>
                                                        <th style="width:22%">الاسم</th>
                                                        <th style="width:16%">الهوية / Passport</th>
                                                        <th style="width:12%">الميلاد</th>
                                                        <th style="width:7%">العمر</th>
                                                        <th>طلبات أخرى</th>
                                                        <th style="width:14%">السعر</th>
                                                    </tr>
                                                    @foreach ($booking->bookingSeats as $i => $seat)
                                                        @php
                                                            $line = $seatLines[$i] ?? [
                                                                'base' => 0,
                                                                'room' => 0,
                                                                'extras' => [],
                                                            ];
                                                            $nid = trim(
                                                                (string) ($seat->passenger_passport ??
                                                                    $seat->passenger_national_id),
                                                            );
                                                            $nid =
                                                                $nid === '' ||
                                                                $nid === '0000000000' ||
                                                                str_starts_with($nid, '99000000')
                                                                    ? '-'
                                                                    : $nid;
                                                            $birthRaw =
                                                                $seat->passenger_birthdate ??
                                                                ($seat->passenger_birth_date ?? null);
                                                            $birth = $birthRaw
                                                                ? \Carbon\Carbon::parse($birthRaw)
                                                                : null;
                                                            $extrasTxt = $line['extras'];
                                                            if (($seatsData[$i]['room_type'] ?? '') === 'additional') {
                                                                array_unshift($extrasTxt, 'مقعد إضافي');
                                                            } elseif (!empty($seatsData[$i]['is_shared'])) {
                                                                array_unshift($extrasTxt, 'غرفة مشتركة');
                                                            }
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td>{{ $seat->passenger_name }}</td>
                                                            <td dir="ltr">{{ $nid }}</td>
                                                            <td>{{ $birth?->format('Y-m-d') ?? '' }}</td>
                                                            <td>{{ $birth?->age ?? '' }}</td>
                                                            <td>{{ implode(' ، ', $extrasTxt) }}</td>
                                                            <td>{{ number_format($line['base'] + $line['room'], 2) }} ريال
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                    <tr>
                                                        <td colspan="5" class="rc-empty"></td>
                                                        <th>المبلغ بدون ضريبة</th>
                                                        <td>{{ number_format($grossTotal, 2) }} ريال</td>
                                                    </tr>
                                                    @if ($extraPaidTotal > 0)
                                                        <tr>
                                                            <td colspan="5" class="rc-empty"></td>
                                                            <th>مدفوعات إضافية</th>
                                                            <td>{{ number_format($extraPaidTotal, 2) }} ريال</td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <td colspan="5" class="rc-empty"></td>
                                                        <th>VAT (Z0)</th>
                                                        <td>{{ number_format($vatAmount, 2) }} ريال</td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="5" class="rc-empty"></td>
                                                        <th>المبلغ شامل الضريبة</th>
                                                        <td><b>{{ number_format($totalWithVat, 2) }} ريال</b></td>
                                                    </tr>
                                                </table>

                                                {{-- الأرصدة --}}
                                                <table>
                                                    <tr>
                                                        <th colspan="6" style="background:#777;color:#fff">الأرصدة</th>
                                                    </tr>
                                                    <tr>
                                                        <th style="width:8%">ID</th>
                                                        <th style="width:14%">أنشئ بواسطة</th>
                                                        <th style="width:18%">تاريخ الإنشاء</th>
                                                        <th>الملاحظات</th>
                                                        <th style="width:14%">طريقة الدفع</th>
                                                        <th style="width:16%">المبلغ</th>
                                                    </tr>
                                                    @forelse ($paymentRows as $pr)
                                                        <tr>
                                                            <td>{{ $pr['id'] }}</td>
                                                            <td>{{ $pr['by'] }}</td>
                                                            <td>
                                                                {{ $pr['date'] ? \Carbon\Carbon::parse($pr['date'])->format('Y-m-d') : '' }}
                                                                @if ($pr['date'] && $hijri($pr['date']))
                                                                    <br><span
                                                                        class="rc-small">{{ \Carbon\Carbon::parse($pr['date'])->format('Y/m/d') }}</span>
                                                                @endif
                                                            </td>
                                                            <td>{{ $pr['notes'] }}</td>
                                                            <td>{{ $pr['method'] }}</td>
                                                            <td>{{ number_format($pr['amount'], 2) }} ريال</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="6" class="rc-small">لا توجد دفعات مسجلة بعد.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                    <tr>
                                                        <td colspan="4" class="rc-empty"></td>
                                                        <th>المبلغ المدفوع</th>
                                                        <td>{{ number_format($paidAmount, 2) }} ريال</td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4" class="rc-empty"></td>
                                                        <th>المبلغ شامل الضريبة</th>
                                                        <td>{{ number_format($totalWithVat, 2) }} ريال</td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4" class="rc-empty"></td>
                                                        <th>المتبقي</th>
                                                        <td><b>{{ number_format($remainingAmount, 2) }} ريال</b></td>
                                                    </tr>
                                                </table>

                                                <div class="rc-notes">
                                                    <div class="rc-notes-title">ملاحظات هامة :</div>
                                                    <ul>
                                                        @foreach ($receiptNotes as $note)
                                                            <li>{{ $note }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                {{-- =================== نهاية نافذة الفاتورة  =================== --}}

                                {{-- نافذة التعديل --}}
                                <div x-show="showEditModal" x-transition style="display: none;"
                                    @keydown.escape.window="showEditModal = false" @click.self="showEditModal = false"
                                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
                                    x-data="{
                                        cash_paid: {{ (float) ($booking->cash_paid ?? 0) }},
                                        platform_commission: {{ (float) ($booking->platform_commission ?? 0) }},
                                        standalonePrice: {{ (float) ($trip->price ?? 0) }},
                                        savedGross: {{ round($savedGross, 2) }},
                                        extraPaid: {{ round($extraPaidTotal, 2) }},
                                        baseCalc: 0,
                                        seats: @js($seatsData),
                                        roomPrices: @js($rp),
                                        roomPackagesData: @js($rpd),
                                        mealPrices: @js($mp),
                                        indivPrices: @js($ip),
                                        roomPerPerson: @js($roomPricePerPerson),
                                    
                                        init() {
                                            (this.seats || [])
                                            .forEach((_, i) => this.updateRoomPackageMeals(i));
                                            this.baseCalc = this.rawCalc;
                                        },
                                        getSelectedPackage(roomType, packageName) {
                                            if (!roomType || !packageName) return null;
                                            const list = this.roomPackagesData[roomType.toString().trim().toLowerCase()];
                                            if (!list) return null;
                                            return list.find(p => p.name.toString().trim() === packageName.toString().trim()) || null;
                                        },
                                        updateRoomPackageMeals(index) {
                                            const s = this.seats[index];
                                            if (!s) return;
                                    
                                            if (s.room_type === 'additional') {
                                                s.meal_pkg = '';
                                                s.room_package = '';
                                                s.bf = s.ln = s.dn = false;
                                                s._auto = { bf: false, ln: false, dn: false };
                                                (s.services || []).forEach(srv => srv.selected = false);
                                                return;
                                            }
                                    
                                            s._auto = s._auto || { bf: false, ln: false, dn: false };
                                            const map = { bf: 'breakfast', ln: 'lunch', dn: 'dinner' };
                                    
                                            Object.keys(map).forEach(k => {
                                                if (s._auto[k]) {
                                                    s[k] = false;
                                                    s._auto[k] = false;
                                                }
                                            });
                                    
                                            const pkg = this.getSelectedPackage(s.room_type, s.room_package);
                                            if (pkg && pkg.meals) {
                                                Object.keys(map).forEach(k => {
                                                    if (pkg.meals[map[k]]) {
                                                        s[k] = true;
                                                        s._auto[k] = true;
                                                    }
                                                });
                                            }
                                        },
                                        isMealIncludedInPackage(index, mealType) {
                                            const s = this.seats[index];
                                            if (!s) return false;
                                            const pkg = this.getSelectedPackage(s.room_type, s.room_package);
                                            return !!(pkg && pkg.meals && pkg.meals[mealType]);
                                        },
                                        get rawCalc() {
                                            if (!this.seats || this.seats.length === 0) {
                                                return 0;
                                            }
                                    
                                            let total = 0;
                                            const roomCounts = {};
                                            const capacities = { single: 1, double: 2, triple: 3, quad: 4 };
                                    
                                            this.seats.forEach(s => {
                                                const rType = (s.room_type || '').toString().toLowerCase().trim();
                                                const solo = rType === 'additional' || !!s.is_shared;
                                    
                                                if (solo) {
                                                    total += parseFloat(this.standalonePrice) || 0;
                                                } else if (capacities[rType]) {
                                                    roomCounts[rType] = (roomCounts[rType] || 0) + 1;
                                                }
                                    
                                                if (rType === 'additional') return;
                                    
                                                const pkg = this.getSelectedPackage(s.room_type, s.room_package);
                                                if (pkg) total += parseFloat(pkg.price) || 0;
                                    
                                                if (s.meal_pkg && Object.prototype.hasOwnProperty.call(this.mealPrices, s.meal_pkg)) {
                                                    total += parseFloat(this.mealPrices[s.meal_pkg]) || 0;
                                                } else {
                                                    if (s.bf && !(pkg && pkg.meals && pkg.meals.breakfast)) total += parseFloat(this.indivPrices.bf) || 0;
                                                    if (s.ln && !(pkg && pkg.meals && pkg.meals.lunch)) total += parseFloat(this.indivPrices.ln) || 0;
                                                    if (s.dn && !(pkg && pkg.meals && pkg.meals.dinner)) total += parseFloat(this.indivPrices.dn) || 0;
                                                }
                                    
                                                (s.services || []).forEach(srv => {
                                                    if (srv.selected) total += parseFloat(srv.price) || 0;
                                                });
                                            });
                                    
                                            for (const type in roomCounts) {
                                                const units = this.roomPerPerson ?
                                                    roomCounts[type] :
                                                    Math.ceil(roomCounts[type] / (capacities[type] || 1));
                                                total += units * (parseFloat(this.roomPrices[type]) || 0);
                                            }
                                    
                                            return total;
                                        },
                                        get liveGross() {
                                            const saved = parseFloat(this.savedGross) || 0;
                                            if (saved <= 0) return Math.max(0, this.rawCalc);
                                            return Math.max(0, Math.round((saved + this.rawCalc - this.baseCalc) * 100) / 100);
                                        },
                                        get liveTotalAmount() {
                                            return this.liveGross;
                                        },
                                        get liveNet() {
                                            const cash = parseFloat(this.cash_paid) || 0;
                                            const commission = parseFloat(this.platform_commission) || 0;
                                            return Math.max(0, this.liveGross - cash - commission);
                                        },
                                        get liveNetWithExtra() {
                                            return Math.round((this.liveNet + (parseFloat(this.extraPaid) || 0)) * 100) / 100;
                                        }
                                    }">


                                    <div
                                        class="bg-white w-full max-w-2xl rounded-[2rem] p-6 shadow-2xl border border-gray-50 text-right overflow-y-auto max-h-[90vh] custom-scrollbar">

                                        <div class="flex justify-between items-center border-b border-gray-100 pb-4 mb-4">
                                            <h3 class="font-black text-gray-800 text-lg">تعديل الحجز
                                                #BK-{{ $booking->id }}</h3>
                                            <button type="button" @click="showEditModal = false"
                                                class="text-gray-400 hover:text-gray-600 font-bold text-xl">✕</button>
                                        </div>

                                        <div class="mb-4" x-data="{ logs: [], loaded: false }" x-init="
                                            fetch('{{ route('agency.bookings.changes', $booking) }}')
                                                .then(r => r.json())
                                                .then(d => { logs = d.data || d; loaded = true; })
                                                .catch(() => loaded = true);
                                        ">
                                            <h4 class="text-xs font-black mb-2">سجل التعديلات</h4>
                                            <div class="max-h-40 overflow-y-auto text-[11px] border rounded-xl">
                                                <template x-for="log in logs" :key="log.id">
                                                    <div class="p-2 border-b">
                                                        <span class="font-black" x-text="log.performed_by"></span>
                                                        — <span x-text="log.action"></span>
                                                        <span class="text-slate-400" x-text="log.created_at"></span>
                                                    </div>
                                                </template>
                                                <p x-show="loaded && !logs.length" class="p-2 text-slate-400">لا توجد تعديلات بعد</p>
                                            </div>
                                        </div>
                                        {{-- السعر الإجمالي (قبل خصم العمولة والمدفوع نقداً) --}}
                                        <div class="mb-4 bg-blue-600 text-white p-4 rounded-2xl shadow-md shadow-blue-100">
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs font-black">💰 السعر الإجمالي:</span>
                                                <span class="text-xl font-black"><span
                                                        x-text="liveTotalAmount.toFixed(2)"></span> ر.س</span>
                                            </div>
                                            <div x-show="Math.abs(liveGross - savedGross) > 0.004" style="display: none;"
                                                class="mt-2 text-[11px] font-bold text-blue-100">
                                                المبلغ المحفوظ سابقاً: <span x-text="savedGross.toFixed(2)"></span> ر.س
                                            </div>
                                            @if ($extraPaidTotal > 0)
                                                <div
                                                    class="mt-2 pt-2 border-t border-white/20 text-[11px] font-bold text-blue-100 space-y-0.5">
                                                    <div>+ مدفوعات إضافية مسددة: {{ number_format($extraPaidTotal, 2) }}
                                                        ر.س</div>
                                                    <div class="text-white">
                                                        الإجمالي مع الإضافي:
                                                        <span
                                                            x-text="(liveTotalAmount + {{ $extraPaidTotal }}).toFixed(2)"></span>
                                                        ر.س
                                                    </div>
                                                </div>
                                            @endif
                                            <div x-show="(parseFloat(cash_paid) || 0) > 0 || (parseFloat(platform_commission) || 0) > 0 || extraPaid > 0"
                                                style="display: none;"
                                                class="mt-3 pt-3 border-t border-white/20 grid grid-cols-4 gap-2 text-[11px] font-bold text-blue-100">
                                                <div>مدفوع نقداً: <span
                                                        x-text="(parseFloat(cash_paid) || 0).toFixed(2)"></span></div>
                                                <div>عمولة المنصة: <span
                                                        x-text="(parseFloat(platform_commission) || 0).toFixed(2)"></span>
                                                </div>
                                                <div>الصافي: <span x-text="liveNet.toFixed(2)"></span></div>
                                                <div class="text-white">الصافي مع الإضافي: <span
                                                        x-text="liveNetWithExtra.toFixed(2)"></span></div>
                                            </div>
                                        </div>

                                        {{-- تحذير المرافقين غير الموثقين --}}
                                        @if (count($unverified) > 0)
                                            <div class="mb-4 bg-rose-50 border-2 border-rose-200 rounded-2xl p-4">
                                                <h4
                                                    class="text-xs font-black text-rose-900 flex items-center gap-1.5 mb-2">
                                                    ⚠️ مرافقون غير موثقين في هذا الحجز ({{ count($unverified) }})
                                                </h4>
                                                <ul class="space-y-1.5">
                                                    @foreach ($unverified as $u)
                                                        <li
                                                            class="flex items-center justify-between gap-2 bg-white border border-rose-100 rounded-xl px-3 py-2">
                                                            <span
                                                                class="text-xs font-bold text-rose-900">{{ $u['name'] }}</span>
                                                            <span class="text-[11px] font-mono text-rose-600"
                                                                dir="ltr">{{ $u['national_id'] }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                                <p class="text-[10px] font-bold text-rose-700/80 mt-2">
                                                    لم يرفع العميل صورة الهوية لهؤلاء المرافقين بعد.
                                                </p>
                                            </div>
                                        @endif

                                        {{-- إيصال الدفع --}}
                                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 space-y-3 mb-4">
                                            <div class="flex items-center justify-between">
                                                <label class="block text-xs font-black text-gray-700">🧾 طريقة وإيصال
                                                    الدفع</label>
                                                @php $pm = strtolower((string) $booking->payment_method); @endphp
                                                @if ($pm !== '')
                                                    @if (in_array($pm, ['mada', 'مدى']))
                                                        <span
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">💳
                                                            مدى</span>
                                                    @elseif(in_array($pm, ['balance', 'wallet', 'رصيد', 'المحفظة']))
                                                        <span
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">👛
                                                            رصيد</span>
                                                    @elseif($pm === 'moyasar')
                                                        <span
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">🌐
                                                            دفع إلكتروني (ميسر)</span>
                                                    @else
                                                        <span
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-700 border border-gray-200">💰
                                                            {{ $booking->payment_method }}</span>
                                                    @endif
                                                @endif
                                            </div>

                                            @if ($booking->bank_receipt)
                                                <div
                                                    class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-2">
                                                    <a href="{{ asset($booking->bank_receipt) }}" target="_blank">
                                                        <img src="{{ asset($booking->bank_receipt) }}" alt="إيصال الدفع"
                                                            class="w-full max-h-56 object-contain rounded-lg hover:scale-102 transition-transform duration-200">
                                                    </a>
                                                </div>
                                            @else
                                                <div
                                                    class="bg-amber-50/60 p-3 rounded-xl border border-amber-200/60 text-center text-xs font-bold text-amber-700">
                                                    ⚠️ لا توجد صورة إيصال مرفقة لهذا الحجز. (قد يكون الدفع إلكترونياً)
                                                </div>
                                            @endif
                                        </div>
                                        @include('agency.bookings._client_requests', [
                                            'booking' => $booking,
                                        ])
                                        <form method="POST" action="{{ route('agency.bookings', $booking->id) }}"
                                            class="space-y-6 text-right">
                                            @csrf
                                            @method('PUT')

                                            {{-- بيانات العميل --}}
                                            <div class="bg-blue-50/40 p-4 rounded-2xl border border-blue-100/50">
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="block text-[11px] font-black text-gray-500 mb-1">اسم
                                                            العميل الرئيسي</label>
                                                        <input type="text" name="customer_name"
                                                            value="{{ $booking->customer_name }}" required
                                                            class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                                                    </div>
                                                    <div>
                                                        <label class="block text-[11px] font-black text-gray-500 mb-1">رقم
                                                            الهاتف</label>
                                                        <input type="text" name="customer_phone"
                                                            value="{{ $booking->customer_phone }}" dir="ltr"
                                                            class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none text-right">
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- تفاصيل المسافرين --}}
                                            @if ($booking->bookingSeats->count() > 0)
                                                <div class="space-y-6 border-t border-gray-100 pt-4" dir="rtl">
                                                    <h4 class="text-sm font-black text-gray-800">🛏️ تفاصيل المسافرين
                                                        والمقاعد الإضافية</h4>

                                                    <div class="space-y-5">
                                                        @foreach ($groupedSeats as $group)
                                                            @php $allSeatsInGroup = array_merge([$group['main']], $group['additional']); @endphp

                                                            <div
                                                                class="p-3 bg-white border-2 border-gray-100 rounded-[2.5rem] shadow-sm space-y-3 relative overflow-hidden">
                                                                <div
                                                                    class="absolute right-0 top-0 bottom-0 w-2 bg-gradient-to-b from-blue-500 to-indigo-600 opacity-70">
                                                                </div>

                                                                @foreach ($allSeatsInGroup as $seatItem)
                                                                    @php
                                                                        $seat = $seatItem['seat'];
                                                                        $index = $seatItem['index'];
                                                                        $isMainInGroup = $loop->first;
                                                                        $seatNum = str_replace(
                                                                            ', ',
                                                                            ', #',
                                                                            (string) $seatLabel($seat),
                                                                        );
                                                                        $isUnv =
                                                                            isset($unverifiedByBooking[$booking->id]) &&
                                                                            collect($unverified)->contains(
                                                                                'national_id',
                                                                                trim(
                                                                                    (string) $seat->passenger_national_id,
                                                                                ),
                                                                            );
                                                                    @endphp

                                                                    <div class="p-5 rounded-[2rem] border transition-all duration-300 shadow-inner space-y-4 mr-3 ml-1"
                                                                        :class="seats[{{ $index }}]
                                                                            .room_type === 'additional' ?
                                                                            'bg-amber-50/40 border-amber-300' :
                                                                            'bg-gray-50/80 border-gray-200'">

                                                                        <div class="flex justify-between items-center border-b pb-3"
                                                                            :class="seats[{{ $index }}]
                                                                                .room_type === 'additional' ?
                                                                                'border-amber-200/60' :
                                                                                'border-gray-200/60'">
                                                                            <div>
                                                                                <span
                                                                                    class="text-xs font-black text-gray-800 flex items-center gap-2 flex-wrap">
                                                                                    @if (!$isMainInGroup)
                                                                                        <span
                                                                                            class="text-amber-500 text-lg">↳</span>
                                                                                    @endif
                                                                                    {{ $seat->passenger_name }}
                                                                                    @if ($isUnv)
                                                                                        <span
                                                                                            class="text-[10px] bg-rose-100 text-rose-700 px-2 py-0.5 rounded-md font-bold">غير
                                                                                            موثق ⚠️</span>
                                                                                    @endif
                                                                                    <span
                                                                                        x-show="seats[{{ $index }}].room_type === 'additional'"
                                                                                        class="text-[10px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded-md font-bold">(مقعد
                                                                                        إضافي مرتبط)</span>
                                                                                </span>
                                                                                <span
                                                                                    class="text-[10px] text-gray-400 font-bold mt-1 block">المقعد
                                                                                    #{{ $seatNum }}</span>
                                                                            </div>
                                                                        </div>

                                                                        <input type="hidden"
                                                                            name="seats[{{ $seat->id }}][seat_id]"
                                                                            value="{{ $seat->id }}">

                                                                        {{-- الغرفة --}}
                                                                        <div class="space-y-3">
                                                                            <label
                                                                                class="block text-xs font-black text-gray-700">🏢
                                                                                نوع الغرفة وتفاصيل الإقامة</label>

                                                                            <div class="flex items-center justify-between p-3 rounded-xl border transition-all duration-200"
                                                                                :class="seats[{{ $index }}].is_shared ?
                                                                                    'bg-purple-50 border-purple-300' :
                                                                                    'bg-gray-50 border-gray-200'">
                                                                                <div class="flex items-center gap-2">
                                                                                    <span class="text-base">🤝</span>
                                                                                    <div>
                                                                                        <span
                                                                                            class="text-xs font-black block"
                                                                                            :class="seats[{{ $index }}]
                                                                                                .is_shared ?
                                                                                                'text-purple-900' :
                                                                                                'text-gray-700'">غرفة
                                                                                            مشتركة</span>
                                                                                        <span
                                                                                            class="text-[10px] font-bold text-gray-400">مشاركة
                                                                                            الغرفة مع مسافرين آخرين بنفس
                                                                                            الرحلة</span>
                                                                                    </div>
                                                                                </div>
                                                                                <label
                                                                                    class="relative inline-flex items-center cursor-pointer">
                                                                                    <input type="checkbox"
                                                                                        name="seats[{{ $seat->id }}][is_room_shared]"
                                                                                        value="1"
                                                                                        x-model="seats[{{ $index }}].is_shared"
                                                                                        class="sr-only peer">
                                                                                    <div
                                                                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600">
                                                                                    </div>
                                                                                </label>
                                                                            </div>

                                                                            <div
                                                                                class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                                                @foreach ($roomOptions as $roomKey => $roomDetails)
                                                                                    @php
                                                                                        $cleanKey = strtolower(
                                                                                            trim($roomKey),
                                                                                        );
                                                                                        if ($cleanKey === 'shared') {
                                                                                            continue;
                                                                                        }
                                                                                        $roomPrice =
                                                                                            $roomDetails['price'] ?? 0;
                                                                                    @endphp
                                                                                    <label
                                                                                        class="relative block cursor-pointer select-none">
                                                                                        <input type="radio"
                                                                                            name="seats[{{ $seat->id }}][room_type]"
                                                                                            value="{{ $cleanKey }}"
                                                                                            x-model="seats[{{ $index }}].room_type"
                                                                                            @change="updateRoomPackageMeals({{ $index }})"
                                                                                            class="peer hidden">
                                                                                        <div
                                                                                            class="p-3 text-xs font-bold text-center rounded-xl bg-gray-50 border border-gray-200 text-gray-600 transition-all hover:bg-gray-100 peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:text-blue-700">
                                                                                            {{ $roomIcons[$cleanKey] ?? '🏨' }}
                                                                                            {{ $roomNamesAr[$cleanKey] ?? $roomKey }}
                                                                                            <span
                                                                                                class="block text-[10px] text-blue-600 mt-1">({{ number_format($roomPrice, 2) }}
                                                                                                ر.س)</span>
                                                                                        </div>
                                                                                    </label>
                                                                                @endforeach

                                                                                <label
                                                                                    class="relative block cursor-pointer select-none">
                                                                                    <input type="radio"
                                                                                        name="seats[{{ $seat->id }}][room_type]"
                                                                                        value="additional"
                                                                                        x-model="seats[{{ $index }}].room_type"
                                                                                        @change="seats[{{ $index }}].is_shared = false; updateRoomPackageMeals({{ $index }})"
                                                                                        class="peer hidden">
                                                                                    <div
                                                                                        class="p-3 text-xs font-bold text-center rounded-xl bg-gray-50 border border-gray-200 text-gray-600 transition-all hover:bg-gray-100 peer-checked:bg-amber-50 peer-checked:border-amber-500 peer-checked:text-amber-700">
                                                                                        🪑 مقعد إضافي
                                                                                        <span
                                                                                            class="block text-[10px] text-amber-600 mt-1">(للمساحة
                                                                                            فقط)</span>
                                                                                    </div>
                                                                                </label>
                                                                            </div>
                                                                        </div>

                                                                        {{-- الوجبات --}}
                                                                        <div x-show="seats[{{ $index }}].room_type !== 'additional'"
                                                                            x-transition
                                                                            class="bg-gray-50 p-4 rounded-2xl border border-gray-100 space-y-4">
                                                                            <div
                                                                                class="flex items-center justify-between border-b border-gray-200 pb-2">
                                                                                <label
                                                                                    class="block text-xs font-black text-gray-700">🍽️
                                                                                    خيارات الوجبات</label>
                                                                            </div>

                                                                            <div class="space-y-3">
                                                                                <label
                                                                                    class="block text-[11px] font-bold text-gray-500">باقات
                                                                                    الوجبات العامة للمقعد (اختياري)</label>
                                                                                <div
                                                                                    class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 items-stretch">
                                                                                    <label
                                                                                        class="relative flex cursor-pointer select-none">
                                                                                        <input type="radio"
                                                                                            name="seats[{{ $seat->id }}][meal_package]"
                                                                                            value=""
                                                                                            x-model="seats[{{ $index }}].meal_pkg"
                                                                                            class="peer hidden">
                                                                                        <div
                                                                                            class="w-full min-h-[64px] p-2 flex flex-col justify-center items-center text-center rounded-xl border bg-white border-gray-200 text-gray-500 peer-checked:bg-gray-200 peer-checked:border-gray-400 peer-checked:text-gray-800 transition shadow-sm">
                                                                                            <span
                                                                                                class="text-xs font-bold">🚫
                                                                                                بدون باقة</span>
                                                                                        </div>
                                                                                    </label>

                                                                                    @foreach ($uiMealPackages as $mName => $mPrice)
                                                                                        @php $mKey = trim($mName); @endphp
                                                                                        <label
                                                                                            class="relative flex cursor-pointer select-none">
                                                                                            <input type="radio"
                                                                                                name="seats[{{ $seat->id }}][meal_package]"
                                                                                                value="{{ $mKey }}"
                                                                                                x-model="seats[{{ $index }}].meal_pkg"
                                                                                                class="peer hidden">
                                                                                            <div
                                                                                                class="w-full min-h-[64px] p-2 flex flex-col justify-center items-center text-center rounded-xl border border-amber-300 bg-amber-50/70 text-amber-950 hover:bg-amber-100 peer-checked:bg-gradient-to-r peer-checked:from-amber-500 peer-checked:to-orange-500 peer-checked:border-orange-600 peer-checked:text-white peer-checked:shadow-sm transition-all duration-150">
                                                                                                <span
                                                                                                    class="text-xs font-bold leading-tight block">{{ $packageLabels[$mKey] ?? $mKey }}</span>
                                                                                                <span
                                                                                                    class="text-[11px] mt-1 font-black peer-checked:text-amber-100 text-orange-600 block">{{ number_format($mPrice, 2) }}
                                                                                                    ر.س</span>
                                                                                            </div>
                                                                                        </label>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>

                                                                            <div class="space-y-2 mt-4 pt-3 border-t border-gray-200"
                                                                                x-show="!seats[{{ $index }}].meal_pkg">
                                                                                <label
                                                                                    class="block text-[11px] font-bold text-gray-500">أو
                                                                                    إضافة وجبات فردية (إذا لم تختر
                                                                                    باقة):</label>
                                                                                <div class="flex flex-wrap gap-4">
                                                                                    @foreach ([['bf', 'breakfast', '🥐 فطور', $bfPrice], ['ln', 'lunch', '🍲 غداء', $lnPrice], ['dn', 'dinner', '🍖 عشاء', $dnPrice]] as [$field, $mealKey, $mealLabel, $mealPrice])
                                                                                        <label
                                                                                            class="flex items-center gap-2 cursor-pointer select-none group"
                                                                                            :class="{
                                                                                                'opacity-50 pointer-events-none': isMealIncludedInPackage(
                                                                                                    {{ $index }},
                                                                                                    '{{ $mealKey }}'
                                                                                                )
                                                                                            }">
                                                                                            <input type="checkbox"
                                                                                                name="seats[{{ $seat->id }}][{{ $field }}]"
                                                                                                value="1"
                                                                                                x-model="seats[{{ $index }}].{{ $field }}"
                                                                                                class="w-4 h-4 rounded text-green-600 focus:ring-green-500 border-gray-300">
                                                                                            <span
                                                                                                class="text-xs font-bold text-gray-700">
                                                                                                {{ $mealLabel }}
                                                                                                <span
                                                                                                    x-show="isMealIncludedInPackage({{ $index }}, '{{ $mealKey }}')"
                                                                                                    class="text-[9px] text-green-600">(مشمول)</span>
                                                                                                <span
                                                                                                    x-show="!isMealIncludedInPackage({{ $index }}, '{{ $mealKey }}')"
                                                                                                    class="text-gray-400">({{ number_format($mealPrice, 2) }})</span>
                                                                                            </span>
                                                                                        </label>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        {{-- الخدمات الإضافية --}}
                                                                        <div x-show="seats[{{ $index }}].services && seats[{{ $index }}].services.length > 0 && seats[{{ $index }}].room_type !== 'additional'"
                                                                            x-transition
                                                                            class="bg-purple-50/60 p-4 rounded-2xl border border-purple-100/80 space-y-3 mt-3">
                                                                            <label
                                                                                class="block text-xs font-black text-purple-800">✨
                                                                                خدمات إضافية للمسافر</label>
                                                                            <div
                                                                                class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                                                <template
                                                                                    x-for="(service, sIdx) in seats[{{ $index }}].services"
                                                                                    :key="sIdx">
                                                                                    <label
                                                                                        class="relative flex items-center justify-between p-2.5 cursor-pointer select-none group bg-white border rounded-xl transition-all"
                                                                                        :class="service.selected ?
                                                                                            'border-purple-500 ring-1 ring-purple-200' :
                                                                                            'border-gray-200 hover:border-purple-300'">
                                                                                        <input type="hidden"
                                                                                            :name="'seats[{{ $seat->id }}][extra_services][' +
                                                                                            sIdx + '][name]'"
                                                                                            :value="service.name">
                                                                                        <input type="hidden"
                                                                                            :name="'seats[{{ $seat->id }}][extra_services][' +
                                                                                            sIdx + '][price]'"
                                                                                            :value="service.price">
                                                                                        <input type="hidden"
                                                                                            :name="'seats[{{ $seat->id }}][extra_services][' +
                                                                                            sIdx + '][selected]'"
                                                                                            :value="service.selected ? '1' : '0'">

                                                                                        <div
                                                                                            class="flex items-center gap-2">
                                                                                            <input type="checkbox"
                                                                                                x-model="service.selected"
                                                                                                class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500 border-gray-300">
                                                                                            <span
                                                                                                class="text-[11px] font-bold text-gray-700"
                                                                                                x-text="service.name"></span>
                                                                                        </div>
                                                                                        <span
                                                                                            class="text-[11px] font-black text-purple-600 bg-purple-50 px-2 py-0.5 rounded-md"
                                                                                            x-text="parseFloat(service.price).toFixed(2) + ' ر.س'"></span>
                                                                                    </label>
                                                                                </template>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- حالة الحجز والدفع --}}
                                            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-200 space-y-4">
                                                <div>
                                                    <label class="block text-[11px] font-black text-gray-500 mb-1">تحديث
                                                        حالة الحجز</label>
                                                    <select name="status"
                                                        class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none appearance-none cursor-pointer">
                                                        <option value="pending" @selected($booking->status == 'pending')>⏳ معلق (قيد
                                                            الانتظار)</option>
                                                        <option value="confirmed" @selected(in_array($booking->status, ['confirmed', 'approved']))>✅ مؤكد
                                                            (موافق عليه)</option>
                                                        <option value="completed" @selected($booking->status == 'completed')>🏁 مكتمل
                                                            (تمت الرحلة)</option>
                                                        <option value="cancelled" @selected($booking->status == 'cancelled')>❌ ملغي
                                                        </option>
                                                        <option value="requestC" @selected($booking->status == 'requestC')>⚠️ طلب إلغاء
                                                        </option>
                                                    </select>
                                                </div>

                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <div>
                                                        <label
                                                            class="block text-[11px] font-black text-gray-500 mb-1">تحديث
                                                            حالة الدفع</label>
                                                        <select name="payment_status"
                                                            class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none appearance-none cursor-pointer">
                                                            <option value="pending" @selected($booking->payment_status == 'pending')>⏳ لم يتم
                                                                الدفع</option>
                                                            <option value="paid" @selected($booking->payment_status == 'paid')>💵 مدفوع
                                                                (من العميل)</option>
                                                            <option value="paidof" @selected($booking->payment_status == 'paidof')>🏛️ مدفوع
                                                                (من وكالة)</option>
                                                            <option value="failed" @selected($booking->payment_status == 'failed')>🚫 فشل
                                                                الدفع</option>
                                                            <option value="refunded" @selected($booking->payment_status == 'refunded')>↩️ تم
                                                                الاسترجاع</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="block text-[11px] font-black text-gray-500 mb-1">المبلغ
                                                            الإجمالي المحفوظ (للمرجعية)</label>
                                                        <input type="text"
                                                            value="{{ number_format($savedGross, 2) }} ر.س" disabled
                                                            class="w-full bg-gray-100 border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-500 outline-none cursor-not-allowed">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                                <button type="button" @click="showEditModal = false"
                                                    class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition">إلغاء</button>
                                                <button type="submit"
                                                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-xl transition shadow-md shadow-blue-600/30">حفظ
                                                    التحديثات</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <!-- نهاية نافذة التعديل -->
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-sm font-bold text-gray-400">
                                لا توجد حجوزات متاحة حالياً.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    {{-- تنسيقات  الفاتورة (تُنسخ أيضاً لنافذة الطباعة) --}}
    <style id="receiptStyles">
        .rc {
            font-family: Tahoma, Arial, sans-serif;
            color: #111;
            direction: rtl;
            background: #fff;
            padding: 22px 26px;
            font-size: 12px;
            line-height: 1.5;
            border-radius: 6px;
        }

        .rc table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .rc th,
        .rc td {
            border: 1px solid #6b6b6b;
            padding: 5px 8px;
            text-align: center;
            font-size: 11px;
            vertical-align: middle;
        }

        .rc th {
            background: #9d9d9d;
            font-weight: 700;
        }

        .rc .rc-empty {
            border: 1px solid #6b6b6b;
            background: #fff;
        }

        .rc .rc-head,
        .rc .rc-head td,
        .rc .rc-meta,
        .rc .rc-meta td {
            border: none;
        }

        .rc .rc-head td {
            vertical-align: top;
            padding: 0;
        }

        .rc .rc-head-contact {
            text-align: left;
            font-size: 10px;
            width: 40%;
        }

        .rc .rc-head-agency {
            text-align: right;
        }

        .rc .rc-logo {
            max-height: 70px;
            max-width: 140px;
            margin-bottom: 4px;
        }

        .rc .rc-agency-name {
            font-size: 15px;
            font-weight: 800;
        }

        .rc .rc-small {
            font-size: 10px;
            color: #444;
        }

        .rc .rc-title {
            text-align: center;
            font-size: 24px;
            font-weight: 800;
            margin: 14px 0 10px;
        }

        .rc .rc-meta td {
            font-size: 11px;
            padding: 2px 0;
        }

        .rc .rc-notes {
            margin-top: 14px;
            font-size: 10.5px;
        }

        .rc .rc-notes-title {
            font-weight: 800;
            margin-bottom: 4px;
        }

        .rc .rc-notes ul {
            margin: 0;
            padding: 0 18px 0 0;
            list-style: disc;
        }

        .rc .rc-notes li {
            margin-bottom: 2px;
        }
    </style>

    <script>
        function printReceipt(id) {
            const el = document.getElementById(id);
            if (!el) return;
            const css = document.getElementById('receiptStyles').textContent;
            const w = window.open('', '_blank', 'width=900,height=1100');
            if (!w) {
                alert('اسمح بالنوافذ المنبثقة لطباعة السند');
                return;
            }
            w.document.write(
                '<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="utf-8"><title>فاتورة </title><style>' +
                css +
                '@@page{size:A4;margin:10mm}body{margin:0}.rc{padding:0}</style></head><body>' +
                el.outerHTML +
                '</body></html>'
            );
            w.document.close();
            w.addEventListener('load', function() {
                w.focus();
                w.print();
            });
        }

        async function runBookingAction(bookingId, phone, name, ev) {
            const ctx = Alpine.$data((ev || event).target.closest('[x-data]'));
            if (!ctx) return;
            ctx.actionBusy = true;
            ctx.actionMsg = '';
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const jsonHeaders = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' };
            try {
                if (ctx.actionModal === 'invoice') {
                    const res = await fetch(`/agency/bookings/${bookingId}/invoice`, { method: 'POST', headers: jsonHeaders });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'تعذر إصدار الفاتورة');
                    window.open(data.print_url, '_blank');
                    ctx.actionModal = null;
                } else if (ctx.actionModal === 'sms') {
                    const res = await fetch(`/agency/bookings/${bookingId}/sms`, { method: 'POST', headers: jsonHeaders, body: JSON.stringify({ phone, body: ctx.smsBody || `تأكيد الحجز #${bookingId} للعميل ${name}`, template_key: 'custom' }) });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'فشل الإرسال');
                    ctx.actionMsg = 'تم قبول الرسالة من المزود';
                } else if (ctx.actionModal === 'whatsapp') {
                    const res = await fetch(`/agency/bookings/${bookingId}/whatsapp`, { method: 'POST', headers: jsonHeaders, body: JSON.stringify({ phone, mode: 'manual', body: ctx.waBody || `تفاصيل حجزك رقم ${bookingId}` }) });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'فشل المشاركة');
                    if (data.wa_url) window.open(data.wa_url, '_blank');
                } else if (ctx.actionModal === 'link') {
                    const res = await fetch(`/agency/bookings/${bookingId}/customer-link`, { method: 'POST', headers: jsonHeaders });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'تعذر إنشاء الرابط');
                    ctx.customerLink = data.url;
                    await navigator.clipboard.writeText(data.url);
                    ctx.actionMsg = 'تم نسخ الرابط';
                } else if (ctx.actionModal === 'credit') {
                    const res = await fetch(`/agency/bookings/${bookingId}/credit`, { method: 'POST', headers: jsonHeaders, body: JSON.stringify({ wallet_type: 'agency', amount: ctx.creditAmount, method: 'cash', reason: 'إضافة رصيد من الحجز', idempotency_key: 'CR-' + bookingId + '-' + Date.now() }) });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'تعذر إضافة الرصيد');
                    ctx.actionMsg = 'تمت إضافة الرصيد';
                } else if (ctx.actionModal === 'points') {
                    const res = await fetch(`/agency/bookings/${bookingId}/points`, { method: 'POST', headers: jsonHeaders, body: JSON.stringify({ points: ctx.pointsAmount, notes: 'صرف نقاط' }) });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'تعذر صرف النقاط');
                    ctx.actionMsg = 'تم الصرف. الرصيد: ' + data.balance_after;
                }
            } catch (e) {
                ctx.actionMsg = e.message;
            } finally {
                ctx.actionBusy = false;
            }
        }
    </script>
@endsection
