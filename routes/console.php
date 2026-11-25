<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Trip;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bookings:cancel-expired')->everyFiveMinutes();

Schedule::command('financial:release-funds')->daily();

Schedule::command('subscriptions:expire')->dailyAt('02:00');

// تشغيل التحديث كل ساعة لتغيير حالة الرحلات المنتهية
Schedule::call(function () {
    Trip::shouldBeCompleted()->update(['status' => 'completed']);
})->hourly();