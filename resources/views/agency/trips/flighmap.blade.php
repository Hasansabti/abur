@extends('layouts.agency')

@section('content')
{{-- أضفنا متغيرات للتحكم بمودال التقييم: showRatingModal, ratingPassenger --}}
<div class="container mx-auto px-4 py-8" x-data="flightMapHandler()">

    <div class="flex flex-col gap-8 mb-10">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-black text-slate-800 flex items-center gap-3">
                    <span class="p-3 bg-indigo-100 text-indigo-600 rounded-2xl shadow-sm">✈️</span>
                    إدارة ركاب الرحلة
                </h1>
                <p class="text-sm text-slate-400 font-bold mt-2 flex items-center gap-2">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    رحلة رقم: <span class="text-indigo-600">#FL-2026</span> | <span class="italic">نظام تقييم المسافرين مفعّل</span>
                </p>
            </div>

            <button @click="saveMap()" class="px-10 py-4 bg-indigo-600 text-white rounded-[2rem] shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all font-black text-xs uppercase tracking-widest flex items-center gap-2">
                <span>💾</span> حفظ التعديلات النهائية
            </button>
        </div>

        {{-- شبكة الإحصائيات --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase">الركاب الحاليين</p>
                    <h4 class="text-2xl font-black text-slate-800" x-text="countOccupied()"></h4>
                </div>
                <div class="text-2xl opacity-20">👥</div>
            </div>
            <div class="bg-amber-50 p-6 rounded-[2rem] border border-amber-100 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-amber-500 uppercase">تم تقييمهم</p>
                    <h4 class="text-2xl font-black text-amber-700">12</h4>
                </div>
                <div class="text-2xl">⭐</div>
            </div>
            <div class="bg-slate-900 p-6 rounded-[2rem] flex items-center justify-between text-white md:col-span-2">
                <p class="text-xs font-bold opacity-60 italic">ملاحظة: يمكنك تقييم المسافرين لرفع مستوى موثوقيتهم في "عبور".</p>
                <div class="text-xl">💡</div>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-12 items-start justify-center">
        {{-- جسم الطائرة --}}
        <div class="relative bg-white p-12 rounded-t-[15rem] rounded-b-[5rem] border-[12px] border-slate-50 shadow-2xl max-w-xl w-full animate-fade-in ring-1 ring-slate-100">
            <div class="w-full h-32 bg-slate-50 rounded-t-[14rem] mb-12 flex items-center justify-center text-slate-300 font-black tracking-[1em] text-[10px] uppercase border-b-4 border-dotted border-slate-100">
                Cabin Area
            </div>

            <div class="grid grid-cols-7 gap-x-3 gap-y-4 px-4">
                <template x-for="(seat, id) in seats" :key="id">
                    <div class="contents">
                        <div class="relative group">
                            <button
                                @click="openSeatMenu(id)"
                                :class="getSeatClass(seat)"
                                class="w-11 h-11 rounded-xl text-[10px] font-black transition-all border-b-4 flex items-center justify-center relative shadow-sm"
                                :title="seat.passenger_name || 'مقعد فارغ'">
                                <span x-text="seat.status === 'blocked' ? '✕' : id"></span>
                                <template x-if="seat.passenger_name">
                                    <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                        <span class="relative inline-flex rounded-full h-3 w-3 bg-white border border-slate-200"></span>
                                    </span>
                                </template>
                            </button>
                        </div>
                        <template x-if="id % 6 === 3">
                            <div class="flex items-center justify-center">
                                <span class="text-[7px] font-black text-slate-200 rotate-90 tracking-widest opacity-50">AISLE</span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- لوحة الركاب والتقييم --}}
        <div class="w-full lg:w-96 space-y-6">
            <div class="bg-white p-8 rounded-[3rem] shadow-xl border border-slate-50">
                <h3 class="text-lg font-black mb-6 text-slate-800 italic">📋 الركاب المسجلين</h3>
                <div class="space-y-3 max-h-[500px] overflow-y-auto custom-scrollbar pr-2">
                    <template x-for="(seat, id) in seats">
                        <template x-if="seat.passenger_name">
                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100 group">
                                <div class="flex items-center gap-3">
                                    <div :class="seat.status === 'male' ? 'bg-sky-500' : 'bg-pink-500'"
                                         class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-xs">
                                        <span x-text="seat.status === 'male' ? '👨' : '👩'"></span>
                                    </div>
                                    <div>
                                        <p class="text-xs font-black text-slate-700" x-text="seat.passenger_name"></p>
                                        <button @click="openRating(seat.passenger_name)" class="text-[9px] font-black text-indigo-500 hover:underline tracking-tighter uppercase">⭐ تقييم المسافر</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- القائمة المنبثقة --}}
    <div x-show="activeSeatId !== null"
         @click.away="activeSeatId = null"
         class="fixed z-[100] bg-white shadow-2xl rounded-[2rem] border p-3 w-64 animate-pop-in border-slate-100"
         :style="`top: ${menuPos.y}px; left: ${menuPos.x}px`" x-cloak>

        <div class="p-3 border-b text-[10px] font-black text-slate-400 uppercase text-center mb-2">المقعد #<span x-text="activeSeatId"></span></div>

        <template x-if="seats[activeSeatId] && seats[activeSeatId].passenger_name">
            <div class="space-y-1">
                <button @click="openRating(seats[activeSeatId].passenger_name)" class="w-full text-right p-4 bg-indigo-50 text-indigo-700 rounded-2xl text-[10px] font-black flex items-center justify-between mb-2 transition-all hover:bg-indigo-600 hover:text-white">
                    تقييم المسافر <span>⭐</span>
                </button>
                <button @click="removePassenger(activeSeatId)" class="w-full text-right p-3 hover:bg-red-50 text-red-600 rounded-2xl text-[10px] font-bold flex items-center justify-between">إلغاء الحجز <span>✕</span></button>
            </div>
        </template>

        <template x-if="seats[activeSeatId] && !seats[activeSeatId].passenger_name">
            <div class="space-y-1">
                <button @click="updateSeat(activeSeatId, 'male', 'راكب جديد')" class="w-full text-right p-3 hover:bg-sky-50 text-sky-700 rounded-2xl text-[10px] font-black flex items-center justify-between transition-all">حجز رجل <span>👨</span></button>
                <button @click="updateSeat(activeSeatId, 'female', 'راكبة جديدة')" class="w-full text-right p-3 hover:bg-pink-50 text-pink-700 rounded-2xl text-[10px] font-black flex items-center justify-between transition-all">حجز امرأة <span>👩</span></button>
                <button @click="updateSeat(activeSeatId, 'blocked', null)" class="w-full text-right p-3 hover:bg-slate-900 text-white rounded-2xl text-[10px] font-bold flex items-center justify-between transition-all">إغلاق المقعد <span>🚫</span></button>
            </div>
        </template>
    </div>

    {{-- مودال تقييم العميل (Rating Modal) --}}
    <div x-show="showRatingModal"
         class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" x-cloak x-transition>
        <div class="bg-white w-full max-w-md rounded-[3rem] p-10 shadow-2xl relative" @click.away="showRatingModal = false">
            <div class="text-center">
                <div class="w-16 h-16 bg-indigo-50 text-3xl flex items-center justify-center rounded-2xl mx-auto mb-6">🏆</div>
                <h3 class="text-xl font-black text-slate-800 mb-2">تقييم المسافر</h3>
                <p class="text-xs text-slate-400 font-bold mb-8" x-text="ratingPassenger"></p>

                <div class="flex justify-center gap-3 mb-8" x-data="{ r: 0 }">
                    <template x-for="i in 5">
                        <button @click="r = i" :class="r >= i ? 'text-amber-400 scale-110' : 'text-slate-200'" class="text-4xl transition-all">★</button>
                    </template>
                </div>

                <div class="space-y-3">
                    <button @click="showRatingModal = false; alert('تم إرسال تقييم العميل بنجاح')" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-black text-xs shadow-xl shadow-indigo-100 transition-all">تأكيد التقييم</button>
                    <button @click="showRatingModal = false" class="w-full py-4 bg-slate-50 text-slate-400 rounded-2xl font-black text-xs">إلغاء</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function flightMapHandler() {
        return {
            seats: {},
            activeSeatId: null,
            showRatingModal: false,
            ratingPassenger: '',
            menuPos: { x: 0, y: 0 },
            init() {
                // توليد بيانات وهمية
                const names = ["عبدالرحمن الراشد", "ليلى السالم", "عمر الفاروق", "نورة البواردي"];
                for (let i = 1; i <= 90; i++) {
                    let rand = Math.random();
                    if (rand > 0.8) {
                        this.seats[i] = { status: 'male', passenger_name: names[Math.floor(Math.random() * names.length)] };
                    } else if (rand > 0.7) {
                        this.seats[i] = { status: 'female', passenger_name: names[Math.floor(Math.random() * names.length)] };
                    } else {
                        this.seats[i] = { status: 'available', passenger_name: null };
                    }
                }
            },
            getSeatClass(seat) {
                switch (seat.status) {
                    case 'male': return 'bg-sky-500 border-sky-700 text-white shadow-lg shadow-sky-100 scale-105';
                    case 'female': return 'bg-pink-400 border-pink-600 text-white shadow-lg shadow-pink-100 scale-105';
                    case 'blocked': return 'bg-slate-900 border-slate-950 text-slate-500 opacity-40';
                    default: return 'bg-white border-slate-200 text-slate-400 hover:border-indigo-400 hover:bg-indigo-50';
                }
            },
            openSeatMenu(id) {
                this.activeSeatId = id;
                this.menuPos = { x: event.clientX - 120, y: event.clientY + 15 };
            },
            openRating(name) {
                this.activeSeatId = null;
                this.ratingPassenger = name;
                this.showRatingModal = true;
            },
            updateSeat(id, status, name) {
                this.seats[id] = { status, passenger_name: name };
                this.activeSeatId = null;
            },
            removePassenger(id) {
                this.updateSeat(id, 'available', null);
            },
            countOccupied() {
                return Object.values(this.seats).filter(s => s.passenger_name).length;
            },
            saveMap() {
                alert('تم حفظ تقييمات الركاب وبيانات الرحلة! ✅');
            }
        }
    }
</script>

<style>
    [x-cloak] { display: none !important; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .animate-fade-in { animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
    .animate-pop-in { animation: popIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes popIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
</style>
@endsection
