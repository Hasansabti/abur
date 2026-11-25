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
        Schema::table('agencies', function (Blueprint $table) {
            // إضافة الأعمدة الجديدة للعمولة والحدود
            $table->decimal('commission_per_passenger', 8, 2)->default(0.00)->after('status');
            $table->decimal('min_booking_commission', 8, 2)->nullable()->after('commission_per_passenger');
            $table->decimal('max_booking_commission', 8, 2)->nullable()->after('min_booking_commission');

            // إذا أردت حذف الحقل القديم (النسبة المئوية)
            if (Schema::hasColumn('agencies', 'commission_percentage')) {
                $table->dropColumn('commission_percentage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            // التراجع عن التعديلات في حال التراجع عن المايجريشن (Rollback)
            $table->decimal('commission_percentage', 8, 2)->default(0.00);
            
            $table->dropColumn([
                'commission_per_passenger',
                'min_booking_commission',
                'max_booking_commission',
            ]);
        });
    }
};