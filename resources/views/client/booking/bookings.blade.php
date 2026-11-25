@extends('layouts.client')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div x-data="{
            statusFilter: 'all',
            currentPage: 1,
            itemsPerPage: 4,
            bookingsData: {{ json_encode(
                $bookings->map(
                    fn($b) => [
                        'id' => $b->id,
                        'status' => (string) ($b->status ?? 'pending'),
                        'payment_status' => (string) ($b->payment_status ?? 'unpaid'),
                    ],
                ),
            ) }},
        
            openRating: false,
            selectedTrip: {},
            openEditModal: false,
            editBookingId: null,
            editNotes: '',
            editLoading: false,
        
            get filteredBookings() {
                return this.bookingsData.filter(b => {
                    if (this.statusFilter === 'all') return true;
                    if (this.statusFilter === 'upcoming') return b.status === 'upcoming' || b.status === 'confirmed' || b.status === 'requestC';
                    if (this.statusFilter === 'done') return b.status === 'completed' && b.payment_status === 'paid';
                    if (this.statusFilter === 'refunded') return b.payment_status === 'refunded';
                    if (this.statusFilter === 'cancelled') return b.status === 'cancelled';
                    return false;
                });
            },
        
            get totalPages() {
                return Math.ceil(this.filteredBookings.length / this.itemsPerPage) || 1;
            },
        
            get paginatedIds() {
                const start = (this.currentPage - 1) * this.itemsPerPage;
                return this.filteredBookings.slice(start, start + this.itemsPerPage).map(b => b.id);
            },
        
            isVisible(id) {
                return this.paginatedIds.includes(id);
            }
        }" class="space-y-6 animate-fade-in max-w-6xl mx-auto">

            {{-- 5. نافذة التقييم المنبثقة --}}
            <div x-show="openRating"
                class="fixed inset-0 z-[2000] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-md"
                x-transition x-cloak x-data="{
                    ratings: { accommodation: 0, meals: 0, staff: 0, organization: 0, price_value: 0, transport: 0, services: 0 },
                    hovers: { accommodation: 0, meals: 0, staff: 0, organization: 0, price_value: 0, transport: 0, services: 0 }
                }">

                <div @click.away="openRating = false"
                    class="bg-white w-full max-w-2xl rounded-[2.5rem] p-6 sm:p-8 shadow-2xl relative animate-scale-up border border-slate-100 max-h-[90vh] overflow-y-auto text-right"
                    dir="rtl">

                    <button type="button" @click="openRating = false"
                        class="absolute top-6 left-6 text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </button>

                    <div class="text-center mb-6">
                        <div
                            class="w-14 h-14 bg-amber-50 text-amber-500 text-2xl flex items-center justify-center rounded-2xl mx-auto mb-3 shadow-inner">
                            ⭐
                        </div>
                        <h2 class="text-xl font-black text-slate-900">تقييم تجربة الرحلة مع الوكالة</h2>
                        <p class="text-indigo-600 text-xs font-bold mt-1.5 bg-indigo-50 inline-block px-3 py-1 rounded-full"
                            x-text="selectedTrip.route"></p>
                    </div>

                    <form action="{{ route('client.ratings') }}" method="POST" class="space-y-6">
                        @csrf
                        <input type="hidden" name="booking_id" :value="selectedTrip.id">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                class="flex items-center justify-between bg-slate-50/80 p-3.5 rounded-2xl border border-slate-100">
                                <span class="text-xs font-black text-slate-700">🏢 السكن الإقامي</span>
                                <div class="flex gap-1" dir="ltr">
                                    <template x-for="i in 5">
                                        <button type="button" @mouseenter="hovers.accommodation = i"
                                            @mouseleave="hovers.accommodation = 0" @click="ratings.accommodation = i"
                                            class="text-2xl transition-all transform hover:scale-110 focus:outline-none"
                                            :class="(hovers.accommodation >= i || ratings.accommodation >= i) ?
                                            'text-amber-400' : 'text-slate-200'">★</button>
                                    </template>
                                </div>
                                <input type="hidden" name="accommodation_rating" :value="ratings.accommodation">
                            </div>

                            <div
                                class="flex items-center justify-between bg-slate-50/80 p-3.5 rounded-2xl border border-slate-100">
                                <span class="text-xs font-black text-slate-700">🍽️ الوجبات الغذائية</span>
                                <div class="flex gap-1" dir="ltr">
                                    <template x-for="i in 5">
                                        <button type="button" @mouseenter="hovers.meals = i" @mouseleave="hovers.meals = 0"
                                            @click="ratings.meals = i"
                                            class="text-2xl transition-all transform hover:scale-110 focus:outline-none"
                                            :class="(hovers.meals >= i || ratings.meals >= i) ? 'text-amber-400' :
                                            'text-slate-200'">★</button>
                                    </template>
                                </div>
                                <input type="hidden" name="meals_rating" :value="ratings.meals">
                            </div>

                            <div
                                class="flex items-center justify-between bg-slate-50/80 p-3.5 rounded-2xl border border-slate-100">
                                <span class="text-xs font-black text-slate-700">👨‍💼 كادر العمل والمشرفين</span>
                                <div class="flex gap-1" dir="ltr">
                                    <template x-for="i in 5">
                                        <button type="button" @mouseenter="hovers.staff = i" @mouseleave="hovers.staff = 0"
                                            @click="ratings.staff = i"
                                            class="text-2xl transition-all transform hover:scale-110 focus:outline-none"
                                            :class="(hovers.staff >= i || ratings.staff >= i) ? 'text-amber-400' :
                                            'text-slate-200'">★</button>
                                    </template>
                                </div>
                                <input type="hidden" name="staff_rating" :value="ratings.staff">
                            </div>

                            <div
                                class="flex items-center justify-between bg-slate-50/80 p-3.5 rounded-2xl border border-slate-100">
                                <span class="text-xs font-black text-slate-700">📅 التنظيم والالتزام بالوقت</span>
                                <div class="flex gap-1" dir="ltr">
                                    <template x-for="i in 5">
                                        <button type="button" @mouseenter="hovers.organization = i"
                                            @mouseleave="hovers.organization = 0" @click="ratings.organization = i"
                                            class="text-2xl transition-all transform hover:scale-110 focus:outline-none"
                                            :class="(hovers.organization >= i || ratings.organization >= i) ? 'text-amber-400' :
                                            'text-slate-200'">★</button>
                                    </template>
                                </div>
                                <input type="hidden" name="organization_rating" :value="ratings.organization">
                            </div>

                            <div
                                class="flex items-center justify-between bg-slate-50/80 p-3.5 rounded-2xl border border-slate-100">
                                <span class="text-xs font-black text-slate-700">💰 قيمة السعر مقابل الخدمة</span>
                                <div class="flex gap-1" dir="ltr">
                                    <template x-for="i in 5">
                                        <button type="button" @mouseenter="hovers.price_value = i"
                                            @mouseleave="hovers.price_value = 0" @click="ratings.price_value = i"
                                            class="text-2xl transition-all transform hover:scale-110 focus:outline-none"
                                            :class="(hovers.price_value >= i || ratings.price_value >= i) ? 'text-amber-400' :
                                            'text-slate-200'">★</button>
                                    </template>
                                </div>
                                <input type="hidden" name="price_value_rating" :value="ratings.price_value">
                            </div>

                            <div
                                class="flex items-center justify-between bg-slate-50/80 p-3.5 rounded-2xl border border-slate-100">
                                <span class="text-xs font-black text-slate-700">🚌 جودة وراحة وسائل النقل</span>
                                <div class="flex gap-1" dir="ltr">
                                    <template x-for="i in 5">
                                        <button type="button" @mouseenter="hovers.transport = i"
                                            @mouseleave="hovers.transport = 0" @click="ratings.transport = i"
                                            class="text-2xl transition-all transform hover:scale-110 focus:outline-none"
                                            :class="(hovers.transport >= i || ratings.transport >= i) ? 'text-amber-400' :
                                            'text-slate-200'">★</button>
                                    </template>
                                </div>
                                <input type="hidden" name="transport_rating" :value="ratings.transport">
                            </div>

                            <div
                                class="flex items-center justify-between bg-slate-50/80 p-3.5 rounded-2xl border border-slate-100 md:col-span-2">
                                <span class="text-xs font-black text-slate-700">🛠️ الخدمات الإضافية العامة</span>
                                <div class="flex gap-1" dir="ltr">
                                    <template x-for="i in 5">
                                        <button type="button" @mouseenter="hovers.services = i"
                                            @mouseleave="hovers.services = 0" @click="ratings.services = i"
                                            class="text-2xl transition-all transform hover:scale-110 focus:outline-none"
                                            :class="(hovers.services >= i || ratings.services >= i) ? 'text-amber-400' :
                                            'text-slate-200'">★</button>
                                    </template>
                                </div>
                                <input type="hidden" name="services_rating" :value="ratings.services">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 mr-1 mb-2">ملاحظات إضافية
                                (اختياري)</label>
                            <textarea rows="3" name="comment" placeholder="اكتب لنا بالتفصيل عن تجربتك لمساعدتنا في تحسين الجودة..."
                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-4 text-sm focus:bg-white focus:border-indigo-500 outline-none transition-all resize-none font-medium text-right shadow-inner"></textarea>
                        </div>

                        <button type="submit"
                            class="w-full py-4 bg-slate-900 text-white hover:bg-indigo-600 rounded-2xl font-black text-sm shadow-lg transition-all transform active:scale-[0.99]">
                            إرسال التقييم الشامل ✨
                        </button>
                    </form>
                </div>
            </div>

            {{-- 6. نافذة طلب التعديل المنبثقة --}}
            <div x-show="openEditModal" x-cloak x-transition
                class="fixed inset-0 z-[2000] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-md">

                <div @click.away="openEditModal = false" @keydown.escape.window="openEditModal = false"
                    class="bg-white w-full max-w-lg rounded-[2.5rem] p-6 sm:p-8 shadow-2xl relative animate-scale-up border border-slate-100 max-h-[90vh] overflow-y-auto text-right"
                    dir="rtl">

                    <button type="button" @click="openEditModal = false"
                        class="absolute top-6 left-6 text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    <div class="text-center mb-6">
                        <div
                            class="w-14 h-14 bg-amber-50 text-amber-500 text-2xl flex items-center justify-center rounded-2xl mx-auto mb-3 shadow-inner">
                            ✏️
                        </div>
                        <h2 class="text-xl font-black text-slate-900">طلب تعديل الحجز</h2>
                        <p x-show="editBookingId"
                            class="text-amber-700 text-xs font-bold mt-1.5 bg-amber-50 inline-block px-3 py-1 rounded-full"
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
                        class="space-y-6">

                        <div>
                            <div class="flex justify-between items-center mb-2 text-right" dir="rtl">
                                <label class="block text-xs font-bold text-slate-700">تفاصيل التعديل المطلوبة</label>
                                <span class="text-[10px] font-bold text-slate-400"
                                    x-text="(40 - editNotes.length) + ' حرف متبقي'">
                                </span>
                            </div>

                            <textarea x-model="editNotes" rows="3" required maxlength="40"
                                placeholder="اكتب التعديل (40 حرفاً كحد أقصى)..."
                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-4 text-sm focus:bg-white focus:border-amber-500 outline-none transition-all resize-none font-medium text-right shadow-inner"></textarea>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="button" @click="openEditModal = false"
                                class="w-1/3 py-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl font-bold text-sm transition-all">
                                إلغاء
                            </button>

                            <button type="submit" :disabled="editLoading || editNotes.length > 40"
                                class="w-2/3 py-4 bg-slate-900 hover:bg-amber-600 text-white rounded-2xl font-black text-sm shadow-lg transition-all transform active:scale-[0.99] disabled:opacity-50 flex items-center justify-center gap-2">
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
                                    جاري الحفظ...
                                </span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            <!-- ترويسة الصفحة وشريط التصفية -->
            <div
                class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200/80 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">سجل الحجوزات والتذاكر 🎫</h1>
                    <p class="text-xs font-bold text-slate-500 mt-1">إدارة رحلاتك وتفاصيل تذاكر السفر بكل سهولة</p>
                </div>

                <!-- أزرار التصفية -->
                <div class="bg-slate-100 p-1.5 rounded-2xl flex gap-1 flex-wrap w-full md:w-auto">
                    <button @click="statusFilter = 'all'; currentPage = 1"
                        :class="statusFilter === 'all' ? 'bg-white shadow-sm text-slate-900' :
                            'text-slate-600 hover:text-slate-900'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all">الكل</button>

                    <button @click="statusFilter = 'upcoming'; currentPage = 1"
                        :class="statusFilter === 'upcoming' ? 'bg-white shadow-sm text-slate-900' :
                            'text-slate-600 hover:text-slate-900'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all">النشطة</button>

                    <button @click="statusFilter = 'done'; currentPage = 1"
                        :class="statusFilter === 'done' ? 'bg-white shadow-sm text-slate-900' :
                            'text-slate-600 hover:text-slate-900'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all">المكتملة</button>

                    <button @click="statusFilter = 'refunded'; currentPage = 1"
                        :class="statusFilter === 'refunded' ? 'bg-white shadow-sm text-slate-900' :
                            'text-slate-600 hover:text-slate-900'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all">المستردة</button>

                    <button @click="statusFilter = 'cancelled'; currentPage = 1"
                        :class="statusFilter === 'cancelled' ? 'bg-white shadow-sm text-slate-900' :
                            'text-slate-600 hover:text-slate-900'"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all">الملغية</button>
                </div>
            </div>

            <!-- قائمة بطاقات الحجز -->
            @forelse($bookings as $booking)
                @php
                    $trip = $booking->trip;

                    $routeTitle =
                        $trip->title ??
                        ($trip->route ??
                            (($trip->from ?? '') . ' ➔ ' . ($trip->to ?? '') ?? 'تفاصيل الرحلة غير متوفرة'));

                    $tripType = $trip->type ?? 'bus';

                    // المبلغ الأساسي + الدفعات الإضافية المسددة
                    $baseAmount = (float) ($booking->total_amount ?? 0);
                    $extraPaid = (float) ($booking->extra_paid ?? 0);
                    $displayTotal = (float) ($booking->display_total ?? $baseAmount + $extraPaid);
                @endphp

                <div x-show="isVisible({{ $booking->id }})"
                    class="bg-white rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-xl hover:border-indigo-200 transition-all duration-300 overflow-hidden relative group">

                    <!-- شريط الحالة العلوي الملون -->
                    <div
                        class="h-1.5 w-full 
                    @if ($booking->status == 'completed') bg-emerald-500
                    @elseif($booking->status == 'cancelled') bg-rose-500
                    @else bg-sky-500 @endif">
                    </div>

                    <!-- ترويسة البطاقة -->
                    <div
                        class="p-4 md:px-6 bg-slate-50/70 border-b border-slate-100 flex flex-wrap justify-between items-center gap-3">
                        <div class="flex items-center gap-2">
                            <span
                                class="bg-white border border-slate-200 text-slate-700 text-xs font-mono px-3 py-1 rounded-xl font-black shadow-sm">
                                BN-{{ $booking->id }}
                            </span>

                            <span
                                class="text-[11px] font-black px-3 py-1 rounded-xl 
                            @if ($booking->payment_status === 'paid') bg-emerald-100/70 text-emerald-700 border border-emerald-200/50
                            @elseif($booking->payment_status === 'pending') bg-amber-100/70 text-amber-700 border border-amber-200/50
                            @elseif($booking->payment_status === 'refunded') bg-indigo-100/70 text-indigo-700 border border-indigo-200/50
                            @else bg-rose-100/70 text-rose-700 border border-rose-200/50 @endif">
                                @if ($booking->payment_status === 'paid')
                                    تم الدفع
                                @elseif($booking->payment_status === 'refunded')
                                    مسترد
                                @elseif($booking->payment_status === 'pending')
                                    قيد الانتظار
                                @else
                                    غير مدفوع
                                @endif
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($booking->edit_notes === 'done')
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-xs font-bold">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    تم تعديل التذكرة
                                </span>
                            @elseif(!empty($booking->edit_notes))
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-xs font-bold">
                                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                    طلب التعديل قيد المراجعة
                                </span>
                            @endif

                            <span
                                class="text-xs font-black flex items-center gap-1.5 px-3 py-1 rounded-xl bg-white border border-slate-200 shadow-sm
                            @if ($booking->status == 'completed') text-emerald-600
                            @elseif($booking->status == 'cancelled') text-rose-600
                            @else text-sky-600 @endif">
                                <span
                                    class="w-2 h-2 rounded-full animate-pulse
                                @if ($booking->status == 'completed') bg-emerald-500
                                @elseif($booking->status == 'cancelled') bg-rose-500
                                @else bg-sky-500 @endif"></span>
                                @if ($booking->status == 'cancelled' || $booking->payment_status == 'refunded')
                                    ملغية
                                @elseif ($booking->status == 'completed')
                                    تم الانتهاء
                                @elseif ($booking->status == 'requestC')
                                    طلب إلغاء
                                @else
                                    مؤكد
                                @endif
                            </span>
                        </div>
                    </div>

                    <!-- محتوى البطاقة الرئيسي -->
                    <div class="p-5 md:p-7 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                        <div class="flex items-start md:items-center gap-4 w-full md:w-auto">
                            {{-- نوع وسيلة النقل --}}
                            <div
                                class="w-14 h-14 shrink-0 rounded-2xl flex items-center justify-center text-2xl shadow-inner border border-slate-100 {{ ($trip->transport_type ?? 'bus') == 'bus' ? 'bg-amber-50 text-amber-600' : 'bg-sky-50 text-sky-600' }}">
                                {{ ($trip->transport_type ?? 'bus') == 'bus' ? '🚌' : '✈️' }}
                            </div>

                            <div class="flex flex-col gap-2">
                                {{-- عنوان الرحلة / خط السير --}}
                                <h3 class="text-lg md:text-xl font-black text-slate-800 tracking-tight">
                                    {{ $trip->title ?? ($routeTitle ?? $trip->from_city . ' ➔ ' . $trip->to_city) }}
                                </h3>

                                @if ($trip)
                                    <div class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-600">
                                        {{-- تاريخ ووقت الانطلاق من حقل departure_at --}}
                                        @if (!empty($trip->departure_at))
                                            @php
                                                $departureDate = \Carbon\Carbon::parse($trip->departure_at)->format(
                                                    'Y-m-d',
                                                );
                                                $departureTime = \Carbon\Carbon::parse($trip->departure_at)->format(
                                                    'h:i A',
                                                );
                                            @endphp
                                            <span
                                                class="bg-slate-100/80 px-3 py-1.5 rounded-xl border border-slate-200/60 flex items-center gap-1">
                                                📅 {{ $departureDate }}
                                            </span>
                                            <span
                                                class="bg-slate-100/80 px-3 py-1.5 rounded-xl border border-slate-200/60 flex items-center gap-1"
                                                dir="ltr">
                                                ⏰ {{ $departureTime }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- تفاصيل العودة للرحلات الذهاب والعودة --}}
                                    @if (($trip->trip_type ?? '') === 'round_trip' && !empty($trip->return_at))
                                        @php
                                            $returnDate = \Carbon\Carbon::parse($trip->return_at)->format('Y-m-d');
                                            $returnTime = \Carbon\Carbon::parse($trip->return_at)->format('h:i A');
                                        @endphp
                                        <div
                                            class="flex flex-wrap items-center gap-2 text-xs font-bold text-amber-900 bg-amber-50/60 p-2 rounded-xl border border-amber-200/60 w-fit mt-1">
                                            <span>↩️ العودة:</span>
                                            <span class="bg-white px-2.5 py-1 rounded-lg border border-amber-200/80">
                                                📅 {{ $returnDate }}
                                            </span>
                                            <span class="bg-white px-2.5 py-1 rounded-lg border border-amber-200/80"
                                                dir="ltr">
                                                ⏰ {{ $returnTime }}
                                            </span>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <!-- السعر الإجمالي (شامل الدفعات الإضافية المسددة) -->
                        <div
                            class="w-full md:w-auto bg-slate-50 md:bg-transparent p-4 md:p-0 rounded-2xl border border-slate-100 md:border-none">
                            <div class="flex md:block justify-between items-center">
                                <span class="block text-xs font-bold text-slate-400 mb-1">التكلفة الإجمالية</span>
                                <div class="text-right">
                                    <span class="text-3xl font-black text-slate-900 tracking-tight">
                                        {{ number_format($displayTotal, 2) }}
                                    </span>
                                    <span class="text-sm font-black text-emerald-600 mr-1">ر.س</span>
                                </div>
                            </div>

                            @if ($extraPaid > 0)
                                <div
                                    class="mt-2 text-[11px] font-bold text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1.5 w-fit leading-relaxed">
                                    <span class="block">قيمة الحجز: {{ number_format($baseAmount, 2) }} ر.س</span>
                                    <span class="block">+ مدفوعات إضافية: {{ number_format($extraPaid, 2) }} ر.س</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- خط الفصل المُنقّط -->
                    <div class="relative w-full flex items-center px-4 z-10 hidden md:flex">
                        <div class="absolute -right-3 w-6 h-6 bg-slate-100 rounded-full border-l border-slate-200"></div>
                        <div class="w-full border-t-2 border-dashed border-slate-200"></div>
                        <div class="absolute -left-3 w-6 h-6 bg-slate-100 rounded-full border-r border-slate-200"></div>
                    </div>

                    <!-- أسفل البطاقة (الإجراءات وطريقة الدفع) -->
                    <div
                        class="p-4 md:px-6 md:py-4 bg-slate-50/50 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="hidden md:flex items-center gap-2 text-xs font-bold text-slate-500">
                            <span>طريقة الدفع:</span>
                            <span
                                class="text-slate-900 font-black bg-white px-2.5 py-1 rounded-lg border border-slate-200">
                                💳 {{ $booking->payment_method ?? 'غير محددة' }}
                            </span>
                        </div>

                        <div class="w-full sm:w-auto flex flex-wrap items-center justify-end gap-2.5">

                            {{-- 1. زر التقييم المعدّل --}}
                            @if ($booking->status === 'completed' && $booking->payment_status === 'paid')
                                @if (!empty($booking->rating) || !empty($booking->is_rated))
                                    <span
                                        class="w-full sm:w-auto px-4 py-2.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-black flex items-center justify-center gap-1.5">
                                        تم التقييم ⭐️
                                    </span>
                                @else
                                    <button type="button"
                                        @click="openRating = true; selectedTrip = {id: {{ $booking->id }}, route: '{{ addslashes($routeTitle) }}'}"
                                        class="w-full sm:w-auto px-5 py-2.5 bg-amber-400 hover:bg-amber-500 text-amber-950 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5 shadow-sm active:scale-95">
                                        ⭐ قيم الرحلة
                                    </button>
                                @endif
                            @endif

                            {{-- زر إلغاء الحجز --}}
                            @if (in_array($booking->status, ['upcoming', 'pending', 'confirmed', 'approved', 'paid']))
                                <form action="{{ route('client.cancel', $booking->id) }}" method="POST"
                                    onsubmit="return confirm('هل أنت متأكد من رغبتك في تقديم طلب إلغاء لهذا الحجز؟');"
                                    class="w-full sm:w-auto inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="w-full sm:w-auto px-4 py-2.5 bg-white hover:bg-rose-50 text-rose-600 border border-slate-200 hover:border-rose-200 rounded-xl text-xs font-black transition-all active:scale-95">
                                        إلغاء الحجز ❌
                                    </button>
                                </form>
                            @elseif($booking->status === 'requestC')
                                <span
                                    class="px-3 py-1.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-xl text-xs font-bold">
                                    طلب الإلغاء قيد المراجعة ⏳
                                </span>
                            @endif
                            {{-- زر إعادة المحاولة / إكمال الحجز في حال فشل الدفع أو بقائه غير مدفوع --}}
                            @if (in_array($booking->payment_status, ['unpaid', 'pending', 'failed']) &&
                                    !in_array($booking->status, ['cancelled', 'completed']))
                                <a href="{{ route('client.select_seat', ['id' => $booking->trip->id, 'booking_id' => $booking->id]) }}"
                                    class="w-full sm:w-auto px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-black text-center transition-all shadow-md shadow-amber-500/20 flex items-center justify-center gap-1.5 active:scale-95">
                                    إكمال الحجز / إعادة المحاولة 🔄
                                </a>
                            @endif

                            {{-- 2. زر طلب التعديل المعدّل --}}
                            @if (in_array($booking->status, ['upcoming', 'pending', 'confirmed', 'approved']) && empty($booking->edit_notes))
                                <button type="button"
                                    @click="openEditModal = true; editBookingId = {{ $booking->id }}; editNotes = ''"
                                    class="w-full sm:w-auto px-4 py-2.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-black transition-all active:scale-95">
                                    طلب تعديل ✏️
                                </button>
                            @endif

                            {{-- زر استعراض التذكرة --}}
                            @if (in_array($booking->status, ['confirmed', 'completed']) || $booking->payment_status === 'paid')
                                <a href="{{ route('client.success', ['id' => $booking->id]) }}"
                                    class="w-full sm:w-auto px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-black text-center transition-all shadow-md shadow-sky-600/20 flex items-center justify-center gap-1.5 active:scale-95">
                                    استعراض التذكرة 📄
                                </a>
                            @else
                                <button type="button"
                                    class="w-full sm:w-auto px-5 py-2.5 bg-slate-100 text-slate-400 cursor-not-allowed rounded-xl text-xs font-black"
                                    disabled>
                                    استعراض التذكرة 📄
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-slate-200 p-8">
                    <div class="text-5xl mb-3">🌍</div>
                    <h3 class="text-base font-black text-slate-800 mb-1">لا توجد حجوزات مسجلة</h3>
                    <p class="text-slate-400 font-bold text-xs mb-6">يمكنك استكشاف الرحلات المتاحة وحجز رحلتك الأولى الآن
                    </p>
                    <a href="{{ route('client.trips') }}"
                        class="inline-block px-6 py-3 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-black shadow-lg shadow-sky-600/20 transition-all">
                        تصفح الرحلات المتاحة 🚌
                    </a>
                </div>
            @endforelse

            <!-- حالة لا توجد نتائج للفلتر المحدد -->
            <div x-show="filteredBookings.length === 0 && {{ $bookings->count() }} > 0"
                class="text-center py-16 bg-white rounded-3xl border-2 border-dashed border-slate-200">
                <div class="text-4xl mb-2">🔍</div>
                <p class="text-slate-500 font-black text-xs">لا توجد حجوزات تطابق هذا الفلتر</p>
            </div>

            <!-- أزرار الترقيم Pagination -->
            <div x-show="totalPages > 1" class="mt-8 flex items-center justify-center gap-2">
                <button @click="if(currentPage > 1) currentPage--" :disabled="currentPage === 1"
                    :class="currentPage === 1 ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400' :
                        'bg-white text-slate-700 hover:bg-slate-100 border-slate-200 active:scale-95'"
                    class="px-4 py-2 rounded-xl border text-xs font-black transition-all shadow-sm">
                    السابق
                </button>

                <template x-for="page in totalPages" :key="page">
                    <button @click="currentPage = page"
                        :class="currentPage === page ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' :
                            'bg-white text-slate-600 hover:bg-slate-100 border-slate-200'"
                        class="w-9 h-9 rounded-xl border text-xs font-black transition-all flex items-center justify-center"
                        x-text="page">
                    </button>
                </template>

                <button @click="if(currentPage < totalPages) currentPage++" :disabled="currentPage === totalPages"
                    :class="currentPage === totalPages ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400' :
                        'bg-white text-slate-700 hover:bg-slate-100 border-slate-200 active:scale-95'"
                    class="px-4 py-2 rounded-xl border text-xs font-black transition-all shadow-sm">
                    التالي
                </button>
            </div>

        </div>
    </div>
@endsection