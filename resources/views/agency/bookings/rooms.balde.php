@extends('layouts.agency')

@section('content')
<div class="container mx-auto px-4 py-8 font-sans" dir="rtl">

    {{-- هيدر الصفحة والتحكم --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8 text-right">
        <div>
            <h1 class="text-xl font-black text-slate-900 flex items-center gap-2">
                <span class="p-2 bg-indigo-50 text-indigo-600 rounded-2xl text-lg">🏢</span>
                <span>إدارة تسكين غرف المشاع (Shared)</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1 font-medium">الرحلة: <span class="text-slate-800 font-bold">{{ $trip->title }}</span> | الرمز: <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-indigo-600">{{ $trip->code }}</span></p>
        </div>
    </div>

    {{-- إحصائيات الغرف الكلية --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8 text-right">
        <div class="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] text-slate-400 font-bold block mb-1">إجمالي الغرف بالرحلة</span>
                <span class="text-xl font-black text-slate-800 font-mono">{{ $totalRooms }}</span>
            </div>
            <span class="w-11 h-11 bg-slate-50 text-slate-500 flex items-center justify-center rounded-2xl text-lg">🔑</span>
        </div>
        <div class="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] text-slate-400 font-bold block mb-1">إجمالي الأسرة المتاحة</span>
                <span class="text-xl font-black text-slate-800 font-mono">{{ $totalBeds }}</span>
            </div>
            <span class="w-11 h-11 bg-indigo-50 text-indigo-500 flex items-center justify-center rounded-2xl text-lg">🛏️</span>
        </div>
        <div class="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] text-slate-400 font-bold block mb-1">الأسرة المشغولة</span>
                <span class="text-xl font-black text-amber-600 font-mono">{{ $occupiedBeds }}</span>
            </div>
            <span class="w-11 h-11 bg-amber-50 text-amber-500 flex items-center justify-center rounded-2xl text-lg">👨‍👦</span>
        </div>
        <div class="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] text-slate-400 font-bold block mb-1">الأسرة الفارغة المتبقية</span>
                <span class="text-xl font-black text-emerald-600 font-mono">{{ $availableBeds }}</span>
            </div>
            <span class="w-11 h-11 bg-emerald-50 text-emerald-500 flex items-center justify-center rounded-2xl text-lg">✨</span>
        </div>
    </div>

    {{-- قسم إدارة وتحديث مخزون الغرف الحالي للرحلة --}}
    <div class="bg-white border border-slate-100 shadow-sm rounded-3xl p-6 mb-8 text-right">
        <h3 class="text-xs font-black text-slate-800 mb-4 flex items-center gap-1.5">
            <span>⚙️</span> تحديث وإضافة مخزون الغرف المتاحة للرحلة
        </h3>
        <form action="{{ route('agency.bookings.rooms') }}" method="GET" class="mb-6 flex gap-4 items-center">
            <div class="flex-1">
                <label for="trip_id" class="block text-sm font-medium text-gray-300 mb-1">اختر الرحلة لعرض المسافرين:</label>
                <select name="trip_id" id="trip_id" onchange="this.form.submit()" class="w-full bg-slate-800 text-white p-3 rounded-xl border border-slate-700">
                    <option value="">-- كل الرحلات --</option>
                    @foreach($trips as $trip)
                    <option value="{{ $trip->id }}" {{ $selectedTripId == $trip->id ? 'selected' : '' }}>
                        {{ $trip->name }} ({{ $trip->date }})
                    </option>
                    @endforeach
                </select>
            </div>
        </form>
        <form action="#" method="POST" class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="text-[11px] text-slate-500 mb-1.5 block font-bold">غرف مفردة (Single):</label>
                <input type="number" name="rooms_count_1" value="{{ $trip->rooms_count_1 }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono outline-none focus:border-indigo-500">
            </div>
            <div>
                <label class="text-[11px] text-slate-500 mb-1.5 block font-bold">غرف ثنائية (Double):</label>
                <input type="number" name="rooms_count_2" value="{{ $trip->rooms_count_2 }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono outline-none focus:border-indigo-500">
            </div>
            <div>
                <label class="text-[11px] text-slate-500 mb-1.5 block font-bold">غرف ثلاثية (Triple):</label>
                <input type="number" name="rooms_count_3" value="{{ $trip->rooms_count_3 }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono outline-none focus:border-indigo-500">
            </div>
            <div>
                <label class="text-[11px] text-slate-500 mb-1.5 block font-bold">غرف رباعية (Quad):</label>
                <input type="number" name="rooms_count_4" value="{{ $trip->rooms_count_4 }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono outline-none focus:border-indigo-500">
            </div>
            <div class="col-span-2 md:col-span-4 flex justify-end mt-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition-all">
                    تحديث وإرسال المخزون ✓
                </button>
            </div>
        </form>
    </div>

    {{-- نظام التسكين وتوزيع ركاب المشاع --}}
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-8 items-start text-right">

        {{-- قوائم انتظار المشاع الجانبية --}}
        <div class="xl:col-span-1 space-y-6">
            <h2 class="text-sm font-black text-slate-800 flex items-center gap-1.5 mb-2">
                <span>📋</span> قوائم انتظار المشاع (Shared)
            </h2>

            {{-- خزان الرجال --}}
            <div class="bg-white border border-slate-100 shadow-sm rounded-3xl p-4">
                <div class="flex items-center justify-between border-b border-slate-50 pb-3 mb-3">
                    <span class="text-xs font-black text-blue-700 flex items-center gap-1">👨 مقاعد (ذكور)</span>
                    <span class="bg-blue-50 text-blue-700 font-mono text-[10px] px-2 py-0.5 rounded-lg font-bold">{{ $sharedMales->count() }}</span>
                </div>
                <div class="space-y-2 max-h-[250px] overflow-y-auto pl-1">
                    @forelse($sharedMales as $seat)
                    <div class="p-2.5 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-between text-right cursor-grab hover:bg-indigo-50/50 transition-all">
                        <div>
                            <p class="text-xs font-bold text-slate-800">{{ $seat->passenger_name }}</p>
                            <p class="text-[10px] text-slate-400 font-mono mt-0.5">حجز #{{ $seat->booking_id }}</p>
                        </div>
                        <span class="text-[10px] font-bold text-indigo-600 bg-white border border-slate-200/60 px-1.5 py-0.5 rounded-md">تسكين</span>
                    </div>
                    @empty
                    <p class="text-[11px] text-slate-400 text-center py-4">لا يوجد مقاعد رجال بالانتظار</p>
                    @endforelse
                </div>
            </div>

            {{-- خزان النساء --}}
            <div class="bg-white border border-slate-100 shadow-sm rounded-3xl p-4">
                <div class="flex items-center justify-between border-b border-slate-50 pb-3 mb-3">
                    <span class="text-xs font-black text-rose-700 flex items-center gap-1">👩 مقاعد (إناث)</span>
                    <span class="bg-rose-50 text-rose-700 font-mono text-[10px] px-2 py-0.5 rounded-lg font-bold">{{ $sharedFemales->count() }}</span>
                </div>
                <div class="space-y-2 max-h-[250px] overflow-y-auto pl-1">
                    @forelse($sharedFemales as $seat)
                    <div class="p-2.5 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-between text-right cursor-grab hover:bg-indigo-50/50 transition-all">
                        <div>
                            <p class="text-xs font-bold text-slate-800">{{ $seat->passenger_name }}</p>
                            <p class="text-[10px] text-slate-400 font-mono mt-0.5">حجز #{{ $seat->booking_id }}</p>
                        </div>
                        <span class="text-[10px] font-bold text-indigo-600 bg-white border border-slate-200/60 px-1.5 py-0.5 rounded-md">تسكين</span>
                    </div>
                    @empty
                    <p class="text-[11px] text-slate-400 text-center py-4">لا يوجد مقاعد نساء بالانتظار</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- شبكة توزيع الغرف التوليدية --}}
        <div class="xl:col-span-3">
            <h2 class="text-sm font-black text-slate-800 flex items-center gap-1.5 mb-4">
                <span>🛏️</span> توزيع وتسكين الغرف الافتراضي
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($roomsList as $room)
                <div class="bg-white border border-slate-100 shadow-sm rounded-3xl p-4 flex flex-col justify-between transition-all hover:shadow-md">

                    <div class="flex justify-between items-start border-b border-slate-50 pb-2.5 mb-3">
                        <div>
                            <h4 class="text-xs font-black text-slate-900">غرفة {{ $room->room_type }}</h4>
                            <span class="text-[10px] font-bold text-slate-400 mt-0.5 block">رقم الغرفة بالباقة: #{{ $room->room_number }}</span>
                        </div>

                        @if($room->gender_type === 'male')
                        <span class="text-[10px] font-bold bg-blue-50 text-blue-700 px-2 py-0.5 rounded-lg">👨 غُرفة رجال</span>
                        @elseif($room->gender_type === 'female')
                        <span class="text-[10px] font-bold bg-rose-50 text-rose-700 px-2 py-0.5 rounded-lg">👩 غُرفة نساء</span>
                        @else
                        <span class="text-[10px] font-bold bg-slate-50 text-slate-500 px-2 py-0.5 rounded-lg">🎲 فارغة</span>
                        @endif
                    </div>

                    {{-- عرض الأسرة والمسافرين بناءً على سعة نوع الغرفة --}}
                    <div class="space-y-2 mb-4">
                        @for($i = 0; $i < $room->capacity; $i++)
                            @php
                            $allocatedSeat = $room->seats->get($i);
                            @endphp

                            @if($allocatedSeat)
                            <div class="p-2 bg-indigo-50/50 border border-indigo-100/40 rounded-xl flex items-center justify-between text-right">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs">🛏️</span>
                                    <div>
                                        <p class="text-xs font-bold text-indigo-900">{{ $allocatedSeat->passenger_name }}</p>
                                        <p class="text-[9px] text-indigo-500 font-mono">حجز #{{ $allocatedSeat->booking_id }}</p>
                                    </div>
                                </div>
                                <button class="text-slate-400 hover:text-red-500 transition-colors text-[11px] px-1 font-bold">✕</button>
                            </div>
                            @else
                            <div class="p-2 border border-dashed border-slate-200 rounded-xl flex items-center justify-center text-center bg-slate-50/30">
                                <button class="text-[10px] text-slate-400 font-bold hover:text-indigo-600 transition-colors flex items-center gap-1">
                                    <span>➕</span> تسكين مسافر
                                </button>
                            </div>
                            @endif
                            @endfor
                    </div>

                    <div class="pt-2 border-t border-slate-50 flex items-center justify-between text-[10px] text-slate-400 font-semibold">
                        <span>حالة الاستيعاب:</span>
                        <span class="font-mono font-bold text-slate-700">{{ $room->seats->count() }} / {{ $room->capacity }} أسرة</span>
                    </div>

                </div>
                @endforeach
            </div>
        </div>

    </div>
</div>
@endsection