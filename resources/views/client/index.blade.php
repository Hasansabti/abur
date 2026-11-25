@extends('layouts.client')

@section('content')
    <style>
        /* إظهار وإخفاء النصوص بناءً على لغة الصفحة التي تحددها أداة الترجمة */
        html[lang="en"] .show-ar {
            display: none !important;
        }

        html[lang="en"] .show-en {
            display: inline-block !important;
        }

        html[lang="ar"] .show-ar {
            display: inline-block !important;
        }

        html[lang="ar"] .show-en {
            display: none !important;
        }
    </style>
    <div class="w-full min-h-screen py-8 sm:py-12 relative bg-slate-50 font-['Tajawal'] overflow-hidden">

        {{-- ================= خلفية خفيفة جداً (بدون فلاتر تسبب تعليق) ================= --}}
        <div class="absolute inset-0 z-0 pointer-events-none">
            {{-- الصورة الثابتة --}}
            <div class="absolute inset-0 bg-cover bg-center lg:bg-right bg-no-repeat opacity-40 lg:opacity-100"
                style="background-image: url('{{ asset('images/main.jpeg') }}');">
            </div>

            {{-- تدرج لوني خفيف يغطي الصورة لضمان وضوح النص بدون استخدام blur ثقيل --}}
            <div
                class="absolute inset-0 bg-gradient-to-t from-slate-50 via-slate-50/90 to-slate-50/40 lg:bg-gradient-to-l lg:from-slate-50 lg:via-slate-50/95 lg:to-transparent">
            </div>
        </div>

        {{-- ================= المحتوى ================= --}}
        <div class="relative z-10 container mx-auto px-5 sm:px-6 max-w-7xl flex flex-col items-start" dir="rtl">

            {{-- 1. النصوص الترحيبية --}}
            <div class="text-right mb-10 max-w-2xl w-full animate-fade">
                <!-- العنوان باللغة العربية -->
                <h1
                    class="show-ar text-4xl sm:text-5xl lg:text-7xl font-black text-slate-900 dark:text-slate-100 leading-[1.3] mb-5">
                    رحلتك تبدأ <br>مع <span class="text-indigo-600 inline-block notranslate">عبور</span>
                </h1>

                <!-- العنوان باللغة الإنجليزية (مصمم ليكون مرتباً مع notranslate لمنع التداخل) -->
                <h1
                    class="show-en text-4xl sm:text-5xl lg:text-7xl font-black text-slate-900 dark:text-slate-100 leading-[1.3] mb-5 notranslate">
                    Your Journey Starts <br>with <span class="text-indigo-600 inline-block">Abur</span>
                </h1>

                {{-- صندوق نصوص بلون سادة (بدون شفافية زجاجية ثقيلة) --}}
                <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-slate-100 flex flex-col gap-3">
                    <p class="text-lg sm:text-xl md:text-2xl text-slate-800 font-bold leading-snug">
                        منصة ذكية تجمع أفضل وكالات السفر والرحلات في مكان واحد.
                    </p>
                    <p class="text-base sm:text-lg text-slate-600 font-medium leading-relaxed">
                        حجز آمن بالكامل • رحلات مجدولة ومنظمة بدقة • خيارات متعددة تتناسب مع وجهتك القادمة.
                    </p>
                </div>
            </div>

            {{-- 2. أزرار التنقل الرئيسية --}}
            <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto mb-14 animate-fade delay-100">
                {{-- الزر الأساسي --}}
                <a href="{{ route('client.trips') }}"
                    class="w-full sm:w-auto px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-lg text-center rounded-xl shadow-lg shadow-indigo-200 transition-colors flex items-center justify-center gap-3 group">
                    <span>استكشف الرحلات الحالية</span>
                    <svg class="w-6 h-6 transform group-hover:-translate-x-1 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>

                {{-- الزر الثانوي --}}
                <a href="{{ route('client.agency.agencies') }}"
                    class="w-full sm:w-auto px-8 py-4 bg-white hover:bg-slate-50 text-slate-800 font-bold text-lg text-center rounded-xl border-2 border-slate-200 shadow-sm transition-colors flex items-center justify-center gap-3">
                    <span>تواصل مع الوكالات</span>
                    <span class="text-xl">🏢</span>
                </a>
            </div>

            {{-- 3. كروت الميزات الأربعة (تصميم نظيف وسريع) --}}
            <div
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 w-full max-w-7xl mb-14 animate-fade delay-200">

                {{-- كرت: دعم --}}
                <div
                    class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow flex items-start gap-4 text-right">
                    <div
                        class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-2xl shrink-0">
                        📞</div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-lg">دعم متواصل</h4>
                        <p class="text-sm sm:text-base text-slate-500 mt-1 leading-relaxed">فريق في خدمتك للإجابة على
                            استفساراتك.</p>
                    </div>
                </div>

                {{-- كرت: شركاء موثوقين --}}
                <div
                    class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow flex items-start gap-4 text-right">
                    <div
                        class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-2xl shrink-0">
                        🤝</div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-lg">شركاء موثوقين</h4>
                        <p class="text-sm sm:text-base text-slate-500 mt-1 leading-relaxed">نعمل مع أفضل وكالات النقل
                            المرخصة.</p>
                    </div>
                </div>

                {{-- كرت: رحلات منظمة --}}
                <div
                    class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow flex items-start gap-4 text-right">
                    <div
                        class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-2xl shrink-0">
                        📅</div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-lg">رحلات منظمة</h4>
                        <p class="text-sm sm:text-base text-slate-500 mt-1 leading-relaxed">مواعيد انطلاق دقيقة وجداول مرنة.
                        </p>
                    </div>
                </div>

                {{-- كرت: جودة الخدمة --}}
                <div
                    class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-shadow flex items-start gap-4 text-right">
                    <div
                        class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center text-2xl shrink-0">
                        ⭐</div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-lg">جودة الخدمة</h4>
                        <p class="text-sm sm:text-base text-slate-500 mt-1 leading-relaxed">تجربة حجز سلسة تضمن لك جودة
                            وراحة.</p>
                    </div>
                </div>

            </div>

            {{-- 4. السلايدر (تم تخفيف حركاته ليكون أسرع على المتصفح) --}}
            <div x-data="{
                activeSlide: 0,
                slides: [
                    { src: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=1200&q=80', title: 'اكتشف وجهاتنا الساحلية', desc: 'استمتع بأجمل الشواطئ الهادئة والاسترخاء المثالي.' },
                    { src: 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=1200&q=80', title: 'خطط لرحلتك بكل ثقة', desc: 'كل ما تحتاجه لتبدأ مغامرتك الجديدة بأمان.' },
                    { src: 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=1200&q=80', title: 'عروض حصرية', desc: 'أسعار تنافسية توفر لك رحلة مريحة لا تُنسى.' }
                ],
                next() { this.activeSlide = (this.activeSlide + 1) % this.slides.length },
                prev() { this.activeSlide = (this.activeSlide - 1 + this.slides.length) % this.slides.length }
            }" x-init="setInterval(() => next(), 5000)"
                class="relative w-full max-w-7xl h-[300px] sm:h-[400px] lg:h-[500px] rounded-2xl overflow-hidden shadow-lg bg-slate-900 group mb-10 animate-fade delay-300">

                <div class="relative w-full h-full flex transition-transform duration-700 ease-in-out"
                    :style="`transform: translateX(${activeSlide * 100}%)`" dir="rtl">
                    <template x-for="(slide, index) in slides" :key="index">
                        <div class="w-full h-full flex-shrink-0 relative">
                            <img :src="slide.src" class="w-full h-full object-cover" alt="Slide Image" loading="lazy">

                            {{-- طبقة تظليل بسيطةوفعالة --}}
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent flex flex-col justify-end p-6 sm:p-10 text-right">
                                <span
                                    class="bg-indigo-600 text-white font-bold text-sm px-3 py-1 rounded-full w-max mb-3">وجهات
                                    عبور</span>
                                <h2 class="text-white text-2xl sm:text-4xl font-black mb-2" x-text="slide.title"></h2>
                                <p class="text-slate-200 text-base sm:text-lg max-w-2xl" x-text="slide.desc"></p>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- أزرار تحكم بسيطة --}}
                <button @click="prev()"
                    class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/20 hover:bg-white text-white hover:text-slate-900 rounded-full flex items-center justify-center transition-colors backdrop-blur-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <button @click="next()"
                    class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/20 hover:bg-white text-white hover:text-slate-900 rounded-full flex items-center justify-center transition-colors backdrop-blur-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            </div>

        </div>
    </div>

    {{-- ================= التنسيقات خفيفة الوزن ================= --}}
    <style>
        /* حركة دخول سريعة ونظيفة لا تستهلك الموارد */
        .animate-fade {
            opacity: 0;
            animation: fastFadeIn 0.5s ease-out forwards;
        }

        .delay-100 {
            animation-delay: 0.1s;
        }

        .delay-200 {
            animation-delay: 0.2s;
        }

        .delay-300 {
            animation-delay: 0.3s;
        }

        @keyframes fastFadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endsection
