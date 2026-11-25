@php
    $typeNames = ['single' => 'فردية', 'double' => 'ثنائية', 'triple' => 'ثلاثية', 'quad' => 'رباعية'];
@endphp

@if (!empty($booking->room_bed_types))
    <div class="mb-3">
        <h4 class="text-xs font-black text-slate-700 mb-1">🛌 أسرّة الغرف المطلوبة</h4>
        <ul class="text-xs space-y-1">
            @foreach ($booking->room_bed_types as $uid => $bed)
                @php [$type, $num] = array_pad(explode('_', $uid), 2, ''); @endphp
                <li class="bg-slate-50 border border-slate-200 rounded-lg px-2 py-1">
                    غرفة {{ $typeNames[$type] ?? $type }} #{{ $num }}:
                    <strong>{{ config("bed_types.$type.$bed", $bed) }}</strong>
                </li>
            @endforeach
        </ul>
    </div>
@endif

@if ($booking->agency_note)
    <div
        class="mb-3 p-3 rounded-xl border {{ $booking->agency_note_status === 'pending' ? 'bg-amber-50 border-amber-300' : 'bg-slate-50 border-slate-200' }}">
        <div class="flex justify-between items-center mb-1">
            <h4 class="text-xs font-black">📝 ملاحظة العميل</h4>
            @if ($booking->agency_note_status === 'pending')
                <span class="text-[10px] font-black bg-amber-200 text-amber-900 px-2 py-0.5 rounded-md">بانتظار
                    المراجعة</span>
            @elseif ($booking->agency_note_status === 'answered')
                <span class="text-[10px] font-black bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-md">تم
                    الرد</span>
            @endif
        </div>
        <p class="text-xs font-bold text-slate-700 whitespace-pre-line">{{ $booking->agency_note }}</p>

        @if ($booking->agency_note_status === 'pending')
            <form method="POST" action="{{ route('agency.bookings.note_handled', $booking->id) }}" class="mt-2">
                @csrf
                <button class="text-[10px] font-bold text-slate-500 underline">إغلاق الملاحظة (لا تحتاج مبلغاً
                    إضافياً)</button>
            </form>
        @endif
    </div>
@endif

{{-- طلبات الدفع الإضافية --}}
@foreach ($booking->extraCharges as $charge)
    <div class="mb-2 p-2.5 rounded-xl border border-slate-200 text-xs flex justify-between items-center gap-2">
        <div>
            <strong>{{ number_format($charge->amount, 2) }} ر.س</strong> — {{ $charge->description }}
            <span
                class="mr-1 px-1.5 py-0.5 rounded text-[10px] font-black
                {{ $charge->status === 'paid' ? 'bg-emerald-100 text-emerald-800'
    : ($charge->status === 'pending' ? 'bg-amber-100 text-amber-800'
    : (in_array($charge->status, ['refunded']) ? 'bg-indigo-100 text-indigo-800'
    : ($charge->status === 'refund_failed' ? 'bg-red-100 text-red-700' : 'bg-slate-200 text-slate-600'))) }}">
                {{ ['paid' => 'مدفوع', 'pending' => 'بانتظار الدفع', 'cancelled' => 'ملغى', 'refunded' => 'مسترد', 'refund_failed' => 'فشل الاسترداد'][$charge->status] ?? $charge->status }}
            </span>
        </div>
        @if ($charge->status === 'pending')
            <form method="POST" action="{{ route('agency.extra_charge.cancel', $charge->id) }}">
                @csrf
                <button class="text-[10px] font-bold text-red-600">إلغاء</button>
            </form>
        @endif
    </div>
@endforeach

@if ($booking->payment_status === 'paid' && !in_array($booking->status, ['cancelled', 'requestC']))
    <form method="POST" action="{{ route('agency.bookings.extra_charge.store', $booking->id) }}"
        class="mt-3 p-3 bg-indigo-50/60 border border-indigo-200 rounded-xl space-y-2">
        @csrf
        <h4 class="text-xs font-black text-indigo-900">💳 إنشاء فاتورة إضافية وإرسال رابط دفع</h4>
        <input type="number" name="amount" step="0.01" min="1" required placeholder="المبلغ (ر.س)"
            class="w-full border rounded-lg px-3 py-2 text-xs font-bold">
        <input type="text" name="description" maxlength="255" required
            placeholder="السبب: مثال: سرير إضافي في الغرفة الثلاثية"
            class="w-full border rounded-lg px-3 py-2 text-xs font-bold">
        <button class="w-full bg-indigo-600 text-white rounded-lg py-2 text-xs font-black">إرسال رابط الدفع
            للمسافر</button>
    </form>
@endif
