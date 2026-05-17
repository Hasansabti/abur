<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        // جلب نوع النقل من الرابط (مثلاً: ?type=flight)، وإذا لم يوجد نفترض الكل أو الباص
        $selectedType = $request->query('type');

        $allTrips = [
            (object)[
                'id' => 1,
                'name' => 'الكعبة المشرفة',
                'image' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&q=80&w=800',
                'agency' => 'وكالة الفارس للنقل',
                'route' => 'الرياض ⬅️ مكة',
                'type' => 'one_way',
                'transport_type' => 'bus', // نوع الرحلة: بري
                'vehicle_model' => 'مرسيدس VIP - 50 راكب',
                'rating' => 5,
                'price' => 150,
                'date' => '2026-04-15',
                'amenities' => ['WiFi', 'AC', 'Snacks']
            ],
            (object)[
                'id' => 2,
                'name' => 'زيارة متاحف دينية',
                'image' => 'https://media.istockphoto.com/id/481413202/photo/passenger-airliner-flight-in-the-blue-sky.jpg?s=2048x2048&w=is&k=20&c=C8STBVw9bykfUx2b6zU3NDyR17-gEerpy31Ok2i-Rzs=',
                'agency' => 'أديل للطيران',
                'route' => 'جدة ⬅️ الرياض',
                'type' => 'round_trip',
                'transport_type' => 'flight', // نوع الرحلة: طيران
                'vehicle_model' => 'طيران أديل - Airbus A320',
                'rating' => 4,
                'price' => 450,
                'date' => '2026-04-18',
                'amenities' => ['WiFi', 'Snacks', 'USB']
            ],
            (object)[
                'id' => 3,
                'name' => 'موسم الرياض',
                'image' => 'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?auto=format&fit=crop&q=80&w=800',
                'agency' => 'شركة رحلاتي',
                'route' => 'جدة ⬅️ المدينة',
                'type' => 'one_way',
                'transport_type' => 'bus',
                'vehicle_model' => 'كيا مريحة - 50 راكب',
                'rating' => 4,
                'price' => 120,
                'date' => '2026-04-20',
                'amenities' => ['AC', 'USB']
            ],
        ];

        // تحويل المصفوفة إلى Collection لسهولة الفلترة (اختياري لكنه أفضل)
        $availableTrips = collect($allTrips);

        // إذا اختار المستخدم نوعاً معيناً من الفلتر في الواجهة
        if ($selectedType) {
            $availableTrips = $availableTrips->where('transport_type', $selectedType);
        }

        return view('client.index', [
            'availableTrips' => $availableTrips,
            'selectedType' => $selectedType
        ]);
    }
    public function success()
    {
        // نقوم بتعريف الحجز ككائن واحد (Object)
        $booking = (object)[
            'id' => 'BK-9905',
            'trip_title' => 'الرياض - مكة المكرمة',
            'from_city' => 'الرياض',
            'to_city' => 'مكة المكرمة',
            'type' => 'bus', // جرب تغييرها لـ 'flight' للاختبار
            'date' => '2026-04-15',
            'time' => '10:00 PM',
            'seats' => [14, 15], // مقعدين يعني سيتم توليد تذكرتين
            'room_type' => 'غرفة رباعية',
            'total_amount' => 3000,
            'name' => 'أحمد المنصور',
            'customer_name' => 'أحمد المنصور',
            'status' => 'confirmed'
        ];

        return view('client.success', compact('booking'));
    }
    // public function myBookings()
    // {
    //     $bookings = [
    //         (object)[
    //             'id' => 'BK-9905',
    //             'trip_title' => 'الرياض ⬅️ مكة المكرمة',
    //             'agency' => 'وكالة الفارس للنقل',
    //             'date' => '2026-04-15',
    //             'status' => 'confirmed', // مؤكد
    //             'seats' => [14, 15],
    //             'price' => 3000,
    //             'image' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?q=80&w=400'
    //         ],
    //         (object)[
    //             'id' => 'BK-8802',
    //             'trip_title' => 'جدة ⬅️ المدينة المنورة',
    //             'agency' => 'شركة رحلاتي',
    //             'date' => '2026-05-10',
    //             'status' => 'pending', // قيد المراجعة
    //             'seats' => [7],
    //             'price' => 1800,
    //             'image' => 'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?q=80&w=400'
    //         ]
    //     ];

    //     return view('client.my_bookings', compact('bookings'));
    // }
    public function selectSeat($id)
    {
        // $trip = Trip::findOrFail($id);

        // بيانات تجريبية (Mock Data) لمحاكاة الواقع:
        $trip = (object)[
            'id' => $id,
            'title' => ($id == 2) ? 'رحلة طيران إلى الرياض' : 'رحلة مكة المكرمة',
            'agency' => ($id == 2) ? 'أديل للطيران' : 'وكالة الفارس',
            'route' => ($id == 2) ? 'جدة ⬅️ الرياض' : 'الرياض ⬅️ مكة',
            'transport_type' => ($id == 2) ? 'flight' : 'bus', // هذا يحدد شكل الصفحة
            'vehicle_model' => ($id == 2) ? 'Airbus A320' : 'مرسيدس VIP',
            'room_prices' => [
                'single' => 300,
                'double' => 600,
                'triple' => 800,
                'quad'   => 1100,
            ],
        ];

        // مصفوفة تحاكي المقاعد المحجوزة لجعل الصفحة واقعية
        // في الحقيقة ستجلبها من جدول الحجوزات: Booking::where('trip_id', $id)->pluck('seat_number')
        $booked_seats = [5, 12, 13, 22, 40];
        // تفاصيل الجنس لكل مقعد محجوز (تأتي من جدول الحجوزات)
        $booked_seats_details = [
            5 => 'female',
            12 => 'male',
            13 => 'female',
            22 => 'female',
            40 => 'female',

        ];

        return view('client.select_seat', compact('trip', 'booked_seats_details', 'booked_seats'));
    }

    public function profile()
    {
        // بيانات المستخدم (تُجلب عادةً عبر Auth::user())
        $user = (object)[
            'name' => 'محمد السبتي',
            'is_verified' => true,
            'email' => 'mohammad@example.com',
            'phone' => '+966 50 123 4567',
            'id' => '232342235',
            'avatar' => 'https://ui-avatars.com/api/?name=Mohammad+Sabti&background=0D8ABC&color=fff',
            'member_since' => 'أكتوبر 2025',
            'trips_count' => 12,
            'points' => 450
        ];

        // قائمة الحجوزات
        $bookings = [
            (object)[
                'id' => 'OB-9921',
                'route' => 'الرياض ⬅️ مكة المكرمة',
                'date' => '2026-04-15',
                'time' => 'am 10:49',
                'status' => 'upcoming', // رحلة قادمة
                'type' => 'bus',
                'price' => 150
            ],
            (object)[
                'id' => 'OB-8842',
                'route' => 'جدة ⬅️ الرياض',
                'date' => '2026-03-20',
                'time' => 'pm 10:49',
                'status' => 'completed', // رحلة مكتملة
                'type' => 'flight',
                'price' => 450
            ]
        ];

        return view('client.profile', compact('user', 'bookings'));
    }
    public function login()
    {
        return view('client.auth.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('client.index');
        }

        return back()->withErrors([
            'email' => 'بيانات الدخول غير صحيحة.',
        ])->onlyInput('email');
    }

    public function create()
    {
        return view('client.auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:6'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('client.index');
    }
}
