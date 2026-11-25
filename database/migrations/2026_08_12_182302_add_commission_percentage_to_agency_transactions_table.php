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
        // 1. إضافة العمود لجدول الحركات
        Schema::table('agency_transactions', function (Blueprint $table) {
            $table->decimal('commission_percentage', 5, 2)->nullable()->after('amount');
        });

        // 2. تحديث الحركات القديمة المضافة سابقاً وتعبئتها بنسبة الوكالة الحالية
        DB::statement("
            UPDATE agency_transactions 
            JOIN agencies ON agency_transactions.agency_id = agencies.id 
            SET agency_transactions.commission_percentage = agencies.commission_percentage 
            WHERE agency_transactions.commission_percentage IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agency_transactions', function (Blueprint $table) {
            $table->dropColumn('commission_percentage');
        });
    }
};