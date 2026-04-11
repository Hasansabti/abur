@extends('layouts.agency')

@section('content')
    <div class="container mx-auto px-4 py-8" x-data="seatMapHandler()">

        {{-- الهيدر --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 border-b pb-6 gap-4">
            <div>
                <h1 class="text-3xl font-black text-slate-800 flex items-center gap-3">
                    <span class="p-3 bg-slate-100 rounded-2xl">🚌</span>
                    خريطة ركاب الحافلة
                </h1>
                <p class="text-sm text-slate-400 font-bold mt-2 flex items-center gap-2">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    رحلة الرياض - مكة | حافلة VIP (44 مقعد)
                </p>
            </div>

            <div class="flex items-center gap-4">
                <button @click="saveMap()"
                    class="px-8 py-4 bg-blue-600 text-white rounded-2xl shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all font-black text-sm flex items-center gap-2">
                    <span>💾</span> حفظ التعديلات
                </button>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-12 items-start justify-center">

            {{-- 1. خريطة الحافلة --}}
            <div
                class="bg-white p-10 rounded-[5rem] border-[12px] border-slate-100 max-w-sm mx-auto shadow-2xl relative animate-fade-in ring-1 ring-slate-200">
                {{-- مقدمة الباص --}}
                <div class="grid grid-cols-5 gap-y-6 mb-12 pb-8 border-b-4 border-dotted border-slate-100">
                    <div class="flex flex-col items-center gap-1">
                        <div
                            class="w-12 h-12 rounded-2xl bg-slate-800 flex items-center justify-center text-xl shadow-lg shadow-slate-200">
                            🎡</div>
                        <span class="text-[9px] font-black text-slate-400 uppercase">السائق</span>
                    </div>
                    <div class="col-span-3"></div>
                    <div class="relative flex flex-col items-center gap-1">
                        <div
                            class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 text-[10px] font-black border-b-4 border-slate-200 flex items-center justify-center italic">
                            مرافق</div>
                    </div>
                </div>

                {{-- مقاعد الركاب --}}
                <div class="grid grid-cols-5 gap-y-5">
                    <template x-for="row in 11">
                        <div class="contents">
                            <template x-for="s in [1, 2]">
                                <div class="relative flex justify-center">
                                    <button @click="toggleMenu(index(row, s))" :class="getSeatClass(index(row, s))"
                                        class="w-12 h-12 rounded-2xl text-[10px] font-black border-b-4 flex items-center justify-center transition-all shadow-sm relative">
                                        <span x-text="getSeatLabel(index(row, s))"></span>
                                        <template x-if="seats[index(row, s)].passenger">
                                            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                                <span
                                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                                            </span>
                                        </template>
                                    </button>
                                </div>
                            </template>

                            <div class="flex justify-center items-center">
                                <div class="h-full w-px border-l-2 border-dashed border-slate-100"></div>
                            </div>

                            <template x-for="s in [3, 4]">
                                <div class="relative flex justify-center">
                                    <button @click="toggleMenu(index(row, s))" :class="getSeatClass(index(row, s))"
                                        class="w-12 h-12 rounded-2xl text-[10px] font-black border-b-4 flex items-center justify-center transition-all shadow-sm relative">
                                        <span x-text="getSeatLabel(index(row, s))"></span>
                                        <template x-if="seats[index(row, s)].passenger">
                                            <span class="absolute -top-1 -right-1 h-2 w-2 bg-white rounded-full"></span>
                                        </template>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- 2. قائمة الركاب والإحصائيات --}}
            <div class="w-full lg:w-[400px] space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-blue-600 p-6 rounded-[2rem] text-white shadow-xl shadow-blue-100">
                        <p class="text-[10px] font-black uppercase opacity-60 mb-1">المحجوز</p>
                        <h4 class="text-3xl font-black" x-text="countOccupied()"></h4>
                    </div>
                    <div class="bg-slate-800 p-6 rounded-[2rem] text-white">
                        <p class="text-[10px] font-black uppercase opacity-60 mb-1">المتبقي</p>
                        <h4 class="text-3xl font-black" x-text="44 - countOccupied()"></h4>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-[3rem] shadow-xl border border-slate-50 overflow-hidden">
                    <h3 class="font-black text-slate-800 mb-6 text-lg italic flex items-center gap-2">
                        قائمة الركاب المأكدين 📋
                    </h3>
                    <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                        <template x-for="(seat, id) in seats">
                            <template x-if="seat.passenger">
                                <div
                                    class="flex items-center justify-between p-4 rounded-2xl border-b border-slate-50 hover:bg-slate-50 transition-colors group">
                                    <div class="flex items-center gap-3">
                                        <div :class="seat.status === 'male' ? 'bg-blue-100 text-blue-600' :
                                            'bg-pink-100 text-pink-600'"
                                            class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm">
                                            <span x-text="seat.status === 'male' ? '👨' : '👩'"></span>
                                        </div>
                                        <div>
                                            <div class="text-xs font-black text-slate-800" x-text="seat.passenger"></div>
                                            <div class="text-[9px] font-bold text-slate-400 uppercase">مقعد #<span
                                                    x-text="id"></span></div>
                                        </div>
                                    </div>
                                    <button @click="openRateUser(seat)"
                                        class="opacity-0 group-hover:opacity-100 bg-slate-100 text-[9px] font-black px-3 py-2 rounded-lg transition-all hover:bg-yellow-400 hover:text-white">
                                        ⭐ تقييم
                                    </button>
                                </div>
                            </template>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- القائمة المنبثقة للتحكم --}}
        <div x-show="activeSeat !== null" @click.away="activeSeat = null"
            class="fixed z-[100] bg-white/95 backdrop-blur-md shadow-2xl rounded-[2.5rem] border p-4 w-64 animate-pop-in"
            :style="`top: ${menuPos.y}px; left: ${menuPos.x}px`" x-cloak>
            <div class="p-2 border-b text-[10px] font-black text-slate-400 uppercase text-center mb-3 tracking-widest">إدارة
                المقعد #<span x-text="activeSeat"></span></div>

            <div class="space-y-1">
                <template x-if="activeSeat && seats[activeSeat].passenger">
                    <div>
                        <div class="p-3 bg-slate-50 rounded-2xl mb-3 border border-slate-100">
                            <p class="text-[8px] font-black text-slate-400 uppercase">الراكب الحالي</p>
                            <p class="text-xs font-black text-slate-800 truncate" x-text="seats[activeSeat].passenger"></p>
                        </div>
                        {{-- زر تقييم العميل --}}
                        <button @click="openRateUser(seats[activeSeat])"
                            class="w-full text-right p-3 bg-yellow-50 text-yellow-700 rounded-2xl text-[10px] font-black flex items-center justify-between mb-2 hover:bg-yellow-100 transition-colors">
                            تقييم سلوك الراكب <span>⭐</span>
                        </button>
                        <hr class="my-2 border-slate-100">
                    </div>
                </template>

                <button @click="updateSeat(activeSeat, 'male', 'سيف العتيبي')"
                    class="w-full text-right p-3 hover:bg-blue-50 text-blue-700 rounded-2xl text-[10px] font-black flex items-center justify-between transition-colors">حجز
                    لرجل <span>👨</span></button>
                <button @click="updateSeat(activeSeat, 'female', 'نورة محمد')"
                    class="w-full text-right p-3 hover:bg-pink-50 text-pink-700 rounded-2xl text-[10px] font-black flex items-center justify-between transition-colors">حجز
                    لامرأة <span>👩</span></button>
                <button @click="updateSeat(activeSeat, 'available', null)"
                    class="w-full text-right p-3 hover:bg-red-50 text-red-500 rounded-2xl text-[10px] font-black flex items-center justify-between">إلغاء
                    الحجز <span>🔓</span></button>
            </div>
        </div>

        {{-- مودال تقييم الراكب --}}
        <div x-show="showRateModal"
            class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak
            x-transition>
            <div class="bg-white w-full max-w-sm rounded-[3rem] p-8 shadow-2xl relative"
                @click.away="showRateModal = false">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-50 text-2xl flex items-center justify-center rounded-2xl mx-auto mb-4">👤
                    </div>
                    <h3 class="text-xl font-black text-slate-800 italic">تقييم الراكب</h3>
                    <p class="text-xs text-slate-400 font-bold mt-1" x-text="userToRate.passenger"></p>
                </div>

                <div class="flex justify-center gap-2 my-8" x-data="{ r: 0 }">
                    <template x-for="i in 5">
                        <button @click="r = i" :class="r >= i ? 'text-yellow-400' : 'text-slate-100'"
                            class="text-3xl transition-transform hover:scale-125">★</button>
                    </template>
                </div>

                <textarea placeholder="ملاحظات حول التزام الراكب بالمواعيد أو السلوك..."
                    class="w-full bg-slate-50 border-none rounded-2xl p-4 text-xs font-medium focus:ring-2 focus:ring-blue-500 outline-none mb-6"
                    rows="3"></textarea>

                <div class="flex gap-2">
                    <button @click="showRateModal = false"
                        class="flex-1 py-4 bg-slate-100 text-slate-400 rounded-2xl font-black text-[10px]">إلغاء</button>
                    <button @click="submitRate()"
                        class="flex-[2] py-4 bg-slate-900 text-white rounded-2xl font-black text-[10px] shadow-lg shadow-slate-200">إرسال
                        التقييم</button>
                </div>
            </div>
        </div>

    </div>

    <script>
        function seatMapHandler() {
            return {
                seats: {},
                activeSeat: null,
                showRateModal: false,
                userToRate: {},
                menuPos: {
                    x: 0,
                    y: 0
                },
                init() {
                    for (let i = 1; i <= 44; i++) {
                        this.seats[i] = {
                            id: i,
                            status: 'available',
                            passenger: null
                        };
                    }
                    // بيانات وهمية
                    this.seats[1] = {
                        id: 1,
                        status: 'male',
                        passenger: 'سعد القحطاني'
                    };
                    this.seats[5] = {
                        id: 5,
                        status: 'female',
                        passenger: 'سارة العلي'
                    };
                    this.seats[12] = {
                        id: 12,
                        status: 'male',
                        passenger: 'عبدالله الشمري'
                    };
                },
                index(row, s) {
                    return (row - 1) * 4 + s;
                },
                getSeatLabel(id) {
                    return id;
                },
                toggleMenu(id) {
                    this.activeSeat = id;
                    this.menuPos = {
                        x: event.clientX > window.innerWidth - 250 ? event.clientX - 250 : event.clientX,
                        y: event.clientY + 15
                    };
                },
                getSeatClass(id) {
                    const seat = this.seats[id];
                    switch (seat.status) {
                        case 'male':
                            return 'bg-blue-600 border-blue-800 text-white shadow-lg shadow-blue-100 scale-105';
                        case 'female':
                            return 'bg-pink-500 border-pink-700 text-white shadow-lg shadow-pink-100 scale-105';
                        default:
                            return 'bg-white border-slate-200 text-slate-400 hover:border-blue-400';
                    }
                },
                updateSeat(id, status, name) {
                    this.seats[id].status = status;
                    this.seats[id].passenger = name;
                    this.activeSeat = null;
                },
                openRateUser(seat) {
                    this.userToRate = seat;
                    this.showRateModal = true;
                    this.activeSeat = null;
                },
                submitRate() {
                    alert('تم تسجيل تقييمك للراكب بنجاح! سيؤثر ذلك على تقييمه العام.');
                    this.showRateModal = false;
                },
                countOccupied() {
                    return Object.values(this.seats).filter(s => s.passenger !== null).length;
                },
                saveMap() {
                    alert('تم تحديث قائمة الركاب بنجاح! ✅');
                }
            }
        }
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        .animate-pop-in {
            animation: popIn 0.3s cubic-bezier(0.26, 0.53, 0.74, 1.48);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
@endsection
