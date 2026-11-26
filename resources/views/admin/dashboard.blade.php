@extends('layouts.admin')

@section('content')
    @php
        // ===== دوال مساعدة للعرض =====
        $val = fn($v) => filled($v) ? $v : '—';

        $fmtDate = fn($d) => blank($d) ? null : \Carbon\Carbon::parse($d)->format('Y-m-d');

        $resolveStorage = function ($path) {
            if (!$path) {
                return null;
            }
            if (str_starts_with($path, 'http')) {
                return $path;
            }
            if (str_starts_with($path, 'storage/')) {
                return asset($path);
            }
            return asset('storage/' . ltrim($path, '/'));
        };

        $resolvePublic = function ($path) {
            if (!$path) {
                return null;
            }
            if (str_starts_with($path, 'http')) {
                return $path;
            }
            return asset(ltrim($path, '/'));
        };

        $isPdf = fn($p) => $p &&
            strtolower(pathinfo(parse_url($p, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)) === 'pdf';

        $genderLabel = fn($g) => $g === 'male' ? 'ذكر' : ($g === 'female' ? 'أنثى' : null);

        $crStatusLabel = fn($s) => match ($s) {
            'pending' => 'قيد المراجعة',
            'approved' => 'معتمد',
            'rejected' => 'مرفوض',
            default => null,
        };

        // صندوق معلومات صغير (الاسم + القيمة)
        $field = function ($label, $value) use ($val) {
            return '<div class="bg-slate-50 rounded-xl p-3 border border-slate-100">'
                . '<p class="text-[10px] font-bold text-slate-400 mb-0.5">' . e($label) . '</p>'
                . '<p dir="auto" class="text-sm font-black text-slate-800 break-words">' . e($val($value)) . '</p>'
                . '</div>';
        };

        // ===== بيانات صندوق الإشعارات =====
        $actionCount = $systemNotifications->where('level', 'action')->count();
        $warningCount = $systemNotifications->where('level', 'warning')->count();

        $levelStyles = [
            'action' => ['bar' => 'bg-amber-500', 'chip' => 'bg-amber-50 text-amber-700', 'label' => 'يتطلب إجراء'],
            'warning' => ['bar' => 'bg-red-500', 'chip' => 'bg-red-50 text-red-700', 'label' => 'تنبيه'],
            'info' => ['bar' => 'bg-blue-500', 'chip' => 'bg-blue-50 text-blue-700', 'label' => 'معلومة'],
        ];
    @endphp

    <div class="container mx-auto px-4 py-8"
        x-data="{
            activeTab: '{{ session('active_tab', 'pending') }}',
            reviewAgency: null,
            reviewUser: null,
            imagePreview: null,
            notifFilter: 'all',
            dismissed: []
        }"
        x-effect="document.body.style.overflow = (reviewAgency || reviewUser || imagePreview) ? 'hidden' : ''"
        @keydown.escape.window="if (imagePreview) { imagePreview = null } else { reviewAgency = null; reviewUser = null }">

        {{-- رسائل النجاح والخطأ (حذفها إذا كان الـ layout يعرضها مسبقاً) --}}
        @if (session('success') || session('error'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
                class="fixed top-5 left-1/2 -translate-x-1/2 z-[80] max-w-md w-[90%] px-5 py-3 rounded-2xl shadow-xl text-sm font-bold text-center
                    {{ session('success') ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white' }}">
                {{ session('success') ?? session('error') }}
            </div>
        @endif

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

        <!-- كروت الإحصائيات العلوية -->
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

            <!-- كارت المستخدمين -->
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

        <!-- الجزء الرئيسي للوحة التحكم -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            {{-- الجزء الأيمن (8 أعمدة): طلبات الاعتماد --}}
            <div class="lg:col-span-8 space-y-8">

                {{-- أزرار التبديل --}}
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

                    <button @click="activeTab = 'users'"
                        :class="activeTab === 'users' ? 'bg-blue-600 text-white shadow-md' :
                            'text-slate-600 hover:bg-slate-50'"
                        class="flex-1 py-3 px-4 rounded-lg font-bold text-sm transition-all duration-200 relative flex items-center justify-center gap-2">
                        <span>🪪 توثيق الهويات</span>
                        @if ($pendingUsers->count() > 0)
                            <span
                                class="bg-amber-500 text-white text-[10px] px-2 py-0.5 rounded-full font-black animate-pulse">
                                {{ $pendingUsers->count() }}
                            </span>
                        @endif
                    </button>
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
                                    class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-2xl font-bold shadow-sm border border-indigo-100/60 shrink-0">
                                    🏢
                                </div>
                                <div class="space-y-1">
                                    <h4 class="font-black text-slate-800 text-base">{{ $agency->name }}</h4>
                                    @if (filled($agency->commercial_name))
                                        <p class="text-xs font-bold text-slate-600">{{ $agency->commercial_name }}</p>
                                    @endif
                                    <p class="text-xs font-bold text-slate-500 bg-slate-100 w-fit px-2 py-0.5 rounded">
                                        السجل التجاري: {{ $agency->commercial_id ?? 'غير متوفر' }}
                                    </p>
                                    <p dir="auto" class="text-xs text-slate-400 font-semibold">
                                        {{ $agency->email }} | {{ $agency->phone ?? 'غير مدخل' }}
                                    </p>
                                    <p class="text-xs text-slate-400 font-semibold flex items-center gap-1 pt-1">
                                        ⏱️ تقديم الطلب: {{ $agency->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-2 w-full md:w-auto border-t md:border-t-0 pt-4 md:pt-0">
                                <button type="button" @click="reviewAgency = {{ $agency->id }}"
                                    class="text-center w-full md:w-auto px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-xs font-bold shadow-sm hover:bg-indigo-700 transition-all flex items-center justify-center gap-1">
                                    🔍 مراجعة البيانات والتوثيق
                                </button>
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
                    @forelse($pendingUsers as $user)
                        <div
                            class="bg-white rounded-2xl p-6 border border-slate-100 hover:border-amber-100 hover:shadow-md transition-all flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-14 h-14 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-2xl font-bold shadow-sm border border-amber-100/60 shrink-0">
                                    🪪
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="font-black text-slate-800 text-base">{{ $user->name }}</h4>
                                        <span
                                            class="bg-amber-100 text-amber-700 text-[10px] font-extrabold px-2 py-0.5 rounded-full">
                                            بانتظار الاعتماد
                                        </span>
                                        <span
                                            class="text-[10px] font-extrabold px-2 py-0.5 rounded-full {{ filled($user->id_image) ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                            {{ filled($user->id_image) ? '✅ صورة الهوية مرفوعة' : '⚠️ بدون صورة هوية' }}
                                        </span>
                                    </div>
                                    <p class="text-xs font-bold text-slate-500 bg-slate-100 w-fit px-2 py-0.5 rounded">
                                        رقم الهوية: {{ $user->national_id ?? 'غير متوفر' }}
                                    </p>
                                    <p dir="auto" class="text-xs text-slate-400 font-semibold">
                                        {{ $user->email }} | {{ $user->phone ?? 'غير مدخل' }}
                                    </p>
                                    <p class="text-[11px] text-slate-400 font-medium">
                                        ⏱️ تاريخ الطلب: {{ $user->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-2 w-full md:w-auto border-t md:border-t-0 pt-4 md:pt-0">
                                <button type="button" @click="reviewUser = {{ $user->id }}"
                                    class="text-center w-full md:w-auto px-6 py-2.5 bg-amber-500 text-white rounded-xl text-xs font-bold shadow-sm hover:bg-amber-600 transition-all flex items-center justify-center gap-1">
                                    🔍 مراجعة البيانات والتوثيق
                                </button>
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
            </div>

            {{-- الجزء الأيسر (4 أعمدة) --}}
            <div class="lg:col-span-4 space-y-6">

                <!-- 🔔 صندوق إشعارات النظام -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between gap-2">
                        <h3 class="text-base font-black text-slate-800 flex items-center gap-2">
                            <span>🔔</span> إشعارات النظام
                        </h3>
                        @if ($actionCount > 0)
                            <span class="text-[10px] font-black bg-amber-500 text-white px-2.5 py-1 rounded-full">
                                {{ $actionCount }} تحتاج إجراء
                            </span>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-2 px-5 py-3 border-b border-slate-100 text-[11px] font-bold">
                        <button type="button" @click="notifFilter = 'all'"
                            :class="notifFilter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-500'"
                            class="px-3 py-1 rounded-full transition">
                            الكل ({{ $systemNotifications->count() }})
                        </button>
                        <button type="button" @click="notifFilter = 'action'"
                            :class="notifFilter === 'action' ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-500'"
                            class="px-3 py-1 rounded-full transition">
                            إجراءات ({{ $actionCount }})
                        </button>
                        <button type="button" @click="notifFilter = 'warning'"
                            :class="notifFilter === 'warning' ? 'bg-red-500 text-white' : 'bg-slate-100 text-slate-500'"
                            class="px-3 py-1 rounded-full transition">
                            تنبيهات ({{ $warningCount }})
                        </button>
                    </div>

                    <div class="max-h-[440px] overflow-y-auto divide-y divide-slate-100">
                        @forelse ($systemNotifications as $n)
                            @php
                                $style = $levelStyles[$n['level']] ?? $levelStyles['info'];
                                $click = match ($n['kind'] ?? null) {
                                    'agency' => "activeTab = 'agencies'; reviewAgency = {$n['ref']}",
                                    'user' => "activeTab = 'users'; reviewUser = {$n['ref']}",
                                    'trip' => "activeTab = 'pending'",
                                    default => '',
                                };
                            @endphp
                            <div x-show="!dismissed.includes('{{ $n['key'] }}') && (notifFilter === 'all' || notifFilter === '{{ $n['level'] }}')"
                                class="flex gap-3 p-4 hover:bg-slate-50/70 transition-colors">
                                <div class="w-1 rounded-full shrink-0 {{ $style['bar'] }}"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-xs font-black text-slate-800">{{ $n['icon'] }} {{ $n['title'] }}</p>
                                        <span
                                            class="text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0 {{ $style['chip'] }}">{{ $style['label'] }}</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 font-semibold mt-1 leading-relaxed">
                                        {{ $n['message'] }}</p>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-[10px] text-slate-400 font-semibold">
                                            {{ $n['time'] ? $n['time']->diffForHumans() : 'الآن' }}
                                        </span>
                                        <div class="flex items-center gap-3">
                                            @if ($click)
                                                <button type="button" @click="{{ $click }}"
                                                    class="text-[11px] font-bold text-blue-600 hover:text-blue-800">
                                                    مراجعة ←
                                                </button>
                                            @endif
                                            @if ($n['level'] !== 'action')
                                                <button type="button" @click="dismissed.push('{{ $n['key'] }}')"
                                                    class="text-[11px] font-bold text-slate-400 hover:text-slate-600">
                                                    إخفاء
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12 text-slate-400 font-bold text-sm">
                                <p class="text-3xl mb-2">🔕</p>
                                لا توجد إشعارات جديدة حالياً.
                            </div>
                        @endforelse
                    </div>
                </div>

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

                <!-- مؤشرات سلامة البيانات -->
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

        {{-- ==================== نوافذ مراجعة الوكالات ==================== --}}
        @foreach ($pendingAgencies as $agency)
            @php
                $crExpiry = filled($agency->cr_expiry_date) ? \Carbon\Carbon::parse($agency->cr_expiry_date) : null;
                $crDays = $crExpiry
                    ? (int) now()->startOfDay()->diffInDays($crExpiry->copy()->startOfDay(), false)
                    : null;

                $crDoc = filled($agency->cr_document_path ?? null)
                    ? $resolveStorage($agency->cr_document_path)
                    : (filled($agency->commercial_image ?? null)
                        ? $resolvePublic($agency->commercial_image)
                        : null);

                $agencyChecks = [
                    'رقم السجل التجاري' => filled($agency->commercial_id),
                    'وثيقة السجل التجاري' => (bool) $crDoc,
                    'سجل تجاري ساري' => $crDays !== null && $crDays >= 0,
                    'رقم الآيبان' => filled($agency->ibanA),
                    'رقم الجوال' => filled($agency->phone),
                ];
            @endphp

            <div x-show="reviewAgency === {{ $agency->id }}" x-cloak x-transition.opacity
                class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-sm"
                @click.self="reviewAgency = null" role="dialog" aria-modal="true">
                <div
                    class="bg-white w-full max-w-3xl max-h-[90vh] rounded-3xl shadow-2xl flex flex-col overflow-hidden border border-slate-100">

                    {{-- رأس النافذة --}}
                    <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/80 shrink-0">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-2xl border border-indigo-100/60">
                                🏢</div>
                            <div>
                                <h3 class="text-base font-black text-slate-900">{{ $agency->name }}</h3>
                                <p class="text-[11px] font-semibold text-slate-400">
                                    طلب انضمام • {{ $agency->created_at->format('Y-m-d H:i') }}
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="reviewAgency = null"
                            class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- محتوى النافذة --}}
                    <div class="p-6 space-y-6 overflow-y-auto flex-1">

                        {{-- قائمة التحقق --}}
                        <div>
                            <p class="text-xs font-black text-slate-500 mb-2">فحص سريع للبيانات</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($agencyChecks as $label => $ok)
                                    <span
                                        class="text-[11px] font-bold px-2.5 py-1 rounded-full border {{ $ok ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-amber-50 text-amber-700 border-amber-100' }}">
                                        {{ $ok ? '✅' : '⚠️' }} {{ $label }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        {{-- بيانات الوكالة --}}
                        <div>
                            <p class="text-xs font-black text-slate-500 mb-2">بيانات الوكالة</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                {!! $field('اسم الوكالة', $agency->name) !!}
                                {!! $field('الاسم التجاري', $agency->commercial_name) !!}
                                {!! $field('البريد الإلكتروني', $agency->email) !!}
                                {!! $field('رقم الجوال', $agency->phone) !!}
                                {!! $field('رقم الآيبان (IBAN)', $agency->ibanA) !!}
                                {!! $field('عدد الرحلات المسجلة', $agency->trips_count) !!}
                            </div>
                        </div>

                        {{-- السجل التجاري --}}
                        <div>
                            <p class="text-xs font-black text-slate-500 mb-2">السجل التجاري</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                {!! $field('رقم السجل التجاري', $agency->commercial_id) !!}
                                {!! $field('تاريخ انتهاء السجل', $fmtDate($agency->cr_expiry_date)) !!}
                                {!! $field('حالة السجل', $crStatusLabel($agency->cr_status)) !!}
                                <div
                                    class="rounded-xl p-3 border flex items-center
                                    {{ $crDays === null ? 'bg-slate-50 border-slate-100 text-slate-500' : ($crDays < 0 ? 'bg-rose-50 border-rose-100 text-rose-700' : ($crDays <= 30 ? 'bg-amber-50 border-amber-100 text-amber-700' : 'bg-emerald-50 border-emerald-100 text-emerald-700')) }}">
                                    <p class="text-sm font-black">
                                        @if ($crDays === null)
                                            تاريخ الانتهاء غير محدد
                                        @elseif ($crDays < 0)
                                            ⛔ منتهي منذ {{ abs($crDays) }} يوم
                                        @elseif ($crDays <= 30)
                                            ⏳ ينتهي خلال {{ $crDays }} يوم
                                        @else
                                            ✅ ساري ({{ $crDays }} يوم متبقٍ)
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3">
                                <p class="text-[10px] font-bold text-slate-400 mb-1">وثيقة السجل التجاري</p>
                                @if ($crDoc)
                                    @if ($isPdf($crDoc))
                                        <a href="{{ $crDoc }}" target="_blank" rel="noopener"
                                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 rounded-xl text-xs font-bold text-slate-700 transition">
                                            📄 فتح الوثيقة (PDF)
                                        </a>
                                    @else
                                        <button type="button" @click="imagePreview = @js($crDoc)"
                                            class="group relative block w-full sm:w-72">
                                            <img src="{{ $crDoc }}" alt="وثيقة السجل التجاري"
                                                class="w-full h-44 object-contain bg-slate-50 rounded-xl border border-slate-200">
                                            <span
                                                class="absolute inset-0 flex items-center justify-center bg-slate-900/0 group-hover:bg-slate-900/40 text-white text-xs font-bold opacity-0 group-hover:opacity-100 rounded-xl transition">
                                                🔍 تكبير الصورة
                                            </span>
                                        </button>
                                    @endif
                                @else
                                    <p class="text-xs font-bold text-amber-600 bg-amber-50 border border-amber-100 rounded-xl px-3 py-2 w-fit">
                                        ⚠️ لم يتم رفع وثيقة السجل التجاري
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- العمولات --}}
                        <div>
                            <p class="text-xs font-black text-slate-500 mb-2">إعدادات العمولة</p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                {!! $field('عمولة المسافر الواحد', filled($agency->commission_per_passenger) ? $agency->commission_per_passenger . ' ر.س' : null) !!}
                                {!! $field('الحد الأدنى للعمولة', filled($agency->min_booking_commission) ? $agency->min_booking_commission . ' ر.س' : null) !!}
                                {!! $field('الحد الأعلى للعمولة', filled($agency->max_booking_commission) ? $agency->max_booking_commission . ' ر.س' : null) !!}
                            </div>
                        </div>
                    </div>

                    {{-- تذييل النافذة --}}
                    <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 p-5 border-t border-slate-100 bg-slate-50/80 shrink-0">
                        <button type="button" @click="reviewAgency = null"
                            class="px-5 py-2.5 bg-white text-slate-600 border border-slate-200 rounded-xl text-xs font-bold hover:bg-slate-100 transition">
                            إغلاق
                        </button>
                        <form action="{{ route('admin.agencies.approve', $agency->id) }}" method="POST"
                            onsubmit="return confirm('هل أنت متأكد من مراجعة البيانات وتفعيل هذه الوكالة؟')">
                            @csrf
                            <button type="submit"
                                class="w-full px-6 py-2.5 bg-green-600 text-white rounded-xl text-xs font-bold shadow-sm hover:bg-green-700 transition-all flex items-center justify-center gap-1">
                                ✅ تفعيل حساب الوكالة
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- ==================== نوافذ مراجعة هويات المستخدمين ==================== --}}
        @foreach ($pendingUsers as $user)
            @php
                $idImg = $resolveStorage($user->id_image);
                $avatarImg = $resolveStorage($user->avatar);

                $userChecks = [
                    'صورة الهوية' => (bool) $idImg,
                    'رقم الهوية (10 أرقام)' => (bool) preg_match('/^\d{10}$/', (string) $user->national_id),
                    'رقم الجوال' => filled($user->phone),
                    'تاريخ الميلاد' => filled($user->birth_date),
                ];
            @endphp

            <div x-show="reviewUser === {{ $user->id }}" x-cloak x-transition.opacity
                class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-sm"
                @click.self="reviewUser = null" role="dialog" aria-modal="true">
                <div
                    class="bg-white w-full max-w-3xl max-h-[90vh] rounded-3xl shadow-2xl flex flex-col overflow-hidden border border-slate-100">

                    {{-- رأس النافذة --}}
                    <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/80 shrink-0">
                        <div class="flex items-center gap-3">
                            @if ($avatarImg)
                                <img src="{{ $avatarImg }}" alt="" class="w-12 h-12 rounded-xl object-cover border border-amber-100"
                                    onerror="this.outerHTML='<div class=&quot;w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-2xl border border-amber-100/60&quot;>🪪</div>'">
                            @else
                                <div
                                    class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-2xl border border-amber-100/60">
                                    🪪</div>
                            @endif
                            <div>
                                <h3 class="text-base font-black text-slate-900">{{ $user->name }}</h3>
                                <p class="text-[11px] font-semibold text-slate-400">
                                    طلب توثيق هوية • {{ $user->created_at->format('Y-m-d H:i') }}
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="reviewUser = null"
                            class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- محتوى النافذة --}}
                    <div class="p-6 space-y-6 overflow-y-auto flex-1">

                        {{-- قائمة التحقق --}}
                        <div>
                            <p class="text-xs font-black text-slate-500 mb-2">فحص سريع للبيانات</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($userChecks as $label => $ok)
                                    <span
                                        class="text-[11px] font-bold px-2.5 py-1 rounded-full border {{ $ok ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-amber-50 text-amber-700 border-amber-100' }}">
                                        {{ $ok ? '✅' : '⚠️' }} {{ $label }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        {{-- صورة الهوية --}}
                        <div>
                            <p class="text-xs font-black text-slate-500 mb-2">صورة الهوية المرفوعة</p>
                            @if ($idImg)
                                @if ($isPdf($idImg))
                                    <a href="{{ $idImg }}" target="_blank" rel="noopener"
                                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 rounded-xl text-xs font-bold text-slate-700 transition">
                                        📄 فتح ملف الهوية (PDF)
                                    </a>
                                @else
                                    <button type="button" @click="imagePreview = @js($idImg)"
                                        class="group relative block w-full sm:w-80">
                                        <img src="{{ $idImg }}" alt="صورة الهوية"
                                            class="w-full h-48 object-contain bg-slate-50 rounded-xl border border-slate-200">
                                        <span
                                            class="absolute inset-0 flex items-center justify-center bg-slate-900/0 group-hover:bg-slate-900/40 text-white text-xs font-bold opacity-0 group-hover:opacity-100 rounded-xl transition">
                                            🔍 تكبير الصورة
                                        </span>
                                    </button>
                                @endif
                            @else
                                <p class="text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 rounded-xl px-3 py-2 w-fit">
                                    ⚠️ لم يرفع المسافر صورة الهوية
                                </p>
                            @endif
                        </div>

                        {{-- البيانات الشخصية --}}
                        <div>
                            <p class="text-xs font-black text-slate-500 mb-2">البيانات الشخصية</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                {!! $field('الاسم', $user->name) !!}
                                {!! $field('رقم الهوية', $user->national_id) !!}
                                {!! $field('تاريخ الميلاد (ميلادي)', $fmtDate($user->birth_date)) !!}
                                {!! $field('تاريخ الميلاد (هجري)', $user->birth_date_hijri) !!}
                                {!! $field('الجنس', $genderLabel($user->gender)) !!}
                                {!! $field('رمز الجنسية', $user->nationality_code) !!}
                                {!! $field('المدينة', $user->city) !!}
                            </div>
                        </div>

                        {{-- التواصل والحساب --}}
                        <div>
                            <p class="text-xs font-black text-slate-500 mb-2">التواصل والحساب</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                {!! $field('البريد الإلكتروني', $user->email) !!}
                                {!! $field('رقم الجوال', $user->phone) !!}
                                {!! $field('تاريخ التسجيل', $user->created_at->format('Y-m-d')) !!}
                                {!! $field('عدد الحجوزات', $user->bookings_count) !!}
                                {!! $field('نقاط الولاء', $user->loyalty_points) !!}
                            </div>
                        </div>

                        {{-- المرافقون --}}
                        <div>
                            <p class="text-xs font-black text-slate-500 mb-2">المرافقون ({{ $user->companions->count() }})</p>
                            @if ($user->companions->isNotEmpty())
                                <div class="overflow-x-auto rounded-xl border border-slate-100">
                                    <table class="w-full text-right text-xs">
                                        <thead class="bg-slate-50 text-slate-400 font-bold">
                                            <tr>
                                                <th class="p-3">الاسم</th>
                                                <th class="p-3">رقم الهوية</th>
                                                <th class="p-3">تاريخ الميلاد</th>
                                                <th class="p-3">الجنس</th>
                                                <th class="p-3">صلة القرابة</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 font-bold text-slate-700">
                                            @foreach ($user->companions as $companion)
                                                <tr>
                                                    <td class="p-3">{{ $val($companion->name) }}</td>
                                                    <td class="p-3" dir="auto">{{ $val($companion->national_id) }}</td>
                                                    <td class="p-3">{{ $val($fmtDate($companion->birth_date)) }}</td>
                                                    <td class="p-3">{{ $val($genderLabel($companion->gender)) }}</td>
                                                    <td class="p-3">{{ $val($companion->relationship) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-xs font-semibold text-slate-400">لا يوجد مرافقون مسجلون.</p>
                            @endif
                        </div>
                    </div>

                    {{-- تذييل النافذة --}}
                    <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 p-5 border-t border-slate-100 bg-slate-50/80 shrink-0">
                        <button type="button" @click="reviewUser = null"
                            class="px-5 py-2.5 bg-white text-slate-600 border border-slate-200 rounded-xl text-xs font-bold hover:bg-slate-100 transition">
                            إغلاق
                        </button>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <form action="{{ route('admin.users.reject', $user->id) }}" method="POST"
                                onsubmit="return confirm('هل أنت متأكد من رفض طلب توثيق الهوية؟')">
                                @csrf
                                <button type="submit"
                                    class="w-full px-5 py-2.5 bg-white text-rose-600 border border-rose-200 rounded-xl text-xs font-bold hover:bg-rose-50 transition-all">
                                    ✖ رفض الطلب
                                </button>
                            </form>
                            <form action="{{ route('admin.users.verify', $user->id) }}" method="POST"
                                onsubmit="return confirm('هل أنت متأكد من اعتماد وتوثيق هوية هذا المسافر؟')">
                                @csrf
                                <button type="submit"
                                    class="w-full px-6 py-2.5 bg-green-600 text-white rounded-xl text-xs font-bold shadow-sm hover:bg-green-700 transition-all flex items-center justify-center gap-1">
                                    ✅ اعتماد الهوية
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- معاينة الصور بحجم كامل --}}
        <div x-show="imagePreview" x-cloak x-transition.opacity
            class="fixed inset-0 z-[70] bg-slate-950/85 flex items-center justify-center p-4" @click="imagePreview = null">
            <button type="button" @click="imagePreview = null"
                class="absolute top-5 left-5 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white text-xl font-bold transition">
                ✕
            </button>
            <img :src="imagePreview" alt="معاينة" class="max-w-full max-h-full rounded-xl shadow-2xl object-contain"
                @click.stop>
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
