@extends('layouts.admin')

@section('content')
    @php
        $allTrips = $trips ?? $pendingTrips ?? collect();
        $totalCount = $allTrips instanceof \Illuminate\Pagination\AbstractPaginator ? $allTrips->total() : $allTrips->count();
        
        // تجهيز مصفوفة مركزية خفيفة لتخزين تفاصيل الرحلات ومنع تعليق المتصفح (DOM Bloat)
        $tripsDataMap = collect($allTrips instanceof \Illuminate\Pagination\AbstractPaginator ? $allTrips->items() : $allTrips)
            ->mapWithKeys(function($t) {
                $arr = $t->toArray();
                $arr['agency_name'] = $t->agency->name ?? 'وكالة غير مسجلة';
                return [$t->id => $arr];
            });
    @endphp

    <div class="container mx-auto px-4 py-8 animate-fade-in" x-data="{
        tripsData: @js($tripsDataMap),
        searchQuery: '',
        filterType: 'all',
        filterStatus: 'all',
        sortBy: 'newest',
        openModal: false,
        selectedTrip: null,

        parseJson(val) {
            if (!val) return null;
            if (typeof val === 'object') return val;
            try { return JSON.parse(val); } catch (e) { return null; }
        },
        formatDate(dateStr) {
            if (!dateStr) return 'غير محدد';
            const d = new Date(dateStr);
            return d.toLocaleString('ar-SA', { dateStyle: 'short', timeStyle: 'short' });
        }
    }">

        {{-- رأس الصفحة --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                    مركز إدارة وإشراف الرحلات
                    <span class="text-xs bg-indigo-600 text-white px-3 py-1 rounded-full font-bold">
                        {{ $totalCount }} رحلة
                    </span>
                </h1>
                <p class="text-slate-500 mt-2 text-sm font-medium">متابعة الرحلات، اعتماد العروض، التعامل مع طلبات الإلغاء، وتعديل وحذف الرحلات.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <div class="bg-amber-50 text-amber-700 px-3.5 py-2 rounded-2xl border border-amber-200/60 text-xs font-black shadow-sm">
                    ⏳ معلقة: {{ $allTrips->whereIn('status', ['pending', 'معلق'])->count() }}
                </div>
                <div class="bg-orange-50 text-orange-700 px-3.5 py-2 rounded-2xl border border-orange-200/60 text-xs font-black shadow-sm">
                    ⚠️ طلبات إلغاء: {{ $allTrips->whereIn('status', ['cancellation_requested', 'طلب إلغاء'])->count() }}
                </div>
                <div class="bg-emerald-50 text-emerald-700 px-3.5 py-2 rounded-2xl border border-emerald-200/60 text-xs font-black shadow-sm">
                    ✅ معتمدة: {{ $allTrips->whereIn('status', ['approved', 'active', 'معتمد'])->count() }}
                </div>
                <div class="bg-blue-50 text-blue-700 px-3.5 py-2 rounded-2xl border border-blue-200/60 text-xs font-black shadow-sm">
                    🏁 مكتملة: {{ $allTrips->whereIn('status', ['completed', 'تمت الرحلة'])->count() }}
                </div>
            </div>
        </div>

        {{-- التنبيهات --}}
        @if (session('success'))
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl font-bold text-xs flex items-center gap-2 shadow-sm">
                <span>✅</span> {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-2xl font-bold text-xs flex items-center gap-2 shadow-sm">
                <span>❌</span> {{ session('error') }}
            </div>
        @endif

        {{-- أدوات التصفية والبحث --}}
        <div class="mb-8 bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col lg:flex-row gap-4 justify-between items-center">
            <div class="relative w-full lg:w-80">
                <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-400">🔍</span>
                <input type="text" x-model="searchQuery" placeholder="بحث بالوكالة، المدن، أو رقم ID..."
                    class="w-full pr-10 pl-4 py-2.5 bg-slate-50 border border-slate-200/80 rounded-2xl text-xs font-bold text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 transition-all">
            </div>

            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto justify-between lg:justify-end">
                <div class="flex gap-1 bg-slate-100/80 p-1 rounded-2xl">
                    <button @click="filterType = 'all'" :class="filterType === 'all' ? 'bg-white text-slate-900 shadow-sm font-black' : 'text-slate-500 font-bold'" class="px-3 py-1.5 rounded-xl text-xs transition-all">الكل</button>
                    <button @click="filterType = 'bus'" :class="filterType === 'bus' ? 'bg-indigo-600 text-white shadow-sm font-black' : 'text-slate-500 font-bold'" class="px-3 py-1.5 rounded-xl text-xs transition-all">🚌 باصات</button>
                    <button @click="filterType = 'flight'" :class="filterType === 'flight' ? 'bg-indigo-600 text-white shadow-sm font-black' : 'text-slate-500 font-bold'" class="px-3 py-1.5 rounded-xl text-xs transition-all">✈️ طيران</button>
                </div>

                <div class="flex gap-1 bg-slate-100/80 p-1 rounded-2xl flex-wrap">
                    <button @click="filterStatus = 'all'" :class="filterStatus === 'all' ? 'bg-white text-slate-900 shadow-sm font-black' : 'text-slate-500 font-bold'" class="px-3 py-1.5 rounded-xl text-xs transition-all">الكل</button>
                    <button @click="filterStatus = 'pending'" :class="filterStatus === 'pending' ? 'bg-amber-500 text-white shadow-sm font-black' : 'text-slate-500 font-bold'" class="px-3 py-1.5 rounded-xl text-xs transition-all">⏳ معلقة</button>
                    <button @click="filterStatus = 'cancellation_requested'" :class="filterStatus === 'cancellation_requested' ? 'bg-orange-600 text-white shadow-sm font-black' : 'text-slate-500 font-bold'" class="px-3 py-1.5 rounded-xl text-xs transition-all">⚠️ طلب إلغاء</button>
                    <button @click="filterStatus = 'approved'" :class="filterStatus === 'approved' ? 'bg-emerald-600 text-white shadow-sm font-black' : 'text-slate-500 font-bold'" class="px-3 py-1.5 rounded-xl text-xs transition-all">✅ معتمدة</button>
                    <button @click="filterStatus = 'completed'" :class="filterStatus === 'completed' ? 'bg-blue-600 text-white shadow-sm font-black' : 'text-slate-500 font-bold'" class="px-3 py-1.5 rounded-xl text-xs transition-all">🏁 مكتملة</button>
                </div>
            </div>
        </div>

        {{-- شبكة الكروت --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($allTrips as $trip)
                <div x-show="(
                        '{{ strtolower($trip->agency->name ?? '') }} {{ strtolower($trip->from_city) }} {{ strtolower($trip->to_city) }} {{ strtolower($trip->title ?? '') }} #{{ $trip->id }}'.toLowerCase().includes(searchQuery.toLowerCase())
                    ) && (
                        filterType === 'all' || filterType === '{{ $trip->transport_type }}'
                    ) && (
                        filterStatus === 'all' || 
                        (filterStatus === 'pending' && ['pending', 'معلق'].includes('{{ $trip->status }}')) ||
                        (filterStatus === 'approved' && ['approved', 'active', 'معتمد'].includes('{{ $trip->status }}')) ||
                        (filterStatus === 'cancellation_requested' && ['cancellation_requested', 'طلب إلغاء'].includes('{{ $trip->status }}')) ||
                        (filterStatus === 'completed' && ['completed', 'تمت الرحلة'].includes('{{ $trip->status }}'))
                    )"
                    class="bg-white rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between group">

                    <div>
                        <div class="relative h-48 overflow-hidden bg-slate-900">
                            <img src="{{ $trip->cover_image ? (str_starts_with($trip->cover_image, 'http') ? $trip->cover_image : asset(str_starts_with($trip->cover_image, 'storage/') ? $trip->cover_image : 'storage/' . $trip->cover_image)) : 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?q=80&w=1000&auto=format&fit=crop' }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-90"
                                loading="lazy"
                                alt="Trip Image">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-black/30"></div>

                            <div class="absolute top-4 right-4 flex items-center gap-2">
                                <span class="bg-slate-900/90 text-white text-[10px] font-black px-2.5 py-1 rounded-xl">
                                    #{{ $trip->id }}
                                </span>
                                <span class="bg-white/95 text-slate-800 text-[10px] font-black px-2.5 py-1 rounded-xl">
                                    {{ $trip->transport_type == 'bus' ? '🚌 باص' : '✈️ طيران' }}
                                </span>
                            </div>

                            <div class="absolute top-4 left-4">
                                @if (in_array($trip->status, ['completed', 'تمت الرحلة']))
                                    <span class="bg-blue-600/95 text-white text-[10px] font-black px-2.5 py-1 rounded-xl shadow-md">🏁 مكتملة</span>
                                @elseif (in_array($trip->status, ['approved', 'active', 'معتمد']))
                                    <span class="bg-emerald-500/95 text-white text-[10px] font-black px-2.5 py-1 rounded-xl shadow-md">✅ معتمدة</span>
                                @elseif(in_array($trip->status, ['cancellation_requested', 'طلب إلغاء']))
                                    <span class="bg-orange-500/95 text-white text-[10px] font-black px-2.5 py-1 rounded-xl shadow-md animate-pulse">⚠️ طلب إلغاء</span>
                                @elseif(in_array($trip->status, ['rejected', 'مرفوض']))
                                    <span class="bg-rose-600/95 text-white text-[10px] font-black px-2.5 py-1 rounded-xl shadow-md">🔴 مرفوضة</span>
                                @else
                                    <span class="bg-amber-500/95 text-white text-[10px] font-black px-2.5 py-1 rounded-xl shadow-md">⏳ معلقة</span>
                                @endif
                            </div>

                            <div class="absolute bottom-3 right-4 left-4">
                                <span class="text-[10px] text-indigo-300 font-extrabold uppercase tracking-wider block">
                                    🏢 {{ $trip->agency->name ?? 'وكالة غير مسجلة' }}
                                </span>
                                <h3 class="text-lg font-black text-white leading-tight truncate mt-0.5">
                                    {{ $trip->from_city }} ↔️ {{ $trip->to_city }}
                                </h3>
                            </div>
                        </div>

                        <div class="p-5 space-y-4">
                            @if ($trip->title)
                                <p class="text-xs font-bold text-slate-600 line-clamp-1">📌 {{ $trip->title }}</p>
                            @endif

                            <div class="bg-slate-50/80 p-3 rounded-2xl border border-slate-100/80 space-y-1.5 text-xs text-slate-600 font-bold">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-400">🛫 المغادرة:</span>
                                    <span class="text-slate-800">{{ \Carbon\Carbon::parse($trip->departure_at)->format('Y-m-d | h:i A') }}</span>
                                </div>
                                @if ($trip->return_at)
                                    <div class="flex items-center justify-between border-t border-slate-200/50 pt-1.5">
                                        <span class="text-slate-400">🛬 العودة:</span>
                                        <span class="text-slate-800">{{ \Carbon\Carbon::parse($trip->return_at)->format('Y-m-d | h:i A') }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center justify-between pt-1">
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 block">المقاعد المتاحة</span>
                                    <span class="text-xs font-black text-slate-800">💺 {{ $trip->available_seats }} / {{ $trip->total_seats }}</span>
                                </div>
                                <div class="text-left">
                                    <span class="text-[10px] font-bold text-slate-400 block">السعر يبدأ من</span>
                                    <span class="text-lg font-black text-indigo-600">{{ number_format($trip->price) }} <small class="text-[10px] font-bold">ر.س</small></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- أزرار الإجراءات للآدمن --}}
                    <div class="p-4 bg-slate-50/50 border-t border-slate-100 space-y-2">
                        
                        @if(in_array($trip->status, ['cancellation_requested', 'طلب إلغاء']))
                            <form action="{{ route('admin.reject_cancellation', ['id' => $trip->id]) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <button type="submit" onclick="return confirm('هل أنت متأكد من رفض طلب الإلغاء وإعادة الرحلة للحالة النشطة؟')"
                                    class="w-full py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-black text-xs transition-all shadow-sm flex items-center justify-center gap-1.5">
                                    <span>🛡️</span> رفض طلب الإلغاء وإعادة التفعيل
                                </button>
                            </form>
                        @endif

                        @if(!in_array($trip->status, ['approved', 'active', 'معتمد', 'completed', 'تمت الرحلة']))
                            <form action="{{ route('admin.approve', ['id' => $trip->id]) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-black text-xs transition-all shadow-sm flex items-center justify-center gap-1.5">
                                    <span>✅</span> اعتماد الرحلة
                                </button>
                            </form>
                        @endif

                        <div class="flex gap-2">
                            <button @click="selectedTrip = tripsData[{{ $trip->id }}]; openModal = true;"
                                class="flex-1 py-2 bg-white hover:bg-indigo-50 hover:text-indigo-600 text-slate-700 rounded-xl font-black text-xs transition-all border border-slate-200 flex items-center justify-center gap-1">
                                👁️ التفاصيل
                            </button>

                            @if(!in_array($trip->status, ['rejected', 'completed', 'تمت الرحلة']))
                                <form action="{{ route('admin.reject_trip', ['id' => $trip->id]) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit" onclick="return confirm('هل أنت متأكد من رفض هذه الرحلة؟')"
                                        class="w-full py-2 bg-white hover:bg-amber-50 hover:text-amber-700 text-slate-600 rounded-xl font-black text-xs transition-all border border-slate-200">
                                        ❌ رفض
                                    </button>
                                </form>
                            @endif

                            <form action="{{ route('admin.trips.destroy', ['id' => $trip->id]) }}" method="POST" onsubmit="return confirm('⚠️ هل أنت متأكد من حذف هذه الرحلة نهائياً؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="حذف الرحلة" class="px-3 py-2 bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white rounded-xl font-black text-xs transition-all border border-rose-100 flex items-center justify-center">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            @empty
                <div class="col-span-full py-20 text-center bg-white rounded-3xl border-2 border-dashed border-slate-100">
                    <div class="text-6xl mb-3">🏜️</div>
                    <h2 class="text-lg font-black text-slate-800">لا توجد رحلات مطابقة</h2>
                </div>
            @endforelse
        </div>

        {{-- 📄 روابط الترقيم (Pagination) --}}
        @if ($allTrips instanceof \Illuminate\Pagination\AbstractPaginator && $allTrips->hasPages())
            <div class="mt-10 flex justify-center dir-ltr">
                <div class="bg-white px-4 py-3 rounded-2xl border border-slate-100 shadow-sm">
                    {{ $allTrips->appends(request()->query())->links() }}
                </div>
            </div>
        @endif

        {{-- النافذة المنبثقة Modal --}}
        <div x-show="openModal" class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/80 p-4" x-cloak>
            <div @click.away="openModal = false" class="bg-white w-full max-w-4xl rounded-3xl p-6 md:p-8 shadow-2xl relative max-h-[90vh] overflow-y-auto" x-if="selectedTrip">

                <div class="flex justify-between items-start mb-6 pb-4 border-b border-slate-100">
                    <div>
                        <span class="text-[10px] font-black uppercase text-indigo-600 tracking-wider" x-text="selectedTrip.agency_name"></span>
                        <h2 class="text-2xl font-black text-slate-900 mt-0.5" x-text="(selectedTrip.title ? selectedTrip.title + ' | ' : '') + selectedTrip.from_city + ' ⬅️ ' + selectedTrip.to_city"></h2>
                    </div>
                    <span class="bg-slate-100 text-slate-700 text-xs font-black px-3 py-1.5 rounded-xl" x-text="'#' + selectedTrip.id"></span>
                </div>

                <template x-if="selectedTrip.cover_image">
                    <div class="h-48 rounded-2xl overflow-hidden mb-6 bg-slate-100">
                        <img :src="selectedTrip.cover_image.startsWith('http') ? selectedTrip.cover_image : '/storage/' + selectedTrip.cover_image.replace('storage/', '')" class="w-full h-full object-cover" alt="Trip Image">
                    </div>
                </template>

                <div class="space-y-5">
    
                    {{-- 1. بيانات المسار الأساسية والشرطية --}}
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <h3 class="text-xs font-black text-slate-400 uppercase mb-3">📌 بيانات المسار والشروط</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                            <div><span class="text-slate-400 block">الحالة:</span> <strong class="text-slate-800" x-text="selectedTrip.status"></strong></div>
                            <div><span class="text-slate-400 block">نوع المسار:</span> <strong class="text-slate-800" x-text="selectedTrip.route_type === 'domestic' ? 'داخلي 🇸🇦' : 'دولي 🌍'"></strong></div>
                            <div><span class="text-slate-400 block">نوع الرحلة:</span> <strong class="text-slate-800" x-text="selectedTrip.trip_type === 'round_trip' ? 'ذهاب وعودة 🔄' : 'ذهاب فقط ➡️'"></strong></div>
                            <div><span class="text-slate-400 block">الحد الأدنى للمقاعد:</span> <strong class="text-slate-800" x-text="(selectedTrip.min_seats_required || 0) + ' مقعد'"></strong></div>
                            <div><span class="text-slate-400 block">المغادرة:</span> <strong class="text-slate-800" x-text="formatDate(selectedTrip.departure_at)"></strong></div>
                            <div><span class="text-slate-400 block">العودة:</span> <strong class="text-slate-800" x-text="formatDate(selectedTrip.return_at)"></strong></div>
                            <div><span class="text-slate-400 block">المقاعد الكلية:</span> <strong class="text-slate-800" x-text="selectedTrip.total_seats"></strong></div>
                            <div><span class="text-slate-400 block">المقاعد المتاحة:</span> <strong class="text-slate-800" x-text="selectedTrip.available_seats"></strong></div>
                        </div>
                    </div>

                    {{-- 2. الأسعار وإعدادات الرحلة --}}
                    <div class="bg-purple-50/50 p-4 rounded-2xl border border-purple-100">
                        <h3 class="text-xs font-black text-purple-600 uppercase mb-3">💰 الأسعار وإعدادات الرحلة</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                            <div><span class="text-slate-400 block">السعر الأساسي:</span> <strong class="text-indigo-600 text-sm" x-text="(selectedTrip.price || 0) + ' ر.س'"></strong></div>
                            <div><span class="text-slate-400 block">مدة الرحلة:</span> <strong class="text-slate-800" x-text="(selectedTrip.duration_days || 0) + ' أيام'"></strong></div>
                            <div><span class="text-slate-400 block">رحلة مشروطة:</span> <strong class="text-slate-800" x-text="selectedTrip.is_conditional ? 'نعم ✅' : 'لا ❌'"></strong></div>
                            <template x-if="selectedTrip.is_conditional">
                                <div><span class="text-slate-400 block">تاريخ حد الشرط:</span> <strong class="text-rose-600" x-text="formatDate(selectedTrip.threshold_deadline_at)"></strong></div>
                            </template>
                            <div><span class="text-slate-400 block">إتاحة إقامة فقط:</span> <strong class="text-slate-800" x-text="selectedTrip.allow_accommodation_only ? 'نعم ✅' : 'لا ❌'"></strong></div>
                            <template x-if="selectedTrip.allow_accommodation_only">
                                <div><span class="text-slate-400 block">خصم الإقامة فقط:</span> <strong class="text-slate-800" x-text="(selectedTrip.accommodation_only_discount || 0) + ' ر.س'"></strong></div>
                            </template>
                            <div><span class="text-slate-400 block">تقسيم تلقائي (Auto Split):</span> <strong class="text-slate-800" x-text="selectedTrip.auto_split ? 'نعم ✅' : 'لا ❌'"></strong></div>
                            <div><span class="text-slate-400 block">حد التقسيم:</span> <strong class="text-slate-800" x-text="selectedTrip.split_boundary || 'غير محدد'"></strong></div>
                            <div><span class="text-slate-400 block">السماح بإضافة مقعد:</span> <strong class="text-slate-800" x-text="selectedTrip.allow_to_add_seat ? 'نعم ✅' : 'لا ❌'"></strong></div>
                        </div>
                    </div>

                    {{-- 3. خيارات الغرف Room Options --}}
                    <template x-if="selectedTrip.room_options && Object.keys(parseJson(selectedTrip.room_options) || {}).length > 0">
                        <div class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100">
                            <h3 class="text-xs font-black text-blue-600 uppercase mb-3">🛏️ خيارات الغرف والإقامة</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <template x-for="(room, type) in parseJson(selectedTrip.room_options)">
                                    <div class="bg-white p-3 rounded-xl border border-blue-200 text-xs">
                                        <div class="flex justify-between items-center mb-2">
                                            <strong class="text-slate-800" x-text="
                                                type === 'single' ? 'غرفة مفردة' :
                                                type === 'double' ? 'غرفة مزدوجة' :
                                                type === 'triple' ? 'غرفة ثلاثية' :
                                                type === 'quad' ? 'غرفة رباعية' : type
                                            "></strong>
                                            <span class="text-indigo-600 font-black" x-text="room.price + ' ر.س'"></span>
                                        </div>
                                        <div class="text-slate-500 mb-2 font-medium" x-text="'العدد المتاح: ' + room.count"></div>
                                        <template x-if="room.packages && room.packages.length > 0">
                                            <div class="space-y-1">
                                                <span class="text-[10px] font-bold text-slate-400 block">الباقات المتاحة للغرفة:</span>
                                                <template x-for="pkg in room.packages">
                                                    <div class="bg-slate-50 p-2 rounded-lg border border-slate-100 flex justify-between">
                                                        <span class="font-bold text-slate-700" x-text="pkg.name"></span>
                                                        <span class="text-slate-500" x-text="pkg.price > 0 ? '(+' + pkg.price + ' ر.س)' : '(مشمول)'"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- 4. خيارات الوجبات (Meal Options) --}}
                    <template x-if="selectedTrip.meal_options && Object.keys(parseJson(selectedTrip.meal_options) || {}).length > 0">
                        <div class="bg-orange-50/50 p-4 rounded-2xl border border-orange-100">
                            <h3 class="text-xs font-black text-orange-600 uppercase mb-3">🍽️ إعدادات الوجبات</h3>
                            
                            <div x-data="{ mealData: parseJson(selectedTrip.meal_options) }">
                                
                                {{-- الوجبات الفردية --}}
                                <template x-if="mealData && mealData.individual">
                                    <div class="mb-3">
                                        <span class="text-[10px] font-bold text-slate-500 block mb-2">الوجبات الفردية:</span>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                            <template x-for="(meal, type) in mealData.individual">
                                                <div class="bg-white p-2.5 rounded-xl border text-xs flex justify-between items-center transition-all" 
                                                     :class="meal.active ? 'border-orange-200' : 'border-slate-200 bg-slate-50 opacity-75'">
                                                    <span class="font-bold" :class="meal.active ? 'text-slate-800' : 'text-slate-400'" x-text="
                                                        type === 'breakfast' ? 'إفطار 🍳' :
                                                        type === 'lunch' ? 'غداء 🍛' :
                                                        type === 'dinner' ? 'عشاء 🍲' : type
                                                    "></span>
                                                    
                                                    <template x-if="meal.active">
                                                        <span class="text-indigo-600 font-black" x-text="meal.price + ' ر.س'"></span>
                                                    </template>
                                                    <template x-if="!meal.active">
                                                        <span class="text-[10px] font-bold text-rose-400 bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-100">غير مفعل</span>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                {{-- باقات الوجبات --}}
                                <template x-if="mealData && mealData.packages && Object.values(mealData.packages).some(val => val !== null)">
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-500 block mb-2">باقات الوجبات المشتركة:</span>
                                        <div class="grid grid-cols-2 gap-3">
                                            <template x-for="(price, type) in mealData.packages">
                                                <template x-if="price !== null">
                                                    <div class="bg-white p-2.5 rounded-xl border border-orange-200 text-xs flex justify-between items-center">
                                                        <span class="font-bold text-slate-800" x-text="
                                                            type === 'all' ? 'جميع الوجبات' :
                                                            type === 'bf_dn' ? 'إفطار + عشاء' :
                                                            type === 'bf_ln' ? 'إفطار + غداء' :
                                                            type === 'ln_dn' ? 'غداء + عشاء' : type
                                                        "></span>
                                                        <span class="text-indigo-600 font-black" x-text="price + ' ر.س'"></span>
                                                    </div>
                                                </template>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                
                            </div>
                        </div>
                    </template>

                    {{-- 5. المميزات (Features) --}}
                    <template x-if="selectedTrip.features">
                        <div class="bg-emerald-50/50 p-4 rounded-2xl border border-emerald-100">
                            <h3 class="text-xs font-black text-emerald-600 uppercase mb-2">✨ مميزات الرحلة</h3>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="feature in (parseJson(selectedTrip.features) || [])">
                                    <span class="bg-white text-emerald-800 text-[11px] font-bold px-3 py-1 rounded-xl border border-emerald-200 shadow-sm" x-text="feature"></span>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- 6. الخدمات الإضافية (Extra Services) --}}
                    <template x-if="selectedTrip.extra_services">
                        <div class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100">
                            <h3 class="text-xs font-black text-indigo-600 uppercase mb-2">🎁 الخدمات الإضافية</h3>
                            <div class="space-y-2">
                                <template x-for="service in (parseJson(selectedTrip.extra_services) || [])">
                                    <div class="bg-white p-2.5 rounded-xl border border-indigo-100 flex justify-between items-center text-xs">
                                        <div>
                                            <strong class="text-slate-800 block" x-text="service.name"></strong>
                                            <span class="text-slate-400 text-[10px]" x-text="service.description"></span>
                                        </div>
                                        <span class="font-black text-indigo-600" x-text="service.price + ' ر.س'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- 7. الحسابات البنكية (Bank Accounts) --}}
                    <template x-if="selectedTrip.bank_accounts">
                        <div class="bg-amber-50/50 p-4 rounded-2xl border border-amber-100">
                            <h3 class="text-xs font-black text-amber-600 uppercase mb-2">🏦 الحسابات البنكية للتحويل</h3>
                            <div class="space-y-2">
                                <template x-for="bank in (parseJson(selectedTrip.bank_accounts) || [])">
                                    <div class="bg-white p-3 rounded-xl border border-amber-200 text-xs space-y-1">
                                        <div class="flex justify-between font-bold text-slate-800">
                                            <span x-text="bank.bank_name"></span>
                                            <span class="text-indigo-600 font-mono" x-text="bank.account_number"></span>
                                        </div>
                                        <div class="text-slate-500 font-mono text-[11px]" x-text="'IBAN: ' + bank.iban"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- 8. الملاحظات والشروط العامة (Notes) --}}
                    <template x-if="selectedTrip.notes">
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <h3 class="text-xs font-black text-slate-400 uppercase mb-2">📝 الشروط والملاحظات العامة</h3>
                            <p class="text-xs text-slate-700 leading-relaxed whitespace-pre-wrap font-medium" x-text="selectedTrip.notes"></p>
                        </div>
                    </template>

                </div>

                {{-- أزرار Modal --}}
                <div class="flex flex-wrap gap-3 mt-6 pt-4 border-t border-slate-100">
                    
                    <template x-if="['cancellation_requested', 'طلب إلغاء'].includes(selectedTrip.status)">
                        <form :action="'{{ route('admin.reject_cancellation', ['id' => ':id']) }}'.replace(':id', selectedTrip.id)" method="POST" class="w-full">
                            @csrf
                            @method('PUT')
                            <button type="submit" onclick="return confirm('هل أنت متأكد من رفض طلب الإلغاء وإعادة الرحلة للحالة النشطة؟')"
                                class="w-full py-3 bg-amber-600 text-white rounded-2xl font-black text-xs hover:bg-amber-700 transition-all flex items-center justify-center gap-1">
                                🛡️ رفض طلب الإلغاء وإعادة التفعيل
                            </button>
                        </form>
                    </template>

                    <template x-if="!['completed', 'تمت الرحلة'].includes(selectedTrip.status)">
                        <form :action="'{{ route('admin.approve', ['id' => ':id']) }}'.replace(':id', selectedTrip.id)" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full py-3 bg-emerald-600 text-white rounded-2xl font-black text-xs hover:bg-emerald-700 transition-all flex items-center justify-center gap-1">
                                <span>✅</span> اعتماد ونشر الرحلة
                            </button>
                        </form>
                    </template>

                    <template x-if="!['completed', 'تمت الرحلة', 'rejected'].includes(selectedTrip.status)">
                        <form :action="'{{ route('admin.reject_trip', ['id' => ':id']) }}'.replace(':id', selectedTrip.id)" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" onclick="return confirm('هل أنت متأكد من رفض هذه الرحلة؟')"
                                class="w-full py-3 bg-slate-100 text-slate-700 rounded-2xl font-black text-xs hover:bg-rose-50 hover:text-rose-600 transition-all">
                                رفض الطلب ❌
                            </button>
                        </form>
                    </template>

                    <form :action="'{{ route('admin.trips.destroy', ['id' => ':id']) }}'.replace(':id', selectedTrip.id)" method="POST" onsubmit="return confirm('⚠️ هل أنت متأكد من حذف هذه الرحلة نهائياً؟');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-3 bg-rose-50 text-rose-600 rounded-2xl font-black text-xs hover:bg-rose-600 hover:text-white transition-all border border-rose-100">
                            🗑️ حذف نهائي
                        </button>
                    </form>

                    <button @click="openModal = false" class="px-5 py-3 bg-slate-100 text-slate-600 rounded-2xl font-black text-xs hover:bg-slate-200">
                        إغلاق
                    </button>
                </div>

            </div>
        </div>

    </div>

    <style>
        [x-cloak] { display: none !important; }
        .animate-fade-in { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
@endsection