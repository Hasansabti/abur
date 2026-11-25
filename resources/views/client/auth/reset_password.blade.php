<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور | عبور</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }

        .animate-slide-up {
            animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-slate-50/50 min-h-screen flex items-center justify-center p-4 sm:p-6 antialiased">

    <!-- الحاوية الرئيسية الواجهة -->
    <div class="bg-white w-full max-w-md p-8 sm:p-10 md:p-12 rounded-[2.5rem] shadow-xl shadow-slate-100 border border-slate-100 relative overflow-hidden animate-slide-up">

        <!-- تأثيرات الخلفية الضبابية المودرن -->
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-50/60 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-blue-50/60 rounded-full blur-3xl pointer-events-none"></div>

        <!-- الشعار والهيدر -->
        <div class="text-center mb-8 relative">
            <h1 class="text-4xl font-semibold text-slate-900 tracking-tight">عبور<span class="text-indigo-600 font-black">.</span></h1>
            <p class="text-slate-400 font-medium text-xs mt-2.5">أنشئ كلمة مرور جديدة وقوية لتأمين حسابك</p>
        </div>

        <!-- صندوق عرض الأخطاء المطور -->
        @if ($errors->any())
            <div class="mb-6 rounded-2xl bg-rose-50/70 border border-rose-100 text-rose-800 text-xs font-bold px-4 py-3.5 relative">
                <div class="flex items-start gap-2.5">
                    <span class="mt-0.5">⚠️</span>
                    <ul class="space-y-1 text-right flex-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- الفورم -->
        <form action="{{ route('password.update') }}" method="POST" class="space-y-5 relative">
            @csrf
            
            <input type="hidden" name="token" value="{{ $token }}">

            <!-- حقل البريد الإلكتروني (للقراءة فقط) -->
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-2 mr-1">البريد الإلكتروني المؤكد</label>
                <div class="relative">
                    <input type="email" name="email" value="{{ request()->email }}" required readonly
                        class="w-full bg-slate-50 border border-slate-200/60 rounded-2xl px-5 py-3.5 text-sm text-slate-400 cursor-not-allowed outline-none select-none">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs opacity-40">🔒</span>
                </div>
            </div>

            <!-- حقل كلمة المرور الجديدة -->
            <div x-data="{ show: false }">
                <label class="block text-xs font-bold text-slate-500 mb-2 mr-1">كلمة المرور الجديدة</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="password" required placeholder="••••••••"
                        class="w-full bg-white border border-slate-200/80 rounded-2xl px-5 py-3.5 text-sm text-slate-800 placeholder-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-50 outline-none transition-all duration-200">
                    
                    <button type="button" @click="show = !show" 
                        class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 hover:text-indigo-600 focus:outline-none transition-colors select-none">
                        <span x-text="show ? 'إخفاء' : 'إظهار'"></span>
                    </button>
                </div>
            </div>

            <!-- حقل تأكيد كلمة المرور -->
            <div x-data="{ show: false }">
                <label class="block text-xs font-bold text-slate-500 mb-2 mr-1">تأكيد كلمة المرور الجديدة</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="password_confirmation" required placeholder="••••••••"
                        class="w-full bg-white border border-slate-200/80 rounded-2xl px-5 py-3.5 text-sm text-slate-800 placeholder-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-50 outline-none transition-all duration-200">
                    
                    <button type="button" @click="show = !show" 
                        class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 hover:text-indigo-600 focus:outline-none transition-colors select-none">
                        <span x-text="show ? 'إخفاء' : 'إظهار'"></span>
                    </button>
                </div>
            </div>

            <!-- زر الإرسال والحفظ -->
            <button type="submit" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 active:scale-[0.98] text-white rounded-2xl font-bold text-sm transition-all shadow-lg shadow-indigo-600/10 mt-3 flex justify-center items-center gap-2">
                تحديث كلمة المرور والدخول 🚀
            </button>
        </form>

        <!-- العودة للخلف -->
        <div class="text-center mt-8 border-t border-slate-100 pt-6 relative">
            <a href="{{ route('client.auth.login') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-indigo-600 transition-colors">
                <span>موافق، العودة لتسجيل الدخول</span>
                <span class="scale-x-[-1] inline-block text-[10px]">➔</span>
            </a>
        </div>

    </div>

</body>
</html>