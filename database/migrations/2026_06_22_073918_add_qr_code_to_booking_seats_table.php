<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_xxxxxx_add_qr_code_to_booking_seats_table.php

    public function up(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            // أضفنا الحقل و جعلناه nullable تفادياً للمشاكل مع الحجوزات القديمة
            $table->string('qr_code_path')->nullable()->after('passenger_national_id');
        });
    }

    public function down(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropColumn('qr_code_path');
        });
    }
};
