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
            // إضافة حقل عدد الغرف بعد حقل نوع الغرفة وجعله يبدأ من 1 كقيمة افتراضية
            $table->integer('room_count')->default(1)->after('room_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // تراجع عن إضافة الحقل في حال عمل rollback
            $table->dropColumn('room_count');
        });
    }
};