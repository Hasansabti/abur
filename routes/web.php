<?php

use Illuminate\Support\Facades\Route;

use App\Models\Trip;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminTicketController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\MoyasarReconciliationController;


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
use App\Http\Controllers\Client\BookingShareController;
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

Route::get('/b/{token}', [BookingShareController::class, 'show'])->name('booking.share');

Route::post('/', [ClientController::class, 'authenticate'])->middleware('throttle:5,1')->name('client.auth.authenticate');


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
    Route::get('/create', [AgencyController::class, 'create'])->name('auth.register');


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

                // الفواتير الإضافية وملاحظات العميل
                Route::post('/bookings/{booking}/extra-charge', [AgencyController::class, 'storeExtraCharge'])->name('bookings.extra_charge.store');
                Route::post('/bookings/{booking}/note-handled', [AgencyController::class, 'noteHandled'])->name('bookings.note_handled');
                Route::post('/extra-charges/{charge}/cancel', [AgencyController::class, 'cancelExtraCharge'])->name('extra_charge.cancel');

                Route::get('/passengers/lookup', [BookingOperationsController::class, 'lookupPassenger'])->name('passengers.lookup');
                Route::get('/companions', [BookingOperationsController::class, 'companions'])->name('companions.index');
                Route::post('/companions', [BookingOperationsController::class, 'storeCompanion'])->name('companions.store');
                Route::post('/discount-codes/preview', [BookingOperationsController::class, 'previewDiscount'])->name('discount.preview');
                Route::post('/bookings/{booking}/invoice', [BookingOperationsController::class, 'issueInvoice'])->name('bookings.invoice.issue');
                Route::get('/invoices/{invoice}/print', [BookingOperationsController::class, 'printInvoice'])->name('bookings.invoice.print');
                Route::get('/bookings/{booking}/changes', [BookingOperationsController::class, 'changeLogs'])->name('bookings.changes');
                Route::post('/bookings/{booking}/sms', [BookingOperationsController::class, 'sendSms'])->name('bookings.sms');
                Route::post('/bookings/{booking}/credit', [BookingOperationsController::class, 'addCredit'])->name('bookings.credit');
                Route::post('/bookings/{booking}/customer-link', [BookingOperationsController::class, 'customerLink'])->name('bookings.customer-link');
                Route::post('/bookings/{booking}/whatsapp', [BookingOperationsController::class, 'shareWhatsapp'])->name('bookings.whatsapp');
                Route::post('/bookings/{booking}/points', [BookingOperationsController::class, 'redeemPoints'])->name('bookings.points');
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
            });

            // === 6. التقارير والخرائط (صلاحية: reports) ===
            Route::middleware('permission:reports')->group(function () {
                Route::get('/trips/seatmap', [AgencyController::class, 'seat'])->name('trips.seatmap');
                Route::get('/flight/map', [AgencyController::class, 'flightmap'])->name('trips.flightmap');
                Route::get('/reports', [AgencyController::class, 'reports'])->name('trips.reports');
                Route::get('/reports/audit', [AgencyController::class, 'auditLogsIndex'])->name('reports.audit');
                Route::get('/reports/meals', [BookingOperationsController::class, 'mealsReport'])->name('reports.meals');
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

    Route::get('/extra-charge/{token}/pay', [PaymentController::class, 'extraChargePay'])
    ->middleware('throttle:20,1')
    ->name('extra_charge.pay');
     // العودة من دفع الفاتورة الإضافية (ميسر) - الاسم النهائي: client.extra_charge.return
        // التحقق يتم عبر التوكن + استعلام ميسر مباشرة
Route::get('/extra-charge/{token}/return', [PaymentController::class, 'extraChargeReturn'])
    ->middleware('throttle:20,1')
    ->name('extra_charge.return');
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

        Route::get('/moyasar/payments', [MoyasarReconciliationController::class, 'index'])->name('moyasar.payments');

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

            Route::get('admin/agencies/{agency}/ledger', [PayoutController::class, 'ledger'])->name('admin.agencies.ledger');
            // مسار تحديث عمولة جميع الوكالات
            Route::post('/agencies/update-all-commissions', [PayoutController::class, 'updateAllCommissions'])
                ->name('agencies.update-all-commissions');
        });

        // ------------------------------------------
        // مسارات حصرية (للمدير العام super_admin فقط)
        // ------------------------------------------
        Route::middleware(['admin.role:super_admin'])->group(function () {
            Route::resource('managers', AdminController::class);

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
    // ⚠️ أبقِ هنا النص الأصلي الطويل (hex) كما هو عندك دون أي تغيير
    $content = 'PASTE_YOUR_ORIGINAL_HEX_STRING_HERE';

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