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
        Schema::table('trips', function (Blueprint $table) {
            // إضافة حقل تاريخ ووقت العودة بعد حقل المغادرة
            $table->dateTime('return_at')->nullable()->after('departure_at');
            
            // الأعمدة الجديدة الخاصة بتفاصيل الرحلة والمميزات بعد حقل السعر (price)
            $table->unsignedInteger('duration_days')->default(1)->after('price'); // مدة الرحلة بالأيام
            $table->string('vehicle_model')->nullable()->after('duration_days'); // موديل الحافلة أو الطيران
            $table->boolean('auto_split')->default(false)->after('vehicle_model'); // الفصل التلقائي (نساء / رجال)
            $table->text('features')->nullable()->after('auto_split'); // المميزات كمصفوفة
            $table->text('notes')->nullable()->after('features'); // الشروط والملاحظات الإضافية

            // 💳 العمود الجديد الخاص ببيانات البنك (سيخزن كل الحسابات المضافة ديناميكياً بصيغة JSON أو مصفوفة)
            $table->text('bank_accounts')->nullable()->after('notes');

            // أعمدة توزيع السكن والأسعار التفصيلية للغرف الأربعة
            $table->decimal('price_single', 10, 2)->nullable()->after('bank_accounts');
            $table->integer('rooms_count_1')->nullable()->after('price_single');
            
            $table->decimal('price_double', 10, 2)->nullable()->after('rooms_count_1');
            $table->integer('rooms_count_2')->nullable()->after('price_double');
            
            $table->decimal('price_triple', 10, 2)->nullable()->after('rooms_count_2');
            $table->integer('rooms_count_3')->nullable()->after('price_triple');
            
            $table->decimal('price_quad', 10, 2)->nullable()->after('rooms_count_3');
            $table->integer('rooms_count_4')->nullable()->after('price_quad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn([
                'return_at',
                'duration_days',
                'vehicle_model',
                'auto_split',
                'features',
                'notes',
                'bank_accounts',
                'price_single', 'rooms_count_1',
                'price_double', 'rooms_count_2',
                'price_triple', 'rooms_count_3',
                'price_quad', 'rooms_count_4'
            ]);
        });
    }
};