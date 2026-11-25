<!DOCTYPE html>
<html lang="ar" dir="rtl" x-data="{
    mobileMenuOpen: false,
    supportModalOpen: false,
    darkMode: localStorage.getItem('theme') === 'dark'
}" x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))" :class="{ 'dark': darkMode }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title class="notranslate">عُبور Abur - منصة حجز الرحلات</title>
    <script>
        if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
    </script>

    <link rel="icon" type="image/jpeg" href="{{ asset('images/DF.jpeg') }}">
    <script src="{{ asset('js/select_seat.js') }}" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&display=swap" rel="stylesheet">

    <script>
        window.gtranslateSettings = {
            "default_language": "ar",
            "languages": ["ar", "en"],
            "wrapper_selector": ".gtranslate_wrapper",
            "native_language_names": true,
            "flag_size": 18,
            "alt_flags": {
                "en": "usa"
            }
        };
    </script>
    <script src="https://cdn.gtranslate.net/widgets/latest/dropdown.js" defer></script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        body {
            font-family: 'Tajawal', system-ui, -apple-system, sans-serif;
        }

        .gtranslate_wrapper select {
            background-color: transparent !important;
            border: none !important;
            font-size: 0.8rem !important;
            font-weight: 800 !important;
            color: inherit !important;
            outline: none !important;
            cursor: pointer;
            padding: 0.2rem 0.4rem !important;
        }

        .dark .gtranslate_wrapper select option {
            background-color: #0f172a !important;
            color: #f8fafc !important;
        }

        /* ===== إصلاح عام للوضع الليلي (يستثني أي عنصر عنده dark: خاص فيه) ===== */

        /* الخلفيات */
        .dark :is(.bg-white, .bg-gray-50, .bg-slate-50, .bg-slate-50\/50, .bg-slate-50\/70):not([class*="dark:bg-"]) {
            background-color: #0f172a;
        }

        .dark :is(.bg-slate-100, .bg-gray-100, .bg-slate-200):not([class*="dark:bg-"]) {
            background-color: #1e293b;
        }

        /* النصوص */
        .dark :is(.text-black, .text-slate-950, .text-slate-900, .text-slate-800, .text-gray-900, .text-gray-800):not([class*="dark:text-"]) {
            color: #f1f5f9;
        }

        .dark :is(.text-slate-700, .text-slate-600, .text-gray-700, .text-gray-600):not([class*="dark:text-"]) {
            color: #cbd5e1;
        }

        .dark :is(.text-slate-500, .text-gray-500):not([class*="dark:text-"]) {
            color: #94a3b8;
        }

        /* الحدود */
        .dark :is(.border-slate-100, .border-slate-200, .border-gray-100, .border-gray-200, .border-slate-200\/60, .border-slate-200\/80, .border-slate-300):not([class*="dark:border-"]) {
            border-color: #1e293b;
        }

        .dark :is(.divide-slate-100, .divide-gray-100)> :not([hidden])~ :not([hidden]) {
            border-color: #1e293b;
        }

        /* الحقول */
        .dark :is(input, select, textarea):not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([class*="dark:bg-"]) {
            background-color: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
        }

        .dark :is(input, textarea)::placeholder {
            color: #64748b;
        }

        .dark select option {
            background-color: #0f172a;
            color: #f8fafc;
        }

        /* الألوان الملونة الفاتحة (تنبيهات، شارات، حالات) */
        .dark :is(.bg-emerald-50, .bg-emerald-100, .bg-emerald-100\/70):not([class*="dark:bg-"]) {
            background-color: rgba(16, 185, 129, .14);
        }

        .dark :is(.bg-amber-50, .bg-amber-100, .bg-amber-50\/70, .bg-amber-50\/50, .bg-amber-100\/70):not([class*="dark:bg-"]) {
            background-color: rgba(245, 158, 11, .14);
        }

        .dark :is(.bg-rose-50, .bg-rose-100, .bg-rose-50\/40, .bg-rose-50\/50, .bg-rose-100\/70):not([class*="dark:bg-"]) {
            background-color: rgba(244, 63, 94, .14);
        }

        .dark :is(.bg-sky-50, .bg-sky-100, .bg-blue-50, .bg-blue-100):not([class*="dark:bg-"]) {
            background-color: rgba(14, 165, 233, .14);
        }

        .dark :is(.bg-indigo-50, .bg-indigo-100, .bg-indigo-50\/50, .bg-indigo-100\/70):not([class*="dark:bg-"]) {
            background-color: rgba(99, 102, 241, .16);
        }

        .dark :is(.text-emerald-600, .text-emerald-700, .text-emerald-800):not([class*="dark:text-"]) {
            color: #6ee7b7;
        }

        .dark :is(.text-amber-600, .text-amber-700, .text-amber-800, .text-amber-900, .text-amber-950):not([class*="dark:text-"]) {
            color: #fcd34d;
        }

        .dark :is(.text-rose-600, .text-rose-700, .text-rose-800, .text-rose-900):not([class*="dark:text-"]) {
            color: #fda4af;
        }

        .dark :is(.text-sky-600, .text-sky-700, .text-blue-600, .text-blue-700):not([class*="dark:text-"]) {
            color: #7dd3fc;
        }

        .dark :is(.text-indigo-600, .text-indigo-700):not([class*="dark:text-"]) {
            color: #a5b4fc;
        }

        .dark :is(.border-emerald-100, .border-emerald-200):not([class*="dark:border-"]) {
            border-color: rgba(16, 185, 129, .3);
        }

        .dark :is(.border-amber-100, .border-amber-200, .border-amber-300):not([class*="dark:border-"]) {
            border-color: rgba(245, 158, 11, .3);
        }

        .dark :is(.border-rose-100, .border-rose-200, .border-rose-300):not([class*="dark:border-"]) {
            border-color: rgba(244, 63, 94, .3);
        }

        .dark :is(.border-sky-100, .border-indigo-100, .border-blue-100):not([class*="dark:border-"]) {
            border-color: rgba(99, 102, 241, .3);
        }

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

        .dark #page-loader {
            background: radial-gradient(circle at 50% 40%, #0b1226 0%, #020617 100%);
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

        .dark #page-loader .loader-glow {
            background: radial-gradient(circle, rgba(96, 165, 250, .45) 0%, rgba(96, 165, 250, 0) 70%);
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

        .dark #page-loader .loader-logo-wrap {
            background: #0f172a;
            box-shadow: 0 24px 50px -16px rgba(0, 0, 0, .7), 0 0 0 1px rgba(148, 163, 184, .15);
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
            from {
                opacity: 0;
                transform: translateY(14px) scale(.9);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes loader-float {

            0%,
            100% {
                transform: translateY(0) scale(1);
            }

            50% {
                transform: translateY(-10px) scale(1.04);
            }
        }

        @keyframes loader-glow {

            0%,
            100% {
                opacity: .55;
                transform: scale(.92);
            }

            50% {
                opacity: 1;
                transform: scale(1.1);
            }
        }

        @keyframes loader-shine {
            0% {
                transform: translateX(-120%);
            }

            60%,
            100% {
                transform: translateX(120%);
            }
        }

        @keyframes loader-bar {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(250%);
            }
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

            #page-loader .loader-bar::before {
                width: 100%;
            }
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const htmlElement = document.documentElement;

            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === "lang") {
                        const currentLang = htmlElement.getAttribute("lang");
                        htmlElement.setAttribute("dir", currentLang === "ar" ? "rtl" : "ltr");
                    }
                });
            });

            observer.observe(htmlElement, {
                attributes: true,
                attributeFilter: ["lang"]
            });
        });
    </script>

    {{-- 🧭 مكوّن الجولة التعليمية (Site Tour) --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('siteTour', () => ({
                open: false,
                stepIndex: 0,
                steps: [],
                rect: {
                    top: 0,
                    left: 0,
                    width: 0,
                    height: 0
                },

                // للمستخدم المسجل: القيمة تجي من قاعدة البيانات (مصدر الحقيقة الوحيد)
                isAuth: @json(Auth::check()),
                alreadySeen: @json(Auth::check() ? (bool) (Auth::user()->tour_seen ?? false) : false),
                // للزائر غير المسجل فقط: نعتمد على المتصفح لعدم وجود حساب نربطها فيه
                guestKey: 'abur_tour_seen_guest',

                allSteps: [{
                        id: 'logo',
                        title: 'أهلاً بك في عُبور 👋',
                        text: 'هذا شعار المنصة، اضغط عليه في أي وقت للعودة إلى الصفحة الرئيسية.'
                    },
                    {
                        id: 'mobile-menu',
                        title: 'القائمة الرئيسية',
                        text: 'اضغط هنا لفتح كل روابط الموقع على شاشات الجوال.'
                    },
                    {
                        id: 'nav-trips',
                        title: 'تصفح الرحلات',
                        text: 'من هنا تتصفح جميع الرحلات المتاحة بين المدن وتحجز مقعدك بسهولة.'
                    },
                    {
                        id: 'nav-agencies',
                        title: 'الوكالات السياحية',
                        text: 'تعرّف على الوكالات المعتمدة التي تنظّم الرحلات على المنصة.'
                    },
                    {
                        id: 'nav-bookings',
                        title: 'تذاكرك المحجوزة',
                        text: 'هنا تجد كل حجوزاتك وتذاكرك السابقة والقادمة في مكان واحد.'
                    },
                    {
                        id: 'nav-private',
                        title: 'طلب رحلة خاصة',
                        text: 'تحتاج رحلة مخصّصة لمجموعتك؟ قدّم طلبك الخاص من هنا.'
                    },
                    {
                        id: 'nav-about',
                        title: 'تعرّف علينا',
                        text: 'صفحة تشرح من نحن ورسالتنا في تنظيم رحلاتك.'
                    },
                    {
                        id: 'nav-profile',
                        title: 'ملفك الشخصي',
                        text: 'عدّل بياناتك الشخصية وصورتك الرمزية من هذه الصفحة.'
                    },
                    {
                        id: 'nav-support',
                        title: 'الدعم الفني',
                        text: 'واجهتك مشكلة؟ افتح تذكرة دعم وتابع الردّ عليها من هنا.'
                    },
                    {
                        id: 'lang-switch',
                        title: 'تغيير اللغة',
                        text: 'يمكنك تبديل لغة الموقع بين العربية والإنجليزية من هنا.'
                    },
                    {
                        id: 'dark-toggle',
                        title: 'الوضع الليلي',
                        text: 'بدّل بين المظهر الفاتح والداكن حسب راحة عينيك.'
                    },
                    {
                        id: 'auth-area',
                        title: 'حسابك',
                        text: 'من هنا تسجّل الدخول أو تنشئ حساباً جديداً لتبدأ الحجز.'
                    },
                ],

                init() {
                    const seen = this.isAuth ?
                        this.alreadySeen :
                        localStorage.getItem(this.guestKey) === 'true';

                    if (!seen) {
                        setTimeout(() => this.start(), 900);
                    }

                    window.addEventListener('resize', () => {
                        if (this.open) this.updateRect();
                    });
                },

                visibleSteps() {
                    return this.allSteps.filter(s => {
                        const el = document.querySelector(`[data-tour="${s.id}"]`);
                        return el && el.offsetParent !== null;
                    });
                },

                start() {
                    this.steps = this.visibleSteps();
                    if (!this.steps.length) return;
                    this.stepIndex = 0;
                    this.open = true;
                    this.$nextTick(() => this.updateRect());
                },

                get current() {
                    return this.steps[this.stepIndex] || null;
                },

                updateRect() {
                    if (!this.current) return;
                    const el = document.querySelector(`[data-tour="${this.current.id}"]`);
                    if (!el) {
                        this.next();
                        return;
                    }
                    el.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    setTimeout(() => {
                        const r = el.getBoundingClientRect();
                        this.rect = {
                            top: r.top,
                            left: r.left,
                            width: r.width,
                            height: r.height
                        };
                    }, 300);
                },

                next() {
                    if (this.stepIndex < this.steps.length - 1) {
                        this.stepIndex++;
                        this.$nextTick(() => this.updateRect());
                    } else {
                        this.finish();
                    }
                },

                prev() {
                    if (this.stepIndex > 0) {
                        this.stepIndex--;
                        this.$nextTick(() => this.updateRect());
                    }
                },

                skip() {
                    this.finish();
                },

                finish() {
                    this.open = false;

                    if (this.isAuth) {
                        fetch('{{ route('tour.seen') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]')?.content ?? ''
                                }
                            })
                            .then(res => {
                                if (!res.ok) {
                                    console.error('Tour seen request failed:', res.status, res
                                        .statusText);
                                } else {
                                    console.log('Tour marked as seen successfully');
                                }
                            })
                            .catch(err => console.error('Tour seen network error:', err));
                    } else {
                        localStorage.setItem(this.guestKey, 'true');
                    }
                },

                tooltipStyle() {
                    const margin = 16;
                    const tooltipWidth = 360;
                    let top = this.rect.top + this.rect.height + margin;
                    let left = Math.min(
                        Math.max(margin, this.rect.left),
                        window.innerWidth - tooltipWidth - margin
                    );
                    if (top + 200 > window.innerHeight) {
                        top = Math.max(margin, this.rect.top - 200 - margin);
                    }
                    return `top:${top}px; left:${left}px;`;
                }
            }));
        });
    </script>
</head>

<body
    class="bg-gray-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex flex-col min-h-screen transition-colors duration-300">

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
            #page-loader {
                display: none
            }
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

            // إخفاء الغلاف بعد اكتمال تحميل الصفحة
            window.addEventListener('load', hide);

            // أمان: لو تأخر شي (مثل سكربت خارجي) لا يبقى الغلاف للأبد
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

    {{--  نظام الإشعارات العائمة التلقائي (النجاح / أخطاء الجلسة / أخطاء المدخلات) 🌟 --}}
    <div class="fixed top-24 start-6 z-[3000] max-w-sm w-full space-y-3 pointer-events-none" dir="rtl">

        {{-- إشعار النجاح --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                class="pointer-events-auto bg-white dark:bg-slate-900 border-r-4 border-emerald-500 shadow-2xl rounded-2xl p-4 flex items-center justify-between border border-slate-100 dark:border-slate-800"
                x-cloak>
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-slate-800 dark:text-slate-100">تمت العملية بنجاح</h4>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-0.5">{{ session('success') }}
                        </p>
                    </div>
                </div>
                <button @click="show = false"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        {{-- إشعار خطأ الجلسة العام --}}
        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                class="pointer-events-auto bg-white dark:bg-slate-900 border-r-4 border-rose-500 shadow-2xl rounded-2xl p-4 flex items-center justify-between border border-slate-100 dark:border-slate-800"
                x-cloak>
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-slate-800 dark:text-slate-100">تنبيه</h4>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-0.5">{{ session('error') }}
                        </p>
                    </div>
                </div>
                <button @click="show = false"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        {{-- إشعار أخطاء التحقق من الحقول (Validation Errors) --}}
        @if ($errors->any())
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 8000)"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                class="pointer-events-auto bg-white dark:bg-slate-900 border-r-4 border-rose-500 shadow-2xl rounded-2xl p-4 flex flex-col gap-2 border border-slate-100 dark:border-slate-800"
                x-cloak>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-rose-600 dark:text-rose-400">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <h4 class="text-xs font-black text-slate-800 dark:text-slate-100">يرجى تصحيح الأخطاء التالية:
                        </h4>
                    </div>
                    <button @click="show = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <ul class="text-xs font-bold text-slate-500 dark:text-slate-400 list-disc list-inside space-y-1 me-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

    </div>{{-- مسح مسودة الحجز المؤقتة بعد نجاح الدفع أو إلغاء الحجز فقط --}}
    @if (session('clear_booking_draft'))
        <script>
            try {
                Object.keys(sessionStorage)
                    .filter(k => k.startsWith('booking_draft_'))
                    .forEach(k => sessionStorage.removeItem(k));
            } catch (e) {}
        </script>
    @endif

    {{-- الهيدر والتنقل --}}
    <nav
        class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-md shadow-sm sticky top-0 z-50 border-b border-gray-100 dark:border-slate-800 transition-colors duration-300">
        <div class="container mx-auto px-4 sm:px-6 h-20 flex justify-between items-center gap-2">

            <div class="flex items-center gap-3 shrink-0">
                <button @click="mobileMenuOpen = !mobileMenuOpen" data-tour="mobile-menu"
                    class="block lg:hidden text-slate-600 dark:text-slate-300 hover:text-blue-600 p-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 transition-colors focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        :class="{ 'hidden': mobileMenuOpen, 'block': !mobileMenuOpen }">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        :class="{ 'block': mobileMenuOpen, 'hidden': !mobileMenuOpen }" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <a href="{{ route('client.index') }}" data-tour="logo" class="flex items-center gap-2 shrink-0">
                    <img src="{{ asset('images/logo.jpeg') }}" alt="عبور Abur"
                        class="h-12 sm:h-14 md:h-16 w-auto object-contain shrink-0 rounded-xl shadow-sm">
                </a>
            </div>

            <div class="flex items-center gap-3 sm:gap-4 h-full shrink-0">
                <div class="hidden lg:flex items-center gap-4 xl:gap-5 ms-2 h-full">
                    <a href="{{ route('client.index') }}"
                        class="relative font-bold text-sm transition-all duration-300 group py-2 {{ Route::is('client.index') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                        الرئيسية
                        <span
                            class="absolute bottom-[-10px] start-0 h-[3px] bg-blue-500 rounded-full transition-all duration-300 {{ Route::is('client.index') ? 'w-full' : 'w-0 group-hover:w-full' }}"></span>
                    </a>

                    @auth
                        <a href="{{ route('client.bookings') }}" data-tour="nav-bookings"
                            class="relative font-bold text-sm transition-all duration-300 group py-2 {{ Route::is('client.bookings') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                            تذاكري
                            <span
                                class="absolute bottom-[-10px] start-0 h-[3px] bg-blue-500 rounded-full transition-all duration-300 {{ Route::is('client.bookings') ? 'w-full' : 'w-0 group-hover:w-full' }}"></span>
                        </a>
                    @endauth
                    @auth
                        <a href="{{ route('client.trips.private_trip') }}" data-tour="nav-private"
                            class="relative font-bold text-sm transition-all duration-300 group py-2 {{ Route::is('client.trips.private_trip') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                            طلبات خاصة
                            <span
                                class="absolute bottom-[-10px] start-0 h-[3px] bg-blue-500 rounded-full transition-all duration-300 {{ Route::is('client.trips.private_trip') ? 'w-full' : 'w-0 group-hover:w-full' }}"></span>
                        </a>
                    @endauth

                    <a href="{{ route('client.trips') }}" data-tour="nav-trips"
                        class="relative font-bold text-sm transition-all duration-300 group py-2 {{ Route::is('client.trips') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                        الرحلات
                        <span
                            class="absolute bottom-[-10px] start-0 h-[3px] bg-blue-500 rounded-full transition-all duration-300 {{ Route::is('client.trips') ? 'w-full' : 'w-0 group-hover:w-full' }}"></span>
                    </a>

                    <a href="{{ route('client.agency.agencies') }}" data-tour="nav-agencies"
                        class="relative font-bold text-sm transition-all duration-300 group py-2 {{ Route::is('client.agency.agencies') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                        الوكالات
                        <span
                            class="absolute bottom-[-10px] start-0 h-[3px] bg-blue-500 rounded-full transition-all duration-300 {{ Route::is('client.agency.agencies') ? 'w-full' : 'w-0 group-hover:w-full' }}"></span>
                    </a>

                    <a href="{{ route('abur.we') }}" data-tour="nav-about"
                        class="relative font-bold text-sm transition-all duration-300 group py-2 {{ Route::is('abur.we') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                        من نحن
                        <span
                            class="absolute bottom-[-10px] start-0 h-[3px] bg-blue-500 rounded-full transition-all duration-300 {{ Route::is('abur.we') ? 'w-full' : 'w-0 group-hover:w-full' }}"></span>
                    </a>

                    <a href="{{ route('client.profile') }}" data-tour="nav-profile"
                        class="relative font-bold text-sm transition-all duration-300 group py-2 {{ Route::is('client.profile') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                        ملفي
                        <span
                            class="absolute bottom-[-10px] start-0 h-[3px] bg-blue-500 rounded-full transition-all duration-300 {{ Route::is('client.profile') ? 'w-full' : 'w-0 group-hover:w-full' }}"></span>
                    </a>
                    <a href="{{ route('client.tickets.index2') }}" data-tour="nav-support"
                        class="relative font-bold text-sm transition-all duration-300 group py-2 {{ Route::is('client.tickets.index2') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                        الدعم الفني
                        <span
                            class="absolute bottom-[-10px] start-0 h-[3px] bg-blue-500 rounded-full transition-all duration-300 {{ Route::is('client.tickets.index2') ? 'w-full' : 'w-0 group-hover:w-full' }}"></span>
                    </a>

                    <a href="{{ route('client.abur.connectUs') }}"
                        class="relative font-bold text-sm transition-all duration-300 group py-2 {{ Route::is('client.abur.connectUs') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                        تواصل معنا
                        <span
                            class="absolute bottom-[-10px] start-0 h-[3px] bg-blue-500 rounded-full transition-all duration-300 {{ Route::is('client.abur.connectUs') ? 'w-full' : 'w-0 group-hover:w-full' }}"></span>
                    </a>
                </div>

                <div data-tour="lang-switch"
                    class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200/60 dark:border-slate-700/60 hover:border-blue-500 dark:hover:border-blue-400 transition-all shadow-sm shrink-0">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3.6 9h16.8M3.6 15h16.8" />
                    </svg>
                    <div class="gtranslate_wrapper"></div>
                </div>

                <button @click="darkMode = !darkMode" type="button" title="تغيير المظهر" data-tour="dark-toggle"
                    class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-amber-400 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/60 dark:border-slate-700/60 transition-all active:scale-95 shrink-0">
                    <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <div class="flex items-center shrink-0" data-tour="auth-area">
                    @auth
                        <a href="{{ route('client.profile') }}" class="group">
                            <div class="flex items-center gap-3">
                                <div class="text-start hidden sm:block">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase leading-none mb-0.5">مرحباً بك
                                    </p>
                                    <p
                                        class="text-xs font-black text-gray-800 dark:text-slate-200 group-hover:text-blue-600 transition-colors max-w-[120px] truncate">
                                        {{ Auth::user()->name ?? 'ضيف' }}
                                    </p>
                                </div>

                                <div
                                    class="w-10 h-10 bg-blue-50 dark:bg-slate-800 rounded-full flex items-center justify-center text-blue-600 border border-blue-100 dark:border-slate-700 shadow-sm overflow-hidden group-hover:border-blue-400 transition-all shrink-0">
                                    @if (Auth::user()->avatar)
                                        <img src="{{ asset(Auth::user()->avatar) }}" alt="Avatar"
                                            class="w-full h-full object-cover">
                                    @else
                                        👤
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endauth

                    @guest
                        <div class="flex items-center gap-2">
                            <a href="{{ route('client.auth.register') }}"
                                class="hidden sm:flex items-center justify-center px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-black rounded-xl transition-all active:scale-95 text-center">
                                حساب جديد ✨
                            </a>
                            <a href="{{ route('client.auth.login') }}"
                                class="flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-xl transition-all shadow-md shadow-blue-600/10 active:scale-95 text-center">
                                تسجيل الدخول
                            </a>
                        </div>
                    @endguest
                </div>

            </div>
        </div>

        {{-- القائمة المنسدلة المخصصة للجوال --}}
        <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform -translate-y-4"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-4"
            class="lg:hidden bg-white dark:bg-slate-900 border-t border-gray-100 dark:border-slate-800 px-6 py-4 space-y-3 shadow-lg absolute w-full start-0 end-0 z-40"
            x-cloak>

            <div class="pb-3 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 dark:text-slate-400">اختر اللغة:</span>
                <div
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200/60 dark:border-slate-700">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="gtranslate_wrapper"></div>
                </div>
            </div>

            @auth
                <a href="{{ route('client.profile') }}"
                    class="flex items-center gap-3 pb-3 mb-2 border-b border-gray-100 dark:border-slate-800 active:bg-slate-50 dark:active:bg-slate-800 rounded-lg transition-colors">
                    <div
                        class="w-11 h-11 bg-blue-50 dark:bg-slate-800 rounded-full flex items-center justify-center text-blue-600 border border-blue-100 dark:border-slate-700 overflow-hidden shrink-0">
                        @if (Auth::user()->avatar)
                            <img src="{{ asset(Auth::user()->avatar) }}" alt="Avatar"
                                class="w-full h-full object-cover">
                        @else
                            👤
                        @endif
                    </div>
                    <div class="text-start">
                        <p class="text-[10px] text-gray-400 font-bold uppercase"> مرحبا </p>
                        <p class="text-sm font-black text-gray-800 dark:text-slate-200">{{ Auth::user()->name }}</p>
                    </div>
                </a>
            @endauth

            <a href="{{ route('client.index') }}"
                class="block py-3 px-4 font-bold rounded-xl text-sm transition-colors {{ Route::is('client.index') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                الرئيسية
            </a>
            <a href="{{ route('client.trips') }}"
                class="block py-3 px-4 font-bold rounded-xl text-sm transition-colors {{ Route::is('client.trips') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                الرحلات
            </a>
            <a href="{{ route('client.agency.agencies') }}"
                class="block py-3 px-4 font-bold rounded-xl text-sm transition-colors {{ Route::is('client.agency.agencies') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                الوكالات
            </a>
            <a href="{{ route('abur.we') }}"
                class="block py-3 px-4 font-bold rounded-xl text-sm transition-colors {{ Route::is('abur.we') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                من نحن
            </a>
            @auth
                <a href="{{ route('client.bookings') }}"
                    class="block py-3 px-4 font-bold rounded-xl text-sm transition-colors {{ Route::is('client.bookings') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                    تذاكري
                </a>
            @endauth
            @auth
                <a href="{{ route('client.trips.private_trip') }}"
                    class="block py-3 px-4 font-bold rounded-xl text-sm transition-colors {{ Route::is('client.trips.private_trip') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                    رحلات خاصة
                </a>
            @endauth

            <a href="{{ route('client.profile') }}"
                class="block py-3 px-4 font-bold rounded-xl text-sm transition-colors {{ Route::is('client.profile') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                ملفي
            </a>
            <a href="{{ route('client.tickets.index2') }}"
                class="block py-3 px-4 font-bold rounded-xl text-sm transition-colors {{ Route::is('client.tickets.index2') ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                الدعم الفني
            </a>
            <a href="{{ route('client.abur.connectUs') }}"
                @click.prevent="supportModalOpen = true; mobileMenuOpen = false"
                class="block py-3 px-4 font-bold rounded-xl text-sm text-blue-600 dark:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800">
                تواصل معنا
            </a>
            @auth
                <form action="{{ route('client.logout') }}" method="POST" class="w-full">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center justify-between py-3 px-4 font-bold rounded-xl text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-start">
                        <span>تسجيل الخروج</span>
                        <svg class="w-4 h-4 text-red-500 rtl:rotate-180" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V5" />
                        </svg>
                    </button>
                </form>
            @endauth
        </div>
    </nav>

    {{-- محتوى الصفحة المتغير --}}
    <main class="flex-grow">
        @yield('content')
    </main>

    {{-- الفوتر المطور --}}
    <footer
        class="bg-white dark:bg-slate-900 border-t border-gray-100 dark:border-slate-800 py-12 mt-20 text-start transition-colors duration-300">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-10">

                <div class="md:col-span-2">
                    <h4 class="text-2xl font-black text-blue-600 dark:text-blue-400 mb-3">
                        <span class="notranslate rtl:inline ltr:hidden">عُـبـور</span>
                        <span class="notranslate ltr:inline rtl:hidden">Abur</span>
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-slate-400 leading-relaxed max-w-sm">
                        المنصة الأولى لتنظيم وحجز رحلات الحافلات السياحية والطيران في المملكة العربية السعودية. نوفر لك
                        تجربة حجز متكاملة وآمنة.
                    </p>
                </div>

                <div>
                    <h5 class="text-sm font-black text-gray-800 dark:text-slate-200 uppercase tracking-wider mb-4">
                        خدماتنا</h5>
                    <ul class="space-y-2.5 text-sm font-bold text-gray-500 dark:text-slate-400">
                        <li><a href="{{ route('abur.policies') }}"
                                class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">السياسات</a>
                        </li>
                        <li><a href="{{ route('abur.we') }}"
                                class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">الأسئلة
                                الشائعة</a></li>
                        <li><a href="#" @click.prevent="supportModalOpen = true"
                                class="text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">الدعم
                                الفني 🛠️</a></li>
                    </ul>
                </div>

                <div>
                    <h5 class="text-sm font-black text-gray-800 dark:text-slate-200 uppercase tracking-wider mb-4">
                        روابط سريعة</h5>
                    <ul class="space-y-2.5 text-sm font-bold text-gray-500 dark:text-slate-400">
                        <li><a href="{{ route('client.trips') }}"
                                class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">الرحلات</a></li>
                        <li><a href="{{ route('client.agency.agencies') }}"
                                class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">الوكالات</a>
                        </li>
                        <li><a href="{{ route('abur.we') }}"
                                class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">من نحن</a></li>
                        <li><a href="{{ route('client.abur.connectUs') }}" @click.prevent="supportModalOpen = true"
                                class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">تواصل معنا</a>
                        </li>
                        <li><a href="{{ route('agency.login') }}"
                                class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">تسجيل دخول
                                الوكالة </a>
                        </li>
                    </ul>
                </div>

            </div>

            <hr class="my-8 border-gray-100 dark:border-slate-800">

            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 text-center">
                <div class="pt-4 text-xs text-slate-400 dark:text-slate-500 font-bold">
                    منصة
                    <span class="notranslate rtl:inline ltr:hidden">عبور</span>
                    <span class="notranslate ltr:inline rtl:hidden">Abur</span>
                    للسياحة والسفر © {{ date('Y') }} — وجهتك الأسرع لرحلات أكثر تنظيماً.
                </div>
                <div class="flex gap-4 text-xs font-bold text-gray-400 dark:text-slate-500">
                    <a href="{{ route('abur.policies') }}"
                        class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">سياسة الخصوصية</a>
                    <span>•</span>
                    <a href="{{ route('abur.policies') }}"
                        class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">شروط الاستخدام</a>
                </div>
            </div>
        </div>
    </footer>

    {{-- نافذة الدعم الفني المنبثقة --}}
    <div x-show="supportModalOpen" class="fixed inset-0 z-[100] overflow-y-auto"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>

        <div class="fixed inset-0 bg-slate-900/40 dark:bg-slate-950/70 backdrop-blur-sm"
            @click="supportModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center">
            <div x-show="supportModalOpen" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-[2rem] bg-white dark:bg-slate-900 p-6 text-start shadow-2xl transition-all w-full max-w-md border border-slate-100 dark:border-slate-800">

                <button @click="supportModalOpen = false"
                    class="absolute end-4 top-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="mb-6 flex items-center gap-3">
                    <div class="p-3 bg-blue-50 dark:bg-slate-800 text-blue-600 dark:text-blue-400 rounded-2xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18.364 5.636l-3.536 3.536m0 0A7.5 7.5 0 1010.59 13.01l4.242 4.242a1 1 0 001.415 0l4.242-4.242a1 1 0 000-1.414l-4.242-4.243a1 1 0 00-1.415 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-800 dark:text-slate-100">إنشاء تذكرة دعم فني</h3>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">يسعدنا مساعدتك، يرجى ملء التفاصيل
                            بالأسفل.</p>
                    </div>
                </div>

                <form action="{{ route('client.tickets.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">عنوان
                            التذكرة</label>
                        <input type="text" name="subject" required
                            placeholder="مثال: مشكلة في استرداد المبلغ، استفسار"
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">قسم
                            المساعدة</label>
                        <select name="category" required
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 transition-all">
                            <option value="general">استفسار عام</option>
                            <option value="changeD">تعديل بيانات</option>
                            <option value="booking">مشكلة في الحجز أو الدفع</option>
                            <option value="technical">عطل تقني في الموقع</option>
                            <option value="complaint">شكوى / اقتراح جديد</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">تفاصيل
                            التذكرة</label>
                        <textarea name="message" rows="4" required placeholder="يرجى كتابة تفاصيل المشكلة أو طلبك هنا بوضوح..."
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 transition-all resize-none"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <button type="button" @click="supportModalOpen = false"
                            class="w-full py-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-black rounded-xl transition-all active:scale-95 text-center">
                            إلغاء
                        </button>
                        <button type="submit"
                            class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-xl transition-all shadow-md shadow-blue-600/10 active:scale-95 text-center">
                            إرسال التذكرة 🚀
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{-- 🧭 الجولة التعليمية التفاعلية لعناصر الموقع --}}
    <div x-data="siteTour()">

        {{-- زر عائم لإعادة تشغيل الجولة يدوياً
        <button @click="start()" title="جولة تعريفية بالموقع" x-show="!open" x-cloak
            class="fixed bottom-6 start-6 z-[150] w-12 h-12 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-600/30 flex items-center justify-center text-xl transition-all active:scale-95">
            🧭
        </button> --}}

        {{-- طبقة الجولة: بقعة إضاءة + تلميح --}}
        <template x-if="open && current">
            <div class="fixed inset-0 z-[200]">

                {{-- طبقة تمنع التفاعل مع الصفحة أثناء الجولة --}}
                <div class="absolute inset-0"></div>

                {{-- بقعة الإضاءة حول العنصر الحالي --}}
                <div class="absolute rounded-2xl transition-all duration-300 ease-out ring-4 ring-blue-500"
                    :style="`top:${rect.top - 8}px; left:${rect.left - 8}px; width:${rect.width + 16}px; height:${rect.height + 16}px; box-shadow: 0 0 0 9999px rgba(15,23,42,0.72);`">
                </div>

                {{-- بطاقة الشرح --}}
                <div class="absolute w-[90vw] max-w-[360px] bg-white dark:bg-slate-900 rounded-2xl shadow-2xl p-5 border border-slate-100 dark:border-slate-800 transition-all duration-300"
                    :style="tooltipStyle()">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-black text-blue-600 dark:text-blue-400"
                            x-text="`الخطوة ${stepIndex + 1} من ${steps.length}`"></span>
                        <button @click="skip()"
                            class="text-xs font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">تخطي
                            الجولة</button>
                    </div>
                    <h4 class="text-sm font-black text-slate-800 dark:text-slate-100 mb-1" x-text="current.title">
                    </h4>
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 leading-relaxed mb-4"
                        x-text="current.text"></p>

                    <div class="flex items-center gap-1.5 mb-4">
                        <template x-for="(s, i) in steps" :key="i">
                            <span class="h-1.5 rounded-full transition-all duration-300"
                                :class="i === stepIndex ? 'w-5 bg-blue-600' : 'w-1.5 bg-slate-200 dark:bg-slate-700'"></span>
                        </template>
                    </div>

                    <div class="flex items-center justify-between gap-2">
                        <button @click="prev()" x-show="stepIndex > 0"
                            class="px-3 py-2 text-xs font-black rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                            السابق
                        </button>
                        <div class="flex-1"></div>
                        <button @click="next()"
                            class="px-4 py-2 text-xs font-black rounded-xl bg-blue-600 hover:bg-blue-700 text-white transition-colors"
                            x-text="stepIndex === steps.length - 1 ? 'إنهاء 🎉' : 'التالي'"></button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @stack('scripts')
</body>

</html>