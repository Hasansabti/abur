@extends('layouts.client')

@section('content')
    @php
        // 0. دعم استرجاع المدخلات القديمة عند أخطاء Validation
        $booking_id = old('booking_id', $booking_id ?? null);
        $selected_seats = old('selected_seats', $selected_seats ?? null);
        $passenger_names = old('passenger_names', $passenger_names ?? null);
        $passenger_ids = old('passenger_ids', $passenger_ids ?? null);
        $passenger_genders = old('passenger_genders', $passenger_genders ?? null);
        $room_quantities = old('room_quantities', $room_quantities ?? null);
        $room_assignments_json = old('room_assignments', $room_assignments_json ?? ($room_assignments ?? '{}'));
        $passenger_meals_json = old('passenger_meals', $passenger_meals_json ?? ($passenger_meals ?? '{}'));
        $passenger_services_json = old('passenger_services', $passenger_services_json ?? ($passenger_services ?? '[]'));

        // 0.1 قيم دفاعية للمتغيرات المفترض أنها تصل من الكنترولر، لتفادي أي "Undefined variable" مستقبلاً
        $room_prices = $room_prices ?? [];
        $mealOptionsArray = $mealOptionsArray ?? [];
        $room_options = $room_options ?? [];
        $agency_whatsapp = $agency_whatsapp ?? '';
        $total_services_cost = $total_services_cost ?? 0;

        // 0.2 دالة موحّدة لتمييز مقاعد السكن الوهمية (H) - لا تُعرض للمستخدم أبداً
        $isHSeat = fn($s) => str_starts_with(strtoupper(trim((string) $s)), 'H');

        // 1. استلام وتأمين المقاعد وتصفيتها من المقاعد السالبة/الوهمية فوراً
        $rawSeats = is_array($selected_seats)
            ? $selected_seats
            : (is_string($selected_seats) && trim($selected_seats) !== ''
                ? explode(',', $selected_seats)
                : []);

        $seatsArray = collect($rawSeats)
            ->filter(fn($val) => $val !== null && $val !== '')
            ->filter(fn($seat) => !(is_numeric($seat) && floatval($seat) < 0))
            ->values()
            ->all();

        // 2. استبعاد مقاعد السكن الوهمية H
        $nonHSeatsArray = collect($seatsArray)->filter(fn($seat) => !$isHSeat($seat))->values()->all();

        $realSeatsArray = $nonHSeatsArray;
        $totalNonHSeatsCount = count($nonHSeatsArray);

        // 3. تحويل وتأمين بيانات المسافرين
        if (is_array($passenger_names)) {
            $namesArray = $passenger_names;
        } else {
            $decodedNames = json_decode($passenger_names ?? '', true);
            $namesArray = is_array($decodedNames) ? $decodedNames : explode(',', $passenger_names ?? '');
        }

        if (is_array($passenger_ids)) {
            $idsArray = $passenger_ids;
        } else {
            $decodedIds = json_decode($passenger_ids ?? '', true);
            $idsArray = is_array($decodedIds)
                ? $decodedIds
                : array_map(fn($id) => trim($id) === '' ? '0000000000' : trim($id), explode(',', $passenger_ids ?? ''));
        }

        if (is_array($passenger_genders)) {
            $gendersArray = $passenger_genders;
        } else {
            $decodedGenders = json_decode($passenger_genders ?? '', true);
            $gendersArray = is_array($decodedGenders) ? $decodedGenders : explode(',', $passenger_genders ?? '');
        }

        // 4. حساب سعة السكن + خريطة تطبيع أنواع الغرف
        $roomQuantitiesArray = is_array($room_quantities)
            ? $room_quantities
            : (json_decode($room_quantities ?? '{}', true) ?:
            []);

        $roomCapacities = ['single' => 1, 'double' => 2, 'triple' => 3, 'quad' => 4, 'quint' => 5];

        $normalizeRoomType = function ($typeStr) {
            if (empty($typeStr)) {
                return '';
            }
            $t = mb_strtolower(trim((string) $typeStr));
            if (str_contains($t, 'sing') || str_contains($t, 'فرد')) {
                return 'single';
            }
            if (str_contains($t, 'doub') || str_contains($t, 'ثنائ') || str_contains($t, 'مزدوج')) {
                return 'double';
            }
            if (str_contains($t, 'trip') || str_contains($t, 'ثلاث')) {
                return 'triple';
            }
            if (str_contains($t, 'quad') || str_contains($t, 'رباع')) {
                return 'quad';
            }
            if (str_contains($t, 'quin') || str_contains($t, 'خماس')) {
                return 'quint';
            }
            return $t;
        };

        // 5. تجهيز roomAssignments الفعلية (seat => label) من التسكين الحقيقي
        $roomAssignmentsArray = is_array($room_assignments_json)
            ? $room_assignments_json
            : (json_decode($room_assignments_json ?? '{}', true) ?:
            []);

        // خريطة seat => نوع الغرفة المُطبّع (single/double/...)، من التسكين الفعلي
        $seatTypeMap = [];
        foreach ($roomAssignmentsArray as $seatKey => $label) {
            $type = $normalizeRoomType($label);
            if (!empty($type) && isset($roomCapacities[$type])) {
                $seatTypeMap[(string) $seatKey] = $type;
            }
        }

        // 6. الحسبة الديناميكية: سعر السكن والمقاعد الإضافية لكل مقعد على حدة
        $seatBasePrice = floatval($trip->extra_seat_price ?? ($trip->seat_price ?? ($trip->price ?? 0)));
        $accommodationOnlyDiscount = floatval($trip->accommodation_only_discount ?? 0);

        $coveredSeatsCount = 0;
        $calcRoomsCost = 0;
        $accommodationOnlySeatsCountView = 0;
        $roomTypeCostBreakdown = [];
        foreach ($seatTypeMap as $seatKey => $type) {
            $calcRoomsCost += floatval($room_prices[$type] ?? 0);
            $coveredSeatsCount++;
            $roomTypeCostBreakdown[$type] = ($roomTypeCostBreakdown[$type] ?? 0) + floatval($room_prices[$type] ?? 0);
            if ($isHSeat($seatKey)) {
                $accommodationOnlySeatsCountView++;
            }
        }
        $totalAccommodationDiscountView = $accommodationOnlySeatsCountView * $accommodationOnlyDiscount;

        $totalExtraSeatsCount = 0;
        foreach ($nonHSeatsArray as $seat) {
            if (!isset($seatTypeMap[(string) $seat])) {
                $totalExtraSeatsCount++;
            }
        }

        $calcSeatsCost = $totalExtraSeatsCount * $seatBasePrice;

        // 7. خريطة الأسماء العربية لأنواع الغرف + بناء seatRoomMapping (نص جاهز للعرض في تفاصيل كل مسافر)
        $typeArabicMap = [
            'single' => 'فردية',
            'double' => 'ثنائية',
            'triple' => 'ثلاثية',
            'quad' => 'رباعية',
            'quint' => 'خماسية',
        ];

// الأنواع اللي حددتها الوكالة كمتاحة للمشاركة
$sharedAllowedKeys = collect($room_options)
    ->filter(fn($r) => is_array($r) && !empty($r['shared']))
    ->keys()
    ->map(fn($k) => strtolower((string) $k))
    ->values()
    ->all();

if (count($sharedAllowedKeys) === 1) {
    // نوع واحد فقط مسموح: اسم الغرفة المشتركة يعرض نوعها
    $defaultTypeArabic = $typeArabicMap[$sharedAllowedKeys[0]] ?? '';
} elseif (count($sharedAllowedKeys) > 1) {
    // أكثر من نوع: نعرض "غرفة مشتركة" بدون نوع إلا لو كان النوع مكتوباً في التسمية نفسها
    $defaultTypeArabic = '';
} else {
    // رحلات قديمة بدون تحديد: السلوك السابق
    $activeRoomType = null;
    foreach ($roomQuantitiesArray as $qType => $qQty) {
        if (intval($qQty) > 0) {
            $activeRoomType = $qType;
            break;
        }
    }
    $defaultTypeArabic = $typeArabicMap[$activeRoomType] ?? '';
}

        $seatRoomMapping = [];

        if (!empty($roomAssignmentsArray)) {
            foreach ($roomAssignmentsArray as $seatKey => $roomLabel) {
                if (!empty($roomLabel)) {
                    $cleanLabel = strtolower(trim((string) $roomLabel));

                    if ($cleanLabel === 'shared') {
                        $roomText = $defaultTypeArabic ? "غرفة مشتركة ({$defaultTypeArabic})" : 'غرفة مشتركة';
                    } elseif (str_contains($cleanLabel, 'shared') || str_contains($cleanLabel, 'مشترك')) {
                        $foundType = null;
                        foreach ($typeArabicMap as $tKey => $tName) {
                            if (str_contains($cleanLabel, $tKey) || str_contains($cleanLabel, $tName)) {
                                $foundType = $tName;
                                break;
                            }
                        }
                        $typeName = $foundType ?: $defaultTypeArabic;
                        $roomText = $typeName ? "غرفة مشتركة ({$typeName})" : 'غرفة مشتركة';
                    } elseif (preg_match('/^(single|double|triple|quad|quint)(?:_(\d+))?$/', $cleanLabel, $m)) {
                        // صيغة مثل single_1 أو double_2 القادمة من الجافاسكربت
                        $roomText = "غرفة خاصة ({$typeArabicMap[$m[1]]})" . (!empty($m[2]) ? " #{$m[2]}" : '');
                    } else {
                        $roomText = $roomLabel;
                    }

                    $seatRoomMapping[$seatKey] = $roomText;
                }
            }
        }

        // 8. مسار احتياطي للمقاعد التي لم يصلها توزيع صريح (للعرض فقط، لا يؤثر على السعر)
        $seatIndex = 0;
        $remainingSeatsForFallback = array_values(array_diff($realSeatsArray, array_keys($seatRoomMapping)));
        foreach ($roomQuantitiesArray as $type => $qty) {
            for ($i = 0; $i < $qty; $i++) {
                $cap = $roomCapacities[$type] ?? 0;
                for ($c = 0; $c < $cap; $c++) {
                    if (isset($remainingSeatsForFallback[$seatIndex])) {
                        $typeArabic = $typeArabicMap[$type] ?? $type;
                        $roomText = $type === 'single' ? 'غرفة خاصة (فردية)' : "غرفة خاصة ({$typeArabic})";
                        $seatRoomMapping[$remainingSeatsForFallback[$seatIndex]] = $roomText;
                        $seatIndex++;
                    }
                }
            }
        }

        // 9. الوجبات والخدمات الإضافية
        $passengerMealsArray = is_array($passenger_meals_json)
            ? $passenger_meals_json
            : (json_decode($passenger_meals_json ?? '{}', true) ?:
            []);

        $passengerServicesArray = is_array($passenger_services_json)
            ? $passenger_services_json
            : (json_decode($passenger_services_json ?? '[]', true) ?:
            []);

        $finalPassengerMeals = $passengerMealsArray;

        $calcServicesCost = $total_services_cost;
        if ($calcServicesCost == 0 && !empty($passengerServicesArray)) {
            foreach ($passengerServicesArray as $seatServices) {
                if (is_array($seatServices)) {
                    foreach ($seatServices as $srv) {
                        $calcServicesCost += floatval($srv['price'] ?? ($srv['cost'] ?? 0));
                    }
                }
            }
        }

        $baseTotalWithoutMeals = max(
            0,
            $calcSeatsCost + $calcRoomsCost + $calcServicesCost - $totalAccommodationDiscountView,
        );

        // 10. تجميع بيانات المسافرين للعرض
        $groupedPassengers = [];
        $lastValidGroupKey = null;

        foreach ($seatsArray as $index => $seat) {
            $rawId = trim($idsArray[$index] ?? '');
            $rawName = trim($namesArray[$index] ?? '');
            $gender = trim(str_replace(['"', '[', ']', '\\'], '', $gendersArray[$index] ?? ''));

            $isDummyId = str_starts_with($rawId, '99000000') || empty($rawId) || $rawId === '0000000000';
            $isSyntheticSeat = is_numeric($seat) && floatval($seat) < 0;

            // مسافر السكن فقط ما عنده مقعد، فلا نولّد له اسم مقعد (ولا نعرض كود H أبداً)
            $isAccomSeat = $isHSeat($seat);
            $cleanSeatLabel = $isAccomSeat ? null : ($isSyntheticSeat ? 'مقعد إضافي' : 'مقعد ' . $seat);

            $hasValidId = !$isDummyId;
            $hasValidName = !empty($rawName) && $rawName !== 'مسافر' && $rawName !== 'لم يسجل اسم بعد';

            if ($hasValidId) {
                $groupKey = 'id_' . $rawId;
                $lastValidGroupKey = $groupKey;
            } elseif ($hasValidName) {
                $groupKey = 'name_' . mb_strtolower($rawName);
                $lastValidGroupKey = $groupKey;
            } else {
                $groupKey = $lastValidGroupKey ?? 'passenger_' . $index;
            }

            if (!isset($groupedPassengers[$groupKey])) {
                $parentPassenger =
                    $lastValidGroupKey && isset($groupedPassengers[$lastValidGroupKey])
                        ? $groupedPassengers[$lastValidGroupKey]
                        : null;

                $groupedPassengers[$groupKey] = [
                    'name' => $hasValidName ? $rawName : $parentPassenger['name'] ?? 'مسافر',
                    'id' => $hasValidId ? $rawId : $parentPassenger['id'] ?? $rawId,
                    'is_dummy_id' => $hasValidId ? false : $parentPassenger['is_dummy_id'] ?? $isDummyId,
                    'gender' => $gender ?: $parentPassenger['gender'] ?? '',
                    'seat_labels' => $cleanSeatLabel !== null ? [$cleanSeatLabel] : [],
                    'raw_seats' => [$seat],
                    'is_accommodation_only' => $isAccomSeat,
                ];
            } else {
                if ($cleanSeatLabel !== null) {
                    $groupedPassengers[$groupKey]['seat_labels'][] = $cleanSeatLabel;
                }
                $groupedPassengers[$groupKey]['raw_seats'][] = $seat;
                if ($isAccomSeat) {
                    $groupedPassengers[$groupKey]['is_accommodation_only'] = true;
                }
            }
        }
    @endphp

    <div class="container mx-auto px-4 py-10 max-w-6xl" x-data="{
        paymentMethod: '{{ old('payment_method', 'card') }}',
        selectedBank: null,
        fileName: '',

        selectedSeats: @js($seatsArray),
        passengerNames: @js($namesArray),
        passengerGenders: @js($gendersArray),
        passengerIds: @js($idsArray),
        passengerMeals: @js($finalPassengerMeals),
        passengerServices: @js($passengerServicesArray),

        mealPrices: {
            breakfast: {{ $mealOptionsArray['individual']['breakfast']['price'] ?? ($trip->breakfast_price ?? 0) }},
            lunch: {{ $mealOptionsArray['individual']['lunch']['price'] ?? ($trip->lunch_price ?? 0) }},
            dinner: {{ $mealOptionsArray['individual']['dinner']['price'] ?? ($trip->dinner_price ?? 0) }},
            bf_ln: {{ $mealOptionsArray['packages']['bf_ln'] ?? ($trip->price_bf_ln ?? 0) }},
            ln_dn: {{ $mealOptionsArray['packages']['ln_dn'] ?? ($trip->price_ln_dn ?? 0) }},
            bf_dn: {{ $mealOptionsArray['packages']['bf_dn'] ?? ($trip->price_bf_dn ?? 0) }},
            all_meals: {{ $mealOptionsArray['packages']['all'] ?? ($trip->price_all_meals ?? 0) }}
        },

        roomPackages: [
            @foreach (['single', 'double', 'triple', 'quad', 'quint'] as $type)
                @if (!empty($room_options[$type]['packages']))
                    @foreach ($room_options[$type]['packages'] as $pkg)
                       { name: '{{ $pkg['name'] }}', price: {{ $pkg['price'] ?? 0 }} }, @endforeach
            @endif
            @endforeach
        ],

        calculateMeals() {
            let totalIndividual = 0;
            let totalWithCombo = 0;

            let mealsList = Array.isArray(this.passengerMeals) ? this.passengerMeals : Object.values(this.passengerMeals);
            mealsList.forEach(meal => {
                if (!meal || !meal.wants_meal) return;

                if (meal.package_name) {
                    let pkgPrice = parseFloat(meal.package_price || 0);
                    if (pkgPrice <= 0) {
                        let found = this.roomPackages.find(p => p.name === meal.package_name);
                        if (found) pkgPrice = found.price;
                    }
                    if (pkgPrice > 0) {
                        totalWithCombo += pkgPrice;
                        totalIndividual += pkgPrice;
                        return;
                    }
                }

                let hasBf = !!meal.breakfast;
                let hasLn = !!meal.lunch;
                let hasDn = !!meal.dinner;

                let individualSum = (hasBf ? this.mealPrices.breakfast : 0) +
                    (hasLn ? this.mealPrices.lunch : 0) +
                    (hasDn ? this.mealPrices.dinner : 0);
                totalIndividual += individualSum;
                let finalPrice = individualSum;

                if (hasBf && hasLn && hasDn) {
                    finalPrice = this.mealPrices.all_meals > 0 ? this.mealPrices.all_meals : individualSum;
                } else if (hasBf && hasLn) {
                    finalPrice = this.mealPrices.bf_ln > 0 ? this.mealPrices.bf_ln : individualSum;
                } else if (hasLn && hasDn) {
                    finalPrice = this.mealPrices.ln_dn > 0 ? this.mealPrices.ln_dn : individualSum;
                } else if (hasBf && hasDn) {
                    finalPrice = this.mealPrices.bf_dn > 0 ? this.mealPrices.bf_dn : individualSum;
                }

                totalWithCombo += Number(finalPrice);
            });

            let discount = totalIndividual - totalWithCombo;

            return {
                subtotal: totalIndividual,
                finalTotal: totalWithCombo,
                discountAmount: discount > 0 ? discount : 0
            };
        },

        banks: [
            @if(!empty($trip->bank_accounts) && is_array($trip->bank_accounts))
            @foreach($trip->bank_accounts as $bank) {
                name: '{{ $bank['bank_name'] ?? '' }}',
                logo: '🏦',
                iban: '{{ $bank['iban'] ?? '' }}',
                acc: '{{ $bank['account_number'] ?? '' }}',
                qr: '{{ !empty($bank['qr_code']) ? asset($bank['qr_code']) : '' }}'
            },
            @endforeach
            @elseif(!empty($trip->agency->bank_name)) {
                name: '{{ $trip->agency->bank_name }}',
                logo: '🏦',
                iban: '{{ $trip->agency->iban }}',
                acc: '{{ $trip->agency->account_number ?? '' }}',
                qr: ''
            }
            @endif
        ],
        copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            alert('تم نسخ النص بنجاح ✅');
        }
    }" x-init="if (banks.length > 0) { selectedBank = banks[0]; }">

        <!-- 🔙 زر الرجوع لتعديل البيانات والمقاعد دون فقدان الحفظ -->
        <div class="mb-6 flex justify-between items-center">
            <a href="{{ route('client.select_seat', $trip->id) }}"
                class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 px-4 py-2.5 rounded-xl shadow-sm transition-all">
                <span>➡️</span>
                <span>رجوع لتعديل البيانات والمقاعد</span>
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border-r-4 border-red-500 rounded-xl text-right animate-fade-in">
                <h4 class="font-black text-red-800 text-sm mb-2">تنبيه: تعذر إتمام الطلب بسبب الأخطاء التالية:</h4>
                <ul class="list-disc list-inside text-xs text-red-700 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 p-4 bg-red-50 border-r-4 border-red-500 rounded-xl text-right text-sm font-bold text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('client.booking.store') }}" method="POST" enctype="multipart/form-data">
            {{-- @submit="sessionStorage.removeItem('booking_draft_{{ $trip->id }}')"> --}}
            @csrf

            <input type="hidden" name="trip_id" value="{{ $trip->id }}">
            <input type="hidden" name="booking_id" value="{{ $booking_id }}">

            <input type="hidden" name="room_bed_types" value="{{ $room_bed_types_json }}">
            <input type="hidden" name="agency_note" value="{{ $agency_note }}">

            <input type="hidden" name="payment_method" :value="paymentMethod">
            <input type="hidden" name="bank_name" :value="selectedBank?.name">

            <input type="hidden" name="selected_seats"
                value="{{ is_array($selected_seats) ? json_encode($selected_seats) : $selected_seats }}">
            <input type="hidden" name="seats"
                value="{{ is_array($selected_seats) ? implode(',', $selected_seats) : $selected_seats }}">
            <input type="hidden" name="passenger_names"
                value="{{ is_array($passenger_names) ? json_encode($passenger_names) : $passenger_names }}">
            <input type="hidden" name="passenger_ids"
                value="{{ is_array($passenger_ids) ? json_encode($passenger_ids) : $passenger_ids }}">
            <input type="hidden" name="passenger_genders"
                value="{{ is_array($passenger_genders) ? json_encode($passenger_genders) : $passenger_genders }}">

            <input type="hidden" name="room_quantities"
                value="{{ is_array($roomQuantitiesArray) ? json_encode($roomQuantitiesArray) : $room_assignments_json }}">
            <input type="hidden" name="room_assignments"
                value="{{ is_array($room_assignments_json) ? json_encode($room_assignments_json) : $room_assignments_json }}">
            <input type="hidden" name="passenger_meals" :value="JSON.stringify(passengerMeals)"
                value="{{ is_array($passenger_meals_json) ? json_encode($passenger_meals_json) : $passenger_meals_json }}">

            <input type="hidden" name="passenger_services" :value="JSON.stringify(passengerServices)"
                value="{{ is_array($passenger_services_json) ? json_encode($passenger_services_json) : $passenger_services_json }}">

            <input type="hidden" name="room_type"
                value="{{ !empty($roomQuantitiesArray) ? implode(', ', array_keys(array_filter($roomQuantitiesArray, fn($qty) => $qty > 0))) : 'shared' }}">
            <input type="hidden" name="room_count" value="{{ array_sum($roomQuantitiesArray ?? []) }}">

            <div class="flex flex-col lg:flex-row gap-10">

                <!-- القسم الأيسر: طرق الدفع وبيانات التحويل -->
                <div class="lg:col-span-2 flex-1 space-y-6">
                    <div class="text-right mb-6">
                        {{-- شارات تفاصيل الوكالة --}}
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            @if (!empty($trip->agency->commercial_name))
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs md:text-sm font-bold text-blue-900 bg-blue-50/80 border border-blue-100 px-3.5 py-1.5 rounded-full shadow-sm">
                                    🏢 {{ $trip->agency->commercial_name }}
                                </span>
                            @endif

                            @if (!empty($trip->agency->name))
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs md:text-sm font-bold text-slate-700 bg-slate-100/80 border border-slate-200/60 px-3.5 py-1.5 rounded-full shadow-sm">
                                    👤 صاحب الوكالة: {{ $trip->agency->name }}
                                </span>
                            @endif
                        </div>

                        {{-- العنوان الرئيسي --}}
                        <h2
                            class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight mb-2 flex items-center gap-2">
                            <span>إتمام عملية الحجز</span>
                            <span>💳</span>
                        </h2>

                        {{-- اسم الرحلة --}}
                        <p class="text-sm md:text-base text-slate-500 font-medium flex items-center gap-1.5">
                            <span class="text-slate-400">رحلة:</span>
                            <span
                                class="font-bold text-slate-800">{{ $trip->title ?? ($trip->name ?? 'غير محددة') }}</span>
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-4 mb-10">
                        <button type="button" @click="paymentMethod = 'card'"
                            :class="paymentMethod === 'card' ?
                                'border-blue-900 bg-blue-900/10 shadow-md ring-2 ring-blue-900/20' :
                                'border-slate-200 bg-white hover:bg-slate-50'"
                            class="p-6 rounded-[2rem] border-2 transition-all flex flex-col items-center gap-3">
                            <span class="text-2xl">💳</span>
                            <span class="text-[10px] font-black text-slate-700">بطاقة مدى / فيزا</span>
                        </button>
                        <button type="button" @click="paymentMethod = 'tabby'"
                            :class="paymentMethod === 'tabby' ?
                                'border-teal-500 bg-teal-50/80 shadow-md ring-2 ring-teal-500/20' :
                                'border-slate-200 bg-white hover:bg-slate-50'"
                            class="p-6 rounded-[2rem] border-2 transition-all flex flex-col items-center gap-3">
                            <span class="text-2xl">💚</span>
                            <span class="text-[10px] font-black text-slate-700">تابي / تمارا</span>
                        </button>
                        <button type="button" @click="paymentMethod = 'bank'"
                            :class="paymentMethod === 'bank' ?
                                'border-blue-900 bg-blue-900/10 shadow-md ring-2 ring-blue-900/20' :
                                'border-slate-200 bg-white hover:bg-slate-50'"
                            class="p-6 rounded-[2rem] border-2 transition-all flex flex-col items-center gap-3">
                            <span class="text-2xl">🏦</span>
                            <span class="text-[10px] font-black text-slate-700 text-center">تحويل بنكي مباشر</span>
                        </button>
                    </div>

                    <div class="bg-white rounded-[2.5rem] p-8 border border-slate-200 shadow-sm min-h-[400px]">
                        <!-- قسم البطاقة البنكية / مدى -->
                        <div x-show="paymentMethod === 'card'" class="space-y-6 animate-fade-in text-right">
                            <h4 class="font-black text-slate-800">الدفع عبر بطاقة مدى / الفيزا / Apple Pay</h4>
                            <div
                                class="flex flex-col items-center justify-center p-8 bg-blue-50/50 rounded-3xl border-2 border-dashed border-blue-200 text-center space-y-3">
                                <div
                                    class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-2xl shadow-sm">
                                    💳</div>
                                <div>
                                    <p class="font-black text-blue-900 text-sm">سيتم تحويلك لصفحة الدفع الآمنة عند تأكيد
                                        الحجز</p>
                                    <p class="text-xs text-slate-500 mt-1">ندعم بطاقات مدى، Visa، Mastercard و Apple Pay</p>
                                </div>
                            </div>
                        </div>

                        <div x-show="paymentMethod === 'tabby'" class="text-center py-10 space-y-6 animate-fade-in">
                            <div class="inline-block bg-teal-100 text-teal-700 px-6 py-2 rounded-full font-black text-xs">
                                قسّمها على 4 دفعات</div>
                            <p class="text-slate-500 font-medium italic">قريباً عبر بوابة الدفع...</p>
                            <div class="flex justify-center gap-6 text-5xl opacity-40">📱 🛡️ ✨</div>
                        </div>

                        <!-- قسم الحساب البنكي -->
                        <div x-show="paymentMethod === 'bank'" class="space-y-8 animate-fade-in text-right">
                            <template x-if="banks.length === 0">
                                <div
                                    class="p-6 bg-slate-100 border border-slate-200 rounded-[1.5rem] text-center text-slate-700 font-bold text-xs">
                                    ⚠️ عذراً، لا توجد حسابات بنكية معتمدة للوكالة حالياً.
                                </div>
                            </template>

                            <template x-if="banks.length > 0">
                                <div class="space-y-8">
                                    <div>
                                        <label
                                            class="text-[11px] text-slate-500 font-black mb-4 block tracking-wide uppercase">1.
                                            اختر البنك الذي ستحول إليه لتظهر معلومات الحساب:</label>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <template x-for="bank in banks">
                                                <button type="button" @click="selectedBank = bank"
                                                    :class="selectedBank?.name === bank.name ?
                                                        'border-blue-900 bg-blue-900/10 ring-2 ring-blue-900/20' :
                                                        'border-slate-200 bg-white hover:border-blue-300'"
                                                    class="p-4 border-2 rounded-2xl transition-all flex flex-col items-center gap-2">
                                                    <span class="text-2xl" x-text="bank.logo"></span>
                                                    <span class="text-xs font-black text-slate-800 text-center"
                                                        x-text="bank.name"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- بطاقة عرض بيانات الحساب البنكي -->
                                    <div x-show="selectedBank" x-transition
                                        class="bg-slate-50/80 rounded-[2rem] p-8 border-2 border-slate-200/90 text-slate-800 relative overflow-hidden shadow-sm">
                                        <h5
                                            class="text-slate-800 text-xs font-black mb-6 flex items-center gap-2 uppercase tracking-wide">
                                            <span class="w-2.5 h-2.5 bg-blue-900 rounded-full animate-ping"></span> بيانات
                                            الحساب البنكي المعتمد للتحويل
                                        </h5>
                                        <div class="space-y-5 relative z-10">
                                            <div class="flex flex-col gap-1.5">
                                                <span class="text-[10px] text-slate-500 font-black uppercase">البنك</span>
                                                <div class="text-sm font-black text-slate-900 bg-white p-4 rounded-xl border border-slate-200 shadow-sm"
                                                    x-text="selectedBank?.name"></div>
                                            </div>
                                            <div class="flex flex-col gap-1.5">
                                                <span class="text-[10px] text-slate-500 font-black uppercase">اسم
                                                    المستفيد</span>
                                                <div
                                                    class="text-sm font-black text-slate-900 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                                                    {{ $trip->agency->name ?? 'وكالة السفر' }}</div>
                                            </div>
                                            <div class="flex flex-col gap-1.5">
                                                <span class="text-[10px] text-slate-500 font-black uppercase">رقم الحساب
                                                    (Account Number)</span>
                                                <div
                                                    class="flex items-center justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-sm group">
                                                    <code
                                                        class="text-sm font-mono text-slate-900 font-bold ltr tracking-wider"
                                                        x-text="selectedBank?.acc"></code>
                                                    <button type="button" @click="copyToClipboard(selectedBank.acc)"
                                                        class="text-xs bg-blue-900 hover:bg-blue-950 text-white px-3.5 py-1.5 rounded-lg transition-colors font-bold shrink-0 shadow-sm">نسخ</button>
                                                </div>
                                            </div>
                                            <div class="flex flex-col gap-1.5">
                                                <span class="text-[10px] text-slate-500 font-black uppercase">رقم الآيبان
                                                    (IBAN)</span>
                                                <div
                                                    class="flex items-center justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-sm group">
                                                    <code
                                                        class="text-sm font-mono text-slate-900 font-bold ltr tracking-wider"
                                                        x-text="selectedBank?.iban"></code>
                                                    <button type="button" @click="copyToClipboard(selectedBank.iban)"
                                                        class="text-xs bg-blue-900 hover:bg-blue-950 text-white px-3.5 py-1.5 rounded-lg transition-colors font-bold shrink-0 shadow-sm">نسخ</button>
                                                </div>
                                            </div>

                                            <template x-if="selectedBank?.qr">
                                                <div
                                                    class="flex flex-col gap-2 items-center text-center bg-white p-4 rounded-xl border border-slate-200 shadow-sm mt-4">
                                                    <span
                                                        class="text-[10px] text-slate-500 font-black w-full text-right">رمز
                                                        الـ QR المباشر للمسح السريع:</span>
                                                    <div
                                                        class="bg-slate-50 p-3 rounded-2xl border border-slate-200 inline-block mt-1">
                                                        <img :src="selectedBank.qr" alt="QR Code"
                                                            class="w-32 h-32 object-contain mx-auto">
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <div x-show="selectedBank" x-transition class="animate-fade-in">
                                        <label
                                            class="text-[11px] text-slate-500 font-black mb-4 block tracking-wide uppercase">2.
                                            اختر طريقة تأكيد الحجز وإرسال الإيصال:</label>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div
                                                class="border-2 border-dashed border-slate-200 rounded-[2rem] p-8 text-center hover:border-blue-900 transition-colors cursor-pointer relative bg-slate-50/50 flex flex-col items-center justify-center min-h-[160px] group">
                                                <input type="file" name="bank_receipt"
                                                    accept="image/jpeg,image/png,image/jpg,image/webp,application/pdf,.jpeg,.jpg,.png,.webp,.pdf"
                                                    class="absolute inset-0 opacity-0 cursor-pointer"
                                                    @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''">
                                                <div class="space-y-3 pointer-events-none">
                                                    <span
                                                        class="text-4xl group-hover:scale-110 transition-transform duration-200 inline-block"
                                                        x-text="fileName ? '📄' : '📸'"></span>
                                                    <div class="space-y-1">
                                                        <p class="text-xs font-black text-slate-700 tracking-tight"
                                                            x-text="fileName ? fileName : 'الرفع المباشر في الموقع'"></p>
                                                        <p class="text-[10px] text-slate-400 font-medium"
                                                            x-show="!fileName">اضغط هنا لإرفاق صورة الإيصال (JPG, PNG, WEBP
                                                            أو PDF)</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <a href="https://wa.me/{{ $agency_whatsapp }}?text={{ urlencode('مرحباً، قمت بالتحويل البنكي لرحلة: ' . ($trip->title ?? $trip->name) . ' وأود إرسال صورة الإيصال لتأكيد حجزي.') }}"
                                                target="_blank"
                                                class="border-2 border-dashed border-emerald-300 bg-emerald-50/40 rounded-[2rem] p-8 text-center hover:border-emerald-500 hover:bg-emerald-50/80 transition-all cursor-pointer group flex flex-col items-center justify-center min-h-[160px]">
                                                <div class="space-y-3">
                                                    <span
                                                        class="text-4xl group-hover:scale-110 transition-transform duration-200 inline-block">💬</span>
                                                    <div class="space-y-1">
                                                        <p class="text-xs font-black text-slate-800 tracking-tight">إرسال
                                                            الإيصال عبر الواتساب</p>
                                                        <p class="text-[10px] text-emerald-600 font-bold">المراسلة الفورية
                                                            مع الوكالة 🚀</p>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- 🚀 زر التأكيد الرئيسي -->
                        <div class="mt-8 space-y-3">
                            <button type="submit" x-show="paymentMethod !== 'tabby'"
                                :disabled="paymentMethod === 'bank' && (!selectedBank || !fileName)"
                                :class="(paymentMethod === 'bank' && (!selectedBank || !fileName)) ?
                                'bg-slate-300 cursor-not-allowed opacity-70 shadow-none' :
                                'bg-blue-900 hover:bg-blue-950 shadow-lg hover:shadow-xl cursor-pointer'"
                                class="w-full py-5 text-white font-black rounded-[1.5rem] transition-all flex items-center justify-center gap-3 text-base">

                                <span
                                    x-text="paymentMethod === 'card'
            ? 'الانتقال لصفحة الدفع الفوري'
            : 'إتمام الحجز وتأكيد الطلب'"></span>
                                <span class="text-xl">🚀</span>
                            </button>

                            <template x-if="paymentMethod === 'bank' && (!selectedBank || !fileName)">
                                <p
                                    class="text-center text-xs font-bold text-amber-600 bg-amber-50 p-3 rounded-xl border border-amber-200 animate-fade-in">
                                    ⚠️ يرجى اختيار البنك وإرفاق صورة إيصال التحويل أولاً لتتمكن من إتمام الحجز.
                                </p>
                            </template>

                            <template x-if="paymentMethod === 'tabby'">
                                <div class="p-4 bg-slate-100 rounded-2xl text-center text-xs font-bold text-slate-500">
                                    ⏳ خدمة التقسيط عبر تابي وقريباً ستكون متوفرة، يرجى اختيار طريقة دفع أخرى حالياً.
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- 🧾 قسم الفاتورة الجانبي -->
                <div class="lg:w-96 space-y-6 text-right">
                    <div
                        class="bg-white rounded-[2.5rem] p-8 text-slate-800 border border-slate-200 shadow-xl overflow-hidden relative">
                        <div class="flex items-center justify-between mb-8 border-b border-slate-100 pb-4">
                            <h3 class="text-xl font-black text-slate-900 tracking-tight">فاتورة الحجز</h3>
                            <span class="text-2xl">🧾</span>
                        </div>

                        <div class="space-y-4 relative z-10">
                            <span class="text-slate-500 font-black text-[10px] block uppercase tracking-wider">الغرف
                                المحجوزة:</span>

                            @php
                                $roomTypesMap = [
                                    'single' => 'فردية',
                                    'double' => 'ثنائية',
                                    'triple' => 'ثلاثية',
                                    'quad' => 'رباعية',
                                    'quint' => 'خماسية',
                                ];
                            @endphp

                            @foreach ($roomQuantitiesArray as $type => $qty)
                                @if ($qty > 0)
                                    <div
                                        class="flex justify-between items-center text-xs bg-slate-50 border border-slate-100 p-3 rounded-xl mb-1 text-slate-700">
                                        <span class="font-medium">
                                            غرفة {{ $roomTypesMap[$type] ?? $type }}:
                                        </span>
                                        <span class="font-black text-blue-900">{{ $qty }} غرف
                                            ({{ number_format($roomTypeCostBreakdown[$type] ?? ($room_prices[$type] ?? 0) * $qty, 2) }}
                                            ر.س)</span>
                                    </div>
                                @endif
                            @endforeach

                            <div class="flex justify-between items-center text-sm pt-3 border-t border-slate-100">
                                <div class="flex flex-col">
                                    <span class="text-slate-600 font-bold">إجمالي المقاعد
                                        ({{ $totalNonHSeatsCount }}):</span>
                                    <span class="text-[10px] text-slate-400">
                                        ({{ $coveredSeatsCount }} مشمول بالسكن | {{ $totalExtraSeatsCount }} إضافي)
                                    </span>
                                </div>
                                <span class="font-black text-slate-900">{{ number_format($calcSeatsCost, 2) }} ر.س</span>
                            </div>

                            @if ($calcServicesCost > 0)
                                <div class="flex justify-between items-center text-sm pt-2 border-t border-slate-100">
                                    <span class="text-slate-600 font-bold">إجمالي الخدمات الإضافية:</span>
                                    <span class="font-black text-purple-700">{{ number_format($calcServicesCost, 2) }}
                                        ر.س</span>
                                </div>
                            @endif

                            @if ($totalAccommodationDiscountView > 0)
                                <div
                                    class="flex justify-between items-center text-xs text-emerald-700 font-bold bg-emerald-50 p-2.5 rounded-xl border border-emerald-200/60">
                                    <span>🏨 خصم حجز السكن فقط:</span>
                                    <span>- {{ number_format($totalAccommodationDiscountView, 2) }} ر.س</span>
                                </div>
                            @endif

                            <div class="flex justify-between items-center text-sm pt-2 border-t border-slate-100"
                                x-show="calculateMeals().finalTotal > 0">
                                <span class="text-slate-600 font-bold">إجمالي وجبات وباقات الطعام:</span>
                                <span class="font-black text-emerald-600"
                                    x-text="calculateMeals().finalTotal + ' ر.س'"></span>
                            </div>

                            <div class="flex justify-between items-center text-xs text-emerald-700 font-bold bg-emerald-50 p-2.5 rounded-xl border border-emerald-200/60"
                                x-show="calculateMeals().discountAmount > 0">
                                <span>✨ وفرت خصم باقات الطعام الذكية:</span>
                                <span x-text="'- ' + calculateMeals().discountAmount + ' ر.س'"></span>
                            </div>

                            <div
                                class="flex justify-between items-center text-base pt-4 border-t-2 border-slate-200 mt-4 bg-blue-50/60 p-4 rounded-2xl border border-blue-100">
                                <span class="font-black text-slate-900">المبلغ الإجمالي النهائي:</span>
                                <span class="text-xl font-black text-blue-900"
                                    x-text="(parseFloat({{ $baseTotalWithoutMeals }}) + Number(calculateMeals().finalTotal)).toFixed(2) + ' ر.س'"></span>
                            </div>

                            <div class="flex flex-col gap-3 text-xs pt-4 border-t border-slate-100 mt-4">
                                <span class="font-black text-[11px] text-slate-500 mb-1 uppercase tracking-wide">
                                    تفاصيل الركاب والتوزيعات:
                                </span>

                                @foreach ($groupedPassengers as $passenger)
                                    @php
                                        $passengerUid = $passenger['uid'] ?? '';

                                        // المقاعد الحقيقية فقط (بدون H وبدون السالبة) - values() لإعادة الترقيم
                                        $realSeatsForPassenger = collect($passenger['raw_seats'] ?? [])
                                            ->reject(
                                                fn($seatKey) => $isHSeat($seatKey) ||
                                                    (is_numeric($seatKey) && floatval($seatKey) < 0),
                                            )
                                            ->values();

                                        // مسافر سكن فقط = عنده مقعد سكن (H) وما عنده أي مقعد حافلة حقيقي
                                        $hasHSeat = collect($passenger['raw_seats'] ?? [])->contains(
                                            fn($k) => $isHSeat($k),
                                        );
                                        $isAccomOnlyPassenger = $hasHSeat && $realSeatsForPassenger->isEmpty();

                                        $displayId = $passenger['is_dummy_id']
                                            ? ($isAccomOnlyPassenger
                                                ? 'سكن فقط'
                                                : 'مقعد إضافي')
                                            : $passenger['id'];
                                        $extraSeatsCountForPassenger = max(
                                            0,
                                            count($passenger['seat_labels'] ?? []) - 1,
                                        );

                                        $roomTypeMap = [
                                            'single' => 'غرفة خاصة (فردية) 👤',
                                            'double' => 'غرفة ثنائية 👥',
                                            'triple' => 'غرفة ثلاثية 👥👤',
                                            'quad' => 'غرفة رباعية 👥👥',
                                            '1' => 'غرفة خاصة (فردية) 👤',
                                            '2' => 'غرفة ثنائية 👥',
                                            '3' => 'غرفة ثلاثية 👥👤',
                                            '4' => 'غرفة رباعية 👥👥',
                                            'غرفة خاصة' => 'غرفة خاصة (فردية) 👤',
                                            'خاصة' => 'غرفة خاصة (فردية) 👤',
                                            'فردية' => 'غرفة خاصة (فردية) 👤',
                                            'ثنائية' => 'غرفة ثنائية 👥',
                                            'ثلاثية' => 'غرفة ثلاثية 👥👤',
                                            'رباعية' => 'غرفة رباعية 👥👥',
                                        ];

                                        $firstRealSeat = $realSeatsForPassenger->first();
                                        $passengerRoomName = null;

                                        if ($firstRealSeat !== null) {
                                            $passengerRoomName =
                                                $seatRoomMapping[$firstRealSeat] ??
                                                ($seatRoomMapping[(string) $firstRealSeat] ?? null);
                                        }

                                        if (!$passengerRoomName && $passengerUid) {
                                            $passengerRoomName = $seatRoomMapping[$passengerUid] ?? null;
                                        }

                                        if (
                                            $passengerRoomName &&
                                            (str_contains($passengerRoomName, 'إضافي') ||
                                                str_contains($passengerRoomName, 'زائد'))
                                        ) {
                                            $passengerRoomName = null;
                                        }

                                        // لا نمرر القيمة القادمة من $seatRoomMapping على $roomTypeMap
                                        // لأنها جملة جاهزة للعرض مثل "غرفة مشتركة (ثنائية)".
                                        // $roomTypeMap مسار احتياطي أخير فقط.
                                        if (!$passengerRoomName) {
                                            $legacyRoomType =
                                                $passenger['room_type_name'] ??
                                                ($passenger['room_type'] ??
                                                    ($passenger['room_type_label'] ??
                                                        ($passenger['room_name'] ?? null)));
                                            $normalizedKey = strtolower(trim((string) $legacyRoomType));
                                            $passengerRoomName =
                                                $roomTypeMap[$normalizedKey] ??
                                                ($legacyRoomType ?: 'غرفة خاصة (فردية) 👤');
                                        }

                                        $passengerAllServices = [];
                                        foreach ($passenger['raw_seats'] ?? [] as $sKey) {
                                            $srvs =
                                                $passengerServicesArray[$sKey] ??
                                                ($passengerServicesArray[(string) $sKey] ?? []);
                                            if (!empty($srvs)) {
                                                $passengerAllServices[$sKey] = (array) $srvs;
                                            }
                                        }
                                        if (
                                            empty($passengerAllServices) &&
                                            $passengerUid &&
                                            !empty($passengerServicesArray[$passengerUid])
                                        ) {
                                            $passengerAllServices[$passengerUid] =
                                                (array) $passengerServicesArray[$passengerUid];
                                        }

                                        $passengerAllMeals = [];
                                        foreach ($passenger['raw_seats'] ?? [] as $sKey) {
                                            $mealData =
                                                $passengerMealsArray[$sKey] ??
                                                ($passengerMealsArray[(string) $sKey] ?? null);
                                            if ($mealData && !empty($mealData['wants_meal'])) {
                                                $passengerAllMeals[$sKey] = $mealData;
                                            }
                                        }
                                        if (empty($passengerAllMeals) && $passengerUid) {
                                            $mealData = $passengerMealsArray[$passengerUid] ?? null;
                                            if ($mealData && !empty($mealData['wants_meal'])) {
                                                $passengerAllMeals[$passengerUid] = $mealData;
                                            }
                                        }
                                    @endphp

                                    <div
                                        class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3 shadow-sm">
                                        <div class="flex justify-between items-start">
                                            <div class="flex flex-col gap-1">
                                                <span
                                                    class="text-slate-900 font-black text-xs flex items-center gap-1.5 flex-wrap">
                                                    <span>{{ $isAccomOnlyPassenger ? '🛏️ نوع الحجز:' : '🪑 المقاعد:' }}</span>
                                                    <span
                                                        class="bg-blue-100 text-blue-900 px-2 py-0.5 rounded-lg border border-blue-200 font-mono text-[11px] font-bold">
                                                        @if ($isAccomOnlyPassenger)
                                                            🏨 سكن فقط
                                                        @else
                                                            {{ implode('، ', $passenger['seat_labels'] ?? []) }}
                                                        @endif
                                                    </span>

                                                    <span class="text-[11px] font-normal text-slate-500">
                                                        @if (($passenger['gender'] ?? '') === 'female')
                                                            (أنثى 👩)
                                                        @elseif (($passenger['gender'] ?? '') === 'male')
                                                            (ذكر 👨)
                                                        @else
                                                            (غير محدد)
                                                        @endif
                                                    </span>
                                                </span>

                                                @if ($extraSeatsCountForPassenger > 0)
                                                    <span
                                                        class="text-[10px] text-amber-800 font-bold bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200 inline-block w-fit">
                                                        ➕ يتضمن {{ $extraSeatsCountForPassenger }} مقعد إضافي
                                                    </span>
                                                @endif
                                            </div>

                                            <code
                                                class="text-blue-900 bg-white px-2 py-0.5 rounded border border-slate-200 font-mono text-[10px] font-bold shrink-0">
                                                {{ $displayId }}
                                            </code>
                                        </div>

                                        <div class="text-[11px] text-slate-700 font-medium">
                                            الاسم: <span
                                                class="font-bold text-slate-900">{{ !empty($passenger['name']) ? $passenger['name'] : 'لم يسجل اسم بعد' }}</span>
                                        </div>

                                        @if ($realSeatsForPassenger->isNotEmpty())
                                            <div
                                                class="text-[10px] text-slate-500 flex flex-col gap-1 bg-white p-2.5 rounded-xl border border-slate-200/60">
                                                <span class="font-bold text-slate-700">🏢 توزيع السكن:</span>

                                                @foreach ($realSeatsForPassenger as $seatKey)
                                                    @php
                                                        $cleanSeatNum = trim((string) $seatKey);
                                                        $isMainSeat = $loop->first;

                                                        if ($isMainSeat) {
                                                            $mappedRoom =
                                                                $seatRoomMapping[$seatKey] ??
                                                                ($seatRoomMapping[(string) $seatKey] ??
                                                                    ($passengerUid
                                                                        ? $seatRoomMapping[$passengerUid] ?? null
                                                                        : null));

                                                            if (
                                                                $mappedRoom &&
                                                                !str_contains($mappedRoom, 'إضافي') &&
                                                                !str_contains($mappedRoom, 'زائد')
                                                            ) {
                                                                $cleanMapped = strtolower(trim((string) $mappedRoom));
                                                                if ($cleanMapped === 'shared') {
                                                                    $displayRoom = $defaultTypeArabic
                                                                        ? "غرفة مشتركة ({$defaultTypeArabic})"
                                                                        : 'غرفة مشتركة';
                                                                } elseif (
                                                                    str_contains($cleanMapped, 'shared') &&
                                                                    !str_contains($cleanMapped, 'غرفة')
                                                                ) {
                                                                    $foundType = null;
                                                                    foreach ($typeArabicMap as $tKey => $tName) {
                                                                        if (
                                                                            str_contains($cleanMapped, $tKey) ||
                                                                            str_contains($cleanMapped, $tName)
                                                                        ) {
                                                                            $foundType = $tName;
                                                                            break;
                                                                        }
                                                                    }
                                                                    $typeName = $foundType ?: $defaultTypeArabic;
                                                                    $displayRoom = $typeName
                                                                        ? "غرفة مشتركة ({$typeName})"
                                                                        : 'غرفة مشتركة';
                                                                } else {
                                                                    $displayRoom = $mappedRoom;
                                                                }
                                                            } else {
                                                                $displayRoom = $passengerRoomName;
                                                            }
                                                        } else {
                                                            $displayRoom = 'مقعد إضافي (وسيلة نقل فقط)';
                                                        }
                                                    @endphp

                                                    <div
                                                        class="flex items-center justify-between text-slate-800 font-medium pr-2 border-b border-slate-100 last:border-none pb-1 last:pb-0">
                                                        <span>مقعد {{ $cleanSeatNum }}:</span>
                                                        <span
                                                            class="{{ $isMainSeat ? 'font-bold text-blue-900' : 'font-bold text-amber-600' }}">
                                                            {{ $displayRoom }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        {{-- 🏨 مسافر السكن فقط: عرض غرفته بدون أي مقعد وبدون كود H --}}
                                        @if ($isAccomOnlyPassenger)
                                            @php
                                                $accomKey = collect($passenger['raw_seats'] ?? [])->first(
                                                    fn($k) => $isHSeat($k),
                                                );
                                                $accomRoom =
                                                    $seatRoomMapping[$accomKey] ??
                                                    ($seatRoomMapping[(string) $accomKey] ?? 'غير محدد');
                                            @endphp
                                            <div
                                                class="text-[10px] text-slate-500 flex flex-col gap-1 bg-white p-2.5 rounded-xl border border-slate-200/60">
                                                <span class="font-bold text-slate-700">🏢 توزيع السكن:</span>
                                                <div
                                                    class="flex items-center justify-between text-slate-800 font-medium pr-2">
                                                    <span>🏨 سكن فقط (بدون مقعد حافلة):</span>
                                                    <span class="font-bold text-blue-900">{{ $accomRoom }}</span>
                                                </div>
                                            </div>
                                        @endif

                                        @if (!empty($passengerAllServices))
                                            @php
                                                $realSeatStrings = $realSeatsForPassenger
                                                    ->map(fn($s) => (string) $s)
                                                    ->all();
                                            @endphp
                                            <div class="pt-2 border-t border-slate-200/60 text-[10px]">
                                                <div class="text-purple-900 font-bold mb-1.5 flex items-center gap-1">
                                                    <span>✨ الخدمات الإضافية المحجوزة:</span>
                                                </div>
                                                <div class="flex flex-col gap-1.5">
                                                    @foreach ($passengerAllServices as $sKey => $servicesList)
                                                        @foreach ($servicesList as $srv)
                                                            @php
                                                                $srvName = is_array($srv)
                                                                    ? $srv['name'] ?? ($srv['title'] ?? 'خدمة إضافية')
                                                                    : $srv;
                                                                $srvDesc = is_array($srv)
                                                                    ? $srv['description'] ?? ($srv['desc'] ?? null)
                                                                    : null;
                                                                $srvPrice = is_array($srv)
                                                                    ? $srv['price'] ?? ($srv['cost'] ?? null)
                                                                    : null;

                                                                // وسم المقعد: يظهر فقط للمقاعد الحقيقية، ولا يظهر أبداً لمسافر السكن فقط
                                                                if ($isAccomOnlyPassenger || $isHSeat($sKey)) {
                                                                    $seatTag = null;
                                                                } elseif (
                                                                    in_array((string) $sKey, $realSeatStrings, true)
                                                                ) {
                                                                    $seatTag =
                                                                        'مقعد ' .
                                                                        trim(str_replace('مقعد', '', (string) $sKey));
                                                                } else {
                                                                    $seatTag = 'مقعد إضافي';
                                                                }
                                                            @endphp

                                                            <div
                                                                class="bg-purple-50/80 border border-purple-200 p-2 rounded-lg text-[10px]">
                                                                <div
                                                                    class="flex justify-between items-center font-bold text-purple-950">
                                                                    <span class="flex items-center gap-1">
                                                                        <span
                                                                            class="w-1.5 h-1.5 rounded-full bg-purple-600 inline-block"></span>
                                                                        {{ $srvName }}
                                                                        @if ($seatTag)
                                                                            <span
                                                                                class="text-[9px] text-purple-700 font-normal">({{ $seatTag }})</span>
                                                                        @endif
                                                                    </span>
                                                                    @if ($srvPrice !== null && is_numeric($srvPrice))
                                                                        <span
                                                                            class="text-purple-700 font-black text-[11px] bg-purple-100 px-1.5 py-0.5 rounded">
                                                                            +{{ number_format((float) $srvPrice, 2) }} ر.س
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                                @if (!empty($srvDesc))
                                                                    <p
                                                                        class="text-slate-500 text-[9px] mt-1 pr-2.5 leading-relaxed">
                                                                        {{ $srvDesc }}</p>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <div class="pt-2 border-t border-slate-200/60 text-[10px]">
                                            @if (!empty($passengerAllMeals))
                                                @foreach ($passengerAllMeals as $sKey => $mealInfo)
                                                    @php
                                                        $realSeatStrings = $realSeatsForPassenger
                                                            ->map(fn($s) => (string) $s)
                                                            ->all();
                                                        if ($isAccomOnlyPassenger || $isHSeat($sKey)) {
                                                            $seatTag = null;
                                                        } elseif (in_array((string) $sKey, $realSeatStrings, true)) {
                                                            $seatTag =
                                                                'مقعد ' . trim(str_replace('مقعد', '', (string) $sKey));
                                                        } else {
                                                            $seatTag = 'مقعد إضافي';
                                                        }
                                                    @endphp
                                                    <div class="text-slate-700 font-bold mb-1">
                                                        {{ $seatTag ? "وجبات {$seatTag}:" : 'الوجبات:' }}</div>
                                                    <div class="flex flex-wrap gap-1 mb-1">
                                                        @if (!empty($mealInfo['package_name']))
                                                            <span
                                                                class="bg-blue-100 text-blue-900 border border-blue-200 px-2 py-0.5 rounded text-[9px] font-black">
                                                                📦 باقة: {{ $mealInfo['package_name'] }}
                                                            </span>
                                                        @elseif (!empty($mealInfo['breakfast']) && !empty($mealInfo['lunch']) && !empty($mealInfo['dinner']))
                                                            <span
                                                                class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded text-[9px] font-black">
                                                                ✨ باقة الوجبات الكاملة
                                                            </span>
                                                        @elseif (
                                                            (!empty($mealInfo['breakfast']) && !empty($mealInfo['lunch'])) ||
                                                                (!empty($mealInfo['lunch']) && !empty($mealInfo['dinner'])) ||
                                                                (!empty($mealInfo['breakfast']) && !empty($mealInfo['dinner'])))
                                                            <span
                                                                class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded text-[9px] font-black">
                                                                ✨ باقة دمج وجبات
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <div class="flex flex-wrap gap-1 mt-1 mb-2">
                                                        @if (!empty($mealInfo['breakfast']))
                                                            <span
                                                                class="bg-slate-200 text-slate-700 px-1.5 py-0.5 rounded text-[9px] font-bold">🍳
                                                                إفطار</span>
                                                        @endif
                                                        @if (!empty($mealInfo['lunch']))
                                                            <span
                                                                class="bg-slate-200 text-slate-700 px-1.5 py-0.5 rounded text-[9px] font-bold">🍔
                                                                غداء</span>
                                                        @endif
                                                        @if (!empty($mealInfo['dinner']))
                                                            <span
                                                                class="bg-slate-200 text-slate-700 px-1.5 py-0.5 rounded text-[9px] font-bold">🥩
                                                                عشاء</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @else
                                                <span class="text-slate-400 italic block mt-1">لم يتم اختيار أي وجبات طعام
                                                    لهذا المسافر</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
@endsection
