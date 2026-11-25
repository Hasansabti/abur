<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 1. تعريف الأسماء المستعارة للـ Middleware الخاصة بك
        $middleware->alias([
            'prevent-back' => \App\Http\Middleware\PreventBackHistory::class,
            'admin.role' => \App\Http\Middleware\AdminRoleMiddleware::class,
            'check.agency.status' => \App\Http\Middleware\CheckAgencyStatus::class,
        ]);

        // 2. 🛡️ التوجيه الديناميكي للضيوف (غير المسجلين) عند انتهاء الجلسة أو الدخول الخاطئ
        $middleware->redirectGuestsTo(function (Request $request) {
            // إذا كان الرابط يبدأ بـ admin، حوله لصفحة دخول الأدمن
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.auth.login');
            }

            // إذا كان الرابط يبدأ بـ agency، حوله لصفحة الدخول 
            if ($request->is('agency') || $request->is('agency/*')) {
                return route('client.auth.login');
            }

            // إذا كان الرابط يبدأ بـ driver (السائقين)
            if ($request->is('driver') || $request->is('driver/*')) {
                return route('driver.auth.login');
            }

            // التوجيه الافتراضي لباقي الموقع (العملاء والزوار)
            return route('client.auth.login');
        });

        // 3. 🔄 التوجيه الديناميكي للمسجلين بالفعل (إذا حاولوا دخول صفحات تسجيل الدخول بالخطأ)
        $middleware->redirectUsersTo(function (Request $request) {
            if (auth('admin')->check()) {
                return route('admin.dashboard');
            }
            if (auth('agency')->check()) {
                return route('agency.dashboard');
            }
            return route('client.index');
        });
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'agency.status' => \App\Http\Middleware\CheckAgencyStatus::class,
        ]);
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: ['webhooks/moyasar/subscriptions']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (Throwable $e, Request $request) {

            // 🟢 إصلاح أمني برميجي: التأكد أولاً أن الخطأ يمتلك دالة getStatusCode (مثل أخطاء HTTP) لكي لا ينهار السيرفر بخطأ 500
            if (method_exists($e, 'getStatusCode') && $e->getStatusCode() === 419) {

                // تحديد رابط التوجيه المناسب حسب القسم
                if ($request->is('admin') || $request->is('admin/*')) {
                    $targetRoute = route('admin.auth.login');
                } elseif ($request->is('agency') || $request->is('agency/*')) {
                    $targetRoute = route('client.auth.login');
                } elseif ($request->is('driver') || $request->is('driver/*')) {
                    $targetRoute = route('driver.auth.login');
                } else {
                    $targetRoute = route('client.auth.login');
                }

                // إذا كان الطلب من نوع GET (تصفح طبيعي) يتم التوجيه مباشرة بدون رسائل خطأ
                if ($request->isMethod('GET')) {
                    return redirect()->to($targetRoute);
                }

                // تظهر الرسالة فقط إذا كان يحاول إرسال بيانات (POST) وانتهت جلسته فعلياً
                return redirect()->to($targetRoute)
                    ->with('error', 'انتهت صلاحية الجلسة، يرجى تسجيل الدخول مرة أخرى.');
            }

            // إذا كان أي خطأ آخر (مثل عدم تسجيل الدخول 401)، نتركه ليتعامل معه لارافيل بشكل طبيعي بناءً على إعدادات redirectGuestsTo فوق
            return null;
        });
    })->create();
