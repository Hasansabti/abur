@php
    $current = Route::currentRouteName();

    // ───────── 1) المستخدم الحالي (موظف أو مالك وكالة) ─────────
    $isEmployee    = auth('employee')->check();
    $currentUser   = $isEmployee ? auth('employee')->user() : auth('agency')->user();
    $currentAgency = $isEmployee ? $currentUser?->agency : $currentUser;

    $agencyStatus = $currentAgency?->status ?? 'pending';
    $agencyIcon   = $currentAgency?->iconA ?? null;
    $agencyName   = session('agency_name') ?? ($currentAgency?->name ?? 'الوكالة');
    $userName     = $currentUser?->name ?? $agencyName;

    // ───────── 2) الصلاحيات (المالك والمدير يشاهدون كل شيء) ─────────
    $hasPermission = function ($permission) use ($isEmployee, $currentUser) {
        if (!$isEmployee) return true;
        if ($currentUser->role === 'admin') return true;

        $perms = $currentUser->permissions ?? [];
        if (is_string($perms)) {
            $perms = json_decode($perms, true) ?? [];
        }
        return in_array($permission, (array) $perms);
    };

    // ───────── 3) القائمة: كل عنصر = [اسم الراوت، أيقونة، النص، الصلاحية] ─────────
    $menu = [
        ['key' => 'trips', 'icon' => '🧳', 'label' => 'إدارة الرحلات والسكن', 'items' => [
            ['agency.trips.create',        '➕', 'إضافة رحلة',             'trips'],
            ['agency.trips.index',         '🚍', 'إدارة الرحلات',          'trips'],
            ['agency.trips.private_trips', '🚐', 'طلبات رحلات خاصة',       'trips'],
            ['agency.bookings.rooms',      '🏢', 'إدارة التسكين والغرف',   'rooms'],
            ['agency.trips.draft',         '📄', 'مسودات الرحلات',         'trips'],
        ]],
        ['key' => 'bookings', 'icon' => '🎫', 'label' => 'إدارة الحجوزات', 'items' => [
            ['agency.addbookings',     '➕', 'إضافة حجز جديد', 'bookings'],
            ['agency.bookings.index',  '👥', 'قائمة الحجوزات', 'bookings'],
        ]],
        ['key' => 'reports', 'icon' => '📈', 'label' => 'التقارير والمالية', 'items' => [
            ['agency.reports.audit',        '📋', 'السجلات التدقيقية', 'reports'],
            ['agency.reports.meals',        '🍽️', 'تقرير الوجبات',     'reports'],
            ['agency.discount-codes.index', '🏷️', 'أكواد الخصم',       'finance'],
            ['agency.trips.reports',        '📊', 'التقارير',          'reports'],
            ['agency.subscription.index',   '💳', 'الاشتراك',          'finance'],
            ['agency.wallet.index',         '💰', 'السجلات المالية',   'finance'],
        ]],
        ['key' => 'settings', 'icon' => '⚙️', 'label' => 'الإعدادات', 'items' => [
            ['agency.employees', '👨‍💼', 'إدارة الموظفين', 'employees'],
        ]],
    ];

    // إبقاء العناصر المسموحة فقط، وإخفاء المجموعة إذا فرغت
    $menu = collect($menu)
        ->map(function ($group) use ($hasPermission) {
            $group['items'] = array_values(array_filter($group['items'], fn($i) => $hasPermission($i[3])));
            return $group;
        })
        ->filter(fn($group) => count($group['items']) > 0)
        ->values();

    // المجموعة المفتوحة تلقائياً حسب الصفحة الحالية
    $openGroup = $menu->first(fn($g) => in_array($current, array_column($g['items'], 0)))['key'] ?? null;

    // ───────── 4) كلاسات مشتركة ─────────
    $linkBase = 'flex items-center rounded-xl transition-all duration-200 font-bold text-xs';
    $linkIdle = 'text-slate-400 hover:text-white hover:bg-slate-800/60';
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/jpeg" href="{{ asset('images/DF.jpeg') }}">
    <title>بوابة الوكالة - عُبور</title>

    <!-- Tailwind CSS & Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">

    <!-- حالة اللاياوت (يجب أن تُعرَّف قبل تحميل Alpine) -->
    <script>
        function agencyLayout(openGroup) {
            const read = () => { try { return localStorage.getItem('sidebar-collapsed') === '1'; } catch (e) { return false; } };
            const save = (v) => { try { localStorage.setItem('sidebar-collapsed', v ? '1' : '0'); } catch (e) {} };

            return {
                sidebarOpen: false,           // درج الجوال
                collapsed: read(),            // القائمة مطوية (الديسكتوب)
                isDesk: window.innerWidth >= 768,
                ready: false,                 // لمنع حركة الطي عند تحميل الصفحة
                openGroup: openGroup,

                // مطوية فعلياً: فقط على الشاشات الكبيرة
                get compact() { return this.collapsed && this.isDesk; },

                init() {
                    this.$watch('collapsed', save);
                    window.addEventListener('resize', () => { this.isDesk = window.innerWidth >= 768; });
                    this.$nextTick(() => { this.ready = true; });
                },

                toggleGroup(key) {
                    // لو القائمة مطوية: وسّعها وافتح القسم مباشرة
                    if (this.compact) { this.collapsed = false; this.openGroup = key; return; }
                    this.openGroup = this.openGroup === key ? null : key;
                }
            };
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Tajawal', sans-serif; }
        [x-cloak] { display: none !important; }

        /* الرابط النشط */
        .active-link {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
        }

        /* القوائم المنسدلة */
        .acc-body {
            display: grid;
            grid-template-rows: 0fr;
            opacity: 0;
            transition: grid-template-rows 0.35s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease;
        }
        .acc-body.open { grid-template-rows: 1fr; opacity: 1; }
        .acc-inner { overflow: hidden; min-height: 0; }

        .acc-chevron { transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1); }
        .acc-chevron.rotated { transform: rotate(180deg); }

        /* خط الشجرة الجانبي للروابط الفرعية */
        .sub-links {
            margin-right: 1.25rem;
            padding-right: 0.75rem;
            border-right: 1px solid rgba(51, 65, 85, 0.7);
        }

        /* Scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(15, 23, 42, 0.6); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #475569; }

        /* ===== غلاف التحميل (شعار متحرك) ===== */
        #page-loader {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 50% 40%, #ffffff 0%, #f1f5f9 100%);
            transition: opacity .5s ease, visibility .5s ease;
        }

        #page-loader.hide {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        #page-loader .loader-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.75rem;
            animation: loader-in .7s cubic-bezier(.22, 1, .36, 1) both;
            transition: transform .5s ease, opacity .5s ease;
        }

        #page-loader.hide .loader-content {
            transform: scale(1.1);
            opacity: 0;
        }

        /* حاوية الشعار */
        #page-loader .loader-stage {
            position: relative;
            width: 7.5rem;
            height: 7.5rem;
        }

        /* التوهّج خلف الشعار */
        #page-loader .loader-glow {
            position: absolute;
            inset: -1.25rem;
            border-radius: 9999px;
            background: radial-gradient(circle, rgba(37, 99, 235, .45) 0%, rgba(37, 99, 235, 0) 70%);
            filter: blur(14px);
            animation: loader-glow 2.4s ease-in-out infinite;
        }

        /* بطاقة الشعار: تطفو بهدوء */
        #page-loader .loader-logo-wrap {
            position: relative;
            width: 100%;
            height: 100%;
            border-radius: 1.75rem;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 24px 50px -16px rgba(37, 99, 235, .45), 0 0 0 1px rgba(148, 163, 184, .18);
            animation: loader-float 2.4s ease-in-out infinite;
        }

        #page-loader .loader-logo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* لمعة تمر فوق الشعار */
        #page-loader .loader-logo-wrap::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(110deg, transparent 35%, rgba(255, 255, 255, .6) 50%, transparent 65%);
            transform: translateX(-120%);
            animation: loader-shine 2.2s ease-in-out infinite;
        }

        /* شريط التقدم الرفيع */
        #page-loader .loader-bar {
            position: relative;
            width: 8rem;
            height: 3px;
            border-radius: 9999px;
            overflow: hidden;
            background: rgba(100, 116, 139, .2);
            direction: ltr;
        }

        #page-loader .loader-bar::before {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 40%;
            border-radius: 9999px;
            background: linear-gradient(90deg, #2563eb, #60a5fa);
            animation: loader-bar 1.4s cubic-bezier(.65, 0, .35, 1) infinite;
        }

        @keyframes loader-in {
            from { opacity: 0; transform: translateY(14px) scale(.9); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes loader-float {
            0%, 100% { transform: translateY(0) scale(1); }
            50%      { transform: translateY(-10px) scale(1.04); }
        }

        @keyframes loader-glow {
            0%, 100% { opacity: .55; transform: scale(.92); }
            50%      { opacity: 1;   transform: scale(1.1); }
        }

        @keyframes loader-shine {
            0%       { transform: translateX(-120%); }
            60%, 100% { transform: translateX(120%); }
        }

        @keyframes loader-bar {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(250%); }
        }

        /* احترام إعداد "تقليل الحركة" في الجهاز */
        @media (prefers-reduced-motion: reduce) {
            #page-loader .loader-content,
            #page-loader .loader-glow,
            #page-loader .loader-logo-wrap,
            #page-loader .loader-logo-wrap::after,
            #page-loader .loader-bar::before {
                animation: none !important;
            }

            #page-loader .loader-bar::before { width: 100%; }
        }
    </style>
</head>

<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased"
    x-data="agencyLayout(@js($openGroup))">

    {{-- غلاف التحميل --}}
    <div id="page-loader">
        <div class="loader-content">
            <div class="loader-stage">
                <span class="loader-glow"></span>
                <div class="loader-logo-wrap">
                    <img src="{{ asset('images/IconP.jpeg') }}" alt="عبور Abur" class="loader-logo">
                </div>
            </div>
            <div class="loader-bar"></div>
        </div>
    </div>
    <noscript>
        <style>
            #page-loader { display: none }
        </style>
    </noscript>

    <script>
        (function() {
            const loader = document.getElementById('page-loader');
            if (!loader) return;

            const MIN_SHOW = 500; // أقل مدة يظهر فيها الغلاف (ms) لتجنب الوميض
            let shownAt = performance.now();

            const hide = () => {
                const wait = Math.max(0, MIN_SHOW - (performance.now() - shownAt));
                setTimeout(() => loader.classList.add('hide'), wait);
            };
            const show = () => {
                shownAt = performance.now();
                loader.classList.remove('hide');
            };

            // متاحة للاستخدام اليدوي: window.pageLoader.show() / .hide()
            window.pageLoader = { show, hide };

            // إخفاء الغلاف بعد اكتمال تحميل الصفحة
            window.addEventListener('load', hide);

            // أمان: لو تأخر شي لا يبقى الغلاف للأبد
            setTimeout(hide, 8000);

            // عند الرجوع بزر "رجوع" والصفحة جاية من الكاش
            window.addEventListener('pageshow', e => {
                if (e.persisted) loader.classList.add('hide');
            });

            // إظهار الغلاف عند الانتقال لصفحة ثانية
            document.addEventListener('click', e => {
                const a = e.target.closest('a');
                if (!a || e.defaultPrevented) return;
                if (a.target === '_blank' || a.hasAttribute('download')) return;
                if (e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;

                const href = a.getAttribute('href');
                if (!href || href.startsWith('#') || /^(javascript|mailto|tel):/.test(href)) return;
                if (a.origin !== location.origin) return;

                show();
            });

            // إظهاره عند إرسال النماذج (احذف هذا الجزء إذا ما تبيه)
            document.addEventListener('submit', e => {
                if (!e.defaultPrevented && !e.target.hasAttribute('data-no-loader')) show();
            });
        })();
    </script>

    <!-- تعتيم الشاشة عند فتح القائمة في الجوال -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 md:hidden">
    </div>

    <!-- ═════════════ القائمة الجانبية ═════════════ -->
    <aside
        :class="[
            sidebarOpen ? 'translate-x-0' : 'translate-x-full md:translate-x-0',
            compact ? 'md:w-20' : 'md:w-64',
            ready ? 'transition-all duration-300 ease-in-out' : ''
        ]"
        class="fixed md:relative inset-y-0 right-0 w-64 bg-slate-900 text-white flex flex-col z-50 shadow-2xl shrink-0">

        <!-- سهم الطي / التوسيع (ديسكتوب فقط) -->
        <button type="button" @click="collapsed = !collapsed"
            :title="collapsed ? 'توسيع القائمة' : 'إخفاء القائمة'"
            :aria-label="collapsed ? 'توسيع القائمة' : 'إخفاء القائمة'"
            class="hidden md:flex absolute -left-3 top-7 z-10 w-6 h-6 items-center justify-center rounded-full bg-white text-slate-600 border border-slate-200 shadow-md hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
            <svg class="w-3 h-3 transition-transform duration-300" :class="compact ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
            </svg>
        </button>

        <!-- الشعار -->
        <div class="h-20 flex justify-center items-center px-4 border-b border-slate-800/80 relative shrink-0">
            <img src="{{ asset('images/logo.jpeg') }}" alt="شعار عبور"
                class="w-auto object-contain transition-all duration-300" :class="compact ? 'h-10' : 'h-16'">

            <!-- زر إغلاق القائمة في الجوال -->
            <button type="button" @click="sidebarOpen = false"
                class="md:hidden absolute left-4 text-slate-400 hover:text-white p-1" aria-label="إغلاق">✕</button>
        </div>

        <!-- روابط التنقل -->
        <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto overflow-x-hidden custom-scrollbar">

            {{-- الرئيسية --}}
            @if ($hasPermission('dashboard'))
                <a href="{{ route('agency.dashboard') }}" title="الرئيسية"
                    :class="compact ? 'justify-center px-0' : 'px-4'"
                    class="{{ $linkBase }} py-3 {{ $current == 'agency.dashboard' ? 'active-link' : $linkIdle }}">
                    <span class="text-base" :class="compact ? '' : 'ml-3'">🏠</span>
                    <span x-show="!compact">الرئيسية</span>
                </a>
            @endif

            @if ($agencyStatus === 'active')
                @foreach ($menu as $group)
                    @php
                        $key = $group['key'];
                        $groupActive = in_array($current, array_column($group['items'], 0));
                    @endphp

                    <div>
                        {{-- رأس المجموعة --}}
                        <button type="button" @click="toggleGroup('{{ $key }}')" title="{{ $group['label'] }}"
                            :class="[
                                compact ? 'justify-center px-0' : 'justify-between px-4',
                                openGroup === '{{ $key }}' && !compact ? 'bg-slate-800/50 text-white' : 'text-slate-300'
                            ]"
                            class="w-full flex items-center py-3 rounded-xl transition-all duration-200 font-bold text-xs hover:bg-slate-800/60">
                            <span class="flex items-center">
                                <span class="text-base relative" :class="compact ? '' : 'ml-3'">
                                    {{ $group['icon'] }}
                                    @if ($groupActive)
                                        <span x-show="compact"
                                            class="absolute -top-0.5 -right-1 w-2 h-2 rounded-full bg-blue-500"></span>
                                    @endif
                                </span>
                                <span x-show="!compact">{{ $group['label'] }}</span>
                                @if ($groupActive)
                                    <span x-show="!compact" class="mr-2 w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                @endif
                            </span>
                            <svg x-show="!compact" class="acc-chevron w-3.5 h-3.5 text-slate-500"
                                :class="openGroup === '{{ $key }}' ? 'rotated' : ''" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- الروابط الفرعية --}}
                        <div class="acc-body" x-show="!compact" :class="openGroup === '{{ $key }}' ? 'open' : ''">
                            <div class="acc-inner">
                                <div class="sub-links mt-1 mb-2 space-y-0.5">
                                    @foreach ($group['items'] as [$routeName, $icon, $label])
                                        <a href="{{ route($routeName) }}"
                                            class="{{ $linkBase }} px-3 py-2.5 {{ $current == $routeName ? 'active-link' : $linkIdle }}">
                                            <span class="ml-3 text-sm">{{ $icon }}</span> {{ $label }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </nav>

        <!-- تسجيل الخروج -->
        <div class="p-4 border-t border-slate-800/80 bg-slate-950/40 shrink-0">
            <button type="button" title="تسجيل الخروج"
                onclick="window.pageLoader && window.pageLoader.show(); document.getElementById('agency-logout-form').submit();"
                :class="compact ? 'justify-center px-0' : 'px-4'"
                class="flex items-center w-full py-3 text-red-400 hover:bg-red-950/40 hover:text-red-300 rounded-xl transition-all font-bold text-xs">
                <span class="text-base" :class="compact ? '' : 'ml-3'">🚪</span>
                <span x-show="!compact">تسجيل الخروج</span>
            </button>
            <form id="agency-logout-form" action="{{ route('agency.logout') }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>
    </aside>

    <!-- ═════════════ منطقة المحتوى ═════════════ -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        <!-- الهيدر العلوي -->
        <header class="h-20 bg-white border-b border-slate-200/70 flex items-center justify-between px-4 md:px-8 z-10 shrink-0 shadow-sm">

            <div class="flex items-center gap-3">
                <!-- زر فتح القائمة في الجوال -->
                <button type="button" @click="sidebarOpen = true" aria-label="فتح القائمة"
                    class="md:hidden p-2 text-slate-600 hover:text-slate-900 bg-slate-100 rounded-xl focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- بيانات المستخدم -->
                <div class="flex flex-col">
                    <h2 class="text-slate-800 font-black text-base md:text-lg tracking-tight">أهلاً بك، {{ $userName }}</h2>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="text-xs text-slate-500 font-bold">{{ $agencyName }}</span>
                        @if ($isEmployee)
                            <span class="bg-blue-50 text-blue-700 border border-blue-200/60 text-[9px] px-2 py-0.5 rounded-md font-extrabold">
                                موظف ({{ $currentUser->role === 'admin' ? 'مدير نظام' : 'محدود' }})
                            </span>
                        @else
                            <span class="bg-emerald-50 text-emerald-700 border border-emerald-200/60 text-[9px] px-2 py-0.5 rounded-md font-extrabold">
                                مالك الوكالة
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- حالة الوكالة والأيقونة -->
            <div class="flex items-center gap-4">
                <div class="hidden sm:flex flex-col text-left">
                    <span class="text-[10px] text-slate-400 font-black tracking-wider">حالة الوكالة</span>
                    @if ($agencyStatus === 'pending')
                        <span class="text-xs text-amber-600 font-black flex items-center gap-1">
                            <span class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></span> قيد المراجعة
                        </span>
                    @elseif ($agencyStatus === 'blocked')
                        <span class="text-xs text-red-600 font-black flex items-center gap-1">
                            <span class="w-2 h-2 bg-red-500 rounded-full"></span> محظورة
                        </span>
                    @else
                        <span class="text-xs text-emerald-600 font-black flex items-center gap-1">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full"></span> معتمدة
                        </span>
                    @endif
                </div>

                <div class="w-10 h-10 bg-slate-100 rounded-2xl flex items-center justify-center text-blue-600 border border-slate-200/80 shadow-sm overflow-hidden shrink-0">
                    @if ($agencyIcon)
                        <img src="{{ asset('storage/' . $agencyIcon) }}" alt="Icon" class="w-full h-full object-cover">
                    @else
                        <span class="text-lg">🏢</span>
                    @endif
                </div>
            </div>
        </header>

        <!-- محتوى الصفحات -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 md:p-8">
            <div class="max-w-7xl mx-auto space-y-6">

                {{-- تنبيه عند حظر الوكالة أو تعليقها --}}
                @if (in_array($agencyStatus, ['pending', 'blocked']) && $current !== 'agency.dashboard')
                    <div class="bg-red-50 border border-red-200 p-4 rounded-2xl shadow-sm text-right" dir="rtl">
                        <div class="flex items-start gap-3">
                            <span class="text-red-500 text-xl">⚠️</span>
                            <div>
                                <h3 class="text-xs font-black text-red-800">الحساب غير نشط</h3>
                                <p class="text-xs text-red-600 font-bold mt-1">
                                    عذراً، حساب الوكالة غير نشط حالياً. يرجى التواصل مع الإدارة لتفعيل الحساب لتتمكن من الوصول لجميع الصلاحيات.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    @yield('content')
                @endif

            </div>
        </main>
    </div>

</body>

</html>