<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->onDelete('cascade');
            $table->decimal('requested_amount', 10, 2); // المبلغ المطلوب من الوكالة
            $table->decimal('whatsapp_fee_deducted', 10, 2)->default(0.00); // رسوم الواتساب المخصومة
            $table->decimal('net_payout_amount', 10, 2); // الصافي المحول للبنك
            
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('bank_receipt')->nullable(); // صورة إيصال التحويل
            $table->text('admin_notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_requests');
    }
};