<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قيد الإنشاء | عبور</title>
    <!-- تضمين Tailwind CSS للمظهر الاحترافي -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- تضمين Alpine.js للمنطق الحي والعداد التنازلي -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- خطوط جوجل للحصول على خط عربي متناسق -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;700;900&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
        .bg-gradient {
            background: radial-gradient(circle at top center, #1e40af 0%, #0f172a 100%);
        }
        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            border: 1px rgba(255, 255, 255, 0.08) solid;
        }
    </style>
</head>
<body class="bg-gradient text-slate-100 min-h-screen flex flex-col justify-between overflow-x-hidden">

    <!-- شريط علوي خفيف -->
    <header class="container mx-auto px-6 py-6 flex justify-between items-center relative z-10">
        <div class="flex items-center gap-2">
            <span class="text-2xl">🚀</span>
            <span class="text-xl font-black tracking-tight text-white">عُبُور <span class="text-blue-400 text-xs font-bold bg-blue-500/10 px-2 py-0.5 rounded-full">Abour</span></span>
        </div>
        <div class="text-xs font-bold text-slate-400 border border-slate-700/50 px-3 py-1 rounded-full bg-slate-800/30">
            الإصدار 1.0
        </div>
    </header>

    <!-- المحتوى الرئيسي للموقع -->
    <main class="container mx-auto px-4 py-8 flex flex-col items-center justify-center text-center flex-grow relative z-10"
          x-data="countdownApp()">
        
        <!-- وسم تفاعلي علوي متحرك -->
        <div class="inline-flex items-center gap-2 bg-blue-500/10 border border-blue-500/20 px-4 py-1.5 rounded-full text-blue-400 text-xs font-black mb-6 animate-pulse">
            <span class="w-2 h-2 bg-blue-400 rounded-full"></span>
            نعمل على بناء شيء مذهل من أجلكم!
        </div>

        <!-- العنوان الرئيسي المنسق للجوال والكمبيوتر -->
        <h1 class="text-4xl md:text-6xl font-black tracking-tight text-white mb-4 max-w-2xl leading-tight">
            موقعنا حالياً <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-300">قيد الإنشاء</span> 🛠️
        </h1>
        
        <p class="text-sm md:text-base text-slate-400 font-medium max-w-md mb-12 leading-relaxed">
            نحن نضع اللمسات الأخيرة على منصتكم المفضلة لتنسيق وإدارة رحلاتكم السياحية بكل سهولة وأمان. انتظرونا قريباً!
        </p>

        <!-- صناديق العداد التنازلي الحي (تتجاوب لتكون بجانب بعضها بالجوال) -->
        <div class="grid grid-cols-4 gap-3 md:gap-6 max-w-xl w-full mb-12">
            <!-- الأيام (مثبتة على 10 بناءً على طلبك) -->
            <div class="glass rounded-2xl md:rounded-[2rem] p-3 md:p-6 flex flex-col items-center justify-center shadow-lg border-blue-500/10">
                <span class="text-2xl md:text-5xl font-black text-white font-mono">10</span>
                <span class="text-[10px] md:text-xs font-bold text-slate-400 mt-1 md:mt-2">يوم</span>
            </div>
            <!-- الساعات -->
            <div class="glass rounded-2xl md:rounded-[2rem] p-3 md:p-6 flex flex-col items-center justify-center shadow-lg">
                <span class="text-2xl md:text-5xl font-black text-white font-mono" x-text="hours">00</span>
                <span class="text-[10px] md:text-xs font-bold text-slate-400 mt-1 md:mt-2">ساعة</span>
            </div>
            <!-- الدقائق -->
            <div class="glass rounded-2xl md:rounded-[2rem] p-3 md:p-6 flex flex-col items-center justify-center shadow-lg">
                <span class="text-2xl md:text-5xl font-black text-white font-mono" x-text="minutes">00</span>
                <span class="text-[10px] md:text-xs font-bold text-slate-400 mt-1 md:mt-2">دقيقة</span>
            </div>
            <!-- الثواني -->
            <div class="glass rounded-2xl md:rounded-[2rem] p-3 md:p-6 flex flex-col items-center justify-center shadow-lg border-blue-500/20">
                <span class="text-2xl md:text-5xl font-black text-blue-400 font-mono" x-text="seconds">00</span>
                <span class="text-[10px] md:text-xs font-bold text-slate-400 mt-1 md:mt-2">ثانية</span>
            </div>
        </div>

    </main>

    <!-- تذييل الصفحة اللطيف بدون أي روابط خارجية -->
    <footer class="container mx-auto px-6 py-8 text-center text-xs text-slate-500 font-medium relative z-10 border-t border-slate-800/40">
        <p>&copy; 2026 منصة عبور لخدمات النقل والسياحة. جميع الحقوق محفوظة.</p>
    </footer>

    <!-- منطق العداد التنازلي عبر Alpine.js لتحديث الساعات والدقائق والثواني تلقائياً -->
    <script>
        function countdownApp() {
            return {
                hours: '00',
                minutes: '00',
                seconds: '00',
                
                init() {
                    this.updateCountdown();
                    setInterval(() => this.updateCountdown(), 1000);
                },
                
                updateCountdown() {
                    const now = new Date();
                    
                    // حساب الساعات والدقائق والثواني المتبقية حتى نهاية اليوم الحالي كمثال ديناميكي حي متحرك دائماً
                    const h = 23 - now.getHours();
                    const m = 59 - now.getMinutes();
                    const s = 59 - now.getSeconds();
                    
                    // إضافة صفر على اليسار إذا كان الرقم أقل من 10 للمظهر الجمالي الموحد
                    this.hours = h < 10 ? '0' + h : h;
                    this.minutes = m < 10 ? '0' + m : m;
                    this.seconds = s < 10 ? '0' + s : s;
                }
            }
        }
    </script>
</body>
</html>