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
            $table->string('commercial_id');
            $table->string('commercial_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            // تراجع عن إضافة الأعمدة بحذفها من الجدول تلقائياً ⬇️
            $table->dropColumn(['commercial_id', 'commercial_name']);
        });
    }
};