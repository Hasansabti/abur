<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوابة الوكالة - مشروع عبور</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <aside class="w-64 bg-slate-900 text-white flex-shrink-0 hidden md:flex flex-col">
        <div class="p-6 text-center border-b border-slate-800">
            <h1 class="text-2xl font-bold tracking-widest text-blue-400">عُـبـور</h1>
            <p class="text-xs text-gray-400 mt-1">بوابة وكالات السفر</p>
        </div>

        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="{{ route('agency.dashboard') }}" class="flex items-center p-3 text-gray-300 hover:bg-slate-800 rounded-lg transition-colors">
                <span class="ml-3">🏠</span> الرئيسة
            </a>
            <a href="{{ route('agency.trips.create') }}" class="flex items-center p-3 text-white bg-blue-600 rounded-lg">
                <span class="ml-3">➕</span> إضافة رحلة
            </a>
            <a href="{{ route('agency.trips.index') }}" class="flex items-center p-3 text-gray-300 hover:bg-slate-800 rounded-lg">
                <span class="ml-3">🚍</span> إدارة الرحلات
            </a>
            <a href="{{ route('agency.bookings.index') }}" class="flex items-center p-3 text-gray-300 hover:bg-slate-800 rounded-lg">
                <span class="ml-3">👥</span> الحجوزات
            </a>
            <a href="{{ route('agency.trips.reports') }}" class="flex items-center p-3 text-gray-300 hover:bg-slate-800 rounded-lg">
                <span class="ml-3">📄</span> التقارير
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800">
            <a href="#" class="flex items-center p-3 text-red-400 hover:bg-red-900/20 rounded-lg">
                <span class="ml-3">🚪</span> تسجيل الخروج
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">

        <header class="h-16 bg-white border-b flex items-center justify-between px-8 shadow-sm">
            <div class="flex items-center">
                <button class="md:hidden p-2 text-gray-600">☰</button>
                <span class="text-gray-500 text-sm">مرحباً، وكالة الفارس للسياحة</span>
            </div>
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="h-8 w-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold">
                    A
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
            @yield('content')
        </main>

    </div>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
