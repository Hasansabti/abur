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
        Schema::table('booking_seats', function (Blueprint $table) {
            // ربط المقعد الإضافي بالمقعد/المسافر الرئيسي
            $table->foreignId('parent_seat_id')->nullable()->constrained('booking_seats')->onDelete('cascade');
            $table->boolean('is_extra')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropForeign(['parent_seat_id']);
            $table->dropColumn(['parent_seat_id', 'is_extra']);
        });
    }
};
