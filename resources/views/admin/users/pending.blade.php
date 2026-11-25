@extends('layouts.admin')

@section('content')
    <div class="container mx-auto px-6 py-10 max-w-6xl" x-data="{
        openModal: false,
        selectedName: '',
        selectedImage: '',
        selectedPhone: '',
        selectedEmail: '',
        selectedDate: '',
        selectedId: null,
        searchQuery: '',
        filterPhoto: 'all'
    }">

        {{-- رأس الصفحة --}}
        <div
            class="mb-8 bg-white p-8 rounded-[3rem] shadow-sm border border-slate-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <h1 class="text-3xl font-black text-slate-900 italic">مركز الاعتماد 🛂</h1>
                <p class="text-slate-400 text-xs font-medium mt-1">التحقق من الوثائق الرسمية وبطاقات الهوية للمسافرين
                    المسجلين.</p>
            </div>
            <div
                class="bg-indigo-600 text-white px-6 py-2.5 rounded-full font-black italic text-sm shadow-md shadow-indigo-200">
                طلبات معلقة: {{ $users->count() }}
            </div>
        </div>

        {{-- شريط البحث والتصفية --}}
        <div
            class="mb-8 bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-50 flex flex-col md:flex-row gap-4 justify-between items-center">
            {{-- حقل البحث --}}
            <div class="relative w-full md:w-1/2">
                <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-400">
                    🔍
                </span>
                <input type="text" x-model="searchQuery" placeholder="ابحث باسم المسافر، رقم الهوية، الهاتف، أو البريد..."
                    class="w-full pr-11 pl-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 transition-all">
            </div>

            {{-- خيارات التصفية --}}
            <div class="flex items-center gap-3 w-full md:w-auto">
                <span class="text-xs font-black text-slate-400 whitespace-nowrap">تصفية حسب:</span>
                <select x-model="filterPhoto"
                    class="w-full md:w-auto bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold rounded-2xl px-4 py-3 focus:outline-none focus:border-indigo-500 cursor-pointer">
                    <option value="all">جميع الطلبات</option>
                    <option value="with_photo">مرفق صورة هوية 📸</option>
                    <option value="without_photo">بدون صورة هوية 🚫</option>
                </select>
            </div>
        </div>

        {{-- رسائل النجاح التنبيهية --}}
        @if (session('success'))
            <div
                class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 rounded-2xl font-bold flex items-center gap-2">
                <span>✅</span> {{ session('success') }}
            </div>
        @endif

        {{-- شبكة عرض المستخدمين المعلقين --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($users as $user)
                <div x-show="(
                        '{{ strtolower($user->name ?? $user->user_name) }} {{ $user->national_id }} {{ $user->phone }} {{ $user->email }}'.toLowerCase().includes(searchQuery.toLowerCase())
                    ) && (
                        filterPhoto === 'all' ||
                        (filterPhoto === 'with_photo' && {{ $user->id_image ? 'true' : 'false' }})
||
                        (filterPhoto === 'without_photo' && !{{ $user->id_image ? 'true' : 'false' }})
                    )"
                    x-transition
                    class="bg-white rounded-[2.5rem] p-7 shadow-xl border border-slate-50 relative flex flex-col justify-between hover:border-indigo-100 transition-all">

                    <div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-2xl">👤
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900 italic">
                                    {{ $user->name ?? $user->user_name }}
                                </h3>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">
                                    {{ $user->national_id ?? 'لا يوجد رقم هوية' }}
                                </p>
                            </div>
                        </div>

                        {{-- تاريخ الإنشاء وحالة الهوية --}}
                        <div
                            class="mb-6 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] font-medium text-slate-400">
                            <span class="flex items-center gap-1">
                                📅 {{ $user->created_at ? $user->created_at->format('Y/m/d') : 'غير محدد' }}
                            </span>
                            @if ($user->id_image)
                                <span
                                    class="bg-emerald-50 text-emerald-600 px-2.5 py-0.5 rounded-full text-[10px] font-bold">مرفق
                                    هوية</span>
                            @else
                                <span
                                    class="bg-amber-50 text-amber-600 px-2.5 py-0.5 rounded-full text-[10px] font-bold">بدون
                                    هوية</span>
                            @endif
                        </div>
                    </div>

                    {{-- زر مراجعة البيانات --}}
                    <button
                        @click="
                            selectedName = '{{ $user->name ?? $user->user_name }}';
                            selectedImage = '{{ $user->id_image ? asset($user->id_image) : '' }}';
                            selectedPhone = '{{ $user->phone }}';
                            selectedEmail = '{{ $user->email }}';
                            selectedDate = '{{ $user->created_at ? $user->created_at->format('Y/m/d - h:i A') : 'غير محدد' }}';
                            selectedId = {{ $user->id }};
                            openModal = true;
                        "
                        class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-600 transition-all italic shadow-md">
                        مراجعة البيانات 👁️
                    </button>
                </div>
            @empty
                <div
                    class="col-span-1 md:col-span-2 lg:col-span-3 py-20 text-center bg-white rounded-[3rem] border border-slate-100 shadow-sm">
                    <div class="text-6xl mb-4">🎉</div>
                    <h3 class="text-xl font-black text-slate-800">لا توجد طلبات توثيق معلقة</h3>
                    <p class="text-slate-400 text-sm mt-1 font-medium">جميع هويات المسافرين في المنصة مراجعة وموثقة بالكامل.
                    </p>
                </div>
            @endforelse
        </div>

        {{-- النافذة المنبثقة (Modal) --}}
        <div x-show="openModal" x-transition.opacity
            class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/90 backdrop-blur-sm p-4" x-cloak>

            <div @click.away="openModal = false"
                class="bg-white w-full max-w-lg rounded-[3rem] p-8 md:p-10 shadow-2xl relative max-h-[90vh] overflow-y-auto">

                <div class="mb-6 flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 italic" x-text="selectedName"></h2>
                        <p class="text-indigo-600 text-[10px] font-black uppercase tracking-widest mt-1">تفاصيل الهوية
                            والاتصال المرفوعة</p>
                    </div>
                    <div class="text-left bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100">
                        <span class="text-[8px] text-slate-400 block font-bold uppercase">تاريخ الطلب</span>
                        <span class="text-[11px] font-black text-slate-700" x-text="selectedDate"></span>
                    </div>
                </div>

                <div class="space-y-6">
                    {{-- 1. صورة الهوية (معدلة لعرض كامل التفاصيل وإمكانية التكبير) --}}
                    <div x-show="selectedImage"
                        class="relative group rounded-3xl overflow-hidden border-4 border-slate-100 shadow-inner min-h-[220px] max-h-[360px] bg-slate-900/5 flex items-center justify-center p-2">

                        <img :src="selectedImage"
                            class="max-h-[340px] w-auto max-w-full object-contain rounded-2xl transition-transform duration-300 group-hover:scale-[1.02]"
                            alt="National ID">

                        {{-- زر فتح الصورة بحجم كامل في تبويب جديد --}}
                        <a :href="selectedImage" target="_blank"
                            class="absolute bottom-3 left-3 bg-slate-900/80 hover:bg-slate-900 text-white px-3 py-1.5 rounded-xl text-[11px] font-bold backdrop-blur-md transition-all flex items-center gap-1.5 shadow-lg">
                            <span>🔍</span>
                            <span>عرض بحجم كامل</span>
                        </a>
                    </div>

                    {{-- 2. في حالة عدم وجود صورة --}}
                    <div x-show="!selectedImage"
                        class="rounded-3xl border-4 border-slate-50 shadow-md h-48 bg-slate-50 flex flex-col items-center justify-center text-slate-400 gap-2 font-bold">
                        <span class="text-3xl">📸</span>
                        <span class="text-xs italic">لا توجد صورة هوية مرفوعة للمستخدم</span>
                    </div>

                    {{-- بيانات الجوال والبريد --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 p-4 rounded-2xl">
                            <span class="text-[8px] text-slate-400 block uppercase font-black mb-1">رقم الجوال</span>
                            <span class="font-black text-slate-900 italic text-xs block" x-text="selectedPhone"></span>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-2xl">
                            <span class="text-[8px] text-slate-400 block uppercase font-black mb-1">البريد الإلكتروني</span>
                            <span class="font-black text-slate-900 italic text-[10px] truncate block"
                                x-text="selectedEmail"></span>
                        </div>
                    </div>

                    {{-- أزرار الإجراءات --}}
                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <form :action="'/admin/users/verify/' + selectedId" method="POST" class="flex-1">
                            @csrf
                            <button type="submit"
                                class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black text-xs italic shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all">
                                اعتماد الهوية ✅
                            </button>
                        </form>

                        <form :action="'/admin/users/reject/' + selectedId" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" onclick="return confirm('هل أنت متأكد من رفض طلب توثيق هذه الهوية؟')"
                                class="w-full bg-rose-600 text-white py-4 rounded-2xl font-black text-xs italic shadow-lg shadow-rose-200 hover:bg-rose-700 transition-all">
                                رفض الطلب ❌
                            </button>
                        </form>

                        <button @click="openModal = false"
                            class="px-6 py-4 bg-slate-100 text-slate-500 rounded-2xl font-black text-xs italic hover:bg-slate-200 transition-all">
                            إغلاق
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (!View::hasSection('scripts'))
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endif

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endsection
