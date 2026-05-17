<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

    public function pendingTrips()
    {
        // بناء بيانات وهمية (Mock Data) للرحلات بانتظار الاعتماد
        $pendingTrips = collect([
            (object)[
                'id' => 1024,
                'route' => 'الرياض ⬅️ مكة المكرمة',
                'price' => 150,
                'date' => '2026-04-15 (25 رمضان)',
                'transport_type' => 'bus',
                'vehicle_model' => 'مرسيدس Travego 2024',
                'capacity' => 45,
                'image' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?q=80&w=1000&auto=format&fit=crop',
                'agency' => (object)[
                    'name' => 'وكالة الفارس للنقل',
                ],
            ],
            (object)[
                'id' => 1025,
                'route' => 'الدمام ⬅️ المدينة المنورة',
                'price' => 280,
                'date' => '2026-04-17 (27 رمضان)',
                'transport_type' => 'bus',
                'vehicle_model' => 'نيوبلان Tourliner',
                'capacity' => 50,
                'image' => 'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?q=80&w=1000&auto=format&fit=crop',
                'agency' => (object)[
                    'name' => 'وكالة النور الساطع',
                ],
            ],
            (object)[
                'id' => 1026,
                'route' => 'جدة ⬅️ الرياض',
                'price' => 450,
                'date' => '2026-04-20 (30 رمضان)',
                'transport_type' => 'flight',
                'vehicle_model' => 'Airbus A320',
                'capacity' => 180,
                'image' => 'https://images.unsplash.com/photo-1436491865332-7a61a109c0f3?q=80&w=1000&auto=format&fit=crop',
                'agency' => (object)[
                    'name' => 'أجنحة الخليج للطيران',
                ],
            ],
            (object)[
                'id' => 1027,
                'route' => 'تبوك ⬅️ المدينة المنورة',
                'price' => 120,
                'date' => '2026-04-12 (22 رمضان)',
                'transport_type' => 'bus',
                'vehicle_model' => 'حافلة هيونداي Universe',
                'capacity' => 45,
                'image' => 'https://images.unsplash.com/photo-1517512048540-02ba691bb133?q=80&w=1000&auto=format&fit=crop',
                'agency' => (object)[
                    'name' => 'شركة مكة للنقل البري',
                ],
            ],
        ]);

        // إرسال البيانات إلى الـ View
        return view('admin.trips.approveTrip', compact('pendingTrips'));
    }

    /**
     * دالة وهمية للموافقة على الرحلة
     */
    public function approveTrip($id)
    {
        // هنا يتم تغيير حالة الرحلة في قاعدة البيانات لاحقاً
        // $trip = Trip::findOrFail($id);
        // $trip->update(['status' => 'approved']);

        return back()->with('success', 'تم اعتماد الرحلة بنجاح وظهورها للمستخدمين.');
    }

    /**
     * دالة وهمية لرفض الرحلة
     */
    public function rejectTrip(Request $request, $id)
    {
        // هنا يتم منطق الرفض
        return back()->with('error', 'تم رفض طلب الرحلة وإشعار الوكالة.');
    }
    public function adminAgency()
    {
        // محاكاة بيانات الوكالات
        $agencies = [
            (object)[
                'id' => 1,
                'name' => 'وكالة الفارس للنقل',
                'email' => 'info@alfaris.com',
                'trips_count' => 45,
                'status' => 'active', // نشط
                'joined_at' => '2025-10-12'
            ],
            (object)[
                'id' => 2,
                'name' => 'شركة رمال الصحراء',
                'email' => 'contact@sand-tours.com',
                'trips_count' => 0,
                'status' => 'pending', // بانتظار التفعيل
                'joined_at' => '2026-03-28'
            ],
            (object)[
                'id' => 3,
                'name' => 'ناقلات الغد',
                'email' => 'support@alghad.sa',
                'trips_count' => 120,
                'status' => 'blocked', // محظور
                'joined_at' => '2025-05-20'
            ],
        ];

        return view('admin.agencies.index', compact('agencies'));
    }

    public function identityRequests()
    {
        // بيانات وهمية لطلبات التوثيق المعلقة
        $requests = [
            (object)[
                'id' => 101,
                'user_name' => 'سارة الشمري',
                'id_number' => '1098223344',
                'uploaded_at' => '2026-04-10',
                'id_image' => 'https://via.placeholder.com/600x400/1e293b/ffffff?text=ID+Front+View',
                'phone' => '0555555551',
                'email' => 'sara@example.com',
                'status' => 'pending'
            ],
            (object)[
                'id' => 102,
                'user_name' => 'فهد القحطاني',
                'id_number' => '1022334455',
                'uploaded_at' => '2026-04-11',
                'id_image' => 'https://via.placeholder.com/600x400/4f46e5/ffffff?text=ID+Front+View',
                'phone' => '0555555552',
                'email' => 'fahad@example.com',
                'status' => 'pending'
            ]
        ];

        return view('admin.users.pending', compact('requests'));
    }
    public function login()
    {
        return view('admin.auth.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $adminEmail = "admin@obour.com";
        $adminPass  = "123456";

        if ($request->email == $adminEmail && $request->password == $adminPass) {
            session(['is_admin' => true]);
            return redirect()->route('admin.users.pending');
        }

        return back()->with('error', 'البيانات غير صحيحة!');
    }
    public function logout()
    {
        session()->forget('is_admin');
        return redirect()->route('admin.auth.login');
    }
}
