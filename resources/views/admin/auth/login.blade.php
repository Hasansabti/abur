<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <title>دخول الإدارة | عبور</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Cairo', sans-serif; 
        }
        .glass-card {
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 40px 100px -30px rgba(0, 0, 0, 0.7);
        }
        .input-premium {
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }
        .input-premium:focus {
            border-color: #6366f1;
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.15);
            background: rgba(30, 41, 59, 0.7);
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center relative overflow-hidden bg-slate-950 text-slate-100">

    <!-- خلفية مضيئة ناعمة (Glow Effect) -->
    <div class="absolute inset-0 z-0 overflow-hidden">
        <div class="absolute top-[-10%] right-[-10%] w-[500px] h-[500px] bg-indigo-600/10 rounded-full blur-[150px]"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[500px] h-[500px] bg-violet-600/10 rounded-full blur-[150px]"></div>
    </div>

    <!-- كارت تسجيل الدخول -->
    <div class="relative z-10 w-full max-w-[440px] px-4" x-data="{ loading: false }">
        
        <div class="glass-card rounded-3xl p-8 md:p-10 border border-slate-800/60">
            
            <!-- الشعار والهيدر -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-2xl shadow-lg shadow-indigo-500/20 mb-4 text-white">
                    <!-- أيقونة درع حماية مودرن -->
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-white">بوابة الإدارة</h1>
                <p class="text-xs text-slate-400 mt-1.5 font-medium">مرحباً بك في لوحة تحكم منصة عبور</p>
            </div>

            <!-- النموذج -->
            <!-- تم تعديل الـ Method إلى POST والـ Action للمسار الصحيح المخصص للاستقبال -->
            <form action="{{ route('admin.auth.login.submit') }}" method="post" @submit="loading = true" class="space-y-6">
                @csrf
                
                <!-- رسائل الخطأ العامة -->
                @if (session('error'))
                    <div class="rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm py-3 px-4 flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span class="font-medium">{{ session('error') }}</span>
                    </div>
                @endif

                <!-- حقل البريد الإلكتروني -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-300 pr-1">البريد الإلكتروني الرسمي</label>
                    <div class="relative">
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="input-premium w-full rounded-xl py-3.5 px-4 text-sm text-white placeholder-slate-500 outline-none"
                               placeholder="admin@obor.com"> <!-- متناسق مع السيدر تماماً -->
                    </div>
                    @error('email')
                        <p class="text-red-400 text-xs mt-1 pr-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- حقل كلمة المرور -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-300 pr-1">كلمة المرور</label>
                    <div class="relative">
                        <input type="password" name="password" required
                               class="input-premium w-full rounded-xl py-3.5 px-4 text-sm text-white placeholder-slate-500 outline-none"
                               placeholder="••••••••">
                    </div>
                    @error('password')
                        <p class="text-red-400 text-xs mt-1 pr-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- زر تسجيل الدخول -->
                <button type="submit" :disabled="loading"
                        class="w-full relative py-3.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white rounded-xl font-semibold text-sm transition-all duration-200 shadow-lg shadow-indigo-600/20 active:scale-[0.99] disabled:opacity-50 flex items-center justify-center min-h-[48px]">

                    <!-- النص العادي -->
                    <div x-show="!loading" class="flex items-center gap-2">
                        <span>تسجيل الدخول</span>
                        <svg class="w-4 h-4 transform rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </div>

                    <!-- مؤشر التحميل النشط -->
                    <div x-show="loading" class="flex items-center justify-center" x-cloak>
                        <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </button>
            </form>

            <!-- الفوتر السفلي -->
            <div class="mt-8 text-center border-t border-slate-800/60 pt-5">
                <p class="text-[11px] text-slate-500 font-medium tracking-wide">
                    منصة عبور الرقمية &copy; {{ date('Y') }}
                </p>
                <div class="flex items-center justify-center gap-1.5 mt-1 text-[9px] text-indigo-500/60 font-mono tracking-wider uppercase">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-ping"></span>
                    <span>Secure Admin Environment</span>
                </div>
            </div>
        </div>
    </div>
</body>
<script>
    // إرسال طلب صامت للسيرفر كل 15 دقيقة للحفاظ على الجلسة حية
    setInterval(function() {
        fetch(window.location.href, {
            method: 'HEAD' // طلب خفيف جداً لا يستهلك بيانات السيرفر
        });
    }, 15 * 60 * 1000); 
</script>
</html>