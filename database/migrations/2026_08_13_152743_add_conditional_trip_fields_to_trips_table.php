<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            // نوع الرحلة: داخلية أو خارجية
            $table->enum('route_type', ['domestic', 'international'])
                  ->default('domestic')
                  ->after('transport_type');

            // تفعيل الرحلة المشروطة باكتفاء العدد
            $table->boolean('is_conditional')
                ->default(false)
                ->after('trip_type');

            // الحد الأدنى المطلوب لتأكيد الرحلة
            $table->unsignedInteger('min_seats_required')
                ->nullable()
                ->after('is_conditional');

            // المهلة الزمنية لاكتمال العدد
            $table->timestamp('threshold_deadline_at')
                ->nullable()
                ->after('min_seats_required');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn([
                'route_type',
                'is_conditional',
                'min_seats_required',
                'threshold_deadline_at',
            ]);
        });
    }
};
