@extends('layouts.agency')

@section('content')
    <style>
        [x-cloak] { display: none !important; }
    </style>

    <div class="space-y-8" x-data="{
        showReceipt: false,
        currentReceipt: '',
        currentBookingId: '',
        expectedAmount: '',
        customerName: ''
    }">

        <div class="flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-black text-slate-800">مراجعة الحوالات البنكية</h1>
                <p class="text-slate-500 mt-1">تأكيد حجز المقاعد بعد مطابقة إيصال التحويل البنكي.</p>
            </div>
            <div class="bg-amber-50 text-amber-700 px-4 py-2 rounded-xl border border-amber-100 text-xs font-black">
                @if ($pendingBookings)
                    <p class="text-yellow-600 font-bold">⚠️ طلب بانتظار التأكيد</p>
                @else
                    <p class="text-gray-400">لا يوجد طلبات حالياً</p>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-right">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase">رقم الطلب / العميل</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase">الرحلة والمقاعد</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase text-center">المبلغ المطلوب</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase text-center">إيصال التحويل</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase text-center">القرار</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr class="hover:bg-slate-50/30 transition-all">
                        <td class="p-6">
                            <p class="font-black text-slate-800 text-sm">{{ $pendingBookings->id }}</p>
                            <p class="text-xs text-slate-400 font-bold">{{ $pendingBookings->customer }}</p>
                            <p class="text-[10px] text-indigo-400 mt-1">{{ $pendingBookings->created_at }}</p>
                        </td>
                        <td class="p-6">
                            <p class="text-xs font-bold text-slate-700 mb-2">{{ $pendingBookings->trip }}</p>
                            <div class="flex gap-1">
                                @foreach ($pendingBookings->seats as $seat)
                                    <span class="px-2 py-0.5 bg-slate-100 rounded text-[10px] font-black text-slate-500 border border-slate-200">#{{ $seat }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="p-6 text-center">
                            <p class="text-lg font-black text-slate-800">{{ $pendingBookings->amount }} <small class="text-[10px]">ر.س</small></p>
                        </td>
                        <td class="p-6 text-center">
                            <button @click="
                                showReceipt = true;
                                currentReceipt = 'https://placehold.co/600x800?text=Receipt+Image';
                                currentBookingId = '{{ $pendingBookings->id }}';
                                expectedAmount = '{{ $pendingBookings->amount }}';
                                customerName = '{{ $pendingBookings->customer }}';
                            " class="p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-100 transition inline-flex items-center gap-2">
                                <span class="text-xs font-black">👁️ عرض الإيصال</span>
                            </button>
                        </td>
                        <td class="p-6">
                            <div class="flex justify-center gap-2">
                                <form action="#" method="get">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-xl text-xs font-black shadow-lg shadow-green-100 active:scale-95 transition-all">
                                        تأكيد الحجز
                                    </button>
                                </form>
                                <button class="px-4 py-2 bg-white border border-red-100 text-red-500 hover:bg-red-50 rounded-xl text-xs font-black transition-all">
                                    رفض
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div x-show="showReceipt" x-cloak class="fixed inset-0 z-50 flex justify-end">
            <div x-show="showReceipt" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" @click="showReceipt = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

            <div x-show="showReceipt" x-transition:enter="transform transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" class="relative bg-white w-full max-w-lg h-screen shadow-2xl flex flex-col p-8 border-r">

                <div class="flex justify-between items-center mb-8 border-b pb-4">
                    <button @click="showReceipt = false" class="w-10 h-10 bg-slate-50 text-slate-400 rounded-full hover:bg-red-50 hover:text-red-500 transition-all flex items-center justify-center font-bold">✕</button>
                    <div class="text-left">
                        <h3 class="font-black text-slate-800">تفاصيل الحوالة</h3>
                        <p class="text-[10px] text-slate-400 font-bold" x-text="'طلب رقم: ' + currentBookingId"></p>
                    </div>
                </div>

                <div class="bg-blue-50 p-5 rounded-3xl mb-6 border border-blue-100 flex justify-between items-center">
                    <div>
                        <p class="text-[10px] font-black text-blue-400 uppercase mb-1">المبلغ المطلوب</p>
                        <p class="text-xl font-black text-blue-700" x-text="expectedAmount + ' ر.س'"></p>
                    </div>
                    <div class="text-left">
                        <p class="text-[10px] font-black text-blue-400 uppercase mb-1">العميل</p>
                        <p class="text-xs font-black text-slate-700" x-text="customerName"></p>
                    </div>
                </div>

                <div class="flex-grow overflow-y-auto rounded-3xl bg-slate-50 border-2 border-slate-100 relative">
                    <img :src="currentReceipt" class="w-full h-auto rounded-2xl">
                </div>

                <div class="grid grid-cols-2 gap-4 mt-8 pt-6 border-t">
                    <button @click="showReceipt = false" class="py-4 bg-green-500 hover:bg-green-600 text-white rounded-2xl font-black text-xs shadow-lg shadow-green-100 transition-all">
                        تطابق وتأكيد ✅
                    </button>
                    <button @click="showReceipt = false" class="py-4 bg-slate-100 text-slate-500 rounded-2xl font-black text-xs hover:bg-slate-200 transition-all">
                        إغلاق 🔙
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.16.1/dist/cdn.min.js" defer></script>
@endsection
