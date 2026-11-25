@php
    // ───────── القائمة: كل عنصر = [الراوت، نمط التفعيل، أيقونة، النص، متغير العداد، لون العداد] ─────────
    $badgeColors = [
        'rose'    => 'bg-rose-500 text-white',
        'amber'   => 'bg-amber-500 text-slate-950',
        'emerald' => 'bg-emerald-500 text-white',
    ];

    $sections = [
        'الرئيسية' => [
            ['admin.dashboard', 'admin.dashboard', '📊', 'ملخص النظام', null, null],
        ],
        'الإدارة التشغيلية' => [
            ['admin.agencies.index',    'admin.agencies.*',        '🏢',  'الوكالات',       $sidebarAgenciesCount ?? 0,      'rose'],
            ['admin.trips.approveTrip', 'admin.trips.approveTrip', '🚍',  'اعتماد الرحلات', $sidebarTripsCount ?? 0,         'amber'],
            ['admin.subscriptions',     'admin.subscriptions',     '📦',  'الاشتراكات',     $sidebarSubscriptionsCount ?? 0, 'amber'],
            ['admin.tickets.index',     'admin.tickets.*',         '🎟️', 'التذاكر',        null,                            null],
        ],
        'المالية' => [
            ['admin.payouts',          'admin.payouts',        '💳', 'إدارة المال',  $sidebarTripsCount ?? 0, 'amber'],
            ['admin.moyasar.payments', 'admin.moyasar.*',      '🧾', 'مدفوعات ميسر', null,                    null],
        ],
        'المستخدمون' => [
            ['admin.users.pending',  'admin.users.pending',  '🪪', 'اعتماد الهوية',    $sidebarUsersCount ?? 0, 'emerald'],
            ['admin.users.showEdit', 'admin.users.showEdit', '👥', 'إدارة المستخدمين', null,                    null],
        ],
    ];

    // ───────── الإشعارات العائمة (نجاح / خطأ / أخطاء التحقق) ─────────
    $toasts = [];
    if (session('success')) {
        $toasts[] = ['color' => 'emerald', 'title' => 'تمت العملية بنجاح', 'text' => session('success'), 'list' => [], 'ms' => 5000, 'icon' => 'M5 13l4 4L19 7'];
    }
    if (session('error')) {
        $toasts[] = ['color' => 'rose', 'title' => 'تنبيه', 'text' => session('error'), 'list' => [], 'ms' => 6000, 'icon' => 'M6 18L18 6M6 6l12 12'];
    }
    if ($errors->any()) {
        $toasts[] = ['color' => 'rose', 'title' => 'يرجى تصحيح الأخطاء التالية:', 'text' => null, 'list' => $errors->all(), 'ms' => 8000, 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'];
    }
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="{{ asset('images/DF.jpeg') }}">
    <title>لوحة التحكم - عبور ADM</title>

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Google Fonts: Tajawal --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">

    {{-- حالة اللاياوت (قبل تحميل Alpine) --}}
    <script>
        function adminLayout() {
            const read = () => { try { return localStorage.getItem('admin-sidebar-collapsed') === '1'; } catch (e) { return false; } };
            const save = (v) => { try { localStorage.setItem('admin-sidebar-collapsed', v ? '1' : '0'); } catch (e) {} };

            return {
                sidebarOpen: false,            // درج الجوال
                collapsed: read(),             // القائمة مطوية (الديسكتوب)
                isDesk: window.innerWidth >= 1024,
                ready: false,                  // لمنع حركة الطي عند تحميل الصفحة

                get compact() { return this.collapsed && this.isDesk; },

                init() {
                    this.$watch('collapsed', save);
                    window.addEventListener('resize', () => { this.isDesk = window.innerWidth >= 1024; });
                    this.$nextTick(() => { this.ready = true; });
                }
            };
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Tajawal', sans-serif; }
        [x-cloak] { display: none !important; }

        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #334155; }
    </style>
</head>

<body class="bg-slate-100 text-slate-800 flex h-screen overflow-hidden antialiased" x-data="adminLayout()">

    {{-- ═════════════ الإشعارات العائمة ═════════════ --}}
    @if (count($toasts))
        <div class="fixed top-24 end-6 z-[3000] max-w-sm w-full space-y-3 pointer-events-none" dir="rtl">
            @foreach ($toasts as $toast)
                @php $c = $toast['color']; @endphp
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, {{ $toast['ms'] }})" x-cloak
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                    class="pointer-events-auto bg-white border border-slate-100 border-r-4 border-r-{{ $c }}-500 shadow-2xl rounded-2xl p-4 flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-{{ $c }}-50 text-{{ $c }}-600 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $toast['icon'] }}" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-xs font-black text-slate-800">{{ $toast['title'] }}</h4>
                            @if ($toast['text'])
                                <p class="text-xs font-bold text-slate-500 mt-0.5">{{ $toast['text'] }}</p>
                            @endif
                            @if (count($toast['list']))
                                <ul class="text-xs font-bold text-slate-500 list-disc list-inside space-y-1 mt-1.5">
                                    @foreach ($toast['list'] as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                    <button type="button" @click="show = false" aria-label="إغلاق"
                        class="text-slate-400 hover:text-slate-600 p-1 rounded-lg transition-colors shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ═════════════ تعتيم الشاشة (الجوال) ═════════════ --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-950/80 z-40 lg:hidden backdrop-blur-sm"></div>

    {{-- ═════════════ الشريط الجانبي ═════════════ --}}
    <aside
        :class="[
            sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0',
            compact ? 'lg:w-20' : 'lg:w-72',
            ready ? 'transition-all duration-300 ease-in-out' : ''
        ]"
        class="fixed lg:relative inset-y-0 right-0 z-50 w-72 bg-slate-950 text-white flex flex-col border-l border-slate-800/60 shadow-2xl lg:shadow-none shrink-0">

        {{-- سهم الطي / التوسيع (ديسكتوب فقط) --}}
        <button type="button" @click="collapsed = !collapsed"
            :title="collapsed ? 'توسيع القائمة' : 'إخفاء القائمة'"
            :aria-label="collapsed ? 'توسيع القائمة' : 'إخفاء القائمة'"
            class="hidden lg:flex absolute -left-3 top-7 z-10 w-6 h-6 items-center justify-center rounded-full bg-white text-slate-600 border border-slate-200 shadow-md hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
            <svg class="w-3 h-3 transition-transform duration-300" :class="compact ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
            </svg>
        </button>

        {{-- الشعار --}}
        <div class="h-20 px-4 flex items-center border-b border-slate-900 bg-slate-950/50 shrink-0"
            :class="compact ? 'justify-center' : 'justify-between px-6'">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-2xl bg-indigo-600 flex items-center justify-center font-black text-white text-xl shadow-lg shadow-indigo-600/30 shrink-0">
                    ع
                </div>
                <h1 x-show="!compact" class="text-xl font-black text-white tracking-tight flex items-center gap-2">
                    عبور
                    <span class="text-[10px] font-bold bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 px-2 py-0.5 rounded-full tracking-wider">
                        ADMIN
                    </span>
                </h1>
            </div>

            {{-- زر إغلاق القائمة في الجوال --}}
            <button type="button" @click="sidebarOpen = false" aria-label="إغلاق"
                class="lg:hidden text-slate-400 hover:text-white p-2 rounded-xl bg-slate-900">✕</button>
        </div>

        {{-- قائمة التنقل --}}
        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto overflow-x-hidden custom-scrollbar">
            @foreach ($sections as $sectionTitle => $items)
                {{-- عنوان القسم (يتحول لخط فاصل عند الطي) --}}
                <p x-show="!compact"
                    class="px-3 text-slate-500 text-[11px] font-black tracking-wider mb-2 {{ $loop->first ? '' : 'mt-6' }}">
                    {{ $sectionTitle }}
                </p>
                @unless ($loop->first)
                    <div x-show="compact" class="my-3 border-t border-slate-800/80"></div>
                @endunless

                @foreach ($items as [$routeName, $pattern, $icon, $label, $count, $color])
                    <a href="{{ route($routeName) }}" title="{{ $label }}"
                        :class="compact ? 'justify-center px-0' : 'px-4'"
                        class="flex items-center gap-3 py-3.5 rounded-2xl text-xs font-bold transition-all duration-200 {{ request()->routeIs($pattern) ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-black' : 'text-slate-400 hover:bg-slate-900 hover:text-white' }}">
                        <span class="text-lg relative">
                            {{ $icon }}
                            @if ($count > 0)
                                {{-- نقطة تنبيه عند الطي --}}
                                <span x-show="compact"
                                    class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full ring-2 ring-slate-950 {{ explode(' ', $badgeColors[$color])[0] }}"></span>
                            @endif
                        </span>
                        <span x-show="!compact">{{ $label }}</span>
                        @if ($count > 0)
                            <span x-show="!compact"
                                class="mr-auto {{ $badgeColors[$color] }} text-[10px] font-black px-2.5 py-0.5 rounded-full shadow-sm">
                                {{ $count }}
                            </span>
                        @endif
                    </a>
                @endforeach
            @endforeach
        </nav>

        {{-- بطاقة المستخدم وتسجيل الخروج --}}
        <div class="p-4 m-4 bg-slate-900/80 rounded-2xl border border-slate-800/80 shrink-0"
            :class="compact ? 'px-2 py-3 mx-3' : ''">
            <div class="flex items-center gap-3" :class="compact ? 'justify-center' : 'justify-between'">
                <div x-show="!compact" class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center font-bold text-indigo-400 shrink-0">
                        A
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-black text-white truncate">مشرف المنصة</p>
                        <p class="text-[10px] text-slate-400 truncate">Admin Dashboard</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.auth.logout') }}">
                    @csrf
                    <button type="submit" title="تسجيل الخروج"
                        class="p-2 bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-600 hover:text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center">
                        🚪
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ═════════════ المحتوى الرئيسي ═════════════ --}}
    <div class="flex-1 flex flex-col h-screen overflow-hidden min-w-0">

        {{-- الهيدر العلوي --}}
        <header class="h-20 bg-white border-b border-slate-200/80 flex items-center justify-between px-6 lg:px-10 shrink-0 shadow-sm z-10">

            <div class="flex items-center gap-4">
                {{-- زر القائمة في الهواتف --}}
                <button type="button" @click="sidebarOpen = true" aria-label="فتح القائمة"
                    class="lg:hidden p-2.5 rounded-2xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                    ☰
                </button>
                <div>
                    <h2 class="text-slate-900 font-black text-base lg:text-lg">لوحة التحكم المركزية</h2>
                    <p class="text-[11px] text-slate-400 font-medium hidden sm:block">
                        إدارة ومتابعة العمليات اليومية للرحلات والحجوزات
                    </p>
                </div>
            </div>

            {{-- التاريخ وحالة الاتصال --}}
            <div class="text-left bg-slate-50 border border-slate-100 px-4 py-2 rounded-2xl">
                <p class="text-xs font-black text-slate-800">{{ now()->translatedFormat('j F Y') }}</p>
                <div class="flex items-center justify-end gap-1.5 mt-0.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <p class="text-[10px] font-bold text-emerald-600">النظام متصل</p>
                </div>
            </div>
        </header>

        {{-- منطقة المحتوى --}}
        <main class="flex-1 overflow-y-auto p-6 lg:p-10 bg-slate-50/50">
            @yield('content')
        </main>
    </div>

</body>

</html>