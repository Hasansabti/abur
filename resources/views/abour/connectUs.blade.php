@extends('layouts.client')

@section('content')
<div class="py-12 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- الهيدر والعنوان الرئيسي -->
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="text-xs font-bold bg-indigo-50 text-indigo-700 px-3.5 py-1.5 rounded-full inline-block mb-3">
                خدمة العملاء
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-4">
                يسعدنا التواصل معك دائماً
            </h1>
            <p class="text-slate-600 text-base sm:text-lg">
                هل لديك استفسار، اقتراح، أو تحتاج لمساعدة؟ فريقنا متواجد للرد عليك في أسرع وقت.
            </p>
        </div>

        <!-- تنبيه نجاح الإرسال -->
        @if(session('success'))
            <div class="max-w-5xl mx-auto mb-8 bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl flex items-center gap-3">
                <svg class="w-6 h-6 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- قسم معلومات التواصل -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 space-y-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">معلومات التواصل</h2>

                    <!-- البريد الإلكتروني -->
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 002-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">البريد الإلكتروني</h3>
                            <p class="text-slate-800 font-bold">support@aburtravel.com</p>
                        </div>
                    </div>

                    <!-- الهاتف / واتساب -->
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">الدعم الفني</h3>
                            <p class="text-slate-800 font-bold dir-ltr text-right">+966 53 269 9399</p>
                        </div>
                    </div>

                    <!-- الموقع -->
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">الموقع</h3>
                            <p class="text-slate-800 font-bold">المملكة العربية السعودية</p>
                        </div>
                    </div>

                    <!-- ساعات العمل -->
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">ساعات الخدمة</h3>
                            <p class="text-slate-800 font-bold">على مدار 24 ساعة / 7 أيام</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- نموذج تواصل معنا (Form) -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100">
                    <form action="#" method="POST" class="space-y-5">
                        @csrf

                        <!-- الاسم -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">الاسم الكامل</label>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="أدخل اسمك الكريم"
                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3.5 text-base text-slate-900 outline-none focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all">
                            @error('name')
                                <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- البريد والجوال -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">البريد الإلكتروني</label>
                                <input type="email" name="email" value="{{ old('email') }}" required placeholder="example@mail.com"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3.5 text-base text-slate-900 outline-none focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all">
                                @error('email')
                                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">رقم الجوال</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="05XXXXXXXX"
                                    inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3.5 text-base text-slate-900 outline-none focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all">
                                @error('phone')
                                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- نوع الاستفسار -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">نوع الاستفسار</label>
                            <select name="subject" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3.5 text-base text-slate-900 outline-none focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all">
                                <option value="" disabled selected>اختر موضوع الرسالة</option>
                                <option value="general">استفسار عام</option>
                                <option value="support">مساعدة فنية / بلاغ عن مشكلة</option>
                                <option value="suggestion">اقتراح لتطوير الخدمة</option>
                                <option value="partnership">استفسار عن الشراكات</option>
                            </select>
                            @error('subject')
                                <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- نص الرسالة -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">نص الرسالة</label>
                            <textarea name="message" rows="4" required placeholder="اكتب تفاصيل رسالتك هنا..."
                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3.5 text-base text-slate-900 outline-none focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all resize-none">{{ old('message') }}</textarea>
                            @error('message')
                                <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- زر الإرسال -->
                        <button type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-4 rounded-2xl transition shadow-lg shadow-indigo-600/10 flex items-center justify-center gap-2">
                            <span>إرسال الرسالة</span>
                            <svg class="w-5 h-5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection