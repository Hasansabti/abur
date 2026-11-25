<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تأكيد الحجز</title>
</head>
<body style="font-family: Cairo, Arial, sans-serif; background:#f8fafc; padding:20px; direction:rtl;">
@php
    $roomNames = ['single'=>'فردية','double'=>'ثنائية','triple'=>'ثلاثية','quad'=>'رباعية','shared'=>'مشتركة','additional'=>'مقعد إضافي'];
    $seatText = fn($s) => is_array($s->seat_number) ? implode('، ', $s->seat_number) : (string) $s->seat_number;
    $isAccom  = fn($s) => str_starts_with(strtoupper($seatText($s)), 'H');
    $roomLabel = function ($t) use ($roomNames) {
        $t = (string) $t;
        if ($t === '') return '---';
        $parts = explode('_', $t);
        if (!isset($roomNames[$parts[0]])) return $t;
        if ($parts[0] === 'additional') return 'مقعد إضافي';
        return 'غرفة ' . $roomNames[$parts[0]] . (isset($parts[1]) ? ' #' . $parts[1] : '');
    };
    $busSeats   = $booking->bookingSeats->reject($isAccom);
    $accomSeats = $booking->bookingSeats->filter($isAccom);
    $paymentMap = ['paid'=>'مدفوع ✅','unpaid'=>'غير مدفوع','pending'=>'قيد المراجعة','failed'=>'فشل','refunded'=>'تم الاسترجاع'];
@endphp

    <div style="max-width:600px;margin:auto;background:#fff;border-radius:16px;padding:24px;border:1px solid #e2e8f0;">
        <h2 style="color:#032B53;">تم تأكيد حجزك بنجاح ✅</h2>
        <p>مرحباً <strong>{{ $booking->customer_name }}</strong>،</p>
        <p>يسعدنا إبلاغك بتفاصيل حجزك رقم <strong>#TRP-{{ $booking->id }}</strong></p>

        <table style="width:100%;border-collapse:collapse;margin-top:16px;">
            <tr>
                <td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">الرحلة</td>
                <td style="padding:8px;border-bottom:1px solid #eee;">{{ $booking->trip->title ?? '---' }}</td>
            </tr>
            <tr>
                <td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">من</td>
                <td style="padding:8px;border-bottom:1px solid #eee;">{{ $booking->trip->from_city ?? '---' }}</td>
            </tr>
            <tr>
                <td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">إلى</td>
                <td style="padding:8px;border-bottom:1px solid #eee;">{{ $booking->trip->to_city ?? '---' }}</td>
            </tr>
            <tr>
                <td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">تاريخ المغادرة</td>
                <td style="padding:8px;border-bottom:1px solid #eee;">
                    {{ $booking->trip?->departure_at ? \Carbon\Carbon::parse($booking->trip->departure_at)->format('Y-m-d h:i A') : '---' }}
                </td>
            </tr>
            <tr>
                <td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">مقاعد الحافلة</td>
                <td style="padding:8px;border-bottom:1px solid #eee;">{{ $busSeats->count() }}</td>
            </tr>
            @if ($accomSeats->count() > 0)
            <tr>
                <td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">مسافرو السكن فقط</td>
                <td style="padding:8px;border-bottom:1px solid #eee;">{{ $accomSeats->count() }}</td>
            </tr>
            @endif
            <tr>
                <td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">الإجمالي</td>
                <td style="padding:8px;border-bottom:1px solid #eee;">{{ number_format($booking->total_amount, 2) }} ر.س</td>
            </tr>
            <tr>
                <td style="padding:8px;font-weight:bold;">حالة الدفع</td>
                <td style="padding:8px;">{{ $paymentMap[$booking->payment_status] ?? $booking->payment_status }}</td>
            </tr>
        </table>

        <h3 style="color:#032B53;margin-top:24px;">تفاصيل المسافرين</h3>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <tr style="background:#f1f5f9;">
                <th style="padding:8px;text-align:right;">الاسم</th>
                <th style="padding:8px;text-align:right;">المقعد</th>
                <th style="padding:8px;text-align:right;">السكن</th>
            </tr>
            @foreach ($booking->bookingSeats as $seat)
                <tr>
                    <td style="padding:8px;border-bottom:1px solid #eee;">{{ $seat->passenger_name }}</td>
                    <td style="padding:8px;border-bottom:1px solid #eee;">
                        {{ $isAccom($seat) ? 'سكن فقط 🏨' : $seatText($seat) }}
                    </td>
                    <td style="padding:8px;border-bottom:1px solid #eee;">{{ $roomLabel($seat->room_type) }}</td>
                </tr>
            @endforeach
        </table>

        <p style="margin-top:20px;color:#64748b;font-size:12px;">
            شكراً لاختياركم منصة عبور 🚀
        </p>
    </div>
</body>
</html>