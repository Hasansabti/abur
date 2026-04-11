<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | عبور</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50">

    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="bg-white w-full max-w-md p-10 md:p-12 rounded-[3.5rem] shadow-2xl shadow-slate-200 border border-white relative overflow-hidden">

            <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-50 rounded-full blur-3xl opacity-50"></div>

            <div class="text-center mb-10 relative">
                <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic">عبور <span class="text-indigo-600">.</span></h1>
                <p class="text-slate-400 font-bold text-[10px] mt-3 uppercase tracking-[0.2em]">مرحباً بعودتك مجدداً</p>
            </div>

            <form action="#" method="POST" class="space-y-5 relative">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 mr-2 italic">البريد الإلكتروني</label>
                    <input type="email" name="email" placeholder="name@example.com" required
                        class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm focus:ring-4 focus:ring-indigo-100 outline-none transition-all">
                </div>

                <div x-data="{ show: false }">
                    <div class="flex justify-between items-center mb-2 px-2">
                        <a href="#" class="text-[9px] font-black text-indigo-500 hover:underline">نسيت كلمة السر؟</a>
                        <label class="block text-[10px] font-black text-slate-400 uppercase italic">كلمة المرور</label>
                    </div>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" name="password" required
                            class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm focus:ring-4 focus:ring-indigo-100 outline-none transition-all">
                        <button type="button" @click="show = !show" class="absolute left-5 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400">
                            <span x-text="show ? 'إخفاء' : 'إظهار'"></span>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full py-5 bg-slate-900 text-white rounded-[2rem] font-black text-sm hover:bg-indigo-600 transition-all shadow-xl mt-4 active:scale-95">
                    دخول للمنصة 🚀
                </button>

                <div class="text-center mt-10">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        ليس لديك حساب؟ <a href="{{ route('client.auth.regis') }}" class="text-indigo-600 hover:underline font-black">سجل الآن</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
