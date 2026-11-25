@extends('layouts.admin')

@section('content')
    <div class="max-w-5xl mx-auto p-6" dir="rtl">

        {{-- رأس الصفحة --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">

            <div>
                <h2 class="text-3xl font-black text-slate-800">
                    إدارة المستخدم: {{ $user->name }}
                </h2>

                <p class="text-slate-500 mt-2">
                    تعديل بيانات المستخدم وإدارة صلاحيات الوصول
                </p>
            </div>

            <div class="inline-flex items-center px-5 py-3 rounded-2xl bg-indigo-50 text-indigo-700 font-bold">
                #{{ $user->id }}
            </div>

        </div>


        {{-- الأخطاء --}}
        @if ($errors->any())
            <div class="mb-8 rounded-3xl border border-red-100 bg-red-50 p-5">

                <ul class="space-y-2">
                    @foreach ($errors->all() as $error)
                        <li class="text-red-600 font-semibold">
                            ⚠️ {{ $error }}
                        </li>
                    @endforeach
                </ul>

            </div>
        @endif


        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" enctype="multipart/form-data"
            class="space-y-8">

            @csrf
            @method('PUT')


            {{-- البيانات الشخصية --}}
            <div class="bg-white rounded-3xl border border-slate-100 p-8 shadow-sm">

                <div class="mb-8">
                    <h3 class="text-xl font-black text-slate-800">
                        👤 البيانات الشخصية
                    </h3>

                    <p class="text-sm text-slate-500 mt-1">
                        تحديث المعلومات الأساسية للمستخدم
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-2 text-sm font-bold text-slate-600">
                            الاسم الكامل
                        </label>

                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500 outline-none">
                    </div>


                    <div>
                        <label class="block mb-2 text-sm font-bold text-slate-600">
                            رقم الهوية
                        </label>

                        <input type="text" name="national_id" value="{{ old('national_id', $user->national_id) }}"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    </div>


                    <div>
                        <label class="block mb-2 text-sm font-bold text-slate-600">
                            تاريخ الميلاد
                        </label>

                        <input type="date" name="birth_date" value="{{ old('birth_date', $user->birth_date) }}"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    </div>


                    <div>
                        <label class="block mb-2 text-sm font-bold text-slate-600">
                            الجنس
                        </label>

                        <select name="gender" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>
                                ذكر
                            </option>

                            <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>
                                أنثى
                            </option>
                        </select>
                    </div>

                </div>

            </div>


            {{-- التواصل وحالة الحساب --}}
            <div class="bg-white rounded-3xl border border-slate-100 p-8 shadow-sm">

                <div class="mb-8">
                    <h3 class="text-xl font-black text-slate-800">
                        🌐 بيانات التواصل والحالة
                    </h3>

                    <p class="text-sm text-slate-500 mt-1">
                        تحديث معلومات الاتصال وحالة الحساب
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-2 text-sm font-bold text-slate-600">
                            البريد الإلكتروني
                        </label>

                        <input type="email" name="email" required value="{{ old('email', $user->email) }}"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    </div>


                    <div>
                        <label class="block mb-2 text-sm font-bold text-slate-600">
                            رقم الجوال
                        </label>

                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    </div>


                    <div>
                        <label class="block mb-2 text-sm font-bold text-slate-600">
                            المدينة
                        </label>

                        <input type="text" name="city" value="{{ old('city', $user->city) }}"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    </div>


                    <div>
                        <label class="block mb-2 text-sm font-bold text-slate-600">
                            حالة الحساب
                        </label>

                        <select name="is_verified" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <option value="0" {{ old('is_verified', $user->is_verified) == 0 ? 'selected' : '' }}>
                                غير موثق
                            </option>

                            <option value="1" {{ old('is_verified', $user->is_verified) == 1 ? 'selected' : '' }}>
                                موثق
                            </option>

                            <option value="2" {{ old('is_verified', $user->is_verified) == 2 ? 'selected' : '' }}>
                                رفض التوثيق
                            </option>
                        </select>
                    </div>

                </div>

            </div>

            {{-- 👥 قسم المرافقين --}}
            <div class="bg-white rounded-3xl border border-slate-100 p-8 shadow-sm">
                
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-black text-slate-800">
                            👥 بيانات المرافقين التابعين
                        </h3>
                        <p class="text-sm text-slate-500 mt-1">
                            عرض وتعديل بيانات مرافقي العميل
                        </p>
                    </div>
                    <div class="bg-indigo-50 text-indigo-700 font-bold px-4 py-2 rounded-xl text-sm">
                        العدد: {{ $user->companions ? $user->companions->count() : 0 }}
                    </div>
                </div>

                @if($user->companions && $user->companions->count() > 0)
                    <div class="space-y-4">
                        @foreach($user->companions as $index => $companion)
                            {{-- استخدام Alpine.js لجعل الكرت قابل للطي، ونفتح الأول فقط افتراضياً --}}
                            <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/50 transition-all hover:border-indigo-300" 
                                 x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }">
                                
                                {{-- رأس المرافق (يُضغط عليه للفتح/الإغلاق) --}}
                                <div @click="open = !open" class="flex flex-wrap items-center gap-3 cursor-pointer select-none">
                                    
                                    <div class="w-12 h-12 rounded-full bg-white border border-slate-200 flex items-center justify-center text-2xl shrink-0 shadow-sm">
                                        {{ $companion->gender == 'female' ? '🧕' : '👨' }}
                                    </div>
                                    
                                    <div class="flex-1 min-w-[150px]">
                                        <h4 class="font-black text-slate-700 text-lg">
                                            {{ $companion->name }}
                                        </h4>
                                        <p class="text-xs font-semibold text-slate-400 mt-0.5">
                                            الهوية: <span class="text-slate-500">{{ $companion->national_id ?? 'غير متوفر' }}</span>
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-2 mr-auto">
                                        {{-- 🌟 إضافة كلمة "مرافق موثق" إذا كان لديه مستند --}}
                                        @if($companion->id_document_path)
                                            <span class="flex items-center gap-1 text-xs font-bold bg-emerald-100 text-emerald-700 px-3 py-1.5 rounded-full border border-emerald-200">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                                مرافق موثق
                                            </span>
                                        @endif

                                        <span class="text-xs font-bold bg-slate-200 text-slate-600 px-3 py-1.5 rounded-full">
                                            {{ $companion->relationship ?? 'غير محدد' }}
                                        </span>

                                        {{-- أيقونة السهم --}}
                                        <div class="w-8 h-8 bg-white border border-slate-200 rounded-full flex items-center justify-center text-slate-400 transition-transform duration-300"
                                             :class="{'rotate-180 bg-indigo-50 text-indigo-600 border-indigo-200': open}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                {{-- تفاصيل المرافق (تظهر وتختفي) --}}
                                <div x-show="open" x-transition class="pt-5 mt-4 border-t border-slate-200" style="display: none;">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                        
                                        {{-- اسم المرافق --}}
                                        <div>
                                            <label class="block mb-2 text-xs font-bold text-slate-600">الاسم الكامل</label>
                                            <input type="text" name="companions[{{ $companion->id }}][name]" value="{{ old('companions.'.$companion->id.'.name', $companion->name) }}" required
                                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 focus:ring-2 focus:ring-indigo-200 outline-none text-sm shadow-sm">
                                        </div>

                                        {{-- رقم الهوية --}}
                                        <div>
                                            <label class="block mb-2 text-xs font-bold text-slate-600">رقم الهوية</label>
                                            <input type="text" name="companions[{{ $companion->id }}][national_id]" value="{{ old('companions.'.$companion->id.'.national_id', $companion->national_id) }}"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 focus:ring-2 focus:ring-indigo-200 outline-none text-sm shadow-sm">
                                        </div>

                                        {{-- صلة القرابة --}}
                                        <div>
                                            <label class="block mb-2 text-xs font-bold text-slate-600">صلة القرابة</label>
                                            <input type="text" name="companions[{{ $companion->id }}][relationship]" value="{{ old('companions.'.$companion->id.'.relationship', $companion->relationship) }}"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 focus:ring-2 focus:ring-indigo-200 outline-none text-sm shadow-sm">
                                        </div>

                                        {{-- تاريخ الميلاد --}}
                                        <div>
                                            <label class="block mb-2 text-xs font-bold text-slate-600">تاريخ الميلاد</label>
                                            <input type="date" name="companions[{{ $companion->id }}][birth_date]" value="{{ old('companions.'.$companion->id.'.birth_date', $companion->birth_date ? $companion->birth_date->format('Y-m-d') : '') }}"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 focus:ring-2 focus:ring-indigo-200 outline-none text-sm shadow-sm">
                                        </div>

                                        {{-- الجنس --}}
                                        <div>
                                            <label class="block mb-2 text-xs font-bold text-slate-600">الجنس</label>
                                            <select name="companions[{{ $companion->id }}][gender]" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 focus:ring-2 focus:ring-indigo-200 outline-none text-sm shadow-sm">
                                                <option value="male" {{ old('companions.'.$companion->id.'.gender', $companion->gender) == 'male' ? 'selected' : '' }}>ذكر</option>
                                                <option value="female" {{ old('companions.'.$companion->id.'.gender', $companion->gender) == 'female' ? 'selected' : '' }}>أنثى</option>
                                            </select>
                                        </div>

                                        {{-- مستند الهوية (للعرض فقط) --}}
                                        <div>
                                            <label class="block mb-2 text-xs font-bold text-slate-600">مرفق الهوية</label>
                                            @if($companion->id_document_path)
                                                <a href="{{ asset('storage/' . $companion->id_document_path) }}" target="_blank" class="flex items-center justify-center gap-2 w-full rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 font-bold px-4 py-2.5 hover:bg-emerald-100 transition text-sm shadow-sm">
                                                    📄 عرض المستند
                                                </a>
                                            @else
                                                <div class="flex items-center justify-center w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 text-slate-400 font-bold px-4 py-2.5 text-sm">
                                                    لا يوجد مرفق
                                                </div>
                                            @endif
                                        </div>

                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-10 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <span class="text-4xl mb-3 opacity-50">🚶‍♂️</span>
                        <p class="text-slate-500 font-bold">لا يوجد مرافقين مسجلين لهذا العميل حالياً.</p>
                    </div>
                @endif
            </div>


            {{-- 🔑 قسم كلمة المرور الجديد --}}
            <div class="bg-white rounded-3xl border border-slate-100 p-8 shadow-sm" x-data="{ showPass: false, showConfirm: false }">

                <div class="mb-8">
                    <h3 class="text-xl font-black text-slate-800">
                        🔑 كلمة المرور
                    </h3>

                    <p class="text-sm text-amber-600 mt-1 font-semibold">
                        💡 اترك الحقول فارغة في حال عدم رغبتك بتغيير كلمة المرور الحالية للمستخدم
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- كلمة المرور الجديدة --}}
                    <div>
                        <label class="block mb-2 text-sm font-bold text-slate-600">
                            كلمة المرور الجديدة
                        </label>

                        <div class="relative">
                            <input :type="showPass ? 'text' : 'password'" name="password" autocomplete="new-password"
                                placeholder="••••••••"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 pl-12 pr-4 py-3 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500 outline-none">

                            <button type="button" @click="showPass = !showPass"
                                class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                {{-- أيقونة إظهار --}}
                                <svg x-show="!showPass" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                {{-- أيقونة إخفاء --}}
                                <svg x-show="showPass" x-cloak xmlns="http://www.w3.org/2000/svg" class="w-5 h-5"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a8.962 8.962 0 012.122-.063c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21f-9 9 0 00-9-9m9 9L3 3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- تأكيد كلمة المرور الجديدة --}}
                    <div>
                        <label class="block mb-2 text-sm font-bold text-slate-600">
                            تأكيد كلمة المرور الجديدة
                        </label>

                        <div class="relative">
                            <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation"
                                autocomplete="new-password" placeholder="••••••••"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 pl-12 pr-4 py-3 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500 outline-none">

                            <button type="button" @click="showConfirm = !showConfirm"
                                class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                {{-- أيقونة إظهار --}}
                                <svg x-show="!showConfirm" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                {{-- أيقونة إخفاء --}}
                                <svg x-show="showConfirm" x-cloak xmlns="http://www.w3.org/2000/svg" class="w-5 h-5"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a8.962 8.962 0 012.122-.063c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21f-9 9 0 00-9-9m9 9L3 3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                </div>

            </div>


            {{-- الأزرار --}}
            <div class="sticky bottom-4 bg-white border rounded-3xl p-5 shadow-lg">

                <div class="flex flex-col md:flex-row gap-4">

                    <a href="{{ route('admin.users.showEdit') }}"
                        class="px-8 py-4 rounded-2xl border text-center font-bold hover:bg-slate-50 transition">
                        إلغاء
                    </a>

                    <button type="submit"
                        class="flex-1 rounded-2xl py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold transition shadow-md">
                        حفظ التغييرات
                    </button>

                </div>

            </div>

        </form>


        {{-- حذف المستخدم --}}
        <div class="mt-10 rounded-3xl border border-red-200 bg-red-50 p-6">

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5">

                <div>
                    <h3 class="text-lg font-black text-red-700">
                        حذف المستخدم
                    </h3>

                    <p class="text-red-500 text-sm mt-1">
                        حذف المستخدم بشكل نهائي.
                    </p>
                </div>


                <form action="{{ route('admin.users.delete', $user->id) }}" method="POST"
                    onsubmit="return confirm('هل أنت متأكد من حذف المستخدم؟')">

                    @csrf
                    @method('DELETE')

                    <button type="submit"
                        class="px-6 py-3 rounded-2xl bg-red-600 hover:bg-red-700 text-white font-bold shadow-lg transition">
                        🗑️ إزالة المستخدم
                    </button>

                </form>

            </div>

        </div>

    </div>
@endsection
