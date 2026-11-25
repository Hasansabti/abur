<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. تنظيف أي بقايا للجدول من المحاولة الفاشلة السابقة لضمان بداية نظيفة
        Schema::dropIfExists('trip_rooms');

        // 2. إنشاء جدول الغرف الآن بأمان
        Schema::create('trip_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
            $table->string('room_type');
            $table->string('room_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. تنظيف البيانات النصية القديمة من جدول الحجوزات وتحويلها إلى null
        DB::table('booking_seats')->update(['trip_room_id' => null]);

        // 4. تعديل جدول الحجوزات وربط المفتاح الأجنبي
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->unsignedBigInteger('trip_room_id')->nullable()->change();

            $table->foreign('trip_room_id')
                  ->references('id')
                  ->on('trip_rooms')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropForeign(['trip_room_id']);
        });

        Schema::dropIfExists('trip_rooms');
    }
};