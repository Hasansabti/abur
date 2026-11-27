<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'booked_by_agency')) {
                $table->boolean('booked_by_agency')->default(false);
            }
            if (!Schema::hasColumn('bookings', 'is_locked')) {
                $table->boolean('is_locked')->default(false);
            }
            if (!Schema::hasColumn('bookings', 'room_quantities')) {
                $table->json('room_quantities')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'room_assignments')) {
                $table->json('room_assignments')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'passenger_meals')) {
                $table->json('passenger_meals')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'extra_services')) {
                $table->json('extra_services')->nullable();
            }
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_seats', 'shared_room_type')) {
                $table->string('shared_room_type', 30)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            foreach (['booked_by_agency', 'room_quantities', 'room_assignments', 'passenger_meals', 'extra_services'] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            if (Schema::hasColumn('booking_seats', 'shared_room_type')) {
                $table->dropColumn('shared_room_type');
            }
        });
    }
};
