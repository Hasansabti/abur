@extends('layouts.client')

@section('content')
<div class="max-w-3xl mx-auto p-6" dir="rtl">
    <div class="bg-white rounded-3xl border p-6 space-y-4">
        <h1 class="text-xl font-black">تأكيد الحجز #{{ $booking->id }}</h1>
        <p class="text-sm text-slate-500">{{ $booking->trip?->title }}</p>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div>الحالة: <strong>{{ $booking->status }}</strong></div>
            <div>الدفع: <strong>{{ $booking->payment_status }}</strong></div>
            <div>الإجمالي: <strong>{{ number_format($booking->total_amount, 2) }} ر.س</strong></div>
            <div>المدفوع: <strong>{{ number_format($booking->cash_paid, 2) }} ر.س</strong></div>
            <div>المتبقي: <strong>{{ number_format(max(0, $booking->total_amount - $booking->cash_paid), 2) }} ر.س</strong></div>
        </div>
        <h2 class="font-black mt-4">المقاعد</h2>
        <ul class="text-sm space-y-2">
            @foreach ($guests as $seat)
                <li class="p-3 bg-slate-50 rounded-xl">
                    {{ $seat->passenger_name }}
                    — مقعد {{ is_array($seat->seat_number) ? implode(',', $seat->seat_number) : $seat->seat_number }}
                    — {{ $seat->room_type }}
                </li>
            @endforeach
        </ul>
        @if ($booking->invoices->isNotEmpty())
            <p class="text-xs text-slate-500">رقم الفاتورة: {{ $booking->invoices->last()->invoice_number }}</p>
        @endif
    </div>
</div>
@endsection
