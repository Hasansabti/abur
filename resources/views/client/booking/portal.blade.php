<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حجز BK-{{ $booking->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 font-sans">
    <div class="max-w-2xl mx-auto p-4 space-y-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <h1 class="text-lg font-black">حجز BK-{{ $booking->id }}</h1>
            <p class="text-xs text-slate-500 mt-1">{{ $booking->trip?->title }} — {{ $booking->status }}</p>
        </div>
        <div class="bg-white rounded-2xl p-5 space-y-2 text-sm">
            <div>المغادرة: {{ optional($booking->trip?->departure_at)->format('Y-m-d H:i') }}</div>
            <div>من {{ $booking->trip?->from_city }} إلى {{ $booking->trip?->to_city }}</div>
        </div>
        <div class="bg-white rounded-2xl p-5">
            <h2 class="font-black text-sm mb-2">المقاعد</h2>
            @foreach ($guests as $g)
                <div class="text-xs py-2 border-b">
                    {{ $g->passenger_name }} — مقعد {{ is_array($g->seat_number) ? implode(',', $g->seat_number) : $g->seat_number }}
                    @if ($g->room?->room_number)
                        — غرفة {{ $g->room->room_number }}
                    @endif
                </div>
            @endforeach
        </div>
        <div class="bg-white rounded-2xl p-5 text-sm space-y-1">
            <div class="flex justify-between"><span>الإجمالي</span><span>{{ number_format($summary['total'], 2) }} ر.س</span></div>
            <div class="flex justify-between"><span>المدفوع</span><span>{{ number_format($summary['paid'], 2) }}</span></div>
            <div class="flex justify-between font-black"><span>المتبقي</span><span>{{ number_format($summary['remaining'], 2) }}</span></div>
        </div>
        @if ($booking->invoice)
            <div class="text-xs text-slate-500">تم إصدار فاتورة {{ $booking->invoice->invoice_number }} بتاريخ {{ optional($booking->invoice->issued_at)->format('Y-m-d') }}</div>
        @endif
    </div>
</body>
</html>
