<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('agency_subscriptions', function (Blueprint $table) {
        $table->string('moyasar_payment_id')->nullable()->unique()->after('payment_reference');
        $table->timestamp('paid_at')->nullable()->after('moyasar_payment_id');
    });
}

public function down(): void
{
    Schema::table('agency_subscriptions', function (Blueprint $table) {
        $table->dropColumn(['moyasar_payment_id', 'paid_at']);
    });
}
};
