<?php

namespace App\Http\Controllers\Agency;

// Controllers
use App\Http\Controllers\Controller;

// Models
use App\Models\Agency;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Models\BookingSeat;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\AuditLog;
use App\Models\TripRoom;
use App\Models\Staff;
use App\Models\AgencyBankAccount;
use App\Models\AgencyRating;
use App\Models\AgencyEmployee;

use App\Models\BookingExtraCharge;
use App\Models\AgencyTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

// Facades & Utilities
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;

class AgencyController extends Controller
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

    // =========================================================================
    // 1. AUTHENTICATION & DASHBOARD (إدارة الحسابات ولوحة التحكم)
    // =========================================================================

    public function register(Request $request)
    {
        $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'commercial_name' => ['required', 'string', 'max:200'],
            'email'          => ['required', 'email', 'max:255', 'unique:agencies,email'],
            'iconA'          => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'phone'          => ['required', 'regex:/^(05|01|9200|800)\d{7,8}$|^(?:\+966|00966)5\d{8}$/'],
            'password'       => ['required', 'confirmed', 'min:8'],
            'ibanA'          => ['required'],
            'commercial_id'  => ['required', 'string', 'max:50'],
            'cr_expiry_date' => ['required', 'date', 'after:today'],
            'status'         => ['nullable', 'string', 'max:50'],
        ]);

        $iconAPath = null;
        if ($request->hasFile('iconA')) {
            $iconAPath = $request->file('iconA')->store('uploads/iconA', 'public');
        }

        $agency = Agency::create([
            'name'           => $request->name,
            'commercial_name' => $request->commercial_name,
            'email'          => $request->email,
            'iconA'          => $iconAPath,
            'phone'          => $request->phone,
            'password'       => Hash::make($request->password),
            'ibanA'          => $request->ibanA,
            'commercial_id'  => $request->commercial_id,
            'cr_expiry_date' => $request->cr_expiry_date,
            'status'         => 'pending',
        ]);

        auth()->guard('agency')->login($agency);

        session([
            'agency_logged_in' => true,
            'agency_id'        => $agency->id,
            'agency_name'      => $agency->name
        ]);

        $request->session()->regenerate();

        return redirect()->route('agency.dashboard');
    }
    public function create(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'يرجى استخدام بروتوكول POST لإنشاء الحساب الجديد.'], 405);
        }
        return view('agency.auth.register');
    }


    public function showLoginForm(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => 'يرجى استخدام بروتوكول POST لتسجيل الدخول.'], 405);
        }
        return view('agency.auth.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "تجاوزت عدد محاولات الدخول المسموح بها. يرجى الانتظار {$seconds} ثانية قبل المحاولة مجدداً.",
            ])->onlyInput('email');
        }

        $agency = Agency::where('email', $credentials['email'])->first();

        if ($agency && Hash::check($credentials['password'], $agency->password)) {

            if ($agency->status !== 'active') {
                return back()->withErrors([
                    'email' => 'عذراً، حساب الوكالة هذا قيد المراجعة حالياً أو تم تجميده من قبل الإدارة.',
                ])->onlyInput('email');
            }

            RateLimiter::clear($throttleKey);
            auth()->guard('agency')->login($agency, $request->has('remember'));

            session([
                'agency_logged_in' => true,
                'agency_id'        => $agency->id,
                'agency_name'      => $agency->name,
                'user_type'        => 'owner',
            ]);

            $request->session()->regenerate();

            return redirect()->route('agency.dashboard');
        }

        $employee = AgencyEmployee::with('agency')->where('email', $credentials['email'])->first();

        if ($employee && Hash::check($credentials['password'], $employee->password)) {

            if (!$employee->is_active) {
                return back()->withErrors([
                    'email' => 'عذراً، حساب الموظف هذا معطل حالياً من قبل إدارة الوكالة.',
                ])->onlyInput('email');
            }

            if (!$employee->agency || $employee->agency->status !== 'active') {
                return back()->withErrors([
                    'email' => 'عذراً، حساب الوكالة التابع لها قيد المراجعة أو معطل حالياً.',
                ])->onlyInput('email');
            }

            RateLimiter::clear($throttleKey);
            auth()->guard('employee')->login($employee, $request->has('remember'));

            session([
                'employee_logged_in' => true,
                'employee_id'        => $employee->id,
                'employee_name'      => $employee->name,
                'employee_role'      => $employee->role,
                'agency_id'          => $employee->agency_id,
                'agency_name'        => $employee->agency->name,
                'user_type'          => 'employee',
            ]);

            $request->session()->regenerate();

            return redirect()->route('agency.dashboard');
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'بيانات الدخول غير صحيحة.',
        ])->onlyInput('email');
    }



    public function logout(Request $request)
    {
        if (auth('agency')->check()) {
            auth()->guard('agency')->logout();
        } elseif (auth('employee')->check()) {
            auth()->guard('employee')->logout();
        }

        session()->forget([
            'agency_logged_in',
            'agency_id',
            'agency_name',
            'employee_logged_in',
            'employee_id',
            'employee_name',
            'employee_role',
            'user_type'
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('agency.login');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // لإعادة تعيين كلمة المرور
        $throttleKey = 'password-reset|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return redirect()->back()->withErrors(['email' => "يرجى الانتظار {$seconds} ثانية قبل إعادة طلب رابط إعادة التعيين."]);
        }
        RateLimiter::hit($throttleKey, 60);

        $status = Password::broker('agencies')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return redirect()->back()->with('status', __($status));
        }

        return redirect()->back()->withErrors(['email' => __($status)]);
    }

    public function agencyDashboard()
    {
        $agencyId = $this->getAgencyId();
        if (!$agencyId) {
            return redirect()->route('client.auth.login')->with('error', 'يرجى تسجيل الدخول أولاً.');
        }

        $agency = Agency::findOrFail($agencyId);

        // 🟢 حساب الأرصدة والأرباح للوكالة
        $availableBalance = $agency->available_balance ?? 0;
        $pendingBalance   = $agency->pending_balance ?? 0;

        $totalApprovedPayouts = $agency->payoutRequests()
            ->whereIn('status', ['approved', 'completed'])
            ->sum('requested_amount');

        // إجمالي الأرباح الصافية التاريخية
        $totalEarnings = $totalApprovedPayouts;

        // جلب جميع رحلات الوكالة (مع إضافة حقل vehicle_model للاستفادة منه في الحسابات)
        $trips = Trip::where('agency_id', $agencyId)
            ->select('id', 'title', 'from_city', 'to_city', 'transport_type', 'status', 'price', 'total_seats', 'available_seats', 'departure_at', 'vehicle_model')
            ->latest()
            ->get();

        // جلب القوائم المساعدة
        $drivers  = Driver::where('agency_id', $agencyId)->latest()->get();
        $vehicles = Vehicle::where('agency_id', $agencyId)->latest()->get();
        $banks    = AgencyBankAccount::where('agency_id', $agencyId)->latest()->get();

        // ⚡ تحسين الأداء: حساب الإحصائيات مباشرة من الذاكرة (Collection) لتقليل الاستعلامات على قاعدة البيانات
        $busesCount = $trips->pluck('vehicle_model')->filter()->unique()->count();

        $activeTripsCount = $trips->filter(function ($trip) {
            return in_array(trim($trip->status), ['active', 'on_way', 'نشطة']);
        })->count();

        $totalAvailableSeats = $trips->where('status', '!=', 'completed')->sum('available_seats');

        return view('agency.dashboard', compact(
            'trips',
            'drivers',
            'vehicles',
            'banks',
            'busesCount',
            'activeTripsCount',
            'totalAvailableSeats',
            'agency',
            'totalEarnings',
            'availableBalance',
            'pendingBalance'
        ));
    }
    /**
     * تحديث إعدادات الوكالة وتقديم طلب تحديث السجل التجاري
     */
    public function updateSettings(Request $request)
    {
        $agencyId = $this->getAgencyId();

        if (!$agencyId) {
            return redirect()->back()->with('error', 'تعذر العثور على حساب الوكالة الخاص بك.');
        }

        $agency = Agency::findOrFail($agencyId);

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'commercial_name' => 'nullable|string|max:255',
            'email'           => 'required|email|max:255|unique:agencies,email,' . $agency->id,
            'phone'           => 'nullable|string|max:20',
            'commercial_id'   => 'required|string|max:50',
            'cr_expiry_date'  => 'required|date',
            'cr_document'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $dataToUpdate = [
            // البيانات العادية تتحدث فوراً
            'name'            => $validated['name'],
            'commercial_name' => $validated['commercial_name'],
            'email'           => $validated['email'],
            'phone'           => $validated['phone'],
        ];

        // التحقق هل تم إدخال رقم سجل جديد أو تاريخ جديد أو إرفاق ملف جديد
        $hasCrChanges = $validated['commercial_id'] !== $agency->commercial_id
            || $validated['cr_expiry_date'] !== $agency->cr_expiry_date
            || $request->hasFile('cr_document');

        if ($hasCrChanges) {
            if ($request->hasFile('cr_document')) {
                // حذف أي ملف سابق كان معلقاً ولم يُعتمد بعد
                if ($agency->pending_cr_document_path) {
                    Storage::disk('public')->delete($agency->pending_cr_document_path);
                }
                $dataToUpdate['pending_cr_document_path'] = $request->file('cr_document')->store('agency_documents/pending', 'public');
            } else {
                $dataToUpdate['pending_cr_document_path'] = $agency->pending_cr_document_path ?? $agency->cr_document_path;
            }

            // حفظ بيانات السجل في حقول "قيد المراجعة"
            $dataToUpdate['pending_commercial_id']  = $validated['commercial_id'];
            $dataToUpdate['pending_cr_expiry_date'] = $validated['cr_expiry_date'];
            $dataToUpdate['cr_status']              = 'pending';
        }

        $agency->update($dataToUpdate);

        $message = $hasCrChanges
            ? 'تم حفظ البيانات الأساسية، وإرسال طلب تحديث السجل التجاري للإدارة للمراجعة والاعتماد.'
            : 'تم تحديث بيانات الوكالة بنجاح.';

        return redirect()->back()->with('success', $message);
    }

    // =========================================================================
    // 2. BOOKING MANAGEMENT (إدارة الحجوزات والتذاكر للوكالة)
    // =========================================================================

    public function indexBooking(Request $request)
    {
        $agencyId = $this->getAgencyId();
        $trips = Trip::where('agency_id', $agencyId)->latest()->get();

        $bookingsQuery = Booking::query()
            ->whereHas('trip', function ($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })
            ->with(['trip', 'bookingSeats.room', 'extraCharges']);

        $search = trim($request->input('x_search', $request->input('search')));

        if (!empty($search)) {
            $bookingsQuery->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                    ->orWhere('customer_name', 'LIKE', "%{$search}%")
                    ->orWhere('customer_phone', 'LIKE', "%{$search}%")
                    ->orWhereHas('bookingSeats', function ($sq) use ($search) {
                        $sq->where('passenger_name', 'LIKE', "%{$search}%")
                            ->orWhere('passenger_national_id', 'LIKE', "%{$search}%");

                        if (is_numeric($search)) {
                            $sq->orWhereJsonContains('seat_number', (int)$search)
                                ->orWhereJsonContains('seat_number', (string)$search);
                        }
                    });
            });
        }

        if ($request->filled('trip_id')) {
            $bookingsQuery->where('trip_id', $request->input('trip_id'));
        }

        $confirmedCount = (clone $bookingsQuery)->where('status', 'confirmed')->count();
        $pendingCount   = (clone $bookingsQuery)->where('status', 'pending')->count();

        $bookings = $bookingsQuery->latest()->paginate(10)->withQueryString();

        // توحيد مصفوفة room_type لجميع المقاعد ليفهمها Alpine.js كـ "shared"
        $bookings->getCollection()->transform(function ($booking) {
            $booking->bookingSeats->transform(function ($seat) {
                if (\Illuminate\Support\Str::contains($seat->room_type, ['مشتركة', 'shared']) || !is_null($seat->trip_room_id)) {
                    $seat->normalized_room_type = 'shared';
                } else {
                    $seat->normalized_room_type = strtolower(trim($seat->room_type));
                }
                return $seat;
            });
            return $booking;
        });

        return view("agency.bookings.index", compact('bookings', 'trips', 'confirmedCount', 'pendingCount'));
    }

    public function storeExtraCharge(Request $request, Booking $booking)
{
    $agencyId = $this->getAgencyId();
    $tripAgencyId = $booking->trip?->agency_id ?? $booking->trip()->withoutGlobalScopes()->value('agency_id');

    abort_unless($agencyId && $tripAgencyId == $agencyId, 403);

    if ($booking->payment_status !== 'paid' || in_array($booking->status, ['cancelled', 'requestC'])) {
        return back()->with('error', 'لا يمكن إنشاء فاتورة إضافية لهذا الحجز.');
    }

    $data = $request->validate([
        'amount'      => 'required|numeric|min:1',
        'description' => 'required|string|max:255',
    ]);

    // الدفع يتم في صفحة الدفع الموحدة عندنا، فما نحتاج فاتورة من ميسر
    $charge = BookingExtraCharge::create([
        'booking_id'  => $booking->id,
        'agency_id'   => $agencyId,
        'amount'      => $data['amount'],
        'description' => $data['description'],
        'token'       => Str::random(40),
        'status'      => 'pending',
        'created_by' => $this->getOperatorName(),
    ]);

    if ($booking->agency_note_status === 'pending') {
        $booking->update(['agency_note_status' => 'answered']);
    }

    $emailSent = false;
    $email = $booking->customer_email;

    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && $email !== 'agency-guest@abur.com') {
        try {
            Mail::to($email)->send(new \App\Mail\ExtraChargeMail($booking, $charge));
            $emailSent = true;
        } catch (\Throwable $e) {
            Log::error("فشل إرسال إيميل الفاتورة الإضافية للحجز #{$booking->id}: " . $e->getMessage());
        }
    }

    return back()->with(
        'success',
        $emailSent
            ? 'تم إنشاء رابط الدفع وإرساله للمسافر بالإيميل.'
            : 'تم إنشاء رابط الدفع، لكن تعذر إرساله بالإيميل. سيظهر للمسافر في صفحة حسابه.'
    );
}

    public function cancelExtraCharge(BookingExtraCharge $charge)
    {
        abort_unless($charge->agency_id == $this->getAgencyId(), 403);
        if ($charge->status === 'pending') {
            $charge->update(['status' => 'cancelled']);
        }
        return back()->with('success', 'تم إلغاء طلب الدفع.');
    }

    public function noteHandled(Booking $booking)
    {
        $agencyId = $this->getAgencyId();
        $tripAgencyId = $booking->trip?->agency_id ?? $booking->trip()->withoutGlobalScopes()->value('agency_id');

        abort_unless($agencyId && $tripAgencyId == $agencyId, 403);
        $booking->update(['agency_note_status' => 'answered']);
        return back()->with('success', 'تم إغلاق الملاحظة.');
    }

    public function dismissNote($id)
    {
        $agencyId = $this->getAgencyId();

        Booking::where('id', $id)
            ->whereHas('trip', fn($q) => $q->where('agency_id', $agencyId))
            ->firstOrFail()
            ->update(['agency_note_status' => 'dismissed']);

        return back()->with('success', 'تم إغلاق الملاحظة.');
    }

    public function updateBooking(Request $request, $id)
    {
        $request->validate([
            'customer_name'                         => 'required|string|max:255',
            'customer_phone'                        => 'nullable|string|max:20',
            'status'                                => 'required|in:pending,confirmed,cancelled,completed,requestC',
            'payment_status'                        => 'required|in:pending,unpaid,paid,paidof,failed,refunded',
            'cash_paid'                             => 'nullable|numeric|min:0',
            'seats'                                 => 'nullable|array',
            'seats.*.room_type'                     => 'required|string',
            'seats.*.room_package'                  => 'nullable|string',
            'seats.*.is_room_shared'                => 'nullable|boolean',
            'seats.*.meal_package'                  => 'nullable|string',
            'seats.*.bf'                            => 'nullable|boolean',
            'seats.*.ln'                            => 'nullable|boolean',
            'seats.*.dn'                            => 'nullable|boolean',
            'seats.*.extra_services'                => 'nullable|array',
            'seats.*.extra_services.*.selected'     => 'nullable|boolean',
            'seats.*.extra_services.*.name'         => 'nullable|string',
            'seats.*.extra_services.*.price'        => 'nullable|numeric',
        ]);

        DB::transaction(function () use ($request, $id) {
            $booking = Booking::with(['trip', 'bookingSeats'])->findOrFail($id);

            $oldTotalAmount   = $booking->total_amount;
            $oldStatus        = $booking->status;
            $oldPaymentStatus = $booking->payment_status;
            $oldCustomerName  = $booking->customer_name;

            $booking->customer_name  = $request->customer_name;
            $booking->customer_phone = $request->customer_phone;
            $booking->status         = $request->status;
            $booking->payment_status = $request->payment_status;

            // حفظ قيمة الخصم إن وجد في الحقل المخصص في الجدول
            if ($request->has('cash_paid')) {
                $booking->cash_paid = (float)$request->cash_paid;
            }

            $trip = $booking->trip;
            $tripRoomOptions = is_array($trip?->room_options) ? $trip->room_options : json_decode($trip?->room_options ?? '[]', true);
            $tripMealOptions = is_array($trip?->meal_options) ? $trip->meal_options : json_decode($trip?->meal_options ?? '[]', true);

            $tripRoomOptions = array_filter($tripRoomOptions, function ($opt) {
                return isset($opt['price']) && (float)$opt['price'] > 0;
            });

            $mealPackages    = $tripMealOptions['packages'] ?? [];
            $individualMeals = $tripMealOptions['individual'] ?? [];

            $capacities = [
                'single'     => 1,
                'double'     => 2,
                'triple'     => 3,
                'quad'       => 4,
                'shared'     => 1,
                'additional' => 1,
            ];

            $roomCounts = [];
            $totalPackageAndMealCost = 0;
            $updatedRoomTypes = [];
            $seatsChangedCount = 0;
            $roomChangesDetails = [];

            if ($request->has('seats')) {
                foreach ($request->seats as $seatId => $seatData) {
                    $seat = $booking->bookingSeats->firstWhere('id', $seatId);

                    if ($seat) {
                        $mealCost = 0;
                        $includedMealsInPackage = ['breakfast' => false, 'lunch' => false, 'dinner' => false];

                        $oldRoomType = $seat->room_type;
                        $newRoomType = strtolower(trim($seatData['room_type'] ?? ''));
                        $roomPackage = trim($seatData['room_package'] ?? '');

                        $isRoomShared = ($newRoomType === 'shared') || (!empty($seatData['is_room_shared']) && ($seatData['is_room_shared'] == '1' || $seatData['is_room_shared'] == 'true'));


                        // تحسين التعرف على الغرفة المشتركة القديمة والجديدة
                        $isOldShared = \Illuminate\Support\Str::contains($oldRoomType, ['مشتركة', 'shared']) || !is_null($seat->trip_room_id);
                        $isNewShared = ($newRoomType === 'shared') || (!empty($seatData['is_room_shared']) && ($seatData['is_room_shared'] == '1' || $seatData['is_room_shared'] == 'true'));

                        // يتحقق من تغيير نوع الغرفة فقط إذا انتقل من مشتركة إلى خاصة أو العكس
                        $roomTypeChanged = false;
                        if ($isOldShared && !$isNewShared) {
                            $roomTypeChanged = true;
                        } elseif (!$isOldShared && $newRoomType !== strtolower(trim($oldRoomType))) {
                            $roomTypeChanged = true;
                        }

                        if ($roomTypeChanged) {
                            $seatsChangedCount++;
                            $seatIdentifier = is_array($seat->seat_number) ? implode(', ', $seat->seat_number) : ($seat->seat_number ?? $seat->id);
                            $roomChangesDetails[] = "مقعد {$seatIdentifier}: [{$oldRoomType} -> {$newRoomType}]";

                            // تصفير الغرفة المشتركة فقط عند التحول إلى غرفة غير مشتركة
                            $seat->trip_room_id = null;
                        }

                        // توحيد القيمة للغرفة المشتركة
                        $seat->room_type = $isNewShared ? 'shared' : $newRoomType;
                        $updatedRoomTypes[] = $seat->room_type;

                        if ($isRoomShared || $newRoomType === 'shared' || $newRoomType === 'additional') {
                            $baseTripPrice = (float)($trip->price ?? 0);
                            $totalPackageAndMealCost += $baseTripPrice;
                        } else if (!empty($newRoomType)) {
                            $roomCounts[$newRoomType] = ($roomCounts[$newRoomType] ?? 0) + 1;
                        }

                        $matchedRoomDetails = null;
                        if (is_array($tripRoomOptions)) {
                            foreach ($tripRoomOptions as $key => $details) {
                                if (strtolower(trim($key)) === $newRoomType) {
                                    $matchedRoomDetails = $details;
                                    break;
                                }
                            }
                        }

                        $packagePrice = 0;
                        if (!empty($roomPackage) && !empty($matchedRoomDetails['packages'])) {
                            foreach ($matchedRoomDetails['packages'] as $pkg) {
                                if (trim($pkg['name']) === $roomPackage) {
                                    $packagePrice = (float)($pkg['price'] ?? 0);
                                    $includedMealsInPackage = $pkg['meals'] ?? $includedMealsInPackage;
                                    break;
                                }
                            }
                        }

                        $seat->room_options = [
                            'package_name'   => $roomPackage,
                            'price'          => $packagePrice,
                            'included_meals' => $includedMealsInPackage,
                            'is_shared'      => $isRoomShared,
                        ];

                        $mealPackage = $seatData['meal_package'] ?? null;

                        if (!empty($mealPackage)) {
                            $packagePriceMeal = (float)($mealPackages[$mealPackage] ?? 0);
                            $mealCost = $packagePriceMeal;

                            $seat->with_breakfast = in_array($mealPackage, ['all', 'bf_ln', 'bf_dn', 'full']);
                            $seat->with_lunch     = in_array($mealPackage, ['all', 'bf_ln', 'ln_dn', 'full']);
                            $seat->with_dinner    = in_array($mealPackage, ['all', 'ln_dn', 'bf_dn', 'full']);

                            $seat->breakfast_price = 0;
                            $seat->lunch_price     = 0;
                            $seat->dinner_price    = 0;

                            $seat->meal_options = [
                                'type'          => 'package',
                                'package_key'   => $mealPackage,
                                'package_price' => $packagePriceMeal
                            ];
                        } else {
                            // مطابقة مع حقول الفرونت إند [bf], [ln], [dn]
                            $seat->with_breakfast = (isset($seatData['bf']) && $seatData['bf'] == '1') || ($includedMealsInPackage['breakfast'] ?? false);
                            $seat->with_lunch     = (isset($seatData['ln']) && $seatData['ln'] == '1') || ($includedMealsInPackage['lunch'] ?? false);
                            $seat->with_dinner    = (isset($seatData['dn']) && $seatData['dn'] == '1') || ($includedMealsInPackage['dinner'] ?? false);

                            $seat->breakfast_price = ($seat->with_breakfast && !($includedMealsInPackage['breakfast'] ?? false)) ? (float)($individualMeals['breakfast']['price'] ?? 0) : 0;
                            $seat->lunch_price     = ($seat->with_lunch && !($includedMealsInPackage['lunch'] ?? false)) ? (float)($individualMeals['lunch']['price'] ?? 0) : 0;
                            $seat->dinner_price    = ($seat->with_dinner && !($includedMealsInPackage['dinner'] ?? false)) ? (float)($individualMeals['dinner']['price'] ?? 0) : 0;

                            $mealCost = $seat->breakfast_price + $seat->lunch_price + $seat->dinner_price;

                            $seat->meal_options = [
                                'type'        => 'individual',
                                'package_key' => null
                            ];
                        }

                        // معالجة الخدمات الإضافية المعدلة
                        $extraServicesCost = 0;
                        $savedExtraServices = [];

                        if (!empty($seatData['extra_services']) && is_array($seatData['extra_services'])) {
                            foreach ($seatData['extra_services'] as $srv) {
                                if (isset($srv['selected']) && ($srv['selected'] == '1' || $srv['selected'] == 'true')) {
                                    $srvPrice = (float)($srv['price'] ?? 0);
                                    $extraServicesCost += $srvPrice;

                                    $savedExtraServices[] = [
                                        'name'        => $srv['name'] ?? '',
                                        'price'       => $srvPrice,
                                        'description' => $srv['description'] ?? '',
                                    ];
                                }
                            }
                        }

                        $seat->extra_services = !empty($savedExtraServices) ? $savedExtraServices : null;
                        $seat->save();

                        if ($seat->trip_room_id) {
                            $cap = $capacities[$newRoomType] ?? 1;
                            $seatNumStr = is_array($seat->seat_number) ? implode('-', $seat->seat_number) : $seat->seat_number;

                            TripRoom::where('id', $seat->trip_room_id)->update([
                                'room_type' => $newRoomType,
                                'room_uid'  => "room_{$cap}_" . $seatNumStr
                            ]);
                        }

                        $totalPackageAndMealCost += ($packagePrice + $mealCost + $extraServicesCost);
                    }
                }
            }

            $totalRoomsCost = 0;
            foreach ($roomCounts as $rType => $passengerCount) {
                $cap = $capacities[$rType] ?? 1;
                $roomsNeeded = (int) ceil($passengerCount / $cap);

                $rPrice = 0;
                if (is_array($tripRoomOptions)) {
                    foreach ($tripRoomOptions as $key => $details) {
                        if (strtolower(trim($key)) === $rType) {
                            $rPrice = (float)($details['price'] ?? 0);
                            break;
                        }
                    }
                }
                $totalRoomsCost += ($roomsNeeded * $rPrice);
            }

            $discount = (float)($request->cash_paid ?? 0);
            $calculatedTotal = $totalRoomsCost + $totalPackageAndMealCost;
            $newTotalAmount = max(0, $calculatedTotal - $discount);

            if (!empty($updatedRoomTypes)) {
                $booking->room_type = implode(', ', array_unique($updatedRoomTypes));
            }

            $booking->total_amount = $newTotalAmount;
            $booking->save();

            $amountDifference = $newTotalAmount - $oldTotalAmount;

            $logDescription = "تم تحديث الحجز رقم #{$booking->id} | " .
                "العميل: [{$oldCustomerName} -> {$booking->customer_name}] | " .
                "حالة الحجز: [{$oldStatus} -> {$booking->status}] | " .
                "حالة الدفع: [{$oldPaymentStatus} -> {$booking->payment_status}] | " .
                "المبلغ الإجمالي: [{$oldTotalAmount} -> {$newTotalAmount}] (الفرق: {$amountDifference})";

            if (!empty($roomChangesDetails)) {
                $logDescription .= " | تغييرات الغرف: (" . implode('، ', $roomChangesDetails) . ")";
            }

            $agencyId = $booking->agency_id ?? $trip?->agency_id ?? $this->getAgencyId();

            AuditLog::create([
                'agency_id'      => $agencyId,
                'trip_id'        => $booking->trip_id,
                'action_type'    => 'update',
                'category'       => 'booking',
                'amount_changed' => $amountDifference,
                'seats_changed'  => 0,
                'description'    => $logDescription,
                'operator_name'  => $this->getOperatorName(),
            ]);
        });

        return redirect()->back()->with('success', 'تم تحديث الحجز بنجاح.');
    }

    public function updateBookingStatus(Request $request, $id, \App\Services\BookingSettlement $settlement)
    {
        $request->validate([
            'status'         => 'required|in:pending,confirmed,cancelled,completed',
            'payment_status' => 'required|in:unpaid,paid,failed,refunded',
        ]);

        $agencyId = $this->getAgencyId();
        $booking = Booking::where('id', $id)
            ->whereHas('trip', function ($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })
            ->firstOrFail();

        $oldPaymentStatus = $booking->payment_status;
        $newPaymentStatus = $request->payment_status;

        DB::transaction(function () use ($booking, $request, $settlement, $oldPaymentStatus, $newPaymentStatus) {
            $booking->update([
                'status'         => $request->status,
                'payment_status' => $newPaymentStatus,
            ]);

            if ($newPaymentStatus === 'paid' && $oldPaymentStatus !== 'paid' && !$booking->booked_by_agency) {
                // حجز عميل تأكد دفعه (تحويل بنكي): تسوية العمولة وإضافة المستحق للمحفظة
                $settlement->settle($booking);
            } elseif ($oldPaymentStatus === 'paid' && in_array($newPaymentStatus, ['unpaid', 'failed', 'refunded'], true)) {
                // تراجع عن الدفع أو استرداد: عكس الحركة والعمولة
                $settlement->reverse($booking);
            }
        });

        $desc = "تحديث حالة الحجز رقم (#{$booking->id}) إلى [{$request->status}] وحالة الدفع إلى [{$newPaymentStatus}].";
        $actionType = 'update';
        $category = 'booking';
        $amountChanged = 0;

        if ($newPaymentStatus === 'refunded') {
            $actionType = 'decrease';
            $category = 'money';
            $amountChanged = -$booking->total_amount;
            $desc = "تم رد وإرجاع المبلغ المالي بالكامل بقيمة ({$booking->total_amount}) للحجز رقم (#{$booking->id}) نظير إلغاء أو تسوية الطلب.";
        } elseif ($newPaymentStatus === 'paid') {
            $actionType = 'increase';
            $category = 'money';
            $amountChanged = $booking->total_amount;
        }

        AuditLog::create([
            'agency_id'      => $agencyId,
            'trip_id'        => $booking->trip_id,
            'action_type'    => $actionType,
            'category'       => $category,
            'amount_changed' => $amountChanged ?: 0,
            'seats_changed'  => 0,
            'description'    => $desc,
            'operator_name'  => $this->getOperatorName(),
        ]);

        return redirect()->back()->with('success', 'تم تحديث حالة الحجز بنجاح! 🎉');
    }

    public function editBooking($id)
    {
        $agencyId = $this->getAgencyId();

        $booking = Booking::where('id', $id)
            ->whereHas('trip', function ($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })
            ->with(['trip', 'bookingSeats'])
            ->firstOrFail();

        return view('agency.bookings.edit', compact('booking'));
    }

    public function cancel(Request $request, $id)
    {
        $agencyId = $this->getAgencyId();

        // 1. البحث عن الحجز المراد إلغاؤه
        $booking = Booking::where('id', $id)
            ->where(function ($query) use ($agencyId) {
                $query->where('agency_id', $agencyId)
                    ->orWhereHas('trip', function ($q) use ($agencyId) {
                        $q->where('agency_id', $agencyId);
                    });
            })
            ->with(['seats', 'bookingSeats', 'trip'])
            ->first();

        if (!$booking) {
            return redirect()->back()->with('error', 'عذراً، لم يتم العثور على الحجز أو لا تملك صلاحية إلغائه.');
        }

        if ($booking->status === 'cancelled') {
            return redirect()->back()->with('error', 'هذا الحجز ملغى بالفعل مسبقاً.');
        }

        DB::beginTransaction();

        try {
            $trip = $booking->trip;
            $seatsCollection = $booking->seats ?? $booking->bookingSeats;
            $seatsCount = $seatsCollection ? $seatsCollection->count() : 0;

            $originalAmount = floatval($booking->total_amount ?? $booking->total_price ?? $booking->amount ?? 0);
            $deductedAmount = -abs($originalAmount);
            $moyasarPaymentId = $booking->moyasar_payment_id ?? $booking->payment_id ?? null;

            $paymentStatus = 'cancelled';
            $refundLogNote = '';

            // 2. إلغاء المعاملات السابقة المرتبطة بالحجز أولاً
            app(\App\Services\BookingSettlement::class)->reverse($booking);

            // 3. معالجة إعادة المبلغ حسب طريقة الدفع
            if ($booking->payment_status === 'paid' && $originalAmount > 0) {
                if (!empty($moyasarPaymentId)) {
                    $refundResult = $this->refundMoyasarPayment($moyasarPaymentId, $originalAmount);
                    if ($refundResult['success']) {
                        $paymentStatus = 'refunded';
                        $refundLogNote = " وتم استرداد مبلغ ({$originalAmount}) ريال للبطاقة عبر ميسر.";
                    } else {
                        $paymentStatus = 'refund_failed';
                        $refundLogNote = " وتأجل الإرجاع التلقائي لتعثره: " . ($refundResult['message'] ?? '');
                    }
                } elseif ($booking->payment_method === 'balance') {
                    // نرجع بالضبط المبلغ اللي انخصم فعلاً من الرصيد وقت الحجز
                    $debited = abs((float) \App\Models\AgencyTransaction::where('booking_id', $booking->id)
                        ->where('type', 'booking_debit')
                        ->sum('amount'));

                    if ($debited > 0) {
                        \App\Models\AgencyTransaction::create([
                            'agency_id'   => $booking->agency_id ?? $agencyId,
                            'booking_id'  => $booking->id,
                            'type'        => 'refund',
                            'amount'      => $debited,
                            'status'      => 'available',
                            'description' => "إعادة مبلغ الحجز الملغى رقم (#{$booking->id}) لرصيد الوكالة",
                        ]);

                        $paymentStatus = 'refunded';
                        $refundLogNote = " وتمت إعادة مبلغ ({$debited}) ريال إلى رصيد الوكالة المتاح.";
                    }
                } elseif ($booking->payment_method === 'bank') {
                    $paymentStatus = 'refund_failed';
                    $refundLogNote = " والمبلغ ({$originalAmount}) ريال يحتاج استرداداً يدوياً للعميل.";
                }
            }
            // 3.5 استرداد الفواتير الإضافية (دفعات مستقلة في ميسر)
            $extraResult = app(\App\Services\ExtraChargeRefunder::class)->refundAll($booking);
            $refundLogNote .= $extraResult['note'];

            // 4. إعادة الغرف والمقاعد للمخزون
            if ($trip) {
                $roomOptions = is_array($trip->room_options)
                    ? $trip->room_options
                    : (json_decode($trip->room_options, true) ?? []);

                $bookingRoomType = $booking->room_type;
                $roomsToRestore = $booking->room_count ?? $booking->rooms_count ?? 1;

                if (!empty($bookingRoomType) && isset($roomOptions[$bookingRoomType])) {
                    $roomOptions[$bookingRoomType]['count'] += $roomsToRestore;
                }

                $trip->room_options = $roomOptions;

                if ($seatsCount > 0) {
                    $trip->available_seats += $seatsCount;
                }

                $trip->save();
            }

            // 5. تحديث حالة الحجز وتصفير القيم
            $booking->update([
                'status'              => 'cancelled',
                'payment_status'      => $paymentStatus,
                'platform_commission' => 0,
                'agency_amount'       => 0,
            ]);

            // 6. حذف المقاعد المحجوزة
            if (method_exists($booking, 'seats') && $booking->seats()) {
                $booking->seats()->delete();
            } elseif (method_exists($booking, 'bookingSeats') && $booking->bookingSeats()) {
                $booking->bookingSeats()->delete();
            }

            // 7. تسجيل الحدث في السجلات
            AuditLog::create([
                'agency_id'      => $agencyId,
                'trip_id'        => $booking->trip_id,
                'action_type'    => 'cancel_booking',
                'category'       => 'booking',
                'amount_changed' => $deductedAmount,
                'seats_changed'  => -$seatsCount,
                'description'    => "تم إلغاء الحجز رقم (#{$booking->id}) بواسطة الوكالة{$refundLogNote}.",
                'operator_name'  => $this->getOperatorName(),
            ]);

            DB::commit();

            $successMessage = 'تم إلغاء الحجز وإعادة الرصيد والمقاعد بنجاح.' . $refundLogNote;

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $successMessage], 200, [], JSON_UNESCAPED_UNICODE);
            }

            return redirect()->back()->with('success', $successMessage);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء إلغاء الحجز: ' . $e->getMessage()], 500, [], JSON_UNESCAPED_UNICODE);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء إلغاء الحجز: ' . $e->getMessage());
        }
    }

    /**
     * دالة مساعدة للاسترداد التلقائي عبر API ميسر
     */
    private function refundMoyasarPayment($paymentId, $amountInRiyal)
    {
        try {
            $amountInHalalas = (int) round($amountInRiyal * 100);

            $response = \Illuminate\Support\Facades\Http::withBasicAuth(config('services.moyasar.secret_key'), '')
                ->post("https://api.moyasar.com/v1/payments/{$paymentId}/refund", [
                    'amount' => $amountInHalalas,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data'    => $response->json(),
                ];
            }

            \Illuminate\Support\Facades\Log::error('Moyasar Refund Error: ' . $response->body());
            return [
                'success' => false,
                'message' => $response->json('message') ?? 'فشلت عملية الاسترداد من ميسر',
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Moyasar Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function confirmEdit($id)
    {
        $booking = Booking::findOrFail($id);

        $booking->update([
            'edit_notes' => 'done',
        ]);

        return back()->with('success', 'تم تأكيد وإغلاق طلب التعديل بنجاح.');
    }

    public function storeAgencyBooking(Request $request)
    {
        if ($request->has('seats') && !$request->has('selected_seats')) {
            $request->merge(['selected_seats' => $request->input('seats')]);
        } elseif ($request->has('selected_seats') && !$request->has('seats')) {
            $request->merge(['seats' => $request->input('selected_seats')]);
        }

        if (!$request->has('room_quantities') && $request->has('room_quantities_json')) {
            $request->merge(['room_quantities' => $request->input('room_quantities_json')]);
        }
        if (!$request->has('room_assignments') && $request->has('room_assignments_json')) {
            $request->merge(['room_assignments' => $request->input('room_assignments_json')]);
        }
        if (!$request->has('passenger_meals') && $request->has('passenger_meals_json')) {
            $request->merge(['passenger_meals' => $request->input('passenger_meals_json')]);
        }

        $request->validate([
            'trip_id'           => 'required|exists:trips,id',
            'room_quantities'   => 'nullable',
            'room_assignments'  => 'nullable',
            'selected_seats'    => 'required|string',
            'passenger_names'   => 'required|string',
            'passenger_ids'     => 'required|string',
            'passenger_genders' => 'required|string',
            'passenger_meals'   => 'nullable',
            'customer_phone'    => 'required|string',
            'customer_email'    => 'nullable|email',
            'payment_method'    => 'nullable|in:balance,card,bank,cash,pos',
            'card_name'         => 'nullable|required_if:payment_method,card|string|max:255',
            'bank_receipt'      => 'nullable|required_if:payment_method,bank|image|mimes:jpeg,png,jpg,webp|max:4096',
            'is_locked'         => 'nullable|boolean',
        ], [
            'bank_receipt.required_if' => 'يرجى إرفاق صورة إيصال التحويل البنكي لاعتماد الحجز.',
            'card_name.required_if'    => 'يرجى كتابة اسم صاحب البطاقة البنكية.',
            'customer_phone.required'  => 'رقم هاتف العميل أساسي للتواصل وإرسال التذاكر.',
        ]);

        $parseIncomingData = function ($source) {
            if (empty($source)) return [];
            if (is_array($source)) return $source;
            $decoded = json_decode($source, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            return collect(explode(',', $source))
                ->map(fn($item) => trim($item))
                ->filter(fn($val) => $val !== '')
                ->values()
                ->all();
        };

        $seatsArray            = $parseIncomingData($request->input('selected_seats'));
        $passengerNamesArray   = $parseIncomingData($request->input('passenger_names'));
        $passengerIdsArray     = array_filter($parseIncomingData($request->input('passenger_ids')));
        $passengerGendersArray = $parseIncomingData($request->input('passenger_genders'));

        if (count($seatsArray) !== count($passengerGendersArray) || count($seatsArray) !== count($passengerNamesArray)) {
            return redirect()->back()->withInput()->with('error', 'عذراً، حدث خطأ في مطابقة بيانات الركاب مع المقاعد المختارة بالجدول الهندسي.');
        }

        $agentId = $this->getAgencyId();

        $roomQuantities  = is_array($request->input('room_quantities')) ? $request->input('room_quantities') : (json_decode($request->input('room_quantities'), true) ?? []);
        $roomAssignments = is_array($request->input('room_assignments')) ? $request->input('room_assignments') : (json_decode($request->input('room_assignments'), true) ?? []);
        $passengerMeals  = is_array($request->input('passenger_meals')) ? $request->input('passenger_meals') : (json_decode($request->input('passenger_meals'), true) ?? []);

        $roomCount       = array_sum($roomQuantities);
        $chosenRoomTypes = array_keys(array_filter($roomQuantities, fn($qty) => $qty > 0));
        $finalRoomType   = !empty($chosenRoomTypes) ? implode(', ', $chosenRoomTypes) : 'shared';

        try {
            $booking = DB::transaction(function () use ($request, $seatsArray, $passengerNamesArray, $passengerIdsArray, $passengerGendersArray, $agentId, $roomCount, $roomQuantities, $finalRoomType, $roomAssignments, $passengerMeals) {

                $trip = Trip::lockForUpdate()->findOrFail($request->trip_id);

                if (!empty($passengerIdsArray)) {
                    $isAnyPassengerAlreadyRegistered = BookingSeat::where('trip_id', $trip->id)
                        ->whereIn('passenger_national_id', $passengerIdsArray)
                        ->exists();

                    if ($isAnyPassengerAlreadyRegistered) {
                        throw new \Exception('🛑 تنبيه: أحد المسافرين مسجل بالفعل في كشوفات هذه الرحلة مسبقاً.');
                    }
                }

                if (count($seatsArray) > $trip->available_seats) {
                    throw new \Exception('عذراً، عدد المقاعد المطلوبة أكبر من المقاعد المتاحة بالحافلة حالياً.');
                }

                $alreadyBooked = BookingSeat::where('trip_id', $trip->id)
                    ->whereIn('seat_number', $seatsArray)
                    ->exists();

                if ($alreadyBooked) {
                    throw new \Exception('المقاعد المحددة تم حجزها للتو من نافذة أخرى. يرجى تحديث المخطط.');
                }

                $roomOptions = is_array($trip->room_options) ? $trip->room_options : (json_decode($trip->room_options, true) ?: []);
                $mealOptions = is_array($trip->meal_options) ? $trip->meal_options : (json_decode($trip->meal_options, true) ?: []);

                foreach ($roomQuantities as $type => $qty) {
                    $qty = intval($qty);
                    if ($qty > 0) {
                        $availableCount = isset($roomOptions[$type]['count']) ? intval($roomOptions[$type]['count']) : 0;
                        if ($qty > $availableCount) {
                            throw new \Exception("عذراً، الغرف من النوع المختار لم تعد كافية بالمخزون الحالي.");
                        }
                        // الخصم الصحيح من مخزون الغرف المتاحة
                        $roomOptions[$type]['count'] = $availableCount - $qty;
                    }
                }

                $room_prices = [
                    'single' => floatval($roomOptions['single']['price'] ?? ($trip->price_single ?? 0)),
                    'double' => floatval($roomOptions['double']['price'] ?? ($trip->price_double ?? 0)),
                    'triple' => floatval($roomOptions['triple']['price'] ?? ($trip->price_triple ?? 0)),
                    'quad'   => floatval($roomOptions['quad']['price']   ?? ($trip->price_quad   ?? 0)),
                ];

                $totalRoomCost = 0;
                foreach ($roomQuantities as $type => $qty) {
                    $totalRoomCost += (intval($qty) * ($room_prices[$type] ?? 0));
                }

                $seatBasePrice  = floatval($trip->price ?? 0);
                $totalSeatsCost = 0;
                $roomCapacities = ['single' => 1, 'double' => 2, 'triple' => 3, 'quad' => 4];

                $availableFreeSlots = [];
                foreach ($roomQuantities as $type => $qty) {
                    if ($qty > 0) {
                        $availableFreeSlots[$type] = intval($qty) * ($roomCapacities[$type] ?? 0);
                    }
                }

                foreach ($seatsArray as $seatNum) {
                    $assignment = $roomAssignments[$seatNum] ?? 'shared';
                    if ($assignment === 'shared') {
                        $totalSeatsCost += $seatBasePrice;
                    } else {
                        $roomType = explode('_', $assignment)[0];
                        if (isset($availableFreeSlots[$roomType]) && $availableFreeSlots[$roomType] > 0) {
                            $availableFreeSlots[$roomType]--;
                        } else {
                            $totalSeatsCost += $seatBasePrice;
                        }
                    }
                }

                $defaultMealPrices = [
                    'breakfast' => (!empty($mealOptions['individual']['breakfast']['active'])) ? floatval($mealOptions['individual']['breakfast']['price'] ?? 0) : 0,
                    'lunch'     => (!empty($mealOptions['individual']['lunch']['active'])) ? floatval($mealOptions['individual']['lunch']['price'] ?? 0) : 0,
                    'dinner'    => (!empty($mealOptions['individual']['dinner']['active'])) ? floatval($mealOptions['individual']['dinner']['price'] ?? 0) : 0,
                ];

                $totalMealsCost    = 0;
                $totalPackagesCost = 0;
                $seatMealsDetails  = [];

                foreach ($seatsArray as $seatNum) {
                    $assignment  = $roomAssignments[$seatNum] ?? 'shared';
                    $roomType    = $assignment !== 'shared' ? explode('_', $assignment)[0] : 'shared';

                    $roomPackage = null;
                    if ($roomType !== 'shared' && isset($roomOptions[$roomType]['packages'])) {
                        $packagesList = $roomOptions[$roomType]['packages'];
                        $roomPackage  = $packagesList[0] ?? null;
                    }

                    $hasBf = !empty($passengerMeals[$seatNum]['breakfast']) || ($roomPackage && !empty($roomPackage['meals']['breakfast']));
                    $hasLn = !empty($passengerMeals[$seatNum]['lunch']) || ($roomPackage && !empty($roomPackage['meals']['lunch']));
                    $hasDn = !empty($passengerMeals[$seatNum]['dinner']) || ($roomPackage && !empty($roomPackage['meals']['dinner']));

                    $calculatedBf = $hasBf ? (($roomPackage && !empty($roomPackage['meals']['breakfast'])) ? 0 : $defaultMealPrices['breakfast']) : 0;
                    $calculatedLn = $hasLn ? (($roomPackage && !empty($roomPackage['meals']['lunch'])) ? 0 : $defaultMealPrices['lunch']) : 0;
                    $calculatedDn = $hasDn ? (($roomPackage && !empty($roomPackage['meals']['dinner'])) ? 0 : $defaultMealPrices['dinner']) : 0;

                    $totalMealsCost += ($calculatedBf + $calculatedLn + $calculatedDn);

                    $packagePrice = 0;
                    if ($roomPackage) {
                        $packagePrice = floatval($roomPackage['price'] ?? ($roomPackage['package_price'] ?? 0));
                        $totalPackagesCost += $packagePrice;
                    }

                    $packageName = $passengerMeals[$seatNum]['package_name'] ?? null;
                    if (!$packageName) {
                        if ($roomPackage && !empty($roomPackage['name'])) {
                            $packageName = $roomPackage['name'];
                        } elseif ($hasBf || $hasLn || $hasDn) {
                            $packageName = 'وجبات اختيارية';
                        } else {
                            $packageName = 'الباقة الأساسية';
                        }
                    }

                    $seatMealsDetails[$seatNum] = [
                        'bf'            => $calculatedBf,
                        'ln'            => $calculatedLn,
                        'dn'            => $calculatedDn,
                        'hasBf'         => $hasBf ? 1 : 0,
                        'hasLn'         => $hasLn ? 1 : 0,
                        'hasDn'         => $hasDn ? 1 : 0,
                        'package_name'  => $packageName,
                        'package_price' => $packagePrice
                    ];
                }

                $totalAmount = $totalRoomCost + $totalSeatsCost + $totalMealsCost + $totalPackagesCost;
                $isLocked = $request->boolean('is_locked', false);

                if ($isLocked) {
                    $bookingStatus = 'locked';
                    $paymentStatus = 'unpaid';
                } else {
                    $isDirectPayment = in_array($request->payment_method, ['balance', 'card', 'cash', 'pos']);
                    $bookingStatus   = $isDirectPayment ? 'confirmed' : 'pending';
                    $paymentStatus   = $isDirectPayment ? 'paid' : 'pending';
                }

                $leadPassengerName = $passengerNamesArray[0] ?? 'عميل محجوز بواسطة الوكالة';
                $leadPassengerId   = $passengerIdsArray[0] ?? null;

                $booking = Booking::create([
                    'agency_id'            => $agentId,
                    'user_id'              => null,
                    'trip_id'              => $trip->id,
                    'customer_name'        => $leadPassengerName,
                    'customer_email'       => $request->input('customer_email') ?? 'agency-guest@abur.com',
                    'customer_phone'       => $request->input('customer_phone'),
                    'customer_national_id' => $leadPassengerId,
                    'room_type'            => $finalRoomType,
                    'room_count'           => $roomCount,
                    'payment_method'       => $request->payment_method,
                    'card_name'            => ($request->payment_method === 'card') ? $request->input('card_name') : null,
                    'status'               => $bookingStatus,
                    'payment_status'       => $paymentStatus,
                    'total_amount'         => $totalAmount,
                    'booked_by_agency'     => true,
                    'is_locked'            => $isLocked,
                    'room_quantities'      => is_array($roomQuantities) ? json_encode($roomQuantities, JSON_UNESCAPED_UNICODE) : $roomQuantities,
                    'room_assignments'     => is_array($roomAssignments) ? json_encode($roomAssignments, JSON_UNESCAPED_UNICODE) : $roomAssignments,
                    'passenger_meals'      => is_array($passengerMeals) ? json_encode($passengerMeals, JSON_UNESCAPED_UNICODE) : $passengerMeals,
                ]);

                $agency = Agency::lockForUpdate()->find($agentId);

                // التحقق والخصم في حال كانت طريقة الدفع عن طريق المحفظة (balance)
                if ($request->payment_method === 'balance' && !$isLocked && $agency) {
                    // 1. التحقق من أن الرصيد المتاح كافٍ لإتمام العملية
                    if ($agency->available_balance < $totalAmount) {
                        throw new \Exception('عذراً، الرصيد المتاح في المحفظة غير كافٍ لإتمام الحجز. الرصيد المتاح: ' . number_format($agency->available_balance, 2) . ' ر.س');
                    }

                    // 2. حساب عمولة الوكالة بناءً على نظام العمولة الجديد (لكل مسافر + الحدود)
                    $passengersCount = count($seatsArray);
                    $calculatedCommission = $agency->calculateBookingCommission($passengersCount);

                    // 3. تسجيل عملية الخصم بقيمة سالبة
                    $agency->transactions()->create([
                        'booking_id'  => $booking->id,
                        'type'        => 'booking_debit',
                        'amount'      => -$totalAmount, // قيمة سالبة للخصم من المحفظة
                        'description' => "خصم قيمة حجز من رصيد المحفظة - رحلة: ({$trip->title}) - حجز #{$booking->id} (عدد المسافرين: {$passengersCount})",
                        'status'      => 'completed',
                    ]);
                }

                if ($request->hasFile('bank_receipt') && $request->payment_method === 'bank' && !$isLocked) {
                    $path = $request->file('bank_receipt')->store('bank_receipts', 'public');
                    $booking->update(['bank_receipt' => $path]);
                }

                foreach ($seatsArray as $index => $seatNum) {
                    $pName   = $passengerNamesArray[$index] ?? $leadPassengerName;
                    $pId     = $passengerIdsArray[$index] ?? $leadPassengerId;
                    $pGender = $passengerGendersArray[$index] ?? 'male';
                    $rType   = $roomAssignments[$seatNum] ?? 'shared';
                    $md      = $seatMealsDetails[$seatNum];

                    $bookedSeat = $booking->bookingSeats()->create([
                        'trip_id'               => $trip->id,
                        'seat_number'           => $seatNum,
                        'passenger_name'        => $pName,
                        'gender'                => $pGender,
                        'passenger_national_id' => $pId,
                        'room_type'             => $rType,
                        'with_breakfast'        => $md['hasBf'],
                        'with_lunch'            => $md['hasLn'],
                        'with_dinner'           => $md['hasDn'],
                        'breakfast_price'       => $md['bf'],
                        'lunch_price'           => $md['ln'],
                        'dinner_price'          => $md['dn'],
                        'trip_room_id'          => null,
                        'meal_options'          => [
                            'package_name'  => $md['package_name'],
                            'package_price' => $md['package_price']
                        ],
                    ]);

                    $qrPayload = [
                        'ticket_no'  => 'TKT-' . $bookedSeat->id,
                        'booking_no' => 'BK-' . $booking->id,
                        'trip'       => $trip->title ?? $trip->id,
                        'seat'       => $seatNum,
                        'passenger'  => $pName,
                        'id_number'  => $pId,
                        'date'       => $trip->departure_at ? \Carbon\Carbon::parse($trip->departure_at)->format('Y-m-d') : now()->format('Y-m-d'),
                    ];

                    $image    = QrCode::format('svg')->encoding('UTF-8')->size(300)->generate(json_encode($qrPayload, JSON_UNESCAPED_UNICODE));
                    $fullPath = 'qrcodes/bookings/' . $booking->id . '/ticket_' . $bookedSeat->id . '.svg';

                    Storage::disk('public')->put($fullPath, $image);
                    $bookedSeat->update(['qr_code_path' => $fullPath]);
                }

                $trip->room_options     = $roomOptions;
                $trip->available_seats -= count($seatsArray);
                $trip->save();

                AuditLog::create([
                    'agency_id'      => $agentId,
                    'trip_id'        => $trip->id,
                    'action_type'    => $booking->is_locked ? 'lock' : 'create',
                    'category'       => 'booking',
                    'amount_changed' => $totalAmount,
                    'seats_changed'  => count($seatsArray),
                    'description'    => $booking->is_locked
                        ? "قفل مقاعد للرحلة: ({$trip->title}) برقم حجز #{$booking->id}."
                        : "إضافة حجز جديد للرحلة: ({$trip->title}) برقم حجز #{$booking->id}.",
                    'operator_name'  => $this->getOperatorName(),
                ]);

                return $booking;
            });

            $message = $booking->is_locked
                ? '🔒 تم قفل المقاعد المحددة للرحلة بنجاح عبر لوحة التحكم!'
                : '✨ تم تسجيل وتأكيد الحجز بنجاح عبر لوحة تحكم الوكالة! رقم السجل الداخلي: #' . $booking->id;

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'       => 'success',
                    'message'      => $message,
                    'redirect_url' => route('agency.trips.index')
                ]);
            }

            return redirect()->route('agency.trips.index')->with('success', $message);
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage()
                ], 422);
            }

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function releaseLockedBooking($bookingId)
    {
        try {
            DB::transaction(function () use ($bookingId) {
                // 1. البحث عن الحجز برقم الحجز المباشر
                $booking = Booking::where('id', $bookingId)->first();

                // 2. إذا لم يجد الحجز، يبحث عنه عن طريق معرف المقعد أو رقم المقعد في جدول BookingSeat
                if (!$booking) {
                    $seat = \App\Models\BookingSeat::where('id', $bookingId)
                        ->orWhere('seat_number', $bookingId)
                        ->first();

                    if ($seat) {
                        $booking = $seat->booking;
                    }
                }

                // 3. إذا تعذر العثور على الحجز بأي طريقة
                if (!$booking) {
                    throw new \Exception("لم يتم العثور على أي حجز مرتبك بالرقم المُرسل (#{$bookingId}).");
                }

                $trip = Trip::lockForUpdate()->findOrFail($booking->trip_id);

                $roomQuantities = json_decode($booking->room_quantities, true) ?: [];
                $roomOptions    = is_array($trip->room_options) ? $trip->room_options : (json_decode($trip->room_options, true) ?: []);

                foreach ($roomQuantities as $type => $qty) {
                    $qty = intval($qty);
                    if ($qty > 0 && isset($roomOptions[$type])) {
                        $roomOptions[$type]['count'] = (isset($roomOptions[$type]['count']) ? intval($roomOptions[$type]['count']) : 0) + $qty;
                    }
                }
                $trip->room_options = $roomOptions;

                $seatsCount = $booking->bookingSeats()->count();
                $trip->available_seats += $seatsCount;
                $trip->save();

                foreach ($booking->bookingSeats as $seatItem) {
                    if ($seatItem->qr_code_path && Storage::disk('public')->exists($seatItem->qr_code_path)) {
                        Storage::disk('public')->delete($seatItem->qr_code_path);
                    }
                }

                $booking->bookingSeats()->delete();
                $booking->delete();

                AuditLog::create([
                    'agency_id'      => $this->getAgencyId(),
                    'trip_id'        => $trip->id,
                    'action_type'    => 'unlock',
                    'category'       => 'booking',
                    'amount_changed' => 0,
                    'seats_changed'  => -$seatsCount,
                    'description'    => "إلغاء قفل المقاعد وإعادتها للرحلة: ({$trip->title}) للحجز رقم #{$booking->id}.",
                    'operator_name'  => $this->getOperatorName(),
                ]);
            });

            return redirect()->back()->with('success', '🔓 تم إلغاء قفل المقاعد بنجاح وإعادتها للرحلة لتكون متاحة للجميع.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء فك قفل المقاعد: ' . $e->getMessage());
        }
    }

    public function deletebooking($id)
    {
        $agencyId = $this->getAgencyId();
        $bookingSeat = BookingSeat::with('booking.trip')->find($id);

        if (!$bookingSeat) {
            return redirect()->back()->with('error', 'عذراً، حجز هذا المقعد غير موجود في النظام! ⚠️');
        }

        if ($agencyId && (!isset($bookingSeat->booking->trip) || $bookingSeat->booking->trip->agency_id != $agencyId)) {
            return redirect()->back()->with('error', 'غير مصرح لك بإلغاء حجز هذا المقعد! ❌');
        }

        DB::transaction(function () use ($bookingSeat, $agencyId) {
            $booking = $bookingSeat->booking;
            $trip = $booking->trip;

            $trip->increment('available_seats');

            $totalSeatsInBooking = max(1, $booking->bookingSeats()->count());
            $estimatedSeatPrice = floatval($booking->total_amount / $totalSeatsInBooking);

            $newTotalAmount = max(0, $booking->total_amount - $estimatedSeatPrice);

            AuditLog::create([
                'agency_id'      => $agencyId,
                'trip_id'        => $trip->id,
                'action_type'    => 'delete',
                'category'       => 'booking',
                'amount_changed' => -$estimatedSeatPrice,
                'seats_changed'  => -1,
                'description'    => "إلغاء حجز المقعد رقم ({$bookingSeat->seat_number}) للمسافر ({$bookingSeat->passenger_name}) في الرحلة: ({$trip->title}).",
                'operator_name'  => $this->getOperatorName(),
            ]);

            $bookingSeat->delete();

            if ($totalSeatsInBooking <= 1) {
                $booking->delete();
            } else {
                $booking->update([
                    'total_amount' => $newTotalAmount
                ]);
            }
        });

        return redirect()->back()->with('success', 'تم إلغاء حجز المقعد بنجاح، وتحديث المبلغ الإجمالي للحجز. 🗑️');
    }

    public function updateSeat(Request $request, $id)
    {
        $agencyId = $this->getAgencyId();
        $bookingSeat = BookingSeat::find($id);

        if (!$bookingSeat) {
            return redirect()->back()->with('error', 'عذراً، حجز هذا المقعد غير موجود! ⚠️');
        }

        if ($agencyId && $bookingSeat->booking->trip->agency_id != $agencyId) {
            return redirect()->back()->with('error', 'غير مصرح لك بتعديل هذا الحجز! ❌');
        }

        $request->validate([
            'new_seat_number' => 'required|integer|min:1',
        ]);

        $newSeatNumber = $request->input('new_seat_number');
        $tripId = $bookingSeat->booking->trip_id;

        $isSeatTaken = BookingSeat::where('seat_number', $newSeatNumber)
            ->whereHas('booking', function ($query) use ($tripId) {
                $query->where('trip_id', $tripId);
            })
            ->where('id', '!=', $id)
            ->exists();

        if ($isSeatTaken) {
            return redirect()->back()->with('error', 'عذراً، المقعد الجديد رقم (' . $newSeatNumber . ') محجوز بالفعل لمسافر آخر في هذه الرحلة! ⚠️');
        }

        $bookingSeat->update([
            'seat_number' => $newSeatNumber
        ]);

        return redirect()->back()->with('success', 'تم تغيير رقم مقعد المسافر بنجاح إلى المقعد (' . $newSeatNumber . ') 💺');
    }

    public function printInvoice($id)
    {
        $agencyId = $this->getAgencyId();

        $booking = Booking::where('id', $id)
            ->whereHas('trip', function ($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })
            ->with(['trip', 'bookingSeats'])
            ->firstOrFail();

        return view('agency.bookings.invoice', compact('booking'));
    }

    // =========================================================================
    // 4. ROOMS & PASSENGER DISTRIBUTION (التسكين وتوزيع الغرف المشتركة)
    // =========================================================================

    public function showshared(Request $request)
    {
        $agencyId = $this->getAgencyId();
        $trips = Trip::where('agency_id', $agencyId)->latest()->get();
        $selectedTripId = $request->input('trip_id');

        $unassignedMales = collect();
        $unassignedFemales = collect();
        $allpeopleintrip = collect();
        $roomsList = [];

        $totalRooms = 0;
        $totalBeds = 0;
        $occupiedBeds = 0;
        $availableBeds = 0;

        if (!empty($selectedTripId)) {
            $trip = Trip::where('id', $selectedTripId)
                ->where('agency_id', $agencyId)
                ->first();

            if ($trip) {
                $allpeopleintrip = BookingSeat::with('room')
                    ->where('trip_id', $trip->id)
                    ->get();

                $allTripRooms = TripRoom::where('trip_id', $trip->id)->get();
                $roomsByUid = $allTripRooms->keyBy('room_uid');

                $typeToCapacity = [
                    'single' => 1,
                    'double' => 2,
                    'triple' => 3,
                    'quad'   => 4,
                ];

                $roomOptions = is_array($trip->room_options)
                    ? $trip->room_options
                    : (json_decode($trip->room_options, true) ?: []);

                foreach ($typeToCapacity as $type => $capacity) {
                    $expectedCount = $roomOptions[$type]['count'] ?? 0;

                    for ($i = 1; $i <= $expectedCount; $i++) {
                        $roomUid = "room_{$capacity}_{$i}";
                        $roomObj = $roomsByUid->get($roomUid);

                        $seatsInRoom = $allpeopleintrip->filter(function ($seat) use ($roomUid, $roomObj) {
                            if ($seat->trip_room_id == $roomUid) {
                                return true;
                            }
                            if ($roomObj && $seat->trip_room_id == $roomObj->id) {
                                return true;
                            }
                            return false;
                        })->values();

                        $roomGender = $seatsInRoom->first()->gender ?? 'none';
                        $displayRoomNumber = $roomObj?->room_number ?? $i;
                        $notes = $roomObj?->notes ?? '';

                        $actualType = $roomObj?->room_type ?? $type;
                        $actualCapacity = $typeToCapacity[$actualType] ?? $capacity;
                        $occupiedCount = $seatsInRoom->count();
                        $isFull = ($occupiedCount >= $actualCapacity);

                        $bookingIds = $seatsInRoom->pluck('booking_id')->filter()->unique();
                        $isSameBooking = ($isFull && $bookingIds->count() === 1);

                        $roomsList[] = (object)[
                            'id'              => $roomObj?->id,
                            'room_uid'        => $roomUid,
                            'room_type'       => $actualType,
                            'capacity'        => $actualCapacity,
                            'occupied_count'  => $occupiedCount,
                            'room_number'     => $displayRoomNumber,
                            'notes'           => $notes,
                            'gender_type'     => $roomGender,
                            'seats'           => $seatsInRoom,
                            'room_obj'        => $roomObj,
                            'is_full'         => $isFull,
                            'is_same_booking' => $isSameBooking
                        ];
                    }
                }

                $roomsList = collect($roomsList)->sortBy([
                    ['is_full', 'asc'],
                    ['occupied_count', 'desc'],
                    ['capacity', 'asc'],
                    ['room_number', 'asc'],
                ])->values()->all();

                $totalRooms = count($roomsList);
                $totalBeds  = array_sum(array_column($roomsList, 'capacity'));

                // تم التعديل هنا: جلب المسافرين الـ shared فقط
                $unassignedMales = $allpeopleintrip
                    ->whereNull('trip_room_id')
                    ->where('gender', 'male')
                    ->where('room_type', 'shared')
                    ->filter(function ($seat) {
                        return $seat->room_type !== 'blocked' && !str_contains($seat->passenger_name ?? '', 'مغلق');
                    })
                    ->values();

                // تم التعديل هنا: جلب المسافرات الـ shared فقط
                $unassignedFemales = $allpeopleintrip
                    ->whereNull('trip_room_id')
                    ->where('gender', 'female')
                    ->where('room_type', 'shared')
                    ->filter(function ($seat) {
                        return $seat->room_type !== 'blocked' && !str_contains($seat->passenger_name ?? '', 'مغلق');
                    })
                    ->values();

                $occupiedBeds  = $allpeopleintrip->whereNotNull('trip_room_id')->count();
                $availableBeds = max(0, $totalBeds - $occupiedBeds);
            }
        }

        $nonSharedMales = $allpeopleintrip->where('gender', 'male')->where('room_type', '!=', 'shared')->count();
        $nonSharedFemales = $allpeopleintrip->where('gender', 'female')->where('room_type', '!=', 'shared')->count();

        return view("agency.bookings.rooms", compact(
            'trips',
            'selectedTripId',
            'unassignedMales',
            'unassignedFemales',
            'nonSharedMales',
            'nonSharedFemales',
            'allpeopleintrip',
            'roomsList',
            'totalRooms',
            'totalBeds',
            'occupiedBeds',
            'availableBeds'
        ));
    }

    public function assignPassenger(Request $request)
    {
        $agencyId = $this->getAgencyId();
        $seatId = $request->input('booking_seat_id');
        $tripRoomUid = $request->input('trip_room_id');
        $tripId = $request->input('trip_id');

        $trip = Trip::where('id', $tripId)->where('agency_id', $agencyId)->firstOrFail();

        $seat = BookingSeat::where('id', $seatId)
            ->where('trip_id', $trip->id)
            ->whereNull('trip_room_id')
            ->firstOrFail();

        if (is_numeric($tripRoomUid)) {
            $room = TripRoom::where('id', $tripRoomUid)
                ->where('trip_id', $trip->id)
                ->firstOrFail();
        } else {
            if (preg_match('/^room_(\d+)_(\d+)$/', $tripRoomUid, $matches)) {
                $capacityNum = intval($matches[1]);
                $roomNumber = intval($matches[2]);

                $typeMapping = [
                    1 => 'single',
                    2 => 'double',
                    3 => 'triple',
                    4 => 'quad'
                ];
                $roomType = $typeMapping[$capacityNum] ?? 'single';
            } else {
                return redirect()->back()->with('error', 'معرف الغرفة غير صالح برمجياً.');
            }

            $room = TripRoom::firstOrCreate(
                [
                    'trip_id'  => $trip->id,
                    'room_uid' => $tripRoomUid,
                ],
                [
                    'room_type'   => $roomType,
                    'room_number' => $roomNumber,
                ]
            );
        }

        $typeCapacityMap = [
            'single' => 1,
            'double' => 2,
            'triple' => 3,
            'quad'   => 4,
        ];
        $roomCapacity = $typeCapacityMap[$room->room_type] ?? 1;

        $currentAllocated = BookingSeat::where('trip_id', $trip->id)
            ->where('trip_room_id', $room->id)
            ->get();

        if ($currentAllocated->count() >= $roomCapacity) {
            return redirect()->back()->with('error', 'عذراً، هذه الغرفة ممتلئة بالكامل ولن تستوعب أسرة إضافية!');
        }

        // if ($currentAllocated->isNotEmpty()) {
        //     $roomGender = $currentAllocated->first()->gender;
        //     if ($roomGender !== $seat->gender) {
        //         return redirect()->back()->with('error', 'نظام الأمان اللوجستي: لا يمكن تسكين الرجال والنساء معاً في غرف المشاع الموحدة.');
        //     }
        // }

        $seat->trip_room_id = $room->id;
        $seat->save();

        return redirect()->back()->with('success', "تمت عملية تسكين المسافر ({$seat->passenger_name}) بنجاح وتحديث المخطط الفندقي للرحلة.");
    }

    public function removePassenger(Request $request)
    {
        $agencyId = $this->getAgencyId();
        $seatId = $request->input('booking_seat_id');

        $seat = BookingSeat::findOrFail($seatId);

        Trip::where('id', $seat->trip_id)->where('agency_id', $agencyId)->firstOrFail();

        $seat->trip_room_id = null;
        $seat->save();

        return redirect()->back()->with('success', 'تم إخراج المسافر من الغرفة وإعادته لقائمة الانتظار.');
    }

    // =========================================================================
    // 5. AGENCY RESOURCES (إدارة السائقين، المركبات، والحسابات البنكية)
    // =========================================================================

    public function storeDriver(Request $request)
    {
        $agencyId = $this->getAgencyId();

        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'phone'               => 'required|string|max:20',
            'national_id'         => 'required|string|unique:drivers,national_id|max:20',
            'license_number'      => 'required|string|max:30',
            'license_expiry_date' => 'required|date',
        ]);

        Driver::create([
            'agency_id'           => $agencyId,
            'name'                => $validated['name'],
            'phone'               => $validated['phone'],
            'national_id'         => $validated['national_id'],
            'license_number'      => $validated['license_number'],
            'license_expiry_date' => $validated['license_expiry_date'],
            'status'              => 'available',
        ]);

        return redirect()->back()->with('success', 'تم تسجيل الكابتن الجديد بنجاح وانضمامه لطاقم القيادة! 🎉');
    }

    public function storeVehicle(Request $request)
    {
        $agencyId = $this->getAgencyId();

        $validated = $request->validate([
            'type'          => 'required|in:bus,flight',
            'plate_number'  => 'required|string|unique:vehicles,plate_number|max:30',
            'total_seats'   => 'required|integer|min:1',
            'vehicle_model' => 'required|string|max:255',
            'check_date'    => 'nullable|date',
        ]);

        Vehicle::create([
            'agency_id'     => $agencyId,
            'type'          => $validated['type'],
            'plate_number'  => $validated['plate_number'],
            'total_seats'   => $validated['total_seats'],
            'vehicle_model' => $validated['vehicle_model'],
            'check_date'    => $validated['check_date'],
            'status'        => 'active',
        ]);

        return redirect()->back()->with('success', 'تم إضافة المركبة الجديدة وتثبيتها في أسطول المنصة بنجاح! 🚀');
    }

    public function storeBank(Request $request)
    {
        $request->validate([
            'bank_name'      => 'required|string|max:255',
            'account_number' => 'nullable|string|max:35',
            'iban'           => 'required|string|max:35',
            'qr_code'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('qr_code')) {
            $imagePath = $request->file('qr_code')->store('banks_qr', 'public');
        }

        AgencyBankAccount::create([
            'agency_id'      => $this->getAgencyId(),
            'bank_name'      => $request->bank_name,
            'account_number' => $request->account_number,
            'iban'           => $request->iban,
            'qr_code'        => $imagePath,
            'is_active'      => true,
        ]);

        return redirect()->back()->with('success', 'تم إضافة الحساب البنكي بنجاح ✅');
    }

    // =========================================================================
    // 6. REPORTS & LOGS (التقارير المالية والتدقيق الحسابي)
    // =========================================================================

    public function reports(Request $request)
    {
        $agencyId = $this->getAgencyId();
        $range = $request->get('range', 'month');

        // ================= 1. استعلام الحجوزات حسب الفترة =================
        $dateQuery = Booking::whereHas('trip', function ($q) use ($agencyId) {
            $q->where('agency_id', $agencyId);
        });

        if ($range == 'week') {
            $dateQuery->where('created_at', '>=', now()->subDays(7));
        } elseif ($range == 'month') {
            $dateQuery->where('created_at', '>=', now()->subDays(30));
        }

        // ================= 2. استعلام التقييمات حسب الفترة =================
        $agencyRatingQuery = AgencyRating::where('agency_id', $agencyId);
        if ($range == 'week') {
            $agencyRatingQuery->where('created_at', '>=', now()->subDays(7));
        } elseif ($range == 'month') {
            $agencyRatingQuery->where('created_at', '>=', now()->subDays(30));
        }

        // حساب متوسط التقييم العام للفترة مع إهمال الحقول الخالية NULL
        $ratingsList = $agencyRatingQuery->get();
        $avgRating = $ratingsList->avg(function ($rating) {
            $scores = array_filter([
                $rating->transport_rating,
                $rating->staff_rating,
                $rating->organization_rating,
                $rating->price_value_rating,
                $rating->services_rating,
                $rating->accommodation_rating,
                $rating->meals_rating,
            ], fn($val) => !is_null($val) && $val > 0);

            return count($scores) > 0 ? array_sum($scores) / count($scores) : null;
        });

        // ================= 3. جلب السائقين والمركبات والكادر =================
        $drivers  = Driver::where('agency_id', $agencyId)->get();
        $vehicles = Vehicle::where('agency_id', $agencyId)->get();
        // جلب الكادر التابع للوكالة 
        $staffMembers = Staff::where('agency_id', $agencyId)->get();
        // ================= 4. تجميع الإحصائيات الشاملة =================
        $stats = (object)[
            'total_revenue'       => (clone $dateQuery)->where('status', 'confirmed')->sum('total_amount'),
            'net_revenue'         => (clone $dateQuery)->whereIn('status', ['approved', 'confirmed'])->sum('agency_amount'),
            'total_bookings'      => (clone $dateQuery)->where('status', 'confirmed')->withCount('bookingSeats')->get()->sum('booking_seats_count'),
            'pending_amount'      => (clone $dateQuery)->where('status', 'pending')->sum('total_amount'),
            'compliance_rate'     => 100,
            'average_rating'      => number_format($avgRating ?? 0, 1),

            // إحصائيات السائقين والمركبات
            'total_drivers'       => $drivers->count(),
            'active_drivers'      => $drivers->where('status', 'active')->count(),
            'total_vehicles'      => $vehicles->count(),
            'active_vehicles'     => $vehicles->where('status', 'active')->count(),
            'total_vehicle_seats' => $vehicles->sum('total_seats'),
        ];

        // ================= 5. جلب الرحلات الأخيرة وتقييماتها ومواردها =================
        // تم إضافة (driver, vehicle, staff) لتقليل عدد الاستعلامات داخل العرض
        $tripsQuery = Trip::where('agency_id', $agencyId)
            ->with(['ratings.user', 'bookings', 'driver', 'vehicle', 'staff']);

        if ($range == 'week') {
            $tripsQuery->where('created_at', '>=', now()->subDays(7));
        } elseif ($range == 'month') {
            $tripsQuery->where('created_at', '>=', now()->subDays(30));
        }

        $recent_transactions = $tripsQuery->latest()->take(10)->get()->map(function ($trip) {
            $ratings = $trip->ratings;

            // حساب صافي المستحق للوكالة المباشر من الحجوزات المقبولة/المؤكدة
            $agencyAmountSum = $trip->bookings
                ->whereIn('status', ['approved', 'confirmed'])
                ->sum('agency_amount');

            $avgRating = $ratings->avg(function ($r) {
                $scores = array_filter([
                    $r->transport_rating,
                    $r->staff_rating,
                    $r->organization_rating,
                    $r->price_value_rating,
                    $r->services_rating,
                    $r->accommodation_rating,
                    $r->meals_rating
                ], fn($val) => !is_null($val) && $val > 0);

                return count($scores) > 0 ? array_sum($scores) / count($scores) : null;
            });

            return [
                'id'             => $trip->id,
                'type'           => $trip->title ?? 'رحلة نقل',
                'departure_at'   => $trip->departure_at ? $trip->departure_at->format('Y-m-d') : $trip->created_at->format('Y-m-d'),
                'amount'         => $trip->bookings->sum('total_amount'),
                'agency_amount'  => $agencyAmountSum,
                'ratings_count'  => $ratings->count(),
                'average_rating' => round($avgRating ?? 0, 1),
                'ratings'        => $ratings,
                'driver'         => $trip->driver,
                'vehicle'        => $trip->vehicle,
                'staff'          => $trip->staff,
                'raw_trip'       => $trip,
            ];
        });

        // ================= 6. بيانات الرسم البياني =================
        $chartTrips = Trip::where('agency_id', $agencyId)->latest()->take(6)->get();
        $chartLabels = $chartTrips->pluck('title')->toArray();
        $chartSoldData = [];
        $chartTotalData = [];

        foreach ($chartTrips as $trip) {
            $sold = Booking::where('trip_id', $trip->id)->where('status', 'confirmed')
                ->withCount('bookingSeats')->get()->sum('booking_seats_count');
            $chartSoldData[] = $sold ?: 0;
            $chartTotalData[] = $trip->bus_seats ?? 50;
        }

        // ================= 7. إرجاع العرض ومشاركة المتغيرات =================
        return view('agency.trips.reports', compact(
            'stats',
            'recent_transactions',
            'chartLabels',
            'chartSoldData',
            'chartTotalData',
            'range',
            'drivers',
            'vehicles',
            'staffMembers'

        ));
    }

    public function auditLogsIndex(Request $request)
    {
        $agencyId = $this->getAgencyId();
        $query = AuditLog::where('agency_id', $agencyId)->with('trip');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('operator_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        $totalMoneyFlow = (clone $query)->sum('amount_changed');
        $totalSeatsFlow = (clone $query)->sum('seats_changed');

        $logs = $query->latest()->paginate(15);

        $trips = Trip::where('agency_id', $agencyId)
            ->select('id', 'title')
            ->latest()
            ->get();

        return view('agency.reports.audit', compact('logs', 'totalMoneyFlow', 'totalSeatsFlow', 'trips'));
    }
}
