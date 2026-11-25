<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_employees', function (Blueprint $table) {
            $table->id();
            
            // ربط الموظف بالوكالة (إذا حذفت الوكالة تحذف حسابات موظفيها تلقائياً)
            $table->foreignId('agency_id')->constrained('agencies')->onDelete('cascade');
            
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->string('role')->default('staff'); // أدوار مثل: admin, accountant, staff
            $table->boolean('is_active')->default(true); // لتمكين/تعطيل الموظف
            
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_employees');
    }
};