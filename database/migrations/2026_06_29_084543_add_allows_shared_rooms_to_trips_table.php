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
        Schema::table('trips', function (Blueprint $table) {
            // إضافة حقل بوولين (صح أو خطأ) مع قيمة افتراضية "خطأ" (لا تقبل غرف مشتركة تلقائياً)
            // الخاصية after اختيارية لتحديد مكان الحقل في الجدول (مثلاً بعد حقل اسم الوكالة)
            $table->boolean('allows_shared_rooms')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            // تراجع عن الحقل في حال قمت بعمل ميرغريشن رول باك (Rollback)
            $table->dropColumn('allows_shared_rooms');
        });
    }
};