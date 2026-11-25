<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAgencyStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1️⃣ إذا كان المسجل دخوله هو مالك الوكالة
        if (auth('agency')->check()) {
            if (auth('agency')->user()->status === 'blocked') {
                return $this->denyAccess('agency', 'حساب الوكالة الخاص بك محظور.');
            }
        }

        // 2️⃣ إذا كان المسجل دخوله هو موظف بالوكالة
        elseif (auth('employee')->check()) {
            $employee = auth('employee')->user();

            // التحقق من تفعيل الموظف نفسه
            if (isset($employee->is_active) && !$employee->is_active) {
                return $this->denyAccess('employee', 'تم تعطيل حساب الموظف الخاص بك.');
            }

            // التحقق من حالة الوكالة التابع لها الموظف
            if (!$employee->agency || $employee->agency->status === 'blocked') {
                return $this->denyAccess('employee', 'حساب الوكالة التابع لها غير نشط أو محظور.');
            }
        }

        return $next($request);
    }

    /**
     * إنهاء الجلسة وإعادة التوجيه إلى صفحة تسجيل الدخول لمنع الـ Loop
     */
    protected function denyAccess(string $guard, string $message): Response
    {
        abort(403, $message);
    }
}