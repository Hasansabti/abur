<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminRoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. التحقق من أن المستخدم مسجل دخوله كـ أدمن فعلاً
        if (!auth()->guard('admin')->check()) {
            return redirect()->route('admin.auth.login')->with('error', 'يجب تسجيل الدخول أولاً.');
        }

        // 🟢 تمديد وقت الجلسة للأدمن ديناميكياً بمجرد تخطي تسجيل الدخول
        config(['session.lifetime' => 43200]);        // 43200 دقيقة تعني (30 يوماً) كاملة للجلسة مفتوحة عملياً
        config(['session.expire_on_close' => false]); // حماية: تمنع طرد الأدمن عند قفل علامة التبويب أو المتصفح

        // 2. جلب بيانات الأدمن الحالي
        $admin = auth()->guard('admin')->user();

        // 3. التحقق مما إذا كانت رتبته الحالية مسموح لها بدخول الصفحة
        if (in_array($admin->role, $roles)) {
            return $next($request);
        }

        // إذا لم تكن لديه الصلاحية، يتم إظهار صفحة خطأ 403
        abort(403, 'عذراً، لا تملك الصلاحية الكافية لدخول هذه الصفحة.');
    }
}
