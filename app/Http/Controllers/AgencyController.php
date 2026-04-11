<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AgencyController extends Controller
{
    // 1. عرض جميع الرحلات الخاصة بالوكالة (التي تمت إضافتها)
    public function indexTrip()
    {
        $trips = [
            (object)[
                'id' => 1,
                'title' => 'رحلة الرياض - مكة (العشر الأواخر)',
                'date' => '2026-04-15',
                'time' => '10:00 PM',
                'days' => 3,
                'price' => 150,
                'available_seats' => 12,
                'total_seats' => 50,
                'status' => 'approved',
                'transport_type' => 'bus', // رحلة برية
            ],
            (object)[
                'id' => 2,
                'title' => 'رحلة جدة - المدينة المنورة',
                'date' => '2026-04-18',
                'time' => '08:00 AM',
                'days' => 3,
                'price' => 100,
                'available_seats' => 30,
                'total_seats' => 30,
                'status' => 'pending',
                'transport_type' => 'bus', // رحلة برية
            ],
            (object)[
                'id' => 3,
                'title' => 'رحلة الدمام - الرياض',
                'date' => '2026-04-20',
                'time' => '02:00 PM',
                'days' => 3,
                'price' => 80,
                'available_seats' => 0,
                'total_seats' => 45,
                'status' => 'completed',
                'transport_type' => 'bus',
            ],
            (object)[
                'id' => 4,
                'title' => 'الرياض - دبي (طيران)',
                'date' => '2026-05-01',
                'time' => '11:00 AM',
                'days' => 5,
                'price' => 1200,
                'available_seats' => 150,
                'total_seats' => 180,
                'status' => 'approved',
                'transport_type' => 'flight', // رحلة جوية
            ],
        ];

        return view('agency.trips.index', compact('trips'));
    }
    public function agencyDashboard()
    {
        $stats = [
            'total_revenue' => '45,250',
            'active_trips' => 12,
            'registered_drivers' => 45,
            'pending_verifications' => 8,
        ];

        $recent_trips = [
            (object)['id' => 'TRP-99', 'route' => 'الرياض - الدمام', 'driver' => 'أحمد علي', 'status' => 'في الطريق', 'time' => '10:30 AM'],
            (object)['id' => 'TRP-102', 'route' => 'جدة - مكة', 'driver' => 'سامي فهد', 'status' => 'انتظار', 'time' => '11:15 AM'],
        ];

        return view('agency.dashboard', compact('stats', 'recent_trips'));
    }

    // 2. صفحة إضافة رحلة جديدة
    public function create()
    {
        return view('agency.trips.create');
    }

    // 3. عرض جميع الحجوزات (المؤكدة والمعلقة) الخاصة بالوكالة
    public function indexBooking()
    {
        $bookings = [
            (object)[
                'id' => 'BK-9901',
                'customer_name' => 'خالد العتيبي',
                'trip_title' => 'الرياض - مكة',
                'seats' => [12, 13],
                'total_price' => 300,
                'status' => 'confirmed', // تم التأكيد مسبقاً
                'payment_date' => '2026-04-01',
            ],
            (object)[
                'id' => 'BK-9902',
                'customer_name' => 'فهد البقمي',
                'trip_title' => 'جدة - المدينة',
                'seats' => [5],
                'total_price' => 100,
                'status' => 'pending', // بانتظار مراجعة الإيصال
                'payment_date' => '2026-04-02',
            ],
        ];
        return view("agency.bookings.index", compact('bookings'));
    }

    // 4.  الطلبات بانتظار التأكيد
    public function pendingApprovals()
    {
        $pendingBookings = (object)[

            'id' => 'BK-9902',
            'customer' => 'فهد البقمي',
            'trip' => 'جدة - المدينة',
            'seats' => [5],
            'amount' => 300,
            'receipt_image' => 'receipt_sample.jpg',
            'created_at' => '2026-04-02 09:30 AM'

            // (object)[
            //     'id' => 'BK-5503',
            //     'customer' => 'محمد السعيد',
            //     'trip' => 'الرياض ⬅️ مكة',
            //     'seats' => [1, 2, 3],
            //     'amount' => 450,
            //     'receipt_image' => 'receipt_sample_3.jpg',
            //     'created_at' => 'منذ 10 دقائق'
            // ],
        ];

        return view('agency.bookings.pending', compact('pendingBookings'));
    }
    public function approveTrips()
    {
        // محاكاة بيانات رحلات بانتظار الاعتماد
        $approveTrips = [
            (object)[
                'id' => 101,
                'agency' => 'وكالة الفارس',
                'route' => 'الرياض ⬅️ مكة',
                'date' => '2026-04-15',
                'price' => 150,
                'bus_info' => 'مرسيدس 50 مقعد',
                'status' => 'pending'
            ],
            (object)[
                'id' => 102,
                'agency' => 'شركة رحلاتي',
                'route' => 'جدة ⬅️ المدينة',
                'date' => '2026-04-18',
                'price' => 120,
                'bus_info' => 'حافلة VIP 30 مقعد',
                'status' => 'pending'
            ],
        ];

        return view('admin.trips.approveTrip', compact('approveTrips'));
    }

    // 5. تقارير الوكالة (إحصائيات مالية تمثيلية)
    public function report()
    {
        $stats = (object)[
            'total_revenue' => 12500,
            'total_bookings' => 84,
            'pending_amount' => 1200,
            'active_trips' => 5
        ];

        $recent_transactions = [
            ['date' => '2026-04-01', 'amount' => 450, 'type' => 'حجز مؤكد'],
            ['date' => '2026-03-30', 'amount' => 900, 'type' => 'حجز مؤكد'],
        ];

        // 👇 بيانات التقرير لكل رحلة
        $reportTrips = [
            (object)[
                'title' => 'مكة - العشر الأواخر',
                'sold' => 48,
                'total_seats' => 50,
                'price' => 150,
            ],
            (object)[
                'title' => 'المدينة - زيارة الروضة',
                'sold' => 30,
                'total_seats' => 50,
                'price' => 150,
            ],
        ];

        return view('agency.trips.reports', compact('stats', 'recent_transactions', 'reportTrips'));
    }

    // 6. خريطة المقاعد للوكالة (لمعاينة وضع الباص)
    public function seat()
    {
        $booked_seats = [
            1 => 'male',
            2 => 'male',
            10 => 'female',
            11 => 'female',
            20 => 'blocked'
        ];
        return view('agency.trips.seatmap', compact('booked_seats'));
    }
    public function flightmap()
    {
        return view('agency.trips.flighmap');
    }

    // --- عمليات العميل (الموجودة سابقاً مع تعديلات بسيطة للاتساق) ---

    // public function selectSeat($trip_id)
    // {
    //     $trip = (object) [
    //         'id' => $trip_id,
    //         'title' => 'رحلة الرياض - مكة (شامل الإقامة)',
    //         'agency' => 'وكالة الفارس للنقل',
    //         'room_prices' => [
    //             'quad' => 1500,   // غرفة رباعية
    //             'triple' => 1800, // غرفة ثلاثية
    //             'double' => 2200, // غرفة ثنائية
    //             'single' => 3500  // غرفة فردية
    //         ]
    //     ];
    //     $booked_seats = [1, 2, 5, 25, 26];
    //     return view('client.select_seat', compact('trip', 'booked_seats'));
    // }

    public function checkout(Request $request)
    {
        $selected_seats = $request->has('seats') ? explode(',', $request->seats) : [];
        $room_type = $request->query('room', 'quad');

        // أسعار افتراضية للمحاكاة
        $prices = ['quad' => 1500, 'triple' => 1800, 'double' => 2200, 'single' => 3500];
        $unit_price = $prices[$room_type] ?? 1500;
        $total_price = count($selected_seats) * $unit_price;

        return view('client.checkout', compact('selected_seats', 'room_type', 'total_price', 'unit_price'));
    }

    // public function store(Request $request)
    // {
    //     return "تم استلام البيانات بنجاح، سيتم تفعيل العمليات البرمجية لاحقاً.";
    // }

    public function confirmation()
    {
        return view('client.confirmation');
    }
}
