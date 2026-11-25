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
            // 1. فحص وإضافة تاريخ انتهاء السجل التجاري إذا لم يكن موجوداً
            if (!Schema::hasColumn('agencies', 'cr_expiry_date')) {
                $table->date('cr_expiry_date')->nullable()->after('commercial_id');
            }
            
            // 2. فحص وإضافة رقم الهاتف إذا لم يكن موجوداً
            if (!Schema::hasColumn('agencies', 'phone')) {
                $table->string('phone')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            // حذف الحقول فقط إذا كانت موجودة فعلياً عند عمل Rollback
            $columnsToDrop = [];
            
            if (Schema::hasColumn('agencies', 'cr_expiry_date')) {
                $columnsToDrop[] = 'cr_expiry_date';
            }
            
            if (Schema::hasColumn('agencies', 'phone')) {
                $columnsToDrop[] = 'phone';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};