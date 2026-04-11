<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - عبور ADM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 flex h-screen">

    <aside class="w-72 bg-slate-950 text-white flex-shrink-0 hidden lg:flex flex-col border-l border-slate-800">
        <div class="p-8 text-center border-b border-slate-900 bg-slate-900">
            <h1 class="text-2xl font-black text-indigo-400 tracking-tighter">عبور <span
                    class="text-white text-xs font-normal">ADMIN</span></h1>
        </div>

        <nav class="flex-1 p-6 space-y-3">
            <p class="text-slate-500 text-[10px] font-bold uppercase mb-2">الرئيسية</p>
            <a href="{{ route('admin.dashboard') }}"
                class="flex items-center p-3 bg-indigo-600 rounded-xl text-white font-bold">
                <span class="ml-3">📊</span> ملخص النظام
            </a>

            <p class="text-slate-500 text-[10px] font-bold uppercase mt-6 mb-2">الإدارة التشغيلية</p>
            <a href="{{ route('admin.agencies.index') }}"
                class="flex items-center p-3 text-slate-400 hover:bg-slate-900 hover:text-white rounded-xl transition">
                <span class="ml-3">🏢</span> الوكالات <span
                    class="mr-auto bg-red-500 text-[10px] px-2 py-0.5 rounded-full text-white">3</span>
            </a>
            <a href="{{ route('admin.trips.approveTrip') }}"
                class="flex items-center p-3 text-slate-400 hover:bg-slate-900 hover:text-white rounded-xl transition">
                <span class="ml-3">🚍</span> اعتماد الرحلات <span
                    class="mr-auto bg-indigo-500 text-[10px] px-2 py-0.5 rounded-full text-white">8</span>
            </a>
            <a href="{{ route('admin.users.pending') }}"
                class="flex items-center p-3 text-slate-400 hover:bg-slate-900 hover:text-white rounded-xl transition">
                <span class="ml-3">👥</span> المستخدمين<span
                    class="mr-auto bg-green-500 hover:bg-green-600 transition text-[10px] px-2 py-0.5 rounded-full text-white">
                    8
                </span>
            </a>

            {{-- <p class="text-slate-500 text-[10px] font-bold uppercase mt-6 mb-2">النظام والرسوم</p>
            <a href="{{ route('driver.auth.login') }}" class="flex items-center p-3 text-slate-400 hover:bg-slate-900 hover:text-white rounded-xl transition">
                <span class="ml-3">💰</span>  السائقين
            </a> --}}
        </nav>

        <div class="p-6 bg-slate-900">
            <div class="flex items-center justify-between">

                <!-- معلومات المستخدم -->
                <div class="flex items-center space-x-3 space-x-reverse">
                    <div class="w-10 h-10 bg-indigo-500 rounded-lg flex items-center justify-center font-bold">
                        M
                    </div>
                    <div>
                        <p class="text-xs font-bold text-white">مشرف المنصة</p>
                        <p class="text-[10px] text-slate-400">خروج آمن</p>
                    </div>
                </div>

                <!-- زر تسجيل الخروج -->
                <form method="get" action="{{ route('admin.auth.logout') }}">
                    @csrf
                    <button type="submit"
                        class="text-[10px] bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg font-bold transition">
                        تسجيل خروج 🚪
                    </button>
                </form>

            </div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col">
        <header class="h-20 bg-white border-b flex items-center justify-between px-10 shadow-sm">
            <h2 class="text-gray-400 font-medium">لوحة التحكم المركزية</h2>
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="text-left">
                    <p class="text-xs font-bold text-gray-800">1 أبريل 2026</p>
                    <p class="text-[10px] text-green-500 text-right">النظام متصل ✅</p>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-10">
            @yield('content')
        </main>
    </div>

</body>

</html>
