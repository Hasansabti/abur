<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. إضافة حقول مالية لجدول الوكالات
        Schema::table('agencies', function (Blueprint $table) {
            $table->decimal('commission_percentage', 5, 2)->default(5.00)->after('status')->comment('نسبة عمولة المنصة %');
            $table->unsignedInteger('whatsapp_monthly_limit')->default(500)->after('commission_percentage')->comment('الحد المجاني الشهري للرسائل');
            $table->decimal('whatsapp_extra_rate', 8, 2)->default(0.15)->after('whatsapp_monthly_limit')->comment('تكلفة الرسالة الزائدة');
        });

        // 2. إضافة حقول توزيع المبالغ لجدول الحجوزات
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('platform_commission', 10, 2)->default(0.00)->after('total_amount')->comment('حصة المنصة');
            $table->decimal('agency_amount', 10, 2)->default(0.00)->after('platform_commission')->comment('صافي حصة الوكالة');
            $table->string('moyasar_payment_id')->nullable()->after('card_name');
            $table->timestamp('trip_completed_at')->nullable()->after('expires_at')->comment('وقت اكتمال الرحلة للإفراج عن المبلغ');
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['commission_percentage', 'whatsapp_monthly_limit', 'whatsapp_extra_rate']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['platform_commission', 'agency_amount', 'moyasar_payment_id', 'trip_completed_at']);
        });
    }
};