<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            // إضافة حقل الاعتماد الأمني كقيمة بولينية (صحيح/خطأ) وتكون افتراضياً غير معتمدة (false)
            $table->boolean('is_security_approved')->default(false)->after('auto_split');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('is_security_approved');
        });
    }
};