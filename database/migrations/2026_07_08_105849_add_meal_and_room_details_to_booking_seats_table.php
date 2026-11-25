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
        Schema::table('booking_seats', function (Blueprint $table) {
            // إضافة حقول الـ JSON وجعلها قابلة للحذف (nullable)
            // تم وضعها بعد حقل dinner_price لترتيب الجدول بشكل منطقي
            $table->json('meal_options')->nullable();
            $table->json('room_options')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            // حذف الحقول في حال قمت بعمل Rollback للمايقريشن
            $table->dropColumn(['meal_options', 'room_options']);
        });
    }
};