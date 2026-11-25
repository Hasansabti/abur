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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('phone');
            $table->string('national_id')->unique();
            $table->string('license_number');
            $table->date('license_expiry_date');
            $table->string('status')->default('available'); // available, on_trip, inactive
            $table->timestamps();
        });
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['bus', 'flight']); // دعم الحافلات والطائرات لمنصة عبور
            $table->string('vehicle_model');
            $table->string('plate_number')->unique(); // رقم اللوحة أو رمز الطائرة
            $table->integer('total_seats');
            $table->date('check_date')->nullable();
            $table->string('status')->default('active'); // active, maintenance
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('drivers');
    }
};
