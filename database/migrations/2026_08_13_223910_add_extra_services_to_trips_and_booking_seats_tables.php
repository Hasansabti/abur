<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->json('extra_services')->nullable()->after('meal_options');
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            $table->json('extra_services')->nullable()->after('meal_options');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('extra_services');
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropColumn('extra_services');
        });
    }
};