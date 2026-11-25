<!DOCTYPE html>
<html dir="rtl" lang="ar">
    <head>


    <link rel="icon" type="image/jpeg" href="{{ asset('images/DF.jpeg') }}">
    </head>
<body style="font-family: Tahoma, Arial, sans-serif; background:#f1f5f9; padding:20px;">
    <div style="max-width:520px; margin:auto; background:#fff; border-radius:12px; padding:24px; text-align:right;">
        <h2 style="margin-top:0; color:#0f172a;">طلب دفع إضافي</h2>

        <p>مرحباً {{ $booking->customer_name }}،</p>

        <p>طلبت منك الوكالة دفع مبلغ إضافي على حجزك رقم <strong>#BK-{{ $booking->id }}</strong>.</p>

        <div style="background:#fffbeb; border:1px solid #fcd34d; border-radius:10px; padding:14px; margin:16px 0;">
            <p style="margin:0 0 6px;"><strong>المبلغ:</strong> {{ number_format($charge->amount, 2) }} ر.س</p>
            <p style="margin:0;"><strong>السبب:</strong> {{ $charge->description }}</p>
        </div>

        <p style="text-align:center; margin:24px 0;">
            <a href="{{ $payUrl }}"
               style="background:#d97706; color:#fff; text-decoration:none; padding:12px 28px; border-radius:10px; font-weight:bold; display:inline-block;">
                ادفع الآن
            </a>
        </p>

        <p style="font-size:12px; color:#64748b;">
            إذا لم يعمل الزر، انسخ الرابط التالي في المتصفح:<br>
            <span dir="ltr" style="word-break:break-all;">{{ $payUrl }}</span>
        </p>
    </div>
</body>
</html>