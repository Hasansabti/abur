<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            // معرّف العميل المخصصة له الرحلة (يكون NULL للرحلات العامة)
            $table->foreignId('user_id')->nullable()->after('agency_id')->constrained()->onDelete('cascade');
            
            
            // ربط الرحلة بالطلب الأصلي (اختياري لمعرفة أصل الطلب)
            $table->foreignId('private_trip_request_id')->nullable()->after('user_id')->constrained('private_trip_requests')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['private_trip_request_id']);
            $table->dropColumn(['user_id', 'private_trip_request_id']);
        });
    }
};