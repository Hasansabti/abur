@extends('layouts.agency')

@section('content')
<div class="p-6 max-w-7xl mx-auto" dir="rtl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-800">تقرير الوجبات</h1>
            <p class="text-xs text-slate-500 font-bold mt-1">الأشخاص الفعليون فقط — بدون المقاعد الإضافية</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-black">تصدير Excel</a>
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 text-white rounded-xl text-xs font-black">طباعة</button>
        </div>
    </div>

    <form class="grid grid-cols-2 md:grid-cols-5 gap-3 bg-white p-4 rounded-2xl border border-slate-100 mb-6" method="get">
        <select name="trip_id" class="border rounded-xl px-3 py-2 text-xs">
            <option value="">كل الرحلات</option>
            @foreach ($trips as $trip)
                <option value="{{ $trip->id }}" @selected(request('trip_id') == $trip->id)>{{ $trip->title }}</option>
            @endforeach
        </select>
        <input type="text" name="booking_id" value="{{ request('booking_id') }}" placeholder="رقم الحجز" class="border rounded-xl px-3 py-2 text-xs">
        <input type="text" name="hotel" value="{{ request('hotel') }}" placeholder="الفندق" class="border rounded-xl px-3 py-2 text-xs">
        <input type="text" name="passenger" value="{{ request('passenger') }}" placeholder="اسم الراكب" class="border rounded-xl px-3 py-2 text-xs">
        <select name="meal" class="border rounded-xl px-3 py-2 text-xs">
            <option value="">كل الوجبات</option>
            <option value="breakfast" @selected(request('meal')==='breakfast')>إفطار</option>
            <option value="lunch" @selected(request('meal')==='lunch')>غداء</option>
            <option value="dinner" @selected(request('meal')==='dinner')>عشاء</option>
        </select>
        <button class="bg-indigo-600 text-white rounded-xl text-xs font-black">تصفية</button>
    </form>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="bg-white p-4 rounded-2xl border"><div class="text-[10px] text-slate-400">الأشخاص</div><div class="text-xl font-black">{{ $summary['people'] }}</div></div>
        <div class="bg-white p-4 rounded-2xl border"><div class="text-[10px] text-slate-400">إفطار</div><div class="text-xl font-black">{{ $summary['breakfast'] }}</div></div>
        <div class="bg-white p-4 rounded-2xl border"><div class="text-[10px] text-slate-400">غداء</div><div class="text-xl font-black">{{ $summary['lunch'] }}</div></div>
        <div class="bg-white p-4 rounded-2xl border"><div class="text-[10px] text-slate-400">عشاء</div><div class="text-xl font-black">{{ $summary['dinner'] }}</div></div>
        <div class="bg-white p-4 rounded-2xl border"><div class="text-[10px] text-slate-400">قيمة الباقات</div><div class="text-xl font-black">{{ number_format($summary['value'], 2) }}</div></div>
    </div>

    <div class="bg-white rounded-2xl border overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-right">#</th>
                    <th class="p-3 text-right">الحجز</th>
                    <th class="p-3 text-right">الراكب</th>
                    <th class="p-3 text-right">الرحلة</th>
                    <th class="p-3 text-right">الفندق</th>
                    <th class="p-3 text-right">الباقة</th>
                    <th class="p-3 text-right">إفطار</th>
                    <th class="p-3 text-right">غداء</th>
                    <th class="p-3 text-right">عشاء</th>
                    <th class="p-3 text-right">السعر</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $i => $row)
                    <tr class="border-t">
                        <td class="p-3">{{ $i + 1 }}</td>
                        <td class="p-3">#{{ $row->booking_id }}</td>
                        <td class="p-3 font-bold">{{ $row->passenger_name }}</td>
                        <td class="p-3">{{ $row->booking?->trip?->title }}</td>
                        <td class="p-3">{{ $row->booking?->trip?->hotel_name ?? '—' }}</td>
                        <td class="p-3">{{ $row->meal_options['package_name'] ?? '—' }}</td>
                        <td class="p-3">{{ $row->with_breakfast ? 'مشمول' : 'غير مشمول' }}</td>
                        <td class="p-3">{{ $row->with_lunch ? 'مشمول' : 'غير مشمول' }}</td>
                        <td class="p-3">{{ $row->with_dinner ? 'مشمول' : 'غير مشمول' }}</td>
                        <td class="p-3">{{ number_format(($row->meal_options['package_price'] ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="p-8 text-center text-slate-400">لا توجد بيانات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
