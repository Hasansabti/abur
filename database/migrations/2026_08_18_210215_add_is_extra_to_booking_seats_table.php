<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. تعطيل الفحوصات مؤقتاً لتجنب قيود المفاتيح والفهارس المركبة
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 2. إضافة عمود مؤقت بنوع JSON
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->json('seat_number_temp')->nullable()->after('seat_number');
        });

        // 3. تحويل البيانات القديمة الموجودة في الجدول إلى مصفوفات JSON
        $seats = DB::table('booking_seats')->get();
        foreach ($seats as $seat) {
            $oldSeat = $seat->seat_number;
            
            if (!empty($oldSeat)) {
                $decoded = json_decode($oldSeat, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $jsonVal = json_encode(array_values(array_map('intval', $decoded)));
                } else {
                    $cleanNum = intval(filter_var($oldSeat, FILTER_SANITIZE_NUMBER_INT));
                    $jsonVal = json_encode([$cleanNum]);
                }
            } else {
                $jsonVal = json_encode([]);
            }

            DB::table('booking_seats')
                ->where('id', $seat->id)
                ->update(['seat_number_temp' => $jsonVal]);
        }

        // 4. حذف العمود القديم (وهذا سيزيل كافة الفهارس والقيود المربوطة به تلقائياً في MySQL)
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropColumn('seat_number');
        });

        // 5. إعادة تسمية العمود الجديد ليصلح هو seat_number الأساسي
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->renameColumn('seat_number_temp', 'seat_number');
        });

        // 6. إعادة تفعيل فحوصات القيود
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('booking_seats', function (Blueprint $table) {
            $table->string('seat_number_temp')->nullable();
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropColumn('seat_number');
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            $table->renameColumn('seat_number_temp', 'seat_number');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};