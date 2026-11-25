<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $permission  اسم الصلاحية المطلوبة للصفحة
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // 1️⃣ إذا كان المسجل هو مالك الوكالة (Agency)، يمر فوراً دون قيود
        if (auth('agency')->check()) {
            return $next($request);
        }

        // 2️⃣ إذا كان المسجل هو موظف (Employee)
        if (auth('employee')->check()) {
            $employee = auth('employee')->user();

            // إذا كان دور الموظف أدمن، يمنح صلاحية كاملة تلقائياً
            if ($employee->role === 'admin') {
                return $next($request);
            }

            // جلب صلاحيات الموظف المقيدة
            $userPermissions = $employee->permissions ?? [];

            // التحقق هل الصلاحية المطلوبة موجودة ضمن مصفوفته
            if (in_array($permission, $userPermissions)) {
                return $next($request);
            }
        }

        // 3️⃣ إذا لم تتوفر الصلاحية يتم منع الوصول برمز 403
        abort(403, 'عذراً، لا تملك الصلاحية الكافية للوصول إلى هذه الصفحة.');
    }
}