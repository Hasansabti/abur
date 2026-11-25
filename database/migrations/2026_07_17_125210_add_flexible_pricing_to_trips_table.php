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
            // حقل لتحديد هل الرحلة تسمح بحجز السكن فقط (قيمته الافتراضية true بناءً على الكود الخاص بك)
            $table->boolean('allow_accommodation_only')->default(true)->after('price');
            
            // حقل إضافي لتحديد قيمة الخصم (مبلغ ثابت) في حال اختيار السكن فقط
            $table->decimal('accommodation_only_discount', 8, 2)->default(0.00)->after('allow_accommodation_only');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['allow_accommodation_only', 'accommodation_only_discount']);
        });
    }
};