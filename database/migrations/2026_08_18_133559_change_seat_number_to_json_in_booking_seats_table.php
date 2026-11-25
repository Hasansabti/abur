<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. إنشاء فهرس مستقل لـ trip_id لفك الارتباط مع القيد الفريد
        try {
            DB::statement('ALTER TABLE `booking_seats` ADD INDEX `booking_seats_trip_id_index` (`trip_id`);');
        } catch (\Throwable $e) {}

        // 2. حذف القيد الفريد المركب الآن بعد أن أصبح لـ trip_id فهرس مستقل
        try {
            DB::statement('ALTER TABLE `booking_seats` DROP INDEX `booking_seats_trip_id_seat_number_unique`;');
        } catch (\Throwable $e) {}

        // 3. حذف أي فهارس أخرى مرتبطة بـ seat_number
        $indexes = DB::select("
            SELECT DISTINCT INDEX_NAME 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = 'booking_seats' 
              AND COLUMN_NAME = 'seat_number'
        ");

        foreach ($indexes as $index) {
            if ($index->INDEX_NAME !== 'PRIMARY') {
                try {
                    DB::statement("ALTER TABLE `booking_seats` DROP INDEX `{$index->INDEX_NAME}`");
                } catch (\Throwable $e) {}
            }
        }

        // 4. إزالة العمود المؤقت إن وجد من المحاولات السابقة
        if (Schema::hasColumn('booking_seats', 'seat_number_temp')) {
            Schema::table('booking_seats', function (Blueprint $table) {
                $table->dropColumn('seat_number_temp');
            });
        }

        // 5. إنشائه مجدداً ونقل البيانات كـ JSON
        if (Schema::hasColumn('booking_seats', 'seat_number')) {
            Schema::table('booking_seats', function (Blueprint $table) {
                $table->json('seat_number_temp')->nullable()->after('seat_number');
            });

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

            // 6. حذف العمود القديم
            Schema::table('booking_seats', function (Blueprint $table) {
                $table->dropColumn('seat_number');
            });

            // 7. إعادة تسمية العمود الجديد إلى seat_number
            Schema::table('booking_seats', function (Blueprint $table) {
                $table->renameColumn('seat_number_temp', 'seat_number');
            });
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        if (Schema::hasColumn('booking_seats', 'seat_number')) {
            Schema::table('booking_seats', function (Blueprint $table) {
                $table->string('seat_number_temp')->nullable();
            });

            Schema::table('booking_seats', function (Blueprint $table) {
                $table->dropColumn('seat_number');
            });

            Schema::table('booking_seats', function (Blueprint $table) {
                $table->renameColumn('seat_number_temp', 'seat_number');
            });
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};