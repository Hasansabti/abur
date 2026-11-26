@extends('layouts.client') {{-- أو الـ layout الأساسي لموقعك العام --}}

@section('title', 'من نحن | منصة عبور')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />

<div class="bg-slate-50 text-slate-800 min-h-screen font-['Cairo'] antialiased selection:bg-indigo-500/20 selection:text-indigo-900 overflow-hidden relative pb-12">

    <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-indigo-500/[0.04] rounded-full blur-[140px] pointer-events-none animate-pulse duration-[8000ms]"></div>
    <div class="absolute bottom-10 right-1/4 w-[500px] h-[500px] bg-emerald-500/[0.03] rounded-full blur-[160px] pointer-events-none animate-pulse duration-[6000ms]"></div>

    <div class="max-w-4xl mx-auto px-6 py-20 relative z-10 space-y-24 text-center">

        <div class="space-y-6 max-w-2xl mx-auto" data-aos="fade-down" data-aos-duration="1000">
            <div class="inline-flex items-center gap-2 bg-indigo-50 text-indigo-600 border border-indigo-100 px-4 py-1.5 rounded-full text-xs font-bold tracking-wide transition-all duration-300 hover:bg-indigo-100 cursor-default">
                <span class="animate-spin-slow">✨</span> هويتنا ورؤيتنا الرقمية
            </div>

            <h1 class="text-4xl md:text-5xl font-black tracking-tight bg-gradient-to-r from-slate-900 via-slate-700 to-indigo-700 bg-clip-text text-transparent italic">
                من نحن
            </h1>

            <p class="text-base md:text-lg text-slate-600 font-medium leading-relaxed text-center border-t border-b border-slate-200/60 py-8 mt-6 relative backdrop-blur-sm px-4 rounded-xl bg-white/40 shadow-sm shadow-slate-100">
                منصة رقمية متكاملة تربط المسافرين بوكالات السفر، تسهل اختيار الخيار الأنسب، وتتيح حجز الرحلات بكل سهولة وموثوقية، كما تمنح الوكالات أدوات ذكية لتحسين إدارة رحلاتهم وتنظيمها.
            </p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-6 pt-4" data-aos="fade-up" data-aos-duration="1200">
            <div class="bg-white border border-slate-100 p-6 rounded-2xl backdrop-blur-sm hover:scale-105 hover:shadow-md transition-all">
                <div class="text-2xl md:text-3xl font-black text-indigo-600 font-sans">+١٠,٠٠٠</div>
                <div class="text-xs text-slate-500 font-bold mt-2">مسافر يثق بنا</div>
            </div>
            <div class="bg-white border border-slate-100 p-6 rounded-2xl backdrop-blur-sm hover:scale-105 hover:shadow-md transition-all">
                <div class="text-2xl md:text-3xl font-black text-emerald-600 font-sans">+١٥٠</div>
                <div class="text-xs text-slate-500 font-bold mt-2">وكالة سفر معتمدة</div>
            </div>
            <div class="col-span-2 md:col-span-1 bg-white border border-slate-100 p-6 rounded-2xl backdrop-blur-sm hover:scale-105 hover:shadow-md transition-all">
                <div class="text-2xl md:text-3xl font-black text-indigo-600 font-sans">+٥٠٠</div>
                <div class="text-xs text-slate-500 font-bold mt-2">رحلة نشطة يومياً</div>
            </div>
        </div>

        <div class="space-y-12">
            <div class="space-y-2" data-aos="fade-up">
                <h2 class="text-2xl md:text-3xl font-black italic text-slate-900">قيمنا ومبادئنا</h2>
                <p class="text-xs text-slate-400 font-bold">الركائز الأساسية التي نبني عليها كل خطوة في رحلتنا</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-right">

                <div class="group bg-white hover:bg-slate-50 border border-slate-100 hover:border-indigo-200 p-6 rounded-[2rem] transition-all duration-300 flex items-start gap-4 flex-row-reverse hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200/50" data-aos="fade-left" data-aos-delay="100">
                    <div class="w-12 h-12 bg-indigo-50 group-hover:bg-indigo-600 text-indigo-600 group-hover:text-white rounded-2xl flex items-center justify-center text-xl transition-all duration-300 shrink-0 shadow-sm">
                        🛡️
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-black text-base text-slate-900 group-hover:text-indigo-600 transition-colors">الموثوقية</h4>
                        <p class="text-xs text-slate-500 font-semibold leading-relaxed">تجربة آمنة ومتكاملة يمكن الاعتماد عليها في كافة مراحل الحجز والدفع.</p>
                    </div>
                </div>

                <div class="group bg-white hover:bg-slate-50 border border-slate-100 hover:border-indigo-200 p-6 rounded-[2rem] transition-all duration-300 flex items-start gap-4 flex-row-reverse hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200/50" data-aos="fade-right" data-aos-delay="200">
                    <div class="w-12 h-12 bg-indigo-50 group-hover:bg-indigo-600 text-indigo-600 group-hover:text-white rounded-2xl flex items-center justify-center text-xl transition-all duration-300 shrink-0 shadow-sm">
                        🔍
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-black text-base text-slate-900 group-hover:text-indigo-600 transition-colors">الشفافية</h4>
                        <p class="text-xs text-slate-500 font-semibold leading-relaxed">وضوح كامل في تفاصيل البيانات والحجوزات والأسعار دون أي رسوم مخفية.</p>
                    </div>
                </div>

                <div class="group bg-white hover:bg-slate-50 border border-slate-100 hover:border-indigo-200 p-6 rounded-[2rem] transition-all duration-300 flex items-start gap-4 flex-row-reverse hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200/50" data-aos="fade-left" data-aos-delay="300">
                    <div class="w-12 h-12 bg-indigo-50 group-hover:bg-indigo-600 text-indigo-600 group-hover:text-white rounded-2xl flex items-center justify-center text-xl transition-all duration-300 shrink-0 shadow-sm">
                        ⚙️
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-black text-base text-slate-900 group-hover:text-indigo-600 transition-colors">التنظيم</h4>
                        <p class="text-xs text-slate-500 font-semibold leading-relaxed">إدارة مرتبة ومؤتمتة للرحلات والمقاعد تضمن راحة المسافر والوكالة معاً.</p>
                    </div>
                </div>

                <div class="group bg-white hover:bg-slate-50 border border-slate-100 hover:border-indigo-200 p-6 rounded-[2rem] transition-all duration-300 flex items-start gap-4 flex-row-reverse hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200/50" data-aos="fade-right" data-aos-delay="400">
                    <div class="w-12 h-12 bg-indigo-50 group-hover:bg-indigo-600 text-indigo-600 group-hover:text-white rounded-2xl flex items-center justify-center text-xl transition-all duration-300 shrink-0 shadow-sm">
                        🚀
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-black text-base text-slate-900 group-hover:text-indigo-600 transition-colors">التطوير المستمر</h4>
                        <p class="text-xs text-slate-500 font-semibold leading-relaxed">تحسين دائم وسريع للواجهات ومواكبة أحدث الحلول البرمجية الذكية لخدمتكم.</p>
                    </div>
                </div>

            </div>
        </div>

        <div class="space-y-6 max-w-2xl mx-auto text-right" data-aos="fade-up">
            <div class="text-center space-y-2 mb-8">
                <h3 class="text-xl md:text-2xl font-black text-slate-900">قد يدور في ذهنك؟</h3>
                <p class="text-xs text-slate-400 font-bold">إجابات سريعة للتعرف على أبعاد منصة عبور</p>
            </div>

            <div class="border border-slate-100 bg-white rounded-2xl overflow-hidden shadow-sm transition-all duration-300 faq-item">
                <button class="w-full p-5 flex justify-between items-center text-right font-bold text-sm md:text-base text-slate-700 hover:text-indigo-600 transition-colors focus:outline-none toggle-faq">
                    <span>كيف تضمن عبور حقوق المسافرين والوكالات؟</span>
                    <span class="text-xs bg-indigo-50 p-2 rounded-lg text-indigo-600 font-sans icon">+</span>
                </button>
                <div class="max-h-0 overflow-hidden transition-all duration-300 bg-slate-50/50 body-faq">
                    <p class="p-5 text-xs md:text-sm text-slate-500 leading-relaxed border-t border-slate-100">
                        تعمل المنصة كوسيط تقني منظم؛ حيث يتم مراجعة وتدقيق السجلات التجارية وهوية كافة الوكالات المسجلة قبل منحها صلاحيات نشر وتوفير الرحلات للمسافرين لضمان المصداقية الكاملة.
                    </p>
                </div>
            </div>

            <div class="border border-slate-100 bg-white rounded-2xl overflow-hidden shadow-sm transition-all duration-300 faq-item">
                <button class="w-full p-5 flex justify-between items-center text-right font-bold text-sm md:text-base text-slate-700 hover:text-indigo-600 transition-colors focus:outline-none toggle-faq">
                    <span>هل تفرض المنصة رسوماً إضافية على أسعار الرحلات؟</span>
                    <span class="text-xs bg-indigo-50 p-2 rounded-lg text-indigo-600 font-sans icon">+</span>
                </button>
                <div class="max-h-0 overflow-hidden transition-all duration-300 bg-slate-50/50 body-faq">
                    <p class="p-5 text-xs md:text-sm text-slate-500 leading-relaxed border-t border-slate-100">
                        لا، نحن نؤمن بقيمة الشفافية المطلقة. الأسعار المعروضة على الرحلات يتم تحديدها مباشرة من قبل وكالات السفر والشركات الناقلة دون أي زيادة أو عمولات خفية مضافة من طرفنا.
                    </p>
                </div>
            </div>
        </div>

        {{-- <div class="border-t border-slate-200/60 pt-8 text-xs text-slate-400 font-bold">
            منصة عبور للتقنية الذكية © {{ date('Y') }} — وجهتك الأسرع لرحلات أكثر تنظيماً.
        </div> --}}

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. تشغيل مكتبة الأنيكيشن Scroll
        AOS.init({
            once: true,
            mirror: false
        });

        // 2. تفاعل الأكورديون (FAQ)
        const faqs = document.querySelectorAll('.faq-item');
        faqs.forEach(faq => {
            const btn = faq.querySelector('.toggle-faq');
            const body = faq.querySelector('.body-faq');
            const icon = faq.querySelector('.icon');

            btn.addEventListener('click', () => {
                const isOpen = body.style.maxHeight && body.style.maxHeight !== '0px';
                
                // إغلاق أي سؤال آخر مفتوح
                faqs.forEach(item => {
                    item.querySelector('.body-faq').style.maxHeight = '0px';
                    item.querySelector('.icon').textContent = '+';
                    item.classList.remove('border-indigo-200', 'shadow-md');
                });

                if (!isOpen) {
                    body.style.maxHeight = body.scrollHeight + "px";
                    icon.textContent = '−';
                    faq.classList.add('border-indigo-200', 'shadow-md');
                } else {
                    body.style.maxHeight = '0px';
                    icon.textContent = '+';
                    faq.classList.remove('border-indigo-200', 'shadow-md');
                }
            });
        });
    });
</script>

<style>
    /* لف الأيقونة المشعة ببطء لمنح الصفحة إحساس بالعمق */
    .animate-spin-slow {
        animation: spin 6s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
</style>
@endsection