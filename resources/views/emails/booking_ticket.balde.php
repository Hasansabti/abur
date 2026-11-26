<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تأكيد الحجز</title>
</head>
<body style="font-family: Cairo, Arial, sans-serif; background:#f8fafc; padding:20px; direction:rtl;">
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
                    {{ $booking->trip->departure_at ? \Carbon\Carbon::parse($booking->trip->departure_at)->format('Y-m-d h:i A') : '---' }}
                </td>
            </tr>
            <tr>
                <td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">عدد المقاعد</td>
                <td style="padding:8px;border-bottom:1px solid #eee;">{{ $booking->bookingSeats->count() }}</td>
            </tr>
            <tr>
                <td style="padding:8px;border-bottom:1px solid #eee;font-weight:bold;">الإجمالي</td>
                <td style="padding:8px;border-bottom:1px solid #eee;">{{ number_format($booking->total_amount, 2) }} ر.س</td>
            </tr>
            <tr>
                <td style="padding:8px;font-weight:bold;">حالة الدفع</td>
                <td style="padding:8px;">{{ $booking->payment_status }}</td>
            </tr>
        </table>

        <p style="margin-top:20px;color:#64748b;font-size:12px;">
            شكراً لاختياركم منصة عبور 🚀
        </p>
    </div>
</body>
</html>