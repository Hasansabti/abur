<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            // ربط التذكرة بالمستخدم (يفترض أن يكون مسجلاً)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('subject'); // عنوان التذكرة
            $table->string('category'); // قسم المساعدة (general, booking, technical, complaint)
            $table->text('message'); // تفاصيل المشكلة
            
            // حالة التذكرة (مفتوحة، قيد المعالجة، تم الحل، مغلقة)
            $table->string('status')->default('open'); 
            
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};