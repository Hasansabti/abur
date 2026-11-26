<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;

// Models
use App\Models\Agency;
use App\Models\Vehicle;
use App\Models\BookingSeat;
use App\Models\Driver;
use App\Models\Staff;
use App\Models\Trip;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\PrivateTripRequest;


// Facades & Utilities
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;


class AgencyTripController extends Controller
{
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
    //عرض الرحلات
    public function indexTrip(Request $request)
    {
        $agencyId = $this->getAgencyId();
        $search = $request->query('search');

        // بناء استعلام الرحلات
        $query = Trip::where('agency_id', $agencyId)
            ->where('is_overflow_standby', false)
            ->with('overflowTrip')
            ->withCount(['bookingSeats' => function ($query) {
                $query->where('room_type', '!=', 'blocked')
                    ->where('passenger_name', 'not like', '%مغلق%')
                    ->where('passenger_name', 'not like', '%حظر%')
                    ->where('passenger_name', 'not like', '%إغلاق%')
                    ->where('seat_number', 'not like', 'H%');
            }]);

        // إضافة فلتر البحث (حسب اسم الرحلة أو المعرف ID)
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        // جلب النتائج مع الباجينيشن (10 رحلات لكل صفحة) والاحتفاظ بـ Query String
        $trips = $query->latest()->paginate(10)->withQueryString();

        // تحويل وتنسيق عناصر الصفحة الحالية بدون كسر كائن الـ Paginator
        $trips->through(function ($trip) {
            $bookedCount = $trip->booking_seats_count ?? 0;
            $availableSeats = $trip->total_seats - $bookedCount;

            return (object)[
                'id'                   => $trip->id,
                'agency_id'            => $trip->agency_id,
                'title'                => $trip->title,
                'date'                 => Carbon::parse($trip->departure_at)->format('Y-m-d'),
                'departure_at'         => Carbon::parse($trip->departure_at)->format('h:i A'),
                'days'                 => $trip->duration_days ?? 1,
                'price'                => $trip->price,
                'booked_count'         => $bookedCount,
                'available_seats'      => $availableSeats < 0 ? 0 : $availableSeats,
                'total_seats'          => $trip->total_seats,
                'status'               => $trip->status,
                'transport_type'       => $trip->transport_type,
                'allows_shared_rooms'  => $trip->allows_shared_rooms,
                'allow_to_add_seat'    => $trip->allow_to_add_seat,
                'is_private'           => $trip->is_private,
                'is_security_approved' => $trip->is_security_approved,
                'auto_split'           => $trip->auto_split,
                'room_options'         => is_array($trip->room_options) ? $trip->room_options : (json_decode($trip->room_options, true) ?: []),

                'overflow_enabled' => $trip->overflow_enabled,
                'parent_trip_id'   => $trip->parent_trip_id,
                'overflowTrip'     => $trip->overflowTrip,
            ];
        });

        $trip = null;
        $booked_seats_details = [];
        $booked_seats = [];
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
                $bookedSeatsData = BookingSeat::with('booking')->where('trip_id', $selectedId)->get();
                $booked_seats = $bookedSeatsData->pluck('seat_number')->toArray();
                $nationalIds = $bookedSeatsData->pluck('passenger_national_id')->filter()->unique()->toArray();

                $usersMap = User::whereIn('national_id', $nationalIds)->get()->keyBy('national_id');

                $totalSeats = $trip->total_seats ?? 50;
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

                $femaleCount = 0;
                $maleCount = 0;

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

                    $booked_seats_details[$seat->seat_number] = [
                        'id'               => $seat->id,
                        'name'             => $seat->passenger_name ?? 'راكب مسجل',
                        'identity'         => $seat->passenger_national_id ?? '',
                        'gender'           => $gender,
                        'assignment'       => $seat->room_type ?? 'shared',
                        'package_name'     => $packageName,
                        'meals'            => $seatMeals,
                        'qr_code'          => $seat->qr_code_path ? asset('storage/' . $seat->qr_code_path) : null,
                        'booking_price'    => floatval($seat->booking->total_amount ?? 0),
                        'base_price'       => floatval($trip->price ?? 0),
                        'breakfast_price'  => floatval($seat->with_breakfast ? ($seat->breakfast_price ?? 0) : 0),
                        'lunch_price'      => floatval($seat->with_lunch ? ($seat->lunch_price ?? 0) : 0),
                        'dinner_price'     => floatval($seat->with_dinner ? ($seat->dinner_price ?? 0) : 0),
                    ];
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

                $rawFeatures = is_array($trip->features) ? $trip->features : json_decode($trip->features, true);
                $features = !empty($rawFeatures) ? array_unique($rawFeatures) : [];
            }
        }

        return view('agency.trips.index', [
            'trips'                => $trips,
            'trip'                 => $trip,
            'booked_seats_details' => $booked_seats_details,
            'meal_options'         => $mealOptions,
            'meal_prices'          => $mealPrices,
            'room_options'         => $roomOptions,
            'booked_seats'         => $booked_seats,
            'features'             => $features,
            'halfSeats'            => $halfSeats,
            'layoutConfig'         => $layoutConfig,
        ]);
    }


    //صفحة انشاء الرحلة
    public function create()
    {
        $agencyId = $this->getAgencyId() ?? 1;

        $drivers = Driver::where('agency_id', $agencyId)->where('status', 'available')->latest()->get();
        $vehicles = Vehicle::where('agency_id', $agencyId)->where('status', 'active')->latest()->get();

        $agency = Agency::with('bankAccounts')->find($agencyId);
        $bankAccounts = $agency ? $agency->bankAccounts : collect();

        $trip = new Trip();

        return view('agency.trips.create', compact('drivers', 'vehicles', 'trip', 'bankAccounts'));
    }

    //معالجة انشاء الرحلة
    public function store(Request $request)
    {
        // فحص الزر المضغوط: هل هو مسودة؟
        $isDraft = $request->input('action') === 'draft';
        $isConditional = $request->boolean('is_conditional');

        $data = $request->validate([
            'agency_id'               => ['nullable', 'exists:agencies,id'],
            'user_id'                 => ['nullable', 'exists:users,id'], // 🆕
            'private_trip_request_id' => ['nullable', 'exists:private_trip_requests,id'], // 🆕
            'title'                   => ['required', 'string', 'max:255'],
            'route_type'              => [$isDraft ? 'nullable' : 'required', 'in:domestic,international'],

            'from_city'               => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'to_city'                 => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'transport_type'          => [$isDraft ? 'nullable' : 'required', 'in:bus,flight'],
            'trip_type'               => [$isDraft ? 'nullable' : 'required', 'in:one_way,round_trip'],
            'departure_at'            => [$isDraft ? 'nullable' : 'required', 'date'],
            'arrival_at'              => ['nullable', 'date', 'after_or_equal:departure_at'],
            'return_at'               => ['nullable', 'date', 'after_or_equal:departure_at'],

            'is_conditional'          => ['nullable', 'boolean'],
            'min_seats_required'      => [
                Rule::requiredIf(!$isDraft && $isConditional),
                'nullable',
                'integer',
                'min:1'
            ],
            'threshold_deadline_at'   => [
                Rule::requiredIf(!$isDraft && $isConditional),
                'nullable',
                'date',
                'before:departure_at'
            ],

            'total_seats'             => [$isDraft ? 'nullable' : 'required', 'integer', 'min:1'],
            'available_seats'         => ['nullable', 'integer', 'min:0', 'lte:total_seats'],
            'price'                   => [$isDraft ? 'nullable' : 'required', 'numeric', 'min:0'],
            'cover_image'             => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],

            'duration_days'           => [$isDraft ? 'nullable' : 'required', 'integer', 'min:1'],
            'vehicle_model'           => ['nullable', 'string', 'max:255'],
            'auto_split'              => ['nullable'],
            'allows_shared_rooms'     => ['nullable'],
            'allow_to_add_seat'       => ['nullable'],
            'is_private'              => ['nullable'],
            'is_security_approved'    => ['nullable'],
            'features'                => ['nullable', 'array'],
            'notes'                   => ['nullable', 'string'],

            'driver_id'               => ['nullable', 'exists:drivers,id'],
            'vehicle_id'              => ['nullable', 'exists:vehicles,id'],

            'allow_accommodation_only'    => ['nullable', 'boolean'],
            'accommodation_only_discount' => ['nullable', 'numeric', 'min:0'],

            'bank_names'              => ['nullable', 'array'],
            'bank_names.*'            => ['nullable', 'string', 'max:255'],
            'iban_numbers'            => ['nullable', 'array'],
            'iban_numbers.*'          => ['nullable', 'string', 'max:35'],
            'bank_images'             => ['nullable', 'array'],
            'bank_images.*'           => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'account_number'          => ['nullable', 'array'],
            'account_number.*'        => ['nullable', 'string', 'max:255'],

            'breakfast_price'         => ['nullable', 'numeric', 'min:0'],
            'lunch_price'             => ['nullable', 'numeric', 'min:0'],
            'dinner_price'            => ['nullable', 'numeric', 'min:0'],
            'price_bf_ln'             => ['nullable', 'numeric', 'min:0'],
            'price_ln_dn'             => ['nullable', 'numeric', 'min:0'],
            'price_bf_dn'             => ['nullable', 'numeric', 'min:0'],
            'price_all_meals'         => ['nullable', 'numeric', 'min:0'],

            'room_options'                             => ['nullable', 'array'],
            'room_options.*.price'                     => ['nullable', 'numeric', 'min:0'],
            'room_options.*.count'                     => ['nullable', 'integer', 'min:0'],
            'room_options.*.shared'                    => ['nullable', 'boolean'],
            'room_options.*.packages'                  => ['nullable', 'array'],
            'room_options.*.packages.*.name'           => ['nullable', 'string', 'max:255'],
            'room_options.*.packages.*.price'          => ['nullable', 'numeric', 'min:0'],
            'room_options.*.packages.*.meals'          => ['nullable', 'array'],
            'room_options.*.packages.*.meals.breakfast' => ['nullable', 'boolean', 'in:0,1'],
            'room_options.*.packages.*.meals.lunch'    => ['nullable', 'boolean', 'in:0,1'],
            'room_options.*.packages.*.meals.dinner'   => ['nullable', 'boolean', 'in:0,1'],

            'extra_services'               => ['nullable', 'array'],
            'extra_services.*.name'        => ['required_with:extra_services', 'string', 'max:255'],
            'extra_services.*.price'       => ['nullable', 'numeric', 'min:0'],
            'extra_services.*.description' => ['nullable', 'string', 'max:500'],


            'overflow_enabled'     => ['nullable', 'boolean'],
            'overflow_total_seats' => [
                Rule::requiredIf(!$isDraft && $request->boolean('overflow_enabled') && $request->input('transport_type') === 'bus'),
                'nullable',
                'integer',
                'min:1',
            ],
            'overflow_vehicle_id'  => ['nullable', 'exists:vehicles,id', 'different:vehicle_id'],
            'overflow_driver_id'   => ['nullable', 'exists:drivers,id', 'different:driver_id'],
        ]);

        $data['overflow_enabled'] = $request->boolean('overflow_enabled') && ($data['transport_type'] ?? '') === 'bus';
        unset($data['overflow_total_seats'], $data['overflow_vehicle_id'], $data['overflow_driver_id']);

        // تحديد حالة الرحلة بناءً على الزر
        $data['status'] = $isDraft ? 'draft' : 'pending';

        // التحقق من صلاحية الوكالة وتعيين الـ ID
        if (empty($data['agency_id'])) {
            if (auth('agency')->check()) {
                $data['agency_id'] = auth('agency')->user()->id;
            } else {
                return $request->wantsJson() || $request->is('api/*')
                    ? response()->json(['message' => 'عذراً، يجب تسجيل الدخول أولاً.'], 401)
                    : redirect()->back()->withErrors(['message' => 'عذراً، يجب تسجيل الدخول أولاً.']);
            }
        }

        $data['auto_split']           = $request->has('auto_split');
        $data['allows_shared_rooms'] = $request->has('allows_shared_rooms');
        $data['is_security_approved'] = $request->has('is_security_approved');
        $data['allow_to_add_seat']    = $request->has('allow_to_add_seat');

        // 🆕 جعل الرحلة خاصة إذا تم تحديد خيار is_private أو إذا تم ربطها بطلب/عميل
        $data['is_private'] = $request->has('is_private') || !empty($data['user_id']) || !empty($data['private_trip_request_id']);

        // 🎯 معالجة الرحلة المشروطة (تجميع المسافرين)
        $data['is_conditional'] = $request->has('is_conditional');
        if (!$data['is_conditional']) {
            $data['min_seats_required'] = null;
            $data['threshold_deadline_at'] = null;
        }

        // 🔄 معالجة الحقول الخاصة بالسكن
        $data['allow_accommodation_only'] = $request->has('allow_accommodation_only');
        $data['accommodation_only_discount'] = $request->input('accommodation_only_discount') ?? 0.00;

        $data['available_seats'] = $data['available_seats'] ?? ($data['total_seats'] ?? 0);

        // 🟢 إضافة حساب split_boundary تلقائياً
        $totalSeats = intval($data['total_seats'] ?? 0);
        $halfSeats = intval($totalSeats / 2);

        if (($data['transport_type'] ?? '') === 'bus') {
            $data['split_boundary'] = $halfSeats + 4;
        } else {
            $data['split_boundary'] = $halfSeats;
        }

        // معالجة تصفير أسعار الوجبات وباقاتها تلقائياً بناء على التفعيل
        $bfActive = $request->boolean('bf_price_active');
        $lnActive = $request->boolean('ln_price_active');
        $dnActive = $request->boolean('dn_price_active');

        if (!$bfActive) {
            $data['breakfast_price'] = null;
            $data['price_bf_ln'] = null;
            $data['price_bf_dn'] = null;
            $data['price_all_meals'] = null;
        }
        if (!$lnActive) {
            $data['lunch_price'] = null;
            $data['price_bf_ln'] = null;
            $data['price_ln_dn'] = null;
            $data['price_all_meals'] = null;
        }
        if (!$dnActive) {
            $data['dinner_price'] = null;
            $data['price_ln_dn'] = null;
            $data['price_bf_dn'] = null;
            $data['price_all_meals'] = null;
        }

        // بناء هيكل JSON الغرف وباقات الوجبات
        $roomTypes = ['single', 'double', 'triple', 'quad'];
        $processedRooms = [];

        foreach ($roomTypes as $type) {
            $roomPackages = [];
            if ($request->has("room_options.{$type}.packages") && is_array($request->input("room_options.{$type}.packages"))) {
                foreach ($request->input("room_options.{$type}.packages") as $pkg) {
                    $roomPackages[] = [
                        'name'  => $pkg['name'] ?? null,
                        'price' => (float) ($pkg['price'] ?? 0.0),
                        'meals' => [
                            'breakfast' => (bool) ($pkg['meals']['breakfast'] ?? false),
                            'lunch'     => (bool) ($pkg['meals']['lunch'] ?? false),
                            'dinner'    => (bool) ($pkg['meals']['dinner'] ?? false),
                        ]
                    ];
                }
            }
            $processedRooms[$type] = [
                'price'    => (float) ($request->input("room_options.{$type}.price") ?? 0.0),
                'count'    => (int) ($request->input("room_options.{$type}.count") ?? 0),
                'shared'   => !empty($data['allows_shared_rooms']) && $request->boolean("room_options.{$type}.shared"),
                'packages' => $roomPackages
            ];
        }
        $data['room_options'] = $processedRooms;
        if (!$isDraft && ($sharedError = $this->sharedRoomsError($processedRooms, (bool) $data['allows_shared_rooms']))) {
            return redirect()->back()->withInput()->withErrors(['room_options' => $sharedError]);
        }

        // بناء هيكل JSON الوجبات العامة
        $data['meal_options'] = [
            'individual' => [
                'breakfast' => ['active' => $bfActive, 'price' => !is_null($data['breakfast_price']) ? (float) $data['breakfast_price'] : 0.0],
                'lunch'     => ['active' => $lnActive, 'price' => !is_null($data['lunch_price']) ? (float) $data['lunch_price'] : 0.0],
                'dinner'    => ['active' => $dnActive, 'price' => !is_null($data['dinner_price']) ? (float) $data['dinner_price'] : 0.0],
            ],
            'packages' => [
                'bf_ln' => !is_null($data['price_bf_ln']) ? (float) $data['price_bf_ln'] : null,
                'ln_dn' => !is_null($data['price_ln_dn']) ? (float) $data['price_ln_dn'] : null,
                'bf_dn' => !is_null($data['price_bf_dn']) ? (float) $data['price_bf_dn'] : null,
                'all'   => !is_null($data['price_all_meals']) ? (float) $data['price_all_meals'] : null,
            ]
        ];

        unset($data['breakfast_price'], $data['lunch_price'], $data['dinner_price'], $data['price_bf_ln'], $data['price_ln_dn'], $data['price_bf_dn'], $data['price_all_meals']);

        // معالجة الخدمات الإضافية
        $formattedExtraServices = [];
        if ($request->has('extra_services') && is_array($request->input('extra_services'))) {
            foreach ($request->input('extra_services') as $service) {
                if (!empty($service['name'])) {
                    $formattedExtraServices[] = [
                        'name'        => trim($service['name']),
                        'price'       => isset($service['price']) && $service['price'] !== '' ? (float) $service['price'] : 0.00,
                        'description' => !empty($service['description']) ? trim($service['description']) : null,
                    ];
                }
            }
        }
        $data['extra_services'] = $formattedExtraServices;

        // معالجة الحسابات البنكية
        $formattedBanks = [];
        if ($request->has('bank_names')) {
            foreach ($request->bank_names as $index => $name) {
                if (!empty($name)) {
                    $imagePath = null;
                    if ($request->hasFile("bank_images.{$index}")) {
                        $imagePath = $request->file("bank_images.{$index}")->store('banks_qr', 'public');
                    }
                    $formattedBanks[] = [
                        'bank_name'      => $name,
                        'iban'           => $request->iban_numbers[$index] ?? '',
                        'qr_code'        => $imagePath,
                        'account_number' => $request->account_number[$index] ?? '',
                    ];
                }
            }
        }
        $data['bank_accounts'] = $formattedBanks;
        unset($data['bank_names'], $data['iban_numbers'], $data['bank_images'], $data['account_number']);

        // معالجة صورة الغلاف
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('trips_covers', 'public');
        } else {
            unset($data['cover_image']);
        }

        if (!$isDraft) {
            $subError = app(\App\Services\SubscriptionService::class)
                ->publishError(Agency::findOrFail($data['agency_id']), (bool) $data['overflow_enabled']);

            if ($subError) {
                return redirect()->back()->withInput()->withErrors(['subscription' => $subError]);
            }
        }

        // إنشاء الرحلة
        $trip = Trip::create($data)->load('agency');
        $this->syncOverflowTrip($trip, $request);

        // 🆕 تحديث حالة طلب الرحلة الخاصة لـ "مقبولة" إذا كانت مرتبطة بطلب خاص
        if (!empty($data['private_trip_request_id'])) {
            \App\Models\PrivateTripRequest::where('id', $data['private_trip_request_id'])
                ->update(['status' => 'accepted']);
        }

        $agencyId = $trip->agency_id ?? $this->getAgencyId();

        // تسجيل العملية في AuditLog
        AuditLog::create([
            'agency_id'      => $agencyId,
            'trip_id'        => $trip->id,
            'action_type'    => 'create',
            'category'       => 'trips',
            'amount_changed' => 0,
            'seats_changed'  => 0,
            'description'    => "تم إنشاء الرحلة بنجاح " . ($isDraft ? "كمسودة 📁" : "ونشرها فوراً ✨") . ": العنوان ({$trip->title})، المسار من (" . ($trip->from_city ?? 'غير محدد') . ") إلى (" . ($trip->to_city ?? 'غير محدد') . ").",
            'operator_name'  => $this->getOperatorName(),
        ]);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $isDraft ? 'تم حفظ الرحلة كمسودة 📁' : 'تم نشر الرحلة بنجاح ✨',
                'trip'    => $trip
            ], 201);
        }

        $msg = $isDraft ? 'تم حفظ الرحلة في المسودة بنجاح 📁' : 'تم نشر الرحلة بنجاح ✨';
        return redirect()->route('agency.dashboard')->with('success', $msg);
    }
    //تحديث الرحلة
    public function updateTrip(Request $request, $id)
    {
        $agencyId = $this->getAgencyId();
        $trip = Trip::where('id', $id)->where('agency_id', $agencyId)->firstOrFail();
        $isDraft = $request->input('action') === 'draft';

        $data = $request->validate([
            'agency_id'             => ['nullable', 'exists:agencies,id'],
            'user_id'               => ['nullable', 'exists:users,id'], // 🆕
            'private_trip_request_id' => ['nullable', 'exists:private_trip_requests,id'], // 🆕
            'title'                 => ['required', 'string', 'max:255'],

            'route_type'            => [$isDraft ? 'nullable' : 'required', 'in:domestic,international'],

            'from_city'             => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'to_city'               => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'transport_type'        => [$isDraft ? 'nullable' : 'required', 'in:bus,flight'],
            'trip_type'             => [$isDraft ? 'nullable' : 'required', 'in:one_way,round_trip'],
            'departure_at'          => [$isDraft ? 'nullable' : 'required', 'date'],
            'arrival_at'            => ['nullable', 'date', 'after_or_equal:departure_at'],
            'return_at'             => ['nullable', 'date', 'after_or_equal:departure_at'],

            'is_conditional'        => ['nullable', 'boolean'],
            'min_seats_required'    => [$isDraft ? 'nullable' : 'required_if:is_conditional,1,true', 'nullable', 'integer', 'min:1'],
            'threshold_deadline_at' => [$isDraft ? 'nullable' : 'required_if:is_conditional,1,true', 'nullable', 'date', 'before:departure_at'],

            'total_seats'           => [$isDraft ? 'nullable' : 'required', 'integer', 'min:1'],
            'available_seats'       => ['nullable', 'integer', 'min:0', 'lte:total_seats'],
            'price'                 => [$isDraft ? 'nullable' : 'required', 'numeric', 'min:0'],
            'cover_image'           => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'duration_days'         => [$isDraft ? 'nullable' : 'required', 'integer', 'min:1'],
            'vehicle_model'         => ['nullable', 'string', 'max:255'],
            'auto_split'            => ['nullable'],
            'allows_shared_rooms'   => ['nullable'],
            'allow_to_add_seat'     => ['nullable'],
            'is_private'            => ['nullable'],
            'is_security_approved'  => ['nullable'],
            'features'              => ['nullable', 'array'],
            'notes'                 => ['nullable', 'string'],
            'driver_id'             => ['nullable', 'exists:drivers,id'],
            'vehicle_id'            => ['nullable', 'exists:vehicles,id'],

            'allow_accommodation_only'    => ['nullable', 'boolean'],
            'accommodation_only_discount' => ['nullable', 'numeric', 'min:0'],

            'bank_names'            => ['nullable', 'array'],
            'bank_names.*'          => ['nullable', 'string', 'max:255'],
            'iban_numbers'          => ['nullable', 'array'],
            'iban_numbers.*'        => ['nullable', 'string', 'max:35'],
            'bank_images'           => ['nullable', 'array'],
            'bank_images.*'         => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'account_number'        => ['nullable', 'array'],
            'account_number.*'      => ['nullable', 'string', 'max:255'],

            'breakfast_price'       => ['nullable', 'numeric', 'min:0'],
            'lunch_price'           => ['nullable', 'numeric', 'min:0'],
            'dinner_price'          => ['nullable', 'numeric', 'min:0'],
            'price_bf_ln'           => ['nullable', 'numeric', 'min:0'],
            'price_ln_dn'           => ['nullable', 'numeric', 'min:0'],
            'price_bf_dn'           => ['nullable', 'numeric', 'min:0'],
            'price_all_meals'       => ['nullable', 'numeric', 'min:0'],

            'room_options'                                 => ['nullable', 'array'],
            'room_options.*.price'                         => ['nullable', 'numeric', 'min:0'],
            'room_options.*.count'                         => ['nullable', 'integer', 'min:0'],
            'room_options.*.shared'                        => ['nullable', 'boolean'],
            'room_options.*.packages'                      => ['nullable', 'array'],
            'room_options.*.packages.*.name'               => ['nullable', 'string', 'max:255'],
            'room_options.*.packages.*.price'              => ['nullable', 'numeric', 'min:0'],
            'room_options.*.packages.*.meals'              => ['nullable', 'array'],
            'room_options.*.packages.*.meals.breakfast'    => ['nullable', 'boolean', 'in:0,1'],
            'room_options.*.packages.*.meals.lunch'        => ['nullable', 'boolean', 'in:0,1'],
            'room_options.*.packages.*.meals.dinner'       => ['nullable', 'boolean', 'in:0,1'],

            'extra_services'             => ['nullable', 'array'],
            'extra_services.*.name'      => ['required_with:extra_services', 'string', 'max:255'],
            'extra_services.*.price'     => ['nullable', 'numeric', 'min:0'],
            'extra_services.*.description' => ['nullable', 'string', 'max:500'],

            'overflow_enabled'      => ['nullable', 'boolean'],
            'overflow_total_seats'  => [
                Rule::requiredIf(!$isDraft && $request->boolean('overflow_enabled') && $request->input('transport_type') === 'bus'),
                'nullable',
                'integer',
                'min:1',
            ],
            'overflow_vehicle_id' => ['nullable', 'exists:vehicles,id', function ($attribute, $value, $fail) use ($request) {
                if ($value && $value == $request->input('vehicle_id')) {
                    $fail('الباص الثاني لازم يكون غير باص الرحلة الأولى.');
                }
            }],
            'overflow_driver_id' => ['nullable', 'exists:drivers,id', function ($attribute, $value, $fail) use ($request) {
                if ($value && $value == $request->input('driver_id')) {
                    $fail('كابتن الباص الثاني لازم يكون غير كابتن الرحلة الأولى.');
                }
            }],
        ]);

        // إذا الباص الثاني تفعّل فعلاً وصار له حجوزات، نبقي الخيار مفعّلاً ولا نلمسه
        $hasActiveOverflow = Trip::where('parent_trip_id', $trip->id)->where('is_overflow_standby', false)->exists();

        $data['overflow_enabled'] = $hasActiveOverflow
            ? true
            : ($request->boolean('overflow_enabled') && ($data['transport_type'] ?? $trip->transport_type) === 'bus');

        if (!$isDraft) {
            $subError = app(\App\Services\SubscriptionService::class)->publishError(
                Agency::findOrFail($trip->agency_id),
                $data['overflow_enabled'] && !$trip->overflow_enabled,   // فقط لو يفعّله الحين
                $trip->status === 'draft'                            // يحسب كرحلة جديدة فقط لو كانت مسودة
            );

            if ($subError) {
                return redirect()->back()->withInput()->withErrors(['subscription' => $subError]);
            }
        }

        unset($data['overflow_total_seats'], $data['overflow_vehicle_id'], $data['overflow_driver_id']);

        if ($isDraft) {
            $data['status'] = 'draft';
        } else {
            $data['status'] = ($trip->status === 'draft') ? 'pending' : $trip->status;
        }

        $data['auto_split']            = $request->has('auto_split');
        $data['allows_shared_rooms']   = $request->has('allows_shared_rooms');
        $data['allow_to_add_seat']     = $request->has('allow_to_add_seat');

        // 🆕 جعل الرحلة خاصة إذا تم تحديد الخيار أو ربطها بشرط عميل/طلب خاص
        $data['is_private']            = $request->has('is_private') || !empty($data['user_id']) || !empty($data['private_trip_request_id']);
        $data['is_security_approved']  = $request->has('is_security_approved');

        $data['is_conditional'] = $request->has('is_conditional');
        if (!$data['is_conditional']) {
            $data['min_seats_required'] = null;
            $data['threshold_deadline_at'] = null;
        }

        $data['allow_accommodation_only'] = $request->has('allow_accommodation_only');
        $data['accommodation_only_discount'] = $request->input('accommodation_only_discount') ?? 0.00;

        $bookedSeatsCount = BookingSeat::where('trip_id', $trip->id)->count();
        $newTotalSeats = isset($data['total_seats']) ? (int)$data['total_seats'] : ($trip->total_seats ?? 50);

        // ✅ تم التصحيح (إضافة علامة الناقص)
        $data['available_seats'] = max(0, $newTotalSeats - $bookedSeatsCount);

        $transportType = $data['transport_type'] ?? $trip->transport_type;
        $halfSeats = intval($newTotalSeats / 2);
        if ($transportType === 'bus') {
            $data['split_boundary'] = $halfSeats + 4;
        } else {
            $data['split_boundary'] = $halfSeats;
        }

        $bfActive = $request->boolean('bf_price_active');
        $lnActive = $request->boolean('ln_price_active');
        $dnActive = $request->boolean('dn_price_active');

        if (!$bfActive) {
            $data['breakfast_price'] = null;
            $data['price_bf_ln'] = null;
            $data['price_bf_dn'] = null;
            $data['price_all_meals'] = null;
        }
        if (!$lnActive) {
            $data['lunch_price'] = null;
            $data['price_bf_ln'] = null;
            $data['price_ln_dn'] = null;
            $data['price_all_meals'] = null;
        }
        if (!$dnActive) {
            $data['dinner_price'] = null;
            $data['price_ln_dn'] = null;
            $data['price_bf_dn'] = null;
            $data['price_all_meals'] = null;
        }

        $roomTypes = ['single', 'double', 'triple', 'quad'];
        $processedRooms = [];

        foreach ($roomTypes as $type) {
            $roomPackages = [];
            if ($request->has("room_options.{$type}.packages") && is_array($request->input("room_options.{$type}.packages"))) {
                foreach ($request->input("room_options.{$type}.packages") as $pkg) {
                    $roomPackages[] = [
                        'name'  => $pkg['name'] ?? null,
                        'price' => (float) ($pkg['price'] ?? 0.0),
                        'meals' => [
                            'breakfast' => (bool) ($pkg['meals']['breakfast'] ?? false),
                            'lunch'     => (bool) ($pkg['meals']['lunch'] ?? false),
                            'dinner'    => (bool) ($pkg['meals']['dinner'] ?? false),
                        ]
                    ];
                }
            }
            $processedRooms[$type] = [
                'price'    => (float) ($request->input("room_options.{$type}.price") ?? 0.0),
                'count'    => (int) ($request->input("room_options.{$type}.count") ?? 0),
                'shared'   => !empty($data['allows_shared_rooms']) && $request->boolean("room_options.{$type}.shared"),
                'packages' => $roomPackages
            ];
        }
        $data['room_options'] = $processedRooms;
        if (!$isDraft && ($sharedError = $this->sharedRoomsError($processedRooms, (bool) $data['allows_shared_rooms']))) {
            return redirect()->back()->withInput()->withErrors(['room_options' => $sharedError]);
        }

        $data['meal_options'] = [
            'individual' => [
                'breakfast' => ['active' => $bfActive, 'price' => !is_null($data['breakfast_price']) ? (float) $data['breakfast_price'] : 0.0],
                'lunch'     => ['active' => $lnActive, 'price' => !is_null($data['lunch_price']) ? (float) $data['lunch_price'] : 0.0],
                'dinner'    => ['active' => $dnActive, 'price' => !is_null($data['dinner_price']) ? (float) $data['dinner_price'] : 0.0],
            ],
            'packages' => [
                'bf_ln' => !is_null($data['price_bf_ln']) ? (float) $data['price_bf_ln'] : null,
                'ln_dn' => !is_null($data['price_ln_dn']) ? (float) $data['price_ln_dn'] : null,
                'bf_dn' => !is_null($data['price_bf_dn']) ? (float) $data['price_bf_dn'] : null,
                'all'   => !is_null($data['price_all_meals']) ? (float) $data['price_all_meals'] : null,
            ]
        ];

        unset($data['breakfast_price'], $data['lunch_price'], $data['dinner_price'], $data['price_bf_ln'], $data['price_ln_dn'], $data['price_bf_dn'], $data['price_all_meals']);

        $formattedBanks = [];
        $oldBankAccounts = is_array($trip->bank_accounts) ? $trip->bank_accounts : (json_decode($trip->bank_accounts ?? '[]', true) ?? []);

        if ($request->has('bank_names')) {
            foreach ($request->bank_names as $index => $name) {
                if (!empty($name)) {
                    $imagePath = $oldBankAccounts[$index]['qr_code'] ?? null;
                    if ($request->hasFile("bank_images.{$index}")) {
                        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                            Storage::disk('public')->delete($imagePath);
                        }
                        $imagePath = $request->file("bank_images.{$index}")->store('banks_qr', 'public');
                    }
                    $formattedBanks[] = [
                        'bank_name'      => $name,
                        'iban'           => $request->iban_numbers[$index] ?? '',
                        'qr_code'        => $imagePath,
                        'account_number' => $request->account_number[$index] ?? '',
                    ];
                }
            }
        }
        $data['bank_accounts'] = $formattedBanks;
        unset($data['bank_names'], $data['iban_numbers'], $data['bank_images'], $data['account_number']);

        $formattedExtraServices = [];
        if ($request->has('extra_services') && is_array($request->input('extra_services'))) {
            foreach ($request->input('extra_services') as $service) {
                if (!empty($service['name'])) {
                    $formattedExtraServices[] = [
                        'name'        => trim($service['name']),
                        'price'       => isset($service['price']) && $service['price'] !== '' ? (float) $service['price'] : 0.00,
                        'description' => !empty($service['description']) ? trim($service['description']) : null,
                    ];
                }
            }
        }
        $data['extra_services'] = $formattedExtraServices;

        // معالجة صورة الغلاف عند التعديل
        if ($request->hasFile('cover_image')) {
            if ($trip->cover_image && Storage::disk('public')->exists($trip->cover_image)) {
                Storage::disk('public')->delete($trip->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('trips_covers', 'public');
        }

        $trip->fill($data);

        $seatsChanged = 0;
        if ($trip->isDirty('available_seats')) {
            // ✅ تم التصحيح (إضافة علامة الناقص بين القيمتين)
            $seatsChanged = (int)$trip->available_seats - (int)$trip->getOriginal('available_seats');
        }

        $trip->save();

        $this->syncOverflowTrip($trip, $request);
        $trip->openOverflowIfFull(); // لو التعديل خفّض السعة وصارت الرحلة ممتلئة

        // 🆕 ربط وتعديل حالة طلب الرحلة الخاصة إذا وُجد
        if (!empty($data['private_trip_request_id'])) {
            \App\Models\PrivateTripRequest::where('id', $data['private_trip_request_id'])
                ->update(['status' => 'accepted']);
        }

        $actionMsg = $isDraft ? "تم حفظ تعديلات المسودة 📁" : "تم نشر/تحديث الرحلة بنجاح ✨";

        AuditLog::create([
            'agency_id'      => $trip->agency_id ?? $this->getAgencyId(),
            'trip_id'        => $trip->id,
            'action_type'    => 'update',
            'category'       => 'trips',
            'amount_changed' => 0,
            'seats_changed'  => $seatsChanged ?: 0,
            'description'    => "تم تحديث بيانات الرحلة ({$trip->title}) - الحالة: " . ($isDraft ? "مسودة" : "منشورة"),
            'operator_name'  => $this->getOperatorName(),
        ]);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $actionMsg,
                'trip'    => $trip->load('agency')
            ], 200);
        }

        return redirect()->route('agency.dashboard')->with('success', $actionMsg);
    }
    /**
 * إذا فعّلت الوكالة الغرف المشتركة لازم تحدد نوعاً واحداً على الأقل، وبسعر.
 */
private function sharedRoomsError(array $rooms, bool $allowsShared): ?string
{
    if (!$allowsShared) {
        return null;
    }

    $labels   = ['single' => 'الفردية', 'double' => 'الثنائية', 'triple' => 'الثلاثية', 'quad' => 'الرباعية'];
    $selected = array_keys(array_filter($rooms, fn($r) => !empty($r['shared'])));

    if (empty($selected)) {
        return 'فعّلت الغرف المشتركة، حدد نوع غرفة واحد على الأقل متاحاً للمشاركة.';
    }

    foreach ($selected as $type) {
        if (($rooms[$type]['price'] ?? 0) <= 0) {
            return 'حدد سعراً للغرفة ' . ($labels[$type] ?? $type) . ' قبل تفعيلها كغرفة مشتركة.';
        }
    }

    return null;
}
    /**
     * إنشاء/تحديث الباص الثاني الاحتياطي بناءً على بيانات الرحلة الأم.
     * - إذا الباص الثاني تفعّل وصار فيه حجوزات: ما نلمسه (نحمي أسعار وحجوزات الركاب).
     * - إذا ما زال احتياطياً: نحذفه ونعيد بناءه من بيانات الأم الحالية.
     */
    private function syncOverflowTrip(Trip $parent, Request $request): void
    {
        $existing = Trip::where('parent_trip_id', $parent->id)->first();

        if ($existing && !$existing->is_overflow_standby) {
            return;
        }

        if ($existing) {
            $this->deleteStandbyTrip($existing, $parent);
        }

        if (!$parent->overflow_enabled || $parent->is_private) {
            return;
        }

        $seats = (int) $request->input('overflow_total_seats');
        if ($seats < 1) {
            return; // مسودة ناقصة: ما نبني الباص الثاني لين تكتمل البيانات
        }

        $child = $parent->replicate();
        $child->unsetRelations();

        $child->title               = $parent->title . ' (الباص الثاني)';
        $child->parent_trip_id      = $parent->id;
        $child->overflow_enabled    = false;
        $child->is_overflow_standby = true;
        $child->status              = 'draft';

        $child->total_seats     = $seats;
        $child->available_seats = $seats;
        $child->vehicle_id      = $request->input('overflow_vehicle_id') ?: null;
        $child->driver_id       = $request->input('overflow_driver_id') ?: null;

        $half = intdiv($seats, 2);
        $child->split_boundary = $parent->transport_type === 'bus' ? $half + 4 : $half;

        // الرحلة المشروطة ما تنطبق على الباص الثاني
        $child->is_conditional        = false;
        $child->min_seats_required    = null;
        $child->threshold_deadline_at = null;

        // الغرف: نفس الأسعار والباقات، لكن العدد صفر (حتى ما نبيع نفس الغرف مرتين)
        $rooms = is_array($parent->room_options)
            ? $parent->room_options
            : (json_decode($parent->room_options ?? '[]', true) ?: []);
        foreach ($rooms as $type => $room) {
            $rooms[$type]['count'] = 0;
        }
        $child->room_options = $rooms;

        // نسخة مستقلة من الملفات حتى لا ينكسر الباص الثاني لو الأم غيّرت الغلاف أو صور QR
        $child->cover_image = $this->copyPublicFile($parent->cover_image, 'trips_covers');

        $banks = is_array($parent->bank_accounts)
            ? $parent->bank_accounts
            : (json_decode($parent->bank_accounts ?? '[]', true) ?: []);
        foreach ($banks as $i => $bank) {
            $banks[$i]['qr_code'] = $this->copyPublicFile($bank['qr_code'] ?? null, 'banks_qr');
        }
        $child->bank_accounts = $banks;

        $child->save();
    }

    private function copyPublicFile(?string $path, string $dir): ?string
    {
        if (!$path || str_starts_with($path, 'http') || !Storage::disk('public')->exists($path)) {
            return $path;
        }

        $newPath = $dir . '/' . uniqid('copy_') . '.' . pathinfo($path, PATHINFO_EXTENSION);
        Storage::disk('public')->copy($path, $newPath);

        return $newPath;
    }

    private function deleteStandbyTrip(Trip $child, Trip $parent): void
    {
        $files = [];

        if ($child->cover_image && $child->cover_image !== $parent->cover_image) {
            $files[] = $child->cover_image;
        }

        $banks = is_array($child->bank_accounts)
            ? $child->bank_accounts
            : (json_decode($child->bank_accounts ?? '[]', true) ?: []);
        $parentQr = collect(is_array($parent->bank_accounts) ? $parent->bank_accounts : (json_decode($parent->bank_accounts ?? '[]', true) ?: []))
            ->pluck('qr_code')->filter()->all();

        foreach ($banks as $bank) {
            if (!empty($bank['qr_code']) && !in_array($bank['qr_code'], $parentQr, true)) {
                $files[] = $bank['qr_code'];
            }
        }

        foreach ($files as $file) {
            if (!str_starts_with($file, 'http') && Storage::disk('public')->exists($file)) {
                Storage::disk('public')->delete($file);
            }
        }

        $child->delete();
    }

    /**
     * فتح الباص الثاني يدوياً
     */
    public function openOverflow(Request $request, $id)
    {
        $parent = Trip::where('id', $id)->where('agency_id', $this->getAgencyId())->firstOrFail();

        $error = null;
        if (!$parent->overflow_enabled) {
            $error = 'هذي الرحلة ما فيها باص ثاني. فعّل الخيار واحفظ الرحلة أولاً.';
        } elseif (in_array($parent->status, ['draft', 'cancelled', 'cancellation_requested'], true)) {
            $error = 'لا يمكن فتح الباص الثاني لرحلة مسودة أو ملغاة أو عليها طلب إلغاء.';
        }

        $child = $error ? null : $parent->activateOverflow('manual', $this->getOperatorName());

        if (!$error && !$child) {
            $error = 'الباص الثاني مفعّل مسبقاً أو غير موجود. احفظ الرحلة أولاً ليتم تجهيزه.';
        }

        if ($error) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $error], 422, [], JSON_UNESCAPED_UNICODE)
                : redirect()->back()->with('error', $error);
        }

        $msg = "تم فتح الباص الثاني (رحلة #{$child->id}) وصار ظاهراً للعملاء ✅";

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'trip_id' => $child->id], 200, [], JSON_UNESCAPED_UNICODE)
            : redirect()->back()->with('success', $msg);
    }

    /**
     * إغلاق الباص الثاني (إرجاعه لوضع احتياطي) - فقط إذا ما عليه حجوزات
     */
    public function closeOverflow(Request $request, $id)
    {
        $parent = Trip::where('id', $id)->where('agency_id', $this->getAgencyId())->firstOrFail();

        $error = $parent->deactivateOverflow($this->getOperatorName());

        if ($error) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $error], 422, [], JSON_UNESCAPED_UNICODE)
                : redirect()->back()->with('error', $error);
        }

        $msg = 'تم إغلاق الباص الثاني وإرجاعه لوضع احتياطي.';

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg], 200, [], JSON_UNESCAPED_UNICODE)
            : redirect()->back()->with('success', $msg);
    }

    //صفحة المسودات للرحلة
    public function drafts()
    {
        $draft = Trip::where('agency_id', $this->getAgencyId())
            ->where('status', 'draft')
            ->where('is_overflow_standby', false)
            ->latest()
            ->paginate(10);

        $draft->through(function ($trip) {
            $defaultImage = $trip->transport_type === 'flight'
                ? 'https://media.istockphoto.com/id/481413202/photo/passenger-airliner-flight-in-the-blue-sky.jpg?s=2048x2048&w=is&k=20&c=C8STBVw9bykfUx2b6zU3NDyR17-gEerpy31Ok2i-Rzs='
                : 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&q=80&w=800';

            $trip->cover_image = $trip->cover_image ? asset('storage/' . $trip->cover_image) : $defaultImage;

            return $trip;
        });

        return view('agency.trips.draft', compact('draft'));
    }
    //بيانات تعديل الرحلة

    public function editTrip($id)
    {
        $agencyId = $this->getAgencyId();
        $trip = Trip::where('id', $id)->where('agency_id', $agencyId)->firstOrFail();

        $drivers = Driver::where('agency_id', $agencyId)->where('status', 'available')->latest()->get();
        $vehicles = Vehicle::where('agency_id', $agencyId)->where('status', 'active')->latest()->get();

        if ($trip->room_options) {
            $trip->price_single  = $trip->room_options['single']['price'] ?? 0;
            $trip->rooms_count_1 = $trip->room_options['single']['count'] ?? 0;
            $trip->price_double  = $trip->room_options['double']['price'] ?? 0;
            $trip->rooms_count_2 = $trip->room_options['double']['count'] ?? 0;
            $trip->price_triple  = $trip->room_options['triple']['price'] ?? 0;
            $trip->rooms_count_3 = $trip->room_options['triple']['count'] ?? 0;
            $trip->price_quad    = $trip->room_options['quad']['price'] ?? 0;
            $trip->rooms_count_4 = $trip->room_options['quad']['count'] ?? 0;
        }

        if ($trip->meal_options) {
            $trip->breakfast_price = $trip->meal_options['individual']['breakfast']['price'] ?? null;
            $trip->lunch_price     = $trip->meal_options['individual']['lunch']['price'] ?? null;
            $trip->dinner_price    = $trip->meal_options['individual']['dinner']['price'] ?? null;

            $trip->price_bf_ln     = $trip->meal_options['packages']['bf_ln'] ?? null;
            $trip->price_ln_dn     = $trip->meal_options['packages']['ln_dn'] ?? null;
            $trip->price_bf_dn     = $trip->meal_options['packages']['bf_dn'] ?? null;
            $trip->price_all_meals = $trip->meal_options['packages']['all'] ?? null;
        }

        return view('agency.trips.create', compact('trip', 'drivers', 'vehicles'));
    }

    //صفحة طلبات خاصة
    public function indexPrivateTrip(Request $request)
    {
        // افترضنا أن الوكالة المسجلة دخلت عبر Auth أو مرتبط حسابها بـ auth()->user()->agency_id
        $agencyId = $this->getAgencyId();

        // 🟢 استعلام جلب الطلبات الخاصة بهذه الوكالة أو الطلبات العامة (agency_id = null)
        $query = PrivateTripRequest::with('user')
            ->where(function ($q) use ($agencyId) {
                $q->where('agency_id', $agencyId)
                    ->orWhereNull('agency_id');
            });

        // 🔍 فلترة بحسب نوع الطلب (خاص بالوكالة / عام)
        if ($request->filled('type')) {
            if ($request->type === 'direct') {
                $query->where('agency_id', $agencyId);
            } elseif ($request->type === 'public') {
                $query->whereNull('agency_id');
            }
        }

        // 🔍 فلترة بحسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 🔍 فلترة بحسب البحث بالمدينة أو الاسم
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('contact_name', 'like', "%{$search}%")
                    ->orWhere('from_city', 'like', "%{$search}%")
                    ->orWhere('to_city', 'like', "%{$search}%");
            });
        }

        $requests = $query->latest()->paginate(10);

        // إحصائيات سريعة للرأس
        // إحصائيات سريعة للرأس
        $stats = [
            'total'    => PrivateTripRequest::where(fn($q) => $q->where('agency_id', $agencyId)->orWhereNull('agency_id'))->count(),
            'direct'   => PrivateTripRequest::where('agency_id', $agencyId)->count(),
            'public'   => PrivateTripRequest::whereNull('agency_id')->count(),
            'pending'  => PrivateTripRequest::where(fn($q) => $q->where('agency_id', $agencyId)->orWhereNull('agency_id'))->where('status', 'pending')->count(),
            'accepted' => PrivateTripRequest::where(fn($q) => $q->where('agency_id', $agencyId)->orWhereNull('agency_id'))->where('status', 'accepted')->count(), // 🟢 الإضافة هنا
        ];

        return view('agency.trips.private_trips', compact('requests', 'stats', 'agencyId'));
    }
    //رفض الطلبات الخاصة
    public function rejectRequestTrip($id)
    {
        $agencyId = $this->getAgencyId();
        $Rtrip = PrivateTripRequest::where(function ($q) use ($agencyId) {
            $q->where('agency_id', $agencyId)->orWhereNull('agency_id');
        })->findOrFail($id);

        $Rtrip->update([
            'status' => 'rejected',
        ]);
        return redirect()->back()->with('success', 'تم رفض طلب الرحلة بنجاح.');
    }





    // طلب إلغاء الرحلة
    public function requestCancellation($id)
    {
        $agencyId = $this->getAgencyId();
        $trip = Trip::find($id);

        if (!$trip) {
            return redirect()->back()->with('error', 'عذراً، هذه الرحلة غير موجودة في النظام! ⚠️');
        }

        if ($agencyId && $trip->agency_id != $agencyId) {
            return redirect()->back()->with('error', 'غير مصرح لك بطلب إلغاء هذه الرحلة! ❌');
        }

        // تحديث حالة الرحلة إلى طلب إلغاء
        $trip->update([
            'status' => 'cancellation_requested', // أو الحالة المعتمدة في قاعدة البيانات لديك مثل 'pending_cancellation'
        ]);

        AuditLog::create([
            'agency_id'      => $agencyId,
            'trip_id'        => $trip->id,
            'action_type'    => 'cancellation_request',
            'category'       => 'trips',
            'amount_changed' => 0,
            'seats_changed'  => 0,
            'description'    => "تم تقديم طلب إلغاء للرحلة ({$trip->title}).",
            'operator_name'  => $this->getOperatorName(),
        ]);

        return redirect()->back()->with('success', 'تم إرسال طلب إلغاء الرحلة بنجاح. 🚫');
    }

    //تحديث مخزون الغرف في صفحة التسكين
    public function updateRoomInventory(Request $request, $tripId)
    {
        $agencyId = $this->getAgencyId();
        $trip = Trip::where('id', $tripId)->where('agency_id', $agencyId)->firstOrFail();

        $request->validate([
            'rooms_count_1' => 'nullable|integer|min:0',
            'rooms_count_2' => 'nullable|integer|min:0',
            'rooms_count_3' => 'nullable|integer|min:0',
            'rooms_count_4' => 'nullable|integer|min:0',
        ]);

        $currentRoomOptions = is_array($trip->room_options)
            ? $trip->room_options
            : (json_decode($trip->room_options, true) ?? []);

        $roomTypesMap = [
            'single' => 'rooms_count_1',
            'double' => 'rooms_count_2',
            'triple' => 'rooms_count_3',
            'quad'   => 'rooms_count_4',
        ];

        foreach ($roomTypesMap as $type => $inputName) {
            $currentCount = $currentRoomOptions[$type]['count'] ?? 0;

            $newCount = $request->has($inputName)
                ? max(0, intval($request->input($inputName)))
                : $currentCount;

            if (isset($currentRoomOptions[$type])) {
                $currentRoomOptions[$type]['count'] = $newCount;
            } else {
                $currentRoomOptions[$type] = [
                    'price'    => 0.0,
                    'count'    => $newCount,
                    'packages' => []
                ];
            }
        }

        $trip->room_options = $currentRoomOptions;
        $trip->save();

        AuditLog::create([
            'agency_id'      => $agencyId,
            'trip_id'        => $trip->id,
            'action_type'    => 'update',
            'category'       => 'trips',
            'amount_changed' => 0,
            'seats_changed'  => 0,
            'description'    => "تعديل مخزون التوزيع الفندقي والغرف الخاص بالرحلة: ({$trip->title}).",
            'operator_name'  => $this->getOperatorName(),
        ]);

        return redirect()->back()->with('success', 'تم تحديث مخزون غرف الرحلة بنجاح.');
    }

    //عرض التقرير الامني
    public function getSecurityManifestData($id)
    {
        $agencyId = $this->getAgencyId();
        $trip = Trip::where('id', $id)
            ->where('agency_id', $agencyId)
            ->with('driver')
            ->firstOrFail();
        $driver = $trip->driver;
        $vehicle = $trip->vehicle;

        $plate = trim($vehicle?->plate_number ?? '');
        $plateNumbers = preg_replace('/\D/u', '', $plate);
        $plateLetters = trim(preg_replace('/\d/u', '', $plate));
        $plateLetters = preg_replace('/\s+/u', ' ', $plateLetters);

        if (empty($plateLetters)) {
            $plateLetters = 'أ ب ت';
        }
        if (empty($plateNumbers)) {
            $plateNumbers = '1234';
        }

        $agency = auth()->user()->agency ?? $trip->agency;

        $bookingSeats = BookingSeat::where('trip_id', $id)
            ->whereNotNull('passenger_name')
            ->where('passenger_name', '!=', '')
            ->orderBy('seat_number')
            ->get();

        $passengers = [];

        foreach ($bookingSeats as $seat) {
            if ($seat->room_type === 'blocked' || str_contains($seat->passenger_name, 'مغلق')) {
                continue;
            }

            $identity = $seat->passenger_national_id;

            if (!empty($identity)) {
                try {
                    if (str_starts_with($identity, 'eyJpdiI6')) {
                        $identity = Crypt::decryptString($identity);
                    }
                } catch (\Exception $e) {
                    //
                }
            }

            $passengers[] = [
                'seat_number' => $seat->seat_number,
                'name'        => $seat->passenger_name,
                'identity'    => $identity ?: 'غير محدد',
                'gender'      => $seat->gender ?? 'male',
            ];
        }

        return response()->json([
            'trip' => [
                'id'             => $trip->id,
                'title'          => $trip->title,
                'departure_at'   => $trip->departure_at,
                'from_city'      => $trip->from_city,
                'to_city'        => $trip->to_city,
                'total_seats'    => $trip->total_seats,
                'plate_letters'  => $plateLetters,
                'plate_numbers'  => $plateNumbers,
                'driver_1_name'  => $driver?->name ?? 'غير محدد',
                'driver_1_id'    => $driver?->national_id ?? 'غير محدد',
                'assistant_name' => $trip->assistant_name ?? 'غير محدد',
                'assistant_id'   => $trip->assistant_id,
            ],
            'agency' => [
                'iconA'         => $agency->iconA,
                'commercial_id' => $agency->commercial_id,
                'name'          => $agency->name ?? 'وكالة عُبور المعتمدة',
                'cr_number'     => $agency->cr_number ?? 'غير مسجل',
                'phone'         => $agency->phone ?? 'غير محدد',
                'email'         => $agency->email ?? 'غير محدد',
            ],
            'passengers' => $passengers,
        ]);
    }

    public function assignResources(Request $request, Trip $trip)
    {
        $agencyId = $this->getAgencyId();

        if (!$trip || $trip->agency_id !== $agencyId) {
            return redirect()->back()->with('error', 'الرحلة غير موجودة أو غير مصرح لك بتعديلها.');
        }

        // 1. قواعد التحقق
        $validated = $request->validate([
            // بيانات السائق الأول
            'driver_id'          => 'nullable|exists:drivers,id',
            'driver_name'        => 'nullable|string|max:255',
            'driver_phone'       => 'nullable|string|max:20',
            'driver_national_id' => 'nullable|string|max:50',

            // بيانات السائق الثاني
            'driver2_id'          => 'nullable|exists:drivers,id',
            'driver2_name'        => 'nullable|string|max:255',
            'driver2_phone'       => 'nullable|string|max:20',
            'driver2_national_id' => 'nullable|string|max:50',

            // بيانات المركبة
            'vehicle_id'          => 'nullable|exists:vehicles,id',
            'plate_number'        => 'nullable|string|max:20',
            'vehicle_model'       => 'nullable|string|max:255',
            'total_seats'         => 'nullable|integer|min:1',

            // بيانات الكادر
            'staff_ids'           => 'nullable|array',
            'staff_ids.*'         => 'exists:staffs,id',
            'staff_name'          => 'nullable|string|max:255',
            'staff_phone'         => 'nullable|string|max:20',
            'staff_role'          => 'nullable|string|max:100',
            'staff_national_id'   => 'nullable|string|max:50', // ⬅️ أُضيف

            // الكوادر الإضافية الديناميكية
            'new_staff'                 => 'nullable|array',
            'new_staff.*.name'          => 'required|string|max:255',
            'new_staff.*.phone'         => 'nullable|string|max:20',
            'new_staff.*.role'          => 'nullable|string|max:100',
            'new_staff.*.national_id'   => 'nullable|string|max:50', // ⬅️ أُضيف
        ]);

        try {
            DB::transaction(function () use ($validated, $trip, $agencyId) {

                // أ. معالجة السائق الأول
                if (!empty($validated['driver_name'])) {
                    $driverPhone = !empty($validated['driver_phone']) ? $validated['driver_phone'] : '05' . rand(10000000, 99999999);

                    $searchCriteria = !empty($validated['driver_national_id'])
                        ? ['agency_id' => $agencyId, 'national_id' => $validated['driver_national_id']]
                        : ['agency_id' => $agencyId, 'phone' => $driverPhone];

                    $driver = Driver::updateOrCreate(
                        $searchCriteria,
                        [
                            'name'        => $validated['driver_name'],
                            'phone'       => $driverPhone,
                            'national_id' => $validated['driver_national_id'] ?? null,
                            'status'      => 'active'
                        ]
                    );
                    $trip->driver_id = $driver->id;
                } else {
                    $trip->driver_id = $validated['driver_id'] ?? null;

                    if ($trip->driver_id && !empty($validated['driver_national_id'])) {
                        Driver::where('id', $trip->driver_id)
                            ->where('agency_id', $agencyId)
                            ->update(['national_id' => $validated['driver_national_id']]);
                    }
                }

                // ب. معالجة السائق الثاني
                $driver2Field = Schema::hasColumn('trips', 'driver_2_id') ? 'driver_2_id' : 'driver2_id';

                if (!empty($validated['driver2_name'])) {
                    $driver2Phone = !empty($validated['driver2_phone']) ? $validated['driver2_phone'] : '05' . rand(10000000, 99999999);

                    $searchCriteria2 = !empty($validated['driver2_national_id'])
                        ? ['agency_id' => $agencyId, 'national_id' => $validated['driver2_national_id']]
                        : ['agency_id' => $agencyId, 'phone' => $driver2Phone];

                    $driver2 = Driver::updateOrCreate(
                        $searchCriteria2,
                        [
                            'name'        => $validated['driver2_name'],
                            'phone'       => $driver2Phone,
                            'national_id' => $validated['driver2_national_id'] ?? null,
                            'status'      => 'active'
                        ]
                    );
                    $trip->{$driver2Field} = $driver2->id;
                } else {
                    $trip->{$driver2Field} = $validated['driver2_id'] ?? null;

                    if ($trip->{$driver2Field} && !empty($validated['driver2_national_id'])) {
                        Driver::where('id', $trip->{$driver2Field})
                            ->where('agency_id', $agencyId)
                            ->update(['national_id' => $validated['driver2_national_id']]);
                    }
                }

                // ج. معالجة المركبة
                if (!empty($validated['vehicle_id'])) {
                    $vehicle = Vehicle::where('id', $validated['vehicle_id'])
                        ->where('agency_id', $agencyId)
                        ->first();

                    if ($vehicle) {
                        $updateData = [];
                        if (!empty($validated['plate_number']))  $updateData['plate_number']  = $validated['plate_number'];
                        if (!empty($validated['vehicle_model'])) $updateData['vehicle_model'] = $validated['vehicle_model'];
                        if (!empty($validated['total_seats']))   $updateData['total_seats']   = $validated['total_seats'];

                        if (!empty($updateData)) {
                            $vehicle->update($updateData);
                        }
                        $trip->vehicle_id = $vehicle->id;
                    }
                } elseif (!empty($validated['plate_number'])) {
                    $vehicle = Vehicle::updateOrCreate(
                        [
                            'agency_id'    => $agencyId,
                            'plate_number' => $validated['plate_number'],
                        ],
                        [
                            'vehicle_model' => $validated['vehicle_model'] ?? 'عام',
                            'total_seats'   => $validated['total_seats'] ?? 49,
                            'status'        => 'active'
                        ]
                    );
                    $trip->vehicle_id = $vehicle->id;
                } else {
                    $trip->vehicle_id = null;
                }

                // حفظ التغييرات على الرحلة
                $trip->save();

                // د. معالجة الكادر المساعد (Pivot)
                $staffIdsToSync = $validated['staff_ids'] ?? [];

                // كادر مباشر فردي — تم التصحيح: بحث ودمج national_id
                if (!empty($validated['staff_name'])) {
                    $staffPhone = !empty($validated['staff_phone']) ? $validated['staff_phone'] : '05' . rand(10000000, 99999999);

                    $searchCriteriaStaff = !empty($validated['staff_national_id'])
                        ? ['agency_id' => $agencyId, 'national_id' => $validated['staff_national_id']]
                        : ['agency_id' => $agencyId, 'phone' => $staffPhone];

                    $newStaff = Staff::updateOrCreate(
                        $searchCriteriaStaff,
                        [
                            'name'        => $validated['staff_name'],
                            'phone'       => $staffPhone,
                            'national_id' => $validated['staff_national_id'] ?? null,
                            'role'        => $validated['staff_role'] ?? 'مشرف رحلة',
                            'status'      => 'active'
                        ]
                    );
                    $staffIdsToSync[] = $newStaff->id;
                }

                // كوادر ديناميكية إضافية (مصفوفة new_staff) — تم التصحيح: بحث ودمج national_id
                if (!empty($validated['new_staff']) && is_array($validated['new_staff'])) {
                    foreach ($validated['new_staff'] as $staffItem) {
                        if (!empty($staffItem['name'])) {
                            $sPhone = !empty($staffItem['phone']) ? $staffItem['phone'] : '05' . rand(10000000, 99999999);

                            $searchCriteriaDynamic = !empty($staffItem['national_id'])
                                ? ['agency_id' => $agencyId, 'national_id' => $staffItem['national_id']]
                                : ['agency_id' => $agencyId, 'phone' => $sPhone];

                            $createdStaff = Staff::updateOrCreate(
                                $searchCriteriaDynamic,
                                [
                                    'name'        => $staffItem['name'],
                                    'phone'       => $sPhone,
                                    'national_id' => $staffItem['national_id'] ?? null,
                                    'role'        => $staffItem['role'] ?? 'كادر',
                                    'status'      => 'active'
                                ]
                            );
                            $staffIdsToSync[] = $createdStaff->id;
                        }
                    }
                }

                // دمج العناصر المحددة
                $trip->staff()->sync(array_unique($staffIdsToSync));
            });

            return redirect()->back()->with('success', 'تم تخصيص وتحديث السائقين والموارد بنجاح.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء الحفظ: ' . $e->getMessage());
        }
    }
    /**
     * إزالة كادر مساعد محدد من الرحلة
     */
    public function removeStaff(Trip $trip, $staffId)
    {
        $agencyId = $this->getAgencyId();

        // التحقق من صلاحية الوصول للرحلة
        if (!$trip || $trip->agency_id !== $agencyId) {
            return redirect()->back()->with('error', 'الرحلة غير موجودة أو غير مصرح لك بتعديلها.');
        }

        try {
            // فك ربط الكادر المساعد بالرحلة
            $trip->staff()->detach($staffId);

            return redirect()->back()->with('success', 'تمت إزالة الكادر المساعد من الرحلة بنجاح.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء إزالة الكادر: ' . $e->getMessage());
        }
    }
}
