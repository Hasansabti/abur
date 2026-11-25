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
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'payment_method')) {
                $table->string('payment_method')->default('card')->after('trip_id');
            }
            if (!Schema::hasColumn('bookings', 'room_type')) {
                $table->string('room_type')->after('payment_method');
            }
            if (!Schema::hasColumn('bookings', 'bank_receipt')) {
                $table->string('bank_receipt')->nullable()->after('room_type');
            }
            if (!Schema::hasColumn('bookings', 'card_name')) {
                $table->string('card_name')->nullable()->after('bank_receipt');
            }
            if (!Schema::hasColumn('bookings', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->default(0.00)->after('card_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'room_type',
                'bank_receipt',
                'card_name',
                'total_amount'
            ]);
        });
    }
};