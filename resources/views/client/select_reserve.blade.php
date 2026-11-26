@extends('layouts.client')

@section('content')
    <div class="container mx-auto px-4 py-10 max-w-6xl" x-data="bookingManager()"
        :class="{ 'overflow-hidden h-screen': showLoginModal }">

        {{-- 1. رأس الصفحة (تمت إضافة شارة طبيعة الرحلة هنا) --}}
        <div
            class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-100 mb-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-6">
                <div
                    class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-3xl shadow-lg shadow-blue-100">
                    {{ $trip->transport_type == 'bus' ? '🚌' : '✈️' }}
                </div>
                <div>
                    <div class="flex items-center gap-3 flex-wrap mb-1">
                        <h1 class="text-2xl font-black text-slate-800">
                            {{ $trip->title ?? $trip->from_city . ' ⬅️ ' . $trip->to_city }}
                        </h1>

                        {{-- ℹ️ شارة معلومة الفصل (عوائل / عامة) المجلوبة من قاعدة البيانات --}}
                        @if (isset($trip->auto_split) && $trip->auto_split)
                            <span
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-emerald-50 border border-emerald-100 text-emerald-600 shadow-sm animate-pulse">
                                👨‍👩‍👧‍👦 رحلة مخصصة للعوائل
                            </span>
                        @else
                            <span
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-blue-50 border border-blue-100 text-blue-600 shadow-sm">
                                🌍 رحلة عامة (شباب وعوائل)
                            </span>
                        @endif
                    </div>

                    <p class="text-slate-400 font-bold text-sm tracking-tight mb-3">عبر:
                        {{ $trip->vehicle_model ?? ($trip->transport_type === 'flight' ? 'طيران ممتد' : 'حافلة VIP مريحة') }}
                    </p>

                    {{-- قسم المميزات (Amenities) المجلوبة ديناميكياً --}}
                    @if (!empty($features) && count($features) > 0)
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach ($features as $feature)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black bg-indigo-50/60 border border-indigo-100 text-indigo-600 shadow-sm">
                                    ✨ {{ $feature }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- اختيار نوع وعدد الغرف المطلوبة --}}
            <div class="flex flex-col gap-3 bg-slate-50 p-5 rounded-2xl border border-slate-100 w-full md:w-auto">
                <label class="text-[11px] text-slate-500 font-black uppercase tracking-wider block">
                    📌 حدد الغرف الخاصة المطلوبة في الفندق (أو اتركها صفر للتوزيع المشترك التلقائي لتوفير السعر):
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <template x-for="(price, type) in roomPrices" :key="type">
                        <template x-if="price && price > 0">
                            <div
                                class="bg-white p-3 rounded-xl border border-slate-200/60 flex flex-col items-center justify-between gap-2 shadow-sm">
                                <div class="text-center">
                                    <span class="text-xs font-black text-slate-800" x-text="getTypeName(type)"></span>
                                    <p class="text-[10px] text-blue-600 font-bold" x-text="parseFloat(price) + ' ر.س'"></p>
                                </div>
                                <div class="flex items-center gap-2 bg-slate-50 border border-slate-100 rounded-lg p-1">
                                    <button type="button" @click="decreaseRoom(type)"
                                        class="w-6 h-6 rounded bg-white border border-slate-200 flex items-center justify-center font-bold text-slate-600 hover:bg-slate-100 text-xs">-</button>
                                    <span class="text-xs font-black text-slate-800 w-4 text-center"
                                        x-text="roomQuantities[type] || 0"></span>
                                    <button type="button" @click="increaseRoom(type)"
                                        class="w-6 h-6 rounded bg-blue-600 text-white flex items-center justify-center font-bold hover:bg-blue-700 text-xs">+</button>
                                </div>
                            </div>
                        </template>
                    </template>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            {{-- 2. مخطط المقاعد التفاعلي --}}
            <div class="lg:col-span-7 bg-white rounded-[3rem] p-10 shadow-sm border border-slate-100">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                    <h3 class="font-black text-slate-800 text-xl tracking-tighter">
                        {{ $trip->transport_type == 'bus' ? 'مخطط الحافلة 🚌' : 'مخطط الطائرة ✈️' }}
                    </h3>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-2">
                        <div class="flex items-center gap-2"><span
                                class="w-3 h-3 bg-white rounded-sm border border-slate-300"></span> <span
                                class="text-[10px] font-bold text-slate-500">متاح</span></div>
                        <div class="flex items-center gap-2"><span class="w-3 h-3 bg-amber-400 rounded-sm"></span> <span
                                class="text-[10px] font-bold text-amber-600">محدد</span></div>
                        <div class="flex items-center gap-2"><span class="w-3 h-3 bg-pink-500 rounded-sm shadow-sm"></span>
                            <span class="text-[10px] font-bold text-pink-600">محجوز (أنثى)</span>
                        </div>
                        <div class="flex items-center gap-2"><span class="w-3 h-3 bg-blue-800 rounded-sm shadow-sm"></span>
                            <span class="text-[10px] font-bold text-blue-900">محجوز (ذكر)</span>
                        </div>
                    </div>
                </div>
                @if ($trip->transport_type == 'bus')
                    {{-- هيكل الحافلة الهندسي الواقعي --}}
                    @php
                        // --- الإعدادات المنطقية للمخطط هندسيًا ---

                        // 1. تحديد عدد المقاعد النهائي بحد أقصى 49
                        $maxAllowedSeats = 49;
                        $totalSeats = isset($trip->total_seats)
                            ? min($trip->total_seats, $maxAllowedSeats)
                            : $maxAllowedSeats;

                        // 2. حسابات التوزيع
                        $halfSeats = ceil($totalSeats / 2 + 3);

                        // 3. تحديد هندسة الصف الأخير (متصل يتسع لـ 5 مقاعد)
                        $lastRowStart = $totalSeats - 4;

                        // 4. حالة التقسيم التلقائي
                        $isAutoSplit = isset($trip->auto_split) && $trip->auto_split == 1;

                        // --- إعداد رابط الصورة كخلفية ---
                        $busUrlImage = asset('images/BD.jpeg');

                        // =========================================================================
                        // *** طريقة التحكم بـأماكن المقاعد ***
                        // =========================================================================
                        // كل صف عبارة عن سلسلة نصية من 5 أحرف (تمثل الـ 5 أعمدة في Grid):
                        // 'S': مقعد مسافر عادي (قابل للحجز)
                        // 'X': مقعد كادر (غير قابل للحجز)
                        // '.': ممر/مساحة فارغة
                        // '-': ممر أفقي (يستخدم في الممر الأوسط)
                        // 'D': مساحة الباب الأمامي/الكادر الخاصة

                        $layout_config = [
                            '...D', // الصف الأول للمسافرين: كادر, فراغ، فراغ، فراغ
                            'SS.SS', // الصف الأول للمسافرين: مقعد، مقعد، ممر، مقعد، مقعد
                            'SS.SS', // الصف الثاني للمسافرين
                            'SS.SS', // الصف الثالث للمسافرين
                            'SS.SS', // الصف الرابع للمسافرين
                            'SS.SS', // ممر أفقي كامل بين الصفوف
                            'SS.SS', // الصف السادس للمسافرين
                            'SS...', // الصف السابع للمسافرين
                            'SS...', // الصف السابع للمسافرين
                            'SS...', // باب
                            'SS.SS', // الصف التاسع للمسافرين
                            'SS.SS', // الصف العاشر للمسافرين
                            'SS.SS', // ممر أفقي كامل آخر
                            'SSSSS', // الصف الأخير (متصل)
                        ];

                        $seatCounter = 3; // عداد المقاعد
                    @endphp

                    <div class="relative max-w-[380px] mx-auto pb-10 font-sans" dir="ltr" x-data="{ showBookedError: false, openPassengerModal: false, activeModalSeat: null }"
                        x-cloak>

                        {{-- رسالة الخطأ المنبثقة --}}
                        <div x-show="showBookedError" x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/20 backdrop-blur-[2px]"
                            @click="showBookedError = false">

                            <div class="bg-white border border-slate-200 shadow-2xl rounded-3xl p-6 flex flex-col items-center gap-4 max-w-[280px] w-full transform"
                                @click.stop>
                                <div
                                    class="w-14 h-14 bg-red-50 text-red-500 rounded-full flex items-center justify-center text-2xl">
                                    🚫
                                </div>
                                <div class="text-center">
                                    <h3 class="text-base font-black text-slate-800 mb-1">عذراً، غير متاح!</h3>
                                    <p class="text-[12px] text-slate-500 font-bold leading-relaxed">هذا المقعد محجوز مسبقاً،
                                        أو مخصص للكادر.</p>
                                </div>
                                <button @click="showBookedError = false"
                                    class="w-full bg-slate-900 text-white font-black text-sm py-3 rounded-xl hover:bg-slate-800 active:scale-95 transition-all">
                                    حسناً
                                </button>
                            </div>
                        </div>
                        {{-- نافذة إدخال بيانات المسافر المنبثقة --}}
                        <div x-show="openPassengerModal" x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm"
                            @click="openPassengerModal = false">

                            {{-- محتوى النافذة --}}
                            <div class="bg-slate-900 border border-slate-700 shadow-2xl rounded-[2rem] p-6 max-w-[340px] w-full transform text-right text-white"
                                @click.stop dir="rtl">

                                <template x-if="activeModalSeat">
                                    <div class="space-y-5">
                                        {{-- الهيدر --}}
                                        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                                            <h3 class="text-base font-black flex items-center gap-2">
                                                <span class="p-1.5 bg-blue-600 rounded-lg text-xs">💺</span>
                                                بيانات راكب المقعد (<span x-text="activeModalSeat"
                                                    class="text-blue-400"></span>)
                                            </h3>
                                            <button @click="openPassengerModal = false"
                                                class="text-slate-400 hover:text-white font-bold text-sm">✕</button>
                                        </div>

                                        {{-- الحقول --}}
                                        <div class="space-y-4">
                                            <div>
                                                <label class="text-[11px] text-slate-400 mb-1.5 block font-bold">رقم الهوية
                                                    / الإقامة:</label>
                                                <input type="text" x-model="passengerIds[activeModalSeat]"
                                                    placeholder="رقم هوية المسافر"
                                                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-xs focus:ring-2 focus:ring-blue-600 transition-all outline-none text-white placeholder-slate-600">
                                            </div>

                                            <div>
                                                <label
                                                    class="text-[11px] text-slate-400 mb-1.5 block font-bold">الجنس:</label>
                                                <select x-model="passengerGenders[activeModalSeat]"
                                                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-xs focus:ring-2 focus:ring-blue-600 transition-all outline-none text-white cursor-pointer">
                                                    <option value="">اختر الجنس</option>
                                                    <option value="male">ذكر 👨</option>
                                                    <option value="female">أنثى 👩</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label class="text-[11px] text-slate-400 mb-1.5 block font-bold">ترتيب
                                                    تسكين الغرف:</label>
                                                <select x-model="seatRoomAssignments[activeModalSeat]"
                                                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-xs focus:ring-2 focus:ring-blue-600 transition-all outline-none text-white cursor-pointer">
                                                    <option value="shared">مشترك مع العموم 🎲</option>
                                                    <template x-for="rm in bookedRoomsList" :key="rm.uid">
                                                        <option :value="rm.uid" x-text="rm.name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- أزرار التحكم داخل المودال --}}
                                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-800">
                                            <button @click="openPassengerModal = false"
                                                class="w-full bg-blue-600 text-white font-black text-xs py-3 rounded-xl hover:bg-blue-700 active:scale-95 transition-all">
                                                حفظ وإغلاق ✓
                                            </button>
                                            <button @click="toggleSeat(activeModalSeat); openPassengerModal = false;"
                                                class="w-full bg-red-950/40 text-red-400 border border-red-900/40 font-bold text-xs py-3 rounded-xl hover:bg-red-900/30 active:scale-95 transition-all">
                                                إلغاء المقعد 🗑
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        {{-- شريط حالة التوزيع (تم دفعه للأسفل بـ mt-32 ليرجع المقاعد للخلف) --}}
                        <div class="relative z-20 mt-32 mb-6 px-6 dashboard-separator pb-10">
                            <div class="text-center text-[10px] font-bold p-3 rounded-xl border shadow-sm tracking-wide {{ $isAutoSplit ? 'bg-indigo-50/90 text-indigo-950 border-indigo-200' : 'bg-white/90 text-slate-800 border-slate-300' }} backdrop-blur-sm"
                                dir="rtl">
                                @if ($isAutoSplit)
                                    <div class="flex items-center justify-center gap-2.5">
                                        <span class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 bg-blue-600 rounded-sm male-indicator"></span>
                                            مقدمة للرجال
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 bg-pink-500 rounded-sm mr-1 female-indicator"></span>
                                            خلفية للسيدات
                                        </span>
                                    </div>
                                    <span class="block text-[8px] font-black opacity-50 mt-1" dir="ltr">
                                        [ MAX SEATS: {{ $totalSeats }} | AUTO_SPLIT: ON ]
                                    </span>
                                @else
                                    <span> اختيار المقاعد متاح حسب أولوية التسجيل</span>
                                @endif
                            </div>
                        </div>
                        {{-- الحاوية الرئيسية للهيكل --}}
                        <div class="relative shadow-2xl overflow-hidden z-10 bg-zinc-100 rounded-[2.5rem]"
                            style="background-image: url('{{ $busUrlImage }}'); background-size: 100% 100%; background-position: top center; background-repeat: no-repeat;">



                            {{-- شبكة المقاعد --}}
                            <div
                                class="grid grid-cols-5 gap-y-4 gap-x-2 relative z-10 px-7 pr-[3.75rem]  pt-[12.25rem] pb-10 seat-grid">

                                @foreach ($layout_config as $rowString)
                                    {{-- تحويل السلسلة النصية إلى مصفوفة من الحروف --}}
                                    @php $cells = str_split($rowString); @endphp

                                    @foreach ($cells as $cellType)
                                        @php
                                            // تهيئة المتغيرات الافتراضية
                                            $isBooked = false;
                                            $btnClass = '';
                                            $iconClass = '';
                                            $seatShape = '';
                                            $showSeat = false;
                                            $isStaffSeat = false;
                                            $customContent = '';
                                        @endphp

                                        @if ($cellType == 'S' || $cellType == 'X')
                                            {{-- تصميم المقاعد --}}
                                            @php
                                                $i = $seatCounter; // رقم المقعد الحالي
                                                $seatCounter++; // زيادة العداد للمقعد التالي

                                                $isStaffSeat = $cellType == 'X'; // هل هو مقعد كادر؟
                                                $isBooked = $isStaffSeat ? false : in_array($i, $booked_seats); // هل هو محجوز؟

                                                $rawGender = $booked_seats_details[$i] ?? null;
                                                $bookedGender = $rawGender ? strtolower(trim($rawGender)) : null;

                                                $isFemaleZone = $isAutoSplit && $i > $halfSeats;
                                                $seatShape = $isFemaleZone ? 'female' : 'male';

                                                // إعداد الفئات المخصصة
                                                if ($isBooked) {
                                                    if (
                                                        $bookedGender === 'female' ||
                                                        ($isFemaleZone && !$bookedGender)
                                                    ) {
                                                        $btnClass =
                                                            'bg-gradient-to-b from-pink-100/90 to-pink-200/90 border-pink-400 text-pink-800 cursor-not-allowed opacity-90';
                                                        $iconClass = 'text-pink-500';
                                                        $seatShape = 'female';
                                                    } elseif (
                                                        $bookedGender === 'male' ||
                                                        (!$isFemaleZone && !$bookedGender)
                                                    ) {
                                                        $btnClass =
                                                            'bg-gradient-to-b from-blue-100/90 to-blue-200/90 border-blue-400 text-blue-800 cursor-not-allowed opacity-90';
                                                        $iconClass = 'text-blue-600';
                                                        $seatShape = 'male';
                                                    } else {
                                                        $btnClass =
                                                            'bg-gradient-to-b from-zinc-200/90 to-zinc-300/90 border-zinc-400 text-zinc-800 cursor-not-allowed opacity-90';
                                                        $iconClass = 'text-zinc-500';
                                                    }
                                                } else {
                                                    $btnClass = $isFemaleZone
                                                        ? 'bg-white/90 border-pink-200 text-pink-700 hover:bg-pink-50 hover:border-pink-400 hover:shadow-md'
                                                        : 'bg-white/90 border-blue-200 text-blue-700 hover:bg-blue-50 hover:border-blue-400 hover:shadow-md';
                                                    $iconClass = $isFemaleZone ? 'text-pink-300' : 'text-blue-300';
                                                }
                                            @endphp

                                            @if ($isStaffSeat)
                                                {{-- تصميم خاص بمقاعد الكادر (غير قابلة للحجز) --}}
                                                <button type="button" @click="showBookedError = true"
                                                    class="relative w-full h-12 rounded-t-xl rounded-b-lg flex flex-col items-center justify-center ">
                                                </button>
                                            @else
                                                {{-- مقاعد المسافرين العادية بعد التعديل --}}
                                                <button type="button"
                                                    @if ($isBooked) @click="showBookedError = true" 
    @else 
        {{-- التعديل هنا: إذا لم يكن مختاراً يتم تحديده أولاً، ثم تفتح النافذة للمقعد الحالي --}}
        @click="if(!isSelected({{ $i }})) { toggleSeat({{ $i }}) }; activeModalSeat = {{ $i }}; openPassengerModal = true;" @endif
                                                    class="relative w-full h-12 rounded-t-xl rounded-b-lg transition-all duration-200 flex flex-col items-center justify-center border-[2px] shadow-sm seat-button backdrop-blur-[1px] {{ $isBooked ? $btnClass : '' }}"
                                                    @if (!$isBooked) :class="{
       '{{ $btnClass }}': !isSelected({{ $i }}),
       'bg-gradient-to-b from-amber-300 to-amber-500 border-amber-600 text-amber-950 shadow-lg scale-105 z-20': isSelected({{ $i }})
    }" @endif>

                                                    <div
                                                        class="absolute left-[2px] top-2 bottom-2 w-1 bg-black/10 rounded-full">
                                                    </div>
                                                    <div
                                                        class="absolute right-[2px] top-2 bottom-2 w-1 bg-black/10 rounded-full">
                                                    </div>
                                                    <div class="absolute top-[2px] w-5 h-1.5 bg-black/10 rounded-full">
                                                    </div>

                                                    <span
                                                        class="text-[11px] font-black z-10 mb-0.5">{{ $i }}</span>

                                                    <div class="flex flex-col items-center transform scale-90"
                                                        :class="isSelected({{ $i }}) ? 'text-amber-100' :
                                                            '{{ $iconClass }}'">
                                                        @if ($seatShape === 'female')
                                                            <div
                                                                class="w-3 h-3 bg-current rounded-full z-10 shadow-sm border border-black/5">
                                                            </div>
                                                            <div
                                                                class="w-[18px] h-2 bg-current rounded-t-full -mt-1 shadow-sm border border-black/5">
                                                            </div>
                                                        @else
                                                            <div
                                                                class="w-2.5 h-2.5 bg-current rounded-sm z-10 shadow-sm border border-black/5">
                                                            </div>
                                                            <div
                                                                class="w-4 h-2 bg-current rounded-t-md -mt-0.5 shadow-sm border border-black/5">
                                                            </div>
                                                        @endif
                                                    </div>
                                                </button>
                                            @endif
                                        @elseif ($cellType == '.')
                                            {{-- ممر أو مساحة فارغة --}}
                                            <div class="w-full h-12 bg-transparent"></div>
                                        @elseif ($cellType == '-')
                                            {{-- ممر أفقي --}}
                                            <div class="w-full h-2 bg-zinc-200 rounded-full"></div>
                                        @elseif ($cellType == 'D')
                                            {{-- تصميم مخصص لمنطقة الكادر والباب الأمامي --}}
                                            <div class="col-span-2 flex flex-col items-center justify-center ">
                                                <button type="button" @click="showBookedError = true"
                                                    class="relative w-full h-12 rounded-t-xl rounded-b-lg flex flex-col items-center justify-center border-[2px] border-slate-300 bg-slate-200 text-slate-500 cursor-not-allowed shadow-sm backdrop-blur-[1px]">
                                                    <div
                                                        class="absolute left-[2px] top-2 bottom-2 w-1 bg-black/5 rounded-full">
                                                    </div>
                                                    <div
                                                        class="absolute right-[2px] top-2 bottom-2 w-1 bg-black/5 rounded-full">
                                                    </div>
                                                    <div class="absolute top-[2px] w-5 h-1.5 bg-black/5 rounded-full">
                                                    </div>
                                                    <span class="text-[11px] font-black z-10 mb-0.5">كادر</span>
                                                    <div
                                                        class="w-3 h-3 bg-slate-400 rounded-full z-10 shadow-sm border border-black/5 mt-0.5">
                                                    </div>
                                                </button>
                                            </div>
                                        @else
                                            {{-- أي حرف آخر غير معروف، نعتبره ممر --}}
                                            <div class="w-full h-12 bg-transparent"></div>
                                        @endif
                                    @endforeach
                                @endforeach

                            </div>
                        </div>
                    </div>
                @else
                    {{-- هيكل الطائرة (كما هو بدون تغيير جذري للحفاظ على التركيز على الحافلة) --}}
                    <div
                        class="max-w-md mx-auto border-x-[12px] border-t-[40px] border-slate-100 rounded-t-[10rem] p-8 relative shadow-inner bg-white">
                        <div
                            class="text-center mb-10 uppercase tracking-widest text-[10px] font-bold text-slate-300 italic">
                            منطقة قمرة القيادة</div>
                        <div class="grid grid-cols-7 gap-y-4 items-center">
                            @for ($i = 1; $i <= 90; $i++)
                                @php
                                    $isBooked = in_array($i, $booked_seats);
                                    $rawFlightGender = $booked_seats_details[$i] ?? null;
                                    $flightGender = $rawFlightGender ? strtolower(trim($rawFlightGender)) : null;

                                    $colorClass = '';
                                    if ($isBooked) {
                                        if ($flightGender === 'female') {
                                            $colorClass =
                                                'bg-pink-500 border-pink-600 text-white cursor-not-allowed shadow-sm';
                                        } elseif ($flightGender === 'male') {
                                            $colorClass =
                                                'bg-blue-900 border-blue-950 text-white cursor-not-allowed shadow-sm';
                                        } else {
                                            $colorClass =
                                                'bg-slate-400 border-slate-500 text-white cursor-not-allowed shadow-sm';
                                        }
                                    }
                                @endphp

                                <button
                                    @if ($isBooked) disabled @else @click="toggleSeat({{ $i }})" @endif
                                    class="w-11 h-11 mx-auto rounded-xl font-bold text-xs transition-all duration-150 flex flex-col items-center justify-center border-2 {{ $isBooked ? $colorClass : '' }}"
                                    @if (!$isBooked) :class="{
                                            'bg-slate-50 border-slate-200/60 text-slate-700 hover:bg-slate-100': !isSelected({{ $i }}),
                                            'bg-blue-600 border-blue-700 text-white shadow-md shadow-blue-200 scale-95': isSelected({{ $i }})
                                        }" @endif>
                                    @if ($isBooked)
                                        <span class="text-[10px] leading-none mb-0.5">
                                            {{ $flightGender === 'female' ? '👩' : ($flightGender === 'male' ? '👨' : '❓') }}
                                        </span>
                                    @endif
                                    <span>{{ $i }}</span>
                                </button>

                                @if ($i % 6 == 3)
                                    <div class="text-center text-[9px] font-black text-slate-200 uppercase rotate-90">ممر
                                    </div>
                                @endif
                            @endfor
                        </div>
                    </div>
                @endif
            </div>

            {{-- 3. تفاصيل الحجز والملخص --}}
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white shadow-2xl sticky top-8">
                    <h3 class="text-xl font-black mb-6 flex items-center gap-2">
                        <span class="p-2 bg-blue-600 rounded-xl text-sm">📋</span> ملخص الرحلة والغرف
                    </h3>

                    <div class="space-y-4 bg-slate-800/50 p-6 rounded-3xl mb-6 border border-slate-700">
                        {{-- المقاعد المختارة --}}
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-slate-400 font-bold tracking-widest uppercase">المقاعد المختارة:</span>
                            <span class="font-black text-white"
                                x-text="selectedSeats.sort((a,b)=>a-b).join(', ') || 'لم يتم الاختيار'"></span>
                        </div>

                        {{-- الغرف المحجوزة --}}
                        <div class="flex justify-between items-start text-xs flex-col gap-1">
                            <span class="text-slate-400 font-bold tracking-widest uppercase">الغرف المحجوزة:</span>
                            <div class="flex flex-wrap gap-1 mt-1">
                                <template x-for="rm in bookedRoomsList" :key="rm.uid">
                                    <span class="bg-slate-700 text-blue-400 px-2 py-0.5 rounded-md font-black text-[10px]"
                                        x-text="rm.name"></span>
                                </template>
                                <template x-if="bookedRoomsList.length === 0">
                                    <span class="text-amber-400 font-bold">لم يتم اختيار غرف (توزيع مشترك تلقائي)</span>
                                </template>
                            </div>
                        </div>

                        {{-- تكلفة المقاعد --}}
                        <div class="flex justify-between items-center text-xs pt-2 border-t border-slate-800/60">
                            <span class="text-slate-400 font-bold tracking-widest uppercase">تكلفة المقاعد (<span
                                    x-text="selectedSeats.length"></span>):</span>
                            <span class="font-black text-white"
                                x-text="(selectedSeats.length * seatPrice) + ' ر.س'"></span>
                        </div>

                        {{-- الإجمالي --}}
                        <div class="pt-4 border-t border-slate-700 flex justify-between items-end">
                            <span class="text-slate-400 text-xs font-bold">المبلغ الإجمالي الموحد:</span>
                            <div class="text-left">
                                <p class="text-3xl font-black text-white" x-text="totalPrice + ' ر.س'"></p>
                                <p class="text-[10px] text-blue-400 font-bold tracking-widest uppercase">شامل الغرف
                                    والمقاعد</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 max-h-[280px] pr-1">
                        <template x-if="selectedSeats.length > 0">
                            <p class="text-[11px] text-slate-400 font-bold mb-2">👥 كشوفات المسافرين المضافين (اضغط
                                للتعديل):</p>
                        </template>

                        <template x-for="(seat, index) in selectedSeats.sort((a,b)=>a-b)" :key="seat">
                            <div @click="activeModalSeat = seat; openPassengerModal = true;"
                                class="bg-slate-800/80 hover:bg-slate-800 p-3 rounded-xl border border-slate-700/60 flex items-center justify-between cursor-pointer transition-all hover:scale-[1.01]">

                                <div class="flex items-center gap-3">
                                    <span
                                        class="w-7 h-7 rounded-lg bg-blue-600/20 text-blue-400 flex items-center justify-center font-black text-xs"
                                        x-text="seat"></span>
                                    <div class="text-right">
                                        <p class="text-xs font-bold text-slate-200">
                                            <span
                                                x-text="passengerIds[seat] ? 'هوية: ' + passengerIds[seat] : '⚠️ لم تُدخل الهوية بعد'"></span>
                                        </p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">
                                            <span
                                                x-text="passengerGenders[seat] === 'male' ? '👨 ذكر' : (passengerGenders[seat] === 'female' ? '👩 أنثى' : 'الجنس غير محدد')"></span>
                                            • <span
                                                x-text="seatRoomAssignments[seat] === 'shared' || !seatRoomAssignments[seat] ? 'سكن مشترك' : 'غرفة خاصة'"></span>
                                        </p>
                                    </div>
                                </div>

                            </div>
                        </template>
                    </div>

                    <button @click="proceed()" :disabled="selectedSeats.length === 0"
                        class="w-full mt-6 py-4 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-800 disabled:text-slate-600 text-white rounded-2xl font-black text-base transition-all shadow-xl shadow-blue-600/20 active:scale-95">
                        تأكيد الحجز والدفع 💳
                    </button>
                </div>
            </div>
        </div>


    </div>

    <script>
        function bookingManager() {
            return {
                selectedSeats: [],
                passengerIds: {},
                passengerGenders: {}, // تخزين الجنس لكل مقعد
                seatRoomAssignments: {}, // تخزين ارتباط المقعد بالغرفة المحددة
                roomQuantities: {
                    quad: 0,
                    triple: 0,
                    double: 0,
                    single: 0
                }, // كمية الغرف المحجوزة لكل نوع
                roomPrices: @json($trip->room_prices),
                seatPrice: parseFloat(@json($trip->price)) || 0,
                isLoggedIn: @json(Auth::check()),
                showLoginModal: false,

                toggleSeat(id) {
                    if (!this.isLoggedIn) {
                        this.showLoginModal = true;
                        return;
                    }

                    if (this.selectedSeats.includes(id)) {
                        this.selectedSeats = this.selectedSeats.filter(s => s !== id);
                        delete this.passengerIds[id];
                        delete this.passengerGenders[id];
                        delete this.seatRoomAssignments[id];
                    } else {
                        // السماح باختيار المقاعد للعائلة بالكامل
                        this.selectedSeats.push(id);
                        this.seatRoomAssignments[id] = 'shared'; // افتراضياً توزيع عشوائي للعموم

                        if (this.selectedSeats.length === 1) {
                            this.passengerIds[id] = @json(optional(Auth::user())->id_number ?? '');
                            this.passengerGenders[id] = @json(optional(Auth::user())->gender ?? '');
                        }
                    }
                },

                increaseRoom(type) {
                    if (!this.isLoggedIn) {
                        this.showLoginModal = true;
                        return;
                    }
                    this.roomQuantities[type] = (this.roomQuantities[type] || 0) + 1;
                },

                decreaseRoom(type) {
                    if (!this.isLoggedIn) {
                        this.showLoginModal = true;
                        return;
                    }
                    if (this.roomQuantities[type] > 0) {
                        this.roomQuantities[type]--;
                        this.syncRoomAssignments();
                    }
                },

                // توليد قائمة الغرف المحجوزة ديناميكياً لاستخدامها في المنسدل
                get bookedRoomsList() {
                    let list = [];
                    for (let type in this.roomQuantities) {
                        let qty = this.roomQuantities[type];
                        for (let i = 1; i <= qty; i++) {
                            list.push({
                                uid: `${type}_${i}`,
                                name: `غرفة ${this.getTypeName(type)} #${i}`
                            });
                        }
                    }
                    return list;
                },

                syncRoomAssignments() {
                    // تنظيف أي مقاعد مرتبطة بغرفة تم حذفها وإعادتها للتوزيع العشوائي
                    const validUids = this.bookedRoomsList.map(r => r.uid);
                    for (let seat in this.seatRoomAssignments) {
                        if (this.seatRoomAssignments[seat] !== 'shared' && !validUids.includes(this.seatRoomAssignments[
                                seat])) {
                            this.seatRoomAssignments[seat] = 'shared';
                        }
                    }
                },

                isSelected(id) {
                    return this.selectedSeats.includes(id);
                },

                getTypeName(type) {
                    return {
                        quad: 'رباعية',
                        triple: 'ثلاثية',
                        double: 'ثنائية',
                        single: 'فردية'
                    } [type];
                },

                // حساب المبلغ الإجمالي التراكمي للغرف + المقاعد المحجوزة
                get totalPrice() {
                    let totalRoomCost = 0;
                    for (let type in this.roomQuantities) {
                        let qty = this.roomQuantities[type] || 0;
                        let price = parseFloat(this.roomPrices[type]) || 0;
                        totalRoomCost += qty * price;
                    }
                    const totalSeatsCost = this.selectedSeats.length * this.seatPrice;
                    return totalRoomCost + totalSeatsCost;
                },

                init() {
                    // إذا لم يختر العميل شيئاً، لا نضع قيم افتراضية لكي يبدأ العداد من 0
                },

                proceed() {
                    if (!this.isLoggedIn) {
                        this.showLoginModal = true;
                        return;
                    }

                    // التحقق من تعبئة البيانات لكل المقاعد المحجوزة
                    for (let seat of this.selectedSeats) {
                        if (!this.passengerIds[seat] || this.passengerIds[seat].trim() === '') {
                            alert(`يرجى كتابة رقم الهوية للمقعد رقم ${seat}.`);
                            return;
                        }
                        if (!this.passengerGenders[seat] || this.passengerGenders[seat] === '') {
                            alert(`يرجى تحديد جنس الراكب للمقعد رقم ${seat}.`);
                            return;
                        }
                    }

                    const seats = this.selectedSeats.join(',');
                    const ids = this.selectedSeats.map(seat => this.passengerIds[seat]).join(',');
                    const genders = this.selectedSeats.map(seat => this.passengerGenders[seat]).join(',');

                    // إنشاء Form وإرساله عبر POST لحماية البيانات
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = "{{ route('client.checkout') }}";

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = "{{ csrf_token() }}";
                    form.appendChild(csrfInput);

                    // تجهيز البيانات المراد نقلها للخلفية (Backend)
                    const fields = {
                        trip_id: "{{ $trip->id }}",
                        seats: seats,
                        passenger_ids: ids,
                        passenger_genders: genders,
                        room_quantities: JSON.stringify(this.roomQuantities), // إرسال الغرف المختارة كـ JSON
                        room_assignments: JSON.stringify(this
                            .seatRoomAssignments) // إرسال توزيع المقاعد على الغرف كـ JSON
                    };

                    for (const [key, value] of Object.entries(fields)) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = value;
                        form.appendChild(input);
                    }

                    document.body.appendChild(form);
                    form.submit();
                }
            }
        }
    </script>
@endsection
