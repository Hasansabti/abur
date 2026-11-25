<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('parent_trip_id')->nullable()->constrained('trips')->nullOnDelete();
            $table->boolean('overflow_enabled')->default(false);      // على الرحلة الاساسية: فيه باص ثاني؟
            $table->boolean('is_overflow_standby')->default(false);   // على الرحلة الثانية: جاهزة لكن غير مفعّلة
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_trip_id');
            $table->dropColumn(['overflow_enabled', 'is_overflow_standby']);
        });
    }
};
