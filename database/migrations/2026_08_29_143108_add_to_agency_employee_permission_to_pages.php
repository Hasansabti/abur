<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إضافة حقل الصلاحيات.
     */
    public function up(): void
    {
        Schema::table('agency_employees', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('role');
        });
    }

    /**
     * حذف حقل الصلاحيات عند التراجع.
     */
    public function down(): void
    {
        Schema::table('agency_employees', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
};