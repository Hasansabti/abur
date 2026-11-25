@extends('layouts.driver')

@section('title', 'قائمة الركاب')

@section('content')
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<div class="max-w-md mx-auto p-4 space-y-6"
     x-data="{
        tripStatus: 'active', {{-- حالة الرحلة الافتراضية: active أو completed --}}
        searchQuery: '',
        filterStatus: 'all',
        passengers: [
            { id: 101, name: 'عبدالله محمد الشمري', seat: '01A', phone: '050123XXXX', status: 'boarded', time: '04:15 م' },
            { id: 102, name: 'سارة عبدالمحسن العتيبي', seat: '04B', phone: '055987XXXX', status: 'pending', time: '--:--' },
            { id: 103, name: 'ياسر سلطان القحطاني', seat: '12C', phone: '053456XXXX', status: 'boarded', time: '04:22 م' },
            { id: 104, name: 'نورة سليمان الدوسري', seat: '07A', phone: '056789XXXX', status: 'pending', time: '--:--' },
            { id: 105, name: 'فهد خالد المطيري', seat: '02B', phone: '054111XXXX', status: 'boarded', time: '04:10 م' },
            { id: 106, name: 'خديجة عمر هوساوي', seat: '15F', phone: '059222XXXX', status: 'absent', time: '--:--' }
        ],

        get counts() {
            return {
                all: this.passengers.length,
                boarded: this.passengers.filter(p => p.status === 'boarded').length,
                pending: this.passengers.filter(p => p.status === 'pending').length
            }
        },

        get filteredPassengers() {
            return this.passengers.filter(p => {
                mbName = p.name.includes(this.searchQuery) || p.seat.includes(this.searchQuery);
                mbStatus = this.filterStatus === 'all' || p.status === this.filterStatus;
                return mbName && mbStatus;
            });
        },

        {{-- دالة إنهاء الرحلة برمجياً --}}
        finishTrip() {
            if(confirm('هل أنت متأكد من الوصول إلى الوجهة وإنهاء الرحلة الحالية؟')) {
                this.tripStatus = 'completed';
                {{-- هنا يمكنك مستقبلاً إرسال طلب fetch لتحديث حالة الرحلة في السيرفر --}}
            }
        }
     }">

    <!-- [أولاً] واجهة الرحلة النشطة (تظهر عندما تكون حالة الرحلة active) -->
    <div x-show="tripStatus === 'active'" class="space-y-6">

        <!-- 1. الهيدر وملخص الرحلة مع زر الإنهاء -->
        <div class="bg-slate-900/60 border border-white/5 p-6 rounded-[2.5rem] flex justify-between items-center flex-row-reverse text-right">
            <div>
                <span class="bg-indigo-500/10 text-indigo-400 text-[9px] font-black uppercase tracking-wider px-3 py-1 rounded-full">الرحلة الحالية</span>
                <h2 class="text-xl font-black italic mt-1">منفذ الرياض ⮕ الدمام</h2>
                <p class="text-[10px] text-slate-500 font-bold mt-0.5">رقم الرحلة: #TRP-902 | حافلة رقم: ٤٢</p>
            </div>
            <button @click="finishTrip()" class="bg-red-500 hover:bg-red-600 active:scale-95 text-white text-xs font-black px-4 py-3 rounded-2xl transition-all shadow-lg shadow-red-500/10">
                🏁 إنهاء
            </button>
        </div>

        <!-- 2. أزرار الفلترة السريعة (Tabs) -->
        <div class="grid grid-cols-3 gap-2 bg-slate-900 p-1.5 rounded-2xl border border-white/5">
            <button @click="filterStatus = 'all'" :class="filterStatus === 'all' ? 'bg-indigo-600 text-white font-black' : 'text-slate-400 font-bold'" class="py-3 text-xs rounded-xl transition-all flex flex-col items-center gap-0.5">
                <span class="text-sm font-black" x-text="counts.all">0</span> <span class="text-[9px] opacity-80">الكل</span>
            </button>
            <button @click="filterStatus = 'boarded'" :class="filterStatus === 'boarded' ? 'bg-emerald-600 text-white font-black' : 'text-slate-400 font-bold'" class="py-3 text-xs rounded-xl transition-all flex flex-col items-center gap-0.5">
                <span class="text-sm font-black" x-text="counts.boarded">0</span> <span class="text-[9px] opacity-80">على المتن</span>
            </button>
            <button @click="filterStatus = 'pending'" :class="filterStatus === 'pending' ? 'bg-amber-600 text-white font-black' : 'text-slate-400 font-bold'" class="py-3 text-xs rounded-xl transition-all flex flex-col items-center gap-0.5">
                <span class="text-sm font-black" x-text="counts.pending">0</span> <span class="text-[9px] opacity-80">لم يصعد</span>
            </button>
        </div>

        <!-- 3. شريط البحث -->
        <div class="relative">
            <input type="text" x-model="searchQuery" placeholder="ابحث باسم الراكب أو رقم المقعد..." class="w-full bg-slate-900 border border-white/5 rounded-2xl py-4 px-5 pr-12 font-bold text-xs text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 placeholder-slate-600 transition-all text-right">
            <span class="absolute right-5 top-1/2 -translate-y-1/2 text-base opacity-30">🔍</span>
        </div>

        <!-- 4. قائمة الأسماء والداتا التفاعلية -->
        <div class="space-y-3">
            <template x-for="passenger in filteredPassengers" :key="passenger.id">
                <div class="bg-slate-900 border border-white/5 p-4 rounded-3xl flex justify-between items-center flex-row-reverse text-right">
                    <div class="flex items-center gap-4 flex-row-reverse">
                        <div class="w-12 h-12 rounded-2xl flex flex-col items-center justify-center border font-black" :class="passenger.status === 'boarded' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-slate-800 text-indigo-400 border-white/5'">
                            <span class="text-[8px] opacity-60 uppercase font-bold">مقعد</span>
                            <span class="text-sm -mt-1 italic" x-text="passenger.seat"></span>
                        </div>
                        <div>
                            <h4 class="font-black text-sm text-white" x-text="passenger.name"></h4>
                            <div class="flex items-center gap-3 flex-row-reverse mt-1">
                                <span class="text-[10px] text-slate-500 font-bold" x-text="passenger.phone"></span>
                                <span class="text-[10px] text-slate-500 font-bold" x-text="'تذكرة #' + passenger.id"></span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span x-show="passenger.status === 'boarded'" class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 py-1.5 rounded-xl text-[10px] font-black italic">✓ على المتن</span>
                        <span x-show="passenger.status === 'pending'" class="bg-amber-500/10 text-amber-500 border border-amber-500/20 px-3 py-1.5 rounded-xl text-[10px] font-black italic">⏱️ متأخر</span>
                        <span x-show="passenger.status === 'absent'" class="bg-red-500/10 text-red-500 border border-red-500/20 px-3 py-1.5 rounded-xl text-[10px] font-black italic">❌ غائب</span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- [ثانياً] واجهة إتمام الرحلة (تظهر فوراً عند الضغط على إنهاء) -->
    <div x-show="tripStatus === 'completed'"
         x-transition:enter="transition ease-out duration-500"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="text-center py-12 px-6 bg-slate-900 rounded-[3rem] border border-white/10 space-y-6 max-w-sm mx-auto shadow-2xl">

        <div class="w-20 h-20 bg-emerald-500/10 text-emerald-400 rounded-full flex items-center justify-center text-4xl mx-auto shadow-xl shadow-emerald-500/5 animate-pulse">
            🏁
        </div>

        <div class="space-y-2">
            <h3 class="text-2xl font-black italic text-white">الحمد لله على السلامة!</h3>
            <p class="text-xs text-slate-400 font-bold">تم إغلاق وتأكيد وصول الرحلة #TRP-902 بنجاح</p>
        </div>

        <!-- إحصائيات الرحلة المغلقة كتقرير سريع للسائق -->
        <div class="grid grid-cols-2 gap-2 bg-slate-950 p-4 rounded-2xl text-right border border-white/5">
            <div>
                <p class="text-[9px] font-bold text-slate-500">إجمالي الركاب المتوقع</p>
                <p class="text-base font-black text-white" x-text="counts.all + ' ركاب'"></p>
            </div>
            <div>
                <p class="text-[9px] font-bold text-emerald-500">الذين صعدوا وتأكدوا</p>
                <p class="text-base font-black text-emerald-400" x-text="counts.boarded + ' ركاب'"></p>
            </div>
        </div>

        <p class="text-[10px] text-amber-400 font-bold bg-amber-500/5 py-2.5 px-4 rounded-xl border border-amber-500/10">⚠️ تم رفع تقرير الحضور تلقائياً لغرفة العمليات بالإدارة.</p>

        <!-- الأزرار التوجيهية للخطوة التالية -->
        <div class="space-y-2 pt-4">
            <a href="#" class="block w-full bg-indigo-600 hover:bg-indigo-700 py-4 rounded-2xl font-black text-xs italic transition-all">
                🔄 جلب رحلتي التالية للوجبة
            </a>
            <button @click="tripStatus = 'active'" class="block w-full bg-slate-800 hover:bg-slate-700 py-4 rounded-2xl font-black text-xs text-slate-400 italic transition-all">
                ⬅ العودة لمراجعة البيانات
            </button>
        </div>
    </div>

</div>

<style>
    body { overscroll-behavior-y: contain; }
</style>
@endsection
