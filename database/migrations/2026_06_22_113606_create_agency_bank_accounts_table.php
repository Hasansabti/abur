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
        Schema::create('agency_bank_accounts', function (Blueprint $table) {
            $table->id();
            
            // 🔗 ربط الحساب بالوكالة: إذا حُذفت الوكالة يتم حذف حساباتها البنكية تلقائياً
            $table->foreignId('agency_id')->constrained('agencies')->onDelete('cascade');
            
            // 📝 البيانات الأساسية للحساب البنكي
            $table->string('bank_name');                  // اسم البنك (مثال: بنك الراجحي، بنك الرياض)
            $table->string('account_number')->nullable(); // رقم الحساب (nullable في حال الاكتفاء بالآيبان)
            $table->string('iban', 35);                   // رقم الآيبان (IBAN) مع تحديد الحد الأقصى للمدخلات
            $table->string('qr_code')->nullable();        // مسار ملف صورة الـ QR كود المرفوعة
            
            // ⚙️ حالة الحساب (مفعل لعرضه في صفحة إنشاء الرحلة أو معطل لإخفائه مؤقتاً)
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_bank_accounts');
    }
};