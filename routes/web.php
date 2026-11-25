<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminTicketController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;


use App\Http\Controllers\Client\ClientController;
use App\Http\Controllers\Client\PaymentController;
use App\Http\Controllers\Client\TicketController;
use App\Http\Controllers\Client\RatingAgencyController;

// use App\Http\Controllers\DriverController;
use App\Http\Controllers\Agency\AgencyController;
use App\Http\Controllers\Agency\AgencyEmployeeController;
use App\Http\Controllers\Agency\WalletController;
use App\Http\Controllers\Agency\BookingController;
use App\Http\Controllers\Agency\BookingOperationsController;
use App\Http\Controllers\Agency\MealsReportController;
use App\Http\Controllers\Client\BookingPortalController;
use App\Http\Controllers\Agency\AgencyTripController;
use App\Http\Controllers\Agency\SubscriptionController as AgencySubscriptionController;
use App\Http\Controllers\SubscriptionWebhookController;

use App\Http\Controllers\ManagerController;

/*
|--------------------------------------------------------------------------
| Public Routes (مسارات عامة للجميع)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('client.index');
});

Route::post('/', [ClientController::class, 'authenticate'])->middleware('throttle:5,1')->name('client.auth.authenticate');

Route::get('/b/{token}', [BookingPortalController::class, 'show'])->middleware('throttle:30,1')->name('booking.portal');


/*
|--------------------------------------------------------------------------
| Agency Routes (الوكالات)
|--------------------------------------------------------------------------
*/
Route::post('agency/forgot-password', [AgencyController::class, 'sendResetLinkEmail'])->middleware('throttle:3,1')
    ->name('agency.password.email');

Route::get('agency/subscription/{subscription}/callback', [AgencySubscriptionController::class, 'callback'])
    ->name('agency.subscription.callback');

Route::post('webhooks/moyasar/subscriptions', [SubscriptionWebhookController::class, 'moyasar'])
    ->name('webhooks.moyasar.subscriptions');

Route::prefix('agency')->name('agency.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | 1. مسارات مفتوحة (تسجيل ودخول موحد للمالك والموظفين)
    |--------------------------------------------------------------------------
    */
    Route::get('/login', [AgencyController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AgencyController::class, 'authenticate'])->middleware('throttle:5,1')->name('authenticate');
    Route::post('/register', [AgencyController::class, 'register'])->name('register');

    /*
    |--------------------------------------------------------------------------
    | 2. مسارات تحتاج تسجيل دخول عام (مالك أو موظف)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:agency,employee', 'prevent-back'])->group(function () {

        // لوحة التحكم وتأكيد تسجيل الخروج (متاحة للجميع)
        Route::get('/dashboard', [AgencyController::class, 'agencyDashboard'])->name('dashboard');
        Route::post('/logout', [AgencyController::class, 'logout'])->name('logout');

        // حفظ البيانات التأسيسية
        Route::post('/drivers/store', [AgencyController::class, 'storeDriver'])->name('drivers.store');
        Route::post('/vehicles/store', [AgencyController::class, 'storeVehicle'])->name('vehicles.store');
        Route::post('/bank/store', [AgencyController::class, 'storeBank'])->name('bank.store');
        Route::put('/settings', [AgencyController::class, 'updateSettings'])->name('settings.update');


        Route::get('subscription', [AgencySubscriptionController::class, 'index'])->name('subscription.index');
        Route::post('subscription/checkout', [AgencySubscriptionController::class, 'checkout'])->name('subscription.checkout');
        Route::get('subscription/{subscription}/pay', [AgencySubscriptionController::class, 'pay'])->name('subscription.pay');
        Route::delete('subscription/{subscription}/checkout', [AgencySubscriptionController::class, 'cancelCheckout'])->name('subscription.cancel-checkout');

        

        /*
        |--------------------------------------------------------------------------
        | 3. المسارات المحمية بحالة الوكالة + الصلاحيات المخصصة
        |--------------------------------------------------------------------------
        */
        Route::middleware(['check.agency.status'])->group(function () {

            // === 1. إدارة الموظفين (صلاحية: employees) ===
            Route::middleware('permission:employees')->group(function () {
                Route::get('/employees', [AgencyEmployeeController::class, 'index'])->name('employees');
                Route::post('/employees/store', [AgencyEmployeeController::class, 'store'])->name('employees.store');
                // مسار تحديث بيانات الموظف
                Route::put('/employees/{id}', [AgencyEmployeeController::class, 'update'])->name('employees.update');
                Route::patch('/employees/{id}/toggle', [AgencyEmployeeController::class, 'toggleStatus'])->name('employees.toggle');
                Route::delete('/employees/{id}/destroy', [AgencyEmployeeController::class, 'destroy'])->name('employees.destroy');
            });

            // === 2. إدارة الرحلات (صلاحية: trips) ===
            Route::middleware('permission:trips')->group(function () {
                Route::get('/trips', [AgencyTripController::class, 'indexTrip'])->name('trips.index');
                Route::get('/trips/drafts', [AgencyTripController::class, 'drafts'])->name('trips.draft');
                Route::get('/trips/create', [AgencyTripController::class, 'create'])->name('trips.create');
                Route::post('/trips/store', [AgencyTripController::class, 'store'])->name('trips.store');
                Route::post('trips/{id}/overflow/open',  [AgencyTripController::class, 'openOverflow'])->name('trips.overflow.open');
                Route::post('trips/{id}/overflow/close', [AgencyTripController::class, 'closeOverflow'])->name('trips.overflow.close');
                Route::get('/trips/{id}/edit', [AgencyTripController::class, 'editTrip'])->name('trips.edit');
                Route::put('/trips/{id}', [AgencyTripController::class, 'updateTrip'])->name('trips.update');
                Route::patch('/trips/{id}/request-cancellation', [AgencyTripController::class, 'requestCancellation'])->name('trips.request-cancellation');
                Route::get('/trips/private/private_trips', [AgencyTripController::class, 'indexPrivateTrip'])->name('trips.private_trips');
                Route::PATCH('/private_trips/reject/{id}', [AgencyTripController::class, 'rejectRequestTrip'])->name('private_trips.reject');

                Route::post('/seats/change-number', [BookingController::class, 'changeSeatNumber'])->name('seats.change-number');
                Route::delete('/seats/{id}', [BookingController::class, 'destroy'])->name('seats.destroy');

                // تعيين ومسح طاقم الرحلة
                Route::post('/trips/{trip}/assign-resources', [AgencyTripController::class, 'assignResources'])->name('trips.assign-resources');
                Route::delete('/trips/{trip}/staff/{staff}', [AgencyTripController::class, 'removeStaff'])->name('trips.staff.destroy');
            });

            // === 3. الحجوزات والبيان الأمني (صلاحية: bookings) ===
            Route::middleware('permission:bookings')->group(function () {
                Route::get('/bookings', [AgencyController::class, 'indexBooking'])->name('bookings.index');
                Route::get('/addbookings', [BookingController::class, 'index'])->name('addbookings');
                Route::post('/trips/storeBooking', [AgencyController::class, 'storeAgencyBooking'])->name('trips.storeBooking');
                Route::post('/trips/addstoreBooking', [BookingController::class, 'storeAgencyBooking'])->name('trips.addstoreBooking');
                Route::put('/bookings/{id}', [AgencyController::class, 'updateBooking'])->name('bookings');
                Route::put('/bookings/{id}/update', [AgencyController::class, 'updateBookingStatus'])->name('bookings.update');
                Route::post('/bookings/{id}/cancel', [AgencyController::class, 'cancel'])->name('bookings.cancel');
                Route::patch('/bookings/{id}/confirm-edit', [AgencyController::class, 'confirmEdit'])->name('booking.confirm-edit');
                Route::delete('/bookings/{id}/seat-cancel', [AgencyController::class, 'deletebooking'])->name('bookings.seat-cancel');
                Route::put('/bookings/seat-update/{id}', [AgencyController::class, 'updateSeat'])->name('bookings.seat-update');
                Route::post('/bookings/release-locked/{bookingId}', [AgencyController::class, 'releaseLockedBooking'])->name('bookings.release-locked');
                Route::get('/pending', [AgencyController::class, 'pendingApprovals'])->name('bookings.pending');
                Route::get('/trips/{id}/security-manifest-data', [AgencyTripController::class, 'getSecurityManifestData'])->name('trips.security-manifest-data');

                Route::get('/passengers/lookup', [BookingOperationsController::class, 'lookupPassenger'])->name('passengers.lookup');
                Route::get('/nationalities', [BookingOperationsController::class, 'nationalities'])->name('nationalities.index');
                Route::post('/companions', [BookingOperationsController::class, 'storeCompanion'])->name('companions.store');
                Route::post('/bookings/preview-discount', [BookingOperationsController::class, 'previewDiscount'])->name('bookings.preview-discount');
                Route::post('/bookings/recalculate', [BookingOperationsController::class, 'recalculate'])->name('bookings.recalculate');
                Route::post('/bookings/{booking}/invoice', [BookingOperationsController::class, 'issueInvoice'])->name('bookings.invoice.issue');
                Route::get('/bookings/{booking}/invoice', [BookingOperationsController::class, 'showInvoice'])->name('bookings.invoice.show');
                Route::get('/bookings/{booking}/invoice.pdf', [BookingOperationsController::class, 'downloadInvoice'])->name('bookings.invoice.pdf');
                Route::post('/bookings/{booking}/sms', [BookingOperationsController::class, 'sendSms'])->name('bookings.sms.send');
                Route::post('/bookings/{booking}/sms/preview', [BookingOperationsController::class, 'previewSms'])->name('bookings.sms.preview');
                Route::get('/bookings/{booking}/wallet-context', [BookingOperationsController::class, 'walletContext'])->name('bookings.wallet.context');
                Route::post('/bookings/{booking}/wallet-credit', [BookingOperationsController::class, 'creditWallet'])->name('bookings.wallet.credit');
                Route::post('/bookings/{booking}/client-link', [BookingOperationsController::class, 'clientLink'])->name('bookings.client-link');
                Route::post('/bookings/{booking}/whatsapp', [BookingOperationsController::class, 'whatsappShare'])->name('bookings.whatsapp');
                Route::get('/bookings/{booking}/points-context', [BookingOperationsController::class, 'pointsContext'])->name('bookings.points.context');
                Route::post('/bookings/{booking}/points/redeem', [BookingOperationsController::class, 'redeemPoints'])->name('bookings.points.redeem');
                Route::get('/bookings/{booking}/change-logs', [BookingOperationsController::class, 'changeLogs'])->name('bookings.change-logs');
            });

            // === 4. إدارة الغرف والتوزيع (صلاحية: rooms) ===
            Route::middleware('permission:rooms')->group(function () {
                Route::get('/bookings/rooms', [AgencyController::class, 'showshared'])->name('bookings.rooms');
                Route::get('/bookings/{trip_id}/rooms', [RoomController::class, 'index'])->name('bookings.rooms.details');
                // Route::match(['post', 'put'], '/bookings/update-room-number', [RoomController::class, 'updateRoomNumber'])->name('bookings.update_number');
                // Route::match(['post', 'put'], '/bookings/update-notes', [RoomController::class, 'updateNotes'])->name('bookings.update-notes');
                Route::post('/bookings/room-number', [RoomController::class, 'updateRoomNumber'])
                    ->name('bookings.room_number.update');

                Route::post('/bookings/room-notes', [RoomController::class, 'updateNotes'])
                    ->name('bookings.room_notes.update');
                Route::post('/bookings/{trip_id}/auto-distribute', [RoomController::class, 'autoDistribute'])->name('bookings.auto_distribute');
                Route::get('/bookings/{trip_id}/export-pdf', [RoomController::class, 'exportPdf'])->name('bookings.export_pdf');
                Route::post('/bookings/{trip_id}/notify-clients', [RoomController::class, 'notifyClients'])->name('bookings.notify_clients');
                Route::post('/rooms/{trip}/update-inventory', [AgencyTripController::class, 'updateRoomInventory'])->name('bookings.update_rooms');
                Route::post('/rooms/assign', [AgencyController::class, 'assignPassenger'])->name('bookings.assign_passenger');
                Route::post('/rooms/remove', [AgencyController::class, 'removePassenger'])->name('bookings.remove_passenger');
            });

            // === 5. الأنظمة المالية والمحفظة (صلاحية: finance) ===
            Route::middleware('permission:finance')->group(function () {
                Route::get('wallet/', [WalletController::class, 'indexWallet'])->name('wallet.index');
                Route::post('wallet/request-payout', [WalletController::class, 'requestPayout'])->name('wallet.request-payout');
                Route::get('/discount-codes', [BookingOperationsController::class, 'discountCodes'])->name('discount-codes.index');
                Route::post('/discount-codes', [BookingOperationsController::class, 'storeDiscountCode'])->name('discount-codes.store');
            });

            // === 6. التقارير والخرائط (صلاحية: reports) ===
            Route::middleware('permission:reports')->group(function () {
                Route::get('/trips/seatmap', [AgencyController::class, 'seat'])->name('trips.seatmap');
                Route::get('/flight/map', [AgencyController::class, 'flightmap'])->name('trips.flightmap');
                Route::get('/reports', [AgencyController::class, 'reports'])->name('trips.reports');
                Route::get('/reports/audit', [AgencyController::class, 'auditLogsIndex'])->name('reports.audit');
                Route::get('/reports/meals', [MealsReportController::class, 'index'])->name('reports.meals');
            });
        });
    });
});




/*
|--------------------------------------------------------------------------
| Client Routes (العملاء)
|--------------------------------------------------------------------------
*/
Route::post('passenger/forgot-password', [ClientController::class, 'sendResetLinkEmail'])->middleware('throttle:3,1')
    ->name('passenger.password.email');

Route::prefix('client')->name('client.')->group(function () {

    // 1. مسارات مفتوحة للعامة (تستقبل الـ Web والـ API بشكل طبيعي)

    //عرض صفحة تسجيل الدخول
    Route::get('/login', [ClientController::class, 'login'])->name('auth.login');
    // Route::get('/client/login', [ClientController::class, 'login'])->name('login');

    Route::get('/trips/private_trip', [ClientController::class, 'indexT'])->name('trips.private_trip');

    //معالجة تسجيل الدخول
    Route::post('/login', [ClientController::class, 'authenticate'])->middleware('throttle:5,1')->name('auth.authenticate');

    //عرض صفحة تسجيل حساب جديد
    Route::get('/register', [ClientController::class, 'create'])->name('auth.register');

    //معالجة تسجيل حساب جديد
    Route::post('/register', [ClientController::class, 'register'])->name('auth.register_store');

    //عرض الصفحة الرئيسية
    Route::get('/', [ClientController::class, 'index'])->name('index');

    //عرض صفحة الرحلات
    Route::get('/trips', [ClientController::class, 'trips'])->name('trips');

    //عرض رحلة محددة
    Route::get('/trip/{id}', [ClientController::class, 'show'])->name('trip.show');

    //عرض الوكالات
    Route::get('/agencies', [ClientController::class, 'agenciesshow'])->name('agency.agencies');

    Route::get('/rate/{agency}', [RatingAgencyController::class, 'create'])->name('rate.public');
    Route::post('/rate/{agency}', [RatingAgencyController::class, 'store'])->name('rate.store');

    // ============================================================
    // مجموعة 1: صفحات الحجز (بدون prevent-back عشان زر الرجوع يشتغل)
    // ============================================================
    Route::middleware(['auth:web,sanctum'])->group(function () {
        Route::get('/select-seat/{id}', [ClientController::class, 'selectSeat'])->name('select_seat');
        Route::match(['get', 'post'], '/checkout', [ClientController::class, 'checkout'])->name('checkout');
        Route::post('/booking/store', [ClientController::class, 'storeBooking'])->name('booking.store');
    });

    // ============================================================
    // مجموعة 2: باقي الصفحات المحمية (مع prevent-back)
    // ============================================================
    Route::middleware(['auth:web,sanctum', 'prevent-back'])->group(function () {
        Route::get('/success/{id}', [ClientController::class, 'success'])->name('success');

        // مسار استقبال حفظ طلب الرحلة الخاصة
        Route::post('/private-trips/store', [ClientController::class, 'storePrivateTrip'])->name('private_trip.store');

        // صفحة عرض نموذج الدفع
        Route::get('/payment/{booking}/pay', [PaymentController::class, 'showPaymentPage'])->name('payment.pay');

        // رابط استقبال نتيجة الدفع من ميسر
        Route::get('/payment/callback', [PaymentController::class, 'handleCallback'])->name('payment.callback');

        //email
        Route::get('/abur/connectUs', [ClientController::class, 'connectUs'])->name('abur.connectUs');
        //send
        Route::post('contact/send', [ClientController::class, 'sendContact'])->name('contact.send');

        //التراجع عن الدفع
        Route::delete('/payment/{booking}/cancel', [App\Http\Controllers\Client\PaymentController::class, 'cancelPayment'])->name('payment.cancel');

        Route::delete('/payment/{booking}/abort', [App\Http\Controllers\Client\PaymentController::class, 'abortPayment'])
            ->name('payment.abort');

        // Route::get('/my-bookings', [ClientController::class, 'myBookings'])->name('my_bookings');
        Route::get('/profile', [ClientController::class, 'profile'])->name('profile');

        // مسار استقبال طلبات تعديل الحجز
        Route::put('/update-booking-request', [ClientController::class, 'updateBookingRequest'])
            ->name('booking.update-request');

        //حفظ المرافق
        Route::post('/companions', [ClientController::class, 'storeCompanion'])
            ->name('companions.store');
        //تعديل بيانات المرافق
        Route::put('/companions/update/{companion}', [ClientController::class, 'update'])->name('companions.update');
        // مسار حذف المرافق
        Route::delete('/profile/companions/{id}', [ClientController::class, 'destroyCompanion'])
            ->name('companions.destroy');
        Route::post('/logout', [ClientController::class, 'logout'])->name('logout');
        Route::delete('/cancel/{id}', [ClientController::class, 'cancel'])->name('cancel');
        Route::put('/profile/update', [ClientController::class, 'updateUser'])->name('profile.update');
        Route::post('/profile', [ClientController::class, 'uploadId'])->name('profile.id');
        Route::post('/ratings', [ClientController::class, 'storeRating'])->name('ratings');

        //صفحة الحجوزات
        // Route::get('/my-bookings', [BookingController::class, 'index'])->name('client.bookings.index');

        //صفحة عرض الحجوزات
        Route::get('/bookings', [ClientController::class, 'indexB'])->name('bookings');

        //صفحة التذاكر
        Route::get('/my-tickets', [TicketController::class, 'index'])->name('tickets.index2');

        //رفع تذكرة
        Route::post('/tickets/store', [TicketController::class, 'store'])->name('tickets.store');
    });
});

Route::get('/passenger/login', [ClientController::class, 'login'])->name('login');
/*
|--------------------------------------------------------------------------
| Admin Routes (المدير)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {

    // ==========================================
    // 1. مسارات الضيوف (غير مسجلي الدخول)
    // ==========================================
    Route::middleware(['guest:admin'])->group(function () {
        Route::get('/login', [AdminController::class, 'login'])->name('auth.login');
        Route::post('/login', [AdminController::class, 'processLogin'])->name('auth.login.submit');
    });

    // ==========================================
    // 2. المسارات المحمية (تتطلب تسجيل دخول كـ أدمن)
    // ==========================================
    Route::middleware(['auth:admin', 'prevent-back'])->group(function () {

        Route::post('/logout', [AdminController::class, 'logout'])->name('auth.logout');

        // ------------------------------------------
        // مسارات مشتركة (لكل الرتب: المدير العام، الشريك، الدعم الفني)
        // ------------------------------------------
        Route::middleware(['admin.role:super_admin,partner,support'])->group(function () {
            Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
            Route::get('/users', [AdminController::class, 'showEdit'])->name('users.showEdit');
            Route::get('/users/{id}/edit', [AdminController::class, 'edit'])->name('users.edit');
            Route::put('/users/{id}', [AdminController::class, 'update'])->name('users.update');
            Route::get('/users/pending', [AdminController::class, 'identityRequests'])->name('users.pending');
            Route::post('/users/verify/{id}', [AdminController::class, 'verifyUser'])->name('users.verify');
            Route::post('/users/reject/{id}', [AdminController::class, 'rejectuser'])->name('users.reject');
            Route::get('/trips/approveTrip', [AdminController::class, 'pendingTrips'])->name('trips.approveTrip');

            Route::post('/approve/{id}', [AdminController::class, 'approveTrip'])->name('approve');
            Route::delete('/trips/{id}', [AdminController::class, 'destroyTrip'])->name('trips.destroy');
            Route::put('/trips/{id}/reject-cancellation', [AdminController::class, 'rejectCancellation'])->name('reject_cancellation');

            Route::delete('/agencies/{id}', [AdminController::class, 'destroy'])->name('agencies.destroy');
            Route::delete('/users/delete/{id}', [AdminController::class, 'delUser'])->name('users.delete');
            // عرض التذاكر للأدمن
            Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');

            // إرسال الرد
            Route::post('/tickets/{id}/reply', [AdminTicketController::class, 'reply'])->name('tickets.reply');

            Route::get('subscriptions', [AdminSubscriptionController::class, 'index'])->name('subscriptions');
Route::post('subscriptions/assign', [AdminSubscriptionController::class, 'assign'])->name('subscriptions.assign');
Route::post('subscriptions/{subscription}/approve', [AdminSubscriptionController::class, 'approve'])->name('subscriptions.approve');
Route::post('subscriptions/{subscription}/reject', [AdminSubscriptionController::class, 'reject'])->name('subscriptions.reject');
Route::post('subscriptions/{subscription}/extend', [AdminSubscriptionController::class, 'extend'])->name('subscriptions.extend');
Route::post('subscriptions/{subscription}/cancel', [AdminSubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

Route::post('subscription-plans', [AdminSubscriptionController::class, 'storePlan'])->name('subscription-plans.store');
Route::put('subscription-plans/{plan}', [AdminSubscriptionController::class, 'updatePlan'])->name('subscription-plans.update');
Route::post('subscription-plans/{plan}/toggle', [AdminSubscriptionController::class, 'togglePlan'])->name('subscription-plans.toggle');
Route::delete('subscription-plans/{plan}', [AdminSubscriptionController::class, 'destroyPlan'])->name('subscription-plans.destroy');
        });

        // ------------------------------------------
        // مسارات متقدمة (للمدير العام والشريك فقط)
        // ------------------------------------------
        Route::middleware(['admin.role:super_admin,partner'])->group(function () {
            Route::post('/agencies/{id}/approve', [AdminController::class, 'approveAgency'])->name('agencies.approve');
            Route::get('/agencies/index', [AdminController::class, 'adminAgency'])->name('agencies.index');
            Route::post('/agencies/{id}/block', [AdminController::class, 'blockAgency'])->name('agencies.block');
            Route::post('/reject/{id}', [AdminController::class, 'rejectTrip'])->name('reject_trip');

            Route::post('/agencies/{agency}/approve-cr', [AdminController::class, 'approveCrUpdate'])->name('agencies.approve-cr');
            Route::post('/agencies/{agency}/reject-cr', [AdminController::class, 'rejectCrUpdate'])->name('agencies.reject-cr');

            Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts');
            Route::post('payouts/{payoutRequest}/approve', [PayoutController::class, 'approve'])->name('payouts.approve');
            Route::post('payouts/{payoutRequest}/reject', [PayoutController::class, 'reject'])->name('payouts.reject');
            Route::post('/agencies/{agency}/update-commission', [PayoutController::class, 'updateCommission'])->name('agencies.update-commission');
            // مسار تحديث عمولة جميع الوكالات
            Route::post('/agencies/update-all-commissions', [PayoutController::class, 'updateAllCommissions'])
                ->name('agencies.update-all-commissions');
        });

        // ------------------------------------------
        // مسارات حصرية (للمدير العام super_admin فقط)
        // ------------------------------------------
        Route::middleware(['admin.role:super_admin'])->group(function () {
            // إصلاح: تم توجيه الـ Resource إلى الـ ManagerController لتفادي الكتابة فوق الـ index والـ edit التابعين للـ AdminController
            Route::resource('managers', ManagerController::class);

            // إصلاح وتأمين: تم نقل مسار تنظيف الكاش والأوامر البرمجة إلى هنا ليصبح محمياً ولا يستدعيه إلا الـ super_admin
            Route::get('/clear-config', function () {
                \Illuminate\Support\Facades\Artisan::call('config:clear');
                \Illuminate\Support\Facades\Artisan::call('route:clear');
                return "تم تنظيف كاش الإعدادات والمسارات بنجاح على السيرفر الفعلي! ✨";
            })->name('clear-config');
        });
    });
});


/*
|--------------------------------------------------------------------------
| Driver Routes (السائقين)
|--------------------------------------------------------------------------
*/
// Route::prefix('driver')->name('driver.')->group(function () {
//     // 1. مسارات مفتوحة
//     Route::get('/login', [DriverController::class, 'login'])->name('auth.login');

//     // 2. مسارات محمية
//     // إصلاح: تم تحديد الحارس المخصص auth:driver بدلاً من auth العام لحماية لوحة السائق من دخول العملاء
//     Route::middleware(['auth:driver', 'prevent-back'])->group(function () {
//         Route::get('/dashboard', [DriverController::class, 'dashboardDriver'])->name('dashboard');
//         Route::get('/passinger', [DriverController::class, 'passinger'])->name('passinger'); 
//     });
// });


/*
|--------------------------------------------------------------------------
| About Routes (صفحة من نحن - عامة)
|--------------------------------------------------------------------------
*/
Route::prefix('abur')->name('abur.')->group(function () {
    Route::get('/we', [ClientController::class, 'we'])->name('we');
    Route::get('/policies', [ClientController::class, 'policies'])->name('policies');
});



/*
|--------------------------------------------------------------------------
| Password Reset Routes (مسارات استعادة كلمة المرور العامة)
|--------------------------------------------------------------------------
*/

Route::get('password/reset/{token}', function ($token) {
    return view('client.auth.reset_password', ['token' => $token]);
})->name('password.reset');

Route::post('password/reset', [ClientController::class, 'resetPassword'])->name('password.update');


/*
|--------------------------------------------------------------------------
| مسار ابل باي
|--------------------------------------------------------------------------
*/
Route::get('/.well-known/apple-developer-merchantid-domain-association', function () {
    $content = '7B227073704964223A2243423939394539333137363744463341383644424145363131413331323544303133353543373842303636343033393832323336324134413238303246334136222C2276657273696F6E223A312C22637265617465644F6E223A313632363336363139383031362C227369676E6174757265223A2233303830303630393261383634383836663730643031303730326130383033303830303230313031333130663330306430363039363038363438303136353033303430323031303530303330383030363039326138363438383666373064303130373031303030306130383033303832303365333330383230333838613030333032303130323032303834633330343134393531396435343336333030613036303832613836343863653364303430333032333037613331326533303263303630333535303430333063323534313730373036633635323034313730373036633639363336313734363936663665323034393665373436353637373236313734363936663665323034333431323032643230343733333331323633303234303630333535303430623063316434313730373036633635323034333635373237343639363636393633363137343639366636653230343137353734363836663732363937343739333131333330313130363033353530343061306330613431373037303663363532303439366536333265333130623330303930363033353530343036313330323535353333303165313730643331333933303335333133383330333133333332333533373561313730643332333433303335333133363330333133333332333533373561333035663331323533303233303630333535303430333063316336353633363332643733366437303264363237323666366236353732326437333639363736653566353534333334326435303532346634343331313433303132303630333535303430623063306236393466353332303533373937333734363536643733333131333330313130363033353530343061306330613431373037303663363532303439366536333265333130623330303930363033353530343036313330323535353333303539333031333036303732613836343863653364303230313036303832613836343863653364303330313037303334323030303463323135373765646562643663376232323138663638646437303930613132313864633762306264366632633238336438343630393564393461663461353431316238333432306564383131663334303765383333333166316335346333663765623332323064366261643564346566663439323839383933653763306631336133383230323131333038323032306433303063303630333535316431333031303166663034303233303030333031663036303335353164323330343138333031363830313432336632343963343466393365346566323765366334663632383663336661326262666432653462333034353036303832623036303130353035303730313031303433393330333733303335303630383262303630313035303530373330303138363239363837343734373033613266326636663633373337303265363137303730366336353265363336663664326636663633373337303330333432643631373037303663363536313639363336313333333033323330383230313164303630333535316432303034383230313134333038323031313033303832303130633036303932613836343838366637363336343035303133303831666533303831633330363038326230363031303530353037303230323330383162363063383162333532363536633639363136653633363532303666366532303734363836393733323036333635373237343639363636393633363137343635323036323739323036313665373932303730363137323734373932303631373337333735366436353733323036313633363336353730373436313665363336353230366636363230373436383635323037343638363536653230363137303730366336393633363136323663363532303733373436313665363436313732363432303734363537323664373332303631366536343230363336663665363436393734363936663665373332303666363632303735373336353263323036333635373237343639363636393633363137343635323037303666366336393633373932303631366536343230363336353732373436393636363936333631373436393666366532303730373236313633373436393633363532303733373436313734363536643635366537343733326533303336303630383262303630313035303530373032303131363261363837343734373033613266326637373737373732653631373037303663363532653633366636643266363336353732373436393636363936333631373436353631373537343638366637323639373437393266333033343036303335353164316630343264333032623330323961303237613032353836323336383734373437303361326632663633373236633265363137303730366336353265363336663664326636313730373036633635363136393633363133333265363337323663333031643036303335353164306530343136303431343934353764623666643537343831383638393839373632663765353738353037653739623538323433303065303630333535316430663031303166663034303430333032303738303330306630363039326138363438383666373633363430363164303430323035303033303061303630383261383634386365336430343033303230333439303033303436303232313030626530393537316665373165316537333562353565356166616362346337326665623434356633303138353232326337323531303032623631656264366635353032323130306431386233353061356464366464366562313734363033356231316562326365383763666133653661663663626438333830383930646338326364646161363333303832303265653330383230323735613030333032303130323032303834393664326662663361393864613937333030613036303832613836343863653364303430333032333036373331316233303139303630333535303430333063313234313730373036633635323035323666366637343230343334313230326432303437333333313236333032343036303335353034306230633164343137303730366336353230343336353732373436393636363936333631373436393666366532303431373537343638366637323639373437393331313333303131303630333535303430613063306134313730373036633635323034393665363332653331306233303039303630333535303430363133303235353533333031653137306433313334333033353330333633323333333433363333333035613137306433323339333033353330333633323333333433363333333035613330376133313265333032633036303335353034303330633235343137303730366336353230343137303730366336393633363137343639366636653230343936653734363536373732363137343639366636653230343334313230326432303437333333313236333032343036303335353034306230633164343137303730366336353230343336353732373436393636363936333631373436393666366532303431373537343638366637323639373437393331313333303131303630333535303430613063306134313730373036633635323034393665363332653331306233303039303630333535303430363133303235353533333035393330313330363037326138363438636533643032303130363038326138363438636533643033303130373033343230303034663031373131383431396437363438356435316135653235383130373736653838306132656664653762616534646530386466633462393365313333353664353636356233356165323264303937373630643232346537626261303866643736313763653838636237366262363637306265633865383239383466663534343561333831663733303831663433303436303630383262303630313035303530373031303130343361333033383330333630363038326230363031303530353037333030313836326136383734373437303361326632663666363337333730326536313730373036633635326536333666366432663666363337333730333033343264363137303730366336353732366636663734363336313637333333303164303630333535316430653034313630343134323366323439633434663933653465663237653663346636323836633366613262626664326534623330306630363033353531643133303130316666303430353330303330313031666633303166303630333535316432333034313833303136383031346262623064656131353833333838396161343861393964656265626465626166646163623234616233303337303630333535316431663034333033303265333032636130326161303238383632363638373437343730336132663266363337323663326536313730373036633635326536333666366432663631373037303663363537323666366637343633363136373333326536333732366333303065303630333535316430663031303166663034303430333032303130363330313030363061326138363438383666373633363430363032306530343032303530303330306130363038326138363438636533643034303330323033363730303330363430323330336163663732383335313136393962313836666233356333353663613632626666343137656464393066373534646132386562656631396338313565343262373839663839386637396235393966393864353431306438663964653963326665303233303332326464353434323162306133303537373663356466333338336239303637666431373763326332313664393634666336373236393832313236663534663837613764316239396362396230393839323136313036393930663039393231643030303033313832303138643330383230313839303230313031333038313836333037613331326533303263303630333535303430333063323534313730373036633635323034313730373036633639363336313734363936663665323034393665373436353637373236313734363936663665323034333431323032643230343733333331323633303234303630333535303430623063316434313730373036633635323034333635373237343639363636393633363137343639366636653230343137353734363836663732363937343739333131333330313130363033353530343061306330613431373037303663363532303439366536333265333130623330303930363033353530343036313330323535353330323038346333303431343935313964353433363330306430363039363038363438303136353033303430323031303530306130383139353330313830363039326138363438383666373064303130393033333130623036303932613836343838366637306430313037303133303163303630393261383634383836663730643031303930353331306631373064333233313330333733313335333133363332333333313338356133303261303630393261383634383836663730643031303933343331316433303162333030643036303936303836343830313635303330343032303130353030613130613036303832613836343863653364303430333032333032663036303932613836343838366637306430313039303433313232303432306230623838633665353565336562356235376264326164393835303766376539313833383966343130303635646464643335656337373333616365383230303933303061303630383261383634386365336430343033303230343438333034363032323130306236653061383437306334666364353335313232343766316565323263636262393665636634666630316366313162336437663339303962626137303665633930323231303061346631303764616165333761626631663665653031323230646236373432333237666534343061623639613537393331306232303833383762336135363233303030303030303030303030227D';

    return response($content, 200)
        ->header('Content-Type', 'text/plain');
});


Route::get('/update-trips-now', function () {
    $updatedCount = Trip::shouldBeCompleted()->update(['status' => 'completed']);
    return "تم تحديث {$updatedCount} رحلة بنجاح إلى تمت الرحلة!";
});

Route::post('/tour/seen', function () {
    if (auth()->check()) {
        auth()->user()->update(['tour_seen' => true]);
    }
    return response()->noContent();
})->middleware('auth')->name('tour.seen');
