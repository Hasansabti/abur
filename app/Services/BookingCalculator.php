<?php

namespace App\Services;

class BookingCalculator
{
    /**
     * الحسبة المالية الشاملة والمؤمنة للغرف والمقاعد والوجبات والخدمات الإضافية
     */
    public static function calculate($trip, $room_quantities, $selected_seats, $roomAssignments, $passenger_meals, $passenger_services = [], array $extras = [])
    {
        // 1. دالة معالجة وفك تشفير JSON والمصفوفات الممررة
        $parseJsonOrArray = function ($input) {
            if (is_array($input)) return $input;
            if (is_numeric($input)) return [$input];
            if (is_string($input)) {
                $trimmed = trim($input);
                while (is_string($trimmed) && $trimmed !== '') {
                    $decoded = json_decode($trimmed, true);
                    if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
                        if (is_array($decoded)) return $decoded;
                        $trimmed = is_string($decoded) ? trim($decoded) : '';
                    } else {
                        if (str_contains($trimmed, ',')) {
                            return array_map('trim', explode(',', $trimmed));
                        }
                        return [$trimmed];
                    }
                }
            }
            return [];
        };

        $selected_seats     = $parseJsonOrArray($selected_seats);
        $room_quantities    = $parseJsonOrArray($room_quantities);
        $roomAssignments    = $parseJsonOrArray($roomAssignments);
        $passenger_meals    = $parseJsonOrArray($passenger_meals);
        $passenger_services = $parseJsonOrArray($passenger_services);

        // 🔄 خريطة لتوحيد أنواع الغرف (تطابق مرن يضمن التعرف على جميع المسميات العربية والإنجليزية)
        $normalizeRoomType = function ($typeStr) {
            if (empty($typeStr)) return '';
            $t = mb_strtolower(trim((string) $typeStr));

            if (str_contains($t, 'sing') || str_contains($t, 'فرد')) return 'single';
            if (str_contains($t, 'doub') || str_contains($t, 'ثنائ') || str_contains($t, 'مزدوج')) return 'double';
            if (str_contains($t, 'trip') || str_contains($t, 'ثلاث')) return 'triple';
            if (str_contains($t, 'quad') || str_contains($t, 'رباع')) return 'quad';
            if (str_contains($t, 'quin') || str_contains($t, 'خماس')) return 'quint';

            return $t;
        };

        // 🔄 1.1 توحيد واستخراج كميات الغرف بمرونة
        $normalizedRoomQuantities = [];

        if (is_array($room_quantities)) {
            foreach ($room_quantities as $key => $val) {
                if (is_array($val) || is_object($val)) {
                    $valArr  = (array) $val;
                    $rawType = $valArr['type'] ?? $valArr['room_type'] ?? $valArr['type_name'] ?? $valArr['room'] ?? $valArr['name'] ?? (is_string($key) ? $key : '');
                    $type    = $normalizeRoomType($rawType);
                    $qty     = intval($valArr['quantity'] ?? $valArr['qty'] ?? $valArr['count'] ?? $valArr['num'] ?? 1);
                    if (!empty($type) && $qty > 0) {
                        $normalizedRoomQuantities[$type] = ($normalizedRoomQuantities[$type] ?? 0) + $qty;
                    }
                } elseif (is_numeric($val) && intval($val) > 0) {
                    $type = $normalizeRoomType($key);
                    if (!empty($type)) {
                        $normalizedRoomQuantities[$type] = ($normalizedRoomQuantities[$type] ?? 0) + intval($val);
                    }
                } elseif (is_string($val) && !empty($val)) {
                    $type = $normalizeRoomType(is_numeric($key) ? $val : $key);
                    if (!empty($type)) {
                        $normalizedRoomQuantities[$type] = ($normalizedRoomQuantities[$type] ?? 0) + 1;
                    }
                }
            }
        }

        // الاستعانة بـ $roomAssignments إذا كانت $room_quantities فارغة
        if (empty($normalizedRoomQuantities) && !empty($roomAssignments)) {
            foreach ($roomAssignments as $assignment) {
                if (is_array($assignment) || is_object($assignment)) {
                    $assignArr = (array) $assignment;
                    $rawType   = $assignArr['room_type'] ?? $assignArr['type'] ?? $assignArr['room'] ?? $assignArr['name'] ?? '';
                    $type      = $normalizeRoomType($rawType);
                    if (!empty($type)) {
                        $normalizedRoomQuantities[$type] = ($normalizedRoomQuantities[$type] ?? 0) + 1;
                    }
                } elseif (is_string($assignment)) {
                    $type = $normalizeRoomType($assignment);
                    if (!empty($type)) {
                        $normalizedRoomQuantities[$type] = ($normalizedRoomQuantities[$type] ?? 0) + 1;
                    }
                }
            }
        }

        // فك ترميز خيارات الغرف والوجبات والخدمات المتاحة
        $roomOptions  = is_array($trip->room_options) ? $trip->room_options : (json_decode($trip->room_options ?? '[]', true) ?: []);
        $mealOptions  = is_array($trip->meal_options) ? $trip->meal_options : (json_decode($trip->meal_options ?? '[]', true) ?: []);
        $tripServices = is_array($trip->additional_services ?? null)
            ? $trip->additional_services
            : (json_decode($trip->additional_services ?? '[]', true) ?: []);

        // 🏨 2. استخراج أسعار الغرف
        $getRoomPrice = function ($type) use ($roomOptions, $trip) {
            if (isset($roomOptions[$type])) {
                $val = $roomOptions[$type];
                if (is_array($val) || is_object($val)) {
                    $valArr = (array) $val;
                    if (isset($valArr['price']) && is_numeric($valArr['price'])) {
                        return floatval($valArr['price']);
                    }
                } elseif (is_numeric($val)) {
                    return floatval($val);
                }
            }

            if (is_array($roomOptions)) {
                foreach ($roomOptions as $opt) {
                    if (is_array($opt) || is_object($opt)) {
                        $optArr  = (array) $opt;
                        $optType = strtolower(trim($optArr['type'] ?? $optArr['room_type'] ?? $optArr['name'] ?? ''));
                        if ($optType === $type && isset($optArr['price']) && is_numeric($optArr['price'])) {
                            return floatval($optArr['price']);
                        }
                    }
                }
            }

            $colName = 'price_' . $type;
            if (isset($trip->$colName) && is_numeric($trip->$colName)) {
                return floatval($trip->$colName);
            }

            return 0;
        };

        $room_prices = [
            'single' => $getRoomPrice('single'),
            'double' => $getRoomPrice('double'),
            'triple' => $getRoomPrice('triple'),
            'quad'   => $getRoomPrice('quad'),
            'quint'  => $getRoomPrice('quint'),
        ];

        // 🛑 تصفية المقاعد المحددة الفعلية (استبعاد مقاعد السكن الوهمية H)
        $numericSeatsOnly = [];
        foreach ((array) $selected_seats as $seat) {
            $seatVal = null;
            if (is_array($seat) || is_object($seat)) {
                $seatArr = (array) $seat;
                $seatVal = $seatArr['seat'] ?? $seatArr['seat_number'] ?? $seatArr['number'] ?? $seatArr['id'] ?? null;
            } else {
                $seatVal = $seat;
            }

            if ($seatVal !== null && $seatVal !== '') {
                $strSeat = trim((string) $seatVal);
                if (!str_starts_with($strSeat, 'H')) {
                    $numericSeatsOnly[] = $strSeat;
                }
            }
        }

        // 💡 3. حسبة تكلفة السكن مباشرة من التسكين الفعلي لكل مقعد/مسافر   
        $roomCapacities = ['single' => 1, 'double' => 2, 'triple' => 3, 'quad' => 4, 'quint' => 5];
        $seat_base_price = floatval($trip->extra_seat_price ?? $trip->seat_price ?? $trip->price ?? 0);

        // تحويل roomAssignments (seat => label) إلى خريطة seat => نوع الغرفة المُطبّع
        $seatTypeMap = [];
        if (is_array($roomAssignments)) {
            foreach ($roomAssignments as $seatKey => $label) {
                $type = $normalizeRoomType($label);
                if (!empty($type) && isset($roomCapacities[$type])) {
                    $seatTypeMap[(string) $seatKey] = $type;
                }
            }
        }

        // 🎯 خصم "سكن فقط": يُطبَّق على غرفة كل مسافر (H) على حدة، بغض النظر عن وجود مقاعد حقيقية معه في نفس الحجز
        // ✅ حساب تكلفة السكن: كل مقعد له نوع غرفة صالح (سواء H أو مقعد نقل عادي) يُضاف سعره الكامل بدون خصم هنا
        $total_room_cost = 0;
        foreach ($seatTypeMap as $seatKey => $type) {
            $total_room_cost += floatval($room_prices[$type] ?? 0);
        }

        // 🎯 خصم "سكن فقط": يُحسب لاحقاً ويُطرح من الإجمالي الكلي للفاتورة (غرفة + مقاعد + وجبات + خدمات)
        $accommodationOnlyDiscount = floatval($trip->accommodation_only_discount ?? 0);
        $accommodationOnlySeatsCount = 0;
        foreach ($seatTypeMap as $seatKey => $type) {
            if (str_starts_with((string) $seatKey, 'H')) {
                $accommodationOnlySeatsCount++;
            }
        }
        $total_accommodation_discount = $accommodationOnlySeatsCount * $accommodationOnlyDiscount;

        // ✅ المقاعد الإضافية = مقاعد النقل الفعلية (غير H) التي لم تحصل على أي تسكين معروف
        $extraSeatsCount = 0;
        foreach ($numericSeatsOnly as $seat) {
            if (!isset($seatTypeMap[(string) $seat])) {
                $extraSeatsCount++;
            }
        }

        $total_seats_cost = $extraSeatsCount * $seat_base_price;
        $allowedFreeSeats = count($seatTypeMap); // إبقاء المتغير لأغراض التوافق مع القيمة المرجعة فقط

        // 🚌 4. حسبة المقاعد الإضافية (المقاعد داخل سعة الغرف تكون مجانية 0 ر.س)
        $seat_base_price = floatval($trip->extra_seat_price ?? $trip->seat_price ?? $trip->price ?? 0);
        $totalSeatsSelected = count($numericSeatsOnly);

        if ($allowedFreeSeats > 0) {
            $extraSeatsCount = max(0, $totalSeatsSelected - $allowedFreeSeats);
        } else {
            $extraSeatsCount = $totalSeatsSelected;
        }

        $total_seats_cost = $extraSeatsCount * $seat_base_price;

        // 🍽️ 5. أسعار الوجبات والخدمات
        $bfPrice = $mealOptions['individual']['breakfast']['price'] ?? $mealOptions['breakfast']['price'] ?? $mealOptions['breakfast_price'] ?? $mealOptions['breakfast'] ?? $trip->breakfast_price ?? 0;
        $lnPrice = $mealOptions['individual']['lunch']['price'] ?? $mealOptions['lunch']['price'] ?? $mealOptions['lunch_price'] ?? $mealOptions['lunch'] ?? $trip->lunch_price ?? 0;
        $dnPrice = $mealOptions['individual']['dinner']['price'] ?? $mealOptions['dinner']['price'] ?? $mealOptions['dinner_price'] ?? $mealOptions['dinner'] ?? $trip->dinner_price ?? 0;

        $baseMealPrices = [
            'breakfast' => floatval(is_array($bfPrice) ? ($bfPrice['price'] ?? 0) : $bfPrice),
            'lunch'     => floatval(is_array($lnPrice) ? ($lnPrice['price'] ?? 0) : $lnPrice),
            'dinner'    => floatval(is_array($dnPrice) ? ($dnPrice['price'] ?? 0) : $dnPrice),
        ];

        $price_all_meals = floatval($mealOptions['packages']['all'] ?? $mealOptions['all_meals'] ?? $trip->price_all_meals ?? 0);
        $price_bf_ln     = floatval($mealOptions['packages']['bf_ln'] ?? $mealOptions['bf_ln'] ?? $trip->price_bf_ln ?? 0);
        $price_ln_dn     = floatval($mealOptions['packages']['ln_dn'] ?? $mealOptions['ln_dn'] ?? $trip->price_ln_dn ?? 0);
        $price_bf_dn     = floatval($mealOptions['packages']['bf_dn'] ?? $mealOptions['bf_dn'] ?? $trip->price_bf_dn ?? 0);

        // 🍳 6. احتساب الوجبات
        $total_meals_cost = 0;
        $processedMealKeys = [];

        foreach ((array) $passenger_meals as $key => $meal) {
            if (in_array($key, $processedMealKeys)) continue;
            $processedMealKeys[] = $key;

            $wantsMeal = false;
            if (isset($meal['wants_meal'])) {
                $wantsMeal = ($meal['wants_meal'] == true || $meal['wants_meal'] === 'true' || $meal['wants_meal'] == 1 || $meal['wants_meal'] === '1');
            }

            $hasBf  = !empty($meal['breakfast']) && ($meal['breakfast'] == true || $meal['breakfast'] == 1 || $meal['breakfast'] === 'true' || $meal['breakfast'] === '1');
            $hasLn  = !empty($meal['lunch']) && ($meal['lunch'] == true || $meal['lunch'] == 1 || $meal['lunch'] === 'true' || $meal['lunch'] === '1');
            $hasDn  = !empty($meal['dinner']) && ($meal['dinner'] == true || $meal['dinner'] == 1 || $meal['dinner'] === 'true' || $meal['dinner'] === '1');
            $hasPkg = !empty($meal['package_name']);

            if (!$wantsMeal) {
                $wantsMeal = $hasBf || $hasLn || $hasDn || $hasPkg;
            }

            if ($wantsMeal) {
                if ($hasPkg) {
                    $packageFound = false;
                    $pkgPrice = 0;

                    if (isset($meal['package_price']) && $meal['package_price'] !== '' && floatval($meal['package_price']) > 0) {
                        $pkgPrice = floatval($meal['package_price']);
                        $packageFound = true;
                    } else {
                        foreach ($roomOptions as $roomKey => $roomData) {
                            if (isset($roomData['packages']) && is_array($roomData['packages'])) {
                                foreach ($roomData['packages'] as $pkg) {
                                    if (trim($pkg['name'] ?? '') == trim($meal['package_name'])) {
                                        $pkgPrice = floatval($pkg['price'] ?? 0);
                                        $packageFound = true;
                                        break 2;
                                    }
                                }
                            }
                        }
                    }

                    if ($packageFound) {
                        $total_meals_cost += $pkgPrice;
                        continue;
                    }
                }

                $individualSum = ($hasBf ? $baseMealPrices['breakfast'] : 0) +
                    ($hasLn ? $baseMealPrices['lunch'] : 0) +
                    ($hasDn ? $baseMealPrices['dinner'] : 0);

                $finalMealPrice = $individualSum;

                if ($hasBf && $hasLn && $hasDn) {
                    $finalMealPrice = ($price_all_meals > 0 && $price_all_meals < $individualSum) ? $price_all_meals : $individualSum;
                } elseif ($hasBf && $hasLn) {
                    $finalMealPrice = ($price_bf_ln > 0 && $price_bf_ln < $individualSum) ? $price_bf_ln : $individualSum;
                } elseif ($hasLn && $hasDn) {
                    $finalMealPrice = ($price_ln_dn > 0 && $price_ln_dn < $individualSum) ? $price_ln_dn : $individualSum;
                } elseif ($hasBf && $hasDn) {
                    $finalMealPrice = ($price_bf_dn > 0 && $price_bf_dn < $individualSum) ? $price_bf_dn : $individualSum;
                }

                $total_meals_cost += $finalMealPrice;
            }
        }

        // 🛍️ 7. احتساب الخدمات الإضافية
        $total_services_cost = 0;

        foreach ((array) $passenger_services as $seat => $services) {
            if (empty($services)) continue;
            $servicesList = is_array($services) ? $services : [$services];

            foreach ($servicesList as $srv) {
                $srvPrice = null;
                $srvIdentifier = null;

                if (is_array($srv) || is_object($srv)) {
                    $srvArr = (array) $srv;
                    $srvPrice = $srvArr['price'] ?? $srvArr['cost'] ?? $srvArr['amount'] ?? null;
                    $srvIdentifier = $srvArr['id'] ?? $srvArr['service_id'] ?? $srvArr['name'] ?? null;
                } else {
                    $srvIdentifier = $srv;
                }

                if ($srvPrice !== null && is_numeric($srvPrice)) {
                    $total_services_cost += floatval($srvPrice);
                    continue;
                }

                if ($srvIdentifier !== null && !empty($tripServices)) {
                    foreach ($tripServices as $tripSrv) {
                        $tripSrvArr = (array) $tripSrv;
                        $matchId    = $tripSrvArr['id'] ?? $tripSrvArr['service_id'] ?? $tripSrvArr['name'] ?? null;
                        $matchPrice = $tripSrvArr['price'] ?? $tripSrvArr['cost'] ?? null;

                        if (trim((string)$matchId) === trim((string)$srvIdentifier) && $matchPrice !== null) {
                            $total_services_cost += floatval($matchPrice);
                            break;
                        }
                    }
                }
            }
        }

        $total_extra_beds_cost = 0;
        $extraBeds = is_array($extras['extra_beds'] ?? null) ? $extras['extra_beds'] : [];
        $extraBedPrice = floatval($trip->extra_bed_price ?? ($extras['extra_bed_price'] ?? 0));
        foreach ($extraBeds as $qty) {
            $total_extra_beds_cost += max(0, (int) $qty) * $extraBedPrice;
        }

        $total_custom_services_cost = 0;
        foreach (($extras['custom_services'] ?? []) as $svc) {
            $qty = max(0, (int) ($svc['quantity'] ?? 0));
            $price = floatval($svc['unit_price'] ?? $svc['price'] ?? 0);
            $total_custom_services_cost += $qty * $price;
        }

        $codeDiscount = floatval($extras['discount_code_amount'] ?? 0);

        // 💳 8. الإجمالي النهائي
        $subtotal_before_discount = $total_room_cost + $total_seats_cost + $total_meals_cost + $total_services_cost + $total_extra_beds_cost + $total_custom_services_cost;
        $total_price = max(0, $subtotal_before_discount - $total_accommodation_discount - $codeDiscount);
        return [
    'room_prices'                    => $room_prices,
    'price_all_meals'                => $price_all_meals,
    'price_bf_ln'                    => $price_bf_ln,
    'price_ln_dn'                    => $price_ln_dn,
    'price_bf_dn'                    => $price_bf_dn,
    'total_room_cost'                => $total_room_cost,
    'total_seats_cost'               => $total_seats_cost,
    'total_meals_cost'               => $total_meals_cost,
    'total_services_cost'            => $total_services_cost,
    'total_accommodation_discount'   => $total_accommodation_discount,   // ✅ جديد
    'total_extra_beds_cost'          => $total_extra_beds_cost,
    'total_custom_services_cost'     => $total_custom_services_cost,
    'discount_code_amount'           => $codeDiscount,
    'total_price'                    => $total_price,
    'extra_seats_count'              => $extraSeatsCount,
    'allowed_free_seats'             => $allowedFreeSeats,
];
    }
}
