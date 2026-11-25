<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عطلاً في السيرفر 500 | منصة عبور</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-lg w-full bg-white rounded-3xl shadow-xl border border-slate-100 p-8 text-center relative overflow-hidden"
         x-data="{ refreshing: false }">
        
        <!-- خلفية تزيينية -->
        <div class="absolute -top-12 -right-12 w-40 h-40 bg-amber-50 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -bottom-12 -left-12 w-40 h-40 bg-indigo-50 rounded-full blur-2xl pointer-events-none"></div>

        <!-- شعار/رمز الرحلات والخطأ -->
        <div class="relative mb-6 inline-flex items-center justify-center">
            <div class="w-24 h-24 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center text-4xl shadow-inner border border-amber-200/50">
                🚌
            </div>
            <span class="absolute -top-2 -right-2 bg-rose-600 text-white font-mono text-xs font-black px-2.5 py-1 rounded-full border-2 border-white shadow-sm">
                500 Error
            </span>
        </div>

        <!-- النصوص الرئيسية -->
        <h1 class="text-2xl md:text-3xl font-black text-slate-900 mb-3 tracking-tight">
            توقفت المحركات موقتاً! 🛠️
        </h1>
        
        <p class="text-slate-500 text-sm font-bold leading-relaxed mb-8">
            واجهت منصة <span class="text-indigo-600 font-black">عبور</span> مشكلة تقنية غير متوقعة في السيرفر. فريقنا الفني يعمل الآن على إصلاح العطل لتستكمل رحلتك بسلاسة.
        </p>

        <!-- أزرار الإجراءات -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <!-- زر إعادة التحديث -->
            <button @click="refreshing = true; setTimeout(() => window.location.reload(), 500)"
                :disabled="refreshing"
                class="w-full sm:w-auto px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black transition-all shadow-lg shadow-indigo-600/20 active:scale-95 flex items-center justify-center gap-2">
                <svg x-show="!refreshing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <svg x-show="refreshing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" x-cloak>
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="refreshing ? 'جاري التحديث...' : 'تحديث الصفحة 🔄'"></span>
            </button>

            <!-- زر العودة للرئيسية -->
            <a href="{{ url('/') }}" 
               class="w-full sm:w-auto px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-black transition-all active:scale-95 flex items-center justify-center gap-2">
               🏠 الرئيسية
            </a>
        </div>

        <!-- تذييل الصفحة -->
        <div class="mt-8 pt-6 border-t border-slate-100 text-xs font-bold text-slate-400">
            رحلة/حجز متعطل؟ يمكنك التواصل مع 
            <a href="{{ route('client.tickets.index2') }}" class="text-indigo-600 hover:underline">مركز الدعم</a>
        </div>

    </div>

</body>
</html>