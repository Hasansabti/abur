@extends('layouts.admin')

@section('content')
    <div class="container mx-auto px-4 py-8" x-data="{ activeTab: 'pending' }">

        <!-- هيدر ترحيبي علوي -->
        <div
            class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4 border-b border-slate-100 pb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                    <span class="text-blue-600">📊</span> لوحة الإشراف والمتابعة
                </h1>
                <p class="text-sm text-slate-500 font-semibold mt-1">مرحباً بك مجدداً! إليك حالة حركة الحجوزات والوكالات في
                    منصة عُبور اليوم.</p>
            </div>
            <div class="flex items-center gap-3">
                <span
                    class="text-xs bg-green-50 text-green-700 px-4 py-2 rounded-xl font-bold flex items-center gap-2 border border-green-100 shadow-sm">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> النظام مستقر
                </span>
                <span
                    class="text-xs bg-white text-slate-500 px-4 py-2 rounded-xl font-bold border border-slate-200/60 shadow-sm">
                    📅 {{ now()->format('Y-m-d') }}
                </span>
            </div>
        </div>

        <!-- كروت الإحصائيات العلوية المستندة بالكامل على بياناتك الفعلية -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">

            <!-- كارت الوكالات النشطة -->
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-all duration-300">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider">الوكالات المعتمدة</p>
                        <p class="text-3xl font-black text-slate-800 mt-2">{{ $activeAgenciesCount }}</p>
                    </div>
                    <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl font-bold text-xl">🏢</div>
                </div>
                <div class="mt-4 flex items-center gap-1.5 text-xs font-bold text-slate-500">
                    <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></span>
                    <span>شركاء نجاح منصة عبور</span>
                </div>
            </div>

            <!-- كارت الرحلات المعتمدة -->
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-all duration-300">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider">الرحلات النشطة حالياً</p>
                        <p class="text-3xl font-black text-slate-800 mt-2">{{ $activeTripsCount }}</p>
                    </div>
                    <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl font-bold text-xl">🚍</div>
                </div>
                <div class="mt-4 flex items-center gap-1.5 text-xs font-bold text-emerald-600">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                    <span>متاحة للحجز المباشر الآن</span>
                </div>
            </div>

            <!-- كارت الإجراءات المعلقة -->
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                @if ($pendingTripsCount + $pendingAgenciesCount > 0)
                    <div class="absolute right-0 top-0 h-full w-1.5 bg-amber-500"></div>
                @endif
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider">طلبات بانتظار المراجعة</p>
                        <p class="text-3xl font-black text-slate-800 mt-2">{{ $pendingTripsCount + $pendingAgenciesCount }}
                        </p>
                    </div>
                    <div class="p-3 bg-amber-50 text-amber-600 rounded-xl font-bold text-xl">⏳</div>
                </div>
                <div class="mt-4 flex items-center justify-between text-xs font-bold text-slate-500">
                    <span>رحلات: {{ $pendingTripsCount }}</span>
                    <span class="text-slate-300">•</span>
                    <span>وكالات: {{ $pendingAgenciesCount }}</span>
                </div>
            </div>

            <!-- كارت المتصلين الآن -->
            <div
                class="bg-slate-900 p-6 rounded-2xl shadow-lg text-white relative overflow-hidden group hover:bg-slate-850 transition-all duration-300">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-blue-600/10 rounded-full blur-xl"></div>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider"> المستخدمين</p>
                        <p class="text-3xl font-black mt-2 tracking-tight">{{ number_format($onlineUsersCount) }}</p>
                    </div>
                    <div class="p-3 bg-white/10 text-emerald-400 rounded-xl text-xl animate-pulse">🟢</div>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-white/5 pt-3">
                    <div class="flex -space-x-2 space-x-reverse">
                        <div class="w-6 h-6 rounded-full border-2 border-slate-900 bg-blue-500"></div>
                        <div class="w-6 h-6 rounded-full border-2 border-slate-900 bg-purple-500"></div>
                    </div>
                    <span class="text-[11px] font-bold text-slate-400">مراقبة حية للنشاط</span>
                </div>
            </div>
        </div>

        <!-- الجزء الرئيسي للوحة التحكم  -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            {{-- الجزء الأيمن (8 أعمدة): طلبات الاعتماد وإدارة المحتوى والـ Logs --}}
            <div class="lg:col-span-8 space-y-8">

                {{-- أزرار التبديل  باستخدام --}}
                <div class="bg-white p-2 rounded-xl border border-slate-100 shadow-sm flex flex-wrap gap-2">
                    <button @click="activeTab = 'pending'"
                        :class="activeTab === 'pending' ? 'bg-blue-600 text-white shadow-md' :
                            'text-slate-600 hover:bg-slate-50'"
                        class="flex-1 py-3 px-4 rounded-lg font-bold text-sm transition-all duration-200 relative flex items-center justify-center gap-2">
                        <span>🚌 رحلات معلقة</span>
                        @if ($pendingTripsCount > 0)
                            <span
                                class="bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-full font-black animate-bounce">
                                {{ $pendingTripsCount }}
                            </span>
                        @endif
                    </button>

                    <button @click="activeTab = 'agencies'"
                        :class="activeTab === 'agencies' ? 'bg-blue-600 text-white shadow-md' :
                            'text-slate-600 hover:bg-slate-50'"
                        class="flex-1 py-3 px-4 rounded-lg font-bold text-sm transition-all duration-200 relative flex items-center justify-center gap-2">
                        <span>🏢 طلبات الوكالات</span>
                        @if ($pendingAgenciesCount > 0)
                            <span class="bg-blue-500 text-white text-[10px] px-2 py-0.5 rounded-full font-black">
                                {{ $pendingAgenciesCount }}
                            </span>
                        @endif
                    </button>
                    <!-- زر تبويب توثيق هويات المستخدمين -->
                    <button @click="activeTab = 'users'"
                        :class="activeTab === 'users' ? 'bg-blue-600 text-white shadow-md' :
                            'text-slate-600 hover:bg-slate-50'"
                        class="flex-1 py-3 px-4 rounded-lg font-bold text-sm transition-all duration-200 relative flex items-center justify-center gap-2">
                        <span>🪪 توثيق الهويات</span>
                        @php
                            $pendingUsersCount = isset($pendingUsers)
                                ? $pendingUsers->count()
                                : (isset($users)
                                    ? $users->where('is_verified', 3)->count()
                                    : 0);
                        @endphp
                        @if ($pendingUsersCount > 0)
                            <span
                                class="bg-amber-500 text-white text-[10px] px-2 py-0.5 rounded-full font-black animate-pulse">
                                {{ $pendingUsersCount }}
                            </span>
                        @endif
                    </button>

                    <!-- زر تبويب سجلات الإزاحة  -->
                    {{-- <button @click="activeTab = 'logs'"
                        :class="activeTab === 'logs' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-50'"
                        class="flex-1 py-3 px-4 rounded-lg font-bold text-sm transition-all duration-200 relative flex items-center justify-center gap-2">
                        <span>🔄 سجلات إزاحة المقاعد</span>
                    </button> --}}
                </div>

                {{-- محتوى تبويب: رحلات بانتظار الاعتماد --}}
                <div x-show="activeTab === 'pending'" class="space-y-4 animate-fade-in">
                    @forelse($pendingTrips as $trip)
                        <div
                            class="bg-white rounded-2xl p-6 border border-slate-100 hover:border-blue-100 hover:shadow-md transition-all flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                            <div class="flex items-start gap-4">
                                <div
                                    class="w-14 h-14 bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200/60 rounded-xl flex items-center justify-center text-2xl shadow-sm">
                                    {{ $trip->transport_type == 'flight' ? '✈️' : '🚍' }}
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span
                                            class="w-8 h-8 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-sm">
                                            🏢
                                        </span>
                                        <h4 class="font-black text-slate-800 text-base">
                                            مالك الوكالة:
                                            <span
                                                class="text-blue-600">{{ $trip->agency->name ?? 'وكالة غير معروفة' }}</span>
                                        </h4>
                                    </div>
                                    <p class="text-sm font-bold text-slate-700">{{ $trip->title }}</p>

                                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 pt-1">
                                        <span
                                            class="bg-slate-100 px-2 py-0.5 rounded text-slate-700">{{ $trip->from_city }}</span>
                                        <span class="text-slate-300">⬅️</span>
                                        <span
                                            class="bg-slate-100 px-2 py-0.5 rounded text-slate-700">{{ $trip->to_city }}</span>
                                    </div>

                                    <div class="flex items-center gap-4 mt-3 flex-wrap">
                                        <span
                                            class="text-xs font-bold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100/50">
                                            {{ number_format($trip->price) }} ر.س
                                        </span>
                                        <span class="text-xs font-bold text-slate-500 flex items-center gap-1">
                                            📅 {{ \Carbon\Carbon::parse($trip->date)->format('Y-m-d') }}
                                        </span>
                                        <span
                                            class="text-xs font-bold text-purple-600 bg-purple-50 px-2.5 py-1 rounded-lg border border-purple-100/50">
                                            💺 إجمالي المقاعد: {{ $trip->total_seats }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-2 w-full md:w-auto border-t md:border-t-0 pt-4 md:pt-0">

                                <a href="{{ route('admin.trips.approveTrip', ['id' => $trip->id]) }}"
                                    class="text-center flex-1 md:flex-none px-6 py-2.5 bg-blue-600 text-white rounded-xl text-xs font-bold shadow-sm hover:bg-blue-700 transition-all">
                                    اعتماد ونشر
                                </a>
                            </div>
                        </div>
                    @empty
                        <div
                            class="text-center py-16 bg-white rounded-2xl border border-slate-100 text-slate-400 font-bold text-sm shadow-sm">
                            <p class="text-3xl mb-2">🎉</p>
                            لا توجد أي رحلات معلقة بانتظار الاعتماد حالياً.
                        </div>
                    @endforelse
                </div>

                {{-- محتوى تبويب: طلبات الوكالات --}}
                <div x-show="activeTab === 'agencies'" class="space-y-4 animate-fade-in" x-cloak>
                    @forelse($pendingAgencies as $agency)
                        <div
                            class="bg-white rounded-2xl p-6 border border-slate-100 hover:border-indigo-100 hover:shadow-md transition-all flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-2xl font-bold shadow-sm border border-indigo-100/60">
                                    🏢
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-black text-slate-800 text-base">{{ $agency->name }}</h4>
                                    <p class="text-xs font-bold text-slate-500 bg-slate-100 w-fit px-2 py-0.5 rounded">
                                        السجل التجاري: {{ $agency->commercial_id ?? 'غير متوفر' }}
                                    </p>
                                    <p class="text-xs text-slate-400 font-semibold flex items-center gap-1 pt-1">
                                        ⏱️ تقديم الطلب: {{ $agency->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-2 w-full md:w-auto border-t md:border-t-0 pt-4 md:pt-0">
                                <form action="{{ route('admin.agencies.approve', $agency->id) }}" method="POST"
                                    class="w-full md:w-auto">
                                    @csrf
                                    <button type="submit"
                                        class="text-center w-full md:w-auto px-6 py-2.5 bg-green-600 text-white rounded-xl text-xs font-bold shadow-sm hover:bg-green-700 transition-all flex items-center justify-center gap-1">
                                        ✅ تفعيل الحساب
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div
                            class="text-center py-16 bg-white rounded-2xl border border-slate-100 text-slate-400 font-bold text-sm shadow-sm">
                            <p class="text-3xl mb-2">🤝</p>
                            لا توجد طلبات انضمام جديدة معلقة من وكالات السفر.
                        </div>
                    @endforelse
                </div>
                {{-- محتوى تبويب: طلبات توثيق هويات المستخدمين (is_verified = 3) --}}
                <div x-show="activeTab === 'users'" class="space-y-4 animate-fade-in" x-cloak>
                    @php
                        $usersToVerify = isset($pendingUsers)
                            ? $pendingUsers
                            : (isset($users)
                                ? $users->where('is_verified', 3)
                                : []);
                    @endphp

                    @forelse($usersToVerify as $user)
                        <div
                            class="bg-white rounded-2xl p-6 border border-slate-100 hover:border-amber-100 hover:shadow-md transition-all flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-14 h-14 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-2xl font-bold shadow-sm border border-amber-100/60">
                                    🪪
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-black text-slate-800 text-base">{{ $user->name }}</h4>
                                        <span
                                            class="bg-amber-100 text-amber-700 text-[10px] font-extrabold px-2 py-0.5 rounded-full">
                                            بانتظار الاعتماد
                                        </span>
                                    </div>
                                    <p class="text-xs font-bold text-slate-500 bg-slate-100 w-fit px-2 py-0.5 rounded">
                                        رقم الهوية: {{ $user->national_id ?? ($user->id_number ?? 'غير متوفر') }}
                                    </p>
                                    @if (isset($user->email))
                                        <p class="text-xs text-slate-400 font-semibold">
                                            البريد: {{ $user->email }} | الهاتف: {{ $user->phone ?? 'غير مدخل' }}
                                        </p>
                                    @endif
                                    @if (isset($user->created_at))
                                        <p class="text-[11px] text-slate-400 font-medium">
                                            ⏱️ تاريخ الطلب: {{ $user->created_at->diffForHumans() }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex gap-2 w-full md:w-auto border-t md:border-t-0 pt-4 md:pt-0">
                                <!-- زر رفض الهوية -->
                                <form action="{{ route('admin.users.reject', $user->id) }}" method="POST"
                                    class="w-full md:w-auto">
                                    @csrf
                                    <button type="submit"
                                        class="w-full md:w-auto px-5 py-2.5 bg-slate-50 text-slate-500 border border-slate-200 rounded-xl text-xs font-bold hover:bg-red-50 hover:text-red-600 hover:border-red-100 transition-all">
                                        رفض الطلب
                                    </button>
                                </form>
                                <!-- زر اعتماد الهوية -->
                                <form action="{{ route('admin.users.verify', $user->id) }}" method="POST"
                                    class="w-full md:w-auto">
                                    @csrf
                                    <button type="submit"
                                        class="w-full md:w-auto px-6 py-2.5 bg-green-600 text-white rounded-xl text-xs font-bold shadow-sm hover:bg-green-700 transition-all flex items-center justify-center gap-1">
                                        ✅ اعتماد الهوية
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div
                            class="text-center py-16 bg-white rounded-2xl border border-slate-100 text-slate-400 font-bold text-sm shadow-sm">
                            <p class="text-3xl mb-2">✨</p>
                            لا توجد أي طلبات معلقة لتوثيق هويات المستخدمين حالياً.
                        </div>
                    @endforelse
                </div>

                {{-- 🔥 محتوى تبويب سجلات إزاحة المقاعد التلقائية الجديد 🔥 --}}
                {{-- <div x-show="activeTab === 'logs'" class="space-y-4 animate-fade-in" x-cloak>
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                            <h3 class="font-black text-slate-800 text-base flex items-center gap-2">
                                🔄 مراقبة عمليات الإزاحة الذكية
                            </h3>
                            <p class="text-xs text-slate-400 font-semibold mt-1">يعرض هذا الجدول تحركات المقاعد التلقائية التي تمت عبر نظام التقسيم الديناميكي لمنع تضارب الفئات داخل الحافلات.</p>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-right border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-400 text-xs font-bold border-b border-slate-100">
                                        <th class="p-4">الرحلة</th>
                                        <th class="p-4">اسم الراكب</th>
                                        <th class="p-4 text-center">المقعد القديم</th>
                                        <th class="p-4 text-center">المقعد الجديد</th>
                                        <th class="p-4">سبب الإزاحة الآلية</th>
                                        <th class="p-4">التوقيت</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm font-bold text-slate-700 divide-y divide-slate-100">
                                    @forelse($seatLogs ?? [] as $log)
                                        <tr class="hover:bg-slate-50/80 transition-all">
                                            <td class="p-4">
                                                <span class="text-xs text-slate-400 block font-normal">#{{ $log->trip_id }}</span>
                                                <span class="text-slate-800">{{ $log->trip->title ?? 'رحلة ملغاة' }}</span>
                                            </td>
                                            <td class="p-4 text-slate-900">{{ $log->passenger_name }}</td>
                                            <td class="p-4 text-center">
                                                <span class="inline-block px-2.5 py-1 bg-red-50 text-red-600 border border-red-100 rounded-lg text-xs">
                                                    💺 {{ $log->old_seat }}
                                                </span>
                                            </td>
                                            <td class="p-4 text-center">
                                                <span class="inline-block px-2.5 py-1 bg-green-50 text-green-600 border border-green-100 rounded-lg text-xs">
                                                    💺 {{ $log->new_seat }}
                                                </span>
                                            </td>
                                            <td class="p-4 text-xs text-slate-500 font-medium max-w-xs truncate" title="{{ $log->reason }}">
                                                {{ $log->reason }}
                                            </td>
                                            <td class="p-4 text-xs text-slate-400 font-semibold">
                                                {{ $log->created_at->diffForHumans() }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-16 text-slate-400">
                                                <p class="text-3xl mb-2">🍃</p>
                                                لم تحدث أي عمليات إزاحة أو تعديل مقاعد تلقائية في النظام حتى الآن.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div> --}}
            </div>

            {{-- الجزء الأيسر (4 أعمدة): ملخص الحالة التشغيلية للمنصة --}}
            <div class="lg:col-span-4 space-y-6">
                <!-- كارت الحالة والنسب التشغيلية الفورية -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm">
                    <h3
                        class="text-base font-black text-slate-800 mb-4 flex items-center gap-2 border-b border-slate-50 pb-3">
                        <span class="text-blue-600">📊</span> التوازن التشغيلي للمنصة
                    </h3>

                    <div class="space-y-4">
                        <!-- مؤشر الوكالات -->
                        <div>
                            <div class="flex justify-between items-center text-xs font-bold text-slate-600 mb-1.5">
                                <span>الوكالات المعتمدة مقابل المعلقة</span>
                                <span class="text-slate-400">{{ $activeAgenciesCount }} نشط / {{ $pendingAgenciesCount }}
                                    معلق</span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden flex">
                                @php
                                    $totalAgencies = $activeAgenciesCount + $pendingAgenciesCount ?: 1;
                                    $activeAgencyPercent = ($activeAgenciesCount / $totalAgencies) * 100;
                                @endphp
                                <div class="bg-indigo-600 h-full" style="width: {{ $activeAgencyPercent }}%"></div>
                            </div>
                        </div>

                        <!-- مؤشر الرحلات -->
                        <div>
                            <div class="flex justify-between items-center text-xs font-bold text-slate-600 mb-1.5">
                                <span>الرحلات المنشورة مقابل المنتظرة</span>
                                <span class="text-slate-400">{{ $activeTripsCount }} نشط / {{ $pendingTripsCount }}
                                    معلق</span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden flex">
                                @php
                                    $totalTrips = $activeTripsCount + $pendingTripsCount ?: 1;
                                    $activeTripPercent = ($activeTripsCount / $totalTrips) * 100;
                                @endphp
                                <div class="bg-emerald-500 h-full" style="width: {{ $activeTripPercent }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- سجل عمليات ومزامنة أوتوماتيكية مبسطة -->
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                    <h3
                        class="text-xs font-black text-slate-400 uppercase mb-4 tracking-wider border-b border-slate-50 pb-2">
                        ⏱️ مؤشرات سلامة البيانات</h3>
                    <div class="space-y-4">
                        <div class="flex gap-3 items-start">
                            <div class="w-1.5 h-8 bg-blue-500 rounded-full mt-0.5"></div>
                            <div>
                                <p class="text-xs font-bold text-slate-700">مزامنة جداول المقاعد المتاحة والرحلات</p>
                                <p class="text-[10px] text-slate-400 font-semibold mt-0.5">منذ ثوانٍ • نظام أوتوماتيكي</p>
                            </div>
                        </div>
                        <div class="flex gap-3 items-start">
                            <div class="w-1.5 h-8 bg-emerald-500 rounded-full mt-0.5"></div>
                            <div>
                                <p class="text-xs font-bold text-slate-700">تحديث مؤشر المتصلين الفوري من الجلسات</p>
                                <p class="text-[10px] text-slate-400 font-semibold mt-0.5">تحديث مستمر</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- أنيميشن وإصلاح لـ x-cloak الخاص بـ AlpineJS -->
    <style>
        [x-cloak] {
            display: none !important;
        }

        .animate-fade-in {
            animation: fadeIn 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endsection
