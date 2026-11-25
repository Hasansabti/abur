<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; direction: rtl; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; }
    </style>
</head>
<body>
@include('agency.bookings._invoice_body', ['invoice' => $invoice, 'booking' => $booking])
</body>
</html>
