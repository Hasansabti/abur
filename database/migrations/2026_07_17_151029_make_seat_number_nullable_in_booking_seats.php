<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            // تعديل الحقل ليصبح قابلاً للقيمة null مع الحفاظ على نوعه unsignedInteger
            $table->string('seat_number', 10)->change();
        });
    }

    public function down(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->integer('seat_number')->change();
        });
    }
};
