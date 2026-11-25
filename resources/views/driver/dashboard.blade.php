@extends('layouts.driver')

@section('content')
<!-- المكتبات الأساسية وخريطة السائق -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<div x-data="{
        tripStarted: false,
        emergency: false,
        showScanner: false,
        passengersCount: 24,

        // حالات الـ API الجديد
        scanStatus: 'idle', // idle, checking, success, duplicate, error
        apiMessage: '',
        scannedTicket: '',

        // دالة إصدار أصوات تنبيهية مختلفة حسب حالة التذكرة
        playAudioFeedback(type) {
            let audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            let oscillator = audioCtx.createOscillator();
            let gainNode = audioCtx.createGain();
            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);

            if (type === 'success') {
                oscillator.frequency.setValueAtTime(800, audioCtx.currentTime); // صوت حاد وقصير للنجاح
                oscillator.start(); oscillator.stop(audioCtx.currentTime + 0.12);
            } else {
                oscillator.frequency.setValueAtTime(250, audioCtx.currentTime); // صوت بمقاطعة منخفضة للخطأ
                oscillator.start(); oscillator.stop(audioCtx.currentTime + 0.3);
            }
        },

        // إرسال البيانات للـ API والتحقق منها حياً
        async verifyTicketWithApi(ticketId) {
            this.scanStatus = 'checking';
            this.scannedTicket = ticketId;

            try {
                // إرسال طلب لـ لارافيل مع الـ CSRF Token لحماية الطلب
                let response = await fetch('/api/driver/verify-ticket', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        ticket_id: ticketId,
                        trip_id: 'TRP-902' // إرسال رقم الرحلة الحالية لمطابقتها
                    })
                });

                let result = await response.json();

                if (response.status === 200 && result.valid) {
                    this.scanStatus = 'success';
                    this.apiMessage = `تم قبول الراكب: ${result.passenger_name}`;
                    this.passengersCount = result.current_count; // تحديث العداد من السيرفر مباشرة
                    this.playAudioFeedback('success');

                    // إغلاق تلقائي بعد النجاح
                    setTimeout(() => { this.closeScanner(); }, 2000);

                } else if (response.status === 409) {
                    this.scanStatus = 'duplicate';
                    this.apiMessage = 'هذه التذكرة تم مسحها مسبقاً!';
                    this.playAudioFeedback('error');
                } else {
                    this.scanStatus = 'error';
                    this.apiMessage = result.message || 'تذكرة غير صالحة لهذه الرحلة!';
                    this.playAudioFeedback('error');
                }

            } catch (error) {
                this.scanStatus = 'error';
                this.apiMessage = 'فشل الاتصال بالخادم، تحقق من الإنترنت';
                this.playAudioFeedback('error');
            }
        },

        startScanner() {
            this.showScanner = true;
            this.scanStatus = 'idle';
            this.apiMessage = '';

            this.$nextTick(() => {
                window.html5QrCode = new Html5Qrcode('reader');
                window.html5QrCode.start(
                    { facingMode: 'environment' },
                    { fps: 12, qrbox: { width: 230, height: 230 } },
                    (decodedText) => {
                        // إيقاف التكرار المؤقت أثناء فحص التذكرة الحالية
                        if(this.scanStatus === 'idle') {
                            this.verifyTicketWithApi(decodedText);
                        }
                    },
                    (errorMessage) => { }
                ).catch(err => console.error(err));
            });
        },

        closeScanner() {
            this.showScanner = false;
            if (window.html5QrCode) {
                window.html5QrCode.stop().then(() => { window.html5QrCode.clear(); }).catch(err => console.error(err));
            }
        }
     }">

    <!-- العدادات والخريطة والتحكم (تظل كما هي لراحة التصميم) -->
    <div class="grid grid-cols-3 gap-1 p-4 max-w-md mx-auto">
        <div class="bg-slate-900 p-4 rounded-3xl border border-white/5 text-center">
            <p class="text-[8px] text-slate-500 font-black uppercase mb-1 italic">السرعة</p>
            <div class="flex items-baseline justify-center gap-1"><span class="text-2xl font-black italic" x-text="tripStarted ? '95' : '0'">0</span><span class="text-[8px] text-slate-400">كم/س</span></div>
        </div>
        <div class="bg-slate-900 p-4 rounded-3xl border border-white/5 text-center">
            <p class="text-[8px] text-slate-500 font-black uppercase mb-1 italic">المتبقي</p>
            <div class="flex items-baseline justify-center gap-1"><span class="text-2xl font-black italic">1:45</span><span class="text-[8px] text-slate-400">ساعة</span></div>
        </div>
        <div class="bg-slate-900 p-4 rounded-3xl border border-white/5 text-center">
            <p class="text-[8px] text-slate-500 font-black uppercase mb-1 italic">على متن الحافلة</p>
            <div class="flex items-baseline justify-center gap-1"><span class="text-2xl font-black italic text-indigo-400" x-text="passengersCount">24</span><span class="text-[8px] text-slate-400">/50</span></div>
        </div>
    </div>

    <div class="px-4 max-w-md mx-auto">
        <div class="h-[45vh] bg-slate-900 rounded-[3rem] border-4 border-slate-900 relative overflow-hidden">
            <div id="driverMap" class="w-full h-full grayscale-[0.5]"></div>
        </div>
    </div>

    <div class="p-6 max-w-md mx-auto space-y-4">
        {{-- <button @click="tripStarted = !tripStarted" :class="tripStarted ? 'bg-red-500' : 'bg-green-600'" class="w-full py-6 rounded-[2.5rem] text-xl font-black italic flex items-center justify-center gap-4">
            <span x-text="tripStarted ? 'إنهاء الرحلة' : 'بدء الرحلة الآن'">بدء الرحلة</span>
        </button> --}}

        <div class="grid grid-cols-1 gap-4">
            <button @click="startScanner()" class="bg-indigo-600 text-white p-5 rounded-[2rem] font-black text-xs flex flex-col items-center gap-2 shadow-xl shadow-indigo-600/10">
                <span class="text-2xl">📷</span> مسح تذاكر الركاب
            </button>
            {{-- <button @click="emergency = true" class="bg-red-500/10 p-5 rounded-[2rem] border border-red-500/20 font-black text-xs text-red-500"><span class="text-2xl animate-pulse">🚨</span> طوارئ</button> --}}
        </div>
    </div>

    <!-- نافذة الماسح الضوئي (محدثة بالكامل لعرض رد الـ API التفاعلي) -->
    <div x-show="showScanner" class="fixed inset-0 z-[3000] flex flex-col justify-end" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full" x-cloak>
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md" @click="closeScanner()"></div>
        <div class="relative bg-slate-900 rounded-t-[3.5rem] border-t border-white/10 p-8 h-[82vh] flex flex-col items-center">
            <div class="w-12 h-1.5 bg-slate-700 rounded-full mb-6"></div>

            <div class="w-full flex justify-between items-center mb-6 flex-row-reverse text-right">
                <div>
                    <h3 class="text-xl font-black italic">قارئ الـ QR المباشر</h3>
                    <p class="text-[10px] text-slate-400 font-bold">يتم التحقق أمنياً عبر سيرفر عبور</p>
                </div>
                <button @click="closeScanner()" class="bg-slate-800 w-10 h-10 rounded-full flex items-center justify-center text-sm">✕</button>
            </div>

            <!-- العدسة -->
            <div class="w-full max-w-sm aspect-square bg-black rounded-[2.5rem] overflow-hidden border-2 border-dashed border-indigo-500/50 p-2 relative">
                <div id="reader" class="w-full h-full rounded-2xl overflow-hidden"></div>
                <div x-show="scanStatus === 'idle'" class="absolute left-0 right-0 h-0.5 bg-indigo-500 top-0 shadow-lg shadow-indigo-500 animate-[scan_2s_linear_infinite]"></div>
            </div>

            <!-- صندوق الرد والتحقق الديناميكي -->
            <div class="w-full max-w-sm mt-6">
                <!-- 1. حالة جاري الفحص -->
                <div x-show="scanStatus === 'checking'" class="bg-indigo-500/10 border border-indigo-500/20 p-4 rounded-2xl text-center text-indigo-400 font-bold text-xs flex items-center justify-center gap-3">
                    <div class="w-4 h-4 border-2 border-indigo-400 border-t-transparent rounded-full animate-spin"></div>
                    <span>جاري التحقق من صلاحية التذكرة رقم (<span x-text="scannedTicket"></span>)...</span>
                </div>

                <!-- 2. حالة تذكرة صالحة ودخول ناجح -->
                <div x-show="scanStatus === 'success'" class="bg-emerald-500/10 border border-emerald-500/30 p-4 rounded-2xl text-center text-emerald-400 font-black italic text-xs flex items-center justify-center gap-2">
                    <span>🎉 تفضل بالصعود | </span> <span x-text="apiMessage"></span>
                </div>

                <!-- 3. حالة تذكرة مكررة الاستخدام -->
                <div x-show="scanStatus === 'duplicate'" class="bg-amber-500/10 border border-amber-500/30 p-4 rounded-2xl text-center text-amber-500 font-black italic text-xs flex items-center justify-center gap-2">
                    <span>⚠️ تنبيه:</span> <span x-text="apiMessage"></span>
                </div>

                <!-- 4. حالة تذكرة خاطئة أو منتهية -->
                <div x-show="scanStatus === 'error'" class="bg-red-500/10 border border-red-500/30 p-4 rounded-2xl text-center text-red-500 font-black italic text-xs flex items-center justify-center gap-2">
                    <span>🛑 مرفوض:</span> <span x-text="apiMessage"></span>
                </div>
            </div>

            <!-- زر تصفير القارئ لإعادة المحاولة عند الخطأ بسرعة دون إغلاق الكاميرا -->
            <button x-show="scanStatus !== 'idle' && scanStatus !== 'checking'" @click="scanStatus = 'idle'" class="w-full max-w-sm mt-4 bg-indigo-600/20 text-indigo-400 py-3 rounded-xl font-black text-[11px] uppercase tracking-widest">إعادة مسح تذكرة أخرى 🔄</button>

            <button @click="closeScanner()" class="w-full max-w-sm mt-auto bg-slate-800 py-4 rounded-2xl font-black text-xs italic">إلغاء والعودة للخريطة</button>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    #driverMap { background: #0f172a; }
    .leaflet-tile { filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%); }
    @keyframes scan { 0% { top: 5%; } 50% { top: 95%; } 100% { top: 5%; } }
    #reader video { object-fit: cover !important; width: 100% !important; height: 100% !important; }
    #reader__dashboard { display: none !important; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var map = L.map('driverMap', { zoomControl: false }).setView([24.7136, 46.6753], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        var busMarker = L.divIcon({ className: 'marker', html: `<div class="w-8 h-8 bg-white rounded-full border-4 border-indigo-600 flex items-center justify-center text-sm">🚌</div>` });
        L.marker([24.7136, 46.6753], {icon: busMarker}).addTo(map);
        setTimeout(() => map.invalidateSize(), 500);
    });
</script>
@endsection
