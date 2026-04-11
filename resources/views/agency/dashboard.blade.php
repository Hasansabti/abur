@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<div class="container mx-auto px-6 py-10 max-w-[1600px] animate-fade-in"
     x-data="{ openAddDriver: false, openAddBus: false }">

    <div class="flex flex-col lg:flex-row justify-between items-center mb-10 gap-6 bg-white p-6 rounded-[3rem] shadow-sm border border-slate-50">
        <div class="flex items-center gap-6">
            <div class="w-14 h-14 bg-slate-900 rounded-3xl flex items-center justify-center text-2xl shadow-xl shadow-slate-200">📊</div>
            <div class="text-right">
                <h1 class="text-2xl font-black text-slate-900 italic tracking-tighter leading-tight">مركز القيادة والتحكم</h1>
                <p class="text-[9px] text-slate-400 font-black uppercase tracking-[0.3em] mt-1 italic">Obour Multi-Modal System v4.5</p>
            </div>
        </div>

        <div class="flex items-center gap-4 bg-slate-50 p-2 rounded-[2rem] border border-slate-100">
            <button @click="openAddBus = true"
                    class="bg-indigo-600 text-white px-6 py-3 rounded-2xl font-black text-[10px] italic hover:bg-slate-900 transition-all shadow-lg flex items-center gap-2 uppercase tracking-widest">
                <span>إضافة حافلة</span>
                <span class="text-lg leading-none">🚌</span>
            </button>

            <button @click="openAddDriver = true"
                    class="bg-slate-900 text-white px-6 py-3 rounded-2xl font-black text-[10px] italic hover:bg-indigo-600 transition-all shadow-lg flex items-center gap-2 uppercase tracking-widest">
                <span>تسجيل كابتن</span>
                <span class="text-lg leading-none">+</span>
            </button>

            <div class="px-6 border-r border-slate-200 text-right">
                <p class="text-[8px] text-slate-400 font-black uppercase italic">حالة النظام</p>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="text-xs font-black italic text-slate-700">متصل (Live)</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <div class="lg:col-span-8 order-1">
            <div class="bg-white rounded-[4rem] p-4 shadow-2xl border border-slate-100 h-[700px] relative overflow-hidden group">
                <div id="map" class="w-full h-full rounded-[3.5rem] z-10"></div>
                <div class="absolute top-10 left-10 z-[1000] flex flex-col gap-2">
                    <button onclick="map.zoomIn()" class="w-12 h-12 bg-white rounded-2xl shadow-xl flex items-center justify-center hover:bg-slate-900 hover:text-white transition-all text-sm">➕</button>
                    <button onclick="map.zoomOut()" class="w-12 h-12 bg-white rounded-2xl shadow-xl flex items-center justify-center hover:bg-slate-900 hover:text-white transition-all text-sm">➖</button>
                </div>
            </div>
        </div>

        <div class="lg:col-span-4 space-y-6 order-2">
            <div class="bg-slate-900 rounded-[3rem] p-8 text-white shadow-2xl text-right">
                <h3 class="text-lg font-black italic mb-4">الأسطول النشط 📡</h3>
                <div class="flex justify-between items-center bg-white/5 p-4 rounded-2xl">
                    <span class="text-xs font-black italic opacity-60">إجمالي المركبات</span>
                    <span class="text-xl font-black italic">24</span>
                </div>
            </div>

            <div class="bg-white rounded-[3rem] p-8 border border-slate-50 shadow-sm max-h-[450px] overflow-y-auto custom-scrollbar text-right">
                <h3 class="text-sm font-black italic mb-6 border-b border-slate-50 pb-4">قائمة الرحلات الحالية</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl hover:bg-indigo-50 transition-all cursor-pointer border border-transparent hover:border-indigo-100">
                        <span class="text-[9px] font-black italic text-indigo-600 uppercase">في الطريق</span>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p class="text-xs font-black italic leading-tight">BUS-X20</p>
                                <p class="text-[8px] font-bold opacity-60 italic text-slate-400">الرياض ⮕ الدمام</p>
                            </div>
                            <span class="text-xl">🚌</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="openAddBus" style="display: none;" class="fixed inset-0 z-[2000] flex items-center justify-center bg-slate-900/80 backdrop-blur-md p-4" x-cloak>
        <div @click.away="openAddBus = false" class="bg-white w-full max-w-2xl rounded-[3rem] shadow-2xl overflow-hidden animate-scale-up">
            <div class="bg-indigo-600 p-8 text-white flex justify-between items-center flex-row-reverse text-right">
                <div>
                    <h2 class="text-2xl font-black italic tracking-tighter leading-tight">تسجيل حافلة جديدة 🚌</h2>
                    <p class="text-[9px] font-black uppercase tracking-widest mt-1 opacity-80">توسيع أسطول الوكالة</p>
                </div>
                <button @click="openAddBus = false" class="text-white/50 hover:text-white transition-colors text-2xl">✕</button>
            </div>
            <form class="p-10 grid grid-cols-1 md:grid-cols-2 gap-6 text-right">
                <div class="space-y-4 text-right">
                    <label class="block text-[10px] font-black text-slate-400 uppercase italic mr-2">رقم اللوحة</label>
                    <input type="text" placeholder="أ ب ج 1234" class="w-full bg-slate-50 border-none rounded-2xl py-4 px-6 font-bold text-sm focus:ring-2 focus:ring-indigo-500 outline-none text-center italic">

                    <label class="block text-[10px] font-black text-slate-400 uppercase italic mr-2">عدد المقاعد</label>
                    <input type="number" placeholder="50" class="w-full bg-slate-50 border-none rounded-2xl py-4 px-6 font-bold text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="space-y-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase italic mr-2">موديل الحافلة</label>
                    <input type="text" placeholder="Mercedes-Benz Travego" class="w-full bg-slate-50 border-none rounded-2xl py-4 px-6 font-bold text-sm focus:ring-2 focus:ring-indigo-500 outline-none">

                    <label class="block text-[10px] font-black text-slate-400 uppercase italic mr-2">تاريخ الفحص الدوري</label>
                    <input type="date" class="w-full bg-slate-50 border-none rounded-2xl py-4 px-6 font-bold text-sm focus:ring-2 focus:ring-indigo-500 outline-none text-slate-400">
                </div>
                <div class="md:col-span-2 flex gap-4 mt-6">
                    <button type="submit" class="flex-grow bg-indigo-600 text-white py-5 rounded-2xl font-black text-xs uppercase tracking-widest italic shadow-xl">تسجيل المركبة في النظام</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="openAddDriver" style="display: none;" class="fixed inset-0 z-[2000] flex items-center justify-center bg-slate-900/80 backdrop-blur-md p-4" x-cloak>
        <div @click.away="openAddDriver = false" class="bg-white w-full max-w-2xl rounded-[3rem] shadow-2xl overflow-hidden animate-scale-up">
            <div class="bg-slate-900 p-8 text-white flex justify-between items-center flex-row-reverse text-right">
                <div>
                    <h2 class="text-2xl font-black italic tracking-tighter leading-tight">تسجيل كابتن جديد 👨‍✈️</h2>
                    <p class="text-[9px] font-black uppercase tracking-widest mt-1 opacity-60">إضافة فرد جديد للطاقم</p>
                </div>
                <button @click="openAddDriver = false" class="text-white/30 hover:text-white transition-colors text-2xl">✕</button>
            </div>
            <form class="p-10 grid grid-cols-1 md:grid-cols-2 gap-6 text-right">
                <div class="space-y-4 text-right">
                    <label class="block text-[10px] font-black text-slate-400 uppercase italic mr-2">الاسم الكامل</label>
                    <input type="text" placeholder="الاسم الثلاثي" class="w-full bg-slate-50 border-none rounded-2xl py-4 px-6 font-bold text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <label class="block text-[10px] font-black text-slate-400 uppercase italic mr-2">رقم الجوال</label>
                    <input type="tel" placeholder="05xxxxxxxx" class="w-full bg-slate-50 border-none rounded-2xl py-4 px-6 font-bold text-sm focus:ring-2 focus:ring-indigo-500 outline-none text-left">
                </div>
                <div class="space-y-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase italic mr-2">رقم الرخصة</label>
                    <input type="text" placeholder="10xxxxxxxx" class="w-full bg-slate-50 border-none rounded-2xl py-4 px-6 font-bold text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <label class="block text-[10px] font-black text-slate-400 uppercase italic mr-2">تاريخ انتهاء الرخصة</label>
                    <input type="date" class="w-full bg-slate-50 border-none rounded-2xl py-4 px-6 font-bold text-sm focus:ring-2 focus:ring-indigo-500 outline-none text-slate-400">
                </div>
                <div class="md:col-span-2 flex gap-4 mt-6">
                    <button type="submit" class="flex-grow bg-slate-900 text-white py-5 rounded-2xl font-black text-xs uppercase tracking-widest italic shadow-xl">حفظ البيانات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    #map { background: #f8fafc; cursor: crosshair; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .animate-scale-up { animation: scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    @keyframes scaleUp { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>

<script>
    var map;
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('map')) {
            map = L.map('map', { zoomControl: false }).setView([24.7136, 46.6753], 6);

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; Obour Pro'
            }).addTo(map);

            var busIcon = L.divIcon({
                className: 'custom-icon',
                html: `<div class="relative"><div class="absolute -inset-4 bg-indigo-500/20 rounded-full animate-ping"></div><div class="w-10 h-10 bg-slate-900 rounded-2xl border-4 border-white shadow-2xl flex items-center justify-center text-sm">🚌</div></div>`,
                iconSize: [40, 40]
            });

            L.marker([25.5, 48.5], {icon: busIcon}).addTo(map)
                .bindPopup("<div class='p-2 font-black italic text-xs text-right'>الرحلة: TRP-99<br>الحافلة: م ب ج 4432</div>");

            setTimeout(function(){ map.invalidateSize(); }, 400);
        }
    });
</script>
@endsection
