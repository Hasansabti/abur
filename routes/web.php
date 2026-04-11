<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\DriverController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('client.auth.login');
});

/*
|--------------------------------------------------------------------------
| Agency Routes
|--------------------------------------------------------------------------
*/
Route::prefix('agency')->name('agency.')->group(function () {
    Route::get('/trips/create', [AgencyController::class, 'create'])->name('trips.create');
    Route::post('/trips/store', [AgencyController::class, 'store'])->name('trips.store');
    Route::get('/trips/seatmap', [AgencyController::class, 'seat'])->name('trips.seatmap');
    Route::get('/trips', [AgencyController::class, 'indexTrip'])->name('trips.index');
    Route::get('/bookings', [AgencyController::class, 'indexBooking'])->name('bookings.index');
    Route::get('/reports', [AgencyController::class, 'report'])->name('trips.reports');
    Route::get('/pending', [AgencyController::class, 'pendingApprovals'])->name('bookings.pending');
    Route::get('/flight/map', [AgencyController::class, 'flightmap'])->name('trips.flightmap');
    Route::get('/dashboard', [AgencyController::class, 'agencyDashboard'])->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Client Routes
|--------------------------------------------------------------------------
*/
Route::prefix('client')->name('client.')->group(function () {
    Route::get('/', [ClientController::class, 'index'])->name('index');
    Route::get('/success', [ClientController::class, 'success'])->name('success');
    Route::get('/booking/select-seat/{id}', [ClientController::class, 'selectSeat'])->name('select_seat');
    Route::get('/checkout', [AgencyController::class, 'checkout'])->name('checkout');
    Route::get('/my-bookings', [ClientController::class, 'myBookings'])->name('my_bookings');
    Route::get('/trip/{id}', [ClientController::class, 'show'])->name('trip.show');
    Route::get('/profile', [ClientController::class, 'profile'])->name('profile');
    Route::get('/login', [ClientController::class, 'login'])->name('auth.login');
    Route::get('/register', [ClientController::class, 'create'])->name('auth.regis');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/agencies/index', [AdminController::class, 'adminAgency'])->name('agencies.index'); // مراجعة الحوالات واعتماد الحجوزات
    // Route::get('/booking/pending', [AdminController::class, 'pendingApprovals'])->name('bookings.pending');
    Route::get('/trips/approve', [AdminController::class, 'pendingTrips'])->name('trips.approveTrip');
    Route::get('/rejact', [AdminController::class, 'rerejectTrip'])->name('reject_trip');
    Route::get('/users/pending', [AdminController::class, 'identityRequests'])->name('users.pending');
    Route::get('/login', [AdminController::class, 'login'])->name('auth.login');
    Route::get('/logout', [AdminController::class, 'logout'])->name('auth.logout');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('driver')->name('driver.')->group(function () {
Route::get('/login', [DriverController::class, 'login'])->name('auth.login');
Route::get('/dashboard', [DriverController::class, 'dashboardDriver'])->name('dashboard');
});
