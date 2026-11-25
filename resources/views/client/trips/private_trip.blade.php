@extends('layouts.client')

@section('content')
    <div class="p-4 md:p-8 bg-slate-50 min-h-screen" x-data="{
        privateTripModalOpen: false,
        searchQuery: '',
        statusFilter: 'all',
        matchesFilter(from, to, agency, status) {
            const query = this.searchQuery.toLowerCase().trim();
            const textToSearch = (from + ' ' + to + ' ' + agency).toLowerCase();
            const matchesSearch = !query || textToSearch.includes(query);
            const matchesStatus = this.statusFilter === 'all' || status === this.statusFilter;
            return matchesSearch && matchesStatus;
        }
    }">

        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-800 flex items-center gap-2">
                    <span>طلباتي للرحلات الخاصة</span>
                    <span class="text-xl">🧳</span>
                </h1>
                <p class="text-xs font-bold text-slate-500 mt-1">متابعة حالة طلبات التنقل الخاص وإكمال إجراءات الحجز</p>
            </div>

            @if ($user->is_verified === 1)
                <!-- Create New Request Button -->
                <button type="button" @click="privateTripModalOpen = true"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white text-xs font-black rounded-xl transition-all shadow-md shadow-amber-500/20 active:scale-95 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>طلب رحلة جديدة</span>
                </button>
            @endif
        </div>

        <!-- Quick Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 block">إجمالي طلباتك</span>
                    <span class="text-xl font-black text-slate-800">{{ $stats['total'] }}</span>
                </div>
                <div class="w-9 h-9 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center text-base">
                    📦
                </div>
            </div>

            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 block">قيد الانتظار</span>
                    <span class="text-xl font-black text-amber-500">{{ $stats['pending'] }}</span>
                </div>
                <div class="w-9 h-9 bg-amber-50 text-amber-500 rounded-xl flex items-center justify-center text-base">
                    ⏳
                </div>
            </div>

            <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 block">مقبولة / جاهزة للحجز</span>
                    <span class="text-xl font-black text-emerald-600">{{ $stats['accepted'] }}</span>
                </div>
                <div class="w-9 h-9 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-base">
                    ✅
                </div>
            </div>
        </div>

        <!-- 🔍 Search & Filter Bar Section -->
        <div
            class="bg-white rounded-2xl border border-slate-200/80 p-3 mb-6 shadow-sm flex flex-col md:flex-row items-center justify-between gap-3">
            <!-- Search Input -->
            <div class="relative w-full md:w-80">
                <input type="text" x-model="searchQuery" placeholder="بحث باسم المدينة أو الوكالة..."
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 pr-9 pl-3 text-xs font-bold text-slate-700 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:bg-white transition-all">
                <svg class="w-4 h-4 text-slate-400 absolute right-3 top-2.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <!-- Status Filter Pills -->
            <div class="flex items-center gap-1.5 w-full md:w-auto overflow-x-auto pb-1 md:pb-0">
                <button type="button" @click="statusFilter = 'all'"
                    :class="statusFilter === 'all' ? 'bg-slate-800 text-white' :
                        'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all shrink-0">
                    الكل
                </button>
                <button type="button" @click="statusFilter = 'pending'"
                    :class="statusFilter === 'pending' ? 'bg-amber-500 text-white' :
                        'bg-slate-100 text-slate-600 hover:bg-amber-50 hover:text-amber-600'"
                    class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all shrink-0">
                    ⏳ قيد الانتظار
                </button>
                <button type="button" @click="statusFilter = 'accepted'"
                    :class="statusFilter === 'accepted' ? 'bg-emerald-600 text-white' :
                        'bg-slate-100 text-slate-600 hover:bg-emerald-50 hover:text-emerald-600'"
                    class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all shrink-0">
                    ✅ المقبولة
                </button>
                <button type="button" @click="statusFilter = 'rejected'"
                    :class="statusFilter === 'rejected' ? 'bg-rose-600 text-white' :
                        'bg-slate-100 text-slate-600 hover:bg-rose-50 hover:text-rose-600'"
                    class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all shrink-0">
                    ❌ الملغاة
                </button>
            </div>
        </div>

        <!-- Requests List (2-Column Grid) -->
        @if ($requests->count() > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @foreach ($requests as $request)
                    <div x-show="matchesFilter('{{ $request->from_city }}', '{{ $request->to_city }}', '{{ $request->agency ? $request->agency->name : 'طلب عام' }}', '{{ $request->status }}')"
                        x-transition
                        class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-sm hover:border-amber-400/80 transition-all flex flex-col justify-between">
                        <div>
                            <!-- Header: Route + Status -->
                            <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5 mb-2.5">
                                <!-- Route Badge -->
                                <div
                                    class="flex items-center gap-1.5 bg-slate-100 px-2.5 py-1 rounded-lg text-xs font-black text-slate-800">
                                    <span>{{ $request->from_city }}</span>
                                    <svg class="w-3 h-3 text-amber-500 rotate-180" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                    <span>{{ $request->to_city }}</span>
                                </div>

                                <!-- Status Badge -->
                                @if ($request->status == 'pending')
                                    <span
                                        class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 border border-amber-200/60 text-[10px] font-extrabold px-2 py-0.5 rounded-full">
                                        ⏳ قيد المعالجة
                                    </span>
                                @elseif($request->status == 'accepted')
                                    <span
                                        class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-200/60 text-[10px] font-extrabold px-2 py-0.5 rounded-full">
                                        ✅ تم القبول وجاهز
                                    </span>
                                @elseif($request->status == 'rejected')
                                    <span
                                        class="inline-flex items-center gap-1 bg-rose-50 text-rose-700 border border-rose-200/60 text-[10px] font-extrabold px-2 py-0.5 rounded-full">
                                        ❌ ملغي
                                    </span>
                                @else
                                    <span
                                        class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                        {{ $request->status }}
                                    </span>
                                @endif
                            </div>

                            <!-- Details Horizontal Chips -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 text-[11px] font-bold mb-2.5">
                                <div class="bg-slate-50 p-1.5 rounded-lg border border-slate-100 text-center">
                                    <span class="text-[9px] text-slate-400 block">📅 المغادرة</span>
                                    <span
                                        class="text-slate-800 font-extrabold">{{ $request->departure_date ? \Carbon\Carbon::parse($request->departure_date)->format('Y-m-d') : '-' }}</span>
                                </div>

                                <div class="bg-slate-50 p-1.5 rounded-lg border border-slate-100 text-center">
                                    <span class="text-[9px] text-slate-400 block">🚘 الوسيلة</span>
                                    <span class="text-slate-800 font-extrabold truncate block">
                                        @switch($request->transport_type)
                                            @case('bus')
                                                حافلة
                                            @break

                                            @case('van')
                                                فان
                                            @break

                                            @case('car_vip')
                                                VIP
                                            @break

                                            @case('flight_charter')
                                                طيران
                                            @break

                                            @default
                                                {{ $request->transport_type }}
                                        @endswitch
                                    </span>
                                </div>

                                <div class="bg-slate-50 p-1.5 rounded-lg border border-slate-100 text-center">
                                    <span class="text-[9px] text-slate-400 block">👥 العدد</span>
                                    <span class="text-slate-800 font-extrabold">{{ $request->passengers_count }}
                                        أفراد</span>
                                </div>

                                <div class="bg-slate-50 p-1.5 rounded-lg border border-slate-100 text-center">
                                    <span class="text-[9px] text-slate-400 block">🏢 الوكالة</span>
                                    <span class="text-slate-800 font-extrabold truncate block"
                                        title="{{ $request->agency ? $request->agency->name : 'عام' }}">
                                        {{ $request->agency ? $request->agency->name : 'عام (الكل)' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Compact Notes -->
                            @if ($request->notes)
                                <div
                                    class="bg-amber-50/50 border border-amber-100/80 px-2.5 py-1.5 rounded-lg text-[10px] text-slate-600 mb-2.5 flex items-center gap-1">
                                    <span class="font-extrabold text-amber-800 shrink-0">📝 ملاحظة:</span>
                                    <span class="truncate">{{ $request->notes }}</span>
                                </div>
                            @endif
                        </div>

                        <div>
                            <!-- 🟢 قسم إكمال الحجز للرحلات المقبولة -->
                            @if ($request->status == 'accepted')
                                <div
                                    class="mt-1 mb-2.5 p-2.5 bg-emerald-50/80 border border-emerald-200/70 rounded-xl flex items-center justify-between gap-2">
                                    <div>
                                        <span class="text-[9px] text-emerald-700 font-bold block">التكلفة</span>
                                        <span class="text-xs font-black text-emerald-900">
                                            {{ isset($request->price) ? number_format($request->price, 2) . ' ر.س' : 'محددة بالعرض' }}
                                        </span>
                                    </div>

                                    <a href="{{ auth()->check() ? route('client.select_seat', $request->trip?->id ?? $request->id) : route('client.auth.login') }}"
                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-black rounded-lg transition-all shadow-sm active:scale-95 shrink-0 flex items-center gap-1">
                                        <span>التفاصيل</span>
                                        <svg class="w-3 h-3 rotate-180" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            @endif

                            <!-- Card Footer Info -->
                            <div
                                class="flex items-center justify-between pt-2 border-t border-slate-100 text-[10px] font-bold text-slate-400">
                                <span class="flex items-center gap-1">
                                    📞 <span class="text-slate-600">{{ $request->contact_name }}</span>
                                    ({{ $request->contact_phone }})
                                </span>
                                <span>{{ $request->created_at ? $request->created_at->format('Y/m/d') : '-' }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if ($requests->hasPages())
                <div
                    class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm">
                    <div class="text-xs font-bold text-slate-500">
                        عرض الصفحة <span class="font-black text-slate-800">{{ $requests->currentPage() }}</span> من أصل
                        <span class="font-black text-slate-800">{{ $requests->lastPage() }}</span>
                    </div>

                    <div class="flex items-center gap-1.5 dir-rtl">
                        @if ($requests->onFirstPage())
                            <span
                                class="px-3 py-1 bg-slate-100 text-slate-400 rounded-lg text-xs font-bold cursor-not-allowed">السابق</span>
                        @else
                            <a href="{{ $requests->previousPageUrl() }}"
                                class="px-3 py-1 bg-slate-100 hover:bg-amber-500 hover:text-white text-slate-700 rounded-lg text-xs font-bold transition-all">السابق</a>
                        @endif

                        @foreach ($requests->getUrlRange(1, $requests->lastPage()) as $page => $url)
                            @if ($page == $requests->currentPage())
                                <span
                                    class="w-7 h-7 flex items-center justify-center bg-amber-500 text-white rounded-lg text-xs font-black shadow-md shadow-amber-500/20">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}"
                                    class="w-7 h-7 flex items-center justify-center bg-slate-100 hover:bg-amber-50 hover:text-amber-600 text-slate-700 rounded-lg text-xs font-bold transition-all">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($requests->hasMorePages())
                            <a href="{{ $requests->nextPageUrl() }}"
                                class="px-3 py-1 bg-slate-100 hover:bg-amber-500 hover:text-white text-slate-700 rounded-lg text-xs font-bold transition-all">التالي</a>
                        @else
                            <span
                                class="px-3 py-1 bg-slate-100 text-slate-400 rounded-lg text-xs font-bold cursor-not-allowed">التالي</span>
                        @endif
                    </div>
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-10 text-center max-w-lg mx-auto my-8 shadow-sm">
                <div
                    class="w-14 h-14 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-3">
                    🚀
                </div>
                <h3 class="text-sm font-black text-slate-800 mb-1">لا توجد لديك طلبات رحلات خاصة حالياً</h3>
                <p class="text-xs font-bold text-slate-400 mb-5 leading-relaxed">يمكنك إنشاء طلب جديد لتلقي العروض والتواصل
                    المباشر من شركات النقل والوكالات.</p>

                @if ($user->is_verified === 1)
                    <button type="button" @click="privateTripModalOpen = true"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white text-xs font-black rounded-xl transition-all shadow-md shadow-amber-500/20 cursor-pointer active:scale-95">
                        <span>+ إنشاء طلب رحلة خاصة الآن</span>
                    </button>
                @endif
            </div>
        @endif

        <!-- 👑 نافذة تقديم طلب رحلة خاصة (Modal) -->
        <div x-show="privateTripModalOpen" style="display: none;"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/70 backdrop-blur-sm p-4 overflow-y-auto"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-100 p-6 md:p-8 my-8 text-slate-800"
                @click.outside="privateTripModalOpen = false">

                <button type="button" @click="privateTripModalOpen = false"
                    class="absolute top-6 left-6 w-8 h-8 bg-slate-100 hover:bg-rose-50 text-slate-400 hover:text-rose-600 rounded-full flex items-center justify-center text-lg transition-all font-bold cursor-pointer">
                    &times;
                </button>

                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center text-2xl shrink-0">
                        👑
                    </div>
                    <div>
                        <h3 class="text-lg md:text-xl font-black text-slate-800">طلب رحلة خاصة</h3>
                        <p class="text-xs text-slate-500 font-bold mt-0.5">حدد متطلباتك وسنقوم بتوصيل طلبك لشركات النقل
                            لتقديم العروض</p>
                    </div>
                </div>

                <form action="{{ route('client.private_trip.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">مدينة الانطلاق</label>
                            <select name="from_city" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none">
                                <option value="">اختر مدينة الانطلاق</option>
                                @if (isset($saudiCities))
                                    @foreach ($saudiCities as $city)
                                        <option value="{{ $city }}">{{ $city }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">الوجهة</label>
                            <select name="to_city" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none">
                                <option value="">اختر الوجهة</option>
                                @if (isset($saudiCities))
                                    @foreach ($saudiCities as $city)
                                        <option value="{{ $city }}">{{ $city }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">الوكالة المستهدفة</label>
                        <select name="agency_id"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none">
                            <option value="">جميع الوكالات (طلب عام)</option>
                            @if (isset($agencies))
                                @foreach ($agencies as $agency)
                                    <option value="{{ $agency->id }}"
                                        {{ old('agency_id') == $agency->id ? 'selected' : '' }}>
                                        {{ $agency->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">تاريخ المغادرة</label>
                            <input type="date" name="departure_date" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">عدد الأفراد (المسافرين)</label>
                            <input type="number" name="passengers_count" min="1" value="1" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">نوع المركبة المفضلة</label>
                            <select name="transport_type" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none">
                                <option value="bus">حافلة (باص)</option>
                                <option value="van">فان (عائلي)</option>
                                <option value="car_vip">سيارة خاصة VIP</option>
                                <option value="flight_charter">طيران خاص</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">الاسم</label>
                            <input type="text" name="contact_name" value="{{ auth()->user()->name ?? '' }}" required
                                placeholder="اسم صاحب الطلب"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">رقم التواصل (واتساب/جوال)</label>
                            <input type="tel" name="contact_phone" value="{{ auth()->user()->phone ?? '' }}"
                                required placeholder="05xxxxxxxx"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none text-left dir-ltr">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">ملاحظات إضافية (اختياري)</label>
                        <textarea name="notes" rows="2" placeholder="أي تفاصيل خاصة مثل نقطة الانطلاق الدقيقة أو متطلبات إضافية..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none"></textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="w-full py-3.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-black rounded-xl transition-all shadow-md shadow-amber-500/20 active:scale-[0.98] text-sm cursor-pointer">
                            إرسال طلب الرحلة الخاصة 🚀
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
