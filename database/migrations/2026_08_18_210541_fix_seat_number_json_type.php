<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. جلب اسم الفهرس المربوط بعمود seat_number ديناميكياً
        $indexes = DB::select("SHOW INDEX FROM booking_seats WHERE Column_name = 'seat_number'");

        // 2. حذف كل الفهارس المرتبطة بهذا العمود مهما كان اسمها
        foreach ($indexes as $index) {
            $indexName = $index->Key_name;
            // تجنب حذف المفتاح الرئيسي إذا كان موجوداً
            if ($indexName !== 'PRIMARY') {
                DB::statement("ALTER TABLE booking_seats DROP INDEX `{$indexName}`");
            }
        }

        // 3. تحويل العمود إلى نوع JSON
        DB::statement("ALTER TABLE booking_seats MODIFY `seat_number` JSON NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE booking_seats MODIFY `seat_number` VARCHAR(255) NULL");
    }
};