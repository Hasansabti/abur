<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'تسجيل دخول المسافر | منصة عبور')</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/DF.jpeg') }}">
    <meta name="description" content="@yield('meta_description', 'منصة عبور هي البوابة الرائدة لإدارة حجز الرحلات والوكالات، سجل الآن لتتمكن من إدارة وتتبع رحلاتك وحجوزاتك بكل سهولة وأمان.')">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }

        /* تأثير حركة الخلفية الحية */
        @keyframes blob {
            0% {
                transform: translate(0px, 0px) scale(1);
            }

            33% {
                transform: translate(30px, -50px) scale(1.1);
            }

            66% {
                transform: translate(-20px, 20px) scale(0.95);
            }

            100% {
                transform: translate(0px, 0px) scale(1);
            }
        }

        .animate-blob {
            animation: blob 10s infinite alternate ease-in-out;
        }

        .animation-delay-2000 {
            animation-delay: 2s;
        }

        .animation-delay-4000 {
            animation-delay: 4s;
        }

        .animate-slide-up {
            animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body
    class="bg-slate-50 text-slate-900 antialiased relative min-h-screen w-screen overflow-x-hidden flex items-center justify-center p-4">

    <!-- عناصر الخلفية -->
    <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
        <div
            class="absolute top-1/4 -right-20 w-80 h-80 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-40 animate-blob">
        </div>
        <div
            class="absolute bottom-1/4 -left-20 w-80 h-80 bg-emerald-100 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-2000">
        </div>
        <div
            class="absolute top-1/2 left-1/3 w-60 h-60 bg-purple-100 rounded-full mix-blend-multiply filter blur-3xl opacity-40 animate-blob animation-delay-4000">
        </div>
    </div>

    <!-- الحاوية الرئيسية للكرت -->
    <div class="w-full max-w-lg z-10">

        <!-- كرت تسجيل الدخول -->
        <div
            class="bg-white/90 backdrop-blur-md p-6 sm:p-10 md:p-12 rounded-3xl shadow-2xl shadow-indigo-950/5 border border-white/60 relative animate-slide-up">

            <!-- الهيدر والشعار -->
            <div class="text-center mb-6 sm:mb-8 relative">
                <div
                    class="inline-flex items-center justify-center w-14 h-14 p-1 rounded-xl from-indigo-600 to-violet-500 shadow-lg shadow-indigo-500/30 mb-4 transform hover:rotate-6 transition-transform duration-300">
                    <img src="{{ asset('images/DF.jpeg') }}" alt="Logo"
                        class="w-full h-full object-cover rounded-lg">
                </div>
                <h1 class="text-3xl sm:text-4xl font-900 text-slate-900 tracking-tight">
                    منصة عُـبـور<span class="text-indigo-600">.</span>
                </h1>
                <p class="text-sm font-medium text-slate-500 mt-2">
                    تسجيل دخول المسافر لإدارة رحلاتك وحجوزاتك
                </p>
            </div>

            {{-- رسائل التنبيه والخطأ --}}
            @if (session('status'))
                <div
                    class="mb-5 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm font-bold px-4 py-3 text-center animate-slide-up flex items-center justify-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div
                    class="mb-5 rounded-2xl bg-rose-50 border border-rose-100 text-rose-800 text-sm font-bold px-4 py-3 text-center animate-slide-up flex items-center justify-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                    {{ $errors->has('email') ? $errors->first('email') : $errors->first() }}
                </div>
            @endif

            {{-- ==================== فورم تسجيل دخول المسافر ==================== --}}
            <div class="space-y-5">
                <form action="{{ route('client.auth.authenticate') }}" method="post" class="space-y-5">
                    @csrf
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">البريد الإلكتروني للمسافر</label>
                        <div class="relative group">
                            <input type="email" name="email" value="{{ old('email') }}"
                                placeholder="name@example.com" required
                                class="w-full bg-slate-50/50 border border-slate-200/80 rounded-2xl pr-4 pl-11 py-3.5 text-base text-slate-900 placeholder-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all duration-200">
                            <span
                                class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207">
                                    </path>
                                </svg>
                            </span>
                        </div>
                    </div>

                    <div x-data="{ show: false }">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-sm font-bold text-slate-700">كلمة المرور</label>
                            <button type="button" onclick="openForgotModal()"
                                class="text-xs sm:text-sm text-indigo-600 font-bold hover:text-indigo-700 transition">نسيت
                                كلمة المرور؟</button>
                        </div>
                        <div class="relative group">
                            <input :type="show ? 'text' : 'password'" name="password" required
                                class="w-full bg-slate-50/50 border border-slate-200/80 rounded-2xl px-4 py-3.5 text-base text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all duration-200">
                            <button type="button" @click="show = !show"
                                class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition focus:outline-none bg-slate-100 px-2.5 py-1 rounded-lg">
                                <span x-text="show ? 'إخفاء' : 'إظهار'"></span>
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full py-4 bg-slate-950 text-white rounded-2xl font-bold text-base hover:bg-indigo-600 shadow-xl shadow-slate-950/10 hover:shadow-indigo-600/20 transition-all duration-200 active:scale-[0.99] focus:ring-4 focus:ring-indigo-100 flex items-center justify-center gap-2">
                        دخول كمسافر
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </form>
            </div>

            {{-- ==================== فورم نسيت كلمة المرور للمسافر (Modal) ==================== --}}
            <div id="forgotPasswordModal"
                class="fixed inset-0 z-50 hidden bg-slate-950/40 backdrop-blur-md flex items-center justify-center p-4">
                <div
                    class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all duration-300 border border-slate-100 animate-slide-up">

                    <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/80">
                        <h3 class="text-base font-bold text-slate-900">استعادة كلمة المرور</h3>
                        <button type="button" onclick="closeForgotModal()"
                            class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6">
                        <form id="passengerForgotForm" action="{{ route('passenger.password.email') }}"
                            method="POST" class="space-y-4">
                            @csrf

                            <!-- حقل رقم الهوية -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">رقم الهوية</label>
                                <input type="text" name="national_id" required placeholder="1XXXXXXXXX"
                                    maxlength="10" pattern="[0-9]{10}" inputmode="numeric"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3.5 text-base text-slate-900 outline-none focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all">
                            </div>

                            <!-- حقل البريد الإلكتروني -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">البريد الإلكتروني للمسافر</label>
                                <input type="email" name="email" required placeholder="example@mail.com"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3.5 text-base text-slate-900 outline-none focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all">
                            </div>

                            <button type="submit"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-4 rounded-2xl transition shadow-lg shadow-indigo-600/10">
                                إرسال رابط الاستعادة
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- رابط إنشاء الحساب السفلي -->
            <div class="text-center mt-6 sm:mt-8 relative border-t border-slate-100 pt-6">
                <p class="text-sm font-medium text-slate-500">
                    ليس لديك حساب؟
                    <a href="{{ route('client.auth.register') }}"
                        class="text-indigo-600 hover:text-indigo-700 font-bold transition-all mr-1">
                        سجل الآن كمسافر
                    </a>
                </p>
            </div>

        </div>
    </div>

</body>

<script>
    function openForgotModal() {
        document.getElementById('forgotPasswordModal').classList.remove('hidden');
    }

    function closeForgotModal() {
        document.getElementById('forgotPasswordModal').classList.add('hidden');
    }
</script>

</html>