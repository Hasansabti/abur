@extends('layouts.agency')

@section('content')
    <div class="p-6 space-y-4" dir="rtl">
        <div class="flex justify-between items-center">
            <h1 class="text-xl font-black">تقرير الوجبات</h1>
            <div class="flex gap-2">
                <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="px-3 py-2 bg-emerald-600 text-white text-xs font-black rounded-xl">Excel</a>
                <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="px-3 py-2 bg-slate-800 text-white text-xs font-black rounded-xl">PDF</a>
                <button onclick="window.print()" class="px-3 py-2 bg-blue-600 text-white text-xs font-black rounded-xl">طباعة</button>
            </div>
        </div>
        <form class="bg-white rounded-2xl p-4 grid grid-cols-2 md:grid-cols-4 gap-3 text-xs" method="GET">
            <select name="trip_id" class="border rounded-xl px-3 py-2">
                <option value="">كل الرحلات</option>
                @foreach ($trips as $t)
                    <option value="{{ $t->id }}" @selected(request('trip_id') == $t->id)>{{ $t->title }}</option>
                @endforeach
            </select>
            <input name="booking_id" value="{{ request('booking_id') }}" placeholder="رقم الحجز" class="border rounded-xl px-3 py-2">
            <input name="passenger" value="{{ request('passenger') }}" placeholder="اسم الراكب" class="border rounded-xl px-3 py-2">
            <select name="meal" class="border rounded-xl px-3 py-2">
                <option value="">كل الوجبات</option>
                <option value="breakfast" @selected(request('meal')==='breakfast')>إفطار</option>
                <option value="lunch" @selected(request('meal')==='lunch')>غداء</option>
                <option value="dinner" @selected(request('meal')==='dinner')>عشاء</option>
            </select>
            <input name="package" value="{{ request('package') }}" placeholder="باقة الوجبات" class="border rounded-xl px-3 py-2">
            <button class="bg-indigo-600 text-white rounded-xl font-black">تصفية</button>
        </form>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-xs">
            <div class="bg-white p-3 rounded-2xl font-black">الأشخاص: {{ $summary['people'] }}</div>
            <div class="bg-white p-3 rounded-2xl">إفطار: {{ $summary['breakfast'] }}</div>
            <div class="bg-white p-3 rounded-2xl">غداء: {{ $summary['lunch'] }}</div>
            <div class="bg-white p-3 rounded-2xl">عشاء: {{ $summary['dinner'] }}</div>
            <div class="bg-white p-3 rounded-2xl">قيمة الباقات: {{ number_format($summary['total_value'], 2) }}</div>
        </div>
        <div class="bg-white rounded-2xl overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-3">#</th>
                        <th class="p-3">الحجز</th>
                        <th class="p-3">الراكب</th>
                        <th class="p-3">الرحلة</th>
                        <th class="p-3">الفندق</th>
                        <th class="p-3">الغرفة</th>
                        <th class="p-3">الباقة</th>
                        <th class="p-3">إفطار</th>
                        <th class="p-3">غداء</th>
                        <th class="p-3">عشاء</th>
                        <th class="p-3">السعر</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $i => $s)
                        @php $snap = $s->meal_snapshot ?? $s->meal_options ?? []; @endphp
                        <tr class="border-t">
                            <td class="p-3">{{ $i + 1 }}</td>
                            <td class="p-3">BK-{{ $s->booking_id }}</td>
                            <td class="p-3">{{ $s->passenger_name }}</td>
                            <td class="p-3">{{ $s->booking?->trip?->title }}</td>
                            <td class="p-3">{{ $s->booking?->trip?->hotel_name ?: '—' }}</td>
                            <td class="p-3">{{ $s->room?->room_number ?: '—' }}</td>
                            <td class="p-3">{{ $snap['package_name'] ?? '—' }}</td>
                            <td class="p-3">{{ $s->with_breakfast ? 'مشمول' : 'غير مشمول' }}</td>
                            <td class="p-3">{{ $s->with_lunch ? 'مشمول' : 'غير مشمول' }}</td>
                            <td class="p-3">{{ $s->with_dinner ? 'مشمول' : 'غير مشمول' }}</td>
                            <td class="p-3">{{ number_format((float) ($snap['package_price'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
