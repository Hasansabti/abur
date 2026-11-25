@extends('layouts.client')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-indigo-50/20 to-slate-100/50 py-12 px-4 sm:px-6 lg:px-8" dir="rtl" 
     x-data="{ activeTab: 'payments' }">

    <div class="max-w-6xl mx-auto">
        
        {{-- رأس الصفحة مع تأثيرات ظهور --}}
        <div class="text-center max-w-3xl mx-auto mb-12 transition-all duration-500 transform">
            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-indigo-100/80 text-indigo-700 border border-indigo-200/60 mb-4 shadow-sm animate-pulse">
                🛡️ الأمان وسياسات المنصة المتكاملة
            </span>
            <h1 class="text-3xl sm:text-5xl font-black text-slate-800 tracking-tight leading-tight">
                سياسة الخصوصية والمدفوعات
            </h1>
            <p class="text-slate-600 mt-4 text-base sm:text-lg leading-relaxed">
                نحن نلتزم بشفافية التعاملات المالية وحماية بياناتك الشخصية عبر بيئة رقمية تفاعلية وآمنة.
            </p>
        </div>

        {{-- المحتوى الرئيسي: التبويبات والمحتوى --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            {{-- قائمة التنقل الجانبية (Tabs Navigation) مع تفاعل حركي --}}
            <div class="lg:col-span-4 space-y-2.5 sticky top-6">
                
                {{-- قالب الزر المتفاعل المشترك --}}
                <template x-for="tab in [
                    { id: 'payments', label: 'سياسات المدفوعات', icon: '💳' },
                    { id: 'info', label: 'المعلومات التي نجمعها', icon: '📋' },
                    { id: 'usage', label: 'كيفية استخدام المعلومات', icon: '⚙️' },
                    { id: 'security', label: 'مشاركة البيانات والأمن', icon: '🔒' },
                    { id: 'cookies', label: 'ملفات تعريف الارتباط', icon: '🍪' },
                    { id: 'rights', label: 'حقوق المستخدم', icon: '👑' }
                ]">
                    <button @click="activeTab = tab.id" 
                        :class="activeTab === tab.id ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-200 scale-[1.02] translate-x-1' : 'bg-white/80 backdrop-blur-sm text-slate-600 hover:bg-white hover:shadow-md hover:scale-[1.01]'"
                        class="w-full text-right px-6 py-4 rounded-2xl font-bold transition-all duration-300 flex items-center justify-between group border border-slate-100/80">
                        <span class="flex items-center gap-3.5 transition-transform duration-300 group-hover:translate-x-1">
                            <span class="text-xl p-2 rounded-xl bg-slate-100/50 group-hover:bg-white/20 transition-colors" x-text="tab.icon"></span>
                            <span x-text="tab.label"></span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" 
                             :class="activeTab === tab.id ? 'rotate-180 text-white' : 'text-slate-400 group-hover:text-indigo-600'"
                             class="w-5 h-5 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                </template>

            </div>

            {{-- منطقة عرض التفاصيل مع حركات دخول وخروج انسيابية (Alpine Transitions) --}}
            <div class="lg:col-span-8">
                <div class="bg-white/90 backdrop-blur-md rounded-3xl border border-slate-100 p-6 sm:p-10 shadow-xl shadow-slate-200/50 min-h-[500px] relative overflow-hidden">
                    
                    {{-- 💳 سياسات المدفوعات --}}
                    <div x-show="activeTab === 'payments'" 
                         x-transition:enter="transition ease-out duration-400 transform"
                         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-200 transform"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                         class="space-y-8">
                        
                        <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
                            <div class="w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-3xl shadow-inner">💳</div>
                            <div>
                                <h2 class="text-2xl font-black text-slate-800">سياسات المدفوعات والتعاملات المالية</h2>
                                <p class="text-xs text-slate-400 mt-1">تحديثات هيكلية شاملة لإدارة الحجوزات والمدفوعات</p>
                            </div>
                        </div>

                        {{-- 1. الحجز عبر المنصة --}}
                        <div class="space-y-3 p-5 rounded-2xl bg-slate-50/70 border border-slate-100 transition-all hover:shadow-md">
                            <h3 class="text-base font-bold text-indigo-700 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs">1</span> 
                                الحجز عبر المنصة (العميل)
                            </h3>
                            <ul class="space-y-2 text-slate-600 text-sm list-disc list-inside pr-2 leading-relaxed">
                                <li>يقوم العميل بإجراء الحجز من حسابه المباشر في المنصة.</li>
                                <li>يشترط سداد كامل قيمة الحجز لإتمام وتأكيد العملية بنجاح.</li>
                                <li><strong>وسائل الدفع الحالية:</strong> التحويل البنكي.</li>
                                <li><strong>وسائل الدفع المستقبلية:</strong> مدى، بطاقات الائتمان، المحافظ الرقمية، وخدمات التقسيط (تابي وتمارا).</li>
                                <li class="text-amber-700 font-semibold bg-amber-50/80 p-2 rounded-xl mt-1 inline-block">لا يتم تأكيد الحجز إلا بعد التحقق التام من اكتمال السداد.</li>
                            </ul>
                        </div>

                        {{-- 2. الحجز بواسطة الوكالة --}}
                        <div class="space-y-3 p-5 rounded-2xl bg-slate-50/70 border border-slate-100 transition-all hover:shadow-md">
                            <h3 class="text-base font-bold text-indigo-700 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs">2</span> 
                                الحجز بواسطة الوكالة
                            </h3>
                            <p class="text-slate-600 text-sm">يمنح النظام موظفي الوكالة مرونة كاملة في إدارة المدفوعات عبر الخيارات الآتية:</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                                <div class="p-3 bg-white rounded-xl border border-slate-200/60 text-slate-700 text-xs font-bold flex items-center gap-2 shadow-sm">✨ إنشاء الحجز بدون دفعة</div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200/60 text-slate-700 text-xs font-bold flex items-center gap-2 shadow-sm">💵 تسجيل دفعة مقدمة</div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200/60 text-slate-700 text-xs font-bold flex items-center gap-2 shadow-sm">📊 تسجيل دفعات متعددة (تقسيط)</div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200/60 text-slate-700 text-xs font-bold flex items-center gap-2 shadow-sm">✅ تسجيل سداد كامل</div>
                            </div>
                            <p class="text-xs text-slate-500 pt-1"><strong>خيارات الدفع المستقلة:</strong> نقدًا، تحويل بنكي، مدى، بطاقات الائتمان، والمحافظ الرقمية.</p>
                        </div>

                        {{-- 3. إدارة المدفوعات --}}
                        <div class="space-y-3 p-5 rounded-2xl bg-slate-50/70 border border-slate-100 transition-all hover:shadow-md">
                            <h3 class="text-base font-bold text-indigo-700 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs">3</span> 
                                إدارة المدفوعات والسجل المالي المستقل
                            </h3>
                            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-600 text-xs">
                                <li class="p-2 bg-white rounded-lg border border-slate-200/50">✓ تسجيل عدد غير محدود من الدفعات.</li>
                                <li class="p-2 bg-white rounded-lg border border-slate-200/50">✓ تحديد المبلغ، الطريقة، والتاريخ بدقة.</li>
                                <li class="p-2 bg-white rounded-lg border border-slate-200/50">✓ تسجيل رقم المرجع للتحويلات (اختياري).</li>
                                <li class="p-2 bg-white rounded-lg border border-slate-200/50">✓ إضافة ملاحظات تفصيلية لكل عملية.</li>
                            </ul>
                        </div>

                        {{-- 4. ملخص الحالة المالية --}}
                        <div class="space-y-3 p-5 rounded-2xl bg-slate-50/70 border border-slate-100 transition-all hover:shadow-md">
                            <h3 class="text-base font-bold text-indigo-700 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs">4</span> 
                                ملخص الحالة المالية الفورية
                            </h3>
                            <div class="bg-white p-4 rounded-xl border border-slate-200/60 space-y-2 text-xs text-slate-700 shadow-sm">
                                <div class="flex justify-between border-b pb-1.5 font-bold"><span>إجمالي قيمة الحجز والرصيد المستحق</span> <span class="text-indigo-600">تحديث لحظي</span></div>
                                <div class="text-slate-500">تفكيك المبالغ (نقدًا، تحويل بنكي، مدى، وسائل إلكترونية).</div>
                                <div><strong>حالات السداد المتاحة:</strong> 
                                    <span class="inline-block px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-bold mr-1 text-[10px]">غير مدفوع</span>
                                    <span class="inline-block px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-bold mr-1 text-[10px]">مدفوع جزئياً</span>
                                    <span class="inline-block px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-bold mr-1 text-[10px]">مدفوع بالكامل</span>
                                </div>
                            </div>
                        </div>

                        {{-- 5 & 6. تعديل الحجز وسجل العمليات --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-100 space-y-2">
                                <h4 class="font-bold text-slate-800 text-sm">5. تعديل الحجز</h4>
                                <p class="text-xs text-slate-600 leading-relaxed">إعادة احتساب تلقائي، تحديث الرصيد، الحفاظ على الدفعات السابقة، وتسجيل الفروقات المالية ضمن السجل.</p>
                            </div>
                            <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-100 space-y-2">
                                <h4 class="font-bold text-slate-800 text-sm">6. سجل العمليات</h4>
                                <p class="text-xs text-slate-600 leading-relaxed">سجل غير قابل للحذف للتدقيق والمراجعة (الدفعات، الاستردادات، التسويات والفروقات) عبر عمليات تسوية رسمية فقط.</p>
                            </div>
                        </div>

                        {{-- الملاحظة التقنية والهندسية بتصميم مميز --}}
                        <div class="p-5 rounded-2xl bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/80 text-amber-900 space-y-2 shadow-sm">
                            <span class="font-bold text-sm block flex items-center gap-2 text-amber-900">
                                💡 ملاحظة تقنية وهندسية معممارية:
                            </span>
                            <p class="text-xs leading-relaxed text-amber-800">
                                يُفضل عدم ربط (تابي وتمارا) داخل منطق التقسيط الخاص بالوكالة؛ لأن تابي وتمارا تُعدان وسيلة دفع إلكترونية مباشرة للعميل عبر المنصة، بينما التقسيط الممنوح من الوكالة هو نظام مديونية داخلي مختلف. الفصل بينهما في التصميم يمنع التعقيدات المحاسبية والبرمجية مستقبلاً.
                            </p>
                        </div>

                    </div>

                    {{-- 📋 المعلومات التي نقوم بجمعها --}}
                    <div x-show="activeTab === 'info'" x-cloak 
                         x-transition:enter="transition ease-out duration-400 transform"
                         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         class="space-y-6">
                        <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
                            <div class="w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-3xl">📋</div>
                            <h2 class="text-2xl font-black text-slate-800">المعلومات التي نقوم بجمعها</h2>
                        </div>
                        <p class="text-slate-600 leading-relaxed text-base">
                            نقوم بجمع بعض المعلومات الشخصية لضمان عمل الخدمات بكفاءة، وتشمل:
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-md transition-all">
                                <span class="font-bold text-slate-800 block mb-1">👤 البيانات الشخصية</span>
                                <span class="text-sm text-slate-500">الاسم، البريد الإلكتروني، ورقم الهاتف.</span>
                            </div>
                            <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-md transition-all">
                                <span class="font-bold text-slate-800 block mb-1">💳 الحجوزات والدفع</span>
                                <span class="text-sm text-slate-500">تفاصيل المعاملات وبيانات الدفع المعالجة بأمان عبر أطراف خارجية معتمدة.</span>
                            </div>
                            <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100 sm:col-span-2 hover:shadow-md transition-all">
                                <span class="font-bold text-slate-800 block mb-1">💻 البيانات التقنية</span>
                                <span class="text-sm text-slate-500">عنوان IP، نوع الجهاز، وملفات تعريف الارتباط (Cookies).</span>
                            </div>
                        </div>
                    </div>

                    {{-- ⚙️ كيفية استخدام المعلومات --}}
                    <div x-show="activeTab === 'usage'" x-cloak 
                         x-transition:enter="transition ease-out duration-400 transform"
                         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         class="space-y-6">
                        <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
                            <div class="w-14 h-14 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-3xl">⚙️</div>
                            <h2 class="text-2xl font-black text-slate-800">كيفية استخدام المعلومات</h2>
                        </div>
                        <p class="text-slate-600 leading-relaxed text-base">
                            نستخدم معلوماتك المدخلة من أجل تحسين وتسهيل تجربتك داخل المنصة للأغراض التالية:
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100 text-slate-600 text-sm">
                                <span class="text-emerald-500 font-bold text-lg">✓</span>
                                <span>تقديم الخدمات ومعالجة الحجوزات بدقة فائقة.</span>
                            </li>
                            <li class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100 text-slate-600 text-sm">
                                <span class="text-emerald-500 font-bold text-lg">✓</span>
                                <span>التواصل مع المستخدمين بشأن التأكيدات والتحديثات الدورية للطلبات.</span>
                            </li>
                            <li class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100 text-slate-600 text-sm">
                                <span class="text-emerald-500 font-bold text-lg">✓</span>
                                <span>تحسين أداء المنصة وتطوير تجربة المستخدم بشكل مستمر.</span>
                            </li>
                            <li class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100 text-slate-600 text-sm">
                                <span class="text-emerald-500 font-bold text-lg">✓</span>
                                <span>الامتثال الكامل للمتطلبات واللوائح القانونية المعتمدة.</span>
                            </li>
                        </ul>
                    </div>

                    {{-- 🔒 مشاركة البيانات وأمن المعلومات --}}
                    <div x-show="activeTab === 'security'" x-cloak 
                         x-transition:enter="transition ease-out duration-400 transform"
                         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         class="space-y-6">
                        <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
                            <div class="w-14 h-14 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center text-3xl">🔒</div>
                            <h2 class="text-2xl font-black text-slate-800">مشاركة البيانات وأمن المعلومات</h2>
                        </div>
                        <div class="p-5 rounded-2xl bg-blue-50/50 border border-blue-100 text-blue-900 font-medium text-sm">
                            🛡️ نلتزم بتطبيق أقصى المعايير الأمنية لحماية معلوماتك من الوصول غير المصرح به أو التعديل والإفصاح.
                        </div>
                        <p class="text-slate-600 leading-relaxed text-sm">
                            نقوم بمشاركة البيانات فقط مع <strong>مزودي خدمات موثوقين</strong> عند الحاجة الضرورية لتقديم الخدمة المطلوبة. 
                        </p>
                        <div class="p-4 rounded-2xl bg-red-50 border border-red-100 text-red-700 font-bold text-sm shadow-sm">
                            🚫 لا يتم بيع أو مشاركة البيانات الشخصية إطلاقاً مع أي أطراف غير مصرح لها.
                        </div>
                    </div>

                    {{-- 🍪 ملفات تعريف الارتباط والتتبع --}}
                    <div x-show="activeTab === 'cookies'" x-cloak 
                         x-transition:enter="transition ease-out duration-400 transform"
                         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         class="space-y-6">
                        <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
                            <div class="w-14 h-14 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center text-3xl">🍪</div>
                            <h2 class="text-2xl font-black text-slate-800">ملفات تعريف الارتباط والتتبع</h2>
                        </div>
                        <p class="text-slate-600 leading-relaxed text-sm">
                            نستخدم ملفات تعريف الارتباط (Cookies) وأدوات التحليل المتقدمة لفهم سلوك استخدام المنصة وتخصيص تجربتك بشكل فريد.
                        </p>
                        <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100">
                            <h4 class="font-bold text-slate-800 mb-2 text-sm">لماذا نستخدم الكوكيز؟</h4>
                            <p class="text-slate-500 text-xs leading-relaxed">
                                تساعدنا هذه التقنيات على حفظ تفضيلاتك الشخصية أثناء التصفح وتحسين الأداء العام للموقع وتقديم المحتوى الملائم لاحتياجاتك.
                            </p>
                        </div>
                    </div>

                    {{-- 👑 حقوق المستخدم --}}
                    <div x-show="activeTab === 'rights'" x-cloak 
                         x-transition:enter="transition ease-out duration-400 transform"
                         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         class="space-y-6">
                        <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
                            <div class="w-14 h-14 rounded-2xl bg-purple-50 border border-purple-100 flex items-center justify-center text-3xl">👑</div>
                            <h2 class="text-2xl font-black text-slate-800">حقوق المستخدم</h2>
                        </div>
                        <p class="text-slate-600 leading-relaxed text-sm">
                            بياناتك هي ملك لك تماماً، ولذلك يُحفظ لك الحق الكامل في التحكم بها عبر خيارات واضحة:
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="p-5 rounded-2xl bg-purple-50/50 border border-purple-100 text-center hover:scale-105 transition-transform shadow-sm">
                                <span class="text-3xl block mb-2">👁️</span>
                                <span class="font-bold text-purple-900 text-sm">طلب الوصول</span>
                            </div>
                            <div class="p-5 rounded-2xl bg-purple-50/50 border border-purple-100 text-center hover:scale-105 transition-transform shadow-sm">
                                <span class="text-3xl block mb-2">✏️</span>
                                <span class="font-bold text-purple-900 text-sm">تعديل البيانات</span>
                            </div>
                            <div class="p-5 rounded-2xl bg-purple-50/50 border border-purple-100 text-center hover:scale-105 transition-transform shadow-sm">
                                <span class="text-3xl block mb-2">🗑️</span>
                                <span class="font-bold text-purple-900 text-sm">حذف البيانات</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        {{-- قسم للتواصل التفاعلي في الأسفل --}}
        <div class="mt-12 bg-gradient-to-r from-indigo-600 via-indigo-700 to-indigo-900 rounded-3xl p-8 text-white shadow-2xl flex flex-col sm:flex-row justify-between items-center gap-6 relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
            <div>
                <h3 class="text-2xl font-black mb-1">لديك استفسار حول الخصوصية أو المدفوعات؟ 📩</h3>
                <p class="text-indigo-100 text-sm">فريق الدعم المالي والتطوير جاهز لخدمتك والإجابة على استفساراتك.</p>
            </div>
            <a href="mailto:support@example.com" class="px-8 py-4 bg-white text-indigo-600 font-bold rounded-2xl hover:bg-indigo-50 hover:scale-105 transition-all shadow-lg whitespace-nowrap">
                تواصل معنا عبر البريد
            </a>
        </div>

    </div>
</div>
@endsection