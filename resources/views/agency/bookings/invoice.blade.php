@extends('layouts.agency')

@section('content')
    @php
        $snap = $invoice->snapshot ?? [];
        $agency = $snap['agency'] ?? [];
        $customer = $snap['customer'] ?? [];
        $trip = $snap['trip'] ?? [];
        $items = $invoice->items ?? collect($snap['items'] ?? []);
    @endphp
    <div class="max-w-4xl mx-auto p-6" dir="rtl">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-xl font-black">فاتورة {{ $invoice->invoice_number }}</h1>
            <div class="flex gap-2">
                <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white text-xs font-black rounded-xl">🖨️ طباعة</button>
                <a href="{{ route('agency.bookings.invoice.pdf', $booking) }}" class="px-4 py-2 bg-slate-800 text-white text-xs font-black rounded-xl">تنزيل PDF</a>
            </div>
        </div>
        @include('agency.bookings._invoice_body', compact('invoice', 'booking', 'snap', 'agency', 'customer', 'trip', 'items'))
    </div>
@endsection
