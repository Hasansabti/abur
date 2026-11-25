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
        Schema::table('bookings', function (Blueprint $table) {
            // 1. إضافة حقل الوكالة كـ Foreign Key مرن (nullable) ليرتبط بجدول الوكالات
            $table->foreignId('agency_id')
                  ->nullable()
                  ->after('user_id') // يوضع بعد معرف المستخدم لتنظيم الجدول
                  ->constrained('agencies')
                  ->onDelete('set null'); // في حال حذف الوكالة لا تضيع بيانات الحجز

            // 2. إضافة حقل قفل المقاعد (true = المقاعد مقفلة للرحلة، false = حجز عادي)
            $table->boolean('is_locked')
                  ->default(false)
                  ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // حذف العلاقات والحقول عند التراجع عن المايقريشن
            $table->dropForeign(['agency_id']);
            $table->dropColumn(['agency_id', 'is_locked']);
        });
    }
};