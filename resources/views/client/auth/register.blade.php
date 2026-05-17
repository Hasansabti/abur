<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد | عبور</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        [x-cloak] { display: none !important; }
        .animate-slide-up { animation: slideUp 0.4s ease-out; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-slate-50">

    <div class="min-h-screen flex items-center justify-center py-12 px-6" x-data="{ accountType: 'user' }">
        <div class="bg-white w-full max-w-2xl p-10 md:p-14 rounded-[4rem] shadow-2xl shadow-slate-200 border border-white">

            <div class="text-center mb-12">
                <h2 class="text-3xl font-black text-slate-900 tracking-tighter italic">انضم لأسرة عبور ✨</h2>
                <p class="text-slate-400 text-[10px] font-black mt-3 uppercase tracking-[0.3em]">حدد نوع حسابك لتبدأ تجربتك</p>
            </div>

            {{-- اختيار نوع الحساب --}}
            <div class="grid grid-cols-2 gap-4 mb-10 bg-slate-50 p-2 rounded-[2.5rem]">
                <button @click="accountType = 'user'"
                    :class="accountType === 'user' ? 'bg-white shadow-lg text-indigo-600' : 'text-slate-400'"
                    class="py-5 rounded-[2.2rem] transition-all flex flex-col items-center gap-2">
                    <span class="text-2xl">👤</span>
                    <span class="text-[10px] font-black uppercase">أنا مسافر</span>
                </button>
                <button @click="accountType = 'agency'"
                    :class="accountType === 'agency' ? 'bg-white shadow-lg text-indigo-600' : 'text-slate-400'"
                    class="py-5 rounded-[2.2rem] transition-all flex flex-col items-center gap-2">
                    <span class="text-2xl">🏢</span>
                    <span class="text-[10px] font-black uppercase">أنا وكالة</span>
                </button>
            </div>

            <form action="{{ route('client.auth.register') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @csrf
                @if ($errors->any())
                    <div class="md:col-span-2 rounded-2xl bg-red-50 text-red-700 text-xs font-bold px-4 py-3">
                        {{ $errors->first() }}
                    </div>
                @endif
                {{-- الاسم --}}
                <div class="md:col-span-1">
                    <label class="block text-[9px] font-black text-slate-400 uppercase mb-2 mr-2 italic">الاسم الكامل</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="الاسم الثلاثي" required
                        class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm focus:ring-4 focus:ring-indigo-100 outline-none transition-all">
                </div>

                {{-- البريد --}}
                <div class="md:col-span-1">
                    <label class="block text-[9px] font-black text-slate-400 uppercase mb-2 mr-2 italic">البريد الإلكتروني</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="mail@example.com" required
                        class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm focus:ring-4 focus:ring-indigo-100 outline-none transition-all">
                </div>

                {{-- يظهر فقط للوكالة --}}
                <template x-if="accountType === 'agency'">
                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 animate-slide-up">
                        <div>
                            <label class="block text-[9px] font-black text-indigo-500 uppercase mb-2 mr-2 italic">اسم الوكالة التجاري</label>
                            <input type="text" name="agency_name" value="{{ old('agency_name') }}" required
                                class="w-full bg-indigo-50/30 border border-indigo-100 rounded-2xl px-6 py-4 text-sm outline-none">
                        </div>
                        <div>
                            <label class="block text-[9px] font-black text-indigo-500 uppercase mb-2 mr-2 italic">رقم السجل التجاري</label>
                            <input type="text" name="commercial_id" value="{{ old('commercial_id') }}" required
                                class="w-full bg-indigo-50/30 border border-indigo-100 rounded-2xl px-6 py-4 text-sm outline-none">
                        </div>
                    </div>
                </template>

                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase mb-2 mr-2 italic">كلمة المرور</label>
                    <input type="password" name="password" required
                        class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm focus:ring-4 focus:ring-indigo-100 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase mb-2 mr-2 italic">تأكيد كلمة المرور</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm focus:ring-4 focus:ring-indigo-100 outline-none transition-all">
                </div>

                <button type="submit" class="md:col-span-2 py-5 bg-slate-900 text-white rounded-[2rem] font-black text-sm shadow-xl mt-4 active:scale-95 transition-all hover:bg-indigo-600">
                    تأكيد إنشاء الحساب ✨
                </button>
            </form>

            <div class="text-center mt-8">
                <a href="{{ route('client.auth.login') }}" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-indigo-600">لديك حساب بالفعل؟ سجل دخولك</a>
            </div>
        </div>
    </div>

</body>
</html>
