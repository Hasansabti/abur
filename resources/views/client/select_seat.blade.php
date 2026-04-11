@extends('layouts.client')

@section('content')
    <div class="container mx-auto px-4 py-10 max-w-6xl" x-data="bookingManager()">

        {{-- 1. رأس الصفحة --}}
        <div
            class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-100 mb-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-6">
                <div
                    class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-3xl shadow-lg shadow-blue-100">
                    {{ $trip->transport_type == 'bus' ? '🚌' : '✈️' }}
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-800">{{ $trip->route }}</h1>
                    <p class="text-slate-400 font-bold text-sm tracking-tight">عبر: {{ $trip->vehicle_model }}</p>
                </div>
            </div>

            {{-- اختيار نوع الغرفة والأسعار --}}
            <div class="flex gap-2 bg-slate-50 p-2 rounded-2xl border border-slate-100">
                <template x-for="(price, type) in roomPrices" :key="type">
                    <button @click="selectedRoomType = type"
                        :class="selectedRoomType === type ? 'bg-white shadow-sm border-blue-200 text-blue-600' :
                            'text-slate-400 border-transparent'"
                        class="px-4 py-2 rounded-xl border text-[10px] font-black transition-all">
                        <span x-text="getTypeName(type)"></span><br>
                        <span class="text-slate-800" x-text="price + ' ر.س'"></span>
                    </button>
                </template>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            {{-- 2. مخطط المقاعد (يسار) --}}
            <div class="lg:col-span-7 bg-white rounded-[3rem] p-10 shadow-sm border border-slate-100">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                    <h3 class="font-black text-slate-800 text-xl tracking-tighter">
                        {{ $trip->transport_type == 'bus' ? 'مخطط الحافلة 🚌' : 'مخطط الطائرة ✈️' }}
                    </h3>

                    <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                        <div class="flex items-center gap-2"><span
                                class="w-3 h-3 bg-slate-100 rounded-sm border border-slate-200"></span> <span
                                class="text-[9px] font-bold text-slate-400">متاح</span></div>
                        <div class="flex items-center gap-2"><span class="w-3 h-3 bg-blue-600 rounded-sm"></span> <span
                                class="text-[9px] font-bold text-slate-400">محدد</span></div>
                        <div class="flex items-center gap-2"><span
                                class="w-3 h-3 bg-pink-100 rounded-sm border border-pink-200"></span> <span
                                class="text-[9px] font-bold text-pink-400">محجوز (أنثى)</span></div>
                        <div class="flex items-center gap-2"><span
                                class="w-3 h-3 bg-blue-100 rounded-sm border border-blue-200"></span> <span
                                class="text-[9px] font-bold text-blue-400">محجوز (ذكر)</span></div>
                    </div>
                </div>

                @if ($trip->transport_type == 'bus')
                    {{-- هيكل الحافلة --}}
                    <div
                        class="max-w-xs mx-auto border-[6px] border-slate-100 rounded-[4rem] p-10 relative shadow-inner bg-white">
                        <div class="flex justify-between items-center mb-12 border-b-2 border-slate-50 pb-6 px-2">
                            <div
                                class="w-10 h-10 bg-slate-50 rounded-full flex items-center justify-center border border-slate-100 text-lg">
                                🎡</div>
                            @include('client.partials.seat_button', ['i' => 0, 'label' => 'مرافق'])
                        </div>

                        <div class="grid grid-cols-5 gap-y-4 gap-x-2">
                            @for ($i = 1; $i <= 48; $i++)
                                @include('client.partials.seat_button', ['i' => $i, 'label' => 'Seat'])
                                @if ($i % 4 == 2)
                                    <div class="w-8 flex items-center justify-center">
                                        <div class="w-px h-full bg-slate-50 border-l border-dashed border-slate-200"></div>
                                    </div>
                                @endif
                            @endfor
                        </div>
                    </div>
                @else
                    {{-- هيكل الطائرة --}}
                    <div
                        class="max-w-md mx-auto border-x-[12px] border-t-[40px] border-slate-100 rounded-t-[10rem] p-8 relative shadow-inner bg-white">
                        <div
                            class="text-center mb-10 uppercase tracking-widest text-[10px] font-bold text-slate-300 italic">
                            منطقة قمرة القيادة</div>

                        <div class="grid grid-cols-7 gap-y-4 items-center">
                            @for ($i = 1; $i <= 90; $i++)
                                @include('client.partials.seat_button', ['i' => $i, 'label' => ''])
                                @if ($i % 6 == 3)
                                    <div class="text-center text-[9px] font-black text-slate-200 uppercase rotate-90">ممر
                                    </div>
                                @endif
                            @endfor
                        </div>
                    </div>
                @endif
            </div>

            {{-- 3. تفاصيل الحجز (يمين) --}}
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white shadow-2xl sticky top-8">
                    <h3 class="text-xl font-black mb-6 flex items-center gap-2">
                        <span class="p-2 bg-blue-600 rounded-xl text-sm">📋</span> ملخص الرحلة
                    </h3>

                    <div class="space-y-4 bg-slate-800/50 p-6 rounded-3xl mb-8 border border-slate-700">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-400 font-bold tracking-widest uppercase">المقاعد المختارة:</span>
                            <span class="font-black text-white"
                                x-text="selectedSeats.join(', ') || 'لم يتم الاختيار'"></span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-400 font-bold tracking-widest uppercase">نوع الغرفة:</span>
                            <span class="font-black text-blue-400" x-text="getTypeName(selectedRoomType)"></span>
                        </div>
                        <div class="pt-4 border-t border-slate-700 flex justify-between items-end">
                            <span class="text-slate-400 text-xs font-bold">المبلغ الإجمالي:</span>
                            <div class="text-left">
                                <p class="text-3xl font-black text-white" x-text="totalPrice"></p>
                                <p class="text-[10px] text-blue-400 font-bold tracking-widest uppercase">ريال سعودي</p>
                            </div>
                        </div>
                    </div>

                    {{-- إدخال بيانات المسافرين --}}
                    <div class="space-y-4 max-h-[280px] overflow-y-auto pr-2 custom-scrollbar">
                        <template x-for="(seat, index) in selectedSeats" :key="seat">
                            <div class="bg-slate-800 p-5 rounded-2xl border border-slate-700 animate-slide-up">
                                <div class="flex justify-between items-center mb-3">
                                    <label class="text-[10px] text-slate-500 font-black uppercase">بيانات المقعد <span
                                            x-text="seat" class="text-blue-500"></span></label>
                                    <div class="flex gap-2">
                                        <span class="text-[9px] bg-slate-700 px-2 py-1 rounded-md">ID Required</span>
                                    </div>
                                </div>
                                <input type="text" placeholder="رقم هوية المسافر"
                                    class="w-full bg-slate-900 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-600 transition-all outline-none text-white placeholder-slate-600">
                            </div>
                        </template>
                    </div>

                    <button @click="proceed()" :disabled="selectedSeats.length === 0"
                        class="w-full mt-8 py-5 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-800 disabled:text-slate-600 text-white rounded-[1.5rem] font-black text-lg transition-all shadow-xl shadow-blue-600/20 active:scale-95">
                        تأكيد الحجز والدفع 💳
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function bookingManager() {
            return {
                selectedSeats: [],
                selectedRoomType: 'quad',
                roomPrices: @json($trip->room_prices),
                capacityMap: {
                    quad: 4,
                    triple: 3,
                    double: 2,
                    single: 1
                },

                toggleSeat(id) {
                    const maxSeats = this.capacityMap[this.selectedRoomType];
                    if (this.selectedSeats.includes(id)) {
                        this.selectedSeats = this.selectedSeats.filter(s => s !== id);
                    } else {
                        if (this.selectedSeats.length < maxSeats) {
                            this.selectedSeats.push(id);
                        } else {
                            alert(`الغرفة الـ ${this.getTypeName(this.selectedRoomType)} تسمح بـ ${maxSeats} مقاعد فقط.`);
                        }
                    }
                },

                isSelected(id) {
                    return this.selectedSeats.includes(id);
                },

                getTypeName(type) {
                    return {
                        quad: 'رباعية',
                        triple: 'ثلاثية',
                        double: 'ثنائية',
                        single: 'فردية'
                    } [type];
                },

                get totalPrice() {
                    return this.roomPrices[this.selectedRoomType] || 0;
                },

                init() {
                    this.$watch('selectedRoomType', () => {
                        this.selectedSeats = [];
                    });
                },

                proceed() {
                    const seats = this.selectedSeats.join(',');
                    window.location.href = `{{ route('client.checkout') }}?seats=${seats}&room=${this.selectedRoomType}`;
                }
            }
        }
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #1e293b;
            border-radius: 10px;
        }

        @keyframes slide-up {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-up {
            animation: slide-up 0.3s ease-out;
        }
    </style>
@endsection
