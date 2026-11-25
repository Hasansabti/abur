@extends('layouts.client')

@section('content')
    @php
        $companionsList = collect($companions ?? []);
        $unverifiedCompanions = $companionsList->filter(fn($c) => empty($c->id_document_path) && empty($c->id_image));
        $unverifiedCount = $unverifiedCompanions->count();

        $paymentsHistory = collect($paymentsHistory ?? []);
        $totalPaid = $totalPaid ?? 0;
        $extraPaidTotal = $extraPaidTotal ?? 0;
    @endphp

    <div class="container mx-auto px-4 py-8 max-w-6xl animate-fade-in" x-data="{
        tab: 'bookings',
        showPayments: false,
        openRating: false,
        openEditModal: false,
        openCompanionModal: false,
        editBookingId: null,
        editNotes: '',
        editLoading: false,
        selectedTrip: {},
        idUploaded: false,
        editCompanion: null,
        companionForm: { id: null, name: '', national_id: '', relationship: '', gender: 'male', birth_date: '', has_document: false },
        openCompanion(companion = null) {
            if (companion) {
                this.editCompanion = companion;
                this.companionForm = {
                    id: companion.id,
                    name: companion.name || '',
                    national_id: companion.national_id || '',
                    relationship: companion.relationship || companion.relation || '',
                    gender: companion.gender || 'male',
                    birth_date: (companion.birth_date || '').substring(0, 10),
                    has_document: !!(companion.id_document_path || companion.id_image)
                };
            } else {
                this.editCompanion = null;
                this.companionForm = { id: null, name: '', national_id: '', relationship: '', gender: 'male', birth_date: '', has_document: false };
            }
            this.openCompanionModal = true;
        }
    }">

        {{-- 1. الهيدر وصندوق معلومات المستخدم الأساسية --}}
        <div
            class="bg-gradient-to-br from-slate-950 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-10 mb-8 text-white relative overflow-hidden shadow-2xl border border-slate-800/80">
            <!-- مؤثرات الضوء الخلفية -->
            <div
                class="absolute -right-20 -top-20 w-80 h-80 bg-sky-500 rounded-full blur-[130px] opacity-10 pointer-events-none">
            </div>
            <div
                class="absolute -left-20 -bottom-20 w-80 h-80 bg-indigo-500 rounded-full blur-[130px] opacity-15 pointer-events-none">
            </div>

            <!-- العلامة المائية -->
            <div class="absolute left-6 bottom-2 opacity-5 select-none pointer-events-none hidden md:block">
                <h1 class="text-9xl font-black italic tracking-tighter text-white">OBOUR</h1>
            </div>

            <div class="relative z-10 flex flex-col lg:flex-row items-center gap-8">
                <!-- الصورة الشخصية -->
                <div class="relative group shrink-0">
                    <div
                        class="absolute inset-0 bg-gradient-to-tr from-sky-500 to-indigo-500 rounded-2xl blur opacity-30 group-hover:opacity-60 transition duration-500">
                    </div>
                    <img src="{{ $user->avatar ? asset($user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=0ea5e9&color=fff' }}"
                        class="w-28 h-28 sm:w-32 sm:h-32 rounded-2xl border-4 border-slate-900 shadow-2xl object-cover transform group-hover:scale-105 transition-all duration-500 relative z-10"
                        alt="Profile">
                    <span class="absolute top-2 right-2 flex h-4 w-4 z-20">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span
                            class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500 border-2 border-slate-950"></span>
                    </span>
                </div>

                <!-- معلومات المستخدم الأساسية -->
                <div class="text-center md:text-right flex-grow w-full">
                    <div class="flex flex-col sm:flex-row items-center justify-center md:justify-start gap-3 mb-3">
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                            {{ $user->name }}
                        </h1>

                        @if ($user->is_verified === 1)
                            <span
                                class="inline-flex items-center gap-1.5 bg-emerald-500/10 text-emerald-400 text-xs px-3 py-1 rounded-full font-bold border border-emerald-500/20">
                                <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>
                                مسافر موثق الحساب
                            </span>
                        @else
                            <span
                                class="inline-flex items-center gap-1.5 bg-amber-500/10 text-amber-400 text-xs px-3 py-1 rounded-full font-bold border border-amber-500/20">
                                <span class="w-2 h-2 bg-amber-400 rounded-full"></span>
                                حساب غير موثق
                            </span>
                        @endif
                    </div>

                    <!-- تفاصيل التواصل والبيانات -->
                    <div
                        class="flex flex-wrap justify-center md:justify-start gap-x-6 gap-y-2 items-center text-slate-300 text-xs font-semibold">
                        <div class="flex items-center gap-2 hover:text-white transition-colors">
                            <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                </path>
                            </svg>
                            <span dir="ltr">{{ $user->phone }}</span>
                        </div>
                        <span class="hidden md:block text-slate-700">•</span>
                        <div class="flex items-center gap-2 hover:text-white transition-colors">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2">
                                </path>
                            </svg>
                            <span>{{ $user->national_id }}</span>
                        </div>
                        <span class="hidden md:block text-slate-700">•</span>
                        <div class="flex items-center gap-2 hover:text-white transition-colors">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                </path>
                            </svg>
                            <span>{{ $user->email }}</span>
                        </div>
                    </div>

                    <!-- أزرار التبويبات -->
                    <div class="mt-6 flex flex-wrap justify-center md:justify-start gap-2.5">
                        <button @click="tab = 'bookings'"
                            :class="tab === 'bookings' ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/20' :
                                'bg-slate-900/80 text-slate-300 hover:bg-slate-800 border border-slate-800'"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 active:scale-95 flex items-center gap-2">
                            <span>🎫</span> التذكرة الفعالة
                        </button>
                        <button @click="tab = 'companions'"
                            :class="tab === 'companions' ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/20' :
                                'bg-slate-900/80 text-slate-300 hover:bg-slate-800 border border-slate-800'"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 active:scale-95 flex items-center gap-2">
                            <span>👨‍👩‍👧‍👦</span> المرافقون المسجلون
                            @if ($unverifiedCount > 0)
                                <span
                                    class="min-w-[20px] h-5 px-1.5 rounded-full bg-rose-500 text-white text-[10px] font-black flex items-center justify-center animate-pulse"
                                    title="مرافقون غير موثقين">{{ $unverifiedCount }}</span>
                            @endif
                        </button>
                        <button @click="tab = 'settings'"
                            :class="tab === 'settings' ? 'bg-sky-500 text-white shadow-lg shadow-sky-500/20' :
                                'bg-slate-900/80 text-slate-300 hover:bg-slate-800 border border-slate-800'"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 active:scale-95 flex items-center gap-2">
                            <span>⚙️</span> إعدادات الحساب
                        </button>
                    </div>
                </div>

                <!-- الإحصائيات الجانبية -->
                <div class="grid grid-cols-2 gap-3 w-full lg:w-auto shrink-0">
                    <div
                        class="bg-white/5 backdrop-blur-md p-4 sm:p-5 rounded-2xl border border-white/10 text-center min-w-[120px] hover:bg-white/10 transition duration-300">
                        <p class="text-3xl font-black text-white mb-1">{{ $user->trips_count }}</p>
                        <p class="text-[11px] text-slate-400 font-bold">إجمالي الرحلات</p>
                    </div>

                    <div
                        class="bg-amber-500/10 backdrop-blur-md p-4 sm:p-5 rounded-2xl border border-amber-500/20 text-center min-w-[120px] hover:bg-amber-500/15 transition duration-300">
                        <p class="text-3xl font-black text-amber-400 mb-1">{{ $user->points ?? 0 }}</p>
                        <p class="text-[11px] text-amber-300 font-bold">رصيد النقاط ✨</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- 💳 طلبات الدفع الإضافية المعلّقة (فواتير الوكالة) --}}
        @if (isset($pendingCharges) && $pendingCharges->count() > 0)
            <div class="mb-6 space-y-3">
                @foreach ($pendingCharges as $charge)
                    <div
                        class="p-4 sm:p-5 bg-amber-50 border-2 border-amber-300 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl shrink-0">💳</span>
                            <div>
                                <p class="font-black text-sm text-amber-950">
                                    طلب دفع إضافي للحجز #BK-{{ $charge->booking_id }}
                                </p>
                                <p class="text-xs font-bold text-amber-900/80 mt-1 leading-relaxed">
                                    {{ $charge->description }}
                                </p>
                            </div>
                        </div>

                        <a href="{{ route('client.extra_charge.pay', $charge->token) }}"
                            class="shrink-0 w-full sm:w-auto text-center px-5 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-black shadow-md shadow-amber-600/20 transition-all active:scale-95">
                            ادفع {{ number_format($charge->amount, 2) }} ر.س
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- تنبيه عام: مرافقون غير موثقين --}}
        @if ($unverifiedCount > 0)
            <div x-show="tab !== 'companions'"
                class="mb-6 p-4 bg-rose-50 border-2 border-rose-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">⚠️</span>
                    <div>
                        <h4 class="text-sm font-black text-rose-900">
                            لديك {{ $unverifiedCount }}
                            {{ $unverifiedCount === 1 ? 'مرافق غير موثق' : 'مرافقين غير موثقين' }}
                        </h4>
                        <p class="text-xs font-bold text-rose-700/80 mt-0.5">
                            توثيق صورة الهوية إجباري لجميع المرافقين.
                        </p>
                    </div>
                </div>
                <button type="button" @click="tab = 'companions'"
                    class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-black transition-all active:scale-95 shrink-0">
                    وثّق المرافقين الآن
                </button>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            {{-- المحتوى الرئيسي (التبويبات) --}}
            <div class="lg:col-span-8 space-y-6">

                {{-- التبويب الأول: أحدث تذكرة فعالة --}}
                <div x-show="tab === 'bookings'" class="animate-fade-in space-y-4" x-transition>
                    <div class="flex justify-between items-center mb-2">
                        <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                            <span>🎫</span> أحدث تذكرة فعالة
                        </h2>
                        @if ($latestBooking)
                            @if ($latestBooking->is_upcoming)
                                <span
                                    class="text-[11px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    تذكرة نشطة
                                </span>
                            @else
                                <span
                                    class="text-[11px] font-bold text-slate-600 bg-slate-100 border border-slate-200 px-3 py-1 rounded-full">
                                    آخر حجز
                                </span>
                            @endif
                        @endif
                    </div>

                    @if ($latestBooking)
                        @php $booking = $latestBooking; @endphp
                        <div
                            class="relative bg-white rounded-3xl shadow-sm border border-slate-200 hover:shadow-md transition-all duration-300 overflow-hidden">
                            <div class="h-1.5 bg-gradient-to-r from-sky-500 to-indigo-600"></div>

                            {{-- الهيدر العلوي للحجز --}}
                            <div
                                class="p-4 sm:px-6 bg-slate-50/70 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span
                                        class="bg-white border border-slate-200 text-slate-800 text-xs font-mono px-2.5 py-1 rounded-lg font-bold shadow-2xs">
                                        #BN-{{ $booking->id }}
                                    </span>
                                    <span
                                        class="text-[11px] font-bold px-2.5 py-1 rounded-lg 
                                        @if ($booking->payment_status === 'paid') bg-emerald-100/70 text-emerald-800
                                        @elseif($booking->payment_status === 'pending') bg-amber-100/70 text-amber-800
                                        @elseif($booking->payment_status === 'refunded') bg-indigo-100/70 text-indigo-800
                                        @else bg-rose-100/70 text-rose-800 @endif">
                                        {{ $booking->payment_text }}
                                    </span>
                                    @if ($booking->title)
                                        <span
                                            class="bg-white border border-slate-200 text-slate-600 text-xs px-2.5 py-1 rounded-lg font-medium hidden sm:inline-block">
                                            {{ $booking->title }}
                                        </span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2">
                                    @if (in_array($booking->edit_notes, ['done', 'تم التعديل']))
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold">
                                            ✓ تم التعديل
                                        </span>
                                    @elseif(!empty($booking->edit_notes))
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold">
                                            ⏳ طلب التعديل قيد المراجعة
                                        </span>
                                    @endif

                                    <span class="text-xs font-bold text-indigo-600 flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                        @if ($booking->status === 'requestC')
                                            طلب إلغاء قيد المراجعة
                                        @elseif ($booking->status === 'completed')
                                            رحلة مكتملة
                                        @elseif ($booking->status === 'ongoing')
                                            رحلة جارية
                                        @else
                                            رحلة قادمة
                                        @endif
                                    </span>
                                </div>
                            </div>

                            {{-- تفاصيل الرحلة والتاريخ --}}
                            <div class="p-5 sm:p-7 flex flex-col md:flex-row items-center justify-between gap-6">
                                <div class="flex items-center w-full md:w-auto gap-4">
                                    <div
                                        class="w-14 h-14 shrink-0 rounded-2xl flex items-center justify-center text-2xl shadow-inner border border-slate-100 
                                        {{ $booking->type == 'bus' ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600' }}">
                                        {{ $booking->type == 'bus' ? '🚌' : '✈️' }}
                                    </div>

                                    <div class="flex flex-col gap-1.5 text-right">
                                        <h3
                                            class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight">
                                            {{ $booking->route }}
                                        </h3>

                                        <div
                                            class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-600">
                                            <span class="bg-slate-100 px-2.5 py-1 rounded-lg flex items-center gap-1">
                                                📅 الذهاب: {{ $booking->date }}
                                            </span>
                                            <span class="bg-slate-100 px-2.5 py-1 rounded-lg flex items-center gap-1"
                                                dir="ltr">
                                                ⏰ {{ $booking->time }}
                                            </span>
                                        </div>

                                        @if (in_array($booking->typetrip ?? '', ['round_trip', 'ذهاب وعودة']))
                                            <div
                                                class="flex flex-wrap items-center gap-2 text-xs font-semibold text-amber-800 mt-1 bg-amber-50/70 p-2 rounded-xl border border-amber-100 w-fit">
                                                <span>↩️ العودة:</span>
                                                <span class="bg-white px-2 py-0.5 rounded-md border border-amber-200">📅
                                                    {{ $booking->return_date }}</span>
                                                <span class="bg-white px-2 py-0.5 rounded-md border border-amber-200"
                                                    dir="ltr">⏰ {{ $booking->return_time }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- المبلغ (شامل المدفوعات الإضافية المسددة) --}}
                                <div
                                    class="w-full md:w-auto text-right md:text-left bg-slate-50 md:bg-transparent p-4 md:p-0 rounded-2xl border border-slate-100 md:border-none">
                                    <span class="block text-xs font-bold text-slate-400 mb-0.5">المبلغ الإجمالي</span>
                                    <div class="flex md:block items-baseline justify-between md:justify-start">
                                        <p class="text-3xl font-black text-slate-900 tracking-tight">
                                            {{ $booking->price }}
                                            <span class="text-sm font-bold text-emerald-600">ر.س</span>
                                        </p>
                                        <div class="md:hidden text-xs font-bold text-slate-500">
                                            💳 {{ $booking->payment_method }}
                                        </div>
                                    </div>

                                    @if (($booking->extra_paid_raw ?? 0) > 0)
                                        <div
                                            class="mt-2 text-[11px] font-bold text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1.5 w-fit leading-relaxed">
                                            <span class="block">قيمة الحجز: {{ $booking->base_price }} ر.س</span>
                                            <span class="block">+ مدفوعات إضافية: {{ $booking->extra_paid }} ر.س</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- خط التقطيع المميز للحجز --}}
                            <div class="relative w-full flex items-center px-2 z-10 hidden md:flex">
                                <div class="absolute -right-3 w-6 h-6 bg-slate-50 rounded-full border-l border-slate-200">
                                </div>
                                <div class="w-full border-t-2 border-dashed border-slate-200"></div>
                                <div class="absolute -left-3 w-6 h-6 bg-slate-50 rounded-full border-r border-slate-200">
                                </div>
                            </div>

                            {{-- أزرار الإجراءات --}}
                            <div
                                class="p-4 sm:px-6 bg-slate-50/50 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-3">
                                <div class="hidden md:flex items-center gap-2 text-xs font-bold text-slate-600">
                                    <span>💳 طريقة الدفع:</span>
                                    <span class="text-slate-900 font-black">{{ $booking->payment_method }}</span>
                                </div>

                                <div class="w-full sm:w-auto flex flex-wrap items-center justify-end gap-2">
                                    @if ($booking->has_ended && $booking->status === 'completed' && $booking->payment_status === 'paid')
                                        <button type="button"
                                            @click="openRating = true; selectedTrip = {id: {{ $booking->id }}, route: @js($booking->route)}"
                                            class="w-full sm:w-auto px-4 py-2.5 bg-amber-400 hover:bg-amber-500 text-amber-950 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5 shadow-xs active:scale-95">
                                            ⭐ قيم رحلتك
                                        </button>
                                    @endif

                                    @if ($booking->status === 'upcoming' && $booking->is_upcoming)
                                        <form action="{{ route('client.cancel', $booking->id) }}" method="POST"
                                            onsubmit="return confirm('هل أنت متأكد من رغبتك في طلب إلغاء هذا الحجز؟');"
                                            class="w-full sm:w-auto inline">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="w-full sm:w-auto px-4 py-2.5 bg-white hover:bg-rose-50 text-rose-600 border border-slate-200 hover:border-rose-200 rounded-xl text-xs font-bold transition-all active:scale-95 flex justify-center items-center gap-1">
                                                إلغاء الحجز ❌
                                            </button>
                                        </form>
                                    @endif
                                    @php
                                        $bookingTripId = $booking->trip_id ?? null;
                                    @endphp

                                    @if (
                                        $bookingTripId &&
                                            in_array($booking->payment_status, ['unpaid', 'pending', 'failed']) &&
                                            !in_array($booking->status, ['cancelled', 'completed']))
                                        <a href="{{ route('client.select_seat', ['id' => $bookingTripId, 'booking_id' => $booking->id]) }}"
                                            class="w-full sm:w-auto px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-black text-center transition-all shadow-md shadow-amber-500/20 flex items-center justify-center gap-1.5 active:scale-95">
                                            إكمال الحجز / إعادة المحاولة 🔄
                                        </a>
                                    @endif

                                    @if ($booking->status === 'upcoming')
                                        <button type="button"
                                            @click="openEditModal = true; editBookingId = {{ $booking->id }}; editNotes = ''"
                                            class="w-full sm:w-auto px-4 py-2.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition-all active:scale-95 flex justify-center items-center gap-1">
                                            طلب تعديل ✏️
                                        </button>
                                    @endif

                                    @if ($booking->status === 'confirmed' || $booking->status === 'completed' || $booking->payment_status === 'paid')
                                        <a href="{{ route('client.success', ['id' => $booking->id]) }}"
                                            class="w-full sm:w-auto px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-black text-center transition-all shadow-md shadow-sky-600/20 flex items-center justify-center gap-1.5 active:scale-95">
                                            عرض التذكرة 📄
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-16 bg-white rounded-3xl border border-dashed border-slate-200 p-6">
                            <div class="text-5xl mb-3">🎫</div>
                            <h3 class="text-slate-800 font-black text-base mb-1">لا توجد تذكرة فعالة حالياً</h3>
                            <p class="text-slate-500 font-medium text-xs max-w-sm mx-auto mb-6">استكشف جميع الرحلات
                                والوجهات المتاحة عبر منصة عبور وابدأ رحلتك الآن.</p>
                            <a href="{{ route('client.trips') }}"
                                class="inline-block px-6 py-3 bg-sky-600 text-white rounded-xl text-xs font-black shadow-md shadow-sky-600/20 hover:bg-sky-700 transition-all active:scale-95">
                                احجز رحلتك الجديدة 🚀
                            </a>
                        </div>
                    @endif
                </div>

                {{-- التبويب الثاني: المرافقون المسجلون --}}
                <div x-show="tab === 'companions'" class="animate-fade-in space-y-4" x-transition>
                    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-100">
                            <div>
                                <h2 class="text-lg font-black text-slate-900">المرافقون الدائمون 👨‍👩‍👧‍👦</h2>
                                <p class="text-xs text-slate-500 font-medium mt-1">إدارة قائمة المرافقين لتسريع عملية الحجز
                                    في الرحلات القادمة</p>
                            </div>
                            <button @click="openCompanion(null)"
                                class="px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-black transition-all shadow-md shadow-sky-600/20 active:scale-95 flex items-center justify-center gap-1.5">
                                <span>+ إضافة مرافق جديد</span>
                            </button>
                        </div>

                        {{-- ملخص التوثيق --}}
                        @if ($companionsList->count() > 0)
                            @if ($unverifiedCount > 0)
                                <div
                                    class="mb-5 p-4 bg-rose-50 border-2 border-rose-200 rounded-2xl flex items-start gap-3">
                                    <span class="text-2xl shrink-0">⚠️</span>
                                    <div class="flex-1">
                                        <h4 class="text-sm font-black text-rose-900">
                                            التوثيق إجباري: {{ $unverifiedCount }} من أصل {{ $companionsList->count() }}
                                            {{ $unverifiedCount === 1 ? 'مرافق غير موثق' : 'مرافقين غير موثقين' }}
                                        </h4>
                                        <p class="text-xs font-bold text-rose-700/80 mt-1 leading-relaxed">
                                            يجب رفع صورة الهوية أو الإقامة لكل مرافق غير موثق (اضغط "وثّق الآن" في كرت
                                            المرافق).
                                        </p>
                                    </div>
                                </div>
                            @else
                                <div
                                    class="mb-5 p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-2.5 text-xs font-black text-emerald-800">
                                    <span class="text-lg">✅</span> جميع المرافقين ({{ $companionsList->count() }}) موثقون
                                </div>
                            @endif
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @forelse($companionsList as $companion)
                                @php
                                    $isUnverified = empty($companion->id_image) && empty($companion->id_document_path);
                                @endphp
                                <div
                                    class="rounded-2xl p-4 border transition-all group relative {{ $isUnverified ? 'bg-rose-50/40 border-rose-300 hover:border-rose-400' : 'bg-slate-50 border-slate-200/80 hover:border-sky-300 hover:shadow-sm' }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 font-bold flex items-center justify-center text-lg shrink-0">
                                                {{ in_array($companion->gender ?? '', ['female', 'أنثى']) ? '👩' : '👨' }}
                                            </div>
                                            <div>
                                                <h3
                                                    class="font-black text-slate-800 text-sm group-hover:text-sky-600 transition-colors flex items-center gap-2 flex-wrap">
                                                    {{ $companion->name }}
                                                    @if ($isUnverified)
                                                        <span
                                                            class="text-[9px] bg-rose-50 text-rose-600 px-1.5 py-0.5 rounded-md border border-rose-200 font-bold"
                                                            title="يرجى إرفاق صورة الهوية">غير موثق ⚠️</span>
                                                    @else
                                                        <span
                                                            class="text-[9px] bg-emerald-50 text-emerald-600 px-1.5 py-0.5 rounded-md border border-emerald-200 font-bold">موثق
                                                            ✓</span>
                                                    @endif
                                                </h3>
                                                <span
                                                    class="inline-block text-[10px] font-bold text-sky-700 bg-sky-50 px-2 py-0.5 rounded-md mt-0.5 border border-sky-100">
                                                    {{ $companion->relationship ?? ($companion->relation ?? 'مرافق') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-1">
                                            <button @click="openCompanion({{ json_encode($companion) }})"
                                                class="text-slate-400 hover:text-sky-600 transition-colors p-1"
                                                title="تعديل البيانات">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>

                                            @if (Route::has('client.companions.destroy'))
                                                <form action="{{ route('client.companions.destroy', $companion->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('هل أنت متأكد من حذف هذا المرافق؟');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="text-slate-400 hover:text-rose-600 transition-colors p-1"
                                                        title="حذف المرافق">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>

                                    <div
                                        class="mt-3 pt-3 border-t border-slate-200/60 grid grid-cols-2 gap-2 text-xs font-semibold text-slate-500">
                                        <div>
                                            <span class="block text-[10px] font-normal text-slate-400">رقم الهوية /
                                                الإقامة</span>
                                            <span class="text-slate-800 font-mono">{{ $companion->national_id }}</span>
                                        </div>
                                        @if (!empty($companion->birth_date))
                                            <div>
                                                <span class="block text-[10px] font-normal text-slate-400">تاريخ
                                                    الميلاد</span>
                                                <span
                                                    class="text-slate-800 font-mono">{{ \Carbon\Carbon::parse($companion->birth_date)->format('Y-m-d') }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    @if ($isUnverified)
                                        <button type="button" @click="openCompanion({{ json_encode($companion) }})"
                                            class="mt-3 w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-black transition-all active:scale-95 flex items-center justify-center gap-1.5">
                                            🪪 وثّق الآن (إجباري)
                                        </button>
                                    @endif
                                </div>
                            @empty
                                <div
                                    class="col-span-full text-center py-10 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200">
                                    <div class="text-3xl mb-2">👨‍👩‍👧‍👦</div>
                                    <p class="text-slate-500 font-bold text-xs">لا يوجد مرافقون مسجلون حالياً</p>
                                    <button @click="openCompanion(null)"
                                        class="mt-2 text-xs font-bold text-sky-600 hover:underline">
                                        + إضافة مرافقك الأول الآن
                                    </button>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- التبويب الثالث: إعدادات الحساب --}}
                <div x-show="tab === 'settings'" class="animate-fade-in space-y-4" x-transition>
                    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100">
                        <h2 class="text-lg font-black text-slate-900 mb-6 pb-3 border-b border-slate-100">بيانات المسافر
                            الشخصية 👤</h2>

                        <form action="{{ route('client.profile.update') }}" method="POST" class="space-y-5">
                            @csrf @method('PUT')

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الاسم الكامل <span
                                            class="text-rose-500">*</span></label>
                                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 outline-none focus:bg-white focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 transition-all">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">تاريخ الميلاد</label>
                                    <input type="date" name="birth_date"
                                        value="{{ old('birth_date', $user->birth_date) }}"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 outline-none focus:bg-white focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 transition-all">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">الجنس <span
                                            class="text-rose-500">*</span></label>
                                    <select name="gender" required
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 outline-none focus:bg-white focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 transition-all">
                                        <option value="">اختر الجنس</option>
                                        <option value="male"
                                            {{ old('gender', $user->gender) == 'male' || old('gender', $user->gender) == 'ذكر' ? 'selected' : '' }}>
                                            ذكر 👨</option>
                                        <option value="female"
                                            {{ old('gender', $user->gender) == 'female' || old('gender', $user->gender) == 'أنثى' ? 'selected' : '' }}>
                                            أنثى 👩</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">المدينة</label>
                                    <input type="text" name="city" value="{{ old('city', $user->city ?? '') }}"
                                        placeholder="مثال: الرياض"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 outline-none focus:bg-white focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 transition-all">
                                </div>
                            </div>

                            <div class="flex items-center justify-end pt-4 border-t border-slate-100">
                                <button type="submit"
                                    class="px-6 py-3 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-black transition-all shadow-md shadow-sky-600/20 active:scale-95">
                                    حفظ التغييرات
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            {{-- 3. القسم الجانبي (بطاقة التوثيق والخروج) --}}
            <div class="lg:col-span-4 space-y-6">
                <div
                    class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm text-center relative overflow-hidden">
                    <div class="relative z-10">

                        @if ($user->is_verified === 1)
                            <div
                                class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-3 border border-emerald-100">
                                ✅
                            </div>
                            <h3 class="text-base font-black text-slate-900 mb-1">الهوية/الإقامة موثقة</h3>
                            <p
                                class="text-xs text-emerald-700 font-semibold leading-relaxed mb-6 bg-emerald-50 py-2 px-3 rounded-xl border border-emerald-100">
                                تم التحقق من حسابك بنجاح وميزة الحجز المباشر تـعمل بـكامل خصائصها 🚀
                            </p>
                        @elseif($user->is_verified === 0)
                            <div
                                class="w-14 h-14 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-3 border border-amber-100">
                                🆔
                            </div>
                            <h3 class="text-base font-black text-slate-900 mb-1">توثيق الهوية/الإقامة</h3>
                            <p class="text-xs text-slate-500 leading-relaxed mb-5">ارفع صورة هويتك الوطنية أو الإقامة
                                لتأكيد الحساب ومطابقة بياناتك لدى الإدارة.</p>

                            <form action="{{ route('client.profile.id') }}" method="POST" enctype="multipart/form-data"
                                class="block mb-3">
                                @csrf
                                <label class="cursor-pointer block">
                                    <span
                                        class="block py-3 px-4 bg-sky-600 text-white rounded-xl text-xs font-black shadow-md shadow-sky-600/20 hover:bg-sky-700 transition-all">
                                        رفع صورة الهوية/الإقامة الآن 📤
                                    </span>
                                    <input type="file" name="id_image" accept="image/*" class="hidden"
                                        @change="idUploaded = true; $el.form.submit()">
                                </label>
                            </form>
                        @elseif($user->is_verified === 2)
                            <div
                                class="w-14 h-14 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-3 border border-rose-100">
                                ❌
                            </div>
                            <h3 class="text-base font-black text-slate-900 mb-1">تم رفض المستند المرفوع</h3>
                            <p
                                class="text-xs text-rose-700 font-semibold leading-relaxed mb-5 bg-rose-50 py-2.5 px-3 rounded-xl border border-rose-100">
                                تم رفض صورة الهوية لعدم وضوح البيانات، يرجى إعادة رفع صورة واضحة. ⚠️
                            </p>

                            <form action="{{ route('client.profile.id') }}" method="POST" enctype="multipart/form-data"
                                class="block mb-3">
                                @csrf
                                <label class="cursor-pointer block">
                                    <span
                                        class="block py-3 px-4 bg-sky-600 text-white rounded-xl text-xs font-black shadow-md shadow-sky-600/20 hover:bg-sky-700 transition-all">
                                        إعادة رفع الهوية 📤
                                    </span>
                                    <input type="file" name="id_image" accept="image/*" class="hidden"
                                        @change="idUploaded = true; $el.form.submit()">
                                </label>
                            </form>
                        @else
                            <div
                                class="w-14 h-14 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-3 border border-amber-100 animate-pulse">
                                ⏳
                            </div>
                            <h3 class="text-base font-black text-slate-900 mb-1">قيد المراجعة</h3>
                            <p
                                class="text-xs text-amber-700 font-semibold leading-relaxed mb-4 bg-amber-50 py-2 px-3 rounded-xl border border-amber-100">
                                تم استلام الهوية بنجاح، وهي الآن قيد تدقيق البيانات ⚙️
                            </p>

                            <div x-data="{ showIdModal: false }">
                                <div @click="showIdModal = true"
                                    class="relative rounded-xl overflow-hidden mb-4 border border-slate-200 cursor-pointer group shadow-2xs">
                                    <img src="{{ asset($user->id_image) }}"
                                        class="w-full h-24 object-cover filter blur-[4px] group-hover:scale-105 transition duration-300"
                                        alt="ID Preview">
                                    <div class="absolute inset-0 bg-slate-950/30 flex items-center justify-center">
                                        <span
                                            class="bg-white/95 text-slate-800 text-[10px] font-bold px-2.5 py-1 rounded-lg shadow-sm">
                                            🔒 معاينة المستند
                                        </span>
                                    </div>
                                </div>

                                <template x-teleport="body">
                                    <div x-show="showIdModal" x-cloak style="display: none;"
                                        @keydown.escape.window="showIdModal = false"
                                        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
                                        <div class="fixed inset-0" @click="showIdModal = false"></div>
                                        <div
                                            class="relative bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl z-10 border border-slate-100">
                                            <div
                                                class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                                                <h3 class="font-black text-slate-800 text-sm flex items-center gap-1.5">
                                                    <span>🪪</span> معاينة صورة الهوية/الإقامة
                                                </h3>
                                                <button @click="showIdModal = false" type="button"
                                                    class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 font-bold">
                                                    ✕
                                                </button>
                                            </div>
                                            <div
                                                class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-950 flex items-center justify-center">
                                                <img src="{{ asset($user->id_image) }}"
                                                    class="w-full h-auto max-h-[75vh] object-contain" alt="صورة الهوية">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        @endif

                        {{-- 💰 زر سجل المدفوعات (يفتح مودال، مخفي افتراضياً) --}}
                        <button type="button" @click="showPayments = true"
                            class="w-full mb-3 py-3 px-4 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-xl text-xs font-bold hover:bg-emerald-100 transition-all flex items-center justify-center gap-2">
                            <span>💰</span> سجل المدفوعات
                        </button>

                        <a href="#"
                            class="block py-3 px-4 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold hover:bg-rose-50 hover:text-rose-600 transition-all text-center"
                            onclick="event.preventDefault(); document.getElementById('user-logout-form').submit();">
                            تسجيل الخروج 🚪
                        </a>

                        <form id="user-logout-form" action="{{ route('client.logout') }}" method="POST"
                            class="hidden">
                            @csrf
                        </form>

                        <template x-if="idUploaded">
                            <div
                                class="mt-3 p-2.5 bg-emerald-50 text-emerald-700 rounded-xl text-xs font-bold animate-pulse flex items-center justify-center gap-1 border border-emerald-100">
                                <span>⏳ جاري معالجة ورفع المستند...</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- 💰 مودال المدفوعات (مخفي افتراضياً) --}}
        <div x-show="showPayments" x-cloak x-transition @keydown.escape.window="showPayments = false"
            class="fixed inset-0 z-[2000] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">

            <div @click.away="showPayments = false"
                class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl relative border border-slate-100 text-right max-h-[85vh] flex flex-col"
                dir="rtl">

                {{-- رأس ثابت --}}
                <div class="p-5 sm:px-6 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-3">
                        <span
                            class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg border border-emerald-100">💰</span>
                        <h3 class="text-sm font-black text-slate-900">المدفوعات وسجل العمليات</h3>
                    </div>
                    <button type="button" @click="showPayments = false"
                        class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 font-bold">
                        ✕
                    </button>
                </div>

                {{-- المحتوى (سكرول داخلي) --}}
                <div class="p-4 sm:p-6 space-y-4 overflow-y-auto">
                    {{-- ملخص --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-3.5 text-center">
                            <p class="text-[10px] font-bold text-emerald-700 mb-1">إجمالي المدفوع</p>
                            <p class="text-lg font-black text-emerald-800">{{ number_format($totalPaid, 2) }}
                                <span class="text-[10px]">ر.س</span>
                            </p>
                        </div>
                        <div class="bg-amber-50 border border-amber-100 rounded-2xl p-3.5 text-center">
                            <p class="text-[10px] font-bold text-amber-700 mb-1">مدفوعات إضافية مسددة</p>
                            <p class="text-lg font-black text-amber-800">
                                {{ number_format($extraPaidTotal, 2) }} <span class="text-[10px]">ر.س</span>
                            </p>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 text-center">
                            <p class="text-[10px] font-bold text-slate-500 mb-1">طلبات دفع معلّقة</p>
                            <p class="text-lg font-black text-slate-800">
                                {{ isset($pendingCharges) ? $pendingCharges->count() : 0 }}</p>
                        </div>
                    </div>

                    {{-- القائمة --}}
                    @forelse ($paymentsHistory as $payment)
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-2xl border {{ $payment['type'] === 'extra' ? 'bg-amber-50/40 border-amber-200' : 'bg-slate-50 border-slate-200' }}">
                            <div class="flex items-start gap-3">
                                <span
                                    class="w-9 h-9 shrink-0 rounded-xl flex items-center justify-center text-base {{ $payment['type'] === 'extra' ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700' }}">
                                    {{ $payment['type'] === 'extra' ? '➕' : '🎫' }}
                                </span>
                                <div>
                                    <p class="text-xs font-black text-slate-900 flex items-center gap-2 flex-wrap">
                                        {{ $payment['label'] }}
                                        <span class="font-mono text-[10px] text-slate-500 font-bold">
                                            #BK-{{ $payment['booking_id'] }}
                                        </span>
                                        @if ($payment['status'] === 'refunded')
                                            <span
                                                class="text-[9px] bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded-md border border-indigo-200 font-bold">تم
                                                الاسترداد</span>
                                        @else
                                            <span
                                                class="text-[9px] bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded-md border border-emerald-200 font-bold">مدفوع
                                                ✓</span>
                                        @endif
                                    </p>
                                    @if (!empty($payment['description']))
                                        <p class="text-[11px] font-semibold text-slate-500 mt-0.5 leading-relaxed">
                                            {{ $payment['description'] }}</p>
                                    @endif
                                    <p class="text-[10px] font-semibold text-slate-400 mt-1" dir="ltr"
                                        style="text-align: right">
                                        {{ $payment['date'] }}
                                        @if (!empty($payment['method']))
                                            • {{ $payment['method'] }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <p
                                class="text-base font-black shrink-0 {{ $payment['status'] === 'refunded' ? 'text-indigo-700 line-through' : 'text-slate-900' }}">
                                {{ number_format($payment['amount'], 2) }}
                                <span class="text-[10px] font-bold text-emerald-600 no-underline">ر.س</span>
                            </p>
                        </div>
                    @empty
                        <div class="text-center py-8 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200">
                            <div class="text-3xl mb-2">💰</div>
                            <p class="text-slate-500 font-bold text-xs">لا توجد مدفوعات مسجلة حتى الآن</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- 4. مودال إضافة وتعديل المرافق (موحّد، مع توثيق إجباري) --}}
        <div x-show="openCompanionModal"
            class="fixed inset-0 z-[2000] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm"
            x-transition x-cloak x-init="$watch('openCompanionModal', v => {
                if (v) {
                    idImagePreview = null;
                    isScanning = false;
                    scanSuccess = false;
                    scanError = '';
                    scanWarning = '';
                    fileChosen = false;
                }
            })" x-data="{
                idImagePreview: null,
                isScanning: false,
                scanSuccess: false,
                scanError: '',
                scanWarning: '',
                fileChosen: false,
            
                // الملف إجباري إلا إذا كان المرافق موثقاً مسبقاً وهو في وضع التعديل
                get docRequired() {
                    return !(editCompanion && companionForm.has_document);
                },
                // الحفظ مسموح فقط بعد نجاح فحص المستند، أو إذا كان موثقاً مسبقاً ولم يُرفع ملف جديد
                get canSubmit() {
                    if (this.isScanning) return false;
                    if (this.fileChosen) return !this.scanError;
                    return !this.docRequired;
                },
            
                async processIDCard(event) {
                    const file = event.target.files[0];
                    if (!file) {
                        this.fileChosen = false;
                        this.idImagePreview = null;
                        this.scanSuccess = false;
                        this.scanError = '';
                        this.scanWarning = '';
                        return;
                    }
            
                    this.fileChosen = true;
                    this.isScanning = true;
                    this.scanSuccess = false;
                    this.scanError = '';
                    this.scanWarning = '';
            
                    // 1. معاينة الصورة
                    const reader = new FileReader();
                    reader.onload = (e) => { this.idImagePreview = e.target.result; };
                    reader.readAsDataURL(file);
            
                    try {
                        // 2. معالجة الصورة وتحسين التباين
                        const img = await new Promise((resolve, reject) => {
                            const i = new Image();
                            i.onload = () => resolve(i);
                            i.onerror = reject;
                            i.src = URL.createObjectURL(file);
                        });
            
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        const maxDim = 1200;
                        let width = img.width,
                            height = img.height;
            
                        if (width > maxDim || height > maxDim) {
                            if (width > height) {
                                height = Math.round((height * maxDim) / width);
                                width = maxDim;
                            } else {
                                width = Math.round((width * maxDim) / height);
                                height = maxDim;
                            }
                        }
            
                        canvas.width = width;
                        canvas.height = height;
                        ctx.drawImage(img, 0, 0, width, height);
            
                        const imgData = ctx.getImageData(0, 0, width, height);
                        const d = imgData.data;
                        for (let i = 0; i < d.length; i += 4) {
                            let avg = (d[i] + d[i + 1] + d[i + 2]) / 3;
                            avg = avg > 120 ? 255 : (avg < 80 ? 0 : avg);
                            d[i] = d[i + 1] = d[i + 2] = avg;
                        }
                        ctx.putImageData(imgData, 0, 0);
            
                        // 3. قراءة النص عبر Tesseract
                        if (typeof Tesseract === 'undefined') {
                            this.scanWarning = '⚠️ تعذر تشغيل الفحص التلقائي. يمكنك المتابعة وسيتم تدقيق المستند من الإدارة.';
                            return;
                        }
                        const result = await Tesseract.recognize(canvas, 'ara+eng');
                        let normText = (result.data.text || '')
                            .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d))
                            .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d));
            
                        // 4.1 استخراج رقم الهوية
                        let lines = normText.split('\n');
                        let foundID = '';
                        for (let line of lines) {
                            let sanitizedLine = line
                                .replace(/[OoQ]/g, '0')
                                .replace(/[Il|!\/]/g, '1')
                                .replace(/[Zz]/g, '2')
                                .replace(/[Ss]/g, '5')
                                .replace(/B/g, '8');
            
                            let match = sanitizedLine.match(/\b[12]\d{9}\b/);
                            if (match) { foundID = match[0]; break; }
            
                            let digitsOnly = sanitizedLine.replace(/[^\d]/g, '');
                            let matchSub = digitsOnly.match(/[12]\d{9}/);
                            if (matchSub) { foundID = matchSub[0]; break; }
                        }
            
                        // 4.2 استخراج تاريخ الميلاد الميلادي (أقدم تاريخ في الهوية)
                        const dateMatches = [...normText.matchAll(/(\d{1,4})[\/\-\.\\\|](\d{1,2})[\/\-\.\\\|](\d{1,4})/g)];
                        let gregorianDates = [];
            
                        for (let m of dateMatches) {
                            let p1 = parseInt(m[1]),
                                p2 = parseInt(m[2]),
                                p3 = parseInt(m[3]);
                            let y, month, dd;
                            if (p1 >= 1930 && p1 <= 2050) {
                                y = p1;
                                month = p2;
                                dd = p3;
                            } else if (p3 >= 1930 && p3 <= 2050) {
                                y = p3;
                                month = p2;
                                dd = p1;
                            }
            
                            if (y && month >= 1 && month <= 12 && dd >= 1 && dd <= 31) {
                                gregorianDates.push({ y, month, d: dd });
                            }
                        }
            
                        let extractedBirthDate = '';
                        if (gregorianDates.length > 0) {
                            gregorianDates.sort((a, b) => (a.y * 10000 + a.month * 100 + a.d) - (b.y * 10000 + b.month * 100 + b.d));
                            let gDob = gregorianDates[0];
                            extractedBirthDate = `${gDob.y}-${String(gDob.month).padStart(2, '0')}-${String(gDob.d).padStart(2, '0')}`;
                        }
            
                        // 5. المطابقة والتعبئة التلقائية
                        let hasError = false;
                        let currentNationalId = companionForm.national_id;
                        let currentName = companionForm.name;
                        let currentBirthDate = companionForm.birth_date;
            
                        // أ. رقم الهوية
                        if (foundID) {
                            if (currentNationalId && currentNationalId !== foundID) {
                                this.scanError = `❌ رقم الهوية في المستند (${foundID}) لا يطابق الرقم المدخل (${currentNationalId})`;
                                hasError = true;
                            } else if (!currentNationalId) {
                                companionForm.national_id = foundID;
                            }
                        }
            
                        // ب. تاريخ الميلاد
                        if (!hasError && extractedBirthDate) {
                            if (currentBirthDate && currentBirthDate !== extractedBirthDate) {
                                this.scanError = `❌ تاريخ الميلاد في المستند (${extractedBirthDate}) لا يطابق التاريخ المدخل (${currentBirthDate})`;
                                hasError = true;
                            } else if (!currentBirthDate) {
                                companionForm.birth_date = extractedBirthDate;
                            }
                        }
            
                        // ج. الاسم
                        if (!hasError && currentName) {
                            let firstName = currentName.trim().split(' ')[0];
                            let cleanNormText = normText.replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي');
                            let cleanFirstName = firstName.replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي');
            
                            if (cleanFirstName.length > 2 && !cleanNormText.includes(cleanFirstName)) {
                                this.scanError = `❌ الاسم المدخل (${firstName}) غير موجود داخل مستند الهوية المرفق.`;
                                hasError = true;
                            }
                        }
            
                        if (!hasError) {
                            if (!foundID && !extractedBirthDate) {
                                // تعذرت القراءة: تحذير فقط، لا يمنع الحفظ (الملف مرفق وسيُراجع من الإدارة)
                                this.scanWarning = '⚠️ تعذر قراءة بيانات المستند تلقائياً. يمكنك المتابعة وسيتم تدقيقه من الإدارة، أو ارفع صورة أوضح.';
                            } else {
                                this.scanSuccess = true;
                            }
                        }
            
                    } catch (err) {
                        console.error('OCR Error:', err);
                        this.scanError = 'حدث خطأ أثناء قراءة المستند، يرجى اختيار صورة واضحة.';
                    } finally {
                        this.isScanning = false;
                    }
                }
            }">
            <div @click.away="openCompanionModal = false"
                class="bg-white w-full max-w-lg rounded-3xl p-6 sm:p-8 shadow-2xl relative border border-slate-100 text-right max-h-[90vh] overflow-y-auto"
                dir="rtl">

                <button type="button" @click="openCompanionModal = false"
                    class="absolute top-6 left-6 text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>

                <div class="text-center mb-6">
                    <div
                        class="w-12 h-12 bg-sky-50 text-sky-600 text-xl flex items-center justify-center rounded-2xl mx-auto mb-2 border border-sky-100">
                        👨‍👩‍👧‍👦
                    </div>
                    <h2 class="text-lg font-black text-slate-900"
                        x-text="editCompanion ? 'تعديل بيانات المرافق' : 'إضافة مرافق جديد'"></h2>
                </div>

                <form
                    :action="editCompanion ? '/client/companions/update/' + companionForm.id :
                        '{{ route('client.companions.store') }}'"
                    method="POST" enctype="multipart/form-data" class="space-y-4"
                    @submit="if (!canSubmit) { $event.preventDefault(); }">

                    @csrf
                    <template x-if="editCompanion">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">الاسم الكامل <span
                                class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="companionForm.name" required
                            placeholder="مثل: أحمد محمد علي"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 outline-none focus:bg-white focus:border-sky-500 transition-all">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">رقم الهوية / الإقامة <span
                                    class="text-rose-500">*</span></label>
                            <input type="text" name="national_id" x-model="companionForm.national_id" required
                                placeholder="10 أرقام" maxlength="10" minlength="10" pattern="[0-9]{10}"
                                inputmode="numeric"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-mono font-bold text-slate-800 outline-none focus:bg-white focus:border-sky-500 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">صلة القرابة <span
                                    class="text-rose-500">*</span></label>
                            <select name="relationship" x-model="companionForm.relationship" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 outline-none focus:bg-white focus:border-sky-500 transition-all">
                                <option value="" disabled>اختر الصلة</option>
                                <option value="أب">أب</option>
                                <option value="أم">أم</option>
                                <option value="زوج/زوجة">زوج / زوجة</option>
                                <option value="ابن/ابنة">ابن / ابنة</option>
                                <option value="أخ/أخت">أخ / أخت</option>
                                <option value="مرافق">مرافق آخر</option>
                            </select>
                        </div>
                    </div>

                    <!-- رفع وتوثيق المستند (إجباري) -->
                    <div class="border-2 rounded-2xl p-4 text-right transition-all my-2"
                        :class="scanError ? 'bg-rose-50/50 border-rose-300' :
                            (docRequired && !fileChosen ? 'bg-amber-50/50 border-amber-300' :
                                'bg-indigo-50/50 border-indigo-100')">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-1.5">
                                <span class="text-base">🪪</span>
                                <h3 class="text-xs font-bold text-slate-800">
                                    صورة الهوية / الإقامة للتوثيق
                                    <span class="text-rose-500" x-show="docRequired">* إجباري</span>
                                </h3>
                            </div>
                            <span class="text-[10px] bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-lg font-bold">فحص
                                الـ OCR ⚡</span>
                        </div>

                        <!-- حالة المرافق الموثق مسبقاً -->
                        <div x-show="editCompanion && companionForm.has_document && !fileChosen"
                            class="mb-2 text-[11px] text-emerald-700 font-bold bg-emerald-50 border border-emerald-200 rounded-lg p-2">
                            ✓ هذا المرافق موثق ولديه مستند مرفوع مسبقاً.
                        </div>

                        <div class="flex flex-col sm:flex-row items-center gap-3">
                            <label class="w-full flex-1 cursor-pointer">
                                <div
                                    class="flex items-center justify-center gap-2 px-3 py-2.5 bg-white border-2 border-dashed border-indigo-200 hover:border-indigo-500 rounded-xl transition-all shadow-sm group">
                                    <svg class="w-4 h-4 text-indigo-500 group-hover:scale-110 transition-transform"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 9a2 2 0 012-2h0.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="text-xs font-bold text-slate-700"
                                        x-text="fileChosen ? 'تغيير صورة الهوية' : 'اختر صورة الهوية للتحقق'"></span>
                                    <input type="file" name="id_document" accept="image/*" class="hidden"
                                        :required="docRequired" @change="processIDCard($event)">
                                </div>
                            </label>

                            <template x-if="idImagePreview">
                                <div
                                    class="w-10 h-10 rounded-xl border border-slate-200 overflow-hidden shrink-0 shadow-sm">
                                    <img :src="idImagePreview" class="w-full h-full object-cover">
                                </div>
                            </template>
                        </div>

                        <div x-show="isScanning"
                            class="mt-2.5 flex items-center gap-2 text-[11px] text-indigo-600 font-bold">
                            <svg class="animate-spin w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            جاري مطابقة بيانات المستند وتاريخ الميلاد تلقائياً...
                        </div>
                        <div x-show="scanSuccess"
                            class="mt-2 text-[11px] text-emerald-600 font-bold flex items-center gap-1">
                            <span>✅</span> تم التحقق وتطابق البيانات بنجاح!
                        </div>
                        <div x-show="scanWarning"
                            class="mt-2 text-[11px] text-amber-700 font-bold bg-amber-50 p-2 rounded-lg border border-amber-200"
                            x-text="scanWarning"></div>
                        <div x-show="scanError"
                            class="mt-2 text-[11px] text-rose-600 font-bold bg-rose-50 p-2 rounded-lg border border-rose-100"
                            x-text="scanError"></div>

                        <p class="text-[10px] text-rose-600 mt-2 font-bold" x-show="docRequired && !fileChosen">
                            لا يمكن حفظ المرافق بدون رفع صورة الهوية.
                        </p>
                        <template x-if="editCompanion && companionForm.has_document">
                            <p class="text-[10px] text-amber-600 mt-1.5 font-bold">ارفع صورة جديدة فقط إذا كنت
                                ترغب في تحديث المستند الحالي.</p>
                        </template>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">الجنس <span
                                    class="text-rose-500">*</span></label>
                            <select name="gender" x-model="companionForm.gender" required
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 outline-none focus:bg-white focus:border-sky-500 transition-all">
                                <option value="male">ذكر 👨</option>
                                <option value="female">أنثى 👩</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">تاريخ الميلاد</label>
                            <input type="date" name="birth_date" x-model="companionForm.birth_date"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 outline-none focus:bg-white focus:border-sky-500 transition-all">
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-3">
                        <button type="submit" :disabled="!canSubmit"
                            class="flex-1 py-3 bg-sky-600 hover:bg-sky-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white rounded-xl font-black text-xs shadow-md shadow-sky-600/20 transition-all active:scale-95"
                            x-text="isScanning ? 'جاري فحص المستند...' : (editCompanion ? 'حفظ التعديلات ✅' : 'حفظ المرافق ✅')">
                        </button>
                        <button type="button" @click="openCompanionModal = false"
                            class="px-5 py-3 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-xl font-bold text-xs transition-all">
                            إلغاء
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- 5. نافذة التقييم المنبثقة --}}
        <div x-show="openRating"
            class="fixed inset-0 z-[2000] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm"
            x-transition x-cloak x-data="{
                ratings: { accommodation: 0, meals: 0, staff: 0, organization: 0, price_value: 0, transport: 0, services: 0 },
                hovers: { accommodation: 0, meals: 0, staff: 0, organization: 0, price_value: 0, transport: 0, services: 0 }
            }">

            <div @click.away="openRating = false"
                class="bg-white w-full max-w-2xl rounded-3xl p-6 sm:p-8 shadow-2xl relative border border-slate-100 max-h-[90vh] overflow-y-auto text-right"
                dir="rtl">

                <button @click="openRating = false"
                    class="absolute top-6 left-6 text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>

                <div class="text-center mb-6">
                    <div
                        class="w-12 h-12 bg-amber-50 text-amber-500 text-xl flex items-center justify-center rounded-2xl mx-auto mb-2 border border-amber-100">
                        ⭐
                    </div>
                    <h2 class="text-lg font-black text-slate-900">تقييم تجربة الرحلة مع الوكالة</h2>
                    <p class="text-indigo-600 text-xs font-bold mt-1 bg-indigo-50 inline-block px-3 py-1 rounded-full"
                        x-text="selectedTrip.route"></p>
                </div>

                <form action="{{ route('client.ratings') }}" method="POST" class="space-y-5">
                    @csrf
                    <input type="hidden" name="booking_id" :value="selectedTrip.id">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <span class="text-xs font-bold text-slate-700">🏢 السكن الإقامي</span>
                            <div class="flex gap-1" dir="ltr">
                                <template x-for="i in 5">
                                    <button type="button" @mouseenter="hovers.accommodation = i"
                                        @mouseleave="hovers.accommodation = 0" @click="ratings.accommodation = i"
                                        class="text-xl transition transform hover:scale-110 focus:outline-none"
                                        :class="(hovers.accommodation >= i || ratings.accommodation >= i) ? 'text-amber-400' :
                                        'text-slate-200'">★</button>
                                </template>
                            </div>
                            <input type="hidden" name="accommodation_rating" :value="ratings.accommodation">
                        </div>

                        <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <span class="text-xs font-bold text-slate-700">🍽️ الوجبات الغذائية</span>
                            <div class="flex gap-1" dir="ltr">
                                <template x-for="i in 5">
                                    <button type="button" @mouseenter="hovers.meals = i" @mouseleave="hovers.meals = 0"
                                        @click="ratings.meals = i"
                                        class="text-xl transition transform hover:scale-110 focus:outline-none"
                                        :class="(hovers.meals >= i || ratings.meals >= i) ? 'text-amber-400' : 'text-slate-200'">★</button>
                                </template>
                            </div>
                            <input type="hidden" name="meals_rating" :value="ratings.meals">
                        </div>

                        <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <span class="text-xs font-bold text-slate-700">👨‍💼 كادر العمل والمشرفين</span>
                            <div class="flex gap-1" dir="ltr">
                                <template x-for="i in 5">
                                    <button type="button" @mouseenter="hovers.staff = i" @mouseleave="hovers.staff = 0"
                                        @click="ratings.staff = i"
                                        class="text-xl transition transform hover:scale-110 focus:outline-none"
                                        :class="(hovers.staff >= i || ratings.staff >= i) ? 'text-amber-400' : 'text-slate-200'">★</button>
                                </template>
                            </div>
                            <input type="hidden" name="staff_rating" :value="ratings.staff">
                        </div>

                        <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <span class="text-xs font-bold text-slate-700">📅 التنظيم والالتزام</span>
                            <div class="flex gap-1" dir="ltr">
                                <template x-for="i in 5">
                                    <button type="button" @mouseenter="hovers.organization = i"
                                        @mouseleave="hovers.organization = 0" @click="ratings.organization = i"
                                        class="text-xl transition transform hover:scale-110 focus:outline-none"
                                        :class="(hovers.organization >= i || ratings.organization >= i) ? 'text-amber-400' :
                                        'text-slate-200'">★</button>
                                </template>
                            </div>
                            <input type="hidden" name="organization_rating" :value="ratings.organization">
                        </div>

                        <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <span class="text-xs font-bold text-slate-700">💰 السعر مقابل الخدمة</span>
                            <div class="flex gap-1" dir="ltr">
                                <template x-for="i in 5">
                                    <button type="button" @mouseenter="hovers.price_value = i"
                                        @mouseleave="hovers.price_value = 0" @click="ratings.price_value = i"
                                        class="text-xl transition transform hover:scale-110 focus:outline-none"
                                        :class="(hovers.price_value >= i || ratings.price_value >= i) ? 'text-amber-400' :
                                        'text-slate-200'">★</button>
                                </template>
                            </div>
                            <input type="hidden" name="price_value_rating" :value="ratings.price_value">
                        </div>

                        <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <span class="text-xs font-bold text-slate-700">🚌 جودة وراحة النقل</span>
                            <div class="flex gap-1" dir="ltr">
                                <template x-for="i in 5">
                                    <button type="button" @mouseenter="hovers.transport = i"
                                        @mouseleave="hovers.transport = 0" @click="ratings.transport = i"
                                        class="text-xl transition transform hover:scale-110 focus:outline-none"
                                        :class="(hovers.transport >= i || ratings.transport >= i) ? 'text-amber-400' :
                                        'text-slate-200'">★</button>
                                </template>
                            </div>
                            <input type="hidden" name="transport_rating" :value="ratings.transport">
                        </div>

                        <div
                            class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-100 md:col-span-2">
                            <span class="text-xs font-bold text-slate-700">🛠️ الخدمات الإضافية</span>
                            <div class="flex gap-1" dir="ltr">
                                <template x-for="i in 5">
                                    <button type="button" @mouseenter="hovers.services = i"
                                        @mouseleave="hovers.services = 0" @click="ratings.services = i"
                                        class="text-xl transition transform hover:scale-110 focus:outline-none"
                                        :class="(hovers.services >= i || ratings.services >= i) ? 'text-amber-400' :
                                        'text-slate-200'">★</button>
                                </template>
                            </div>
                            <input type="hidden" name="services_rating" :value="ratings.services">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">ملاحظات إضافية (اختياري)</label>
                        <textarea rows="3" name="comment" placeholder="اكتب ملاحظاتك لمساعدتنا في تحسين خدمات الرحلات..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-medium focus:bg-white focus:border-indigo-500 outline-none transition-all resize-none text-right"></textarea>
                    </div>

                    <button type="submit"
                        class="w-full py-3.5 bg-slate-900 hover:bg-sky-600 text-white rounded-xl font-black text-xs shadow-md transition-all active:scale-95">
                        إرسال التقييم ✨
                    </button>
                </form>
            </div>
        </div>

        {{-- 6. نافذة طلب التعديل المنبثقة --}}
        <div x-show="openEditModal" x-cloak x-transition
            class="fixed inset-0 z-[2000] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">

            <div @click.away="openEditModal = false" @keydown.escape.window="openEditModal = false"
                class="bg-white w-full max-w-lg rounded-3xl p-6 sm:p-8 shadow-2xl relative border border-slate-100 text-right"
                dir="rtl">

                <button type="button" @click="openEditModal = false"
                    class="absolute top-6 left-6 text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>

                <div class="text-center mb-6">
                    <div
                        class="w-12 h-12 bg-amber-50 text-amber-500 text-xl flex items-center justify-center rounded-2xl mx-auto mb-2 border border-amber-100">
                        ✏️
                    </div>
                    <h2 class="text-lg font-black text-slate-900">طلب تعديل الحجز</h2>
                    <p x-show="editBookingId"
                        class="text-amber-700 text-xs font-bold mt-1 bg-amber-50 inline-block px-3 py-0.5 rounded-full"
                        x-text="'رقم الحجز: #' + editBookingId"></p>
                </div>

                <form
                    @submit.prevent="
                    if (!editNotes.trim()) return;
                    editLoading = true;
                    fetch('{{ route('client.booking.update-request') }}', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            booking_id: editBookingId,
                            edit_notes: editNotes
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message || 'تم حفظ طلب التعديل بنجاح!');
                            openEditModal = false;
                            location.reload();
                        } else {
                            alert(data.message || 'حدث خطأ أثناء الحفظ.');
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        alert('حدث خطأ في الاتصال بالسيرفر.');
                    })
                    .finally(() => editLoading = false);
                "
                    class="space-y-4">

                    <div>
                        <div class="flex justify-between items-center mb-1.5 text-right">
                            <label class="block text-xs font-bold text-slate-700">تفاصيل التعديل المطلوبة</label>
                            <span class="text-[10px] font-bold text-slate-400"
                                x-text="(40 - editNotes.length) + ' حرف متبقي'"></span>
                        </div>

                        <textarea x-model="editNotes" rows="3" required maxlength="40"
                            placeholder="اكتب تفاصيل التعديل (40 حرف كحد أقصى)..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-medium focus:bg-white focus:border-amber-500 outline-none transition-all resize-none text-right"></textarea>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" @click="openEditModal = false"
                            class="w-1/3 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs transition-all">
                            إلغاء
                        </button>

                        <button type="submit" :disabled="editLoading || editNotes.length > 40"
                            class="w-2/3 py-3 bg-slate-900 hover:bg-amber-600 text-white rounded-xl font-black text-xs shadow-md transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                            <span x-show="!editLoading">إرسال طلب التعديل ✨</span>
                            <span x-show="editLoading" x-cloak class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                جاري الإرسال...
                            </span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>

    {{-- التنسيقات والأنيميشن --}}
    <style>
        [x-cloak] {
            display: none !important;
        }

        .animate-fade-in {
            animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
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