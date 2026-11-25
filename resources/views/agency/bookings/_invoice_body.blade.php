@php
    $snap = $snap ?? ($invoice->snapshot ?? []);
    $agency = $agency ?? ($snap['agency'] ?? []);
    $customer = $customer ?? ($snap['customer'] ?? []);
    $trip = $trip ?? ($snap['trip'] ?? []);
    $items = $items ?? ($invoice->items ?? collect());
@endphp
<div class="bg-white border rounded-2xl p-6 text-sm" dir="rtl" id="invoice-print">
    <div class="flex justify-between border-b pb-4 mb-4">
        <div>
            <div class="font-black text-lg">{{ $agency['name'] ?? $booking->trip?->agency?->name }}</div>
            <div class="text-slate-500 text-xs">{{ $agency['phone'] ?? '' }}</div>
        </div>
        <div class="text-left">
            <div class="font-black">{{ $invoice->invoice_number }}</div>
            <div class="text-xs text-slate-500">تاريخ الإصدار: {{ optional($invoice->issued_at)->format('Y-m-d H:i') }}</div>
            <div class="text-xs">حجز {{ 'BK-' . ($snap['booking_id'] ?? $booking->id) }}</div>
        </div>
    </div>
    <div class="grid grid-cols-2 gap-4 mb-4 text-xs">
        <div>
            <div class="font-black mb-1">العميل</div>
            <div>{{ $customer['name'] ?? $booking->customer_name }}</div>
            <div>{{ $customer['phone'] ?? $booking->customer_phone }}</div>
        </div>
        <div>
            <div class="font-black mb-1">الرحلة</div>
            <div>{{ $trip['title'] ?? $booking->trip?->title }}</div>
            <div>{{ ($trip['from'] ?? '') }} → {{ ($trip['to'] ?? '') }}</div>
        </div>
    </div>
    <table class="w-full text-xs border mb-4">
        <thead class="bg-slate-50">
            <tr>
                <th class="p-2 text-right">البند</th>
                <th class="p-2">الكمية</th>
                <th class="p-2">سعر الوحدة</th>
                <th class="p-2">الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                @php
                    $label = is_array($item) ? ($item['label'] ?? '') : $item->label;
                    $qty = is_array($item) ? ($item['quantity'] ?? 1) : $item->quantity;
                    $unit = is_array($item) ? ($item['unit_price'] ?? 0) : $item->unit_price;
                    $total = is_array($item) ? ($item['total'] ?? 0) : $item->total;
                    $meta = is_array($item) ? ($item['meta'] ?? []) : ($item->meta ?? []);
                @endphp
                <tr class="border-t">
                    <td class="p-2">
                        {{ $label }}
                        @if (!empty($meta['bed_type']))
                            <span class="block text-[10px] text-slate-400">السرير: {{ \App\Support\PassengerSeat::bedLabel($meta['bed_type']) }}</span>
                        @endif
                    </td>
                    <td class="p-2 text-center">{{ $qty }}</td>
                    <td class="p-2 text-center">{{ number_format((float) $unit, 2) }}</td>
                    <td class="p-2 text-center font-bold">{{ number_format((float) $total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="space-y-1 text-xs max-w-xs mr-auto">
        <div class="flex justify-between"><span>الإجمالي قبل الخصم</span><span>{{ number_format((float) $invoice->subtotal, 2) }}</span></div>
        <div class="flex justify-between"><span>الخصومات {{ $snap['discount_code'] ?? '' }}</span><span>{{ number_format((float) $invoice->discount_total, 2) }}</span></div>
        <div class="flex justify-between"><span>الضرائب</span><span>{{ number_format((float) $invoice->tax_total, 2) }}</span></div>
        <div class="flex justify-between font-black text-sm border-t pt-1"><span>صافي الفاتورة</span><span>{{ number_format((float) $invoice->grand_total, 2) }} ر.س</span></div>
        <div class="flex justify-between"><span>المدفوع</span><span>{{ number_format((float) $invoice->paid_amount, 2) }}</span></div>
        <div class="flex justify-between"><span>المتبقي</span><span>{{ number_format((float) $invoice->remaining_amount, 2) }}</span></div>
    </div>
    @if (!empty($snap['payments']))
        <div class="mt-4 text-xs">
            <div class="font-black mb-1">طرق الدفع</div>
            @foreach ($snap['payments'] as $p)
                <div>{{ $p['method'] ?? '' }} — {{ number_format((float) ($p['amount'] ?? 0), 2) }} {{ !empty($p['reference']) ? ' (' . $p['reference'] . ')' : '' }}</div>
            @endforeach
        </div>
    @endif
</div>
