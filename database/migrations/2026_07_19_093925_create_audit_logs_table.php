<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agency_id'); // معرف الوكالة
            $table->unsignedBigInteger('trip_id'); // معرف الوكالة
            $table->string('action_type'); // نوع العملية: delete, increase, decrease, update
            $table->string('category'); // التصنيف: money, bookings, trip
            $table->decimal('amount_changed', 10, 2)->default(0.00); // التغير المالي (موجب للزيادة، سالب للنقصان)
            $table->integer('seats_changed')->default(0); // التغير في الحجوزات/المقاعد
            $table->text('description'); // وصف تفصيلي للعملية (مثال: تم إلغاء المقعد رقم 5 من الرحلة X)
            $table->string('operator_name')->nullable(); // اسم الشخص الذي قام بالعملية من نظام الوكالة
            $table->timestamps();

            // روابط الأمان
            $table->foreign('agency_id')->references('id')->on('agencies')->onDelete('cascade');
            $table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade');

        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
};