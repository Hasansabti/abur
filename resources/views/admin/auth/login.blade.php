<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دخول الإدارة | عبور برو</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #020617; }
        .glass-card {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            /* تحديد البوردر بشكل أوضح وأنحف */
            border: 1px solid rgba(79, 70, 229, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .animate-glow {
            animation: glow 4s infinite alternate;
        }
        @keyframes glow {
            from { border-color: rgba(79, 70, 229, 0.2); }
            to { border-color: rgba(79, 70, 229, 0.6); }
        }
        .input-dark {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .input-dark:focus {
            border-color: #4f46e5;
            background: rgba(30, 41, 59, 0.8);
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center relative overflow-hidden bg-slate-950">

    <div class="absolute inset-0 z-0">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-600/20 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-blue-600/10 rounded-full blur-[120px]"></div>
    </div>

    <div class="relative z-10 w-full max-w-[420px] px-6 py-10" x-data="{ loading: false }">

        <div class="glass-card rounded-[2.5rem] p-8 md:p-10 animate-glow">

            <div class="text-center mb-8">
                <div class="relative inline-block mb-4">
                    <div class="absolute inset-0 bg-indigo-600 blur-xl opacity-30 rounded-full animate-pulse"></div>
                    <div class="relative w-20 h-20 bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-3xl flex items-center justify-center text-3xl shadow-2xl rotate-3">
                        <span class="drop-shadow-md">🛂</span>
                    </div>
                </div>

                <h1 class="text-2xl font-black text-white italic tracking-tighter">بوابة التحكم</h1>
                <p class="text-indigo-400 font-black text-[9px] uppercase tracking-[0.4em] italic mt-2 opacity-80">OBOUR ADMIN PRO</p>
            </div>

            <form action="{{ route('admin.auth.authenticate') }}" method="POST" @submit="loading = true" class="space-y-5">
                @csrf
                @if (session('error'))
                    <div class="rounded-xl bg-red-500/10 border border-red-400/20 text-red-300 text-xs font-bold px-4 py-3">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="group">
                    <label class="block text-[9px] font-black text-slate-500 uppercase mr-4 mb-2 italic tracking-widest">البريد الرسمي</label>
                    <div class="relative">
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="input-dark w-full rounded-2xl py-4 px-6 font-bold text-white placeholder-slate-600 transition-all outline-none italic text-sm"
                               placeholder="admin@obour.com">
                    </div>
                </div>

                <div class="group">
                    <label class="block text-[9px] font-black text-slate-500 uppercase mr-4 mb-2 italic tracking-widest">كلمة السر</label>
                    <div class="relative">
                        <input type="password" name="password" required
                               class="input-dark w-full rounded-2xl py-4 px-6 font-bold text-white placeholder-slate-600 transition-all outline-none italic text-sm"
                               placeholder="••••••••">
                    </div>
                </div>

                <button type="submit"
                        class="w-full relative overflow-hidden py-5 bg-indigo-600 text-white rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] italic hover:bg-indigo-500 transition-all shadow-xl shadow-indigo-900/40 mt-4 active:scale-95">

                    <div x-show="!loading" class="flex items-center justify-center gap-2">
                        <span>دخول النظام</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </div>

                    <div x-show="loading" class="flex items-center justify-center" x-cloak>
                        <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </button>
            </form>

            <div class="mt-8 text-center border-t border-white/5 pt-6">
                <p class="text-[8px] text-slate-600 font-black uppercase tracking-widest italic leading-relaxed">
                    جميع الحقوق محفوظة لمنصة عبور <br>
                    <span class="text-indigo-900/50">SECURE ENCRYPTION ACTIVE</span>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
