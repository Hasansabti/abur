<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;

class CancelExpiredBookings extends Command
{
    protected $signature = 'bookings:cancel-expired';
    protected $description = 'إلغاء الحجوزات المعلقة التي انتهت مهلة الدفع الخاصة بها';

    public function handle()
    {
        // البحث عن الحجوزات المعلقة وغير المدفوعة التي انتهت مهلتها
        $expiredBookings = Booking::where('status', 'pending')
            ->where('payment_status', 'unpaid')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;

        foreach ($expiredBookings as $booking) {
            // 1. تغيير حالة الحجز إلى ملغى
            $booking->update([
                'status' => 'cancelled',
            ]);
            $count++;
        }

        $this->info("تم إلغاء {$count} حجز انتهت مهلته بنجاح.");
    }
}