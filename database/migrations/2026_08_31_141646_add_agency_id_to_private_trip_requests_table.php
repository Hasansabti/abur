<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_trip_requests', function (Blueprint $table) {
            // جعل الحقل nullable يسمح بإرسال الطلب لجميع الوكالات أو لوكالة محددة
            $table->foreignId('agency_id')->nullable()->after('user_id')->constrained('agencies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('private_trip_requests', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropColumn('agency_id');
        });
    }
};