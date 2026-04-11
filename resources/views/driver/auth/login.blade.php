<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دخول السائق | عبور</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: #ffffff;
        }

        .input-focus:focus {
            border-color: #4f46e5;
            background: white;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center justify-center p-6 bg-slate-50">

    <div class="mb-10 text-center">
        <div
            class="w-20 h-20 bg-indigo-600 rounded-[2rem] flex items-center justify-center text-3xl mx-auto shadow-2xl shadow-indigo-100 mb-4 rotate-3">
            <span class="text-white">👨‍✈️</span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 italic">مرحباً بك، كابتن</h1>
        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">سجل دخولك لبدء الرحلة</p>
    </div>

    <div class="w-full max-w-[400px] bg-white rounded-[3rem] p-8 shadow-xl border border-slate-50"
        x-data="{ loading: false }">

        <form action="#" method="POST"
            @submit.prevent="loading = true; setTimeout(() => { window.location.href='/driver/dashboard' }, 1000)"
            class="space-y-6">

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase mr-4 mb-2 italic">رقم الجوال أو ID
                    السائق</label>
                <div class="relative">
                    <input type="text" name="driver_id" required
                        class="w-full bg-slate-50 border-2 border-transparent rounded-2xl py-4 px-6 font-bold text-slate-900 placeholder-slate-300 focus:bg-white focus:border-indigo-100 transition-all outline-none italic"
                        placeholder="05xxxxxxxx">
                    <span class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-300">📱</span>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase mr-4 mb-2 italic">كلمة
                    المرور</label>
                <div class="relative">
                    <input type="password" name="password" required
                        class="w-full bg-slate-50 border-2 border-transparent rounded-2xl py-4 px-6 font-bold text-slate-900 placeholder-slate-300 focus:bg-white focus:border-indigo-100 transition-all outline-none italic"
                        placeholder="••••••">
                    <span class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-300">🔒</span>
                </div>
            </div>

            <a href="{{ route('driver.dashboard') }}"
                class="w-full py-5 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-[0.2em] italic hover:bg-indigo-600 transition-all shadow-xl mt-2 flex items-center justify-center gap-3">

                <span x-show="!loading">تسجيل الدخول وبدء العمل</span>

                <div x-show="loading" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin">
                </div>

            </a>

            <a href="#"
                class="block text-center text-[10px] font-bold text-slate-400 hover:text-indigo-600 transition-colors uppercase italic">نسيت
                كلمة السر؟</a>
        </form>
    </div>

    <div class="mt-12 flex items-center gap-2 opacity-30">
        <div class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></div>
        <p class="text-[9px] font-black text-slate-900 uppercase italic">الربط مع GPS مفعل</p>
    </div>

</body>

</html>
