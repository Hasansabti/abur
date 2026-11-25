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
        Schema::create('agency_ratings', function (Blueprint $table) {
            $table->id();

            // العلاقات
            $table->foreignId('agency_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // ربط التقييم برحلة (اختياري)
            $table->foreignId('trip_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // التقييمات (من 1 إلى 5)
            $table->unsignedTinyInteger('accommodation_rating'); // السكن
            $table->unsignedTinyInteger('meals_rating');         // الوجبات
            $table->unsignedTinyInteger('staff_rating');         // الكادر
            $table->unsignedTinyInteger('organization_rating');  // التنظيم
            $table->unsignedTinyInteger('price_value_rating');   // قيمة السعر
            $table->unsignedTinyInteger('transport_rating');     // وسائل النقل
            $table->unsignedTinyInteger('services_rating');      // الخدمات

            // تعليق المستخدم
            $table->text('comment')->nullable();

            $table->timestamps();

            // منع المستخدم من تقييم نفس الرحلة أكثر من مرة
            $table->unique(['user_id', 'trip_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_ratings');
    }
};