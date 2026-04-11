<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عُبور - منصة حجز الرحلات البرية</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&display=swap" rel="stylesheet">
    <style> body { font-family: 'Tajawal', sans-serif; } </style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">

    <nav class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="container mx-auto px-6 h-20 flex justify-between items-center">
            <a href="{{ route('client.index') }}" class="text-3xl font-black text-blue-600 tracking-tighter">عُـبـور</a>

            <div class="hidden md:flex space-x-8 space-x-reverse items-center">
                <a href="{{ route('client.index') }}" class="text-gray-700 font-bold hover:text-blue-600 transition">الرئيسية</a>
                <a href="{{ route('client.profile') }}" class="text-gray-500 hover:text-blue-600 transition">ملفي</a>
                <a href="#" class="text-gray-500 hover:text-blue-600 transition">عن المنصة</a>
            </div>

            <a href="{{ route('client.profile') }}">
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="text-left hidden sm:block">
                    <p class="text-[10px] text-gray-400 font-bold uppercase">مرحباً بك</p>
                    <p class="text-xs font-bold text-gray-800">محمد السبتي</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">👤</div>
            </div>
            </a>
        </div>
    </nav>

    <main class="flex-grow">
        @yield('content')
    </main>

    <footer class="bg-white border-t border-gray-100 py-12 mt-20">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6 text-center md:text-right">
                <div>
                    <h4 class="text-xl font-black text-blue-600 mb-2">عُـبـور</h4>
                    <p class="text-xs text-gray-400 max-w-xs">المنصة الأولى لتنظيم وحجز رحلات الحافلات السياحية في المملكة.</p>
                </div>
                <div class="flex space-x-6 space-x-reverse text-sm font-bold text-gray-500">
                    <a href="#" class="hover:text-blue-600">الشروط والأحكام</a>
                    <a href="#" class="hover:text-blue-600">سياسة الخصوصية</a>
                    <a href="#" class="hover:text-blue-600">الدعم الفني</a>
                </div>
            </div>
            <hr class="my-8 border-gray-50">
            <p class="text-center text-[10px] text-gray-300 font-bold uppercase tracking-widest">&copy; 2026 مشروع عبور - جميع الحقوق محفوظة</p>
        </div>
    </footer>

</body>
</html>
