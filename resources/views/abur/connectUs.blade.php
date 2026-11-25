@extends('layouts.client')

@section('content')
    <div class="py-12 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- الهيدر والعنوان الرئيسي -->
            <div class="text-center max-w-3xl mx-auto mb-12">
                <span
                    class="inline-flex items-center gap-2 text-xs font-bold bg-indigo-50 text-indigo-700 px-4 py-1.5 rounded-full mb-3 border border-indigo-100">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    خدمة العملاء والدعم الفني
                </span>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-4 tracking-tight">
                    يسعدنا التواصل معك دائماً
                </h1>
                <p class="text-slate-600 text-base sm:text-lg">
                    هل لديك استفسار أو تفضيل خاص؟ اكتب رسالتك مباشرة وسنتولى المتابعة معك عبر حسابك.
                </p>
            </div>

            <!-- تنبيه نجاح الإرسال -->
            @if (session('success'))
                <div
                    class="max-w-5xl mx-auto mb-8 bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl flex items-center gap-3 shadow-sm animate-fade-in">
                    <svg class="w-6 h-6 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-semibold">{{ session('success') }}</span>
                </div>
            @endif

            <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                <!-- قسم معلومات التواصل المباشر -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 space-y-5">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-xl font-bold text-slate-900">قنوات التواصل</h2>
                            <span
                                class="text-xs bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg font-medium">مباشر</span>
                        </div>

                        <!-- البريد الإلكتروني (تفاعلي) -->
                        <a href="mailto:support.abur.sa@gmail.com"
                            class="group flex items-center gap-4 p-3 rounded-2xl hover:bg-slate-50 transition-all duration-200 border border-transparent hover:border-slate-200/60">
                            <div
                                class="w-12 h-12 bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white rounded-2xl flex items-center justify-center flex-shrink-0 transition-colors duration-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 002-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">البريد
                                    الإلكتروني</h3>
                                <p class="text-slate-800 font-bold group-hover:text-indigo-600 transition-colors truncate">
                                    support@aburtravel.com</p>
                            </div>
                        </a>

                        <!-- الهاتف / واتساب (تفاعلي) -->
                        <a href="https://wa.me/الرقم" target="_blank"
                            class="group flex items-center gap-4 p-3 rounded-2xl hover:bg-slate-50 transition-all duration-200 border border-transparent hover:border-slate-200/60">
                            <div
                                class="w-12 h-12 bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white rounded-2xl flex items-center justify-center flex-shrink-0 transition-colors duration-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">الدعم الفني
                                        / واتساب</h3>
                                </div>
                                {{-- <p class="text-slate-800 font-bold group-hover:text-emerald-600 transition-colors dir-ltr text-right">+966 53 269 9399</p> --}}
                            </div>
                        </a>

                        <!-- الموقع -->
                        <div class="flex items-center gap-4 p-3">
                            <div
                                class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">الموقع الرئيسي
                                </h3>
                                <p class="text-slate-800 font-bold">المملكة العربية السعودية</p>
                            </div>
                        </div>

                        <!-- ساعات العمل -->
                        <div class="flex items-center gap-4 p-3 bg-slate-50/80 rounded-2xl border border-slate-100">
                            <div
                                class="w-10 h-10 bg-white text-indigo-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">ساعات الخدمة
                                </h3>
                                <p class="text-xs font-bold text-slate-800">خدمة متواصلة 24/7</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- نموذج تواصل معنا (Form) -->
                <div class="lg:col-span-7">
                    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100">

                        <!-- كارت تعريف المستخدم المسجل -->
                        <div
                            class="flex items-center justify-between p-4 bg-gradient-to-r from-indigo-50/80 to-slate-50 border border-indigo-100 rounded-2xl mb-6">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-11 h-11 rounded-2xl bg-indigo-600 text-white font-bold flex items-center justify-center text-base shadow-sm flex-shrink-0">
                                    {{ mb_substr(auth()->user()->name ?? 'م', 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-extrabold text-slate-900 truncate">
                                            {{ auth()->user()->name ?? 'حساب المستخدم' }}</p>
                                        <span
                                            class="px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-700 rounded-md">حساب
                                            مسجل</span>
                                    </div>
                                    <p class="text-xs text-slate-500 truncate mt-0.5">{{ auth()->user()->email ?? '' }}</p>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('client.contact.send') }}" method="POST" class="space-y-6">
                             @csrf

                            <!-- نوع الاستفسار -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">نوع الاستفسار</label>
                                <div class="relative">
                                    <select name="subject" required
                                        class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3.5 text-slate-900 outline-none focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all appearance-none cursor-pointer">
                                        <option value="" disabled selected>اختر موضوع الرسالة</option>
                                        <option value="general">استفسار عام</option>
                                        <option value="support">مساعدة فنية / بلاغ عن مشكلة</option>
                                        <option value="suggestion">اقتراح لتطوير الخدمة</option>
                                        <option value="partnership">استفسار عن الشراكات</option>
                                    </select>
                                    <div
                                        class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                                @error('subject')
                                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- نص الرسالة -->
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <label class="block text-sm font-bold text-slate-700">نص الرسالة</label>
                                    <span class="text-xs text-slate-400">اكتب تفاصيل طلبك بوضوح</span>
                                </div>
                                <textarea name="message" rows="5" required placeholder="اكتب رسالتك هنا..."
                                    class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-4 text-base text-slate-900 outline-none focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all resize-none shadow-inner">{{ old('message') }}</textarea>
                                @error('message')
                                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- زر الإرسال -->
                            <button type="submit"
                                class="group relative w-full bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white font-bold text-sm py-4 rounded-2xl transition-all duration-200 shadow-lg shadow-indigo-600/20 flex items-center justify-center gap-2">
                                <span>إرسال الرسالة</span>
                                <svg class="w-5 h-5 rotate-180 transform group-hover:-translate-x-1 transition-transform duration-200"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
