<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Models\Agency;
use App\Models\Booking;
use App\Models\TripSeatLog;
use App\Models\User;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function index()
    {
        // 1. جلب بيانات بطاقات الإحصائيات 
        $totalSales = Booking::where('payment_status', 'paid')->sum('total_amount');
        $seatLogs = TripSeatLog::with('trip')->latest()->get();

        $activeAgenciesCount = Agency::where('status', 'active')->count();
        $pendingAgenciesCount = Agency::where('status', 'pending')->count();

        // تنبيه: تأكد أن اسم الحقل في جدول الرحلات هو departure_at وليس start_date
        $activeTripsCount = Trip::where('status', 'active')->where('departure_at', '>=', now())->count();
        $pendingTripsCount = Trip::where('status', 'pending')->count();

        $onlineUsersCount = User::count();

        // 2. جلب القوائم بانتظار الاعتماد
        $pendingTrips = Trip::with('agency')->whereIn('status', ['published', 'pending'])->latest()->get();
        $pendingAgencies = Agency::where('status', 'pending')->latest()->get();

        // 🆕 جلب المستخدمين المنتظرين لتوثيق الهوية (is_verified = 3)
        $pendingUsers = User::where('is_verified', 3)->latest()->get();

        // تمرير كافة البيانات المتغيرة إلى صفحة الـ Blade
        return view('admin.dashboard', compact(
            'totalSales',
            'activeAgenciesCount',
            'pendingAgenciesCount',
            'activeTripsCount',
            'pendingTripsCount',
            'onlineUsersCount',
            'pendingTrips',
            'pendingAgencies',
            'pendingUsers',
            'seatLogs'
        ));
    }


    public function showEdit(Request $request)
    {
        $search = $request->input('search');

        $users = User::select(
            'id',
            'name',
            'email',
            'phone',
            'city',
            'gender',
            'national_id',
            'is_verified',
            'created_at'
        )
            // إضافة الفلترة عند وجود كلمة بحث
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%");
                });
            })
            ->withCount(['bookings' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->latest()
            ->paginate(10)
            ->withQueryString(); // مهمة جداً للحفاظ على كلمة البحث أثناء الانتقال بين صفحات الترقيم

        return view('admin.users.updUsers', compact('users'));
    }

    public function edit($id)
    {
        // إضافة with('companions') لجلب المرافقين مع المستخدم
        $user = User::with('companions')->select(
            'id',
            'name',
            'email',
            'phone',
            'birth_date',
            'city',
            'gender',
            'national_id',
            'is_verified'
        )->findOrFail($id);

        return view('admin.users.edit', compact('user'));
    }

    //تحديث بيانات المستخدم
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // 1. تحديث قواعد التحقق لتشمل المرافقين
        $validatedData = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|max:255|unique:users,email,' . $user->id,
            'national_id'  => 'nullable|string|max:20|unique:users,national_id,' . $user->id,
            'phone'        => 'nullable|string|max:20',
            'birth_date'   => 'nullable|date',
            'city'         => 'nullable|string|max:255',
            'gender'       => 'required|in:male,female',
            'is_verified'  => 'required|in:0,1,2',
            'password'     => 'nullable|string|min:8|confirmed',

            // التحقق من بيانات المرافقين (إن وجدت)
            'companions'                 => 'nullable|array',
            'companions.*.name'          => 'required|string|max:255',
            'companions.*.national_id'   => 'nullable|string|max:20',
            'companions.*.birth_date'    => 'nullable|date',
            'companions.*.gender'        => 'required|in:male,female',
            'companions.*.relationship'  => 'nullable|string|max:255',
        ], [
            'email.unique'       => 'البريد الإلكتروني مستخدم بالفعل لنظام آخر.',
            'national_id.unique' => 'رقم الهوية الوطنية مسجل مسبقاً في النظام.',
            'password.min'       => 'يجب أن لا تقل كلمة المرور عن 8 رموز.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        // معالجة كلمة المرور
        if ($request->filled('password')) {
            $validatedData['password'] = Hash::make($request->password);
        } else {
            unset($validatedData['password']);
        }

        // 2. تحديث بيانات المستخدم الأساسية
        $user->update($validatedData);

        // 3. تحديث بيانات المرافقين
        if ($request->has('companions')) {
            foreach ($request->companions as $companionId => $companionData) {
                // نستخدم where للتأكد أن المرافق يتبع لنفس المستخدم
                $user->companions()->where('id', $companionId)->update([
                    'name'         => $companionData['name'],
                    'national_id'  => $companionData['national_id'],
                    'birth_date'   => $companionData['birth_date'],
                    'gender'       => $companionData['gender'],
                    'relationship' => $companionData['relationship'],
                ]);
            }
        }

        return redirect()->route('admin.users.showEdit', $user->id) // تأكد من تمرير الـ id هنا إذا كان المسار يطلبه
            ->with('success', 'تم تحديث بيانات المستخدم والمرافقين بنجاح! ✨');
    }


    public function approveCrUpdate($agencyId)
    {
        $agency = Agency::findOrFail($agencyId);

        if ($agency->cr_status !== 'pending') {
            return redirect()->back()->with('error', 'لا يوجد طلب تحديث سجل تجاري معلق لهذه الوكالة.');
        }

        // حذف ملف السجل القديم إن وجد
        if ($agency->pending_cr_document_path && $agency->cr_document_path && $agency->cr_document_path !== $agency->pending_cr_document_path) {
            Storage::disk('public')->delete($agency->cr_document_path);
        }

        // نقل البيانات المعلقة إلى الحقول المعتمدة الرسمية
        $agency->update([
            'commercial_id'            => $agency->pending_commercial_id ?? $agency->commercial_id,
            'cr_expiry_date'           => $agency->pending_cr_expiry_date ?? $agency->cr_expiry_date,
            'cr_document_path'         => $agency->pending_cr_document_path ?? $agency->cr_document_path,
            'cr_status'                => 'approved',
            'pending_commercial_id'    => null,
            'pending_cr_expiry_date'   => null,
            'pending_cr_document_path' => null,
        ]);

        return redirect()->back()->with('success', 'تم اعتماد السجل التجاري الجديد للوكالة بنجاح.');
    }

    /**
     * رفض طلب تحديث السجل التجاري
     */
    public function rejectCrUpdate($agencyId)
    {
        $agency = Agency::findOrFail($agencyId);

        // حذف الملف المعلق الذي تم رفعه
        if ($agency->pending_cr_document_path) {
            Storage::disk('public')->delete($agency->pending_cr_document_path);
        }

        $agency->update([
            'cr_status'                => 'rejected',
            'pending_commercial_id'    => null,
            'pending_cr_expiry_date'   => null,
            'pending_cr_document_path' => null,
        ]);

        return redirect()->back()->with('success', 'تم رفض طلب تحديث السجل وإلغاء المرفقات المعلقة.');
    }

    //اعتماد الرحلات
    public function pendingTrips()
    {
        // عرض 12 رحلة في كل صفحة لتتناسب مع التقسيم (3 كروت في السطر)
        $trips = Trip::with('agency')->latest()->paginate(12);

        return view('admin.trips.approveTrip', compact('trips'));
    }
    /**
     * الحصول على رابط صورة الغلاف بشكل جاهز ومباشر
     */
    public function getCoverImageUrlAttribute()
    {
        // 1. إذا لم تكن هناك صورة، ارجاع الصورة الافتراضية
        if (!$this->cover_image) {
            return $this->transport_type === 'flight'
                ? 'https://media.istockphoto.com/id/481413202/photo/passenger-airliner-flight-in-the-blue-sky.jpg?s=2048x2048&w=is&k=20&c=C8STBVw9bykfUx2b6zU3NDyR17-gEerpy31Ok2i-Rzs='
                : 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&q=80&w=800';
        }

        // 2. إذا كانت الصورة رابطاً خارجياً يبدأ بـ http
        if (str_starts_with($this->cover_image, 'http')) {
            return $this->cover_image;
        }

        // 3. إذا كان المسار يحتوي بالفعل على storage/
        if (str_starts_with($this->cover_image, 'storage/')) {
            return asset($this->cover_image);
        }

        // 4. المسار الافتراضي للملفات المرفوعة
        return asset('storage/' . $this->cover_image);
    }
    // رفض طلب الإلغاء وإعادة الرحلة لحالتها المعتمدة
    public function rejectCancellation($id)
    {
        $trip = Trip::find($id);

        if (!$trip) {
            return redirect()->back()->with('error', 'عذراً، الرحلة غير موجودة! ⚠️');
        }

        // إعادة حالة الرحلة إلى 'approved'
        $trip->update([
            'status' => 'active'
        ]);

        return redirect()->back()->with('success', 'تم رفض طلب الإلغاء وإعادة الرحلة للحالة المعتمدة بنجاح. 🛡️');
    }

    public function approveTrip($id)
    {
        $trip = Trip::findOrFail($id);
        $trip->update(['status' => 'active']);

        return redirect()->route('admin.trips.approveTrip')->with('success', 'تم اعتماد الرحلة بنجاح، وهي الآن متاحة للحجز في المنصة. ✅');
    }

    // حذف الرحلة بواسطة الأدمن
    public function destroyTrip($id)
    {
        $trip = Trip::find($id);

        if (!$trip) {
            return redirect()->back()->with('error', 'عذراً، هذه الرحلة غير موجودة في النظام! ⚠️');
        }

        // التحقق من وجود حجوزات نشطة على الرحلة
        $activeBookingsExists = $trip->bookings()
            ->whereNotIn('status', ['cancelled', 'ملغي', 'rejected', 'مرفوض'])
            ->exists();

        if ($activeBookingsExists) {
            return redirect()->back()->with('error', 'لا يمكن حذف هذه الرحلة لوجود حجوزات نشطة قائمة عليها حالياً! ⚠️');
        }

        // تسجيل العملية في السجلات وتحديد وكالة الرحلة المحذوفة
        AuditLog::create([
            'agency_id'      => $trip->agency_id, // ربط السجل بالوكالة التابعة لها الرحلة
            'trip_id'        => $trip->id,
            'action_type'    => 'delete',
            'category'       => 'trips',
            'amount_changed' => 0,
            'seats_changed'  => 0,
            'description'    => "تم حذف الرحلة ({$trip->title}) بواسطة الأدمن وتنظيف السجلات المرتبطة بها.",
            'operator_name'  => auth()->user()->name ?? $this->getOperatorName(),
        ]);

        // حذف الحجوزات التابعة ثم حذف الرحلة نهائياً
        $trip->bookings()->delete();
        $trip->delete();

        return redirect()->back()->with('success', 'تم حذف الرحلة بنجاح وتنظيف السجلات المرتبطة بها. 🗑️');
    }

    public function rejectUser($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_verified' => 2]);

        return redirect()->back()->with('success', 'تم رفض اعتماد الهوية!');
    }

    public function identityRequests(Request $request)
    {
        $query = User::where('is_verified', 3);

        // 1. البحث (الاسم، الهوية، الجوال، البريد)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 2. التصفية حسب وجود صورة الهوية
        if ($request->filled('filter')) {
            if ($request->filter === 'with_photo') {
                $query->whereNotNull('id_image')->where('id_image', '!=', '');
            } elseif ($request->filter === 'without_photo') {
                $query->where(function ($q) {
                    $q->whereNull('id_image')->orWhere('id_image', '');
                });
            }
        }

        // جلب البيانات مع الحفاظ على معايير البحث عند التنقل بين الصفحات
        $users = $query->latest()->paginate(15)->withQueryString();

        return view('admin.users.pending', compact('users'));
    }

    public function verifyUser($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_verified' => 1]);

        return redirect()->back()->with('success', 'تم اعتماد وتوثيق هوية المسافر بنجاح! 🛂');
    }

    public function rejectTrip($id)
    {
        $trip = Trip::findOrFail($id);
        $trip->update(['status' => 'rejected']);

        return redirect()->back()->with('success', 'تم رفض الرحلة وإزالتها من قائمة الانتظار.');
    }

    public function adminAgency()
    {
        $agencies = Agency::withCount('trips')->withSum('trips', 'price')->latest()->paginate(15);
        $activeCount = Agency::where('status', 'active')->count();
        $pendingCount = Agency::where('status', 'pending')->count();

        $topAgency = Agency::withCount('trips')->orderBy('trips_count', 'desc')->first();
        $topAgencyName = $topAgency ? $topAgency->name : 'لا يوجد حالياً';

        return view('admin.agencies.index', compact('agencies', 'activeCount', 'pendingCount', 'topAgencyName'));
    }

    public function approveAgency($id)
    {
        $agency = Agency::findOrFail($id);
        $agency->update(['status' => 'active']);

        return redirect()->back()->with('success', "تم اعتماد وتفعيل حساب ({$agency->name}) بنجاح! 🎉");
    }

    public function blockAgency($id)
    {
        $agency = Agency::findOrFail($id);
        $newStatus = $agency->status === 'blocked' ? 'active' : 'blocked';
        $agency->update(['status' => $newStatus]);

        $message = $newStatus === 'blocked' ? 'تم حظر الوكالة بنجاح 🔒' : 'تم إلغاء حظر الوكالة وتنشيطها ✅';
        return redirect()->back()->with('success', $message);
    }

    public function destroy($id)
    {
        $agency = Agency::findOrFail($id);
        if ($agency->commercial_image && file_exists(public_path($agency->commercial_image))) {
            @unlink(public_path($agency->commercial_image));
        }

        $agency->delete();
        return redirect()->back()->with('success', 'تم حذف الوكالة وكافة بياناتها بنجاح 🗑️');
    }

    public function delUser($id)
    {
        try {
            $user = User::findOrFail($id);
            if (auth()->guard('admin')->id() == $user->id) {
                return redirect()
                    ->route('admin.users.showEdit')
                    ->with('error', 'لا يمكن حذف الحساب الحالي الذي تسجل الدخول به.');
            }

            $user->delete();
            return redirect()
                ->route('admin.users.showEdit')
                ->with('success', 'تم حذف المستخدم بنجاح');
        } catch (\Throwable $e) {
            report($e);
            return redirect()
                ->route('admin.users.showEdit')
                ->with('error', 'تعذر حذف المستخدم لوجود بيانات مرتبطة');
        }
    }

    public function login()
    {
        return view('admin.auth.login');
    }

    public function processLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'حقل البريد الإلكتروني إجباري.',
            'email.email' => 'يرجى إدخال بريد إلكتروني صحيح.',
            'password.required' => 'حقل كلمة المرور إجباري.',
        ]);

        if (Auth::guard('admin')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard');
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', 'عذراً، البيانات المدخلة غير متطابقة مع سجلات الإدارة.');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.auth.login');
    }
}
