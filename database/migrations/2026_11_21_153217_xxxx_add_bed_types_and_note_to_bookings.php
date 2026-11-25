<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_add_bed_types_and_note_to_bookings.php
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->json('room_bed_types')->nullable();
            $table->text('agency_note')->nullable();
            $table->string('agency_note_status', 20)->nullable(); // pending | answered | dismissed
        });

        Schema::create('booking_extra_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('agency_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('description', 255);
            $table->string('status', 20)->default('pending'); // pending | paid | cancelled
            $table->string('token', 64)->unique();
            $table->string('moyasar_invoice_id')->nullable();
            $table->string('moyasar_payment_id')->nullable();
            $table->text('payment_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            //
        });
    }
};
