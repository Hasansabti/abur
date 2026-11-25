<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; direction: rtl; color: #111; font-size: 12px; }
        h1 { font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: right; }
        th { background: #f3f4f6; }
        .muted { color: #666; }
        .totals { margin-top: 16px; width: 280px; margin-right: auto; }
    </style>
</head>
<body>
    @php $snap = $invoice->snapshot ?? []; @endphp
    <h1>فاتورة {{ $invoice->invoice_number }}</h1>
    <p class="muted">تاريخ الإصدار: {{ $invoice->issued_at?->format('Y-m-d H:i') }}</p>
    <p>الوكالة: {{ $snap['agency']['name'] ?? '' }}</p>
    <p>العميل: {{ $snap['customer']['name'] ?? '' }} — {{ $snap['customer']['phone'] ?? '' }}</p>
    <p>الحجز: #{{ $invoice->booking_id }} — الرحلة: {{ $snap['trip']['title'] ?? '' }}</p>

    <table>
        <thead>
            <tr>
                <th>البند</th>
                <th>الكمية</th>
                <th>سعر الوحدة</th>
                <th>الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->label }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>المجموع قبل الخصم</td><td>{{ number_format($invoice->subtotal, 2) }}</td></tr>
        <tr><td>الخصومات</td><td>{{ number_format($invoice->discount_total, 2) }}</td></tr>
        <tr><td>الضرائب</td><td>{{ number_format($invoice->tax_total, 2) }}</td></tr>
        <tr><td><strong>الصافي</strong></td><td><strong>{{ number_format($invoice->grand_total, 2) }}</strong></td></tr>
        <tr><td>المدفوع</td><td>{{ number_format($invoice->paid_amount, 2) }}</td></tr>
        <tr><td>المتبقي</td><td>{{ number_format($invoice->remaining_amount, 2) }}</td></tr>
    </table>
</body>
</html>
