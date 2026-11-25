<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_trip_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // بيانات المسار والتاريخ
            $table->string('from_city');
            $table->string('to_city');
            $table->date('departure_date');
            $table->date('return_date')->nullable();
            $table->enum('trip_type', ['one_way', 'round_trip'])->default('one_way');

            // الخصوصية ونوع المركبة
            $table->string('transport_type')->default('bus');
            $table->unsignedInteger('passengers_count')->default(1);

            // بيانات التواصل والملاحظات
            $table->string('contact_name');
            $table->string('contact_phone');
            $table->text('notes')->nullable();

            // حالة الطلب
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_trip_requests');
    }
};
