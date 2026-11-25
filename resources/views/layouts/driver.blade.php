<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>كابتن عبور | @yield('title', 'الرئيسية')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }

        /* إخفاء شريط التمرير */
        ::-webkit-scrollbar {
            display: none;
        }

        /* منع سحب الصفحة للأسفل لتحديثها في الجوال (اختياري) */
        body {
            overscroll-behavior-y: contain;
        }

        .safe-area-bottom {
            padding-bottom: env(safe-area-inset-bottom);
        }
    </style>
    @yield('styles')
</head>

<body class="bg-slate-950 text-slate-100 antialiased overflow-x-hidden">

    <div class="fixed top-0 inset-x-0 z-[2000] bg-slate-950/80 backdrop-blur-md border-b border-white/5 px-6 py-4">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">نظام عبور v5.0</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold italic">كابتن فهد</span>
                <div class="w-8 h-8 bg-indigo-600 rounded-xl flex items-center justify-center text-sm shadow-lg">👨‍✈️
                </div>
            </div>
        </div>
    </div>

    <main class="pt-20 pb-32">
        @yield('content')
    </main>

    <nav
        class="fixed bottom-0 inset-x-0 z-[2000] bg-slate-900/90 backdrop-blur-2xl border-t border-white/5 safe-area-bottom">
        <div class="grid grid-cols-4 h-20">
            <a href="{{ route('driver.dashboard') }}"
                class="flex flex-col items-center justify-center gap-1 text-indigo-400">
                <span class="text-xl">🗺️</span>
                <span class="text-[10px] font-black italic">الرادار</span>
            </a>

            {{-- <a href="#" class="flex flex-col items-center justify-center gap-1 text-slate-500 hover:text-white transition-all">
                <span class="text-xl">📋</span>
                <span class="text-[10px] font-black italic">رحلاتي</span>
            </a> --}}

            {{-- <a href="#" class="flex flex-col items-center justify-center gap-1 text-slate-500 hover:text-white transition-all">
                <span class="text-xl">💰</span>
                <span class="text-[10px] font-black italic">الأرباح</span>
            </a> --}}
            <a href="{{ route('driver.driver.passinger') }}"
                class="flex flex-col items-center justify-center gap-1 text-slate-500 hover:text-white transition-all">
                <span class="text-xl">👤</span>
                <span class="text-[10px] font-black italic">الراكبين</span>
            </a>
            <a href="#"
                class="flex flex-col items-center justify-center gap-1 text-slate-500 hover:text-white transition-all">
                <span class="text-xl">⚙️</span>
                <span class="text-[10px] font-black italic">الإعدادات</span>
            </a>
        </div>
    </nav>

    @yield('scripts')
</body>

</html>
