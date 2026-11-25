<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\AgencyEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AgencyEmployeeController extends Controller
{
    /**
     * عرض صفحة الموظفين مع قائمة الموظفين الحاليين
     */

    public function index(Request $request)
    {
        // الحصول على آيدي الوكالة الحالية
        $agencyId = auth('agency')->id() ?? session('agency_id');

        // الاستعلام الأساسي المحصور بالوكالة الحالية
        $baseQuery = AgencyEmployee::where('agency_id', $agencyId);

        // 1. حساب إحصائيات الموظفين الخاصة بملف هذه الوكالة فقط
        $stats = [
            'total'      => (clone $baseQuery)->count(),
            'admin'      => (clone $baseQuery)->where('role', 'admin')->count(),
            'accountant' => (clone $baseQuery)->where('role', 'accountant')->count(),
            'staff'      => (clone $baseQuery)->where('role', 'staff')->count(),
        ];

        // 2. تطبيق البحث والتصفية
        $query = clone $baseQuery;

        // البحث بالاسم أو البريد أو رقم الهاتف
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // التصفية حسب الدور الوظيفي عند تحديده
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        // جلب النتائج مرقمة مع الحفاظ على مدخلات البحث عند التنقل بين الصفحات
        $employees = $query->latest()->paginate(10)->withQueryString();

        return view('agency.employees.index', compact('employees', 'stats'));
    }

    public function store(Request $request)
    {
        $agencyId = auth('agency')->id() ?? session('agency_id');

        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:agency_employees,email',
            'phone'         => 'nullable|string|max:20',
            'password'      => 'required|string|min:8',
            'role'          => 'required|in:admin,accountant,staff',
            'permissions'   => 'nullable|array', // مصفوفة الصلاحيات
            'permissions.*' => 'string'
        ], [
            'name.required'     => 'اسم الموظف مطلوب.',
            'email.required'    => 'البريد الإلكتروني مطلوب.',
            'email.unique'      => 'هذا البريد الإلكتروني مسجل لموظف آخر بالفعل.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min'      => 'كلمة المرور يجب أن لا تقل عن 8 خانات.',
            'role.required'     => 'يرجى تحديد الدور الوظيفي.',
        ]);

        // إذا كان الدور مدير نظام (admin)، نجعل مصفوفة الصلاحيات فارغة أو كاملة لأنه يملك كل شيء ضمناً
        $permissions = $request->role === 'admin' ? [] : ($request->permissions ?? []);

        AgencyEmployee::create([
            'agency_id'   => $agencyId,
            'name'        => $request->name,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'password'    => Hash::make($request->password),
            'role'        => $request->role,
            'is_active'   => true,
            'permissions' => $permissions, // حفظ الصلاحيات
        ]);

        return redirect()->back()->with('success', 'تمت إضافة الموظف بنجاح.');
    }

    /**
     * تحديث بيانات وصلاحيات الموظف (في حال كان لديك فورم للتعديل)
     */
    public function update(Request $request, $id)
    {
        $agencyId = auth('agency')->id() ?? session('agency_id');

        $employee = AgencyEmployee::where('agency_id', $agencyId)->findOrFail($id);

        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => ['required', 'email', Rule::unique('agency_employees')->ignore($employee->id)],
            'phone'         => 'nullable|string|max:20',
            'password'      => 'nullable|string|min:8', // اختياري عند التعديل
            'role'          => 'required|in:admin,accountant,staff',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string'
        ]);

        $permissions = $request->role === 'admin' ? [] : ($request->permissions ?? []);

        $employee->update([
            'name'        => $request->name,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'role'        => $request->role,
            'permissions' => $permissions,
        ]);

        // تحديث كلمة المرور فقط إذا قام بإدخال كلمة مرور جديدة
        if ($request->filled('password')) {
            $employee->update([
                'password' => Hash::make($request->password)
            ]);
        }

        return redirect()->back()->with('success', 'تم تحديث بيانات الموظف والصلاحيات بنجاح.');
    }

    /**
     * تفعيل / إيقاف حساب الموظف
     */
    public function toggleStatus($id)
    {
        $agencyId = auth('agency')->id() ?? session('agency_id');
        $employee = AgencyEmployee::where('agency_id', $agencyId)->findOrFail($id);

        $employee->is_active = !$employee->is_active;
        $employee->save();

        $statusText = $employee->is_active ? 'تفعيل' : 'تجميد';
        return redirect()->back()->with('success', "تم {$statusText} حساب الموظف بنجاح.");
    }

    public function authenticateEmployee(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // جلب الموظف مع بيانات الوكالة التابع لها
        $employee = AgencyEmployee::with('agency')->where('email', $credentials['email'])->first();

        if ($employee && Hash::check($credentials['password'], $employee->password)) {

            // 1️⃣ التحقق من حالة حساب الموظف
            if (!$employee->is_active) {
                return back()->withErrors([
                    'email' => 'عذراً، حساب الموظف هذا معطل حالياً من قبل إدارة الوكالة.',
                ])->onlyInput('email');
            }

            // 2️⃣ التحقق من حالة حساب الوكالة التابع لها الموظف
            if (!$employee->agency || $employee->agency->status !== 'active') {
                return back()->withErrors([
                    'email' => 'عذراً، لا يمكنك الدخول لأن حساب الوكالة قيد المراجعة أو معطل حالياً.',
                ])->onlyInput('email');
            }

            // 3️⃣ تسجيل الدخول عبر الـ Guard الخاص بالموظفين
            auth()->guard('employee')->login($employee, $request->has('remember'));

            // 4️⃣ تعيين بيانات الجلسة (Session)
            session([
                'employee_logged_in' => true,
                'employee_id'        => $employee->id,
                'employee_name'      => $employee->name,
                'employee_role'      => $employee->role,

                'agency_id'          => $employee->agency_id,
                'agency_name'        => $employee->agency->name,
            ]);

            $request->session()->regenerate();

            return redirect()->route('agency.dashboard');
        }

        return back()->withErrors([
            'email' => 'بيانات الدخول غير صحيحة.',
        ])->onlyInput('email');
    }
    public function logoutEmployee(Request $request)
    {
        auth()->guard('employee')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('employee.login');
    }

    /**
     * حذف موظف
     */
    public function destroy($id)
    {
        $agencyId = auth('agency')->id() ?? session('agency_id');
        $employee = AgencyEmployee::where('agency_id', $agencyId)->findOrFail($id);

        $employee->delete();

        return redirect()->back()->with('success', 'تم حذف الموظف بنجاح.');
    }
}
