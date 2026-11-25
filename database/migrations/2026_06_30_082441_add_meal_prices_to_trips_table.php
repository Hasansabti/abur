<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. تعديل جدول الرحلات (Trips) لتحديد أسعار الخدمات
        Schema::table('trips', function (Blueprint $table) {
            // إضافة حقول أسعار الوجبات منفردة
            $table->decimal('breakfast_price', 8, 2)->nullable()->comment('سعر وجبة الفطور الفردية');
            $table->decimal('lunch_price', 8, 2)->nullable()->comment('سعر وجبة الغداء الفردية');
            $table->decimal('dinner_price', 8, 2)->nullable()->comment('سعر وجبة العشاء الفردية');
            
            // أسعار الباقات الثنائية الاختيارية
            $table->decimal('price_bf_ln', 8, 2)->nullable()->comment('سعر باقة: فطور + غداء');
            $table->decimal('price_ln_dn', 8, 2)->nullable()->comment('سعر باقة: غداء + عشاء');
            $table->decimal('price_bf_dn', 8, 2)->nullable()->comment('سعر باقة: فطور + عشاء');

            // سعر الباقة الثلاثية الكاملة
            $table->decimal('price_all_meals', 8, 2)->nullable()->comment('سعر باقة الوجبات الكاملة: فطور + غداء + عشاء');
        });

        // 2. تعديل جدول مقاعد الحجوزات (Booking Seats) لتوثيق اختيارات الركاب وأسعارها
        Schema::table('booking_seats', function (Blueprint $table) {
            // خيارات الوجبات المختارة من قبل المسافر
            $table->boolean('with_breakfast')->default(false)->comment('هل طلب فطور؟');
            $table->boolean('with_lunch')->default(false)->comment('هل طلب غداء؟');
            $table->boolean('with_dinner')->default(false)->comment('هل طلب عشاء؟');

            // تسجيل أسعار الوجبات الفردية وقت الحجز الفعلي (حماية للبيانات المالية)
            $table->decimal('breakfast_price', 8, 2)->default(0.00)->comment('سعر الفطور المعتمد أثناء الحجز');
            $table->decimal('lunch_price', 8, 2)->default(0.00)->comment('سعر الغداء المعتمد أثناء الحجز');
            $table->decimal('dinner_price', 8, 2)->default(0.00)->comment('سعر العشاء المعتمد أثناء الحجز');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // تراجع آمن ونظيف عن كافة الحقول المضافة لجدول الرحلات
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn([
                'breakfast_price', 
                'lunch_price', 
                'dinner_price', 
                'price_bf_ln', 
                'price_ln_dn', 
                'price_bf_dn', 
                'price_all_meals'
            ]);
        });

        // تم إصلاح التراجع هنا لحذف كافة الحقول الستة المضافة لجدول المقاعد لمنع تضارب قاعدة البيانات لاحقاً
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropColumn([
                'with_breakfast', 
                'with_lunch', 
                'with_dinner',
                'breakfast_price',
                'lunch_price',
                'dinner_price'
            ]);
        });
    }
};