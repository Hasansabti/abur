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
            // 🟢 إضافة الحقول الجديدة بعد حقل المعرف الأساسي أو أي حقل تريد
            $table->foreignId('driver_id')->nullable()->after('agency_id')->constrained()->onDelete('set null');
            $table->foreignId('vehicle_id')->nullable()->after('driver_id')->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            // 🔴 حذف الربط الخارجي أولاً ثم حذف الأعمدة عند التراجع (Rollback)
            $table->dropForeign(['trips_driver_id_foreign']);
            $table->dropForeign(['trips_vehicle_id_foreign']);
            
            $table->dropColumn(['driver_id', 'vehicle_id']);
        });
    }
};