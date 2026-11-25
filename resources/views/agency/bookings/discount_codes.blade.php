@extends('layouts.agency')

@section('content')
    <div class="p-6 space-y-4" dir="rtl">
        <h1 class="text-xl font-black">أكواد الخصم</h1>
        @if (session('success'))
            <div class="bg-emerald-50 text-emerald-800 p-3 rounded-xl text-xs font-bold">{{ session('success') }}</div>
        @endif
        <form method="POST" action="{{ route('agency.discount-codes.store') }}" class="bg-white p-4 rounded-2xl grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            @csrf
            <input name="code" required placeholder="الكود" class="border rounded-xl px-3 py-2">
            <select name="type" class="border rounded-xl px-3 py-2">
                <option value="fixed">مبلغ ثابت</option>
                <option value="percent">نسبة مئوية</option>
            </select>
            <input name="value" type="number" step="0.01" required placeholder="القيمة" class="border rounded-xl px-3 py-2">
            <input name="max_discount" type="number" step="0.01" placeholder="الحد الأعلى" class="border rounded-xl px-3 py-2">
            <input name="min_booking_amount" type="number" step="0.01" placeholder="الحد الأدنى للحجز" class="border rounded-xl px-3 py-2">
            <input name="starts_at" type="datetime-local" class="border rounded-xl px-3 py-2">
            <input name="ends_at" type="datetime-local" class="border rounded-xl px-3 py-2">
            <input name="max_uses" type="number" placeholder="أقصى استخدام" class="border rounded-xl px-3 py-2">
            <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" checked> مفعّل</label>
            <button class="bg-indigo-600 text-white rounded-xl font-black py-2">حفظ الكود</button>
        </form>
        <div class="bg-white rounded-2xl overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-50"><tr>
                    <th class="p-3">الكود</th><th>النوع</th><th>القيمة</th><th>الصلاحية</th><th>الحالة</th>
                </tr></thead>
                <tbody>
                @foreach ($codes as $code)
                    <tr class="border-t">
                        <td class="p-3 font-mono font-black">{{ $code->code }}</td>
                        <td>{{ $code->type === 'percent' ? 'نسبة' : 'ثابت' }}</td>
                        <td>{{ $code->value }}</td>
                        <td>{{ optional($code->starts_at)->format('Y-m-d') }} — {{ optional($code->ends_at)->format('Y-m-d') }}</td>
                        <td>{{ $code->is_active ? 'مفعّل' : 'متوقف' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="p-3">{{ $codes->links() }}</div>
        </div>
    </div>
@endsection
