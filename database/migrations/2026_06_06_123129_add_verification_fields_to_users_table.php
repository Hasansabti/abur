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
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
            $table->string('id_image')->nullable()->after('avatar');
            
            $table->boolean('is_verified')->default(false)->after('id_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 🟢 هنا عند عمل php artisan migrate:rollback سيتم حذف الأعمدة المضافة فقط دون حذف الجدول بالكامل
            $table->dropColumn(['avatar', 'id_image', 'is_verified']);
        });
    }
};