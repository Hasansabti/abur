<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;

class CustomResetPassword extends BaseResetPassword implements ShouldQueue
{
    use Queueable;

    // 🟢 تركنا الكلاس فارغاً هنا ليرث دوال الإرسال الجاهزة من لارافيل 
    // مع تفعيل ميزة الـ Queueable والـ ShouldQueue بنجاح
}