<?php

namespace App\Http\Controllers\Agency;


use App\Models\BookingSeat;
use App\Models\Trip;
use App\Models\Booking;



use App\Models\User;


// Facades & Utilities
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

use Carbon\Carbon;

use App\Http\Controllers\Controller;
use App\Models\BookingCustomService;
use App\Models\Nationality;
use App\Services\BookingChangeLogger;
use App\Services\BookingCalculator;
use App\Services\DiscountCodeService;
use App\Support\Money;
use App\Support\PassengerSeat;
use Illuminate\Http\Request;

class BookingController extends Controller
{

    /**
     * جلب معرف الوكالة الحالي (من الجلسة أو الجارد المفعّل)
     */
    private function getAgencyId()
    {
        if (auth('agency')->check()) {
            return auth('agency')->id();
        }

        if (auth('employee')->check()) {
            return auth('employee')->user()->agency_id;
        }

        if (session()->has('agency_id')) {
            return session('agency_id');
        }

        return null;
    }
    private function getOperatorName(): string
    {
        if (auth('employee')->check()) {
            return auth('employee')->user()->name . ' (موظف)';
        }

        if (auth('agency')->check()) {
            return auth('agency')->user()->name . ' (مالك الوكالة)';
        }

        if (session()->has('employee_name')) {
            return session('employee_name') . ' (موظف)';
        }

        if (session()->has('agency_name')) {
            return session('agency_name');
        }

        return 'نظام الوكالة الآلي';
    }

    /**
     * ✅ جديد: تفكيك قيمة seat_number (رقم / JSON / نص مفصول بفواصل / مصفوفة) إلى مصفوفة أرقام صحيحة
     */
    private function extractSeatNumbers($raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : explode(',', $raw);
        }
        if (!is_array($raw)) {
            $raw = [$raw];
        }

        $out = [];
        foreach ($raw as $s) {
            $s = trim((string) $s, " \t\n\r\0\x0B\"'");
            if ($s !== '' && is_numeric($s)) {
                $out[] = (int) $s;
            }
        }
        return $out;
    }

    public function index(Request $request)
    {
        $agencyId = $this->getAgencyId();
        $search = $request->query('search');

        // بناء استعلام الرحلات
        $query = Trip::where('agency_id', $agencyId)
            ->withCount(['bookingSeats' => function ($query) {
                $query->where('room_type', '!=', 'blocked')
                    ->where('passenger_name', 'not like', '%مغلق%')
                    ->where('passenger_name', 'not like', '%حظر%')
                    ->where('passenger_name', 'not like', '%إغلاق%')
                    ->where('seat_number', 'not like', '%H%');
            }]);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $trips = $query->latest()->paginate(10)->withQueryString();

        $trips->through(function ($trip) {
            $bookedCount = $trip->booking_seats_count ?? 0;
            $availableSeats = $trip->total_seats - $bookedCount;

            return (object)[
                'id'                  => $trip->id,
                'title'               => $trip->title,
                'date'                => Carbon::parse($trip->departure_at)->format('Y-m-d'),
                'departure_at'        => Carbon::parse($trip->departure_at)->format('h:i A'),
                'days'                => $trip->duration_days ?? 1,
                'price'               => $trip->price,
                'booked_count'        => $bookedCount,
                'available_seats'     => $availableSeats < 0 ? 0 : $availableSeats,
                'total_seats'         => $trip->total_seats,
                'status'              => $trip->status,
                'transport_type'      => $trip->transport_type,
                'allows_shared_rooms' => $trip->allows_shared_rooms,
                'allow_to_add_seat'   => $trip->allow_to_add_seat,
                'is_security_approved' => $trip->is_security_approved,
                'auto_split'          => $trip->auto_split,
                'room_options'        => is_array($trip->room_options) ? $trip->room_options : (json_decode($trip->room_options, true) ?: []),
            ];
        });

        $trip = null;
        $booked_seats_details = [];
        $booked_seats = [];
        $availableSeats = [];
        $mealOptions = [];
        $roomOptions = [];
        $features = [];
        $mealPrices = [];
        $halfSeats = 0;
        $layoutConfig = [];

        if ($request->has('selected_trip_id')) {
            $selectedId = $request->query('selected_trip_id');
            $trip = Trip::with('agency')->where('agency_id', $agencyId)->find($selectedId);

            if ($trip) {
                $bookedSeatsData = BookingSeat::with(['booking', 'parentSeat'])
                    ->where('trip_id', $selectedId)
                    ->get();

                // 1. تجميع أرقام المقاعد المحجوزة في مصفوفة رقمية مسطحة
                foreach ($bookedSeatsData as $seatItem) {
                    $booked_seats = array_merge($booked_seats, $this->extractSeatNumbers($seatItem->seat_number));
                }

                // إزالة التكرارات وضمان الترتيب
                $booked_seats = array_values(array_unique($booked_seats));

                $nationalIds = $bookedSeatsData->pluck('passenger_national_id')->filter()->unique()->toArray();
                $usersMap = User::whereIn('national_id', $nationalIds)->get()->keyBy('national_id');
                $totalSeats = $trip->total_seats ?? 50;

                // 2. احتساب المقاعد المتاحة استناداً إلى جميع المقاعد المأخوذة
                $allSeatNumbers = range(1, $totalSeats);
                $availableSeats = array_values(array_diff($allSeatNumbers, $booked_seats));

                $isFlight = $trip->transport_type === 'flight';

                if ($isFlight) {
                    $halfSeats = ceil($totalSeats / 2);
                    $layoutConfig = [];
                    $seatsRemaining = $totalSeats;
                    while ($seatsRemaining > 0) {
                        if ($seatsRemaining >= 6) {
                            $layoutConfig[] = 'SSS.SSS';
                            $seatsRemaining -= 6;
                        } else {
                            $rowStr = '';
                            for ($i = 0; $i < $seatsRemaining; $i++) {
                                if ($i == 3) $rowStr .= '.';
                                $rowStr .= 'S';
                            }
                            $layoutConfig[] = $rowStr;
                            $seatsRemaining = 0;
                        }
                    }
                } else {
                    $halfSeats = ceil($totalSeats / 2);
                    $layoutConfig = [
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
                        'SSSSS'
                    ];
                }

                // ✅ تم النقل: حساب خيارات الغرف والوجبات والأسعار قبل حلقة المقاعد
                // (سابقاً كانت تُحسب بعدها فيصير $basePrice بقيم فاضية)
                $mealOptions = is_array($trip->meal_options) ? $trip->meal_options : (json_decode($trip->meal_options, true) ?: []);
                $roomOptions = is_array($trip->room_options) ? $trip->room_options : (json_decode($trip->room_options, true) ?: []);

                $trip->room_prices = [
                    'single' => floatval($roomOptions['single']['price'] ?? ($trip->price_single ?? $trip->price)),
                    'double' => floatval($roomOptions['double']['price'] ?? ($trip->price_double ?? ($trip->price * 2))),
                    'triple' => floatval($roomOptions['triple']['price'] ?? ($trip->price_triple ?? ($trip->price * 3))),
                    'quad'   => floatval($roomOptions['quad']['price']   ?? ($trip->price_quad   ?? ($trip->price * 4))),
                ];

                $mealPrices = [
                    'breakfast' => floatval($mealOptions['individual']['breakfast']['price'] ?? ($trip->price_breakfast ?? 0)),
                    'lunch'     => floatval($mealOptions['individual']['lunch']['price']    ?? ($trip->price_lunch    ?? 0)),
                    'dinner'    => floatval($mealOptions['individual']['dinner']['price']   ?? ($trip->price_dinner   ?? 0)),
                ];

                $femaleCount = 0;
                $maleCount = 0;

                // 3. ربط كل مقعد (أساسي أو إضافي) بالتفاصيل الخاصة به بشكل منفصل
                foreach ($bookedSeatsData as $seat) {
                    $associatedUser = $usersMap->get($seat->passenger_national_id);
                    $gender = ($associatedUser && $associatedUser->gender) ? $associatedUser->gender : ($seat->gender ?: 'male');

                    if ($gender === 'female') {
                        $femaleCount++;
                    } else {
                        $maleCount++;
                    }

                    $seatMealOptions = is_array($seat->meal_options)
                        ? $seat->meal_options
                        : (json_decode($seat->meal_options, true) ?: []);

                    $packageName = $seatMealOptions['package_name'] ?? 'الباقة الأساسية';

                    $seatMeals = [];
                    if ($seat->with_breakfast == 1) $seatMeals[] = 'breakfast';
                    if ($seat->with_lunch == 1) $seatMeals[] = 'lunch';
                    if ($seat->with_dinner == 1) $seatMeals[] = 'dinner';

                    $roomType = $seat->room_type ?? 'shared';
                    $sharedType = $seat->shared_room_type ?? null;

                    // استخراج نوع الغرفة ذكياً من نص room_type في حال كان الحجز مسجلاً بالنص العربي
                    if (!$sharedType && (str_contains($roomType, 'مشاركة') || str_contains($roomType, 'مشترك') || $roomType === 'shared')) {
                        if (str_contains($roomType, 'فردية') || str_contains($roomType, 'single')) $sharedType = 'single';
                        elseif (str_contains($roomType, 'ثنائية') || str_contains($roomType, 'double')) $sharedType = 'double';
                        elseif (str_contains($roomType, 'ثلاثية') || str_contains($roomType, 'triple')) $sharedType = 'triple';
                        elseif (str_contains($roomType, 'رباعية') || str_contains($roomType, 'quad')) $sharedType = 'quad';
                    }

                    $isShared = ($roomType === 'shared' || str_contains($roomType, 'مشترك') || str_contains($roomType, 'مشاركة'));
                    $isPrivateRoom = in_array($roomType, ['single', 'double', 'triple', 'quad']);

                    // احتساب السعر الأساسي بناءً على نوع الغرفة
                    if ($isPrivateRoom) {
                        $basePrice = 0;
                    } elseif ($isShared && $sharedType) {
                        $basePrice = floatval($trip->room_prices[$sharedType] ?? $roomOptions[$sharedType]['price'] ?? $trip->price ?? 0);
                    } else {
                        $basePrice = floatval($trip->price ?? 0);
                    }

                    // ✅ التصحيح الأساسي: تعريف $seatsArray (كان غير معرّف فيتخطى الكود كل التفاصيل)
                    $seatsArray = $this->extractSeatNumbers($seat->seat_number);

                    foreach ($seatsArray as $index => $sNumClean) {
                        if ($sNumClean > 0) {
                            $isExtra = ($seat->is_extra == 1) || ($index > 0);
                            $baseName = $seat->passenger_name ?? 'راكب مسجل';

                            $booked_seats_details[$sNumClean] = [
                                'id'                    => $seat->id,
                                'booking_id'            => $seat->booking_id,
                                'name'                  => $baseName . (($isExtra && !str_contains($baseName, 'مقعد إضافي')) ? ' (مقعد إضافي)' : ''),
                                'passenger_name'        => $baseName,
                                'identity'              => $seat->passenger_national_id ?? '',
                                'passenger_national_id' => $seat->passenger_national_id ?? '',
                                'seat_number'           => $sNumClean,
                                'status'                => $seat->booking->status ?? 'confirmed',
                                'gender'                => $gender,
                                'assignment'            => $isExtra ? 'additional' : ($isShared ? 'shared' : $roomType),
                                'room_type'             => $isShared ? 'shared' : $seat->room_type,
                                'shared_room_type'      => $sharedType,
                                'package_name'          => $packageName,
                                'meals'                 => $isExtra ? [] : $seatMeals,
                                'qr_code'               => $seat->qr_code_path ? asset('storage/' . $seat->qr_code_path) : null,
                                'booking_price'         => floatval($seat->booking->total_amount ?? 0),
                                'base_price'            => $basePrice,
                                'breakfast_price'       => floatval($seat->with_breakfast ? ($seat->breakfast_price ?? 0) : 0),
                                'lunch_price'           => floatval($seat->with_lunch ? ($seat->lunch_price ?? 0) : 0),
                                'dinner_price'          => floatval($seat->with_dinner ? ($seat->dinner_price ?? 0) : 0),
                                'is_extra_seat'         => $isExtra,
                                'parent_seat_id'        => $seat->parent_seat_id,
                                'parent_name'           => $seat->parentSeat->passenger_name ?? null,
                                'parent_identity'       => $seat->parentSeat->passenger_national_id ?? null,
                                'nationality_id'        => $seat->nationality_id,
                                'identity_version'      => $seat->identity_version,
                                'identity_issue_place'  => $seat->identity_issue_place,
                                'identity_expiry_date'  => optional($seat->identity_expiry_date)?->format('Y-m-d'),
                                'date_of_birth'         => optional($seat->date_of_birth)?->format('Y-m-d'),
                                'companion_id'          => $seat->companion_id,
                                'bed_type'              => $seat->bed_type,
                                'extra_bed_quantity'    => (int) ($seat->extra_bed_quantity ?? 0),
                                'extra_bed_unit_price'  => (float) ($seat->extra_bed_unit_price ?? 0),
                                'meal_snapshot'         => $seat->meal_snapshot ?? $seatMealOptions,
                            ];
                        }
                    }
                }

                if (isset($trip->auto_split) && $trip->auto_split == 1 && !$trip->is_security_approved) {
                    $defaultBoundary = intval($trip->split_boundary ?: 28);
                    $availableMaleSeats = $defaultBoundary;
                    $availableFemaleSeats = $totalSeats - $defaultBoundary;

                    if ($femaleCount >= ($availableFemaleSeats * 0.80)) {
                        $trip->split_boundary = max(5, $defaultBoundary - 5);
                    } elseif ($maleCount >= ($availableMaleSeats * 0.80)) {
                        $trip->split_boundary = min($totalSeats - 5, $defaultBoundary + 5);
                    }
                }

                $rawFeatures = is_array($trip->features) ? $trip->features : json_decode($trip->features, true);
                $features = !empty($rawFeatures) ? array_unique($rawFeatures) : [];
            }
        }

        $nationalities = \Illuminate\Support\Facades\Schema::hasTable('nationalities')
            ? Nationality::where('is_active', true)->orderBy('name_ar')->get(['id', 'iso2', 'name_ar', 'name_en'])
            : collect();
        $bedTypes = \App\Support\BedTypes::all();

        return view('agency.bookings.addBookings', [
            'trips'                => $trips,
            'trip'                 => $trip,
            'booked_seats_details' => $booked_seats_details,
            'meal_options'         => $mealOptions,
            'meal_prices'          => $mealPrices,
            'room_options'         => $roomOptions,
            'booked_seats'         => $booked_seats,
            'availableSeats'       => $availableSeats,
            'features'             => $features,
            'halfSeats'            => $halfSeats,
            'layoutConfig'         => $layoutConfig,
            'nationalities'        => $nationalities,
            'bedTypes'             => $bedTypes,
            'extraBedPrice'        => floatval($trip?->extra_bed_price ?? 0),
        ]);
    }

    public function changeSeatNumber(Request $request)
    {
        $request->validate([
            'seat_id'         => 'required|exists:booking_seats,id',
            'new_seat_number' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $seat = BookingSeat::lockForUpdate()->findOrFail($request->seat_id);

            $trip = Trip::where('id', $seat->trip_id)
                ->where('agency_id', $this->getAgencyId())
                ->lockForUpdate()
                ->first();

            if (!$trip) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بتعديل هذا المقعد.'
                ], 403);
            }

            $new = (int) $request->new_seat_number;

            if ($new > ($trip->total_seats ?? 0)) {
                return response()->json([
                    'success' => false,
                    'message' => 'رقم المقعد خارج سعة الرحلة.'
                ], 422);
            }

            $hasExtraSeats = BookingSeat::where('parent_seat_id', $seat->id)->exists();
            if ($seat->is_extra || !empty($seat->parent_seat_id) || $hasExtraSeats) {
                return response()->json([
                    'success' => false,
                    'message' => 'عذراً، لا يمكن تغيير رقم مقعد مرتبط بمقاعد إضافية.'
                ], 422);
            }

            $taken = BookingSeat::where('trip_id', $trip->id)
                ->where('id', '!=', $seat->id)
                ->lockForUpdate()
                ->pluck('seat_number')
                ->flatMap(fn($raw) => $this->extractSeatNumbers($raw))
                ->contains($new);

            if ($taken) {
                return response()->json([
                    'success' => false,
                    'message' => 'المقعد المختار محجوز بالفعل، يرجى اختيار مقعد آخر.'
                ], 422);
            }

            $old = is_array($seat->seat_number) ? implode(',', $seat->seat_number) : (string) $seat->seat_number;
            $seat->seat_number = (string) $new;
            $seat->save();

            if ($seat->booking) {
                BookingChangeLogger::log($seat->booking, 'change_seat', ['seat_number' => $old], ['seat_number' => $new], 'booking_seat', $seat->id);
                try {
                    \App\Models\TripSeatLog::create([
                        'booking_seat_id' => $seat->id,
                        'trip_id' => $trip->id,
                        'passenger_name' => $seat->passenger_name,
                        'old_seat' => (int) preg_replace('/\D/', '', $old) ?: 0,
                        'new_seat' => $new,
                        'reason' => 'تغيير رقم المقعد من لوحة الوكالة',
                    ]);
                } catch (\Throwable $e) {
                    // السجل الموحد كافٍ إذا فشل السجل القديم
                }
            }

            return response()->json([
                'success' => true,
                'message' => "تم بنجاح تغيير المقعد إلى ($new)"
            ]);
        });
    }


    /**
     * حذف المقعد المحجوز
     */

    public function destroy($id)
    {
        $seat = BookingSeat::with(['booking.trip', 'booking.roomOption'])->findOrFail($id);

        DB::beginTransaction();
        try {
            $booking = $seat->booking;
            $trip = Trip::lockForUpdate()->find($booking->trip_id);

            // 1. تحديد ما إذا كان المقعد المراد حذفه مقعداً إضافياً أو مقعداً رئيسياً
            $isAdditionalSeat = !empty($seat->parent_seat_id) || $seat->room_type === 'additional';

            if ($isAdditionalSeat) {
                $seatsToDelete = collect([$seat]);
            } else {
                // جلب المقعد الرئيسي والمقاعد الإضافية التابعة له
                $childSeats = BookingSeat::where('parent_seat_id', $seat->id)->get();
                $seatsToDelete = $childSeats->prepend($seat);
            }

            $deletedSeatsCount = $seatsToDelete->count();
            $totalBookingSeatsCount = BookingSeat::where('booking_id', $booking->id)->count();

            // 2. حساب المبالغ المراد خصمها
            // إذا كان الحذف يشمل كل مقاعد الحجز، ينخصم إجمالي مبلغ الحجز الفعلي لتجنب التكرار
            if ($totalBookingSeatsCount <= $deletedSeatsCount) {
                $totalDeduction = (float) $booking->total_amount;
            } else {
                $totalDeduction = 0;

                foreach ($seatsToDelete as $s) {
                    $seatDeduction = 0;
                    $sIsAdditional = !empty($s->parent_seat_id) || $s->room_type === 'additional';

                    if ($sIsAdditional) {
                        // المقعد الإضافي: خصم سعر المقعد الأساسي للرحلة فقط
                        $seatDeduction += (float) ($trip->price ?? 0);

                        // الوجبات الفردية للمقعد الإضافي
                        $seatDeduction += (float) ($s->breakfast_price ?? 0);
                        $seatDeduction += (float) ($s->lunch_price ?? 0);
                        $seatDeduction += (float) ($s->dinner_price ?? 0);
                    } else {
                        // المقعد الرئيسي: احتساب سعر الغرفة
                        $roomType = strtolower(trim($s->room_type ?? ''));
                        $sharedType = strtolower(trim($s->shared_room_type ?? ''));

                        // استخراج نوع الغرفة المشتركة من النص العربي إن وجد
                        if (!$sharedType && (str_contains($roomType, 'مشاركة') || str_contains($roomType, 'مشترك') || $roomType === 'shared')) {
                            if (str_contains($roomType, 'فردية') || str_contains($roomType, 'single')) $sharedType = 'single';
                            elseif (str_contains($roomType, 'ثنائية') || str_contains($roomType, 'double')) $sharedType = 'double';
                            elseif (str_contains($roomType, 'ثلاثية') || str_contains($roomType, 'triple')) $sharedType = 'triple';
                            elseif (str_contains($roomType, 'رباعية') || str_contains($roomType, 'quad')) $sharedType = 'quad';
                        }

                        $isShared = ($roomType === 'shared' || str_contains($roomType, 'مشترك') || str_contains($roomType, 'مشاركة'));
                        $isPrivateRoom = in_array($roomType, ['single', 'double', 'triple', 'quad']);

                        if ($isPrivateRoom) {
                            $roomOpts = is_string($s->room_options)
                                ? json_decode($s->room_options, true)
                                : ($s->room_options ?? []);

                            $roomPrice = (float) (
                                $roomOpts['price']
                                ?? $roomOpts['package_price']
                                ?? $roomOpts['package']['price']
                                ?? 0
                            );

                            $seatDeduction += ($roomPrice > 0) ? $roomPrice : (float) ($trip->price ?? 0);
                        } elseif ($isShared && $sharedType) {
                            $roomOpts = is_string($s->room_options)
                                ? json_decode($s->room_options, true)
                                : ($s->room_options ?? []);

                            $sharedPrice = (float) (
                                $trip->room_prices[$sharedType]
                                ?? $roomOpts[$sharedType]['price']
                                ?? $trip->price
                                ?? 0
                            );

                            $seatDeduction += ($sharedPrice > 0) ? $sharedPrice : (float) ($trip->price ?? 0);
                        } else {
                            $seatDeduction += (float) ($trip->price ?? 0);
                        }

                        // الوجبات الفردية للمقعد الرئيسي
                        $seatDeduction += (float) ($s->breakfast_price ?? 0);
                        $seatDeduction += (float) ($s->lunch_price ?? 0);
                        $seatDeduction += (float) ($s->dinner_price ?? 0);
                    }

                    $totalDeduction += $seatDeduction;
                }

                // سقف أعلى لضمان عدم تجاوز الخصم لإجمالي مبلغ الحجز
                $totalDeduction = min((float) $booking->total_amount, $totalDeduction);
            }

            // 3. تنفيذ الحذف
            $seatIdsToDelete = $seatsToDelete->pluck('id');
            BookingSeat::whereIn('id', $seatIdsToDelete)->delete();

            // 4. تحديث مبالغ الحجز وحالة الدفع
            if ($booking) {
                $booking->total_amount = max(0, (float) $booking->total_amount - $totalDeduction);

                if (isset($booking->net_amount)) {
                    $booking->net_amount = max(0, (float) $booking->net_amount - $totalDeduction);
                }
                if (isset($booking->agency_amount)) {
                    $booking->agency_amount = max(0, (float) $booking->agency_amount - $totalDeduction);
                }

                if (isset($booking->cash_paid)) {
                    if ($booking->total_amount <= 0 || $booking->cash_paid >= $booking->total_amount) {
                        $booking->payment_status = 'paidof';
                    } elseif ($booking->cash_paid > 0) {
                        $booking->payment_status = 'partial';
                    }
                }

                $booking->save();
            }

            // 5. مزامنة مقاعد الرحلة المتاحة
            if ($trip) {
                $this->syncTripAvailableSeats($trip);
            }

            // 6. توثيق العملية في سجل التدقيق
            \App\Models\AuditLog::create([
                'agency_id'      => $this->getAgencyId(),
                'trip_id'        => $trip->id ?? null,
                'action_type'    => 'delete',
                'category'       => 'booking',
                'amount_changed' => $totalDeduction,
                'seats_changed'  => $deletedSeatsCount,
                'description'    => "🗑️ حذف مقعد رقم ({$seat->seat_number}) من الحجز #{$booking->id} وخصم مبلغ (" . number_format($totalDeduction, 2) . " ر.س).",
                'operator_name'  => $this->getOperatorName(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المقعد وخصم مبلغ (' . number_format($totalDeduction, 2) . ' ر.س) من الحجز بنجاح.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المقعد: ' . $e->getMessage()
            ], 500);
        }
    }


    public function storeAgencyBooking(Request $request)
    {
        // 1. دمج وتأكيد صحة البيانات
        $this->mergeAndValidateBookingRequest($request);

        // 2. استخراج وتنظيف البيانات من الـ Request
        $parsed = $this->extractBookingInputs($request);
        $agentId = $this->getAgencyId();

        try {
            // 3. بدء عملية الحفظ داخل Database Transaction
            $booking = DB::transaction(function () use ($request, $parsed, $agentId) {

                $trip = Trip::lockForUpdate()->findOrFail($request->trip_id);

                // 4. التحقق من توافر المقاعد وقواعد العمل
                $this->validateBookingBusinessRules($trip, $parsed, $agentId);

                // 5. حساب التكاليف (غرف، مقاعد، وجبات، خدمات) وتجهيز التفاصيل
                $costsAndDetails = $this->calculateBookingCostsAndDetails($trip, $parsed, $request);

                // 6. معالجة الدفع المتعدد وتحديد حالات الدفع
                $paymentInfo = $this->processSplitPayments($request, $costsAndDetails['totalAmount'], $parsed['isLocked']);

                // 7. إنشاء سجل الحجز الأساسي وحفظ الدفعات المتعددة
                $remainingNow = max(0, (float) $costsAndDetails['totalAmount'] - (float) $paymentInfo['cashPaid']);
                if (!$parsed['isLocked'] && $remainingNow > 0.004 && !$parsed['confirmUnpaid']) {
                    if (!config('booking.allow_confirm_with_remaining', true)) {
                        throw new \Exception('لا يمكن اعتماد الحجز مع وجود مبلغ متبقٍ.');
                    }
                    throw new \Exception('CONFIRM_UNPAID:' . number_format($remainingNow, 2));
                }

                $booking = $this->createBookingAndPayments($trip, $request, $parsed, $costsAndDetails, $paymentInfo, $agentId);

                // 8. إنشاء مقاعد الحجز وربط التذاكر (QR Codes)
                $this->createSeatsAndGenerateTickets($booking, $trip, $parsed, $costsAndDetails);

                // 9. إنهاء العمليات (تحديث سعة الرحلة وتسجيل التدقيق)
                $this->finalizeTripAndLogAudit($booking, $trip, $parsed, $paymentInfo, $costsAndDetails, $agentId);

                return $booking;
            });

            // 10. إرجاع رسالة النجاح
            $message = $booking->is_locked
                ? '🔒 تم قفل المقاعد المحددة للرحلة بنجاح عبر لوحة التحكم!'
                : '✨ تم تسجيل وتأكيد الحجز بنجاح عبر لوحة تحكم الوكالة! رقم السجل الداخلي: #' . $booking->id;

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'       => 'success',
                    'message'      => $message,
                    'redirect_url' => route('agency.addbookings')
                ]);
            }

            return redirect()->route('agency.addbookings')->with('success', $message);
        } catch (\Exception $e) {
            if (str_starts_with($e->getMessage(), 'CONFIRM_UNPAID:')) {
                $remain = substr($e->getMessage(), strlen('CONFIRM_UNPAID:'));
                $msg = 'يوجد مبلغ متبقٍ (' . $remain . ' ر.س). أكّد اعتماد الحجز غير مكتمل الدفع أو أكمل الدفع أولاً.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['status' => 'confirm_unpaid', 'message' => $msg, 'remaining' => $remain], 409);
                }
                return redirect()->back()->withInput()->with('error', $msg);
            }
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage()
                ], 422);
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
    /* =========================================================
       الدوال الفرعية لمعالجة الحجز (Refactored Methods)
    ========================================================= */

    // 1. دمج البيانات والتحقق من الـ Request
    private function mergeAndValidateBookingRequest(Request $request)
    {
        if (empty($request->input('selected_seats')) && $request->filled('seats')) $request->merge(['selected_seats' => $request->input('seats')]);
        elseif (empty($request->input('seats')) && $request->filled('selected_seats')) $request->merge(['seats' => $request->input('selected_seats')]);

        if (empty($request->input('room_quantities')) && $request->filled('room_quantities_json')) $request->merge(['room_quantities' => $request->input('room_quantities_json')]);
        if (empty($request->input('room_assignments')) && $request->filled('room_assignments_json')) $request->merge(['room_assignments' => $request->input('room_assignments_json')]);
        if (empty($request->input('passenger_meals')) && $request->filled('passenger_meals_json')) $request->merge(['passenger_meals' => $request->input('passenger_meals_json')]);
        if (empty($request->input('passenger_services')) && $request->filled('passenger_services_json')) $request->merge(['passenger_services' => $request->input('passenger_services_json')]);

        $request->validate([
            'trip_id'            => 'required|exists:trips,id',
            'payments_data'      => 'nullable|string', // الحقل الجديد للدفع المتعدد
            'cash_paid'          => 'nullable|numeric|min:0', // احتياطي للطريقة القديمة
            'room_quantities'    => 'nullable',
            'room_assignments'   => 'nullable',
            'selected_seats'     => 'required',
            'passenger_names'    => 'required',
            'passenger_ids'      => 'required',
            'passenger_genders'  => 'required',
            'seat_number'        => 'nullable|string',
            'parent_seats'       => 'nullable',
            'passenger_meals'    => 'nullable',
            'shared_room_types' => 'nullable',
            'passenger_services' => 'nullable',
            'customer_phone'     => 'required|string',
            'customer_email'     => 'nullable|email',
            'payment_method'     => 'nullable|in:balance,card,bank,cash,pos,moyasar,bank_transfer',
            'card_name'          => 'nullable|required_if:payment_method,card|string|max:255',
            'bank_receipt'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'is_locked'          => 'nullable|boolean',
            'passenger_details'  => 'nullable',
            'custom_services'    => 'nullable',
            'discount_code'      => 'nullable|string|max:50',
            'confirm_unpaid'     => 'nullable|boolean',
        ], [
            'customer_phone.required'  => 'رقم هاتف العميل أساسي للتواصل وإرسال التذاكر.',
        ]);
    }

    // 2. استخراج وتنظيف البيانات وتجميعها في مصفوفة واحدة مرتبة
    private function extractBookingInputs(Request $request): array
    {
        $parsed = [];
        $parsed['isLocked'] = $request->boolean('is_locked', false);

        $parsed['seatsArray']            = $this->parseIncomingData($request->input('selected_seats'));
        $parsed['passengerNamesArray']   = $this->parseIncomingData($request->input('passenger_names'));
        $parsed['passengerIdsArray']     = array_values(array_filter($this->parseIncomingData($request->input('passenger_ids')), fn($v) => $v !== null && $v !== ''));
        $parsed['passengerGendersArray'] = $this->parseIncomingData($request->input('passenger_genders'));

        $parsed['extraSeatNum'] = $request->filled('seat_number') ? $this->cleanSeatNum($request->input('seat_number')) : null;

        $parentSeatsInput = $request->input('parent_seats');
        $parsed['parentSeatsMap'] = is_array($parentSeatsInput) ? $parentSeatsInput : (json_decode($parentSeatsInput, true) ?: []);

        $allExtraSeatNumbers = [];
        foreach ($parsed['parentSeatsMap'] as $parent => $extras) {
            if (is_array($extras)) {
                foreach ($extras as $ex) $allExtraSeatNumbers[] = (string)$this->cleanSeatNum($ex);
            }
        }
        if ($parsed['extraSeatNum'] !== null && !in_array((string)$parsed['extraSeatNum'], $allExtraSeatNumbers)) {
            $allExtraSeatNumbers[] = (string)$parsed['extraSeatNum'];
        }
        if ($parsed['extraSeatNum'] !== null && !in_array($parsed['extraSeatNum'], $parsed['seatsArray'])) {
            $parsed['seatsArray'][] = $parsed['extraSeatNum'];
        }
        $parsed['allExtraSeatNumbers'] = $allExtraSeatNumbers;

        $parsed['roomAssignments']   = $this->parseJsonOrArray($request->input('room_assignments'));
        $parsed['roomQuantities']    = $this->parseJsonOrArray($request->input('room_quantities'));
        $parsed['passengerMeals']    = $this->parseJsonOrArray($request->input('passenger_meals'));
        $parsed['passengerServices'] = $this->parseJsonOrArray($request->input('passenger_services'));

        $parsed['roomCount'] = $parsed['isLocked'] ? 0 : array_sum(is_array($parsed['roomQuantities']) ? $parsed['roomQuantities'] : []);
        $chosenRoomTypes = is_array($parsed['roomQuantities']) ? array_keys(array_filter($parsed['roomQuantities'], fn($qty) => $qty > 0)) : [];
        $parsed['finalRoomType'] = $parsed['isLocked'] ? 'none' : (!empty($chosenRoomTypes) ? implode(', ', $chosenRoomTypes) : 'shared');

        $sharedRaw = $this->parseJsonOrArray($request->input('shared_room_types'));
        $parsed['sharedRoomTypes'] = [];
        if (is_array($sharedRaw)) {
            foreach ($sharedRaw as $seat => $type) {
                $cleanKey = (string) $this->cleanSeatNum($seat);
                $parsed['sharedRoomTypes'][$cleanKey] = strtolower(trim((string) $type));
                $parsed['sharedRoomTypes'][(int)$cleanKey] = strtolower(trim((string) $type));
            }
        }

        $parsed['passengerDetails'] = $this->parseJsonOrArray($request->input('passenger_details'));
        $parsed['customServices'] = $this->parseJsonOrArray($request->input('custom_services'));
        $parsed['discountCode'] = trim((string) $request->input('discount_code', ''));
        $parsed['confirmUnpaid'] = $request->boolean('confirm_unpaid');

        $usedCompanionIds = [];
        foreach ((array) $parsed['passengerDetails'] as $detail) {
            if (!is_array($detail)) {
                continue;
            }
            $cid = $detail['companion_id'] ?? null;
            if ($cid) {
                if (in_array((int) $cid, $usedCompanionIds, true)) {
                    throw new \Exception('لا يمكن إضافة التابع نفسه أكثر من مرة في نفس الحجز.');
                }
                $usedCompanionIds[] = (int) $cid;
            }
            $dob = $detail['date_of_birth'] ?? null;
            if ($dob && \Carbon\Carbon::parse($dob)->isFuture()) {
                throw new \Exception('تاريخ الميلاد لا يمكن أن يكون في المستقبل.');
            }
            $expiry = $detail['identity_expiry_date'] ?? null;
            if ($expiry && \Carbon\Carbon::parse($expiry)->isPast() && config('booking.block_on_expired_identity')) {
                throw new \Exception('وثيقة الهوية منتهية ولا يمكن إتمام الحجز حسب سياسة الرحلة.');
            }
        }

        return $parsed;
    }

    // 3. التحقق من قواعد العمل (الأقفال، التكرار، السعة)
    private function validateBookingBusinessRules(Trip $trip, array $parsed, $agentId)
    {
        // حساب المقاعد الرئيسية
        $mainSeatsCount = 0;
        foreach ($parsed['seatsArray'] as $s) {
            if (!in_array((string)$s, $parsed['allExtraSeatNumbers']) && ($parsed['roomAssignments'][$s] ?? '') !== 'additional') {
                $mainSeatsCount++;
            }
        }

        if ($mainSeatsCount !== count($parsed['passengerGendersArray']) || $mainSeatsCount !== count($parsed['passengerNamesArray'])) {
            throw new \Exception('عذراً، حدث خطأ في مطابقة بيانات الركاب مع المقاعد المختارة بالجدول الهندسي.');
        }

        if ($parsed['isLocked']) {
            $maxAllowedLocks = 6;
            $currentLockedCount = BookingSeat::whereHas('booking', function ($q) use ($agentId, $trip) {
                $q->where('agency_id', $agentId)->where('trip_id', $trip->id)->where('is_locked', true);
            })->count();

            if (($currentLockedCount + count($parsed['seatsArray'])) > $maxAllowedLocks) {
                throw new \Exception("عذراً، تجاوزت الحد الأقصى المسموح به لإغلاق المقاعد لهذه الرحلة ({$maxAllowedLocks} مقاعد). المغلق حالياً: {$currentLockedCount} مقعد.");
            }
        }

        if (!empty($parsed['passengerIdsArray']) && !$parsed['isLocked']) {
            $isAlreadyRegistered = BookingSeat::where('trip_id', $trip->id)
                ->whereIn('passenger_national_id', $parsed['passengerIdsArray'])
                ->where('passenger_national_id', '!=', '0000000000')
                ->exists();
            if ($isAlreadyRegistered) throw new \Exception('🛑 تنبيه: أحد المسافرين مسجل بالفعل في كشوفات هذه الرحلة مسبقاً.');
        }

        // ✅ التحقق يعتمد على العدّ الفعلي من booking_seats داخل نفس الـ Transaction
        // (الرحلة مقفولة بـ lockForUpdate قبل هذي الدالة، فهذا يمنع تعارض الحجوزات المتزامنة)
        $occupiedSeats  = $this->countOccupiedSeats($trip->id);
        $availableSeats = max(0, ($trip->total_seats ?? 0) - $occupiedSeats);

        if (count($parsed['seatsArray']) > $availableSeats) {
            throw new \Exception('عذراً، عدد المقاعد المطلوبة أكبر من المقاعد المتاحة بالحافلة حالياً.');
        }

        $alreadyBooked = BookingSeat::where('trip_id', $trip->id)->whereIn('seat_number', $parsed['seatsArray'])->where('is_extra', 0)->exists();
        if ($alreadyBooked) throw new \Exception('المقاعد المحددة تم حجزها للتو من نافذة أخرى. يرجى تحديث المخطط.');
    }
    /**
     * حساب عدد المقاعد المشغولة فعلياً لرحلة معينة، اعتماداً مباشرة على سجلات booking_seats
     * (يُفكّك القيمة إذا كانت مخزّنة كمصفوفة JSON أو نص مفصول بفواصل، لضمان دقة العدّ)
     */
    private function countOccupiedSeats(int $tripId): int
    {
        $rawSeatNumbers = BookingSeat::where('trip_id', $tripId)->pluck('seat_number');

        $count = 0;
        foreach ($rawSeatNumbers as $raw) {
            $count += count($this->extractSeatNumbers($raw));
        }

        return $count;
    }

    /**
     * إعادة مزامنة عمود available_seats المخزّن بالرحلة ليطابق الواقع الفعلي دائماً.
     * يُستخدم كـ "كاش" للعرض فقط بأماكن ثانية بالتطبيق - لا يُعتمد عليه إطلاقاً
     * بقرارات الحجز؛ تلك تعتمد على countOccupiedSeats() مباشرة.
     */
    private function syncTripAvailableSeats(Trip $trip): int
    {
        $occupied  = $this->countOccupiedSeats($trip->id);
        $available = max(0, ($trip->total_seats ?? 0) - $occupied);

        $trip->available_seats = $available;
        $trip->save();

        return $available;
    }

    // 4. حساب التكاليف المالية وتجهيز تفاصيل الوجبات والخدمات
    private function calculateBookingCostsAndDetails(Trip $trip, array &$parsed, Request $request): array
    {
        $seatMealsDetails = [];
        $seatServicesDetails = [];
        $roomOptions = is_array($trip->room_options) ? $trip->room_options : (json_decode($trip->room_options, true) ?: []);
        $mealOptions = is_array($trip->meal_options) ? $trip->meal_options : (json_decode($trip->meal_options, true) ?: []);
        $tripServices = is_array($trip->extra_services ?? $trip->services) ? ($trip->extra_services ?? $trip->services) : (json_decode($trip->extra_services ?? $trip->services, true) ?: []);

        $totalAmount = 0;
        $seatSharedTypes = [];
        $calc = [];

        if ($parsed['isLocked']) {
            foreach ($parsed['seatsArray'] as $seatNum) {
                $seatNum = $this->cleanSeatNum($seatNum);
                $seatMealsDetails[$seatNum] = ['bf' => 0, 'ln' => 0, 'dn' => 0, 'hasBf' => 0, 'hasLn' => 0, 'hasDn' => 0, 'package_name' => null, 'package_price' => 0];
                $seatServicesDetails[$seatNum] = [];
            }
        } else {
            // التحقق من مخزون الغرف وخصمه محلياً
            if (is_array($parsed['roomQuantities'])) {
                foreach ($parsed['roomQuantities'] as $type => $qty) {
                    $qty = intval($qty);
                    if ($qty > 0) {
                        $availableCount = isset($roomOptions[$type]['count']) ? intval($roomOptions[$type]['count']) : 0;
                        if ($qty > $availableCount) throw new \Exception("عذراً، الغرف من النوع المختار لم تعد كافية بالمخزون الحالي.");
                        $roomOptions[$type]['count'] = $availableCount - $qty;
                    }
                }
            }

            $requestPrices = $this->parseJsonOrArray($request->input('prices'));
            $getRoomPrice = function ($type) use ($roomOptions, $trip, $requestPrices) {
                if (!empty($requestPrices[$type])) return floatval($requestPrices[$type]);
                if (isset($roomOptions[$type]['price']) && $roomOptions[$type]['price'] !== '') return floatval($roomOptions[$type]['price']);
                if (isset($roomOptions[$type]['room_price']) && $roomOptions[$type]['room_price'] !== '') return floatval($roomOptions[$type]['room_price']);
                $tripField = 'price_' . $type;
                if (isset($trip->$tripField) && $trip->$tripField > 0) return floatval($trip->$tripField);
                return 0;
            };

            $totalRoomCost = 0;
            if (is_array($parsed['roomQuantities'])) {
                foreach ($parsed['roomQuantities'] as $type => $qty) {
                    $totalRoomCost += (intval($qty) * $getRoomPrice($type));
                }
            }

            $seatBasePrice = floatval($trip->price ?? 0);
            $totalSeatsCost = $totalPackagesCost = $totalMealsCost = $totalServicesCost = 0;
            $defaultMealPrices = [
                'breakfast' => (!empty($mealOptions['individual']['breakfast']['active'])) ? floatval($mealOptions['individual']['breakfast']['price'] ?? 0) : 0,
                'lunch'     => (!empty($mealOptions['individual']['lunch']['active'])) ? floatval($mealOptions['individual']['lunch']['price'] ?? 0) : 0,
                'dinner'    => (!empty($mealOptions['individual']['dinner']['active'])) ? floatval($mealOptions['individual']['dinner']['price'] ?? 0) : 0,
            ];
            $seatPkgSel = $this->parseJsonOrArray($request->input('seat_package_selection'));
            foreach ($parsed['seatsArray'] as $seatNum) {
                $seatNum = $this->cleanSeatNum($seatNum);
                $assignment = $parsed['roomAssignments'][$seatNum] ?? 'shared';
                $rType = $assignment !== 'shared' ? explode('_', $assignment)[0] : 'shared';

                if (in_array((string) $seatNum, $parsed['allExtraSeatNumbers'])) {
                    $rType = 'additional';
                }

                if ($rType === 'additional') {
                    $totalSeatsCost += $seatBasePrice;
                } elseif ($rType === 'shared') {
                    $sharedType = $parsed['sharedRoomTypes'][$seatNum] ?? null;

                    if (!in_array($sharedType, ['single', 'double', 'triple', 'quad'], true)) {
                        throw new \Exception("حدد نوع الغرفة المشتركة للمقعد رقم {$seatNum}.");
                    }

                    $sharedPrice = $getRoomPrice($sharedType);
                    if ($sharedPrice <= 0) {
                        throw new \Exception("نوع الغرفة المشتركة المختار للمقعد رقم {$seatNum} غير متاح.");
                    }

                    $totalSeatsCost += $sharedPrice;
                    $seatSharedTypes[$seatNum] = $sharedType;
                }

                $isPackageSelected = false;
                if (is_array($seatPkgSel) && isset($seatPkgSel[$seatNum])) $isPackageSelected = ($seatPkgSel[$seatNum] === 'package');
                elseif (!empty($parsed['passengerMeals'][$seatNum]['package_name']) && $parsed['passengerMeals'][$seatNum]['package_name'] !== 'الباقة الأساسية') $isPackageSelected = true;
                elseif (in_array($rType, ['single', 'double', 'triple', 'quad'])) $isPackageSelected = true;

                $roomPackage = (in_array($rType, ['single', 'double', 'triple', 'quad']) && isset($roomOptions[$rType]['packages'])) ? ($roomOptions[$rType]['packages'][0] ?? null) : null;

                if (in_array($rType, ['single', 'double', 'triple', 'quad']) && $isPackageSelected && $roomPackage) {
                    $totalPackagesCost += floatval($roomPackage['price'] ?? ($roomPackage['package_price'] ?? 0));
                } elseif (!$isPackageSelected && isset($parsed['passengerMeals'][$seatNum])) {
                    if (!empty($parsed['passengerMeals'][$seatNum]['breakfast'])) $totalMealsCost += $defaultMealPrices['breakfast'];
                    if (!empty($parsed['passengerMeals'][$seatNum]['lunch'])) $totalMealsCost += $defaultMealPrices['lunch'];
                    if (!empty($parsed['passengerMeals'][$seatNum]['dinner'])) $totalMealsCost += $defaultMealPrices['dinner'];
                }

                $hasBf = !empty($parsed['passengerMeals'][$seatNum]['breakfast']) || ($roomPackage && $isPackageSelected && !empty($roomPackage['meals']['breakfast']));
                $hasLn = !empty($parsed['passengerMeals'][$seatNum]['lunch']) || ($roomPackage && $isPackageSelected && !empty($roomPackage['meals']['lunch']));
                $hasDn = !empty($parsed['passengerMeals'][$seatNum]['dinner']) || ($roomPackage && $isPackageSelected && !empty($roomPackage['meals']['dinner']));

                $seatMealsDetails[$seatNum] = [
                    'bf' => ($hasBf && !$isPackageSelected) ? $defaultMealPrices['breakfast'] : 0,
                    'ln' => ($hasLn && !$isPackageSelected) ? $defaultMealPrices['lunch'] : 0,
                    'dn' => ($hasDn && !$isPackageSelected) ? $defaultMealPrices['dinner'] : 0,
                    'hasBf' => $hasBf ? 1 : 0,
                    'hasLn' => $hasLn ? 1 : 0,
                    'hasDn' => $hasDn ? 1 : 0,
                    'package_name' => $parsed['passengerMeals'][$seatNum]['package_name'] ?? ($isPackageSelected && $roomPackage ? ($roomPackage['name'] ?? $rType) : 'الباقة الأساسية'),
                    'package_price' => ($isPackageSelected && $roomPackage) ? floatval($roomPackage['price'] ?? ($roomPackage['package_price'] ?? 0)) : 0
                ];

                // Services Processing
                $chosenServices = $parsed['passengerServices'][$seatNum] ?? $parsed['passengerServices'][(string)$seatNum] ?? [];
                if (is_string($chosenServices)) {
                    $decoded = json_decode($chosenServices, true);
                    $chosenServices = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $chosenServices)));
                }

                $seatServTotal = 0;
                $seatServList = [];
                if (is_array($chosenServices) && !empty($chosenServices)) {
                    foreach ($tripServices as $index => $serv) {
                        $servId = $serv['id'] ?? $index;
                        $servName = $serv['name'] ?? ($serv['title'] ?? '');
                        $servPrice = floatval($serv['price'] ?? ($serv['cost'] ?? 0));

                        $isChosen = in_array($servId, $chosenServices) || in_array((string)$servId, $chosenServices) || in_array($index, $chosenServices) || in_array((string)$index, $chosenServices);
                        if (!$isChosen && isset($chosenServices[$servId])) $isChosen = filter_var($chosenServices[$servId], FILTER_VALIDATE_BOOLEAN);

                        if ($isChosen) {
                            $seatServTotal += $servPrice;
                            $seatServList[] = ['id' => $servId, 'name' => $servName, 'price' => $servPrice];
                        }
                    }
                }
                $totalServicesCost += $seatServTotal;
                $seatServicesDetails[$seatNum] = $seatServList;
            }
            $totalAmount = $totalRoomCost + $totalSeatsCost + $totalPackagesCost + $totalMealsCost + $totalServicesCost;

            $bedAssignments = [];
            $extraBedCost = 0;
            $extraBedPrice = floatval($trip->extra_bed_price ?? 0);
            foreach ($parsed['seatsArray'] as $seatNumRaw) {
                $seatNum = $this->cleanSeatNum($seatNumRaw);
                $detail = $parsed['passengerDetails'][$seatNum] ?? $parsed['passengerDetails'][(string) $seatNum] ?? [];
                $qty = intval($detail['extra_bed_quantity'] ?? 0);
                $unit = floatval($detail['extra_bed_unit_price'] ?? $extraBedPrice);
                $bedAssignments[$seatNum] = [
                    'bed_type' => $detail['bed_type'] ?? null,
                    'extra_bed_quantity' => $qty,
                    'extra_bed_unit_price' => $unit,
                ];
                if ($qty > 0) {
                    $extraBedCost += $qty * $unit;
                }
            }

            $customServicesCost = 0;
            $normalizedCustom = [];
            foreach ((array) $parsed['customServices'] as $custom) {
                if (!is_array($custom) || empty($custom['name'])) {
                    continue;
                }
                $qty = max(1, intval($custom['quantity'] ?? 1));
                $unit = max(0, floatval($custom['unit_price'] ?? 0));
                $line = $qty * $unit;
                $customServicesCost += $line;
                $normalizedCustom[] = [
                    'name' => $custom['name'],
                    'description' => $custom['description'] ?? null,
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'total_price' => $line,
                    'seat' => $custom['seat'] ?? $custom['booking_seat'] ?? null,
                ];
            }

            $subtotal = $totalAmount + $extraBedCost + $customServicesCost;
            $codeDiscount = 0;
            $discountMeta = null;
            if (!empty($parsed['discountCode'])) {
                $preview = app(DiscountCodeService::class)->preview(
                    $parsed['discountCode'],
                    (int) $this->getAgencyId(),
                    (float) $subtotal,
                    $trip->id,
                    $request->input('customer_phone')
                );
                $codeDiscount = $preview['amount'];
                $discountMeta = $preview;
            }

            $stack = config('booking.stack_discount_code_with_trip_discounts', true);

            $calc = BookingCalculator::calculate(
                $trip,
                $parsed['roomQuantities'],
                $parsed['seatsArray'],
                $parsed['roomAssignments'],
                $parsed['passengerMeals'],
                $parsed['passengerServices'],
                [
                    'custom_services' => $normalizedCustom,
                    'bed_assignments' => $bedAssignments,
                    'extra_bed_price' => $extraBedPrice,
                    'discount_code_amount' => $codeDiscount,
                    'stack_discounts' => $stack,
                ]
            );

            $totalAmount = $calc['total_price'];
            $parsed['customServices'] = $normalizedCustom;
            $parsed['bedAssignments'] = $bedAssignments;
            $parsed['discountMeta'] = $discountMeta;
        }

        return [
            'totalAmount' => $totalAmount,
            'seatMealsDetails' => $seatMealsDetails,
            'seatServicesDetails' => $seatServicesDetails,
            'roomOptions' => $roomOptions,
            'seatSharedTypes' => $seatSharedTypes,
            'subtotalAmount' => $calc['subtotal_before_discount'] ?? $totalAmount,
            'discountAmount' => $calc['discount_code_amount'] ?? 0,
            'taxAmount' => $calc['tax_amount'] ?? 0,
            'customServices' => $parsed['customServices'] ?? [],
            'bedAssignments' => $parsed['bedAssignments'] ?? [],
            'discountMeta' => $parsed['discountMeta'] ?? null,
            'remainingAmount' => $calc['remaining_amount'] ?? 0,
        ];
    }

    // 5. معالجة الدفع المتعدد (Split Payments)
    private function processSplitPayments(Request $request, $totalAmount, $isLocked): array
    {
        $rawPaymentsData = $request->input('payments_data');
        $paymentsData = is_string($rawPaymentsData) ? (json_decode($rawPaymentsData, true) ?? []) : (is_array($rawPaymentsData) ? $rawPaymentsData : []);

        // إذا لم تصل مصفوفة تفصيلية ولكن العميل أدخل كاش مع طريقة أخرى بالطريقة القديمة
        $cashInput = floatval($request->input('cash_paid', 0));
        if (empty($paymentsData) && $cashInput > 0) {
            $paymentsData[] = ['method' => 'cash', 'amount' => $cashInput];
            $rem = $totalAmount - $cashInput;
            if ($rem > 0 && $request->filled('payment_method') && $request->payment_method !== 'cash') {
                $paymentsData[] = ['method' => $request->payment_method, 'amount' => $rem];
            }
        }

        $actualTotalPaid = 0;
        $hasBankTransfer = false;
        $normalizedPayments = [];

        foreach ($paymentsData as $payment) {
            // مرونة في قراءة الحقول سواء كُتبت method أو payment_method
            $method = $payment['method'] ?? $payment['payment_method'] ?? 'cash';
            $amount = floatval($payment['amount'] ?? 0);
            $ref    = $payment['reference'] ?? $payment['reference_id'] ?? null;

            if ($amount > 0) {
                $actualTotalPaid += $amount;
                if (in_array($method, ['bank_transfer', 'bank'])) {
                    $hasBankTransfer = true;
                }
                $normalizedPayments[] = [
                    'method'    => $method,
                    'amount'    => $amount,
                    'reference' => $ref,
                ];
            }
        }

        $primaryMethod = count($normalizedPayments) > 1 ? 'split' : ($normalizedPayments[0]['method'] ?? ($request->payment_method ?? 'cash'));
        $cashPaid      = min($actualTotalPaid, $totalAmount);
        $netAgencyAmount = max(0, $totalAmount - $cashPaid);

        if ($isLocked) {
            $bookingStatus = 'locked';
            $paymentStatus = 'unpaid';
        } else {
            if ($cashPaid >= $totalAmount && $totalAmount > 0) $paymentStatus = 'paidof';
            elseif ($cashPaid > 0) $paymentStatus = 'partial';
            else $paymentStatus = 'unpaid';
            $bookingStatus = 'confirmed';
        }

        return [
            'cashPaid'        => $cashPaid,
            'netAgencyAmount' => $netAgencyAmount,
            'bookingStatus'   => $bookingStatus,
            'paymentStatus'   => $paymentStatus,
            'primaryMethod'   => $primaryMethod,
            'hasBankTransfer' => $hasBankTransfer,
            'paymentsData'    => $normalizedPayments
        ];
    }

    // 6. إنشاء الحجز الأساسي وحفظ دفعاته
    private function createBookingAndPayments(Trip $trip, Request $request, array $parsed, array $costs, array $pay, $agentId)
    {
        $leadPassengerName = $parsed['passengerNamesArray'][0] ?? 'عميل محجوز بواسطة الوكالة';
        $leadPassengerId   = $parsed['passengerIdsArray'][0] ?? null;

        $booking = Booking::create([
            'agency_id'            => $agentId,
            'user_id'              => null,
            'trip_id'              => $trip->id,
            'customer_name'        => $leadPassengerName,
            'customer_email'       => $request->input('customer_email') ?? 'agency-guest@abur.com',
            'customer_phone'       => $request->input('customer_phone'),
            'customer_national_id' => $leadPassengerId,
            'room_type'            => $parsed['finalRoomType'],
            'room_count'           => $parsed['roomCount'],
            'payment_method'       => $pay['primaryMethod'],
            'card_name'            => (!$parsed['isLocked'] && in_array($pay['primaryMethod'], ['card', 'moyasar'])) ? $request->input('card_name') : null,
            'status'               => $pay['bookingStatus'],
            'payment_status'       => $pay['paymentStatus'],
            'total_amount'         => $costs['totalAmount'],
            'cash_paid'            => $pay['cashPaid'],
            'net_amount'           => $pay['netAgencyAmount'],
            'subtotal_amount'      => $costs['subtotalAmount'] ?? $costs['totalAmount'],
            'discount_amount'      => $costs['discountAmount'] ?? 0,
            'tax_amount'           => $costs['taxAmount'] ?? 0,
            'discount_code'        => $parsed['discountMeta']['code'] ?? $parsed['discountCode'] ?? null,
            'discount_code_id'     => $parsed['discountMeta']['discount_code_id'] ?? null,
            'booked_by_agency'     => true,
            'is_locked'            => $parsed['isLocked'],
            'room_quantities'      => $parsed['isLocked'] ? null : (is_array($parsed['roomQuantities']) ? $parsed['roomQuantities'] : json_decode($parsed['roomQuantities'], true)),
            'room_assignments'     => $parsed['isLocked'] ? null : (is_array($parsed['roomAssignments']) ? $parsed['roomAssignments'] : json_decode($parsed['roomAssignments'], true)),
            'passenger_meals'      => $parsed['isLocked'] ? null : (is_array($parsed['passengerMeals']) ? $parsed['passengerMeals'] : json_decode($parsed['passengerMeals'], true)),
            'extra_services'       => $parsed['isLocked'] ? null : $costs['seatServicesDetails'],
        ]);

        if ($request->hasFile('bank_receipt') && $pay['hasBankTransfer'] && !$parsed['isLocked']) {
            $path = $request->file('bank_receipt')->store('bank_receipts', 'public');
            $booking->update(['bank_receipt' => $path]);
        }

        // حفظ جميع الدفعات المتعددة في جدول booking_payments
        if (!$parsed['isLocked'] && !empty($pay['paymentsData'])) {
            foreach ($pay['paymentsData'] as $payment) {
                $booking->payments()->create([
                    'payment_method' => $payment['method'],
                    'amount'         => $payment['amount'],
                    'reference_id'   => $payment['reference'],
                ]);
            }
        }

        if (!$parsed['isLocked'] && !empty($costs['discountMeta'])) {
            try {
                app(DiscountCodeService::class)->applyOnBooking(
                    $booking,
                    $costs['discountMeta']['code'],
                    (float) ($costs['subtotalAmount'] ?? $costs['totalAmount']),
                    $request->input('customer_phone')
                );
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        foreach ($costs['customServices'] ?? [] as $custom) {
            BookingCustomService::create([
                'booking_id' => $booking->id,
                'agency_id' => $agentId,
                'name' => $custom['name'],
                'description' => $custom['description'] ?? null,
                'quantity' => $custom['quantity'],
                'unit_price' => $custom['unit_price'],
                'total_price' => $custom['total_price'],
            ]);
        }

        return $booking;
    }

    // 7. إنشاء المقاعد والتذاكر
    private function createSeatsAndGenerateTickets($booking, $trip, array $parsed, array $costs)
    {
        $mainSeats = [];
        $extraSeats = [];

        // خريطة أسماء الغرف المشتركة
        $sharedTypeNames = [
            'single' => 'فردية',
            'double' => 'ثنائية',
            'triple' => 'ثلاثية',
            'quad'   => 'رباعية',
        ];

        foreach ($parsed['seatsArray'] as $rawSeatNum) {
            $cleanSeat = $this->cleanSeatNum($rawSeatNum);
            $rType = $parsed['isLocked'] ? 'none' : ($parsed['roomAssignments'][$cleanSeat] ?? 'shared');
            $isExtra = in_array((string)$cleanSeat, $parsed['allExtraSeatNumbers']) || ($rType === 'additional');
            if ($isExtra) $extraSeats[] = ['cleanSeat' => $cleanSeat, 'rType' => 'additional'];
            else $mainSeats[] = ['cleanSeat' => $cleanSeat, 'rType' => $rType];
        }

        $createdMainSeats = [];
        $leadName = $parsed['passengerNamesArray'][0] ?? 'عميل محجوز';

        foreach ($mainSeats as $mainIdx => $item) {
            $cleanSeat = $item['cleanSeat'];
            $md = $costs['seatMealsDetails'][$cleanSeat] ?? ['bf' => 0, 'ln' => 0, 'dn' => 0, 'hasBf' => 0, 'hasLn' => 0, 'hasDn' => 0, 'package_name' => null, 'package_price' => 0];

            // جلب نوع الغرفة الفرعي من المصفوفة مع دعم المفاتيح بأكثر من صيغة
            $sharedSubtype = $item['rType'] === 'shared'
                ? ($costs['seatSharedTypes'][$cleanSeat] ?? $costs['seatSharedTypes'][(int)$cleanSeat] ?? $parsed['sharedRoomTypes'][$cleanSeat] ?? $parsed['sharedRoomTypes'][(int)$cleanSeat] ?? null)
                : null;

            // صياغة نص الغرفة مطابقة لعملية العميل: "غرفة مشتركة (فردية)"
            $formattedRoomType = $item['rType'];
            if ($item['rType'] === 'shared' && $sharedSubtype) {
                $formattedRoomType = 'غرفة مشتركة (' . ($sharedTypeNames[$sharedSubtype] ?? $sharedSubtype) . ')';
            }

            $details = $parsed['passengerDetails'][$cleanSeat] ?? $parsed['passengerDetails'][(string) $cleanSeat] ?? [];
            $beds = $costs['bedAssignments'][$cleanSeat] ?? [];
            $mealSnapshot = [
                'package_name' => $md['package_name'] ?? null,
                'package_price' => $md['package_price'] ?? 0,
                'breakfast' => (bool) $md['hasBf'],
                'lunch' => (bool) $md['hasLn'],
                'dinner' => (bool) $md['hasDn'],
                'days' => $trip->duration_days ?? null,
            ];

            $bookedSeat = $booking->bookingSeats()->create([
                'trip_id'               => $trip->id,
                'seat_number'           => $cleanSeat,
                'passenger_name'        => $parsed['passengerNamesArray'][$mainIdx] ?? $leadName,
                'gender'                => $parsed['passengerGendersArray'][$mainIdx] ?? 'male',
                'passenger_national_id' => $parsed['passengerIdsArray'][$mainIdx] ?? '',
                'nationality_id'        => $details['nationality_id'] ?? null,
                'identity_version'      => $details['identity_version'] ?? null,
                'identity_issue_place'  => $details['identity_issue_place'] ?? null,
                'identity_expiry_date'  => $details['identity_expiry_date'] ?? null,
                'date_of_birth'         => $details['date_of_birth'] ?? null,
                'companion_id'          => $details['companion_id'] ?? null,
                'bed_type'              => $beds['bed_type'] ?? ($details['bed_type'] ?? null),
                'extra_bed_quantity'    => $beds['extra_bed_quantity'] ?? 0,
                'extra_bed_unit_price'  => $beds['extra_bed_unit_price'] ?? 0,
                'meal_snapshot'         => $mealSnapshot,
                'room_type'             => $formattedRoomType,
                'with_breakfast'        => $md['hasBf'],
                'with_lunch'            => $md['hasLn'],
                'with_dinner'           => $md['hasDn'],
                'breakfast_price'       => $md['bf'],
                'lunch_price'           => $md['ln'],
                'dinner_price'          => $md['dn'],
                'extra_services'        => $costs['seatServicesDetails'][$cleanSeat] ?? [],
                'meal_options'          => ['package_name' => $md['package_name'], 'package_price' => $md['package_price']],
                'is_extra'              => 0,
                'parent_seat_id'        => null,
                'shared_room_type'      => $sharedSubtype,
            ]);

            $createdMainSeats[(string)$cleanSeat] = $bookedSeat;
            $this->generateSeatQRCode($bookedSeat, $booking, $trip, $cleanSeat);
        }

        $firstMainSeatObj = reset($createdMainSeats) ?: null;

        foreach ($extraSeats as $item) {
            $cleanSeat = $item['cleanSeat'];
            $parentSeatNum = null;
            foreach ($parsed['parentSeatsMap'] as $pSeat => $extras) {
                if (is_array($extras) && in_array((string)$cleanSeat, array_map([$this, 'cleanSeatNum'], $extras))) {
                    $parentSeatNum = (string)$this->cleanSeatNum($pSeat);
                    break;
                }
            }
            $parentBookedSeat = ($parentSeatNum && isset($createdMainSeats[$parentSeatNum])) ? $createdMainSeats[$parentSeatNum] : $firstMainSeatObj;
            $md = $costs['seatMealsDetails'][$cleanSeat] ?? ['bf' => 0, 'ln' => 0, 'dn' => 0, 'hasBf' => 0, 'hasLn' => 0, 'hasDn' => 0, 'package_name' => null, 'package_price' => 0];

            $extraBookedSeat = $booking->bookingSeats()->create([
                'trip_id'               => $trip->id,
                'seat_number'           => $cleanSeat,
                'passenger_name'        => ($parentBookedSeat ? $parentBookedSeat->passenger_name : $leadName) . ' (مقعد إضافي)',
                'gender'                => $parentBookedSeat ? $parentBookedSeat->gender : 'male',
                'passenger_national_id' => '',
                'room_type'             => 'additional',
                'with_breakfast'        => $md['hasBf'],
                'with_lunch'            => $md['hasLn'],
                'with_dinner'           => $md['hasDn'],
                'breakfast_price'       => $md['bf'],
                'lunch_price'           => $md['ln'],
                'dinner_price'          => $md['dn'],
                'extra_services'        => $costs['seatServicesDetails'][$cleanSeat] ?? [],
                'meal_options'          => ['package_name' => $md['package_name'], 'package_price' => $md['package_price']],
                'is_extra'              => 1,
                'parent_seat_id'        => $parentBookedSeat ? $parentBookedSeat->id : null,
            ]);

            $this->generateSeatQRCode($extraBookedSeat, $booking, $trip, $cleanSeat);
        }
    }

    // 8. إنشاء الـ QR Code (مفصولة لترتيب الكود)
    private function generateSeatQRCode($bookedSeat, $booking, $trip, $cleanSeat)
    {
        $qrPayload = [
            'ticket_no'  => 'TKT-' . $bookedSeat->id,
            'booking_no' => 'BK-' . $booking->id,
            'trip'       => $trip->title ?? $trip->id,
            'seat'       => $cleanSeat,
            'passenger'  => $bookedSeat->passenger_name,
            'id_number'  => $bookedSeat->passenger_national_id,
            'date'       => $trip->departure_at ? \Carbon\Carbon::parse($trip->departure_at)->format('Y-m-d') : now()->format('Y-m-d'),
        ];
        $image = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->encoding('UTF-8')->size(300)->generate(json_encode($qrPayload, JSON_UNESCAPED_UNICODE));
        $fullPath = 'qrcodes/bookings/' . $booking->id . '/ticket_' . $bookedSeat->id . '.svg';
        \Illuminate\Support\Facades\Storage::disk('public')->put($fullPath, $image);
        $bookedSeat->update(['qr_code_path' => $fullPath]);
    }

    // 9. إنهاء العمليات وتحديث سجل التدقيق مع التفاصيل الدقيقة للدفع المتعدد
    private function finalizeTripAndLogAudit($booking, $trip, array $parsed, array $pay, array $costs, $agentId)
    {
        if (!$parsed['isLocked']) $trip->room_options = $costs['roomOptions'];

        $this->syncTripAvailableSeats($trip);
        $trip->openOverflowIfFull();


        $auditDesc = $booking->is_locked
            ? "🔒 قفل مقاعد للرحلة: ({$trip->title}) برقم حجز #{$booking->id}."
            : "✨ إضافة حجز جديد للرحلة: ({$trip->title}) برقم حجز #{$booking->id}.";

        if (!$booking->is_locked && $pay['cashPaid'] > 0) {
            // خريطة لترجمة وسائط الدفع إلى العربية
            $methodTranslations = [
                'cash'          => 'كاش',
                'bank_transfer' => 'تحويل بنكي',
                'bank'          => 'تحويل بنكي',
                'moyasar'       => 'دفع إلكتروني (ميسر)',
                'card'          => 'بطاقة مدى/ائتمان',
                'pos'           => 'جهاز نقاط البيع (POS)',
                'balance'       => 'من الرصيد',
            ];

            $paymentDetailsList = [];
            if (!empty($pay['paymentsData'])) {
                foreach ($pay['paymentsData'] as $p) {
                    $translatedMethod = $methodTranslations[$p['method']] ?? $p['method'];
                    $refInfo = !empty($p['reference']) ? " [مرجع: {$p['reference']}]" : '';
                    $paymentDetailsList[] = "{$translatedMethod}: {$p['amount']} ر.س{$refInfo}";
                }
            }

            if (!empty($paymentDetailsList)) {
                $auditDesc .= " | 💳 تفاصيل الدفع: (" . implode(' + ', $paymentDetailsList) . ")";
            } else {
                $translatedMethod = $methodTranslations[$pay['primaryMethod']] ?? $pay['primaryMethod'];
                $auditDesc .= " | 💳 تم دفع: {$pay['cashPaid']} ر.س بواسطة ({$translatedMethod})";
            }
        }

        \App\Models\AuditLog::create([
            'agency_id'      => $agentId,
            'trip_id'        => $trip->id,
            'action_type'    => $booking->is_locked ? 'lock' : 'create',
            'category'       => 'booking',
            'amount_changed' => $costs['totalAmount'],
            'seats_changed'  => count($parsed['seatsArray']),
            'description'    => $auditDesc,
            'operator_name'  => $this->getOperatorName(),

        ]);
    }

    // ================== دوال مساعدة صغيرة للـ Parsing ==================
    private function cleanSeatNum($val)
    {
        if (is_null($val)) return null;
        return trim(str_replace(['"', "'", '\\', ' ', '[', ']'], '', (string)$val));
    }

    private function parseIncomingData($source): array
    {
        if (empty($source)) return [];
        $result = [];
        if (is_array($source)) {
            $result = array_values($source);
        } else {
            $decoded = json_decode($source, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $result = array_values($decoded);
            } else {
                $result = collect(explode(',', $source))->map(fn($item) => trim($item))->filter(fn($val) => $val !== '')->values()->all();
            }
        }
        return array_map([$this, 'cleanSeatNum'], $result);
    }

    private function parseJsonOrArray($input)
    {
        if (is_array($input)) return $input;
        if ((is_string($input) || is_numeric($input)) && !empty($input)) {
            $decoded = json_decode((string)$input, true);
            return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : $input;
        }
        return [];
    }
}