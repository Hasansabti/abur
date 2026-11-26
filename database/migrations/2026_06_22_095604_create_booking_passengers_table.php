<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_passengers', function (Blueprint $table) {
            $table->id();
            
            // الربط مع الحجز الرئيسي (عند حذف الحجز تحذف بيانات الركاب تلقائياً)
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            
            // بيانات المرافق الأساسية
            $table->string('name')->nullable(); // اسم المرافق (اختياري أو إجباري حسب رغبتك)
            $table->string('passenger_id_number'); // رقم الهوية أو الإقامة للمرافق
            $table->enum('gender', ['male', 'female']); // الجنس
            
            // ربط المرافق بالمقعد والغرفة داخل هذه الرحلة
            $table->integer('seat_number'); // رقم المقعد المحجوز له (مثلاً: 12)
            
            // إذا كان مسكناً في غرفة خاصة (يرتبط بجدول غرف الحجز المتاحة عندك)
            $table->foreignId('booking_room_id')->nullable()->constrained('booking_rooms')->onDelete('set null'); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_passengers');
    }
};