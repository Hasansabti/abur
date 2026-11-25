<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            
            // تحديد رتبة الإدارة (الأدوار) مع قيمة افتراضية للدعم الفني
            // الخيارات المتاحة: super_admin (المدير العام)، partner (الشريك)، support (الدعم الفني)
            $table->enum('role', ['super_admin', 'partner', 'support'])->default('support');
            
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};