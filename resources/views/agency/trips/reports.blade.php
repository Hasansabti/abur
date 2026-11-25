@extends('layouts.agency')

@section('content')
    @php
        $maskId = fn($v) => filled($v)
            ? \Illuminate\Support\Str::mask((string) $v, '*', 0, -4)
            : '---';
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @if(session('success') || session('error') || $errors->any())
<div x-data="{ open: true }" 
     x-show="open" 
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 transition-opacity">
    
    <!-- بطاقة التنبيه المنتصفية -->
    <div @click.away="open = false" 
         class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden transform transition-all text-center p-6 space-y-4 border border-gray-100">
        
        @if(session('success'))
            <!-- أيقونة النجاح -->
            <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-emerald-100 text-emerald-600">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-800">تمت العملية بنجاح</h3>
            <p class="text-gray-600 text-sm leading-relaxed">{{ session('success') }}</p>
        @endif

        @if(session('error'))
            <!-- أيقونة الخطأ -->
            <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-rose-100 text-rose-600">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-800">حدث خطأ</h3>
            <p class="text-gray-600 text-sm leading-relaxed">{{ session('error') }}</p>
        @endif

        @if($errors->any())
            <!-- أيقونة تنبيه التحقق -->
            <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-amber-100 text-amber-600">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-800">يرجى مراجعة البيانات</h3>
            <ul class="text-xs text-rose-600 text-right space-y-1 list-disc list-inside bg-rose-50 p-3 rounded-xl border border-rose-100">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <!-- زر الإغلاق -->
        <div class="pt-2">
            <button @click="open = false" 
                    type="button" 
                    class="w-full py-2.5 px-4 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-xl transition duration-150 shadow-sm text-sm">
                موافق
            </button>
        </div>

    </div>
</div>
@endif

    <div class="container mx-auto px-4 py-8 animate-fade-in" x-data="{ showExport: false }">

        {{-- ================= 1. رأس الصفحة وأدوات التحكم ================= --}}
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-10">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight">التقارير والكشوفات الحكومية
                    والأسطول</h1>
                <p class="text-slate-500 mt-2 font-medium italic">
                    منصة عبور: إصدار بيانات الركاب الأمني، تقارير الامتثال، إدارة أسطول المركبات والسائقين والكادر، ومؤشرات
                    الجودة.
                </p>
            </div>

            {{-- أدوات الإضافة والتصدير --}}
            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                {{-- زر إضافة فرد كادر جديد --}}
                <button type="button" onclick="openModal('create-staff-modal')"
                    class="bg-teal-600 hover:bg-teal-700 text-white font-bold px-5 py-3 rounded-2xl transition-all shadow-md flex items-center gap-2 text-xs">
                    <span>👨‍💼 إضافة فرد كادر جديد</span>
                </button>

                <div class="relative w-full sm:w-auto" @click.away="showExport = false">
                    <div x-show="showExport"
                        class="absolute left-0 mt-3 w-80 bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden z-50 animate-fade-in"
                        style="display: none;">
                        <form action="{{ url()->current() }}" method="GET">
                            <input type="hidden" name="range" value="{{ $range }}">

                            <button type="submit" name="export" value="manifest_pdf"
                                class="w-full text-right p-4 text-xs font-bold hover:bg-slate-50 flex items-center justify-between border-b border-slate-50 text-slate-700">
                                <span>📋 بيان الركاب الأمني (PDF)</span>
                                <span class="text-[9px] bg-red-50 text-red-600 px-2 py-0.5 rounded font-black">وزارة
                                    الداخلية</span>
                            </button>

                            <button type="submit" name="export" value="operation_excel"
                                class="w-full text-right p-4 text-xs font-bold hover:bg-slate-50 flex items-center justify-between border-b border-slate-50 text-slate-700">
                                <span>📊 كشف التشغيل والإشغال (Excel)</span>
                                <span class="text-[9px] bg-green-50 text-green-600 px-2 py-0.5 rounded font-black">هيئة
                                    النقل</span>
                            </button>

                            <button type="submit" name="export" value="vat_pdf"
                                class="w-full text-right p-4 text-xs font-bold hover:bg-slate-50 flex items-center justify-between text-slate-700">
                                <span>💰 الإقرار الضريبي والـ VAT (PDF)</span>
                                <span class="text-[9px] bg-amber-50 text-amber-600 px-2 py-0.5 rounded font-black">هيئة
                                    الزكاة</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= 2. الشبكة الإحصائية المحدثة (Stats Grid) ================= --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-4 mb-8">
            <div
                class="bg-white p-4 rounded-3xl border border-slate-100 flex flex-col justify-between shadow-sm hover:shadow-md transition-shadow">
                <div class="w-9 h-9 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-sm mb-3">💰
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider">صافي أرباح الوكالة</p>
                    <p class="text-sm font-black text-slate-800 mt-0.5">
                        {{ number_format($stats->net_revenue ?? 0, 2) }}
                        <small class="text-[9px]">ر.س</small>
                    </p>
                </div>
            </div>

            <div
                class="bg-white p-4 rounded-3xl border border-slate-100 flex flex-col justify-between shadow-sm hover:shadow-md transition-shadow">
                <div class="w-9 h-9 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-sm mb-3">🎫
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider">المقاعد المباعة</p>
                    <p class="text-sm font-black text-slate-800 mt-0.5">{{ $stats->total_bookings ?? 0 }} مقعد</p>
                </div>
            </div>

            <div
                class="bg-white p-4 rounded-3xl border border-slate-100 flex flex-col justify-between shadow-sm hover:shadow-md transition-shadow">
                <div class="w-9 h-9 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center text-sm mb-3">
                    ⚖️</div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider">قيد التدقيق</p>
                    <p class="text-sm font-black text-slate-800 mt-0.5">{{ number_format($stats->pending_amount ?? 0, 2) }}
                        <small class="text-[9px]">ر.س</small>
                    </p>
                </div>
            </div>

            <div
                class="bg-amber-50/50 p-4 rounded-3xl border border-amber-100 flex flex-col justify-between shadow-sm hover:shadow-md transition-shadow">
                <div class="w-9 h-9 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-sm mb-3">⭐
                </div>
                <div>
                    <p class="text-[9px] font-black text-amber-700 uppercase tracking-wider">رضا العملاء</p>
                    <p class="text-sm font-black text-amber-600 mt-0.5">{{ $stats->average_rating ?? '0.0' }} / 5.0</p>
                </div>
            </div>

            <div
                class="bg-white p-4 rounded-3xl border border-slate-100 flex flex-col justify-between shadow-sm hover:shadow-md transition-shadow">
                <div class="w-9 h-9 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-sm mb-3">
                    🚌</div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider">المركبات</p>
                    <p class="text-sm font-black text-slate-800 mt-0.5">{{ $vehicles->count() }} <span
                            class="text-[10px] text-emerald-600 font-bold">({{ $vehicles->where('status', 'active')->count() }}
                            نشطة)</span></p>
                </div>
            </div>

            <div
                class="bg-white p-4 rounded-3xl border border-slate-100 flex flex-col justify-between shadow-sm hover:shadow-md transition-shadow">
                <div class="w-9 h-9 bg-teal-50 text-teal-600 rounded-xl flex items-center justify-center text-sm mb-3">👨‍✈️
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider">السائقين والكادر</p>
                    <p class="text-sm font-black text-slate-800 mt-0.5">
                        {{ $drivers->count() + ($staffMembers->count() ?? 0) }}
                    </p>
                </div>
            </div>

            <div
                class="bg-white p-4 rounded-3xl border border-slate-100 flex flex-col justify-between shadow-sm hover:shadow-md transition-shadow col-span-2 sm:col-span-1">
                <div
                    class="w-9 h-9 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-sm mb-3">
                    🛡️</div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider">الامتثال الأمني</p>
                    <p class="text-sm font-black text-emerald-600 mt-0.5">%{{ $stats->compliance_rate ?? 100 }}</p>
                </div>
            </div>
        </div>

        {{-- ================= 3. الرسم البياني لنسبة الإشغال ================= --}}
        <div class="bg-white p-6 md:p-8 rounded-[2.5rem] border border-slate-100 shadow-sm mb-10">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <h3 class="font-black text-slate-800 flex items-center gap-3 text-base sm:text-lg">
                    📊 مؤشر كثافة الركاب وإشغال المقاعد الفعلي للرحلات
                    <span class="text-[10px] bg-slate-100 text-slate-500 px-3 py-1 rounded-full font-bold">تحليل بياني
                        مباشر</span>
                </h3>
            </div>
            <div class="relative w-full h-72">
                <canvas id="tripsOccupancyChart"></canvas>
            </div>
        </div>

        {{-- ================= 4. السجل الموحد لامتثال وتدقيق الرحلات ================= --}}
        {{-- ================= 4. السجل الموحد لامتثال وتدقيق الرحلات ================= --}}
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden mb-12"
            x-data="{ recordSearch: '' }">
            <div class="p-6 md:p-8 border-b border-slate-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-slate-50/30">
                <h3 class="font-black text-slate-800 flex items-center gap-2 text-base sm:text-lg">
                    📑 السجل الموحد لامتثال الرحلات والتحصيل والتقييمات
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[10px] rounded-lg font-bold">تحديث تشغيلي
                        فوري</span>
                </h3>

                {{-- حقل البحث عن رحلة --}}
                <div class="relative w-full md:w-72">
                    <input type="text" x-model="recordSearch"
                        placeholder="ابحث برقم الرحلة أو العنوان أو الوجهة..."
                        class="w-full bg-white border border-slate-200 rounded-xl py-2.5 px-3 pr-9 text-xs font-bold outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    <span class="absolute right-3 top-2.5 text-slate-400 text-xs">🔍</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse min-w-[900px]">
                    <thead>
                        <tr
                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest bg-slate-50/50 border-b border-slate-100">
                            <th class="p-6">رقم الرحلة المرجعي</th>
                            <th class="p-6">عنوان / وجهة الرحلة</th>
                            <th class="p-6">تاريخ الانطلاق</th>
                            <th class="p-6">إجمالي التحصيل</th>
                            <th class="p-6">تقييم المسافرين</th>
                            <th class="p-6">الامتثال الأمني</th>
                            <th class="p-6 text-left">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-xs">
                        @forelse($recent_transactions as $transaction)
                            <tr x-show="'{{ addslashes(strtolower('trp-' . $transaction['id'] . ' ' . ($transaction['type'] ?? ''))) }}'.includes(recordSearch.toLowerCase())"
                                class="group hover:bg-slate-50/80 transition-all">
                                <td class="p-6 font-mono font-black text-slate-400">#TRP-{{ $transaction['id'] }}</td>
                                <td class="p-6">
                                    <p class="font-black text-slate-800 text-sm mb-0.5">{{ $transaction['type'] }}</p>
                                    <p class="text-[10px] text-indigo-500 font-bold italic">نقل جماعي وسياحي معتمد</p>
                                </td>
                                <td class="p-6 font-bold text-slate-600">{{ $transaction['departure_at'] }}</td>
                                <td class="p-6 font-black text-slate-900 text-sm">
                                    {{ number_format($transaction['amount'], 2) }} ر.س</td>
                                <td class="p-6">
                                    @if (($transaction['ratings_count'] ?? 0) > 0)
                                        <div class="flex flex-col gap-0.5">
                                            <span class="font-black text-amber-500 flex items-center gap-1">
                                                ⭐ {{ $transaction['average_rating'] }} <small class="text-slate-400">/
                                                    5</small>
                                            </span>
                                            <span class="text-[10px] text-slate-400 font-medium">
                                                ({{ $transaction['ratings_count'] }} تقييم)
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-slate-300 font-bold italic">لا توجد تقييمات</span>
                                    @endif
                                </td>
                                <td class="p-6">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-600 rounded-xl text-[10px] font-black border border-emerald-100">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span> كشف
                                        جاهز ومعتمد
                                    </span>
                                </td>
                                <td class="p-6 text-left flex items-center justify-end gap-2">
                                    {{-- زر تعيين المركبة والسائق والكادر --}}
                                    <button type="button" onclick="openModal('assign-modal-{{ $transaction['id'] }}')"
                                        class="bg-teal-50 hover:bg-teal-600 hover:text-white border border-teal-200 text-teal-700 px-3 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-1">
                                        🚌 تخصيص الطاقم والمركبة
                                    </button>

                                    <button type="button" onclick="openModal('ratings-modal-{{ $transaction['id'] }}')"
                                        class="bg-amber-50 hover:bg-amber-500 hover:text-white border border-amber-200 text-amber-700 px-3 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-1">
                                        ⭐ التقييمات
                                    </button>
                                    <button type="button" onclick="openModal('manifest-modal-{{ $transaction['id'] }}')"
                                        class="bg-indigo-50 hover:bg-indigo-600 hover:text-white border border-indigo-100 text-indigo-700 px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-1">
                                        👁️ البيان الأمني
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-sm text-slate-400 font-medium">لا توجد
                                    رحلات مسجلة في السجل حالياً.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ================= 5. قسم إدارة أسطول المركبات والسائقين والكادر ================= --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">

            {{-- جدول المركبات --}}
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-50 flex justify-between items-center bg-slate-50/20">
                    <h3 class="font-black text-slate-800 flex items-center gap-2 text-base">
                        🚌 أسطول المركبات
                        <span
                            class="px-2.5 py-0.5 bg-indigo-50 text-indigo-600 text-[10px] rounded-lg font-bold">{{ $vehicles->count() }}
                            مركبة</span>
                    </h3>
                </div>
                <div class="overflow-x-auto max-h-96 overflow-y-auto">
                    <table class="w-full text-right border-collapse">
                        <thead>
                            <tr
                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest bg-slate-50/50 sticky top-0 bg-white">
                                <th class="p-4">نوع المركبة</th>
                                <th class="p-4">الموديل / اللوحة</th>
                                <th class="p-4">السعة</th>
                                <th class="p-4">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs">
                            @forelse($vehicles as $vehicle)
                                <tr class="hover:bg-slate-50/60 transition-all">
                                    <td class="p-4 font-black text-slate-800">{{ $vehicle->type }}</td>
                                    <td class="p-4">
                                        <p class="font-bold text-slate-700">{{ $vehicle->vehicle_model }}</p>
                                        <p class="font-mono text-[10px] text-slate-400 font-bold">
                                            {{ $vehicle->plate_number }}</p>
                                    </td>
                                    <td class="p-4 font-bold text-slate-600">{{ $vehicle->total_seats }} مقعد</td>
                                    <td class="p-4">
                                        <span
                                            class="px-2.5 py-1 rounded-lg text-[10px] font-black {{ $vehicle->status == 'active' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-red-50 text-red-600 border border-red-100' }}">
                                            {{ $vehicle->status == 'active' ? 'نشطة' : 'غير متاحة' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-6 text-center text-slate-400 font-medium">لم يتم تسجيل أي
                                        مركبات.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- جدول السائقين --}}
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-50 flex justify-between items-center bg-slate-50/20">
                    <h3 class="font-black text-slate-800 flex items-center gap-2 text-base">
                        👨‍✈️ كادر السائقين
                        <span
                            class="px-2.5 py-0.5 bg-teal-50 text-teal-600 text-[10px] rounded-lg font-bold">{{ $drivers->count() }}
                            سائق</span>
                    </h3>
                </div>
                <div class="overflow-x-auto max-h-96 overflow-y-auto">
                    <table class="w-full text-right border-collapse">
                        <thead>
                            <tr
                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest bg-slate-50/50 sticky top-0 bg-white">
                                <th class="p-4">اسم السائق</th>
                                <th class="p-4">رقم الجوال / الهوية</th>
                                <th class="p-4">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs">
                            @forelse($drivers as $driver)
                                <tr class="hover:bg-slate-50/60 transition-all">
                                    <td class="p-4 font-black text-slate-800">{{ $driver->name }}</td>
                                    <td class="p-4">
                                        <p class="font-mono text-slate-700 font-bold" dir="ltr">{{ $driver->phone }}
                                        </p>
                                        <p class="font-mono text-[10px] text-slate-400" dir="ltr">{{ $maskId($driver->national_id) }}</p>
                                    </td>
                                    <td class="p-4">
                                        <span
                                            class="px-2.5 py-1 rounded-lg text-[10px] font-black {{ $driver->status == 'active' ? 'bg-teal-50 text-teal-600 border border-teal-100' : 'bg-amber-50 text-amber-600 border border-amber-100' }}">
                                            {{ $driver->status == 'active' ? 'متاح' : 'غير متاح' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-6 text-center text-slate-400 font-medium">لم يتم تسجيل أي
                                        سائقين.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- جدول الكادر والإشراف (جديد) --}}
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-50 flex justify-between items-center bg-slate-50/20">
                    <h3 class="font-black text-slate-800 flex items-center gap-2 text-base">
                        👨‍💼 كادر الإشراف والمساعدين
                        <span
                            class="px-2.5 py-0.5 bg-amber-50 text-amber-600 text-[10px] rounded-lg font-bold">{{ isset($staffMembers) ? $staffMembers->count() : 0 }}
                            موظف</span>
                    </h3>
                </div>
                <div class="overflow-x-auto max-h-96 overflow-y-auto">
                    <table class="w-full text-right border-collapse">
                        <thead>
                            <tr
                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest bg-slate-50/50 sticky top-0 bg-white">
                                <th class="p-4">اسم الموظف</th>
                                <th class="p-4">الدور / الوظيفة</th>
                                <th class="p-4">الهوية / الجوال</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs">
                            @forelse($staffMembers ?? [] as $staff)
                                <tr class="hover:bg-slate-50/60 transition-all">
                                    <td class="p-4 font-black text-slate-800">{{ $staff->name }}</td>
                                    <td class="p-4">
                                        <span
                                            class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md text-[10px] font-bold">
                                            {{ $staff->role ?? 'مساعد' }}
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <p class="font-mono text-slate-700 font-bold" dir="ltr">{{ $staff->phone }}
                                        </p>
                                        <p class="font-mono text-[10px] text-slate-400" dir="ltr">{{ $maskId($staff->national_id) }}</p>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-6 text-center text-slate-400 font-medium">لم يتم تسجيل أي
                                        أفراد كادر بعد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    {{-- ================= 6. مودال التقرير الأمني (مع إخفاء الحجوزات locked) ================= --}}
    @foreach ($recent_transactions as $transaction)
        @php
            $trip = $transaction['raw_trip'] ?? null;
            $agency = auth('agency')->user() ?? (auth()->user()->agency ?? auth()->user());
            $isFlightTrip = ($trip->transport_type ?? '') === 'flight';

            // تجميع بيانات المقاعد (مع استبعاد حالة locked) ثم دمج المقاعد الإضافية مع صاحبها
            $rawSeatRows = [];
            if (isset($trip->bookings)) {
                foreach ($trip->bookings as $b) {
                    // استبعاد الحجوزات المقفولة locked
                    if (isset($b->status) && strtolower($b->status) === 'locked') {
                        continue;
                    }

                    if (isset($b->bookingSeats)) {
                        foreach ($b->bookingSeats as $s) {
                            // فحص حالة المقعد نفسه في حال كان للمقعد status مستقل
                            if (isset($s->status) && strtolower($s->status) === 'locked') {
                                continue;
                            }

                            $seatRaw = $s->seat_number ?? '';
                            $seatNum = is_array($seatRaw) ? implode('-', $seatRaw) : (string) $seatRaw;

                            if (!str_starts_with(strtoupper($seatNum), 'H')) {
                                $rawSeatRows[] = [
                                    'id' => $s->id ?? null,
                                    'booking_id' => $b->id ?? null,
                                    'parent_id' => $s->parent_seat_id ?? null,
                                    'is_extra' =>
                                        ($s->is_extra ?? 0) == 1 ||
                                        ($s->room_type ?? '') === 'additional' ||
                                        !empty($s->parent_seat_id),
                                    'seat_number' => $seatNum,
                                    'name' =>
                                        $s->passenger_name ?? ($s->passenger->name ?? ($b->customer_name ?? '---')),
                                    'identity' =>
                                        $s->passenger_national_id ??
                                        ($s->passenger->national_id ?? ($b->user->national_id ?? '---')),
                                    'gender' => $s->gender ?? ($s->passenger->gender ?? 'male'),
                                ];
                            }
                        }
                    }
                }
            }

            // المقاعد الرئيسية أولاً
            $passengers = [];
            $mainIndexById = [];
            foreach ($rawSeatRows as $row) {
                if (!$row['is_extra']) {
                    $row['seats'] = [$row['seat_number']];
                    $passengers[] = $row;
                    $mainIndexById[$row['id']] = array_key_last($passengers);
                }
            }

            // ثم ربط كل مقعد إضافي بصاحبه
            foreach ($rawSeatRows as $row) {
                if (!$row['is_extra']) {
                    continue;
                }

                $baseName = trim(str_replace('(مقعد إضافي)', '', (string) $row['name']));
                $idx = $mainIndexById[$row['parent_id']] ?? null;

                // احتياط: إذا لم يوجد parent_seat_id نطابق بالاسم داخل نفس الحجز
                if ($idx === null) {
                    foreach ($passengers as $i => $cand) {
                        if ($cand['booking_id'] === $row['booking_id'] && trim((string) $cand['name']) === $baseName) {
                            $idx = $i;
                            break;
                        }
                    }
                }

                if ($idx !== null) {
                    $passengers[$idx]['seats'][] = $row['seat_number'];
                } else {
                    // مقعد إضافي بلا صاحب ظاهر: يُعرض كسطر مستقل باسمه الأصلي
                    $row['name'] = $baseName;
                    $row['seats'] = [$row['seat_number']];
                    $passengers[] = $row;
                }
            }

            foreach ($passengers as &$pp) {
                sort($pp['seats'], SORT_NATURAL);
                $pp['seat_number'] = implode(' - ', $pp['seats']);
            }
            unset($pp);

            // عدد المقاعد المشغولة فعلياً (رئيسي + إضافي) لحساب المتبقي بدقة
            $occupiedSeats = count($rawSeatRows);

            $totalPassengers = count($passengers);
            $maleCount = 0;
            $femaleCount = 0;
            foreach ($passengers as $p) {
                if (in_array($p['gender'], ['female', 'أنثى', 'F'])) {
                    $femaleCount++;
                } else {
                    $maleCount++;
                }
            }

            $busCapacity = $trip->bus->capacity ?? ($trip->total_seats ?? $totalPassengers);
            $remainingSeats = max(0, $busCapacity - $occupiedSeats);
        @endphp

        <div id="manifest-modal-{{ $transaction['id'] }}"
            class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 text-right">
            <div
                class="bg-white w-full max-w-4xl rounded-[2.5rem] p-6 md:p-8 shadow-2xl overflow-y-auto max-h-[90vh] animate-fade-in">

                <div class="flex justify-between items-center border-b border-slate-100 pb-4 mb-6">
                    <div>
                        <h3 class="font-black text-slate-900 text-lg">📄 التقرير الأمني المعتمد للرحلة</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">تصدير رسمي مطابق لمتطلبات الجهات المختصة والهيئة
                            العامة للنقل</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="printManifestSection('print-area-{{ $transaction['id'] }}')"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-5 py-2.5 rounded-xl font-black transition-all flex items-center gap-2 shadow-md shadow-blue-100">
                            🖨️ طباعة / حفظ PDF
                        </button>
                        <button type="button" onclick="closeModal('manifest-modal-{{ $transaction['id'] }}')"
                            class="w-8 h-8 flex items-center justify-center bg-slate-100 rounded-full text-slate-500 hover:bg-red-50 hover:text-red-600 transition-all font-bold text-base">✕</button>
                    </div>
                </div>

                <div id="print-area-{{ $transaction['id'] }}">
                    <div
                        style="font-family: 'Cairo', sans-serif; border: 2px solid #e2e8f0; border-radius: 20px; padding: 20px; background: #fff; width: 100%; box-sizing: border-box;">

                        <div
                            style="display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #eff6ff; padding-bottom: 15px; margin-bottom: 18px;">
                            <div
                                style="width: 35%; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 10px; background: #fafafa;">
                                <div style="font-weight: 800; font-size: 11px; color: #1e3a8a;">🏢
                                    {{ $agency->name ?? 'وكالة سفر معتمدة' }}</div>
                                <div style="font-size: 8px; color: #64748b; margin-top: 3px; font-weight: bold;">السجل
                                    التجاري: <span
                                        style="font-family: monospace;">{{ $agency->commercial_id ?? '---' }}</span></div>
                                <div style="font-size: 8px; color: #64748b; font-weight: bold;">الهاتف: <span
                                        dir="ltr"
                                        style="font-family: monospace;">{{ $agency->phone ?? '---' }}</span></div>
                                <div style="font-size: 8px; color: #64748b; font-weight: bold;">الإيميل: <span
                                        style="font-family: monospace;">{{ $agency->email ?? '---' }}</span></div>
                            </div>

                            <div style="text-align: center; width: 30%;">
                                <h1 style="font-size: 17px; font-weight: 900; color: #1e3a8a; margin: 0;">التقرير الأمني
                                    للرحلة</h1>
                                <div
                                    style="font-size: 8.5px; font-weight: 800; color: #64748b; margin-top: 2px; text-transform: uppercase;">
                                    Passenger Security Manifest</div>
                                <p
                                    style="font-size: 8px; color: #2563eb; margin: 4px 0 0 0; font-weight: 800; background: #eff6ff; display: inline-block; padding: 2px 8px; border-radius: 12px;">
                                    تصدير رسمي آلي معتمد</p>
                            </div>

                            <div
                                style="width: 35%; text-align: left; display: flex; justify-content: flex-end; align-items: center;">
                                <img src="{{ asset('images/logo.jpeg') }}" alt="شعار عبور"
                                    style="height: 48px; width: auto; display: block;"
                                    onerror="this.style.display='none'">
                            </div>
                        </div>

                        <div style="margin-bottom: 18px;">
                            <div
                                style="display: flex; align-items: center; justify-content: center; gap: 6px; font-weight: 900; font-size: 11px; color: #1e3a8a; margin-bottom: 6px;">
                                <span>{{ $isFlightTrip ? '✈️' : '🗺️' }}</span> معلومات الرحلة وجدولة الحركة
                            </div>

                            <table
                                style="width: 100%; border-collapse: collapse; text-align: center; font-size: 10px; border: 1.5px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                                <thead>
                                    <tr
                                        style="background-color: #f8fafc; font-weight: 900; color: #475569; border-bottom: 1.5px solid #e2e8f0;">
                                        <th style="padding: 8px; border-left: 1.5px solid #e2e8f0;">تاريخ الرحلة</th>
                                        <th style="padding: 8px; border-left: 1.5px solid #e2e8f0;">وقت الانطلاق</th>
                                        <th style="padding: 8px; border-left: 1.5px solid #e2e8f0;">مدينة المغادرة</th>
                                        <th style="padding: 8px; border-left: 1.5px solid #e2e8f0;">مدينة الوصول</th>
                                        <th style="padding: 8px; width: 120px;">
                                            {{ $isFlightTrip ? 'رقم الرحلة' : 'رقم لوحة المركبة' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="font-weight: bold; color: #0f172a; background: #fff;">
                                        <td
                                            style="padding: 10px; border-left: 1.5px solid #e2e8f0; font-family: monospace;">
                                            {{ $trip->departure_at ? \Carbon\Carbon::parse($trip->departure_at)->format('Y-m-d') : '---' }}
                                        </td>
                                        <td
                                            style="padding: 10px; border-left: 1.5px solid #e2e8f0; font-family: monospace;">
                                            {{ $trip->departure_at ? \Carbon\Carbon::parse($trip->departure_at)->format('H:i') : '---' }}
                                        </td>
                                        <td
                                            style="padding: 10px; border-left: 1.5px solid #e2e8f0; color: #2563eb; font-weight: 900;">
                                            📍 {{ $trip->from_city ?? '---' }}</td>
                                        <td
                                            style="padding: 10px; border-left: 1.5px solid #e2e8f0; color: #10b981; font-weight: 900;">
                                            🏁 {{ $trip->to_city ?? '---' }}</td>
                                        <td
                                            style="padding: 4px; display: flex; justify-content: center; align-items: center;">
                                            <div
                                                style="border: 2px solid #1e293b; border-radius: 6px; padding: 2px 4px; width: 100px; text-align: center; background: #fff; box-sizing: border-box;">
                                                @if ($isFlightTrip)
                                                    <!-- حالة رحلة الطيران -->
                                                    <div
                                                        style="font-size: 8px; border-bottom: 1.5px solid #1e293b; padding-bottom: 1px; font-weight: 900; background: #f8fafc; color: #475569;">
                                                        FLIGHT NO
                                                    </div>
                                                    <div style="font-size: 11px; font-weight: 900; font-family: monospace; padding-top: 2px;"
                                                        dir="ltr">
                                                        {{ $trip->flight_number ?? '---' }}
                                                    </div>
                                                @else
                                                    <!-- حالة رحلة الحافلة / المركبة (شكل لوحة المبيعات) -->
                                                    <div
                                                        style="font-size: 9px; border-bottom: 1.5px solid #1e293b; padding-bottom: 1px; font-weight: 900; background: #f8fafc; color: #1e293b; letter-spacing: 2px;">
                                                        {{ $trip->vehicle->plate_letters ?? ($trip->plate_letters ?? 'اللوحة') }}
                                                    </div>
                                                    <div style="font-size: 11px; font-weight: 900; font-family: monospace; padding-top: 2px; display: flex; justify-content: center; align-items: center; gap: 4px;"
                                                        dir="ltr">
                                                        @if (!empty($trip->vehicle->plate_number))
                                                            {{-- في حال كانت اللوحة محفوظة كنص كامل في المركبة --}}
                                                            <span>{{ $trip->vehicle->plate_number }}</span>
                                                        @else
                                                            {{-- في حال كانت الحروف والأرقام منفصلة --}}
                                                            <span>{{ $trip->plate_numbers ?? '----' }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- جداول الكادر والتراخيص --}}
                        <div style="display: flex; gap: 12px; margin-bottom: 18px;">

                            <!-- قسم طاقم الملاحة والرحلة -->
                            <div style="width: 50%;">
                                <div
                                    style="font-weight: 900; font-size: 10.5px; color: #1e3a8a; margin-bottom: 4px; text-align: right;">
                                    👥 بيانات طاقم الملاحة والرحلة
                                </div>
                                <table
                                    style="width: 100%; border-collapse: collapse; text-align: center; font-size: 9px; border: 1.5px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                                    <thead>
                                        <tr
                                            style="background: #f8fafc; font-weight: 900; border-bottom: 1.5px solid #e2e8f0; color: #475569;">
                                            <th style="padding: 6px; border-left: 1.5px solid #e2e8f0; width: 25%;">الصفة
                                                الوظيفية</th>
                                            <th style="padding: 6px; border-left: 1.5px solid #e2e8f0; width: 30%;">الاسم
                                                الكامل</th>
                                            <th style="padding: 6px; border-left: 1.5px solid #e2e8f0; width: 22%;">رقم
                                                الهوية / الإقامة</th>
                                            <th style="padding: 6px; width: 23%;">رقم الجوال</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- 1. السائق الأول / الكابتن -->
                                        <tr>
                                            <td style="padding: 6px; border-left: 1.5px solid #e2e8f0; font-weight: 800;">
                                                {{ $isFlightTrip ? 'الكابتن / الطيار' : 'السائق الأول' }}
                                            </td>
                                            <td
                                                style="padding: 6px; border-left: 1.5px solid #e2e8f0; font-weight: bold; text-align: right; padding-right: 8px;">
                                                {{ $trip->driver->name ?? ($trip->driver_1_name ?? 'غير محدد') }}
                                            </td>
                                            <td
                                                style="padding: 6px; border-left: 1.5px solid #e2e8f0; font-family: monospace; font-weight: bold;">
                                                {{ $maskId($trip->driver->national_id ?? null) }}
                                            </td>
                                            <td style="padding: 6px; font-family: monospace; font-weight: bold;"
                                                dir="ltr">
                                                {{ $trip->driver->phone ?? '---' }}
                                            </td>
                                        </tr>

                                        <!-- 2. السائق الثاني / مساعد الطيار (يظهر إذا كان مسجلاً) -->
                                        @php
                                            $secondDriver = $trip->secondDriver ?? ($trip->driver2 ?? null);
                                        @endphp
                                        @if ($secondDriver || !empty($trip->driver_2_name) || !empty($trip->driver2_id) || !empty($trip->driver_2_id))
                                            <tr style="border-top: 1px solid #e2e8f0;">
                                                <td
                                                    style="padding: 6px; border-left: 1.5px solid #e2e8f0; font-weight: 800;">
                                                    {{ $isFlightTrip ? 'مساعد الطيار' : 'السائق الثاني' }}
                                                </td>
                                                <td
                                                    style="padding: 6px; border-left: 1.5px solid #e2e8f0; font-weight: bold; text-align: right; padding-right: 8px;">
                                                    {{ $secondDriver->name ?? ($trip->driver_2_name ?? 'غير محدد') }}
                                                </td>
                                                <td
                                                    style="padding: 6px; border-left: 1.5px solid #e2e8f0; font-family: monospace; font-weight: bold;">
                                                    {{ $maskId($secondDriver->national_id ?? null) }}
                                                </td>
                                                <td style="padding: 6px; font-family: monospace; font-weight: bold;"
                                                    dir="ltr">
                                                    {{ $secondDriver->phone ?? '---' }}
                                                </td>
                                            </tr>
                                        @endif

                                        <!-- 3. الكادر والمساعدون المسجلون (ديناميكي لعدة أفراد) -->
                                        @if (isset($trip->staff) && $trip->staff->count() > 0)
                                            @foreach ($trip->staff as $member)
                                                <tr style="border-top: 1px solid #e2e8f0;">
                                                    <td
                                                        style="padding: 6px; border-left: 1.5px solid #e2e8f0; font-weight: 800;">
                                                        {{ $member->role ?? ($isFlightTrip ? 'طاقم الضيافة' : 'مشرف / مساعد') }}
                                                    </td>
                                                    <td
                                                        style="padding: 6px; border-left: 1.5px solid #e2e8f0; font-weight: bold; text-align: right; padding-right: 8px;">
                                                        {{ $member->name }}
                                                    </td>
                                                    <td
                                                        style="padding: 6px; border-left: 1.5px solid #e2e8f0; font-family: monospace; font-weight: bold;">
                                                        {{ $maskId($member->national_id ?? null) }}
                                                    </td>
                                                    <td style="padding: 6px; font-family: monospace; font-weight: bold;"
                                                        dir="ltr">
                                                        {{ $member->phone ?? '---' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @elseif(!empty($trip->assistant) || !empty($trip->assistant_name))
                                            <!-- خيار احتياطي للمساعد القديم في حال عدم استخدام Pivot Table الكادر -->
                                            <tr style="border-top: 1px solid #e2e8f0;">
                                                <td
                                                    style="padding: 6px; border-left: 1.5px solid #e2e8f0; font-weight: 800;">
                                                    {{ $isFlightTrip ? 'طاقم الضيافة' : 'المساعد / الكادر' }}
                                                </td>
                                                <td
                                                    style="padding: 6px; border-left: 1.5px solid #e2e8f0; font-weight: bold; text-align: right; padding-right: 8px;">
                                                    {{ $trip->assistant->name ?? ($trip->assistant_name ?? 'غير محدد') }}
                                                </td>
                                                <td
                                                    style="padding: 6px; border-left: 1.5px solid #e2e8f0; font-family: monospace; font-weight: bold;">
                                                    {{ $maskId($trip->assistant->national_id ?? null) }}
                                                </td>
                                                <td style="padding: 6px; font-family: monospace; font-weight: bold;"
                                                    dir="ltr">
                                                    {{ $trip->assistant->phone ?? '---' }}
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <!-- قسم بيانات المصادقة والترخيص -->
                            <div style="width: 50%;">
                                <div
                                    style="font-weight: 900; font-size: 10.5px; color: #1e3a8a; margin-bottom: 4px; text-align: right;">
                                    🏢 بيانات المصادقة والترخيص
                                </div>
                                <table
                                    style="width: 100%; border-collapse: collapse; font-size: 9px; border: 1.5px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td
                                            style="padding: 6px; font-weight: 800; background: #f8fafc; border-left: 1.5px solid #e2e8f0; width: 35%; text-align: center;">
                                            الجهة المصدرة
                                        </td>
                                        <td
                                            style="padding: 6px; font-weight: bold; text-align: right; color: #1e3a8a; padding-right: 8px;">
                                            {{ $agency->name ?? '---' }}
                                        </td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td
                                            style="padding: 6px; font-weight: 800; background: #f8fafc; border-left: 1.5px solid #e2e8f0; text-align: center;">
                                            السجل التجاري
                                        </td>
                                        <td
                                            style="padding: 6px; font-family: monospace; font-weight: bold; text-align: right; padding-right: 8px;">
                                            {{ $agency->commercial_id ?? '---' }}
                                        </td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td
                                            style="padding: 6px; font-weight: 800; background: #f8fafc; border-left: 1.5px solid #e2e8f0; text-align: center;">
                                            هاتف التواصل
                                        </td>
                                        <td style="padding: 6px; font-family: monospace; font-weight: bold; text-align: right; padding-right: 8px;"
                                            dir="ltr">
                                            {{ $agency->phone ?? '---' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="padding: 6px; font-weight: 800; background: #f8fafc; border-left: 1.5px solid #e2e8f0; text-align: center;">
                                            البريد الإلكتروني
                                        </td>
                                        <td style="padding: 6px; font-family: monospace; font-weight: bold; text-align: right; padding-right: 8px;"
                                            dir="ltr">
                                            {{ $agency->email ?? '---' }}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        {{-- كشف المسافرين الفعليين --}}
                        <div>
                            <div
                                style="font-weight: 900; font-size: 10.5px; color: #1e3a8a; margin-bottom: 4px; text-align: right;">
                                📋 كشف تفاصيل ركاب الرحلة الحالية</div>
                            <table
                                style="width: 100%; border-collapse: collapse; font-size: 9.5px; border: 1.5px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                                <thead>
                                    <tr style="background: #1e3a8a; font-weight: 900; color: #ffffff;">
                                        <th
                                            style="padding: 8px; border-left: 1px solid rgba(255,255,255,0.15); width: 8%; text-align: center;">
                                            م</th>
                                        <th
                                            style="padding: 8px; border-left: 1px solid rgba(255,255,255,0.15); width: 12%; text-align: center;">
                                            رقم المقعد</th>
                                        <th
                                            style="padding: 8px; border-left: 1px solid rgba(255,255,255,0.15); width: 46%; text-align: right; padding-right: 12px;">
                                            الاسم الرباعي للمسافر</th>
                                        <th
                                            style="padding: 8px; border-left: 1px solid rgba(255,255,255,0.15); width: 20%; text-align: center;">
                                            رقم الهوية / الإقامة</th>
                                        <th style="padding: 8px; width: 14%; text-align: center;">الجنس</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($passengers as $idx => $p)
                                        <tr
                                            style="background: {{ $idx % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom: 1.5px solid #e2e8f0;">
                                            <td
                                                style="padding: 8px; border-left: 1px solid #e2e8f0; text-align: center; font-weight: bold; color: #475569;">
                                                {{ $idx + 1 }}</td>
                                            <td
                                                style="padding: 8px; border-left: 1px solid #e2e8f0; text-align: center; font-weight: 900; color: #2563eb;">
                                                {{ $p['seat_number'] }}</td>
                                            <td
                                                style="padding: 8px; border-left: 1px solid #e2e8f0; text-align: right; padding-right: 12px; font-weight: 800; color: #1e293b;">
                                                {{ $p['name'] }}</td>
                                            <td
                                                style="padding: 8px; border-left: 1px solid #e2e8f0; text-align: center; font-family: monospace; font-weight: bold; color: #334155;">
                                                <span dir="ltr">{{ $maskId($p['identity']) }}</span></td>
                                            <td style="padding: 8px; text-align: center; font-weight: bold;">
                                                {{ in_array($p['gender'], ['female', 'أنثى', 'F']) ? 'أنثى 👩' : 'ذكر 👨' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5"
                                                style="padding: 20px; text-align: center; color: #64748b; font-weight: bold;">
                                                لا يوجد ركاب مسجلين ومؤكدين في هذه الرحلة حتى الآن</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- مربعات الإحصائيات الفورية --}}
                        <div style="display: flex; gap: 12px; margin-top: 18px; margin-bottom: 15px;">
                            <div
                                style="flex: 1; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 10px 12px; display: flex; align-items: center; justify-content: space-between; background: #f8fafc;">
                                <div style="text-align: right;">
                                    <span
                                        style="font-size: 9px; font-weight: 800; color: #64748b; display: block; margin-bottom: 2px;">إجمالي
                                        الركاب</span>
                                    <span
                                        style="font-size: 14px; font-weight: 900; color: #1e3a8a;">{{ $totalPassengers }}
                                        مسافر</span>
                                </div>
                                <div
                                    style="width: 36px; height: 36px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; color: #2563eb;">
                                    🎫</div>
                            </div>

                            <div
                                style="flex: 1.2; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 10px 12px; display: flex; align-items: center; justify-content: space-between; background: #f8fafc;">
                                <div style="text-align: right; width: 100%;">
                                    <span
                                        style="font-size: 9px; font-weight: 800; color: #64748b; display: block; margin-bottom: 2px;">توزيع
                                        الركاب (جنس)</span>
                                    <div style="display: flex; gap: 8px; font-size: 11px; font-weight: 900;">
                                        <span style="color: #2563eb;">👨 ذكور: {{ $maleCount }}</span>
                                        <span style="color: #cbd5e1;">|</span>
                                        <span style="color: #ec4899;">👩 إناث: {{ $femaleCount }}</span>
                                    </div>
                                </div>
                                <div
                                    style="width: 36px; height: 36px; background: #fdf2f8; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                                    👥</div>
                            </div>

                            <div
                                style="flex: 1; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 10px 12px; display: flex; align-items: center; justify-content: space-between; background: #f8fafc;">
                                <div style="text-align: right;">
                                    <span
                                        style="font-size: 9px; font-weight: 800; color: #64748b; display: block; margin-bottom: 2px;">المقاعد
                                        المتبقية</span>
                                    <span style="font-size: 14px; font-weight: 900; color: #10b981;">{{ $remainingSeats }}
                                        مقعد</span>
                                </div>
                                <div
                                    style="width: 36px; height: 36px; background: #f0fdf4; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; color: #10b981;">
                                    🪑</div>
                            </div>
                        </div>

                        <div
                            style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center; border-top: 1.5px solid #f1f5f9; padding-top: 10px; font-size: 8px; color: #94a3b8; font-weight: bold;">
                            <span>تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</span>
                            <span>مستند رسمي مخصص لتقديم الكشف الأمني للرحلات عبر منصة عبور</span>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    @endforeach

    {{-- ================= مودال موحد لتخصيص وتحديث وتعديل (سائق + مركبة + كادر) ================= --}}
@foreach ($recent_transactions as $transaction)
    @php
        $trip = $transaction['raw_trip'] ?? null;
        $driver1 = $trip?->driver;
        $driver2 = $trip?->secondDriver ?? ($trip?->driver2 ?? null);
        $vehicle = $trip?->vehicle;
        $assignedStaff = $trip?->staff ?? collect();
        // جلب قائمة الكوادر المسجلة من أي متغير ممرر للملف
        $availableStaffList = $all_staff ?? $staff_members ?? $staff_list ?? [];
    @endphp

    <div id="assign-modal-{{ $transaction['id'] }}"
        class="hidden fixed inset-0 z-[150] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 text-right overflow-y-auto"
        x-data="{ newStaff: [] }">
        <div
            class="bg-white w-full max-w-3xl rounded-[2.5rem] p-6 md:p-8 shadow-2xl animate-fade-in my-8 max-h-[90vh] overflow-y-auto scrollbar-thin">

            <!-- Header -->
            <div
                class="flex justify-between items-center border-b border-slate-100 pb-4 mb-6 sticky top-0 bg-white z-10">
                <div>
                    <h3 class="font-black text-slate-900 text-lg flex items-center gap-2">
                        <span>🚌</span> تخصيص وتعديل موارد الرحلة
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        رحلة: {{ $transaction['type'] }} (#TRP-{{ $transaction['id'] }})
                    </p>
                </div>
                <button type="button" onclick="closeModal('assign-modal-{{ $transaction['id'] }}')"
                    class="w-8 h-8 flex items-center justify-center bg-slate-100 rounded-full text-slate-500 hover:bg-red-50 hover:text-red-600 transition-all font-bold text-base">✕</button>
            </div>

            @if ($trip)
                <form action="{{ route('agency.trips.assign-resources', $trip->id) }}" method="POST"
                    class="space-y-6">
                    @csrf

                    <!-- أخطاء التحقق -->
                    @if ($errors->any())
                        <div
                            class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-xl text-xs font-bold space-y-1">
                            <p>⚠️ تعذر الحفظ بسبب الأخطاء التالية:</p>
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- ================= 1. قسم السائق الأول ================= -->
                    <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200/80 space-y-3">
                        <div class="flex justify-between items-center">
                            <h4 class="font-black text-slate-800 text-xs flex items-center gap-1.5">
                                👨‍✈️ بيانات السائق الأول (الرئيسي)
                            </h4>
                            @if ($driver1)
                                <span
                                    class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-md font-bold">
                                    معيّن حالياً: {{ $driver1->name }}
                                </span>
                            @else
                                <span class="text-[10px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded-md font-bold">
                                    لم يتم تعيين سائق
                                </span>
                            @endif
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">اختر سائق أو إلغاء التعيين:</label>
                            <select name="driver_id"
                                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                <option value="">-- بدون سائق أول (إلغاء التعيين) --</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}"
                                        {{ $trip->driver_id == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->name }} ({{ $driver->phone }})
                                        {{ $driver->national_id ? '- ' . $maskId($driver->national_id) : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="border-t border-slate-200/80 pt-3">
                            <p class="text-[10px] font-bold text-teal-600 mb-2">
                                📝 تعديل بيانات السائق المحدد أو إضافة سائق جديد:
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                <input type="text" name="driver_name"
                                    value="{{ old('driver_name', $driver1?->name) }}" placeholder="اسم السائق"
                                    class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                <input type="text" name="driver_phone"
                                    value="{{ old('driver_phone', $driver1?->phone) }}" placeholder="رقم الجوال"
                                    dir="ltr"
                                    class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                <input type="text" name="driver_national_id"
                                    value="{{ old('driver_national_id', $driver1?->national_id) }}"
                                    placeholder="الهوية / الإقامة"
                                    class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                            </div>
                        </div>
                    </div>

                    <!-- ================= 2. قسم السائق الثاني ================= -->
                    <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200/80 space-y-3">
                        <div class="flex justify-between items-center">
                            <h4 class="font-black text-slate-800 text-xs flex items-center gap-1.5">
                                👨‍✈️ بيانات السائق الثاني / المساعد
                            </h4>
                            @if ($driver2)
                                <span
                                    class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-md font-bold">
                                    معيّن حالياً: {{ $driver2->name }}
                                </span>
                            @else
                                <span class="text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded-md font-bold">
                                    غير محدد
                                </span>
                            @endif
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">اختر السائق الثاني أو إلغاء التعيين:</label>
                            <select name="driver2_id"
                                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                <option value="">-- بدون سائق ثاني (إلغاء التعيين) --</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}"
                                        {{ ($trip->driver2_id ?? $trip->driver_2_id) == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->name }} ({{ $driver->phone }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="border-t border-slate-200/80 pt-3">
                            <p class="text-[10px] font-bold text-teal-600 mb-2">
                                📝 تعديل بيانات السائق الثاني الحالي أو إضافة جديد:
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                <input type="text" name="driver2_name"
                                    value="{{ old('driver2_name', $driver2?->name) }}"
                                    placeholder="اسم السائق الثاني"
                                    class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                <input type="text" name="driver2_phone"
                                    value="{{ old('driver2_phone', $driver2?->phone) }}" placeholder="رقم الجوال"
                                    dir="ltr"
                                    class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                <input type="text" name="driver2_national_id"
                                    value="{{ old('driver2_national_id', $driver2?->national_id) }}"
                                    placeholder="الهوية / الإقامة"
                                    class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                            </div>
                        </div>
                    </div>

                    <!-- ================= 3. قسم المركبة ================= -->
                    <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200/80 space-y-3">
                        <div class="flex justify-between items-center">
                            <h4 class="font-black text-slate-800 text-xs flex items-center gap-1.5">
                                🚌 بيانات المركبة / الحافلة
                            </h4>
                            @if ($vehicle)
                                <span
                                    class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-md font-bold">
                                    الحافلة الحالية: {{ $vehicle->plate_number }} ({{ $vehicle->model }})
                                </span>
                            @else
                                <span class="text-[10px] bg-amber-100 text-amber-800 px-2 py-0.5 rounded-md font-bold">
                                    لم تحدد حافلة
                                </span>
                            @endif
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">اختر الحافلة أو إزالة المركبة:</label>
                            <select name="vehicle_id"
                                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                <option value="">-- بدون مركبة (إلغاء التعيين) --</option>
                                @foreach ($vehicles as $v)
                                    <option value="{{ $v->id }}"
                                        {{ $trip->vehicle_id == $v->id ? 'selected' : '' }}>
                                        {{ $v->plate_number }} - {{ $v->model }} ({{ $v->total_seats }} مقعد)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="border-t border-slate-200/80 pt-3">
                            <p class="text-[10px] font-bold text-teal-600 mb-2">
                                📝 تعديل بيانات المركبة الحالية أو إضافة مركبة جديدة:
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                <input type="text" name="plate_number"
                                    value="{{ old('plate_number', $vehicle?->plate_number) }}"
                                    placeholder="رقم اللوحة (مثال: أ ب ج 1234)"
                                    class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                <input type="text" name="vehicle_model"
                                    value="{{ old('vehicle_model', $vehicle?->vehicle_model) }}"
                                    placeholder="موديل المركبة"
                                    class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                <input type="number" name="total_seats"
                                    value="{{ old('total_seats', $vehicle?->total_seats) }}"
                                    placeholder="عدد المقاعد"
                                    class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                            </div>
                        </div>
                    </div>

                    <!-- ================= 4. قسم الكادر والمساعدين ================= -->
                    <div class="bg-slate-50/80 p-4 rounded-2xl border border-slate-200/80 space-y-4">
                        <div class="flex justify-between items-center">
                            <h4 class="font-black text-slate-800 text-xs flex items-center gap-1.5">
                                👔 الكادر المرافق (مشرفين / مرشدين / ضيافة)
                            </h4>
                            <span class="text-[10px] bg-teal-100 text-teal-800 px-2.5 py-1 rounded-md font-bold">
                                العدد المعيّن: {{ $assignedStaff->count() }}
                            </span>
                        </div>

                        <!-- الكادر المعيّن حالياً مع خيار الإزالة -->
                        @if ($assignedStaff->count() > 0)
                            <div class="bg-white p-3 rounded-xl border border-slate-200 space-y-2">
                                <p class="text-[10px] font-bold text-slate-500">الكادر المعيّن حالياً (ألغِ التحديد لإزالة الفرد من الرحلة):</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @foreach ($assignedStaff as $st)
                                        <label
                                            class="flex items-center justify-between p-2 rounded-lg border border-slate-200 bg-slate-50 hover:bg-red-50/50 hover:border-red-200 transition-all cursor-pointer group">
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox" name="staff_ids[]"
                                                    value="{{ $st->id }}" checked
                                                    class="rounded text-teal-600 focus:ring-teal-500 h-4 w-4">
                                                <div>
                                                    <p class="text-xs font-black text-slate-800">{{ $st->name }}</p>
                                                    <p class="text-[10px] text-slate-400">
                                                        {{ $st->role ?? 'كادر' }} - {{ $st->phone }}
                                                        {{ $st->national_id ? ' | هوية: ' . $maskId($st->national_id) : '' }}
                                                    </p>
                                                </div>
                                            </div>
                                            <span
                                                class="text-[10px] font-bold text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">إزالة ✕</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- اختيار من الكوادر المسجلة مسبقاً في النظام -->
                        @if (count($availableStaffList) > 0)
                            <div class="bg-white p-3 rounded-xl border border-slate-200 space-y-2">
                                <label class="block text-[11px] font-bold text-slate-700">📋 اختر من الكادر المسجّل مسبقاً:</label>
                                <select name="select_staff_ids[]" multiple
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500 min-h-[90px]">
                                    @foreach ($availableStaffList as $st)
                                        @if (!$assignedStaff->contains('id', $st->id))
                                            <option value="{{ $st->id }}">
                                                {{ $st->name }} ({{ $st->role ?? 'كادر' }}) - {{ $st->phone }} {{ $st->national_id ? '- هوية: ' . $maskId($st->national_id) : '' }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                <p class="text-[10px] text-slate-400">💡 يمكنك اختيار أكثر من كادر عبر الضغط المستمر على زر Ctrl أو Cmd أثناء النقر.</p>
                            </div>
                        @endif

                        <!-- إضافة أفراد كادر جُدد بشكل ديناميكي (Alpine.js) -->
                        <div class="border-t border-slate-200/80 pt-3 space-y-3">
                            <div class="flex justify-between items-center">
                                <p class="text-[11px] font-black text-teal-700 flex items-center gap-1">
                                    <span>➕</span> إضافة أو تسجيل كادر جديد للرحلة
                                </p>
                                <button type="button" @click="newStaff.push({ name: '', phone: '', national_id: '', role: '' })"
                                    class="bg-teal-50 hover:bg-teal-100 text-teal-700 border border-teal-200 text-[11px] font-bold px-3 py-1 rounded-lg transition-all flex items-center gap-1 shadow-sm">
                                    <span>+</span> إضافة كادر آخر
                                </button>
                            </div>

                            <!-- نموذج إضافة كادر جديد مباشر -->
                            <div class="bg-white p-3 rounded-xl border border-slate-200 space-y-2">
                                <p class="text-[10px] font-bold text-slate-400">إضافة فرد كادر جديد (مباشر):</p>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                                    <input type="text" name="staff_name" placeholder="الاسم الرباعي"
                                        class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                    <input type="text" name="staff_phone" placeholder="رقم الجوال" dir="ltr"
                                        class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                    <input type="text" name="staff_national_id" placeholder="الهوية / الإقامة"
                                        class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                    <select name="staff_role"
                                        class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                        <option value="">المسمى الوظيفي</option>
                                        <option value="مشرف رحلة">مشرف رحلة</option>
                                        <option value="مرشد سياحي">مرشد سياحي</option>
                                        <option value="طاقم ضيافة">طاقم ضيافة</option>
                                        <option value="مساعد">مساعد</option>
                                    </select>
                                </div>
                            </div>

                            <!-- قائمة الكوادر الجديدة الإضافية الديناميكية -->
                            <template x-for="(staffItem, index) in newStaff" :key="index">
                                <div
                                    class="bg-teal-50/40 p-3 rounded-xl border border-teal-200/80 space-y-2 transition-all">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[10px] font-bold text-teal-800"
                                            x-text="`فرد كادر إضافي #${index + 1}`"></span>
                                        <button type="button" @click="newStaff.splice(index, 1)"
                                            class="text-red-500 hover:text-red-700 text-xs font-bold px-2 py-0.5 rounded bg-red-50 hover:bg-red-100 transition-all">
                                            حذف ✕
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                                        <input type="text" :name="`new_staff[${index}][name]`"
                                            x-model="staffItem.name" placeholder="الاسم الرباعي" required
                                            class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                        <input type="text" :name="`new_staff[${index}][phone]`"
                                            x-model="staffItem.phone" placeholder="رقم الجوال" dir="ltr"
                                            class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                        <input type="text" :name="`new_staff[${index}][national_id]`"
                                            x-model="staffItem.national_id" placeholder="الهوية / الإقامة"
                                            class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                        <select :name="`new_staff[${index}][role]`" x-model="staffItem.role"
                                            class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-teal-500">
                                            <option value="">المسمى الوظيفي</option>
                                            <option value="مشرف رحلة">مشرف رحلة</option>
                                            <option value="مرشد سياحي">مرشد سياحي</option>
                                            <option value="طاقم ضيافة">طاقم ضيافة</option>
                                            <option value="مساعد">مساعد</option>
                                        </select>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- الأزرار السفلية -->
                    <div class="pt-2 flex gap-3 sticky bottom-0 bg-white pb-2 border-t border-slate-100 mt-6">
                        <button type="submit"
                            class="flex-1 bg-teal-600 hover:bg-teal-700 text-white font-black py-3 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2">
                            <span>💾</span> حفظ وتحديث جميع البيانات
                        </button>
                        <button type="button" onclick="closeModal('assign-modal-{{ $transaction['id'] }}')"
                            class="px-5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl text-xs transition-all">
                            إلغاء
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endforeach

    {{-- =================  مودالات تقييمات المسافرين ================= --}}
    @foreach ($recent_transactions as $transaction)
        <div id="ratings-modal-{{ $transaction['id'] }}"
            class="hidden fixed inset-0 z-[150] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 text-right">
            <div
                class="bg-white w-full max-w-2xl rounded-[2.5rem] p-8 shadow-2xl overflow-y-auto max-h-[85vh] animate-fade-in">

                <div class="flex justify-between items-center border-b border-slate-100 pb-5 mb-5">
                    <div>
                        <h3 class="font-black text-slate-900 text-xl">⭐ تقييمات وملاحظات المسافرين</h3>
                        <p class="text-[10px] text-slate-400 mt-1">تفاصيل أداء الرحلة: {{ $transaction['type'] }}</p>
                    </div>
                    <button type="button" onclick="closeModal('ratings-modal-{{ $transaction['id'] }}')"
                        class="w-8 h-8 flex items-center justify-center bg-slate-50 rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600 transition-all font-bold text-base">✕</button>
                </div>

                <div class="bg-amber-50/40 border border-amber-100 rounded-2xl p-4 mb-6 flex justify-between items-center">
                    <div>
                        <p class="text-xs font-black text-amber-800">متوسط التقييم العام للرحلة</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">محسوب بناءً على المعايير المقدمة من الركاب</p>
                    </div>
                    <div class="text-left">
                        <span class="text-2xl font-black text-amber-600">{{ $transaction['average_rating'] }}</span>
                        <span class="text-xs text-slate-400">/ 5.0</span>
                    </div>
                </div>

                <div class="space-y-4">
                    @forelse($transaction['ratings'] as $rating)
                        <div class="bg-slate-50/80 p-5 rounded-2xl border border-slate-100">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-9 h-9 bg-slate-200 text-slate-600 rounded-full flex items-center justify-center font-bold text-sm">
                                        👤</div>
                                    <div>
                                        <h4 class="text-xs font-black text-slate-800">
                                            {{ $rating->user->name ?? 'مسافر عابر' }}</h4>
                                        <p class="text-[9px] text-slate-400 font-bold mt-0.5">
                                            {{ $rating->created_at ? $rating->created_at->format('Y-m-d H:i') : '' }}</p>
                                    </div>
                                </div>
                                @php
                                    $scores = array_filter(
                                        [
                                            $rating->transport_rating,
                                            $rating->staff_rating,
                                            $rating->organization_rating,
                                            $rating->price_value_rating,
                                            $rating->services_rating,
                                            $rating->accommodation_rating,
                                            $rating->meals_rating,
                                        ],
                                        fn($val) => !is_null($val) && $val > 0,
                                    );
                                    $userAvg = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
                                @endphp

                                <div
                                    class="bg-white border border-slate-200 px-2.5 py-1 rounded-xl text-xs font-black text-amber-500">
                                    ⭐ {{ number_format($userAvg, 1) }}
                                </div>
                            </div>

                            @if ($rating->comment)
                                <p
                                    class="text-xs text-slate-600 bg-white p-3 rounded-xl border border-slate-100 leading-relaxed font-medium">
                                    <span class="text-slate-400 text-xs">💬</span> "{{ $rating->comment }}"
                                </p>
                            @else
                                <p class="text-[11px] text-slate-400 italic">اكتفى العميل بالتقييم النجمي دون كتابة تعليق.
                                </p>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-10 text-slate-400 font-medium text-xs">
                            لم يقم أي مسافر بتقديم تقييم تفصيلي لهذه الرحلة بعد.
                        </div>
                    @endforelse
                </div>

            </div>
        </div>
    @endforeach

    {{-- ================= 10. JavaScript Functions ================= --}}
    <script>
        // دالة عامة لفتح أي مودال بـ ID
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.remove('hidden');
        }

        // دالة عامة لإغلاق أي مودال بـ ID
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('hidden');
        }

        // طباعة الكشف الأمني
        function printManifestSection(areaId) {
            const printContent = document.getElementById(areaId).innerHTML;
            const printWindow = window.open('', '_blank', 'width=1000,height=800');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html dir="rtl" lang="ar">
                <head>
                    <meta charset="UTF-8">
                    <title>طباعة الكشف الأمني للرحلة</title>
                    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
                    <style>
                        body { margin: 0; padding: 20px; background: #fff; font-family: 'Cairo', sans-serif; }
                        @media print {
                            body { padding: 0; }
                        }
                    </style>
                </head>
                <body>
                    ${printContent}
                    <script>
                        window.onload = function() {
                            window.print();
                            window.close();
                        };
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }

        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('tripsOccupancyChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($chartLabels ?? []) !!},
                    datasets: [{
                            label: 'المقاعد المحجوزة فعلياً',
                            data: {!! json_encode($chartSoldData ?? []) !!},
                            backgroundColor: '#4f46e5',
                            borderRadius: 12,
                            borderSkipped: false,
                        },
                        {
                            label: 'السعة الإجمالية للحافلة',
                            data: {!! json_encode($chartTotalData ?? []) !!},
                            backgroundColor: '#f1f5f9',
                            borderRadius: 12,
                            borderSkipped: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    family: 'Cairo, sans-serif',
                                    weight: 'bold'
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            stacked: false,
                            grid: {
                                color: '#f8fafc'
                            }
                        }
                    }
                }
            });
        });
    </script>

    <style>
        .animate-fade-in {
            animation: fadeIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endsection