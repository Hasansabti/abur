@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ activeTab: 'pending' }">

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-2 bg-blue-600"></div>
            <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">إجمالي المبيعات</p>
            <p class="text-3xl font-black text-slate-800 mt-1">45,200 <small class="text-xs text-slate-400 font-bold">ر.س</small></p>
            <div class="mt-4 flex items-center gap-1 text-[10px] font-bold text-green-500 bg-green-50 w-fit px-2 py-1 rounded-lg">
                <span>↑ 12%</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 group">
            <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">الوكالات النشطة</p>
            <div class="flex items-end justify-between">
                <p class="text-3xl font-black text-slate-800 mt-1">24</p>
                <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">3 طلبات انضمام</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 group">
            <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">الرحلات النشطة</p>
            <p class="text-3xl font-black text-slate-800 mt-1">156</p>
            <div class="mt-4 flex items-center gap-1 text-[10px] font-bold text-orange-500">
                <span class="w-2 h-2 bg-orange-500 rounded-full animate-ping"></span> 8 بانتظار الاعتماد
            </div>
        </div>

        <div class="bg-slate-900 p-6 rounded-[2rem] shadow-xl text-white">
            <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest">المستخدمين أونلاين</p>
            <p class="text-3xl font-black mt-1">1,840</p>
            <div class="mt-4 flex -space-x-2 space-x-reverse">
                <div class="w-6 h-6 rounded-full border-2 border-slate-900 bg-blue-500"></div>
                <div class="w-6 h-6 rounded-full border-2 border-slate-900 bg-purple-500"></div>
                <div class="w-6 h-6 rounded-full border-2 border-slate-900 bg-slate-700 flex items-center justify-center text-[8px] font-bold">+40</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        {{-- الجزء الأيسر: إدارة الرحلات والوكالات --}}
        <div class="lg:col-span-8 space-y-8">

            {{-- تبديلات التحكم --}}
            <div class="flex gap-4 border-b border-slate-100 pb-2">
                <button @click="activeTab = 'pending'" :class="activeTab === 'pending' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-slate-400'" class="pb-2 px-4 font-black text-sm transition-all">رحلات بانتظار الاعتماد</button>
                <button @click="activeTab = 'agencies'" :class="activeTab === 'agencies' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-slate-400'" class="pb-2 px-4 font-black text-sm transition-all">طلبات الوكالات</button>
            </div>

            <div x-show="activeTab === 'pending'" class="space-y-4 animate-fade-in">
                {{-- بطاقة رحلة بانتظار الاعتماد --}}
                <div class="bg-white rounded-[2rem] p-6 border border-slate-100 hover:shadow-lg transition-all flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-3xl">🚍</div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="font-black text-slate-800">وكالة الفارس - رحلة مكة</h4>
                                <span class="text-[9px] bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full font-bold">عاجل</span>
                            </div>
                            <p class="text-xs text-slate-400 font-bold mt-1 uppercase tracking-tighter">الرياض ⬅️ مكة المكرمة</p>
                            <div class="flex items-center gap-3 mt-2">
                                <span class="text-[10px] font-black text-blue-600 bg-blue-50 px-2 py-1 rounded-lg">150 ر.س / مقعد</span>
                                <span class="text-[10px] font-black text-slate-500">📅 25 رمضان</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2 w-full md:w-auto">
                        <button class="flex-1 md:flex-none px-6 py-3 bg-slate-50 text-slate-400 rounded-xl text-xs font-black hover:bg-red-50 hover:text-red-500 transition-all">رفض</button>
                        <button class="flex-1 md:flex-none px-8 py-3 bg-blue-600 text-white rounded-xl text-xs font-black shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all">اعتماد الرحلة</button>
                    </div>
                </div>
            </div>

            {{-- قسم الدعم الفني السريع --}}
            <div class="bg-white rounded-[3rem] p-8 border border-slate-100 shadow-sm">
                <h2 class="text-xl font-black text-slate-800 mb-6 flex items-center gap-2">
                    <span class="p-2 bg-purple-100 text-purple-600 rounded-xl text-sm">🎫</span> أحدث تذاكر الدعم
                </h2>
                <div class="divide-y divide-slate-50">
                    <div class="py-4 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <div>
                                <p class="text-sm font-black text-slate-700">مشكلة في استعادة كلمة المرور</p>
                                <p class="text-[10px] text-slate-400 font-bold">بواسطة: محمد علي - منذ 10 دقائق</p>
                            </div>
                        </div>
                        <button class="text-[10px] font-black text-blue-600 hover:underline">رد الآن</button>
                    </div>
                    <div class="py-4 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 bg-slate-200 rounded-full"></div>
                            <div>
                                <p class="text-sm font-black text-slate-700 italic opacity-50">تأكيد عملية دفع معلقة</p>
                                <p class="text-[10px] text-slate-400 font-bold">بواسطة: وكالة النور - منذ ساعتين</p>
                            </div>
                        </div>
                        <span class="text-[9px] font-black text-slate-300 uppercase">تم الرد</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- الجزء الأيمن: الإعدادات والأدوات --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- التحكم في العمولات --}}
            <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white shadow-2xl relative overflow-hidden">
                <div class="absolute -right-4 -top-4 w-20 h-20 bg-blue-600/20 rounded-full blur-2xl"></div>
                <h3 class="text-lg font-black mb-6 flex items-center gap-2">
                    <span class="text-blue-500">💰</span> النظام المالي
                </h3>
                <div class="space-y-6">
                    <div>
                        <label class="text-[10px] text-slate-400 font-black uppercase mb-2 block">عمولة المنصة على التذكرة (%)</label>
                        <div class="flex items-center gap-4">
                            <input type="range" min="1" max="20" value="5" class="flex-grow accent-blue-600">
                            <span class="text-xl font-black text-blue-500">5%</span>
                        </div>
                    </div>
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-bold text-slate-300">رسوم تسجيل الوكالات</span>
                            <div class="w-10 h-5 bg-blue-600 rounded-full relative">
                                <div class="absolute right-1 top-1 w-3 h-3 bg-white rounded-full"></div>
                            </div>
                        </div>
                        <p class="text-[9px] text-slate-500 font-bold italic">يتم تحصيل 500 ريال عند تفعيل حساب أي وكالة جديدة.</p>
                    </div>
                    <button class="w-full py-4 bg-blue-600 text-white rounded-2xl font-black text-xs hover:bg-blue-700 transition-all shadow-xl shadow-blue-900/50">
                        حفظ الإعدادات المالية
                    </button>
                </div>
            </div>

            {{-- اقتراحات الذكاء الاصطناعي/النظام --}}
            <div class="bg-blue-50 p-6 rounded-[2.5rem] border border-blue-100 relative group overflow-hidden">
                <div class="absolute -left-2 -bottom-2 text-6xl opacity-5 group-hover:rotate-12 transition-transform">💡</div>
                <h3 class="text-sm font-black text-blue-800 mb-3 flex items-center gap-2">نصيحة النظام</h3>
                <p class="text-xs text-blue-600 leading-relaxed font-bold">
                    تم رصد ضغط كبير على مسار <span class="underline">الرياض - مكة</span>. ننصح الوكالات بزيادة عدد الرحلات في العشر الأواخر.
                </p>
                <button class="mt-4 w-full py-2 bg-white text-blue-600 border border-blue-200 rounded-xl text-[10px] font-black hover:bg-blue-600 hover:text-white transition-all">
                    إرسال تنبيه للوكالات 📣
                </button>
            </div>

            {{-- سجل العمليات السريع --}}
            <div class="p-6">
                <h3 class="text-[10px] font-black text-slate-400 uppercase mb-4 tracking-widest">آخر النشاطات</h3>
                <div class="space-y-4">
                    <div class="flex gap-3">
                        <div class="w-1 h-8 bg-slate-200 rounded-full"></div>
                        <div>
                            <p class="text-[11px] font-black text-slate-700">قام الأدمن "سعد" بتغيير نسبة العمولات</p>
                            <p class="text-[9px] text-slate-400 font-bold italic">منذ 3 ساعات</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="w-1 h-8 bg-green-400 rounded-full"></div>
                        <div>
                            <p class="text-[11px] font-black text-slate-700">اعتماد رحلة وكالة النور #5502</p>
                            <p class="text-[9px] text-slate-400 font-bold italic">منذ 5 ساعات</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .animate-fade-in { animation: fadeIn 0.5s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endsection
