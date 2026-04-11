@extends('layouts.driver')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<div x-data="{
        tripStarted: false,
        emergency: false,
        showPassengers: false, {{-- حالة القائمة المنبثقة --}}
        passengers: [
            { id: 1, name: 'عبدالله محمد', seat: '1A', status: 'checked' },
            { id: 2, name: 'سارة العتيبي', seat: '4B', status: 'pending' },
            { id: 3, name: 'ياسر القحطاني', seat: '12C', status: 'pending' },
            { id: 4, name: 'نورة الدوسري', seat: '7A', status: 'checked' }
        ]
     }">

    <div class="grid grid-cols-3 gap-1 p-4 max-w-md mx-auto">
        <div class="bg-slate-900 p-4 rounded-3xl border border-white/5 text-center">
            <p class="text-[8px] text-slate-500 font-black uppercase mb-1 italic">السرعة</p>
            <div class="flex items-baseline justify-center gap-1">
                <span class="text-2xl font-black italic" x-text="tripStarted ? '95' : '0'">0</span>
                <span class="text-[8px] text-slate-400">كم/س</span>
            </div>
        </div>
        <div class="bg-slate-900 p-4 rounded-3xl border border-white/5 text-center">
            <p class="text-[8px] text-slate-500 font-black uppercase mb-1 italic">المتبقي</p>
            <div class="flex items-baseline justify-center gap-1">
                <span class="text-2xl font-black italic">1:45</span>
                <span class="text-[8px] text-slate-400">ساعة</span>
            </div>
        </div>
        <div class="bg-slate-900 p-4 rounded-3xl border border-white/5 text-center">
            <p class="text-[8px] text-slate-500 font-black uppercase mb-1 italic">الركاب</p>
            <div class="flex items-baseline justify-center gap-1">
                <span class="text-2xl font-black italic">24</span>
                <span class="text-[8px] text-slate-400">/50</span>
            </div>
        </div>
    </div>

    <div class="px-4 max-w-md mx-auto">
        <div class="h-[45vh] bg-slate-900 rounded-[3rem] border-4 border-slate-900 shadow-2xl relative overflow-hidden">
            <div id="driverMap" class="w-full h-full grayscale-[0.5] contrast-[1.2]"></div>
        </div>
    </div>

    <div class="p-6 max-w-md mx-auto space-y-4">
        <button @click="tripStarted = !tripStarted"
                :class="tripStarted ? 'bg-red-500 shadow-red-500/20' : 'bg-green-600 shadow-green-500/20'"
                class="w-full py-6 rounded-[2.5rem] text-xl font-black italic uppercase transition-all shadow-2xl flex items-center justify-center gap-4">
            <span x-text="tripStarted ? 'إنهاء الرحلة' : 'بدء الرحلة الآن'">بدء الرحلة</span>
            <span class="text-2xl" x-text="tripStarted ? '⏹️' : '🚀'"></span>
        </button>

        <div class="grid grid-cols-2 gap-4">
            <button @click="showPassengers = true" class="bg-slate-800 p-5 rounded-[2rem] border border-white/5 font-black text-xs italic flex flex-col items-center gap-2 active:bg-indigo-600 transition-all">
                <span class="text-2xl">👥</span>
                قائمة الركاب
            </button>

            <button @click="emergency = true" class="bg-red-500/10 p-5 rounded-[2rem] border border-red-500/20 font-black text-xs italic flex flex-col items-center gap-2 text-red-500">
                <span class="text-2xl animate-pulse">🚨</span>
                طوارئ
            </button>
        </div>
    </div>

    <div x-show="showPassengers"
         class="fixed inset-0 z-[3000] flex flex-col justify-end"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         x-cloak>

        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="showPassengers = false"></div>

        <div class="relative bg-slate-900 rounded-t-[3rem] border-t border-white/10 p-8 h-[70vh] overflow-y-auto">
            <div class="w-12 h-1.5 bg-slate-700 rounded-full mx-auto mb-8"></div>

            <div class="flex justify-between items-center mb-8 flex-row-reverse text-right">
                <div>
                    <h3 class="text-2xl font-black italic">تأكيد الركاب</h3>
                    <p class="text-xs text-slate-500 font-bold">رحلة الرياض - الدمام</p>
                </div>
                <button @click="showPassengers = false" class="bg-slate-800 w-10 h-10 rounded-full flex items-center justify-center text-xl">✕</button>
            </div>

            <div class="space-y-4">
                <template x-for="p in passengers" :key="p.id">
                    <div class="bg-white/5 p-5 rounded-3xl border border-white/5 flex justify-between items-center flex-row-reverse text-right">
                        <div class="flex items-center gap-4 flex-row-reverse">
                            <div class="w-12 h-12 bg-slate-800 rounded-2xl flex items-center justify-center font-black text-indigo-400" x-text="p.seat"></div>
                            <div>
                                <p class="font-black italic text-sm" x-text="p.name"></p>
                                <p class="text-[10px] text-slate-500 font-bold" x-text="'تذكرة: #TK-' + p.id + '00'"></p>
                            </div>
                        </div>

                        <button @click="p.status = (p.status === 'checked' ? 'pending' : 'checked')"
                                :class="p.status === 'checked' ? 'bg-green-500 text-white' : 'bg-slate-700 text-slate-400'"
                                class="w-10 h-10 rounded-full flex items-center justify-center transition-all">
                            <span x-show="p.status === 'checked'">✓</span>
                            <span x-show="p.status === 'pending'">+</span>
                        </button>
                    </div>
                </template>
            </div>

            <button @click="showPassengers = false" class="w-full mt-8 bg-indigo-600 py-5 rounded-2xl font-black italic uppercase shadow-xl shadow-indigo-500/20">تحديث الحالة</button>
        </div>
    </div>

</div>

<style>
    [x-cloak] { display: none !important; }
    #driverMap { background: #0f172a; }
    .leaflet-tile { filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%); }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var map = L.map('driverMap', { zoomControl: false }).setView([24.7136, 46.6753], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        var busMarker = L.divIcon({
            className: 'marker',
            html: `<div class="w-8 h-8 bg-white rounded-full border-4 border-indigo-600 shadow-2xl flex items-center justify-center text-sm">🚌</div>`
        });
        L.marker([24.7136, 46.6753], {icon: busMarker}).addTo(map);

        setTimeout(() => map.invalidateSize(), 500);
    });
</script>
@endsection
