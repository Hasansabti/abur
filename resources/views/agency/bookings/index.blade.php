@extends('layouts.agency') 

@section('content')
<div class="space-y-8" x-data="{ filter: 'all' }">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-gray-800 tracking-tighter">إدارة الحجوزات</h1>
            <p class="text-gray-500 font-medium">متابعة طلبات المسافرين وحالة الدفع لكل مقعد.</p>
        </div>
        <div class="flex gap-3">
            <div class="bg-green-50 px-4 py-2 rounded-2xl border border-green-100 flex items-center gap-2">
                <span class="text-green-600 font-black text-sm">32 مؤكد</span>
            </div>
            <div class="bg-amber-50 px-4 py-2 rounded-2xl border border-amber-100 flex items-center gap-2">
                <span class="text-amber-600 font-black text-sm">5 معلق</span>
            </div>
        </div>
    </div>

    <div class="bg-white p-4 rounded-[2rem] shadow-sm border border-gray-100 flex flex-wrap gap-4 items-center">
        <div class="flex-1 min-w-[200px] relative">
            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
            <input type="text" placeholder="ابحث برقم الحجز أو اسم العميل..." class="w-full bg-gray-50 border-none rounded-xl pr-10 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
        <div class="flex bg-gray-50 p-1 rounded-xl">
            <button @click="filter = 'all'" :class="filter === 'all' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-400'" class="px-6 py-2 rounded-lg text-xs font-black transition-all">الكل</button>
            <button @click="filter = 'confirmed'" :class="filter === 'confirmed' ? 'bg-white shadow-sm text-green-600' : 'text-gray-400'" class="px-6 py-2 rounded-lg text-xs font-black transition-all">المؤكدة</button>
            <button @click="filter = 'pending'" :class="filter === 'pending' ? 'bg-white shadow-sm text-amber-600' : 'text-gray-400'" class="px-6 py-2 rounded-lg text-xs font-black transition-all">بانتظار الدفع</button>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-right border-collapse">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                    <th class="p-6">رقم الحجز / العميل</th>
                    <th class="p-6">الرحلة</th>
                    <th class="p-6">المقاعد</th>
                    <th class="p-6">المبلغ</th>
                    <th class="p-6 text-center">الحالة</th>
                    <th class="p-6 text-center">التحكم</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($bookings as $booking)
                <tr class="hover:bg-gray-50/30 transition-colors" x-show="filter === 'all' || filter === '{{ $booking->status }}'">
                    <td class="p-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 font-bold text-xs">
                                #
                            </div>
                            <div>
                                <p class="font-black text-gray-800 text-sm">{{ $booking->id }}</p>
                                <p class="text-[10px] text-gray-400 font-bold uppercase">{{ $booking->customer_name }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="p-6">
                        <p class="text-xs font-bold text-gray-700">{{ $booking->trip_title }}</p>
                        <p class="text-[10px] text-gray-400 italic">بتاريخ: {{ $booking->payment_date }}</p>
                    </td>
                    <td class="p-6">
                        <div class="flex gap-1 flex-wrap max-w-[120px]">
                            @foreach($booking->seats as $seat)
                                <span class="px-2 py-0.5 bg-blue-600 text-white rounded text-[10px] font-black shadow-sm shadow-blue-100">
                                    {{ $seat }}
                                </span>
                            @endforeach
                        </div>
                    </td>
                    <td class="p-6">
                        <p class="font-black text-gray-800">{{ $booking->total_price }} <small class="text-[10px] text-gray-400">ر.س</small></p>
                    </td>
                    <td class="p-6 text-center">
                        @if($booking->status == 'confirmed')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-100 text-green-700 rounded-lg text-[10px] font-black">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                تم التأكيد
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-100 text-amber-700 rounded-lg text-[10px] font-black">
                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span>
                                قيد المراجعة
                            </span>
                        @endif
                    </td>
                    <td class="p-6 text-center">
                        <div class="flex justify-center gap-2">
                            @if($booking->status == 'pending')
                                <a href="{{ route('agency.bookings.pending') }}" class="p-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-100" title="مراجعة الحوالة">
                                    💳
                                </a>
                            @endif
                            <button class="p-2 bg-white border border-gray-100 text-gray-400 rounded-xl hover:bg-gray-50 transition" title="طباعة التذكرة">
                                🖨️
                            </button>
                            <button class="p-2 bg-white border border-gray-100 text-gray-400 rounded-xl hover:bg-gray-50 transition" title="تفاصيل">
                                👁️
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection
