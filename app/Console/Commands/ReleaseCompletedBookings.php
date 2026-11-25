<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\AgencyTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReleaseCompletedBookings extends Command
{
    /**
     * اسم الأمر الذي سيتم تشغيله عبر السيرفر
     */
    protected $signature = 'financial:release-funds';

    /**
     * وصف الأمر
     */
    protected $description = 'تحويل رصيد الحجوزات المكتملة من معلق (pending) إلى متاح (available) للوكالة';

    public function handle()
    {
        $this->info('جاري فحص الرحلات المكتملة للإفراج عن المستحقات المعلقة...');

        $releasedCount = 0;

        // معالجة الحجوزات على دفعات (Chunking) لتفادي استهلاك الذاكرة
        Booking::query()
            ->where('payment_status', 'paid')
            ->whereIn('status', ['confirmed', 'approved']) // التأكد من أن الحجز غير ملغى
            ->whereNull('trip_completed_at')
            ->whereHas('trip', function ($query) {
                // افتراض: الإفراج بعد وقت المغادرة بـ 6 ساعات للتأكد من اكتمال الرحلة
                // أو يمكنك استبدال departure_at بحقل arrival_at إن وجد في جدول الرحلات
                $query->where('departure_at', '<=', now()->subHours(6));
            })
            ->chunkById(100, function ($completedBookings) use (&$releasedCount) {
                foreach ($completedBookings as $booking) {
                    try {
                        DB::transaction(function () use ($booking) {
                            // 1. تحديث تاريخ توثيق اكتمال الرحلة في جدول الحجز
                            $booking->update([
                                'trip_completed_at' => now(),
                            ]);

                            // 2. تحويل حالة الحركة المالية للوكالة إلى متاح (available)
                            AgencyTransaction::where('booking_id', $booking->id)
                                ->where('type', 'booking_credit')
                                ->where('status', 'pending')
                                ->update([
                                    'status'      => 'available',
                                    'description' => "تم تحويل المبلغ إلى متاح للسحب لاكتمال الرحلة (حجز #{$booking->id})",
                                ]);
                        });

                        $releasedCount++;
                    } catch (\Exception $e) {
                        Log::error("فشل الإفراج عن المبلغ للحجز #{$booking->id}: " . $e->getMessage());
                    }
                }
            });

        if ($releasedCount === 0) {
            $this->info('لا توجد حجوزات مستحقة الإفراج حالياً.');
        } else {
            $this->info("تم الإفراج بنجاح عن أرباح {$releasedCount} حجز وأصبحت متاحة في محفظة الوكالات.");
        }

        return Command::SUCCESS;
    }
}