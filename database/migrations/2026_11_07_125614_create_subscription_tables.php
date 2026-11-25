<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('yearly_price', 10, 2)->nullable();      // null = شهري × 12

            // الحدود (null = غير محدود)
            $table->unsignedInteger('max_active_trips')->nullable();
            $table->unsignedInteger('max_vehicles')->nullable();
            $table->unsignedInteger('max_drivers')->nullable();
            $table->unsignedInteger('max_employees')->nullable();

            // الميزات
            $table->boolean('allows_overflow_bus')->default(false);

            // إعدادات تُطبّق على الوكالة عند التفعيل
            $table->decimal('commission_per_passenger', 8, 2)->default(0);
            $table->decimal('min_booking_commission', 8, 2)->nullable();
            $table->decimal('max_booking_commission', 8, 2)->nullable();
            $table->unsignedInteger('whatsapp_monthly_limit')->default(0);
            $table->decimal('whatsapp_extra_rate', 8, 2)->default(0);

            $table->boolean('is_default')->default(false);           // الباقة المجانية الافتراضية
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('agency_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('subscription_plans');
            $table->string('status')->default('pending');            // pending | active | expired | cancelled | rejected
            $table->string('billing_cycle')->default('monthly');     // monthly | yearly
            $table->decimal('price_paid', 10, 2)->default(0);
            $table->string('payment_method')->nullable();            // bank_transfer | manual
            $table->string('payment_reference')->nullable();
            $table->string('payment_receipt')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
