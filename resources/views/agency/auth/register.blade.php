<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>إنشاء حساب وكالة جديد | عبور</title>
    <meta name="description" content="@yield('meta_description', 'منصة عبور هي البوابة الرائدة لإدارة حجز الرحلات والوكالات، سجل وكالتك الآن لتتمكن من إدارة وتتبع رحلاتك وحجوزاتك بكل سهولة وأمان.')">
    <link rel="icon" type="image/jpeg" href="{{ asset('images/DF.jpeg') }}">
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
    class="bg-slate-50 text-slate-900 antialiased relative min-h-screen w-screen overflow-x-hidden flex items-center justify-center p-4 sm:p-8 md:p-12">

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

    <div class="w-full max-w-2xl z-10">

        <div
            class="bg-white/90 backdrop-blur-md p-6 sm:p-10 md:p-12 rounded-3xl shadow-2xl shadow-indigo-950/5 border border-white/60 relative animate-slide-up">

            <div class="text-center mb-8 relative">
                <div
                    class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white shadow-lg shadow-indigo-500/20 mb-3 transform hover:rotate-6 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                </div>
                <h2 class="text-2xl sm:text-3xl font-900 text-slate-900 tracking-tight">تسجيل وكالة سفر في عُـبـور<span
                        class="text-indigo-600">.</span></h2>
                <p class="text-sm font-medium text-slate-500 mt-1">أدخل بيانات وكالتك للانضمام وإدارة رحلاتك بكل سهولة</p>
            </div>

            @if ($errors->any())
                <div
                    class="mb-6 rounded-2xl bg-rose-50 border border-rose-100 text-rose-800 text-sm font-bold px-4 py-3.5 text-center flex items-center justify-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- ==================== فورم الوكالة ==================== --}}
            <div class="space-y-5">
                <form action="{{ route('agency.register') }}" enctype="multipart/form-data" method="POST"
                    class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    @csrf
                    <!-- شعار الوكالة (iconA) -->
                    <div class="md:col-span-2 flex flex-col items-center justify-center bg-slate-50/60 p-5 rounded-2xl border border-slate-200/50 shadow-inner mb-2"
                        x-data="{ iconPreview: null }">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-3">شعار الوكالة</label>
                        <div
                            class="relative w-24 h-24 rounded-2xl bg-white border-2 border-slate-200 shadow-sm overflow-hidden group flex items-center justify-center transition-all hover:border-indigo-500">
                            <template x-if="iconPreview">
                                <img :src="iconPreview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!iconPreview">
                                <span class="text-3xl opacity-40 group-hover:opacity-60 transition-opacity">🏢</span>
                            </template>
                            <label
                                class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-bold cursor-pointer transition-all backdrop-blur-[1px]">
                                رفع 📸
                                <input type="file" name="iconA" accept="image/*" class="hidden"
                                    @change="const file = $el.files[0]; if (file) { const reader = new FileReader(); reader.onload = (e) => { iconPreview = e.target.result; }; reader.readAsDataURL(file); }">
                            </label>
                        </div>
                    </div>

                    <!-- الاسم التجاري للوكالة (commercial_name) -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">الاسم التجاري للوكالة</label>
                        <input type="text" name="commercial_name" value="{{ old('commercial_name') }}"
                            placeholder="مؤسسة / شركة..." required
                            class="w-full bg-slate-50/50 border border-slate-200/80 rounded-2xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-indigo-500 outline-none transition-all duration-200">
                    </div>

                    <!-- اسم المسؤول (name) -->
                    <div class="md:col-span-1">
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">اسم المسؤول</label>
                        <input type="text" name="name" value="{{ old('name') }}"
                            placeholder="الاسم الثلاثي" required
                            class="w-full bg-slate-50/50 border border-slate-200/80 rounded-2xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-indigo-500 outline-none transition-all duration-200">
                    </div>

                    <!-- البريد الإلكتروني (email) -->
                    <div class="md:col-span-1">
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">البريد الإلكتروني للوكالة</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                            placeholder="agency@example.com" required
                            class="w-full bg-slate-50/50 border border-slate-200/80 rounded-2xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-indigo-500 outline-none transition-all duration-200">
                    </div>

                    <!-- رقم السجل التجاري (commercial_id) -->
                    <div class="md:col-span-1">
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">رقم السجل التجاري</label>
                        <input type="text" name="commercial_id" value="{{ old('commercial_id') }}"
                            placeholder="10XXXXXXXX" required inputmode="numeric"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            class="w-full bg-slate-50/50 border border-slate-200/80 rounded-2xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-indigo-500 outline-none transition-all duration-200">
                    </div>

                    <!-- تاريخ انتهاء السجل (cr_expiry_date) -->
                    <div class="md:col-span-1">
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">تاريخ انتهاء السجل</label>
                        <input type="date" name="cr_expiry_date" value="{{ old('cr_expiry_date') }}" required
                            class="w-full bg-slate-50/50 border border-slate-200/80 rounded-2xl px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-indigo-500 outline-none transition-all duration-200 text-right">
                    </div>

                    <!-- رقم الهاتف (phone) -->
                    <div class="md:col-span-1">
                        <label class="block text-sm font-bold mb-1.5 text-indigo-600">رقم هاتف الوكالة</label>
                        <div class="flex items-center w-full bg-slate-50/50 border border-indigo-100 rounded-2xl focus-within:bg-white focus-within:border-indigo-500 transition-all overflow-hidden"
                            dir="ltr">
                            <select name="country_code"
                                class="bg-transparent border-none py-3 pl-4 pr-6 text-sm font-bold text-slate-700 focus:ring-0 outline-none cursor-pointer">
                                <option value="+966" {{ old('country_code') == '+966' ? 'selected' : '' }}>+966
                                </option>
                                <option value="+971" {{ old('country_code') == '+971' ? 'selected' : '' }}>+971
                                </option>
                            </select>
                            <div class="w-px h-6 bg-indigo-200 mx-1"></div>
                            <input type="text" name="phone" value="{{ old('phone') }}"
                                placeholder="05XXXXXXXX" required maxlength="10" minlength="10" pattern="\d{10}"
                                inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                class="w-full bg-transparent border-none px-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:ring-0 outline-none text-left">
                        </div>
                    </div>

                    <!-- رقم الآيبان (ibanA) -->
                    <div class="md:col-span-1">
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">الآيبان البنكي (IBAN)</label>
                        <div class="relative" dir="ltr">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-500 font-bold">SA</span>
                            <input type="text" name="ibanA" value="{{ old('ibanA') }}"
                                placeholder="0000000000000000000000" required maxlength="22" minlength="22"
                                oninput="this.value = this.value.replace(/[^0-9a-zA-Z]/g, '').toUpperCase()"
                                class="w-full bg-slate-50/50 border border-slate-200/80 rounded-2xl pl-12 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-indigo-500 outline-none transition-all duration-200">
                        </div>
                    </div>

                    <!-- كلمة المرور (password) -->
                    <div x-data="{ show: false }" class="md:col-span-1">
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">كلمة المرور</label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'" name="password" required placeholder="••••••••"
                                class="w-full bg-slate-50/50 border border-slate-200/80 rounded-2xl pl-12 pr-4 py-3 text-sm focus:outline-none">
                            <button type="button" @click="show = !show"
                                class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">👁️</button>
                        </div>
                    </div>

                    <!-- تأكيد كلمة المرور -->
                    <div x-data="{ show: false }" class="md:col-span-1">
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">تأكيد كلمة المرور</label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'" name="password_confirmation" required
                                placeholder="••••••••"
                                class="w-full bg-slate-50/50 border border-slate-200/80 rounded-2xl pl-12 pr-4 py-3 text-sm focus:outline-none">
                            <button type="button" @click="show = !show"
                                class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">👁️</button>
                        </div>
                    </div>

                    <button type="submit"
                        class="md:col-span-2 py-4 bg-indigo-600 text-white rounded-2xl font-bold text-base hover:bg-indigo-700 transition-all duration-200 mt-2 shadow-lg shadow-indigo-500/30">
                        تأكيد إنشاء حساب وكالة 🏢
                    </button>
                </form>
            </div>

            <div class="text-center mt-6 border-t border-slate-100 pt-5">
                <a href="{{ route('agency.login') }}"
                    class="text-sm font-bold text-slate-400 hover:text-indigo-600 transition-all">لديك حساب بالفعل؟ سجل
                    دخولك من هنا</a>
            </div>

        </div>
    </div>
</body>

</html>
