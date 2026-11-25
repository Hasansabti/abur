@extends('layouts.agency')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap');

        .font-cairo {
            font-family: 'Cairo', sans-serif !important;
        }

        [x-cloak] {
            display: none !important;
        }

        /* تحسين مظهر شريط التمرير الداخلي */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f8fafc;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>

    {{-- منطق Alpine --}}
    <script>
        function roomsBoard(cfg) {
            return {
                viewMode: 'shared_allocation',
                showFull: false,
                submitting: false,
                waitingMales: cfg.males || [],
                waitingFemales: cfg.females || [],
                assign: {
                    open: false,
                    roomUid: '',
                    roomLabel: '',
                    gender: '',
                    seatId: '',
                    search: ''
                },

                openAssign(roomUid, roomLabel, gender) {
                    this.assign = {
                        open: true,
                        roomUid: roomUid,
                        roomLabel: roomLabel,
                        gender: gender || '',
                        seatId: '',
                        search: ''
                    };
                    this.$nextTick(() => {
                        if (this.$refs.assignSearch) this.$refs.assignSearch.focus();
                    });
                },

                closeAssign() {
                    this.assign.open = false;
                },

                matches(p) {
                    const q = (this.assign.search || '').trim().toLowerCase();
                    if (!q) return true;
                    return (p.name || '').toLowerCase().includes(q) || String(p.booking_id).includes(q);
                },

                get filteredMales() {
                    if (this.assign.gender === 'female') return [];
                    return this.waitingMales.filter(p => this.matches(p));
                },

                get filteredFemales() {
                    if (this.assign.gender === 'male') return [];
                    return this.waitingFemales.filter(p => this.matches(p));
                },

                pick(id) {
                    if (this.submitting) return;
                    this.submitting = true;
                    this.assign.seatId = id;
                    this.$nextTick(() => this.$refs.assignForm.submit());
                }
            };
        }
    </script>

    @php
        /* ============================================================
         | 1) بيانات الرحلة
         ============================================================ */
        $activeTrip = $trips->firstWhere('id', $selectedTripId);

        $rc1 = $rc2 = $rc3 = $rc4 = 0;
        if ($activeTrip) {
            $roomOptions = is_array($activeTrip->room_options)
                ? $activeTrip->room_options
                : (json_decode($activeTrip->room_options ?? '[]', true) ?:
                []);

            $rc1 = (int) ($roomOptions['single']['count'] ?? 0);
            $rc2 = (int) ($roomOptions['double']['count'] ?? 0);
            $rc3 = (int) ($roomOptions['triple']['count'] ?? 0);
            $rc4 = (int) ($roomOptions['quad']['count'] ?? 0);
        }

        $activeRoomsCount = ($rc1 > 0 ? 1 : 0) + ($rc2 > 0 ? 1 : 0) + ($rc3 > 0 ? 1 : 0) + ($rc4 > 0 ? 1 : 0);
        $gridCols = max($activeRoomsCount, 1);

        $mdGrid = match ($gridCols) {
            1 => 'md:grid-cols-1',
            2 => 'md:grid-cols-2',
            3 => 'md:grid-cols-3',
            default => 'md:grid-cols-4',
        };
        $mdSpan = match ($gridCols) {
            1 => 'md:col-span-1',
            2 => 'md:col-span-2',
            3 => 'md:col-span-3',
            default => 'md:col-span-4',
        };

        $agency = auth('employee')->check() ? auth('employee')->user()->agency : auth('agency')->user();

        $allpeopleintrip = collect($allpeopleintrip ?? []);
        $roomsList = collect($roomsList ?? []);

        /* ============================================================
         | 2) دوال مساعدة (تُعرَّف مرة واحدة وتُستخدم في كل الأقسام)
         ============================================================ */
        $isLockedSeat = fn($seat) => $seat->booking &&
            ($seat->booking->status === 'locked' || $seat->booking->is_locked == 1);

        // 👈 تصحيح دالة فحص المقعد المشترك لدعم النصوص العربية والإنجليزية
        $isSharedSeat = function ($seat) {
            if (!$seat) {
                return false;
            }
            $rt = strtolower(trim((string) ($seat->room_type ?? '')));
            return $rt === 'shared' ||
                str_contains($rt, 'مشترك') ||
                str_contains($rt, 'مشاركة') ||
                !empty($seat->shared_room_type);
        };

        $cleanType = fn($type) => explode('_', (string) $type)[0];
        $seatName = fn($seat) => $seat->passenger_name ?: ($seat->booking?->customer_name ?: 'مسافر بدون اسم');
        $isExtraPassenger = fn($s) => (bool) ($s->is_extra ?? false)
            || filled($s->parent_seat_id)
            || (($s->room_type ?? '') === 'additional');
        $bedTypeLabel = function ($type) {
            return match ($type) {
                'single', 'مفرد', 'سرير مفرد' => 'سرير مفرد',
                'double', 'مزدوج', 'سرير مزدوج' => 'سرير مزدوج',
                default => $type ?: '—',
            };
        };

        $typeLabel = function ($type) {
            $t = mb_strtolower((string) $type);

            // فحص الغرف المشتركة وتحديد نوعها (ثنائية، ثلاثية، إلخ)
            if (
                str_contains($t, 'مشترك') ||
                str_contains($t, 'مشاركة') ||
                str_contains($t, 'shared') ||
                str_contains($t, 'public')
            ) {
                if (str_contains($t, 'single') || str_contains($t, 'فردية')) {
                    return 'غرفة مشتركة (فردية)';
                }
                if (str_contains($t, 'double') || str_contains($t, 'ثنائية')) {
                    return 'غرفة مشتركة (ثنائية)';
                }
                if (str_contains($t, 'triple') || str_contains($t, 'ثلاثية')) {
                    return 'غرفة مشتركة (ثلاثية)';
                }
                if (str_contains($t, 'quad') || str_contains($t, 'رباعية')) {
                    return 'غرفة مشتركة (رباعية)';
                }
                return 'غرفة مشتركة (عام)';
            }

            // الغرف الخاصة
            return match ($type) {
                'single' => 'غرفة مفردة (Single)',
                'double' => 'غرفة ثنائية (Double)',
                'triple' => 'غرفة ثلاثية (Triple)',
                'quad' => 'غرفة رباعية (Quad)',
                default => 'غرفة خاصة',
            };
        };

        $roomsIndexById = $roomsList->keyBy('id');
$roomsIndexByUid = $roomsList->keyBy('room_uid');

$resolveRoom = function ($seat) use ($roomsIndexById, $roomsIndexByUid) {
    if (!$seat || !$seat->trip_room_id) return null;
    return $roomsIndexById->get($seat->trip_room_id)
        ?? $roomsIndexByUid->get($seat->trip_room_id)
        ?? $seat->room;
};

// دالة تحديد سعة الغرفة الاستيعابية (ثنائية: 2، ثلاثية: 3، رباعية: 4، فردية: 1)
$getRoomCapacity = function ($room, $seat = null) {
    if ($room && isset($room->capacity) && (int) $room->capacity > 0) {
        return (int) $room->capacity;
    }
    $rt = mb_strtolower((string) ($room?->room_type ?? $seat?->room_type ?? $seat?->shared_room_type ?? ''));
    if (str_contains($rt, 'ثنائية') || str_contains($rt, 'double')) return 2;
    if (str_contains($rt, 'ثلاثية') || str_contains($rt, 'triple')) return 3;
    if (str_contains($rt, 'رباعية') || str_contains($rt, 'quad')) return 4;
    return 1;
};

        /* ============================================================
         | 3) قوائم الانتظار (الغرف المشتركة فقط)
         ============================================================ */
        $waitingBase = $allpeopleintrip
            ->filter(
                fn($s) => $isSharedSeat($s) &&
                    is_null($s->trip_room_id) &&
                    !$isLockedSeat($s) &&
                    !$isExtraPassenger($s) &&
                    !str_contains($s->passenger_name ?? '', 'مغلق'),
            )
            ->unique('id');

        $sharedTypeNames = [
            'single' => 'فردية',
            'double' => 'ثنائية',
            'triple' => 'ثلاثية',
            'quad' => 'رباعية',
        ];

        $getSharedLabel = function ($s) use ($sharedTypeNames) {
            $st = $s->shared_room_type ?? null;
            if (!$st) {
                $rt = $s->room_type ?? '';
                if (str_contains($rt, 'فردية') || str_contains($rt, 'single')) {
                    $st = 'single';
                } elseif (str_contains($rt, 'ثنائية') || str_contains($rt, 'double')) {
                    $st = 'double';
                } elseif (str_contains($rt, 'ثلاثية') || str_contains($rt, 'triple')) {
                    $st = 'triple';
                } elseif (str_contains($rt, 'رباعية') || str_contains($rt, 'quad')) {
                    $st = 'quad';
                }
            }
            return $st && isset($sharedTypeNames[$st]) ? 'عام (مشترك - ' . $sharedTypeNames[$st] . ')' : 'عام (مشترك)';
        };

        $mapWaiting = fn($s) => [
            'id' => $s->id,
            'name' => $seatName($s),
            'booking_id' => $s->booking_id,
            'sub_label' => $getSharedLabel($s),
        ];

        $waitingMalesArr = $waitingBase->where('gender', 'male')->map($mapWaiting)->values();
        $waitingFemalesArr = $waitingBase->where('gender', 'female')->map($mapWaiting)->values();
        $waitingUnknown = $waitingBase->whereNotIn('gender', ['male', 'female'])->count();

        $boardConfig = [
            'males' => $waitingMalesArr,
            'females' => $waitingFemalesArr,
        ];

        $availableRoomsCount = $roomsList->filter(fn($r) => $r->seats->count() < $r->capacity)->count();

        /* ============================================================
         | 4) تسكين العملاء: دمج حسب رقم الحجز
         ============================================================ */
       $customerGroups = $allpeopleintrip
    ->filter(fn($s) => $s->room_type !== 'blocked' && !$isLockedSeat($s) && !$isExtraPassenger($s))
    ->filter(fn($s) => !$isSharedSeat($s) || !is_null($s->trip_room_id))
    ->groupBy(fn($s) => $isSharedSeat($s) ? 'room-' . $s->trip_room_id : 'booking-' . $s->booking_id)
    ->map(function ($seats) use ($isSharedSeat, $cleanType, $resolveRoom, $seatName, $getRoomCapacity, $bedTypeLabel) {
        $first = $seats->first();
        $shared = $isSharedSeat($first);

        $rooms = $seats
            ->map(fn($s) => $resolveRoom($s))
            ->filter()
            ->unique(fn($r) => $r->id ?? $r->room_uid)
            ->values();

        $primaryRoom = $rooms->first(fn($r) => filled($r->room_number)) ?? $rooms->first();
        $types = $seats->map(fn($s) => $cleanType($s->room_type));
        $bookingIds = $seats->pluck('booking_id')->unique()->values();

        $subType = null;
        if ($shared) {
            $sources = [$first->shared_room_type ?? '', $first->room_type ?? '', $primaryRoom->room_type ?? ''];
            foreach ($sources as $src) {
                $s = mb_strtolower((string) $src);
                if (str_contains($s, 'فردية') || str_contains($s, 'single')) { $subType = 'single'; break; }
                if (str_contains($s, 'ثنائية') || str_contains($s, 'double')) { $subType = 'double'; break; }
                if (str_contains($s, 'ثلاثية') || str_contains($s, 'triple')) { $subType = 'triple'; break; }
                if (str_contains($s, 'رباعية') || str_contains($s, 'quad')) { $subType = 'quad'; break; }
            }
        }

        $type = $shared
            ? ($subType ? 'shared_' . $subType : 'shared')
            : $types->countBy()->sortDesc()->keys()->first() ?? '';

        $isExtra = fn($s) => !$shared && $cleanType($s->room_type) !== $type;

        $rows = $seats
            ->reject($isExtra)
            ->map(fn($s) => (object) ['main' => $s, 'extras' => collect()])
            ->values();

        $extraSeats = $seats->filter($isExtra)->values();

        foreach ($extraSeats as $x) {
            $row = $rows->first(fn($r) => $seatName($r->main) === $seatName($x)) ??
                   ($rows->first(fn($r) => $r->extras->isEmpty()) ?? $rows->first());

            if ($row) {
                $row->extras->push($x);
            } else {
                $rows->push((object) ['main' => $x, 'extras' => collect()]);
            }
        }

        $roomCapacity = $getRoomCapacity($primaryRoom, $first);
        $isFull = $seats->count() >= $roomCapacity;
        $roomUidOrId = $primaryRoom->id ?? $primaryRoom->room_uid ?? $seats->pluck('trip_room_id')->filter()->first();
        $effectiveGender = $primaryRoom->gender_type ?? $seats->pluck('gender')->filter()->first() ?? '';

        return (object) [
            'seats' => $seats->values(),
            'rows' => $rows,
            'guestCount' => $rows->count(),
            'extraCount' => 0,
            'bedTypes' => $seats->pluck('bed_type')->filter()->unique()->values()->map($bedTypeLabel),
            'extraBeds' => (int) $seats->sum(fn($s) => (int) ($s->extra_bed_quantity ?? 0)),
            'isShared' => $shared,
            'bookingIds' => $bookingIds,
            'bookingLabel' => $shared ? 'غرفة عامة' : '#' . $bookingIds->first(),
            'rooms' => $rooms,
            'roomIds' => $rooms->pluck('id')->filter()->values()->all(),
            'primaryRoom' => $primaryRoom,
            'roomNumber' => $primaryRoom->room_number ?? null,
            'notes' => $rooms->pluck('notes')->filter()->first(),
            'type' => $type,
            'hasMixedTypes' => $extraSeats->isNotEmpty(),
            'assigned' => $seats->contains(fn($s) => !is_null($s->trip_room_id)),
            'capacity' => $roomCapacity,
            'isFull' => $isFull,
            'roomUidOrId' => $roomUidOrId,
            'gender' => $effectiveGender,
        ];
    });

        /* ============================================================
         | 5) بيانات قالب الطباعة
         ============================================================ */
        $printGroups = $customerGroups->filter(fn($g) => $g->assigned)->values();
        $printSeats = $printGroups->flatMap(fn($g) => $g->seats);
        $totalGuests = $printSeats->count();
        $printMales = $printSeats->where('gender', 'male')->count();
        $printFemales = $printSeats->where('gender', 'female')->count();
        $totalRoomsCount = $printGroups->count();
        $totalBookingsCount = $printSeats->pluck('booking_id')->unique()->count();
        $totalExtraBeds = $printGroups->sum(fn($g) => (int) ($g->extraBeds ?? 0));
        $singleBeds = $printSeats->filter(fn($s) => in_array($s->bed_type, ['single', 'مفرد', 'سرير مفرد'], true))->count();
        $doubleBeds = $printSeats->filter(fn($s) => in_array($s->bed_type, ['double', 'مزدوج', 'سرير مزدوج'], true))->count();
        $unassignedGuestCount = $customerGroups->filter(fn($g) => !$g->assigned)->sum('guestCount');
    @endphp

    <div class="container mx-auto px-4 py-8 font-cairo" dir="rtl">

        {{-- رسائل التنبيه والنجاح --}}
        @if (session('success'))
            <div
                class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-2xl mb-6 text-xs font-bold text-right">
                ✓ {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-2xl mb-6 text-xs font-bold text-right">
                ⚠️ {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-2xl mb-6 text-xs font-bold text-right">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>⚠️ {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- هيدر الصفحة والتحكم --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8 text-right">
            <div>
                <h1 class="text-xl font-black text-slate-900 flex items-center gap-2">
                    <span class="p-2 bg-indigo-50 text-indigo-600 rounded-2xl text-lg">🏢</span>
                    <span>إدارة تسكين وتوزيع غرف الرحلات</span>
                </h1>
                <p class="text-xs text-slate-500 mt-1 font-medium">
                    الرحلة: <span
                        class="text-slate-800 font-bold">{{ $activeTrip ? $activeTrip->title : 'لم يتم اختيار رحلة بعد' }}</span>
                    @if ($activeTrip)
                        | الرمز: <span
                            class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-indigo-600">#{{ $activeTrip->id }}</span>
                    @endif
                </p>
            </div>

            @if ($activeTrip)
                <div class="flex flex-wrap gap-2.5 items-center justify-start lg:justify-end">
                    <form action="{{ route('agency.bookings.auto_distribute', $activeTrip->id) }}" method="POST"
                        onsubmit="return confirm('هل تريد ربط وتوزيع الحجوزات تلقائيًا وتسكين أصحاب الحجز الواحد في غرف متجاورة؟')">
                        @csrf
                        <button type="submit"
                            class="bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all flex items-center gap-1.5 shadow-sm cursor-pointer">
                            <span>🤖</span> توزيع تلقائي
                        </button>
                    </form>

                    <a href="{{ route('agency.reports.meals', ['trip_id' => $activeTrip->id]) }}"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all flex items-center gap-1.5 shadow-sm">
                        <span>🍽</span> تقرير الوجبات
                    </a>
                    <button type="button" onclick="printCustomTable()"
                        class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition-all flex items-center gap-1.5 shadow-sm cursor-pointer">
                        <span>📄</span> تصدير كشف الغرف (PDF)
                    </button>
                </div>
            @endif
        </div>

        {{-- قسم اختيار وتصفية الرحلة --}}
        <div class="bg-white border border-slate-200/60 shadow-sm rounded-2xl p-5 mb-6 text-right">
            <form action="{{ route('agency.bookings.rooms') }}" method="GET" id="filterForm" class="w-full">
                <div class="w-full relative" id="custom-dropdown-container">
                    <label for="trip_search_input" class="block text-[11px] font-black text-slate-500 mb-1.5 mr-1">
                        اختر أو اكتب اسم الرحلة لعرض وتوزيع الغرف:
                    </label>

                    <input type="hidden" name="trip_id" id="real_trip_id" value="{{ $selectedTripId }}">

                    <div class="relative">
                        @php
                            $currentTrip = $selectedTripId ? $trips->firstWhere('id', $selectedTripId) : null;
                            $currentValue = $currentTrip
                                ? $currentTrip->title .
                                    ' (' .
                                    ($currentTrip->departure_at
                                        ? \Carbon\Carbon::parse($currentTrip->departure_at)->format('Y-m-d')
                                        : '-') .
                                    ')'
                                : '';
                        @endphp

                        <input type="text" id="trip_search_input" autocomplete="off"
                            placeholder="🔍 اكتب اسم الرحلة أو تاريخها للاختيار الفوري..." value="{{ $currentValue }}"
                            class="w-full bg-slate-50 text-slate-800 pr-4 pl-10 py-3 rounded-xl border border-slate-200 text-xs font-bold focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all placeholder:text-slate-400" />

                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4 transition-transform duration-200" id="dropdown-chevron" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                                </path>
                            </svg>
                        </div>
                    </div>

                    <div id="dropdown-list-panel"
                        class="hidden absolute z-50 left-0 right-0 mt-2 max-h-60 overflow-y-auto bg-white border border-slate-200 shadow-xl rounded-xl transition-all custom-scrollbar">
                        <div class="p-1">
                            @foreach ($trips as $t)
                                @php
                                    $tripDate = $t->departure_at
                                        ? \Carbon\Carbon::parse($t->departure_at)->format('Y-m-d')
                                        : '-';
                                    $searchKey = mb_strtolower($t->title . ' ' . $tripDate);
                                @endphp

                                <div data-value="{{ $t->id }}" data-search="{{ $searchKey }}"
                                    data-label="{{ $t->title }} ({{ $tripDate }})"
                                    class="dropdown-item px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg cursor-pointer transition-all {{ $selectedTripId == $t->id ? 'bg-indigo-50 text-indigo-600' : '' }}">
                                    <div class="flex justify-between items-center pointer-events-none">
                                        <span class="trip-title-text">{{ $t->title }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono font-medium">📅
                                            {{ $tripDate }}</span>
                                    </div>
                                </div>
                            @endforeach

                            <div id="no-results-msg"
                                class="hidden px-4 py-3 text-xs text-slate-400 text-center italic font-medium">
                                لا توجد رحلات مطابقة لبحثك الحالي...
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        @if (!$activeTrip)
            <div class="bg-amber-50 border border-amber-100 rounded-3xl p-8 text-center my-12">
                <span class="text-3xl block mb-2">💡</span>
                <h3 class="text-sm font-black text-amber-900">يرجى اختيار رحلة من القائمة المنسدلة أعلاه</h3>
                <p class="text-xs text-amber-600 mt-1 font-medium">اختر الرحلة ليتم توليد الغرف الافتراضية وعرض قوائم الركاب
                    المتاحين للتسكين.</p>
            </div>
        @else
            {{-- إحصائيات الغرف الكلية --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8 text-right">
                <div class="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-[11px] text-slate-400 font-bold block mb-1">إجمالي الغرف بالرحلة</span>
                        <span class="text-xl font-black text-slate-800 font-mono">{{ $totalRooms }}</span>
                    </div>
                    <span
                        class="w-11 h-11 bg-slate-50 text-slate-500 flex items-center justify-center rounded-2xl text-lg">🔑</span>
                </div>
                <div class="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-[11px] text-slate-400 font-bold block mb-1">إجمالي الأسرة المتاحة</span>
                        <span class="text-xl font-black text-slate-800 font-mono">{{ $totalBeds }}</span>
                    </div>
                    <span
                        class="w-11 h-11 bg-indigo-50 text-indigo-500 flex items-center justify-center rounded-2xl text-lg">🛏️</span>
                </div>
                <div class="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-[11px] text-slate-400 font-bold block mb-1">الأسرة المشغولة</span>
                        <span class="text-xl font-black text-amber-600 font-mono">{{ $occupiedBeds }}</span>
                    </div>
                    <span
                        class="w-11 h-11 bg-amber-50 text-amber-500 flex items-center justify-center rounded-2xl text-lg">👨‍👦</span>
                </div>
                <div class="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-[11px] text-slate-400 font-bold block mb-1">الأسرة الفارغة المتبقية</span>
                        <span class="text-xl font-black text-emerald-600 font-mono">{{ $availableBeds }}</span>
                    </div>
                    <span
                        class="w-11 h-11 bg-emerald-50 text-emerald-500 flex items-center justify-center rounded-2xl text-lg">✨</span>
                </div>
            </div>

            {{-- تحديث مخزون الغرف --}}
            @if ($activeRoomsCount > 0)
                <div class="bg-white border border-slate-100 shadow-sm rounded-3xl p-6 mb-8 text-right">
                    <h3 class="text-xs font-black text-slate-800 mb-4 flex items-center gap-1.5">
                        <span>⚙️</span> تحديث مخزون الغرف المتاحة لهذه الرحلة
                    </h3>

                    <form action="{{ route('agency.bookings.update_rooms', $activeTrip->id) }}" method="POST"
                        onsubmit="return confirm('سيتم تحديث مخزون الغرف. قد تؤثر إنقاص العدد على الغرف الحالية، هل تريد المتابعة؟')"
                        class="grid grid-cols-2 {{ $mdGrid }} gap-4">
                        @csrf

                        @if ($rc1 > 0)
                            <div>
                                <label class="text-[11px] text-slate-500 mb-1.5 block font-bold">غرف مفردة (Single):</label>
                                <input type="number" name="rooms_count_1" value="{{ $rc1 }}" min="0"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono outline-none focus:border-indigo-500">
                            </div>
                        @endif

                        @if ($rc2 > 0)
                            <div>
                                <label class="text-[11px] text-slate-500 mb-1.5 block font-bold">غرف ثنائية
                                    (Double):</label>
                                <input type="number" name="rooms_count_2" value="{{ $rc2 }}" min="0"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono outline-none focus:border-indigo-500">
                            </div>
                        @endif

                        @if ($rc3 > 0)
                            <div>
                                <label class="text-[11px] text-slate-500 mb-1.5 block font-bold">غرف ثلاثية
                                    (Triple):</label>
                                <input type="number" name="rooms_count_3" value="{{ $rc3 }}" min="0"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono outline-none focus:border-indigo-500">
                            </div>
                        @endif

                        @if ($rc4 > 0)
                            <div>
                                <label class="text-[11px] text-slate-500 mb-1.5 block font-bold">غرف رباعية (Quad):</label>
                                <input type="number" name="rooms_count_4" value="{{ $rc4 }}" min="0"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono outline-none focus:border-indigo-500">
                            </div>
                        @endif

                        <div class="col-span-2 {{ $mdSpan }} flex justify-end mt-2">
                            <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition-all cursor-pointer">
                                تحديث وإرسال المخزون ✓
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div
                    class="bg-amber-50/50 border border-amber-100 rounded-3xl p-5 mb-8 text-center text-xs text-amber-700 font-bold flex flex-col sm:flex-row items-center justify-center gap-3">
                    <span>ℹ️ لا توجد غرف مخصصة أو متاحة لتحديث مخزونها في هذه الرحلة حالياً.</span>
                    <a href="{{ route('agency.trips.edit', $activeTrip->id) }}"
                        class="p-2.5 bg-sky-50 text-sky-600 hover:bg-sky-100 rounded-xl transition-all active:scale-95 text-xs font-black inline-flex items-center gap-1">
                        ✏️ تعديل بيانات الرحلة
                    </a>
                </div>
            @endif

            <div x-data="roomsBoard(@js($boardConfig))" class="w-full">

                {{-- أزرار التحكم في العرض الرئيسي --}}
                <div class="flex flex-wrap gap-3 mb-6 justify-start text-right">
                    <button type="button" @click="viewMode = 'shared_allocation'"
                        :class="viewMode === 'shared_allocation' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' :
                            'bg-slate-100 text-slate-600 hover:bg-slate-200/80'"
                        class="px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2 outline-none cursor-pointer">
                        <span>📊</span> غرف العام (المتاحة للتسكين)
                        <span class="font-mono text-[10px] opacity-80">({{ $availableRoomsCount }})</span>
                    </button>

                    <button type="button" @click="viewMode = 'customer_booked'"
                        :class="viewMode === 'customer_booked' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' :
                            'bg-slate-100 text-slate-600 hover:bg-slate-200/80'"
                        class="px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2 outline-none cursor-pointer">
                        <span>🛎️</span> الغرف المحجوزة
                        <span class="font-mono text-[10px] opacity-80">({{ $customerGroups->count() }})</span>
                    </button>
                </div>

                {{-- ===================== القسم الأول: غرف العام ===================== --}}
                <div x-show="viewMode === 'shared_allocation'"
                    class="grid grid-cols-1 xl:grid-cols-4 gap-8 items-start text-right">

                    {{-- قوائم انتظار العام --}}
                    <div class="xl:col-span-1 space-y-6">
                        <h2 class="text-sm font-black text-slate-800 flex items-center gap-1.5 mb-2">
                            <span>📋</span> قوائم انتظار العام (المشتركة)
                        </h2>

                        @if ($waitingUnknown > 0)
                            <div
                                class="bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-3 text-[11px] font-bold">
                                ⚠️ يوجد {{ $waitingUnknown }} مقعد مشترك بالانتظار بدون جنس محدد، لذلك لا يظهر في القوائم
                                أدناه.
                            </div>
                        @endif

                        <div class="bg-white border border-slate-100 shadow-sm rounded-3xl p-4">
                            <div class="flex items-center justify-between border-b border-slate-50 pb-3 mb-3">
                                <span class="text-xs font-black text-blue-700 flex items-center gap-1">👨 مقاعد
                                    (ذكور)</span>
                                <span
                                    class="bg-blue-50 text-blue-700 font-mono text-[10px] px-2 py-0.5 rounded-lg font-bold"
                                    x-text="waitingMales.length"></span>
                            </div>
                            <div class="space-y-2 max-h-[250px] overflow-y-auto custom-scrollbar pl-1">
                                <template x-for="seat in waitingMales" :key="seat.id">
                                    <div
                                        class="p-2.5 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-between text-right hover:bg-indigo-50/50 transition-all">
                                        <div>
                                            <p class="text-xs font-bold text-slate-800" x-text="seat.name"></p>
                                            <p class="text-[10px] text-slate-400 font-mono mt-0.5">
                                                حجز #<span x-text="seat.booking_id"></span> |
                                                <span class="text-indigo-600 font-black text-[9px]"
                                                    x-text="seat.sub_label || 'عام (مشترك)'"></span>
                                            </p>
                                        </div>
                                        <span
                                            class="text-[9px] font-bold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-md cursor-default">بالانتظار</span>
                                    </div>
                                </template>
                                <div x-show="waitingMales.length === 0"
                                    class="text-[11px] text-slate-400 text-center py-4">
                                    لا يوجد مقاعد رجال بالانتظار
                                </div>
                            </div>
                        </div>

                        <div class="bg-white border border-slate-100 shadow-sm rounded-3xl p-4">
                            <div class="flex items-center justify-between border-b border-slate-50 pb-3 mb-3">
                                <span class="text-xs font-black text-rose-700 flex items-center gap-1">👩 مقاعد
                                    (إناث)</span>
                                <span
                                    class="bg-rose-50 text-rose-700 font-mono text-[10px] px-2 py-0.5 rounded-lg font-bold"
                                    x-text="waitingFemales.length"></span>
                            </div>
                            <div class="space-y-2 max-h-[250px] overflow-y-auto custom-scrollbar pl-1">
                                <template x-for="seat in waitingFemales" :key="seat.id">
                                    <div
                                        class="p-2.5 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-between text-right hover:bg-indigo-50/50 transition-all">
                                        <div>
                                            <p class="text-xs font-bold text-slate-800" x-text="seat.name"></p>
                                            <p class="text-[10px] text-slate-400 font-mono mt-0.5">
                                                حجز #<span x-text="seat.booking_id"></span> |
                                                <span class="text-indigo-600 font-black text-[9px]"
                                                    x-text="seat.sub_label || 'عام (مشترك)'"></span>
                                            </p>
                                        </div>
                                        <span
                                            class="text-[9px] font-bold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-md cursor-default">بالانتظار</span>
                                    </div>
                                </template>
                                <div x-show="waitingFemales.length === 0"
                                    class="text-[11px] text-slate-400 text-center py-4">
                                    لا يوجد مقاعد نساء بالانتظار
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- شبكة غرف العام --}}
                    <div class="xl:col-span-3">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5 pb-2 border-b border-slate-100">
                            <h2 class="text-sm font-black text-slate-800 flex items-center gap-1.5">
                                <span>🔓</span> غرف العام
                                <span class="text-[10px] font-mono font-bold text-slate-400">
                                    (متاحة {{ $availableRoomsCount }} من {{ $roomsList->count() }})
                                </span>
                            </h2>

                            <label class="flex items-center gap-2 text-[11px] font-bold text-slate-600 cursor-pointer">
                                <input type="checkbox" x-model="showFull"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                إظهار الغرف الممتلئة (لإلغاء التسكين)
                            </label>
                        </div>

                        @if ($roomsList->isEmpty())
                            <div
                                class="bg-slate-50 border border-dashed border-slate-200 rounded-3xl p-8 text-center text-xs font-bold text-slate-400">
                                لا توجد غرف عامة لهذه الرحلة.
                            </div>
                        @else
                            @if ($availableRoomsCount === 0)
                                <div x-show="!showFull"
                                    class="bg-emerald-50 border border-emerald-100 rounded-3xl p-6 mb-4 text-center text-xs font-bold text-emerald-700">
                                    ✓ جميع الغرف العامة ممتلئة. فعّل «إظهار الغرف الممتلئة» لعرضها.
                                </div>
                            @endif

                            <div
                                class="max-h-[620px] overflow-y-auto custom-scrollbar p-2 bg-slate-50/30 rounded-3xl border border-slate-100/80">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach ($roomsList as $room)
                                        @php
                                            $roomTypeLabel = match ($room->room_type) {
                                                'single' => 'مفردة (Single)',
                                                'double' => 'ثنائية (Double)',
                                                'triple' => 'ثلاثية (Triple)',
                                                'quad' => 'رباعية (Quad)',
                                                default => $room->room_type,
                                            };

                                            $seatsList = $room->seats->values();
                                            $isRoomFull = $seatsList->count() >= $room->capacity;

                                            $seatGenders = $seatsList->pluck('gender')->filter()->unique();
                                            $effectiveGender =
                                                $room->gender_type ?:
                                                ($seatGenders->count() === 1
                                                    ? $seatGenders->first()
                                                    : ($seatGenders->count() > 1
                                                        ? 'mixed'
                                                        : ''));
                                            if (!in_array($effectiveGender, ['male', 'female', 'mixed'], true)) {
                                                $effectiveGender = '';
                                            }

                                            $roomPickLabel =
                                                $roomTypeLabel .
                                                ($room->room_number ? ' - رقم ' . $room->room_number : '');
                                        @endphp

                                        <div x-show="showFull || {{ $isRoomFull ? 'false' : 'true' }}"
                                            class="bg-white border border-slate-100 shadow-sm rounded-3xl p-4 flex flex-col justify-between transition-all hover:shadow-md border-r-4 {{ $isRoomFull ? 'border-r-slate-400' : ($seatsList->count() > 0 ? 'border-r-amber-500' : 'border-r-emerald-500') }}">
                                            <div>
                                                <div
                                                    class="flex justify-between items-start border-b border-slate-50 pb-2.5 mb-3">
                                                    <div>
                                                        <h4 class="text-xs font-black text-slate-900">غرفة
                                                            {{ $roomTypeLabel }}</h4>
                                                        @if ($room->room_number)
                                                            <span
                                                                class="text-[10px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded mt-1 inline-block">غرفة
                                                                رقم: {{ $room->room_number }}</span>
                                                        @endif
                                                    </div>

                                                    @if ($effectiveGender === 'male')
                                                        <span
                                                            class="text-[10px] font-bold bg-blue-50 text-blue-700 px-2 py-0.5 rounded-lg">👨
                                                            غُرفة رجال</span>
                                                    @elseif($effectiveGender === 'female')
                                                        <span
                                                            class="text-[10px] font-bold bg-rose-50 text-rose-700 px-2 py-0.5 rounded-lg">👩
                                                            غُرفة نساء</span>
                                                    @elseif($effectiveGender === 'mixed')
                                                        <span
                                                            class="text-[10px] font-bold bg-purple-50 text-purple-700 px-2 py-0.5 rounded-lg">👫
                                                            غُرفة عائلية</span>
                                                    @else
                                                        <span
                                                            class="text-[10px] font-bold bg-slate-50 text-slate-500 px-2 py-0.5 rounded-lg">🎲
                                                            غير محددة</span>
                                                    @endif
                                                </div>

                                                <div class="space-y-2 mb-4">
                                                    @for ($i = 0; $i < $room->capacity; $i++)
                                                        @php $allocatedSeat = $seatsList->get($i); @endphp

                                                        @if ($allocatedSeat)
                                                            <div
                                                                class="p-2 bg-indigo-50/50 border border-indigo-100/40 rounded-xl flex items-center justify-between text-right">
                                                                <div class="flex items-center gap-1.5">
                                                                    <span
                                                                        class="text-xs">{{ $allocatedSeat->gender === 'male' ? '👨' : '👩' }}</span>
                                                                    <div>
                                                                        <p class="text-xs font-bold text-indigo-900">
                                                                            {{ $seatName($allocatedSeat) }}</p>
                                                                        <p class="text-[9px] text-indigo-500 font-mono">حجز
                                                                            #{{ $allocatedSeat->booking_id }}</p>
                                                                    </div>
                                                                </div>
                                                                <form
                                                                    action="{{ route('agency.bookings.remove_passenger') }}"
                                                                    method="POST"
                                                                    onsubmit="return confirm('هل أنت متأكد من إلغاء تسكين هذا المسافر؟')">
                                                                    @csrf
                                                                    <input type="hidden" name="booking_seat_id"
                                                                        value="{{ $allocatedSeat->id }}">
                                                                    <button type="submit" title="إلغاء التسكين"
                                                                        class="text-slate-400 hover:text-red-500 transition-colors text-[11px] px-1 font-bold cursor-pointer">✕</button>
                                                                </form>
                                                            </div>
                                                        @else
                                                            <div
                                                                class="p-1 border border-dashed border-slate-200 rounded-xl bg-slate-50/30">
                                                                <button type="button" data-uid="{{ $room->room_uid }}"
                                                                    data-label="{{ $roomPickLabel }}"
                                                                    data-gender="{{ $effectiveGender }}"
                                                                    @click="openAssign($el.dataset.uid, $el.dataset.label, $el.dataset.gender)"
                                                                    class="w-full text-[10px] text-slate-500 hover:text-indigo-600 font-black p-2 flex items-center justify-center gap-1.5 bg-slate-50/60 hover:bg-indigo-50/40 rounded-xl transition-all border border-slate-100/70 outline-none cursor-pointer">
                                                                    <span>➕</span> تسكين مسافر بالسرير
                                                                </button>
                                                            </div>
                                                        @endif
                                                    @endfor
                                                </div>
                                            </div>

                                            <div
                                                class="pt-2 mt-2 border-t border-slate-50 flex items-center justify-between text-[10px] text-slate-400 font-semibold">
                                                <span>حالة الاستيعاب:</span>
                                                <span
                                                    class="font-mono font-bold {{ $isRoomFull ? 'text-emerald-600' : 'text-slate-700' }}">
                                                    {{ $seatsList->count() }} / {{ $room->capacity }} أسرة
                                                    @if ($isRoomFull)
                                                        ✓
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ===================== القسم الثاني: تسكين العملاء (مدموج حسب رقم الحجز) ===================== --}}
                <div x-show="viewMode === 'customer_booked'" x-cloak class="w-full text-right space-y-10">

                    <div>
                        <h2 class="text-sm font-black text-slate-800 flex items-center gap-2 mb-1">
                            <span class="text-lg">🏨</span>
                            <span>التسكين للعملاء</span>
                        </h2>
                        <p class="text-[11px] text-slate-400 font-medium mb-3">
                            كل حجز في بطاقة واحدة للأشخاص الفعليين فقط (بدون المقاعد الإضافية). الغرف المشتركة المسكّنة تظهر
                            كبطاقة لكل غرفة.
                        </p>

                        @if ($customerGroups->isNotEmpty())
                            <div
                                class="max-h-[620px] overflow-y-auto custom-scrollbar p-3 bg-slate-50/50 rounded-3xl border border-slate-100">
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                    @foreach ($customerGroups as $key => $g)
                                        <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between overflow-hidden border-r-4 {{ $g->isShared ? 'border-r-purple-500' : 'border-r-indigo-500' }}"
                                            data-card data-group="{{ $key }}">

                                            <div class="p-4 space-y-3.5">
                                                <div
                                                    class="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-100">
                                                    <span
                                                        class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                                        <span>🏢</span>
                                                        <span>{{ $typeLabel($g->type) }}</span>
                                                    </span>
                                                    <span
                                                        class="text-[10px] font-mono font-bold bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-md border border-indigo-100">
                                                        {{ $g->bookingLabel }}
                                                    </span>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-bold">
                                                    @if ($g->assigned)
                                                        <span
                                                            class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-md">✓
                                                            مسكّن</span>
                                                    @else
                                                        <span class="bg-amber-50 text-amber-700 px-2 py-0.5 rounded-md">⏳
                                                            بانتظار تعيين رقم الغرفة</span>
                                                    @endif
                                                    <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md">
                                                        {{ $g->guestCount }} نزلاء
                                                    </span>
                                                    @if ($g->bedTypes->isNotEmpty())
                                                        <span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-md">
                                                            {{ $g->bedTypes->implode(' / ') }}
                                                        </span>
                                                    @endif
                                                    @if ($g->extraBeds)
                                                        <span class="bg-amber-50 text-amber-700 px-2 py-0.5 rounded-md">
                                                            + {{ $g->extraBeds }} سرير إضافي
                                                        </span>
                                                    @endif
                                                </div>

                                                <div>
                                                    <span
                                                        class="text-[10px] font-bold text-slate-400 block mb-1.5">الركاب:</span>
                                                    <div class="space-y-1.5">
                                                        @foreach ($g->rows as $r)
                                                            @php $p = $r->main; @endphp
                                                            <div
                                                                class="p-2 bg-slate-50 hover:bg-slate-100/80 border border-slate-100 rounded-xl flex items-center justify-between gap-2 transition-colors">
                                                                <span
                                                                    class="text-xs font-bold text-slate-700 truncate flex items-center gap-1.5">
                                                                    <span>{{ $p->gender === 'male' ? '👨' : '👩' }}</span>
                                                                    {{ $seatName($p) }}
                                                                </span>
                                                                @if ($g->isShared)
                                                                    <span
                                                                        class="text-[9px] text-slate-400 font-mono bg-white px-1.5 py-0.5 rounded border border-slate-100">
                                                                        #{{ $p->booking_id }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                @if ($g->isShared && !$g->isFull && $g->roomUidOrId)
    @php
        $assignLabel = $typeLabel($g->type) . ($g->roomNumber ? ' - رقم ' . $g->roomNumber : '');
    @endphp
    <div class="pt-2 border-t border-slate-100 mt-2">
        <button type="button"
            data-uid="{{ $g->roomUidOrId }}"
            data-label="{{ $assignLabel }}"
            data-gender="{{ $g->gender }}"
            @click="openAssign($el.dataset.uid, $el.dataset.label, $el.dataset.gender)"
            class="w-full text-[11px] text-indigo-700 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 font-black p-2.5 rounded-xl flex items-center justify-center gap-1.5 transition-all border border-indigo-200/60 shadow-sm outline-none cursor-pointer">
            <span>➕</span> تسكين مسافر إضافي بالغرفة ({{ $g->guestCount }}/{{ $g->capacity }})
        </button>
    </div>
@endif
                                            </div>

                                            <div class="p-3 bg-slate-50/80 border-t border-slate-100 space-y-2">
                                                <div>
                                                    <label class="text-[10px] font-bold text-slate-500 block mb-1">رقم
                                                        الغرفة بالفندق:</label>
                                                    <div class="relative">
                                                        <input type="text" value="{{ $g->roomNumber }}"
                                                            data-group="{{ $key }}"
                                                            data-room-id="{{ $g->primaryRoom?->id }}"
                                                            data-room-ids="{{ json_encode($g->roomIds) }}"
                                                            data-booking-id="{{ $g->bookingIds->first() }}"
                                                            data-room-type="{{ $g->type }}"
                                                            placeholder="أدخل رقم الغرفة"
                                                            class="ajax-room-number-input w-full bg-white text-slate-800 px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-center outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition-all">
                                                        <span
                                                            class="save-status-indicator absolute inset-y-0 left-2.5 flex items-center text-[10px] font-bold text-emerald-600 hidden">
                                                            ✓ حُفظ
                                                        </span>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label
                                                        class="text-[10px] font-bold text-slate-500 block mb-1">الملاحظات:</label>
                                                    <div class="relative">
                                                        <input type="text" value="{{ $g->notes }}"
                                                            data-group="{{ $key }}"
                                                            data-room-id="{{ $g->primaryRoom?->id }}"
                                                            data-room-ids="{{ json_encode($g->roomIds) }}"
                                                            data-booking-id="{{ $g->bookingIds->first() }}"
                                                            data-room-type="{{ $g->type }}"
                                                            placeholder="أضف ملاحظة..."
                                                            class="ajax-room-notes-input w-full bg-white text-slate-700 px-3 py-1.5 rounded-xl border border-slate-200 text-xs outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition-all">
                                                        <span
                                                            class="save-notes-indicator absolute inset-y-0 left-2.5 flex items-center text-[10px] font-bold text-emerald-600 hidden">
                                                            ✓ حُفظ
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div
                                class="bg-slate-50 border border-dashed border-slate-200 rounded-3xl p-8 text-center space-y-2">
                                <span class="text-2xl block">🏨</span>
                                <p class="text-xs font-bold text-slate-400">لا توجد غرف مسكنة حالياً للرحلة.</p>
                            </div>
                        @endif
                    </div>

                    {{-- كشف تفاصيل حجوزات غرف العملاء --}}
                    <div id="private-rooms-table">
                        <h2 class="text-sm font-black text-slate-800 flex items-center gap-1.5 mb-3">
                            <span>📋</span> كشف تفاصيل حجوزات غرف العملاء للرحلة
                        </h2>

                        @if ($customerGroups->isNotEmpty())
                            <div class="bg-white border border-slate-100 shadow-sm rounded-3xl p-6">
                                <div class="overflow-x-auto max-h-[450px] overflow-y-auto custom-scrollbar">
                                    <table class="w-full text-right text-xs border-collapse">
                                        <thead class="sticky top-0 bg-slate-50 z-10">
                                            <tr class="text-slate-600 border-b border-slate-100 font-bold">
                                                <th class="p-3 rounded-r-xl">رقم الحجز</th>
                                                <th class="p-3">النزيل</th>
                                                <th class="p-3">الجنس</th>
                                                <th class="p-3">نوع الغرفة</th>
                                                <th class="p-3">نوع السرير</th>
                                                <th class="p-3">سرير إضافي</th>
                                                <th class="p-3">حالة التسكين</th>
                                                <th class="p-3 rounded-l-xl text-center">رقم الغرفة</th>
                                            </tr>
                                        </thead>

                                        @foreach ($customerGroups as $key => $g)
                                            <tbody
                                                class="border-b border-slate-100 text-slate-700 font-medium hover:bg-slate-50/60 transition-colors">
                                                @foreach ($g->rows as $r)
                                                    @php $p = $r->main; @endphp
                                                    <tr>
                                                        @if ($loop->first)
                                                            <td rowspan="{{ $g->rows->count() }}"
                                                                class="p-3 font-bold text-slate-800 align-top">
                                                                <span class="block font-mono text-[11px] text-slate-500">
                                                                    @if ($g->isShared)
                                                                        غرفة عامة
                                                                        <span
                                                                            class="text-[10px] text-slate-400">({{ $g->bookingIds->map(fn($b) => '#' . $b)->implode('، ') }})</span>
                                                                    @else
                                                                        #BK-{{ $g->bookingIds->first() }}
                                                                    @endif
                                                                </span>
                                                                <span data-live-notes="{{ $key }}"
                                                                    class="{{ $g->notes ? '' : 'hidden' }} mt-2 inline-block bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded-md text-[10px] font-medium max-w-[200px] truncate">{{ $g->notes ? '📝 ' . $g->notes : '' }}</span>
                                                            </td>
                                                        @endif

                                                        <td class="p-3">
                                                            <span class="flex items-center gap-1.5">
                                                                <span
                                                                    class="w-1.5 h-1.5 rounded-full bg-indigo-300"></span>
                                                                {{ $seatName($p) }}
                                                            </span>
                                                        </td>

                                                        <td class="p-3">
                                                            <span
                                                                class="bg-slate-50 text-slate-700 text-[10px] px-2 py-0.5 rounded-md font-bold inline-block">
                                                                {{ $p->gender === 'male' ? 'ذكر' : 'أنثى' }}
                                                            </span>
                                                        </td>

                                                        @if ($loop->first)
                                                            <td rowspan="{{ $g->rows->count() }}"
                                                                class="p-3 font-bold text-indigo-600 align-top">
                                                                {{ $typeLabel($g->type) }}
                                                            </td>
                                                            <td rowspan="{{ $g->rows->count() }}" class="p-3 align-top text-[11px]">
                                                                {{ $g->bedTypes->implode(' / ') ?: '—' }}
                                                            </td>
                                                            <td rowspan="{{ $g->rows->count() }}" class="p-3 align-top font-mono">
                                                                {{ $g->extraBeds ?: '—' }}
                                                            </td>

                                                            <td rowspan="{{ $g->rows->count() }}" class="p-3 align-top">
                                                                @if ($g->assigned)
                                                                    <span data-live-status="{{ $key }}"
                                                                        class="bg-emerald-50 text-emerald-700 text-[10px] px-2 py-1 rounded-md font-bold inline-flex items-center gap-1">
                                                                        ✓ مسكّن
                                                                        {{ $g->roomNumber ? 'بالغرفة رقم #' . $g->roomNumber : '(بدون رقم)' }}
                                                                    </span>
                                                                @else
                                                                    <span data-live-status="{{ $key }}"
                                                                        class="bg-amber-50 text-amber-700 text-[10px] px-2 py-1 rounded-md font-bold inline-flex items-center gap-1">
                                                                        ⏳ بالانتظار (لم يُسكّن بعد)
                                                                    </span>
                                                                @endif
                                                            </td>

                                                            <td rowspan="{{ $g->rows->count() }}"
                                                                data-live-number="{{ $key }}"
                                                                class="p-3 text-center font-mono font-bold align-top text-lg">
                                                                {{ $g->roomNumber ?: '-' }}</td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="bg-white border border-slate-100 shadow-sm rounded-3xl p-6 text-center py-12">
                                <span class="text-3xl block mb-2">🛎️</span>
                                <h3 class="text-xs font-black text-slate-400">لا توجد حجوزات غرف حالياً للرحلة.</h3>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ===================== نافذة اختيار المسافر للتسكين ===================== --}}
                <div x-show="assign.open" x-cloak x-transition.opacity @keydown.escape.window="closeAssign()"
                    @click.self="closeAssign()"
                    class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/50">
                    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl p-4 text-right font-cairo" dir="rtl">

                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div>
                                <h3 class="text-sm font-black text-slate-900">تسكين مسافر</h3>
                                <p class="text-[11px] text-slate-500 font-bold mt-0.5" x-text="assign.roomLabel"></p>
                                <p x-show="assign.gender === 'male'" class="text-[10px] text-blue-600 font-bold mt-0.5">
                                    غرفة رجال: تظهر قائمة الذكور فقط
                                </p>
                                <p x-show="assign.gender === 'female'" class="text-[10px] text-rose-600 font-bold mt-0.5">
                                    غرفة نساء: تظهر قائمة الإناث فقط
                                </p>
                            </div>
                            <button type="button" @click="closeAssign()"
                                class="text-slate-400 hover:text-slate-700 text-sm font-bold px-2 cursor-pointer">✕</button>
                        </div>

                        <form x-ref="assignForm" action="{{ route('agency.bookings.assign_passenger') }}" method="POST"
                            class="hidden">
                            @csrf
                            <input type="hidden" name="trip_id" value="{{ $activeTrip->id }}">
                            <input type="hidden" name="trip_room_id" :value="assign.roomUid">
                            <input type="hidden" name="booking_seat_id" :value="assign.seatId">
                        </form>

                        <input type="text" x-model="assign.search" x-ref="assignSearch"
                            placeholder="🔍 ابحث بالاسم أو رقم الحجز..."
                            class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 outline-none focus:border-indigo-500 font-bold text-slate-700 mb-3">

                        <div class="max-h-72 overflow-y-auto custom-scrollbar space-y-3 pl-1">
                            <div x-show="filteredMales.length > 0">
                                <span
                                    class="text-[10px] font-black text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md block mb-1">👨
                                    ذكور بالانتظار</span>
                                <template x-for="sm in filteredMales" :key="sm.id">
                                    <button type="button" @click="pick(sm.id)" :disabled="submitting"
                                        class="w-full text-right text-xs font-bold text-slate-700 p-2 hover:bg-indigo-50 rounded-xl flex items-center justify-between cursor-pointer disabled:opacity-50">
                                        <div>
                                            <span x-text="sm.name"></span>
                                            <span class="text-[9px] text-indigo-600 font-bold block"
                                                x-text="sm.sub_label"></span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-mono"
                                            x-text="'#' + sm.booking_id"></span>
                                    </button>
                                </template>
                            </div>

                            <div x-show="filteredFemales.length > 0">
                                <span
                                    class="text-[10px] font-black text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md block mb-1">👩
                                    إناث بالانتظار</span>
                                <template x-for="sf in filteredFemales" :key="sf.id">
                                    <button type="button" @click="pick(sf.id)" :disabled="submitting"
                                        class="w-full text-right text-xs font-bold text-slate-700 p-2 hover:bg-indigo-50 rounded-xl flex items-center justify-between cursor-pointer disabled:opacity-50">
                                        <div>
                                            <span x-text="sf.name"></span>
                                            <span class="text-[9px] text-indigo-600 font-bold block"
                                                x-text="sf.sub_label"></span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-mono"
                                            x-text="'#' + sf.booking_id"></span>
                                    </button>
                                </template>
                            </div>

                            <div x-show="filteredMales.length === 0 && filteredFemales.length === 0"
                                class="text-[11px] text-slate-400 text-center py-6 font-bold">
                                لا يوجد مسافرون مطابقون بالانتظار
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- ===================== قالب الطباعة ===================== --}}
    @if ($activeTrip)
        <div id="print-area" class="hidden">
            <div class="w-full max-w-[297mm] mx-auto bg-white p-8 rounded-[2rem] border border-slate-200 text-right font-cairo"
                dir="rtl">
                <div class="flex items-center justify-between border-b-2 border-slate-100 pb-5 mb-5">
                    <div class="flex items-center gap-3 w-1/3">
                        <div
                            class="w-12 h-12 rounded-full overflow-hidden border-2 border-slate-100 shadow-sm bg-white flex items-center justify-center">
                            @if ($agency && $agency->iconA)
                                <img src="{{ asset('storage/' . $agency->iconA) }}" alt="شعار الوكالة"
                                    class="w-full h-full object-cover">
                            @else
                                🏢
                            @endif
                        </div>
                        <div>
                            <h2 class="text-sm font-black text-[#032B53]">{{ $agency?->name }}</h2>
                            <p class="text-[9px] text-slate-400 font-bold">لخدمات السياحة والسفر</p>
                        </div>
                    </div>

                    <div class="text-center w-1/3">
                        <h1 class="text-2xl font-black text-[#032B53] tracking-wide">تقرير تسكين الغرف</h1>
                        <p class="text-[10px] text-teal-600 font-bold uppercase tracking-widest mt-0.5">ACCOMMODATION
                            REPORT</p>
                        <div class="w-24 h-0.5 bg-teal-500 mx-auto mt-2"></div>
                    </div>

                    <div class="flex items-center justify-end gap-3 w-1/3">
                        <img src="{{ asset('images/logo.jpeg') }}" alt="عبور" class="h-16 w-auto object-contain">
                    </div>
                </div>

                <div
                    class="grid grid-cols-5 gap-2 border border-slate-200 rounded-2xl p-4 bg-slate-50/50 mb-6 text-xs font-bold text-slate-700">
                    <div class="flex items-center gap-2 justify-center border-l border-slate-200">
                        <span class="text-base">💼</span>
                        <div>
                            <span class="text-[10px] text-slate-400 block">اسم الرحلة</span>
                            <span class="text-[#032B53] font-black text-[11px]">{{ $activeTrip->title }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 justify-center border-l border-slate-200">
                        <span class="text-base">🏨</span>
                        <div>
                            <span class="text-[10px] text-slate-400 block">الفندق</span>
                            <span
                                class="text-[#032B53] font-black text-[11px]">{{ $activeTrip->hotel_name ?? '---' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 justify-center border-l border-slate-200">
                        <span class="text-base">📍</span>
                        <div>
                            <span class="text-[10px] text-slate-400 block">المدينة</span>
                            <span class="text-[#032B53] font-black text-[11px]">{{ $activeTrip->to_city ?? '---' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 justify-center border-l border-slate-200">
                        <span class="text-base">📅</span>
                        <div>
                            <span class="text-[10px] text-slate-400 block">تاريخ المغادرة</span>
                            <span class="text-[#032B53] font-mono text-[11px]">
                                {{ $activeTrip->departure_at ? \Carbon\Carbon::parse($activeTrip->departure_at)->format('d / m / Y') : '---' }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 justify-center">
                        <span class="text-base">📅</span>
                        <div>
                            <span class="text-[10px] text-slate-400 block">تاريخ الوصول</span>
                            <span class="text-[#032B53] font-mono text-[11px]">
                                {{ $activeTrip->arrival_at ? \Carbon\Carbon::parse($activeTrip->arrival_at)->format('d / m / Y') : '---' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-[#032B53] text-white grid grid-cols-[50px_130px_1fr_110px_200px_200px] items-center font-black text-[11px] text-center py-3.5 rounded-2xl mb-4 shadow-sm">
                    <div class="text-center">م</div>
                    <div class="border-r border-white/20 text-center">رقم الحجز</div>
                    <div class="border-r border-white/20 text-center">الغرفة والنزلاء</div>
                    <div class="border-r border-white/20 text-center">نوع الغرفة</div>
                    <div class="border-r border-white/20 text-center">ملاحظات السكن والغرف</div>
                    <div class="border-r border-white/20 text-center">ملاحظات الفندق والإدارة</div>
                </div>

                <div class="space-y-4">
                    @forelse($printGroups as $g)
                        @php
                            $personIconsCount = min($g->seats->count(), 4);
                            $typeIcons = match ($g->type) {
                                'single' => 1,
                                'double' => 2,
                                'triple' => 3,
                                'quad' => 4,
                                default => min($g->seats->count(), 4),
                            };
                            $printTypeLabel = match ($g->type) {
                                'single' => 'مفردة (Single)',
                                'double' => 'ثنائية (Double)',
                                'triple' => 'ثلاثية (Triple)',
                                'quad' => 'رباعية (Quad)',
                                default => 'مشتركة',
                            };
                        @endphp

                        <div
                            class="grid grid-cols-[50px_130px_1fr_110px_200px_200px] items-stretch border border-slate-200 rounded-2xl overflow-hidden min-h-[110px] page-break-inside-avoid bg-white">
                            <div
                                class="flex items-center justify-center bg-slate-50/80 border-l border-slate-200 font-bold text-slate-800">
                                <span
                                    class="w-7 h-7 rounded-lg bg-[#032B53] text-white flex items-center justify-center font-black text-xs">
                                    {{ $loop->iteration }}
                                </span>
                            </div>

                            <div
                                class="border-l border-slate-200 p-3 flex flex-col items-center justify-center bg-slate-50/40">
                                <span class="font-mono font-black text-xs text-[#032B53] text-center">
                                    @if ($g->isShared)
                                        عام: {{ $g->bookingIds->implode('، ') }}
                                    @else
                                        {{ $g->bookingIds->first() }}
                                    @endif
                                </span>
                                <div class="flex items-center justify-center gap-0.5 text-teal-600 my-1.5">
                                    @for ($i = 0; $i < $personIconsCount; $i++)
                                        <span class="text-xs">👤</span>
                                    @endfor
                                </div>
                                <span class="text-[10px] text-slate-500 font-bold">{{ $g->seats->count() }} نزلاء</span>
                            </div>

                            <div class="border-l border-slate-200 p-3 flex items-center justify-center bg-white">
                                <div
                                    class="border border-teal-500/20 rounded-xl bg-slate-50/30 p-2.5 flex-1 min-w-[130px] max-w-[260px] shadow-sm">
                                    <div
                                        class="flex items-center justify-center gap-1 border-b border-slate-100 pb-1 mb-1.5 text-teal-700 font-black text-[10px]">
                                        <span>🛏️</span>
                                        <span>الغرفة {{ $g->roomNumber ?: 'بدون رقم' }}</span>
                                    </div>
                                    <ul class="text-[10px] text-slate-700 space-y-1">
                                        @foreach ($g->seats as $index => $p)
                                            <li class="truncate flex items-center gap-1">
                                                <span
                                                    class="text-slate-400 font-mono text-[9px]">{{ $index + 1 }}.</span>
                                                <span class="font-bold text-slate-800">{{ $seatName($p) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>

                            <div
                                class="border-l border-slate-200 p-3 flex flex-col items-center justify-center text-center bg-white">
                                <span class="text-[11px] font-black text-slate-800 mb-1">{{ $printTypeLabel }}</span>
                                <div class="flex items-center justify-center gap-0.5 text-[#032B53]">
                                    @for ($i = 0; $i < $typeIcons; $i++)
                                        <span class="text-[10px]">👤</span>
                                    @endfor
                                </div>
                                <span class="text-[9px] text-slate-500 mt-1">{{ $g->bedTypes->implode(' / ') ?: 'نوع السرير غير محدد' }}</span>
                                @if ($g->extraBeds)
                                    <span class="text-[9px] text-amber-700 font-bold">أسرة إضافية: {{ $g->extraBeds }}</span>
                                @endif
                            </div>

                            <div
                                class="border-l border-slate-200 p-3 flex items-center justify-center text-center bg-slate-50/10">
                                <span class="text-[11px] text-slate-600 italic font-medium leading-relaxed">
                                    {{ $g->notes ?: 'لا توجد ملاحظات خاصة بالمسكن' }}
                                </span>
                            </div>

                            <div class="p-3 flex flex-col justify-between bg-white">
                                <h4 class="text-[9px] font-black text-slate-400 mb-1 text-center">📝 ملاحظات الفندق</h4>
                                <div class="flex-1 flex flex-col justify-around gap-1.5">
                                    <div class="border-b border-dashed border-slate-300 h-4"></div>
                                    <div class="border-b border-dashed border-slate-300 h-4"></div>
                                    <div class="border-b border-dashed border-slate-300 h-4"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div
                            class="p-8 text-center text-slate-400 italic text-xs font-bold border border-dashed border-slate-200 rounded-2xl">
                            لا توجد غرف مسكنة للطباعة حالياً
                        </div>
                    @endforelse
                </div>

                <div class="mt-8 grid grid-cols-3 gap-6 items-end">
                    <div class="col-span-1 border border-slate-200 rounded-2xl p-4 bg-slate-50/30 text-right">
                        <h4 class="text-xs font-black text-rose-600 flex items-center gap-1 mb-2">
                            <span>📝</span> ملاحظات هامة للطباعة:
                        </h4>
                        <ul class="text-[10px] text-slate-500 font-bold space-y-1.5 leading-relaxed">
                            <li class="flex items-start gap-1">
                                <span class="text-rose-500">•</span>
                                <span>يرجى تسليم هذا الكشف المعتمد رسمياً إلى موظفي استقبال الفندق لتسهيل عملية توزيع
                                    الغرف.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="col-span-2 grid grid-cols-4 gap-3">
                        <div
                            class="border border-slate-200 rounded-2xl p-3 text-center bg-white shadow-sm flex flex-col justify-between">
                            <span class="text-[9px] text-slate-400 font-bold block mb-1">إجمالي النزلاء</span>
                            <span class="text-base font-black text-[#032B53] font-mono">{{ $totalGuests }}</span>
                        </div>
                        <div
                            class="border border-slate-200 rounded-2xl p-3 text-center bg-white shadow-sm flex flex-col justify-between">
                            <span class="text-[9px] text-slate-400 font-bold block mb-1">إجمالي الغرف</span>
                            <span class="text-base font-black text-[#032B53] font-mono">{{ $totalRoomsCount }}</span>
                        </div>
                        <div
                            class="border border-slate-200 rounded-2xl p-3 text-center bg-white shadow-sm flex flex-col justify-between">
                            <span class="text-[9px] text-slate-400 font-bold block mb-1">أسرة مفردة / مزدوجة</span>
                            <span class="text-base font-black text-[#032B53] font-mono">{{ $singleBeds }} / {{ $doubleBeds }}</span>
                        </div>
                        <div
                            class="border border-slate-200 rounded-2xl p-3 text-center bg-white shadow-sm flex flex-col justify-between">
                            <span class="text-[9px] text-slate-400 font-bold block mb-1">أسرة إضافية</span>
                            <span class="text-base font-black text-[#032B53] font-mono">{{ $totalExtraBeds }}</span>
                        </div>
                        <div
                            class="border border-slate-200 rounded-2xl p-3 text-center bg-white shadow-sm flex flex-col justify-between">
                            <span class="text-[9px] text-slate-400 font-bold block mb-1">غير مسكّنين</span>
                            <span class="text-base font-black text-[#032B53] font-mono">{{ $unassignedGuestCount }}</span>
                        </div>
                        <div
                            class="border border-slate-200 rounded-2xl p-3 text-center bg-white shadow-sm flex flex-col justify-between">
                            <span class="text-[9px] text-slate-400 font-bold block mb-1">النساء</span>
                            <span class="text-base font-black text-[#032B53] font-mono">{{ $printFemales }}</span>
                        </div>
                        <div
                            class="border border-slate-200 rounded-2xl p-3 text-center bg-white shadow-sm flex flex-col justify-between">
                            <span class="text-[9px] text-slate-400 font-bold block mb-1">الرجال</span>
                            <span class="text-base font-black text-[#032B53] font-mono">{{ $printMales }}</span>
                        </div>
                        <div
                            class="border border-slate-200 rounded-2xl p-3 text-center bg-white shadow-sm flex flex-col justify-between">
                            <span class="text-[9px] text-slate-400 font-bold block mb-1">عدد الحجوزات</span>
                            <span class="text-base font-black text-[#032B53] font-mono">{{ $totalBookingsCount }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- جافا سكريبت --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /* ---------- 1) قائمة اختيار الرحلة ---------- */
            const container = document.getElementById('custom-dropdown-container');
            const searchInput = document.getElementById('trip_search_input');
            const panel = document.getElementById('dropdown-list-panel');
            const hiddenInput = document.getElementById('real_trip_id');
            const chevron = document.getElementById('dropdown-chevron');
            const noResults = document.getElementById('no-results-msg');
            const form = document.getElementById('filterForm');

            if (searchInput && panel) {
                const items = Array.from(panel.querySelectorAll('.dropdown-item'));
                const selectedLabel = searchInput.value;

                const openPanel = () => {
                    panel.classList.remove('hidden');
                    chevron.classList.add('rotate-180');
                };

                const closePanel = () => {
                    panel.classList.add('hidden');
                    chevron.classList.remove('rotate-180');
                    searchInput.value = selectedLabel;
                    items.forEach(i => i.classList.remove('hidden'));
                    noResults.classList.add('hidden');
                };

                const selectItem = (item) => {
                    if (item.dataset.value === hiddenInput.value) {
                        closePanel();
                        return;
                    }
                    hiddenInput.value = item.dataset.value;
                    searchInput.value = item.dataset.label || item.querySelector('.trip-title-text').innerText;
                    panel.classList.add('hidden');
                    chevron.classList.remove('rotate-180');
                    form.submit();
                };

                searchInput.addEventListener('focus', () => {
                    searchInput.select();
                    openPanel();
                });

                searchInput.addEventListener('input', function() {
                    const query = this.value.trim().toLowerCase();
                    let visible = 0;
                    openPanel();

                    items.forEach(item => {
                        const match = (item.dataset.search || '').includes(query);
                        item.classList.toggle('hidden', !match);
                        if (match) visible++;
                    });

                    noResults.classList.toggle('hidden', visible > 0 || query === '');
                });

                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const first = items.find(i => !i.classList.contains('hidden'));
                        if (first) selectItem(first);
                    } else if (e.key === 'Escape') {
                        closePanel();
                        searchInput.blur();
                    }
                });

                items.forEach(item => item.addEventListener('click', () => selectItem(item)));

                document.addEventListener('click', function(e) {
                    if (!container.contains(e.target) && !panel.classList.contains('hidden')) {
                        closePanel();
                    }
                });
            }

            /* ---------- 2) حفظ رقم الغرفة والملاحظات عبر AJAX ---------- */
            const CSRF = @js(csrf_token());
            const TRIP_ID = @js($selectedTripId);
            const ROUTES = {
                room_number: @js(route('agency.bookings.room_number.update')),
                notes: @js(route('agency.bookings.room_notes.update'))
            };

            const borderStates = {
                idle: 'border-slate-200',
                saving: 'border-indigo-400',
                ok: 'border-emerald-500',
                error: 'border-rose-400'
            };

            function setBorder(input, state) {
                input.classList.remove(...Object.values(borderStates));
                input.classList.add(borderStates[state]);
            }

            function syncCardIds(input, newRoomId) {
                const card = input.closest('[data-card]');
                if (!card) return;
                card.querySelectorAll('.ajax-room-number-input, .ajax-room-notes-input').forEach(i => {
                    i.dataset.roomId = newRoomId;
                    i.dataset.roomIds = JSON.stringify([newRoomId]);
                });
            }

            function updateTable(group, field, value) {
                if (field === 'room_number') {
                    document.querySelectorAll(`[data-live-number="${group}"]`).forEach(el => {
                        el.textContent = value || '-';
                    });
                    document.querySelectorAll(`[data-live-status="${group}"]`).forEach(el => {
                        el.className =
                            'bg-emerald-50 text-emerald-700 text-[10px] px-2 py-1 rounded-md font-bold inline-flex items-center gap-1';
                        el.textContent = value ? `✓ مسكّن بالغرفة رقم #${value}` : '✓ مسكّن (بدون رقم)';
                    });
                } else {
                    document.querySelectorAll(`[data-live-notes="${group}"]`).forEach(el => {
                        el.textContent = value ? '📝 ' + value : '';
                        el.classList.toggle('hidden', !value);
                    });
                }
            }

            async function saveRoomField(input, field, indicatorSelector) {
                const value = input.value.trim();

                if (field === 'room_number' && value !== '') {
                    const duplicated = Array.from(document.querySelectorAll('.ajax-room-number-input'))
                        .some(o => o !== input && o.value.trim() === value);
                    if (duplicated && !confirm(
                            `رقم الغرفة ${value} مُدخل مسبقاً في حجز آخر. هل تريد المتابعة؟`)) {
                        input.value = input.dataset.original || '';
                        return;
                    }
                }

                setBorder(input, 'saving');
                const indicator = input.parentElement.querySelector(indicatorSelector);

                let roomIds = [];
                try {
                    roomIds = JSON.parse(input.dataset.roomIds || '[]');
                } catch (e) {}

                try {
                    const res = await fetch(ROUTES[field], {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            room_id: input.dataset.roomId || null,
                            room_ids: roomIds,
                            [field]: value,
                            trip_id: TRIP_ID,
                            booking_id: input.dataset.bookingId,
                            room_type: input.dataset.roomType
                        })
                    });

                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) {
                        throw new Error(data.message || 'حدث خطأ أثناء الحفظ.');
                    }

                    input.dataset.original = value;
                    if (data.new_room_id) syncCardIds(input, data.new_room_id);
                    updateTable(input.dataset.group, field, value);

                    setBorder(input, 'ok');
                    if (indicator) {
                        indicator.classList.remove('hidden');
                        setTimeout(() => {
                            indicator.classList.add('hidden');
                            setBorder(input, 'idle');
                        }, 2000);
                    }
                } catch (err) {
                    setBorder(input, 'error');
                    alert(err.message || 'حدث خطأ أثناء الحفظ.');
                }
            }

            function bindRoomInputs(selector, field, indicatorSelector) {
                document.querySelectorAll(selector).forEach(input => {
                    input.dataset.original = input.value;
                    input.addEventListener('keydown', e => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            input.blur();
                        }
                    });
                    input.addEventListener('change', () => saveRoomField(input, field, indicatorSelector));
                });
            }

            bindRoomInputs('.ajax-room-number-input', 'room_number', '.save-status-indicator');
            bindRoomInputs('.ajax-room-notes-input', 'notes', '.save-notes-indicator');
        });

        /* ---------- 3) الطباعة عبر iframe مخفي ---------- */
        function printCustomTable() {
            const printElement = document.getElementById('print-area');

            if (!printElement) {
                alert('لم يتم العثور على قالب الطباعة للرحلة الحالية!');
                return;
            }

            const old = document.getElementById('print-iframe');
            if (old) old.remove();

            const iframe = document.createElement('iframe');
            iframe.id = 'print-iframe';
            iframe.style.cssText =
                'position:fixed;left:-10000px;top:0;width:1123px;height:794px;border:0;visibility:hidden;';
            document.body.appendChild(iframe);

            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write(
                '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>تقرير السكن - منصة عبور</title></head><body></body></html>'
            );
            doc.close();

            const pending = [];
            document.querySelectorAll('link[rel="stylesheet"]').forEach(l => {
                const link = doc.createElement('link');
                link.rel = 'stylesheet';
                link.href = l.href;
                pending.push(new Promise(r => {
                    link.onload = link.onerror = r;
                }));
                doc.head.appendChild(link);
            });
            document.querySelectorAll('style').forEach(s => doc.head.appendChild(doc.importNode(s, true)));

            const printStyle = doc.createElement('style');
            printStyle.textContent = `
                @page { size: A4 landscape; margin: 6mm; }
                body { font-family: 'Cairo', sans-serif !important; background: #fff; padding: 12px; color: #000; }
                * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                .page-break-inside-avoid { page-break-inside: avoid !important; break-inside: avoid !important; }
            `;
            doc.head.appendChild(printStyle);

            doc.body.innerHTML = '<div class="w-full">' + printElement.innerHTML + '</div>';

            Array.from(doc.images).forEach(img => {
                if (!img.complete) {
                    pending.push(new Promise(r => {
                        img.onload = img.onerror = r;
                    }));
                }
            });
            if (doc.fonts && doc.fonts.ready) pending.push(doc.fonts.ready);

            Promise.race([Promise.all(pending), new Promise(r => setTimeout(r, 5000))]).then(() => {
                setTimeout(() => {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                }, 300);
            });
        }
    </script>
@endsection
