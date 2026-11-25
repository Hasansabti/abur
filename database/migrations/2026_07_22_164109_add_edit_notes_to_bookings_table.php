<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تشغيل المايقريشن لإضافة العمود.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // إضافة حقل ملاحظات التعديل بعد حقل اسم البطاقة ليكون الترتيب منظماً
            $table->text('edit_notes')->nullable()->after('card_name');
            $table->unsignedTinyInteger('edit_count')->default(0)->after('edit_notes');
        });
    }

    /**
     * التراجع عن التغييرات (في حال عمل Rollback).
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('edit_notes');
            $table->dropColumn('edit_count');
        });
    }
};