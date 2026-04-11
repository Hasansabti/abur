{{-- @extends('layouts.agency')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">إضافة رحلة جديدة</h1>
                <p class="text-xs text-gray-400 mt-1">تجهيز تفاصيل الرحلة، السكن، وبيانات التحويل البنكي</p>
            </div>
            <span class="text-sm text-gray-500 bg-gray-50 px-3 py-1 rounded-lg border">إدارة الوكالة</span>
        </div>

        <form action="{{ route('agency.trips.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            {{-- 1. نظام الرحلة ووسيلة النقل --}}
{{-- <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-blue-50 p-5 rounded-2xl border border-blue-100">
                    <label class="block text-xs font-black text-blue-800 mb-3 uppercase tracking-wider">نظام الرحلة</label>
                    <div class="flex gap-6">
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio" name="trip_type" value="one_way" checked
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500" onchange="toggleReturnFields(false)">
                            <span
                                class="mr-2 text-sm font-bold text-gray-700 group-hover:text-blue-600 transition-colors">ذهاب
                                فقط</span>
                        </label>
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio" name="trip_type" value="round_trip"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500" onchange="toggleReturnFields(true)">
                            <span
                                class="mr-2 text-sm font-bold text-gray-700 group-hover:text-blue-600 transition-colors">ذهاب
                                وعودة</span>
                        </label>
                    </div>
                </div>

                <div class="bg-indigo-50 p-5 rounded-2xl border border-indigo-100">
                    <label class="block text-xs font-black text-indigo-800 mb-3 uppercase tracking-wider">وسيلة
                        النقل</label>
                    <div class="flex gap-6">
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio" name="transport_type" value="bus" checked
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                            <span
                                class="mr-2 text-sm font-bold text-gray-700 group-hover:text-indigo-600 transition-colors">🚌
                                بري (باص)</span>
                        </label>
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio" name="transport_type" value="flight"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                            <span
                                class="mr-2 text-sm font-bold text-gray-700 group-hover:text-indigo-600 transition-colors">✈️
                                طيران</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- 2. المسار والجدولة --}}
{{-- <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
                <h2 class="text-md font-black mb-6 flex items-center gap-2 text-slate-800">
                    <span class="p-2 bg-slate-100 rounded-lg text-xs">📍</span> المسار والجدولة الزمنية
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-4">
                        <h3 class="font-black text-gray-400 text-[10px] uppercase tracking-widest flex items-center">تفاصيل
                            الانطلاق</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">من (المدينة)</label>
                                <select name="from_city"
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-blue-500">
                                    <option>الرياض</option>
                                    <option>جدة</option>
                                    <option>الدمام</option>
                                    <option>الأحساء</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">إلى (الوجهة)</label>
                                <select name="to_city"
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-blue-500">
                                    <option>مكة المكرمة</option>
                                    <option>المدينة المنورة</option>
                                    <option>العلا</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">تاريخ الانطلاق</label>
                                <input type="datetime-local" name="departure_at"
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">عدد الأيام</label>
                                <input type="number" name="duration_days" placeholder="مثلاً: 5"
                                    class="w-full bg-gray-50 border-gray-200 rounded-xl text-sm">
                            </div>
                        </div>

                        <div
                            class="border-2 border-dashed border-slate-200 rounded-2xl p-3 w-40 h-40 flex flex-col items-center justify-center bg-slate-50 group hover:border-blue-300 transition-all">

                            <label class="cursor-pointer flex flex-col items-center text-center">
                                <span class="text-xl mb-1 group-hover:scale-110 transition-transform">📸</span>
                                <span class="text-[10px] font-bold text-slate-500">ارفق صورة الرحلة</span>
                                <input type="file" name="bank_image" class="hidden">
                            </label>

                        </div>
                    </div>

                    <div id="return_section" class="space-y-4 hidden border-r pr-8 border-gray-100 animate-fade-in">
                        <h3 class="font-black text-blue-400 text-[10px] uppercase tracking-widest flex items-center">تفاصيل
                            العودة</h3>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">تاريخ ووقت العودة</label>
                            <input type="datetime-local" name="return_at"
                                class="w-full bg-blue-50/50 border-blue-100 rounded-xl text-sm">
                        </div>
                        <p class="text-[10px] text-blue-500 bg-blue-50 p-3 rounded-xl border border-blue-100 italic">💡 سيتم
                            استخدام مسار عكسي تلقائياً.</p>
                    </div>
                </div>

            </div>
 --}}

{{-- 3. المميزات (طلب العميل) --}}
{{-- <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
                <h2 class="text-md font-black mb-4 flex items-center gap-2 text-slate-800">
                    <span class="p-2 bg-slate-100 rounded-lg text-xs">✨</span> مميزات الرحلة وكوادرها
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-slate-50/50 p-4 rounded-2xl border border-dashed">
                    @php $features = ['يرافقك شيخ بالرحلة', 'يوجد كوادر بالرحلة', 'شاملة الوجبات', 'إنترنت مجاني', 'زيارة المشاعر', 'سكن قريب من الحرم']; @endphp
                    @foreach ($features as $feature)
                        <label
                            class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-100 cursor-pointer hover:border-blue-200 transition-all">
                            <input type="checkbox" name="features[]" value="{{ $feature }}"
                                class="rounded-md text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-bold text-slate-600">{{ $feature }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- 4. التسعير وتوزيع الغرف المطور (طلب العميل) --}}
{{-- <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
                <h2 class="text-md font-black mb-6 flex items-center gap-2 text-slate-800">
                    <span class="p-2 bg-slate-100 rounded-lg text-xs">🏨</span> توزيع السكن وأعداد الغرف
                </h2>
                <p class="text-[10px] text-gray-400 mb-6 uppercase font-bold tracking-widest">حدد السعر للشخص والعدد المتاح
                    من الغرف في الفندق</p>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach (['1' => 'فردية', '2' => 'ثنائية', '3' => 'ثلاثية', '4' => 'رباعية'] as $key => $label)
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <label
                                class="block text-[10px] font-black text-slate-500 mb-3 uppercase tracking-tighter text-center italic">غرفة
                                {{ $label }}</label>
                            <div class="space-y-3">
                                <div class="relative">
                                    <span class="absolute right-3 top-2 text-[10px] font-bold text-slate-400">السعر</span>
                                    <input type="number" name="price_{{ $key }}" placeholder="0.00"
                                        class="w-full pr-12 bg-white border-slate-200 rounded-xl font-bold text-blue-600 text-sm">
                                </div>
                                <div class="relative">
                                    <span class="absolute right-3 top-2 text-[10px] font-bold text-slate-400">العدد</span>
                                    <input type="number" name="rooms_count_{{ $key }}" placeholder="عدد الغرف"
                                        class="w-full pr-12 bg-white border-slate-200 rounded-xl font-bold text-slate-600 text-sm">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 5. بيانات البنك والآيبان (الوكيل ) --}}
{{-- <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
                <h2 class="text-md font-black mb-6 flex items-center gap-2 text-slate-800">
                    <span class="p-2 bg-slate-100 rounded-lg text-xs">💳</span> بيانات التحويل البنكي (للمسافرين)
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-2">اسم البنك</label>
                            <input type="text" name="bank_name" placeholder="مثلاً: مصرف الراجحي"
                                class="w-full bg-gray-50 border-gray-200 rounded-xl text-sm p-3">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-2">رقم الحساب / الايبان</label>
                            <input type="text" name="iban_number" placeholder="SA..."
                                class="w-full bg-gray-50 border-gray-200 rounded-xl text-sm p-3">
                        </div>
                    </div>
                </div>
            </div>  --}}

{{-- 6. تفاصيل المركبة والملاحظات --}}
{{-- <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100">
                <h2 class="text-md font-black mb-6 flex items-center gap-2 text-slate-800">
                    <span class="p-2 bg-slate-100 rounded-lg text-xs">🚌</span> تفاصيل المركبة والمقاعد
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-2">موديل الحافلة / شركة الطيران</label>
                        <select name="vehicle_model"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl text-sm p-3">
                            <option>مرسيدس VIP - 50 راكب</option>
                            <option>كيا مريحة - 50 راكب</option>
                            <option>طيران أديل</option>
                            <option>الخطوط السعودية</option>
                        </select>
                    </div>
                    <div class="flex items-start gap-3 p-4 bg-amber-50 rounded-2xl border border-amber-100">
                        <input type="checkbox" id="auto_split" name="auto_split"
                            class="mt-1 h-5 w-5 text-amber-600 border-amber-300 rounded focus:ring-amber-500">
                        <label for="auto_split" class="text-xs font-black text-amber-800 leading-tight">تفعيل الفصل
                            التلقائي<br><span class="font-normal text-amber-700">(نصف أمامي رجال / نصف خلفي
                                نساء)</span></label>
                    </div>
                </div>
                <div class="mt-6">
                    <label class="block text-xs font-black text-slate-500 mb-2 uppercase tracking-widest">ملاحظات إضافية
                        للمسافرين</label>
                    <textarea name="notes" rows="3" class="w-full border border-slate-200 rounded-[2rem] p-5 text-sm"
                        placeholder="اكتب شروط العمرة هنا..."></textarea>
                </div>
            </div>

            <div class="flex flex-col md:flex-row justify-end gap-4 pt-6">
                <button type="button"
                    class="px-10 py-4 bg-slate-100 text-slate-500 font-black rounded-[1.5rem] text-xs uppercase tracking-widest">حفظ
                    كمسودة</button>
                <button type="submit"
                    class="px-12 py-4 bg-blue-600 text-white font-black rounded-[1.5rem] shadow-xl text-xs uppercase tracking-widest">نشر
                    الرحلة للجمهور 🚀</button>
            </div>
        </form>
    </div>  --}}

{{-- <script>
        function toggleReturnFields(show) {
            const returnSection = document.getElementById('return_section');
            if (show) {
                returnSection.classList.remove('hidden');
                returnSection.classList.add('flex', 'flex-col');
            } else {
                returnSection.classList.add('hidden');
            }
        }
    </script> --}}
{{-- @endsection --}}



@extends('layouts.agency')

@section('content')
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        {{-- الهيدر --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h1 class="text-3xl font-black text-slate-800 flex items-center gap-3">
                    <span
                        class="w-12 h-12 bg-blue-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-blue-100">🚀</span>
                    إضافة رحلة جديدة
                </h1>
                <p class="text-sm text-slate-400 mt-2 font-medium">قم بتعبئة بيانات الرحلة وتسميتها لنشرها في "عبور"</p>
            </div>
            <div class="flex items-center gap-2 bg-white p-2 rounded-2xl border border-slate-100 shadow-sm">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Agency Mode</span>
            </div>
        </div>

        <form action="{{ route('agency.trips.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf

            {{-- القسم الأول: التسمية ونوع النقل --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    {{-- تسمية الرحلة ونظامها --}}
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label
                                    class="block text-[10px] font-black text-slate-400 mb-2 uppercase tracking-widest">تسمية
                                    الرحلة (العنوان التسويقي)</label>
                                <input type="text" name="title" placeholder="مثلاً: رحلة الأبرار لعيد الفطر"
                                    class="w-full bg-slate-50 border-none rounded-2xl text-lg font-black p-5 focus:ring-2 focus:ring-blue-600 transition-all shadow-inner">
                            </div>

                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-blue-800 uppercase tracking-widest">نظام
                                    الرحلة</label>
                                <div class="flex gap-2">
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="trip_type" value="one_way" checked class="peer hidden"
                                            onchange="toggleReturnFields(false)">
                                        <div
                                            class="p-3 text-center bg-slate-50 border border-slate-200 rounded-xl peer-checked:bg-blue-600 peer-checked:text-white transition-all text-xs font-bold">
                                            ذهاب فقط</div>
                                    </label>
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="trip_type" value="round_trip" class="peer hidden"
                                            onchange="toggleReturnFields(true)">
                                        <div
                                            class="p-3 text-center bg-slate-50 border border-slate-200 rounded-xl peer-checked:bg-blue-600 peer-checked:text-white transition-all text-xs font-bold">
                                            ذهاب وعودة</div>
                                    </label>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-indigo-800 uppercase tracking-widest">وسيلة
                                    النقل</label>
                                <div class="flex gap-2">
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="transport_type" value="bus" checked
                                            class="peer hidden" onchange="updateVehicleList('bus')">
                                        <div
                                            class="p-3 text-center bg-slate-50 border border-slate-200 rounded-xl peer-checked:bg-indigo-600 peer-checked:text-white transition-all text-xs font-bold">
                                            🚌 بري</div>
                                    </label>
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="transport_type" value="flight" class="peer hidden"
                                            onchange="updateVehicleList('flight')">
                                        <div
                                            class="p-3 text-center bg-slate-50 border border-slate-200 rounded-xl peer-checked:bg-indigo-600 peer-checked:text-white transition-all text-xs font-bold">
                                            ✈️ طيران</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- المسار والجدولة --}}
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 relative overflow-hidden">
                        <h2 class="text-md font-black mb-8 flex items-center gap-3 text-slate-800 relative">
                            <span class="w-8 h-8 bg-slate-100 rounded-xl flex items-center justify-center text-sm">📍</span>
                            المسار والجدولة الزمنية
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative">
                            <div class="space-y-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase">من
                                            (المدينة)</label>
                                        <select name="from_city"
                                            class="w-full bg-slate-50 border-none rounded-xl text-sm font-bold p-3">
                                            <option>الرياض</option>
                                            <option>جدة</option>
                                            <option>الدمام</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase">إلى
                                            (الوجهة)</label>
                                        <select name="to_city"
                                            class="w-full bg-slate-50 border-none rounded-xl text-sm font-bold p-3">
                                            <option>مكة المكرمة</option>
                                            <option>المدينة المنورة</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase">تاريخ
                                            الانطلاق</label>
                                        <input type="datetime-local" name="departure_at"
                                            class="w-full bg-slate-50 border-none rounded-xl text-sm font-bold p-3">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase">مدة
                                            الرحلة</label>
                                        <input type="number" name="duration_days" placeholder="أيام"
                                            class="w-full bg-slate-50 border-none rounded-xl text-sm font-bold p-3">
                                    </div>
                                </div>
                            </div>
                            <div id="return_section"
                                class="hidden animate-fade-in bg-blue-50/30 p-6 rounded-3xl border border-blue-100 border-dashed">
                                <label
                                    class="block text-[10px] font-black text-blue-500 mb-4 uppercase tracking-widest">تفاصيل
                                    العودة</label>
                                <input type="datetime-local" name="return_at"
                                    class="w-full bg-white border-none rounded-xl text-sm font-bold shadow-sm mb-3 p-3">
                                <p class="text-[10px] text-blue-400 italic">سيتم عرض رحلة العودة للمسافرين تلقائياً</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- صورة الغلاف --}}
                <div class="lg:col-span-1">
                    <div
                        class="w-full h-[250px] border-2 border-dashed border-slate-200 rounded-xl flex flex-col items-center justify-center cursor-pointer hover:bg-slate-50 hover:border-blue-200 transition-all group relative overflow-hidden">
                        <label
                            class="w-full h-full min-h-[250px] border-4 border-dashed border-slate-100 rounded-[2rem] flex flex-col items-center justify-center cursor-pointer hover:bg-slate-50 hover:border-blue-200 transition-all group relative overflow-hidden">
                            <input type="file" name="trip_image" class="hidden">
                            <span class="text-4xl mb-4 group-hover:scale-110 transition-transform">🖼️</span>
                            <span class="text-xs font-black text-slate-500 uppercase tracking-widest">صورة الغلاف
                                للرحلة</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- 2. المميزات --}}
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                <h2 class="text-md font-black mb-6 flex items-center gap-3 text-slate-800">
                    <span
                        class="w-8 h-8 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-sm">✨</span>
                    مميزات الرحلة وكوادرها
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    @php $features = ['يرافقك شيخ', 'يوجد كوادر بالرحلة', 'شاملة الوجبات', 'إنترنت مجاني', 'زيارة المشاعر', 'قريب من الحرم']; @endphp
                    @foreach ($features as $feature)
                        <label class="group relative cursor-pointer">
                            <input type="checkbox" name="features[]" value="{{ $feature }}" class="peer hidden">
                            <div
                                class="p-4 bg-slate-50 border border-slate-100 rounded-2xl text-center peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 transition-all">
                                <span class="text-[10px] font-black uppercase tracking-tighter">{{ $feature }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- 3. السكن والأسعار --}}
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                <h2 class="text-md font-black mb-8 flex items-center gap-3 text-slate-800">
                    <span
                        class="w-8 h-8 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-sm">🏨</span>
                    توزيع السكن والأسعار
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach (['1' => 'فردية', '2' => 'ثنائية', '3' => 'ثلاثية', '4' => 'رباعية'] as $key => $label)
                        <div class="bg-slate-50/50 p-6 rounded-3xl border border-slate-100 relative">
                            <div
                                class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-white border border-slate-100 rounded-full text-[10px] font-black text-slate-400">
                                {{ $label }}
                            </div>
                            <div class="space-y-4 pt-2">
                                <input type="number" name="price_{{ $key }}" placeholder="السعر"
                                    class="w-full bg-white border-none rounded-xl font-bold text-blue-600 text-sm shadow-sm p-2">
                                <input type="number" name="rooms_count_{{ $key }}" placeholder="عدد الغرف"
                                    class="w-full bg-white border-none rounded-xl font-bold text-slate-600 text-sm shadow-sm p-2">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 4. البنك والمركبة --}}
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 lg:col-span-2">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-md font-black flex items-center gap-3 text-slate-800">
                        <span class="w-8 h-8 bg-slate-100 rounded-xl flex items-center justify-center text-sm">💳</span>
                        بيانات التحويل البنكي
                    </h2>
                    <button type="button" onclick="addBankRow()"
                        class="text-[10px] font-black bg-blue-50 text-blue-600 px-4 py-2 rounded-xl hover:bg-blue-600 hover:text-white transition-all">
                        + إضافة حساب آخر
                    </button>
                </div>
                <div id="bank_accounts_container" class="space-y-4">
                    <div
                        class="bank-row grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-slate-50/50 rounded-2xl border border-slate-100 relative">
                        <input type="text" name="bank_names[]" placeholder="اسم البنك"
                            class="w-full bg-white border-none rounded-xl text-sm font-bold p-4 shadow-sm" required>
                        <input type="text" name="iban_numbers[]" placeholder="الايبان SA..."
                            class="w-full bg-white border-none rounded-xl text-sm font-bold p-4 shadow-sm" required>
                        <label
                            class="p-4 bg-white border border-dashed border-blue-200 rounded-xl text-center cursor-pointer hover:bg-blue-50 transition-all">
                            <span class="text-[9px] font-black text-blue-600">📎 صورة QR</span>
                            <input type="file" name="bank_images[]" class="hidden">
                        </label>
                    </div>
                </div>
            </div>

            {{-- المركبة --}}
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                <h2 class="text-md font-black mb-6 flex items-center gap-3 text-slate-800">
                    <span id="vehicle_icon"
                        class="w-8 h-8 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center text-sm transition-colors">🚌</span>
                    المركبة
                </h2>
                <div class="space-y-4">
                    <select name="vehicle_model" id="vehicle_select"
                        class="w-full bg-slate-50 border-none rounded-xl text-sm font-bold p-4">
                        <option value="مرسيدس VIP - 50 راكب">مرسيدس VIP - 50 راكب</option>
                        <option value="كيا مريحة - 50 راكب">كيا مريحة - 50 راكب</option>
                    </select>
                    <label
                        class="flex items-center gap-3 p-4 bg-amber-50 rounded-xl border border-amber-100 cursor-pointer">
                        <input type="checkbox" name="auto_split" class="rounded text-amber-600">
                        <span class="text-[10px] font-black text-amber-800 uppercase">فصل تلقائي (نساء / رجال)</span>
                    </label>
                </div>
            </div>

            {{-- الملاحظات --}}

            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">

                <textarea name="notes" rows="3"
                    class="w-full border-none bg-slate-50 rounded-[1.5rem] p-5 text-sm font-medium focus:ring-2 focus:ring-blue-500"
                    placeholder="اكتب شروط الرحلة أو ملاحظات إضافية هنا..."></textarea>

            </div>
            {{-- الأزرار --}}
            <div class="flex flex-col sm:flex-row justify-end gap-4 pt-6 pb-12">
                <button type="button"
                    class="px-12 py-5 bg-slate-100 text-slate-500 font-black rounded-2xl text-xs uppercase tracking-widest">حفظ
                    مسودة</button>
                <button type="submit"
                    class="px-16 py-5 bg-blue-600 text-white font-black rounded-3xl shadow-xl shadow-blue-200 text-xs uppercase tracking-widest hover:-translate-y-1 transition-all">نشر
                    الرحلة الآن 🚀</button>
            </div>
        </form>
    </div>

    <script>
        const vehicleData = {
            bus: ["مرسيدس VIP - 50 راكب", "كيا مريحة - 50 راكب", "حافلة نقل جماعي - 45 راكب"],
            flight: ["الخطوط السعودية (اقتصادية)", "أديل للطيران", "طيران ناس", "إيرباص A320"]
        };

        function updateVehicleList(type) {
            const select = document.getElementById('vehicle_select');
            const iconSpan = document.getElementById('vehicle_icon');
            select.innerHTML = '';
            vehicleData[type].forEach(v => {
                let opt = new Option(v, v);
                select.add(opt);
            });
            if (type === 'flight') {
                iconSpan.textContent = '✈️';
                iconSpan.className =
                    "w-8 h-8 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center text-sm transition-colors";
            } else {
                iconSpan.textContent = '🚌';
                iconSpan.className =
                    "w-8 h-8 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center text-sm transition-colors";
            }
        }

        function addBankRow() {
            const container = document.getElementById('bank_accounts_container');
            const row = document.createElement('div');
            row.className =
                "bank-row grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-slate-50/50 rounded-2xl border border-slate-100 relative animate-fade-in";
            row.innerHTML = `
            <input type="text" name="bank_names[]" placeholder="اسم البنك" class="w-full bg-white border-none rounded-xl text-sm font-bold p-4 shadow-sm" required>
            <input type="text" name="iban_numbers[]" placeholder="الايبان SA..." class="w-full bg-white border-none rounded-xl text-sm font-bold p-4 shadow-sm" required>
            <div class="flex gap-2">
                <label class="flex-1 p-4 bg-white border border-dashed border-blue-200 rounded-xl text-center cursor-pointer">
                    <span class="text-[9px] font-black text-blue-600">📎 صورة QR</span>
                    <input type="file" name="bank_images[]" class="hidden">
                </label>
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="w-12 bg-red-50 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-all">🗑️</button>
            </div>`;
            container.appendChild(row);
        }

        function toggleReturnFields(show) {
            document.getElementById('return_section').classList.toggle('hidden', !show);
        }
    </script>

    <style>
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
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
