<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            
            // نوع الحركة
            $table->enum('type', [
                'booking_credit',     // إيداع إيراد حجز
                'payout',             // سحب أرباح للبنك
                'whatsapp_fee',       // خصم تجاوز الاستخدام العادل للواتساب
                'refund',             // استرداد حجز
                'adjustment'          // تعديل يدوي من الإدارة
            ]);
            
            $table->decimal('amount', 10, 2); // موجب للإيداع، سالب للخصم/السحب
            
            // حالة الرصيد: pending = معلق لحين اكتمال الرحلة، available = متاح للسحب، cancelled = ملغى
            $table->enum('status', ['pending', 'available', 'completed', 'cancelled'])->default('pending');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_transactions');
    }
};