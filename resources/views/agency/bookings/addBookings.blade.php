@extends('layouts.agency')

@section('content')
    {{-- الحاوية الرئيسية للتحكم بالصفحة والمودالات العامة --}}
    <div x-data="{
        selectedRoomForSeat: {},
        deleteModal: false,
        activeSeatZone: 'any',
        deleteUrl: '',
        openChangeSeatModal: false,
        seatToMove: { id: null, currentNum: null },
        targetSeatNumber: '',
        isSubmitting: false,
    
        openBusModal: new URLSearchParams(window.location.search).has('selected_trip_id')
    }" x-cloak>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 md:p-8" dir="rtl">
            <div class="max-w-7xl mx-auto space-y-6">

                {{-- رأس الصفحة --}}
                <div
                    class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800 flex items-center gap-2">
                            <span>🚍</span> إدارة وحجز رحلات المسافرين
                        </h3>
                        <p class="text-slate-400 text-xs font-bold mt-1">
                            لوحة مخصصة للوكالة لتتبع الرحلات، حجز المقاعد، تخصيص الغرف للركاب وإصدار التذاكر.
                        </p>
                    </div>
                    <a href="{{ route('agency.trips.create') }}"
                        class="px-5 py-3 bg-blue-600 text-white rounded-2xl font-bold text-sm shadow-md shadow-blue-200 hover:bg-blue-700 transition-all active:scale-95 flex items-center gap-2">
                        <span>➕</span> إضافة رحلة جديدة
                    </a>
                </div>

                <!-- مكتبة SweetAlert2 -->
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

                @if (session('success'))
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بنجاح!',
                                text: "{{ session('success') }}",
                                confirmButtonText: 'حسناً',
                                confirmButtonColor: '#28a745',
                                target: 'body'
                            });
                        });
                    </script>
                @endif

                @if (session('error'))
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ!',
                                text: "{{ session('error') }}",
                                confirmButtonText: 'موافق',
                                confirmButtonColor: '#dc3545',
                                target: 'body'
                            });
                        });
                    </script>
                @endif

                {{-- شريط البحث والفلترة --}}
                <div class="bg-white p-4 rounded-3xl border border-slate-100 shadow-sm">
                    <form action="{{ route('agency.bookings.index') }}" method="GET"
                        class="flex flex-col md:flex-row gap-3 items-center justify-between">
                        @if (request('selected_trip_id'))
                            <input type="hidden" name="selected_trip_id" value="{{ request('selected_trip_id') }}">
                        @endif

                        <div class="relative w-full md:w-96">
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="ابحث عن رحلة باسمها أو رقم التعريف (#)..."
                                class="w-full pr-10 pl-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-700 focus:outline-none focus:border-blue-500 transition-all">
                            <span class="absolute right-3.5 top-2.5 text-slate-400 text-sm">🔍</span>
                        </div>

                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <button type="submit"
                                class="px-5 py-2.5 bg-blue-600 text-white font-bold text-xs rounded-2xl hover:bg-blue-700 transition-all shadow-sm active:scale-95">
                                بحث
                            </button>
                            @if (request('search'))
                                <a href="{{ route('agency.trips.index', request()->only('selected_trip_id')) }}"
                                    class="px-4 py-2.5 bg-slate-100 text-slate-600 font-bold text-xs rounded-2xl hover:bg-slate-200 transition-all">
                                    إلغاء البحث ✖
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                {{-- عرض الرحلات --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse($trips as $t)
                        @php
                            $percentage =
                                $t->total_seats > 0
                                    ? (($t->total_seats - $t->available_seats) / $t->total_seats) * 100
                                    : 0;
                            $bookedSeatsCount = $t->total_seats - $t->available_seats;
                        @endphp
                        <div
                            class="bg-white rounded-3xl border border-slate-100 shadow-sm hover:shadow-md transition-all p-6 flex flex-col justify-between space-y-5 relative overflow-hidden">

                            {{-- الشريط العلوي للبطاقة --}}
                            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-xs font-mono font-black text-slate-400 bg-slate-100 px-2.5 py-1 rounded-xl">#{{ $t->id }}</span>
                                    @if ($t->transport_type === 'flight')
                                        <span
                                            class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-50 border border-indigo-100 text-indigo-600 rounded-full text-[11px] font-black">
                                            ✈️ طيران
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1 px-3 py-1 bg-amber-50 border border-amber-100 text-amber-600 rounded-full text-[11px] font-black">
                                            🚌 حافلة برية
                                        </span>
                                    @endif
                                </div>

                                {{-- شارة حالة الرحلة --}}
                                @if ($t->status === 'completed' || $t->status === 'تمت الرحلة')
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-blue-50 text-blue-600 border border-blue-100">
                                        ● تمت الرحلة
                                    </span>
                                @elseif ($t->status === 'active')
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-600 border border-emerald-100">
                                        ● في الطريق
                                    </span>
                                @elseif ($t->status === 'approved')
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-600 border border-emerald-100">
                                        ● معتمدة
                                    </span>
                                @elseif($t->status === 'pending' || $t->status === 'published')
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-50 text-amber-600 border border-amber-100">
                                        ● قيد المراجعة
                                    </span>
                                @elseif($t->status === 'draft')
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-slate-100 text-slate-600 border border-slate-200">
                                        ● مسودة
                                    </span>
                                @elseif($t->status === 'cancellation_requested')
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-orange-50 text-orange-600 border border-orange-100">
                                        ● تم طلب إلغاء الرحلة
                                    </span>
                                @elseif($t->status === 'cancelled')
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-50 text-rose-600 border border-rose-100">
                                        ● ملغاة
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-50 text-rose-600 border border-rose-100">
                                        ● مرفوضة
                                    </span>
                                @endif
                            </div>

                            {{-- تفاصيل الرحلة المسار والاسم --}}
                            <div class="space-y-2">
                                <h4 class="text-base font-black text-slate-800 leading-snug">{{ $t->title }}</h4>
                                <div
                                    class="flex items-center justify-between bg-slate-50 p-3 rounded-2xl border border-slate-100 text-xs text-slate-600 font-bold">
                                    <div class="flex items-center gap-1">
                                        <span class="text-blue-500">📍</span>
                                        <span>{{ $t->from_city ?? 'الإنطلاق' }}</span>
                                    </div>
                                    <span class="text-slate-300">⬅️</span>
                                    <div class="flex items-center gap-1">
                                        <span class="text-emerald-500">🏁</span>
                                        <span>{{ $t->to_city ?? 'الوجهة' }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- تفاصيل الوقت والمدة --}}
                            <div
                                class="grid grid-cols-2 gap-2 text-xs font-bold text-slate-600 bg-slate-50/50 p-3 rounded-2xl">
                                <div>
                                    <span class="text-[10px] text-slate-400 block mb-0.5">تاريخ المغادرة</span>
                                    <span>📅 {{ $t->date }}</span>
                                    <span
                                        class="text-[10px] text-slate-400 block font-mono font-normal mt-0.5">{{ $t->departure_at }}</span>
                                </div>
                                <div class="border-r border-slate-200/60 pr-2">
                                    <span class="text-[10px] text-slate-400 block mb-0.5">المدة / السعر</span>
                                    <span>⏱️ {{ $t->days }} أيام</span>
                                    <span
                                        class="text-[11px] text-emerald-600 font-black block mt-0.5">{{ number_format($t->price, 2) }}
                                        ⃁</span>
                                </div>
                            </div>

                            {{-- نسبة المقاعد المباعة --}}
                            <div class="space-y-1.5">
                                <div class="flex justify-between items-center text-xs font-bold">
                                    <span class="text-slate-400">المقاعد المحجوزة:</span>
                                    <span class="text-slate-800 font-black font-mono">{{ $bookedSeatsCount }} /
                                        {{ $t->total_seats }}</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-blue-600 h-full rounded-full transition-all duration-300"
                                        style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>

                            {{-- زر الإجراء المخصص للوكالة --}}
                            <div class="pt-2">
                                @if ($t->transport_type == 'bus' || $t->transport_type == 'flight')
                                    <button
                                        @click="window.location.href='{{ route('agency.addbookings', array_merge(request()->query(), ['selected_trip_id' => $t->id])) }}'"
                                        type="button"
                                        class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-black text-xs transition-all shadow-md shadow-blue-100 flex items-center justify-center gap-2 active:scale-95">
                                        <span>🎫</span> إدارة وحجز المسافرين
                                    </button>
                                @endif
                            </div>

                        </div>
                    @empty
                        <div
                            class="col-span-full bg-white rounded-3xl p-12 text-center text-slate-400 font-bold border border-slate-100">
                            <div class="text-4xl mb-3">📭</div>
                            <p class="text-sm">لا توجد أي رحلات مسجلة مطابقة للبحث حالياً.</p>
                        </div>
                    @endforelse
                </div>

                {{-- الترقيم (Pagination) --}}
                @if ($trips->hasPages())
                    <div
                        class="bg-white p-4 rounded-3xl border border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-xs text-slate-500 font-bold">
                            عرض من {{ $trips->firstItem() ?? 0 }} إلى {{ $trips->lastItem() ?? 0 }} من إجمالي
                            {{ $trips->total() }} رحلة
                        </div>
                        <div>
                            {{ $trips->links() }}
                        </div>
                    </div>
                @endif

            </div>
        </main>


        @if (isset($trip) && ($trip->transport_type == 'bus' || $trip->transport_type == 'flight'))
            @php
                $isFlight = ($trip->transport_type ?? null) === 'flight';
                $bookedSeatsMap = array_flip($booked_seats ?? []);
                $seatCounter = 1;

                $totalSeats = isset($trip->total_seats) ? (int) $trip->total_seats : ($isFlight ? 60 : 49);

                // تحديد مخطط المقاعد حسب نوع الرحلة
                if ($isFlight) {
                    $layout_config = [];
                    $seatsRemaining = $totalSeats;
                    while ($seatsRemaining > 0) {
                        if ($seatsRemaining >= 6) {
                            $layout_config[] = 'SSS.SSS';
                            $seatsRemaining -= 6;
                        } else {
                            $rowStr = '';
                            for ($c = 0; $c < $seatsRemaining; $c++) {
                                if ($c == 3) {
                                    $rowStr .= '.';
                                }
                                $rowStr .= 'S';
                            }
                            $layout_config[] = $rowStr;
                            $seatsRemaining = 0;
                        }
                    }
                } else {
                    $layout_config = [
                        '...SS',
                        'SS.SS',
                        'SS.SS',
                        'SS.SS',
                        'SS.SS',
                        'SS.SS',
                        'SS.SS',
                        'SS...',
                        'SS...',
                        'SS...',
                        'SS.SS',
                        'SS.SS',
                        'SS.SS',
                        'SSSSS',
                    ];
                }

                // حد تقسيم المقاعد
                $halfSeats =
                    isset($trip->split_boundary) && $trip->split_boundary > 0 ? (int) $trip->split_boundary : 28;

                $isAutoSplit = isset($trip->auto_split) && $trip->auto_split == 1;

                // تحديد صورة الخلفية ديناميكياً حسب عدد المقاعد
                $busUrlImage = match ((int) $totalSeats) {
                    49 => asset('images/BD.jpeg'),
                    30 => asset('images/bus_30.jpeg'),
                    15 => asset('images/van_15.jpeg'),
                    7 => asset('images/suv_7.jpeg'),
                    4 => asset('images/sedan_4.jpeg'),
                    default => asset('images/BD.jpeg'),
                };

                // خيارات الغرف والوجبات
                $roomOptions = is_array($trip->room_options)
                    ? $trip->room_options
                    : (json_decode($trip->room_options, true) ?:
                    []);

                $mealOptions = is_array($trip->meal_options)
                    ? $trip->meal_options
                    : (json_decode($trip->meal_options, true) ?:
                    []);
            @endphp

            <div x-data="bookingManager" dir="rtl" class="relative z-[80]">
                <script>
                    document.addEventListener('alpine:init', () => {

                        Alpine.data('bookingManager', () => ({

                            extraServices: @json(is_array($trip->extra_services) ? $trip->extra_services : (json_decode($trip->extra_services, true) ?: [])),

                            passengerServices: {},

                            openBookedTicketModal: false,
                            activeBookedSeat: null,
                            showBookedError: false,

                            openPassengerModal: false,
                            activeModalSeat: null,

                            selectedSeats: [],
                            passengerExtraSeats: {},
                            passengerData: {},

                            allSeatsArray: Array.from({
                                    length: {{ $trip->total_seats ?? 45 }}
                                },
                                (_, i) => i + 1
                            ),

                            bookedSeatsDetails: @json($booked_seats_details ?? []),

                            customerPhone: '',
                            customerEmail: '',

                            payments: [{
                                method: 'moyasar',
                                amount: 0,
                                reference: ''
                            }],

                            addPayment() {
                                this.payments.push({
                                    method: 'cash',
                                    amount: 0,
                                    reference: ''
                                });
                            },



                            // 1. فتح نافذة التغيير بأمان
                            changeSeatNumber(seatKey = null) {
                                // تحديد المقعد الممرر أو استخدام المقعد النشط تلقائياً
                                const rawSeat = seatKey || this.activeBookedSeat;

                                if (!rawSeat) {
                                    console.error('لم يتم تحديد رقم المقعد');
                                    return;
                                }

                                // تنظيف رقم المقعد من علامات التنصيص الزائدة (مثل "13" أو "\"13\"")
                                const cleanKey = String(rawSeat).replace(/"/g, '').trim();

                                // البحث عن المقعد داخل القاموس بأكثر من احتمال للمفتاح
                                const seatObj = this.bookedSeatsDetails[rawSeat] ||
                                    this.bookedSeatsDetails[cleanKey] ||
                                    Object.values(this.bookedSeatsDetails || {}).find(s => String(s.seat_number || s
                                        .seat_num).replace(/"/g, '').trim() === cleanKey);

                                console.log('تتبع البحث عن المقعد:', {
                                    rawSeat,
                                    cleanKey,
                                    seatObj
                                });

                                if (!seatObj) {
                                    console.error('بيانات المقعد غير متوفرة داخل bookedSeatsDetails:', rawSeat);
                                    alert('عذراً، لم يتم العثور على بيانات هذا المقعد.');
                                    return;
                                }

                                // تجهيز بيانات النقل وفتح النافذة
                                this.seatToMove = {
                                    id: seatObj.id,
                                    currentNum: cleanKey
                                };

                                this.targetSeatNumber = '';
                                this.openBookedTicketModal = false;
                                this.openChangeSeatModal = true;
                            },

                            // 2. تنفيذ نقل المقعد
                            async submitChangeSeat() {
                                if (!this.targetSeatNumber || this.isSubmitting) return;

                                this.isSubmitting = true;
                                try {
                                    const response = await fetch('/agency/seats/change-number', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector(
                                                'meta[name="csrf-token"]').getAttribute('content')
                                        },
                                        body: JSON.stringify({
                                            seat_id: this.seatToMove.id,
                                            new_seat_number: this.targetSeatNumber
                                        })
                                    });

                                    const data = await response.json();
                                    if (data.success) {
                                        alert(data.message);
                                        window.location.reload(); // أو إعادة تحديث البيانات محلياً
                                    } else {
                                        alert(data.message || 'حدث خطأ أثناء تغيير المقعد');
                                    }
                                } catch (e) {
                                    alert('حدث خطأ بالاتصال بالسيرفر');
                                } finally {
                                    this.isSubmitting = false;
                                }
                            },

                            // 3. تنفيذ حذف المقعد
                            async deleteBookedSeat(seatId, seatNumber, bookingId) {
                                if (!confirm(`هل أنت تأكد من حذف المقعد رقم (${seatNumber})؟`)) return;

                                try {
                                    const response = await fetch(`/agency/seats/${seatId}`, {
                                        method: 'DELETE',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector(
                                                'meta[name="csrf-token"]').getAttribute('content')
                                        }
                                    });

                                    const data = await response.json();
                                    if (data.success) {
                                        alert(data.message);
                                        window.location.reload();
                                    } else {
                                        alert(data.message || 'فشل حذف المقعد');
                                    }
                                } catch (e) {
                                    alert('حدث خطأ أثناء تنفيذ الطلب');
                                }
                            },

                            removePayment(index) {
                                if (this.payments.length > 1) {
                                    this.payments.splice(index, 1);
                                }
                            },

                            calculateTotalPaid() {
                                return this.payments.reduce((sum, p) => sum + (Number(p.amount) || 0), 0);
                            },

                            roomCapacities: {
                                single: 1,
                                double: 2,
                                triple: 3,
                                quad: 4
                            },

                            passengerMeals: {},
                            passengerNames: {},
                            passengerIds: {},
                            passengerGenders: {},

                            seatRoomAssignments: {},
                            seatPackageSelection: {},
                            sharedRoomTypes: {},
                            allowsSharedRooms: @json((bool) $trip->allows_shared_rooms),
                            roomOptionsData: @json($roomOptions),
                            roomPricesData: @json($trip->room_prices ?? []),

                            roomQuantities: {
                                single: 0,
                                double: 0,
                                triple: 0,
                                quad: 0
                            },

                            prices: {
                                seat: {{ floatval($trip->price ?? 0) }},

                                single: {{ floatval($roomOptions['single']['price'] ?? 0) }},
                                double: {{ floatval($roomOptions['double']['price'] ?? 0) }},
                                triple: {{ floatval($roomOptions['triple']['price'] ?? 0) }},
                                quad: {{ floatval($roomOptions['quad']['price'] ?? 0) }},

                                breakfast: {{ floatval($mealOptions['individual']['breakfast']['price'] ?? 0) }},
                                lunch: {{ floatval($mealOptions['individual']['lunch']['price'] ?? 0) }},
                                dinner: {{ floatval($mealOptions['individual']['dinner']['price'] ?? 0) }}
                            },

                            roomPackages: {
                                single: {
                                    name: @json($roomOptions['single']['packages'][0]['name'] ?? 'فردية'),
                                    price: {{ floatval($roomOptions['single']['packages'][0]['price'] ?? 0) }},
                                    meals: {
                                        breakfast: {{ !empty($roomOptions['single']['packages'][0]['meals']['breakfast']) ? 'true' : 'false' }},
                                        lunch: {{ !empty($roomOptions['single']['packages'][0]['meals']['lunch']) ? 'true' : 'false' }},
                                        dinner: {{ !empty($roomOptions['single']['packages'][0]['meals']['dinner']) ? 'true' : 'false' }}
                                    }
                                },

                                double: {
                                    name: @json($roomOptions['double']['packages'][0]['name'] ?? 'ثنائية'),
                                    price: {{ floatval($roomOptions['double']['packages'][0]['price'] ?? 0) }},
                                    meals: {
                                        breakfast: {{ !empty($roomOptions['double']['packages'][0]['meals']['breakfast']) ? 'true' : 'false' }},
                                        lunch: {{ !empty($roomOptions['double']['packages'][0]['meals']['lunch']) ? 'true' : 'false' }},
                                        dinner: {{ !empty($roomOptions['double']['packages'][0]['meals']['dinner']) ? 'true' : 'false' }}
                                    }
                                },

                                triple: {
                                    name: @json($roomOptions['triple']['packages'][0]['name'] ?? 'ثلاثية'),
                                    price: {{ floatval($roomOptions['triple']['packages'][0]['price'] ?? 0) }},
                                    meals: {
                                        breakfast: {{ !empty($roomOptions['triple']['packages'][0]['meals']['breakfast']) ? 'true' : 'false' }},
                                        lunch: {{ !empty($roomOptions['triple']['packages'][0]['meals']['lunch']) ? 'true' : 'false' }},
                                        dinner: {{ !empty($roomOptions['triple']['packages'][0]['meals']['dinner']) ? 'true' : 'false' }}
                                    }
                                },

                                quad: {
                                    name: @json($roomOptions['quad']['packages'][0]['name'] ?? 'رباعية'),
                                    price: {{ floatval($roomOptions['quad']['packages'][0]['price'] ?? 0) }},
                                    meals: {
                                        breakfast: {{ !empty($roomOptions['quad']['packages'][0]['meals']['breakfast']) ? 'true' : 'false' }},
                                        lunch: {{ !empty($roomOptions['quad']['packages'][0]['meals']['lunch']) ? 'true' : 'false' }},
                                        dinner: {{ !empty($roomOptions['quad']['packages'][0]['meals']['dinner']) ? 'true' : 'false' }}
                                    }
                                }
                            },

                            init() {
                                for (let seat in this.bookedSeatsDetails) {
                                    if (['canceled', 'cancelled'].includes(this.bookedSeatsDetails[seat].status)) {
                                        delete this.bookedSeatsDetails[seat];
                                    }
                                }
                                this.$watch('roomQuantities', () => this.reconcileRoomAssignments(), {
                                    deep: true
                                });
                            },


                            isPrivateRoomKey(v) {
                                return ['single', 'double', 'triple', 'quad'].includes(v);
                            },
                            hasInvalidAssignments() {
                                return this.selectedSeats.some(s => {
                                    const v = this.seatRoomAssignments[s];
                                    if (v === 'additional') return false;
                                    if (v === 'shared') return !this.sharedRoomTypes[s];
                                    return !this.isPrivateRoomKey(v);
                                });
                            },


                            getSharedPrice(type) {
                                let rp = this.roomPricesData;
                                if (typeof rp === 'string') {
                                    try {
                                        rp = JSON.parse(rp);
                                    } catch (e) {
                                        rp = {};
                                    }
                                }
                                return parseFloat(rp?.[type]) || parseFloat(this.roomOptionsData?.[type]?.price) ||
                                    parseFloat(this.prices[type]) || 0;
                            },

                            getAvailableSharedRoomTypes() {
                                const names = {
                                    single: 'مفردة',
                                    double: 'ثنائية',
                                    triple: 'ثلاثية',
                                    quad: 'رباعية'
                                };
                                return Object.keys(names)
                                    .map(k => ({
                                        id: k,
                                        name: names[k],
                                        price: this.getSharedPrice(k)
                                    }))
                                    .filter(t => t.price > 0);
                            },

                            setAssignment(seat, value) {
                                this.seatRoomAssignments[seat] = value;
                                if (value !== 'shared') delete this.sharedRoomTypes[seat];
                                if (!this.isPrivateRoomKey(value)) this.seatPackageSelection[seat] = 'custom';
                            },

                            reconcileRoomAssignments() {
                                ['single', 'double', 'triple', 'quad'].forEach(key => {
                                    const cap = (this.roomCapacities[key] || 1) * (parseInt(this
                                        .roomQuantities[key]) || 0);
                                    Object.keys(this.seatRoomAssignments)
                                        .filter(s => this.seatRoomAssignments[s] === key)
                                        .slice(cap)
                                        .forEach(s => this.setAssignment(s, this.allowsSharedRooms ?
                                            'shared' : ''));
                                });
                            },


                            getAssignedCount(roomKey) {

                                return Object.values(this.seatRoomAssignments)
                                    .filter(key => key === roomKey)
                                    .length;
                            },

                            getRoomMaxCapacity(roomKey, roomCount) {

                                const cap = this.roomCapacities[roomKey] || 1;

                                return (roomCount || 0) * cap;
                            },

                            isRoomFull(roomKey, roomCount, currentSeat) {

                                const maxCapacity =
                                    this.getRoomMaxCapacity(roomKey, roomCount);

                                let assignedCount =
                                    this.getAssignedCount(roomKey);

                                if (this.seatRoomAssignments[currentSeat] === roomKey) {
                                    assignedCount -= 1;
                                }

                                return assignedCount >= maxCapacity;
                            },

                            initModalSeat(seatId) {

                                if (!this.passengerMeals[seatId]) {
                                    this.passengerMeals[seatId] = {
                                        breakfast: false,
                                        lunch: false,
                                        dinner: false
                                    };
                                }

                                if (typeof this.passengerNames[seatId] === 'undefined') {
                                    this.passengerNames[seatId] = '';
                                }

                                if (typeof this.passengerIds[seatId] === 'undefined') {
                                    this.passengerIds[seatId] = '';
                                }

                                if (!this.passengerGenders[seatId]) {
                                    this.passengerGenders[seatId] = 'male';
                                }

                                if (!this.seatRoomAssignments[seatId]) {
                                    this.seatRoomAssignments[seatId] = this.allowsSharedRooms ? 'shared' : '';
                                }

                                if (!this.seatPackageSelection[seatId]) {
                                    this.seatPackageSelection[seatId] = 'custom';
                                }

                                if (!this.bookedSeatsDetails[seatId]) {
                                    this.bookedSeatsDetails[seatId] = {
                                        identity: '',
                                        gender: ''
                                    };
                                }

                                if (!this.passengerServices[seatId]) {
                                    this.passengerServices[seatId] = {};
                                }

                                if (!this.passengerExtraSeats[seatId]) {
                                    this.passengerExtraSeats[seatId] = [];
                                }
                            },

                            getAvailableExtraSeats() {

                                const activeId = Number(this.activeModalSeat);

                                const bookedSeatNumbers =
                                    Object.keys(this.bookedSeatsDetails || {})
                                    .map(Number);

                                const currentExtras =
                                    (this.passengerExtraSeats[activeId] || [])
                                    .map(Number);

                                return this.allSeatsArray.filter(seat => {

                                    const seatNum = Number(seat);

                                    if (seatNum === activeId) {
                                        return false;
                                    }

                                    if (bookedSeatNumbers.includes(seatNum)) {
                                        return false;
                                    }

                                    if (currentExtras.includes(seatNum)) {
                                        return true;
                                    }

                                    return !this.selectedSeats.some(
                                        s => Number(s) === seatNum
                                    );
                                });
                            },

                            toggleExtraSeat(seat) {

                                const seatNum = Number(seat);
                                const activeId = Number(this.activeModalSeat);

                                if (!this.passengerExtraSeats[activeId]) {
                                    this.passengerExtraSeats[activeId] = [];
                                }

                                const currentExtras =
                                    this.passengerExtraSeats[activeId].map(Number);

                                const isAlreadyExtra =
                                    currentExtras.includes(seatNum);

                                if (isAlreadyExtra) {

                                    this.passengerExtraSeats[activeId] =
                                        currentExtras.filter(s => s !== seatNum);

                                    this.selectedSeats =
                                        this.selectedSeats.filter(
                                            s => Number(s) !== seatNum
                                        );

                                    if (this.passengerData) {
                                        delete this.passengerData[seatNum];
                                    }

                                    delete this.seatRoomAssignments[seatNum];
                                    delete this.seatPackageSelection[seatNum];
                                    delete this.bookedSeatsDetails[seatNum];
                                    delete this.sharedRoomTypes[seatNum];

                                } else {

                                    this.passengerExtraSeats[activeId] = [
                                        ...currentExtras,
                                        seatNum
                                    ];

                                    if (
                                        !this.selectedSeats.some(
                                            s => Number(s) === seatNum
                                        )
                                    ) {
                                        this.selectedSeats = [
                                            ...this.selectedSeats.map(Number),
                                            seatNum
                                        ];
                                    }

                                    this.seatRoomAssignments[seatNum] = 'additional';
                                    this.seatPackageSelection[seatNum] = 'custom';

                                    if (!this.passengerData) {
                                        this.passengerData = {};
                                    }

                                    this.passengerData[seatNum] = {
                                        is_extra: true,
                                        parent_seat: activeId,
                                        meal_id: null,
                                        services: []
                                    };
                                }
                            },

                            toggleSeat(seatId) {

                                const seatNum = Number(seatId);

                                if (this.isSelected(seatNum)) {

                                    this.selectedSeats =
                                        this.selectedSeats.filter(
                                            id => Number(id) !== seatNum
                                        );

                                    if (
                                        this.passengerExtraSeats[seatNum] &&
                                        this.passengerExtraSeats[seatNum].length > 0
                                    ) {

                                        this.passengerExtraSeats[seatNum].forEach(extra => {

                                            const extraNum = Number(extra);

                                            this.selectedSeats =
                                                this.selectedSeats.filter(
                                                    s => Number(s) !== extraNum
                                                );

                                            delete this.seatRoomAssignments[extraNum];
                                            delete this.seatPackageSelection[extraNum];
                                            delete this.sharedRoomTypes[extraNum]; // ← هنا


                                            if (this.passengerData) {
                                                delete this.passengerData[extraNum];
                                            }

                                            delete this.bookedSeatsDetails[extraNum];
                                        });
                                    }

                                    for (let parentSeat in this.passengerExtraSeats) {

                                        if (
                                            Array.isArray(
                                                this.passengerExtraSeats[parentSeat]
                                            )
                                        ) {
                                            this.passengerExtraSeats[parentSeat] =
                                                this.passengerExtraSeats[parentSeat]
                                                .map(Number)
                                                .filter(id => id !== seatNum);
                                        }
                                    }

                                    delete this.passengerMeals[seatNum];
                                    delete this.passengerServices[seatNum];
                                    delete this.seatRoomAssignments[seatNum];
                                    delete this.seatPackageSelection[seatNum];
                                    delete this.passengerNames[seatNum];
                                    delete this.passengerIds[seatNum];
                                    delete this.passengerGenders[seatNum];
                                    delete this.passengerExtraSeats[seatNum];
                                    delete this.sharedRoomTypes[seatNum];

                                    if (this.passengerData) {
                                        delete this.passengerData[seatNum];
                                    }

                                    delete this.bookedSeatsDetails[seatNum];

                                } else {

                                    this.selectedSeats.push(seatNum);

                                    this.passengerMeals[seatNum] = {
                                        breakfast: false,
                                        lunch: false,
                                        dinner: false
                                    };

                                    this.passengerServices[seatNum] = {};
                                    this.seatRoomAssignments[seatNum] = this.allowsSharedRooms ? 'shared' : '';
                                    this.seatPackageSelection[seatNum] = 'custom';
                                    this.passengerNames[seatNum] = '';
                                    this.passengerIds[seatNum] = '';
                                    this.passengerGenders[seatNum] = 'male';
                                    this.passengerExtraSeats[seatNum] = [];
                                }
                            },
                            cancelSeat(seat) {
                                const n = Number(seat);
                                if (!seat || !this.isSelected(n)) {
                                    this.openPassengerModal = false;
                                    return;
                                }
                                this.toggleSeat(n); // يلغي المقعد وكل مقاعده الإضافية وبياناته
                                this.openPassengerModal = false;
                                this.activeModalSeat = null;
                            },

                            isSelected(seatId) {

                                return this.selectedSeats.some(
                                    id => Number(id) === Number(seatId)
                                );
                            },

                            getMealPrice(seatId, mealType) {

                                return this.prices[mealType] || 0;
                            },

                            getServicesSummary() {
                                let summary = [];

                                this.selectedSeats.forEach(s => {
                                    if (this.passengerServices[s]) {

                                        this.extraServices.forEach((srv, idx) => {

                                            if (this.passengerServices[s][idx]) {
                                                summary.push(
                                                    srv.name + ' (مـ' + s + ')'
                                                );
                                            }

                                        });
                                    }
                                });

                                return summary.length ?
                                    summary.join(' - ') :
                                    'لا يوجد خدمات إضافية';
                            },

                            getSelectedRoomsList() {
                                let list = [];

                                if (parseInt(this.roomQuantities.single) > 0) {
                                    list.push({
                                        key: 'single',
                                        name: 'غرفة مفردة 🛌',
                                        count: this.roomQuantities.single
                                    });
                                }

                                if (parseInt(this.roomQuantities.double) > 0) {
                                    list.push({
                                        key: 'double',
                                        name: 'غرفة ثنائية 👥',
                                        count: this.roomQuantities.double
                                    });
                                }

                                if (parseInt(this.roomQuantities.triple) > 0) {
                                    list.push({
                                        key: 'triple',
                                        name: 'غرفة ثلاثية 👑',
                                        count: this.roomQuantities.triple
                                    });
                                }

                                if (parseInt(this.roomQuantities.quad) > 0) {
                                    list.push({
                                        key: 'quad',
                                        name: 'غرفة رباعية 🏰',
                                        count: this.roomQuantities.quad
                                    });
                                }

                                return list;
                            },

                            showTicket(seatNumber) {
                                console.log('رقم المقعد:', seatNumber);
                                console.log('بيانات المقعد:', this.bookedSeatsDetails[seatNumber]);

                                this.activeBookedSeat = seatNumber;
                                this.openBookedTicketModal = true;
                            },

                            getRoomPrice(type) {
                                let rp = this.roomPricesData;
                                if (typeof rp === 'string') {
                                    try {
                                        rp = JSON.parse(rp);
                                    } catch (e) {
                                        rp = {};
                                    }
                                }
                                return parseFloat(rp?.[type]) || parseFloat(this.prices[type]) || 0;
                            },

                            calculateTotal() {
                                const privateKeys = ['single', 'double', 'triple', 'quad'];
                                let total = 0;

                                this.selectedSeats.forEach(seat => {
                                    const a = this.seatRoomAssignments[seat];

                                    if (a === 'additional') {
                                        total += this.prices.seat || 0; // مقعد إضافي: بدون وجبات أو خدمات
                                        return;
                                    }

                                    if (privateKeys.includes(a)) {
                                        total += this.getRoomPrice(a); // لكل مسافر في غرفة خاصة
                                    } else if (a === 'shared') {
                                        const t = this.sharedRoomTypes[seat];
                                        total += t ? this.getSharedPrice(t) : 0;
                                    } else {
                                        total += this.prices.seat || 0; // بدون سكن صالح
                                    }

                                    // الوجبات
                                    if (privateKeys.includes(a) && this.seatPackageSelection[seat] ===
                                        'package' && this.roomPackages[a]) {
                                        total += this.roomPackages[a].price || 0;
                                    } else if (this.passengerMeals[seat]) {
                                        ['breakfast', 'lunch', 'dinner'].forEach(m => {
                                            if (this.passengerMeals[seat][m]) total += this
                                                .getMealPrice(seat, m);
                                        });
                                    }

                                    // الخدمات
                                    if (this.passengerServices[seat]) {
                                        this.extraServices.forEach((srv, idx) => {
                                            if (this.passengerServices[seat][idx]) total +=
                                                parseFloat(srv.price) || 0;
                                        });
                                    }
                                });

                                return total;
                            },

                            getMealsSummary() {

                                let summary = [];

                                this.selectedSeats.forEach(s => {

                                    let rType =
                                        this.seatRoomAssignments[s];

                                    if (
                                        ['single', 'double', 'triple', 'quad']
                                        .includes(rType) &&
                                        this.seatPackageSelection[s] === 'package'
                                    ) {

                                        summary.push(
                                            'باقة ' +
                                            (
                                                this.roomPackages[rType]?.name ||
                                                rType
                                            ) +
                                            ' (مـ' + s + ')'
                                        );

                                    } else if (this.passengerMeals[s]) {

                                        if (
                                            this.passengerMeals[s].breakfast
                                        ) {
                                            summary.push(
                                                'فطور (مـ' + s + ')'
                                            );
                                        }

                                        if (
                                            this.passengerMeals[s].lunch
                                        ) {
                                            summary.push(
                                                'غداء (مـ' + s + ')'
                                            );
                                        }

                                        if (
                                            this.passengerMeals[s].dinner
                                        ) {
                                            summary.push(
                                                'عشاء (مـ' + s + ')'
                                            );
                                        }
                                    }

                                });

                                return summary.length ?
                                    summary.join(' - ') :
                                    'لا يوجد وجبات محددة';
                            },


                            unlockSeat(bookingId, seatId = null) {

                                let targetId = bookingId || seatId;

                                if (!targetId) {

                                    alert(
                                        'تعذر العثور على رقم الحجز.'
                                    );

                                    return;
                                }

                                if (
                                    confirm(
                                        'هل أنت متأكد من إلغاء قفل هذا المقعد وإعادة إتاحته للجميع؟'
                                    )
                                ) {

                                    fetch(
                                            `/agency/bookings/release-locked/${targetId}`, {
                                                method: 'POST',

                                                headers: {
                                                    'X-CSRF-TOKEN': document
                                                        .querySelector(
                                                            'meta[name="csrf-token"]'
                                                        )
                                                        ?.getAttribute('content') || '',

                                                    'Content-Type': 'application/json',

                                                    'Accept': 'application/json'
                                                },

                                                body: JSON.stringify({
                                                    seat_id: seatId,
                                                    trip_id: {{ $trip->id ?? 'null' }}
                                                })
                                            }
                                        )

                                        .then(response => {

                                            if (
                                                response.ok ||
                                                response.redirected
                                            ) {

                                                window.location.reload();

                                            } else {

                                                fetch(
                                                        `/agency/bookings/release-locked/${targetId}`, {
                                                            method: 'POST',

                                                            headers: {
                                                                'X-CSRF-TOKEN': document
                                                                    .querySelector(
                                                                        'meta[name="csrf-token"]'
                                                                    )
                                                                    ?.getAttribute('content') || '',

                                                                'Content-Type': 'application/json',

                                                                'Accept': 'application/json'
                                                            }
                                                        }
                                                    )

                                                    .then(res => {

                                                        if (
                                                            res.ok ||
                                                            res.redirected
                                                        ) {

                                                            window.location.reload();

                                                        } else {

                                                            alert(
                                                                'حدث خطأ أثناء فك قفل المقعد.'
                                                            );
                                                        }

                                                    });

                                            }

                                        })

                                        .catch(error => {

                                            console.error(
                                                'Error:',
                                                error
                                            );

                                            alert(
                                                'تعذر الاتصال بالسيرفر.'
                                            );

                                        });

                                }
                            },

                            prepareAndSubmit(e) {
                                for (const s of this.selectedSeats.map(Number)) {
                                    const v = this.seatRoomAssignments[s];
                                    if (v === 'additional') continue;
                                    if (v === 'shared') {
                                        if (!this.sharedRoomTypes[s]) {
                                            alert('حدد نوع الغرفة المشتركة للمقعد رقم ' + s);
                                            e.preventDefault();
                                            return false;
                                        }
                                    } else if (!this.isPrivateRoomKey(v)) {
                                        alert('حدد السكن (غرفة أو مشترك) للمقعد رقم ' + s);
                                        e.preventDefault();
                                        return false;
                                    }
                                }
                                const sharedOut = {};
                                this.selectedSeats.map(Number).forEach(s => {
                                    if (this.seatRoomAssignments[s] === 'shared') sharedOut[s] = this
                                        .sharedRoomTypes[s];
                                });
                                this.$refs.inputSharedRoomTypes.value = JSON.stringify(sharedOut);

                                let selectedSeatsNums = this.selectedSeats.map(Number);
                                let cleanExtraSeatsMap = {};
                                let allExtraSeats = [];

                                for (let mainSeat in this.passengerExtraSeats) {
                                    let mainSeatNum = Number(mainSeat);

                                    if (selectedSeatsNums.includes(mainSeatNum) && Array.isArray(this
                                            .passengerExtraSeats[mainSeat])) {

                                        let validExtras = this.passengerExtraSeats[mainSeat]
                                            .map(Number)
                                            .filter(extraNum => selectedSeatsNums.includes(extraNum));

                                        if (validExtras.length > 0) {
                                            cleanExtraSeatsMap[mainSeatNum] = validExtras;
                                            allExtraSeats.push(...validExtras);
                                        }
                                    }
                                }

                                let mainSeatsOnly = selectedSeatsNums
                                    .filter(seat => !allExtraSeats.includes(seat))
                                    .sort((a, b) => a - b);

                                let names = [];
                                let ids = [];
                                let genders = [];

                                for (let seat of mainSeatsOnly) {
                                    names.push(
                                        this.passengerNames[seat] || 'راكب مقعد ' + seat
                                    );

                                    ids.push(
                                        this.passengerIds[seat] || ''
                                    );

                                    genders.push(
                                        this.passengerGenders[seat] || 'male'
                                    );
                                }

                                this.$refs.inputPassengerNames.value = names.join(',');
                                this.$refs.inputPassengerIds.value = ids.join(',');
                                this.$refs.inputPassengerGenders.value = genders.join(',');

                                this.$refs.inputRoomQuantities.value = JSON.stringify(this.roomQuantities);
                                this.$refs.inputRoomAssignments.value = JSON.stringify(this.seatRoomAssignments);
                                this.$refs.inputPassengerMeals.value = JSON.stringify(this.passengerMeals);
                                this.$refs.inputPackageSelections.value = JSON.stringify(this.seatPackageSelection);
                                this.$refs.inputPassengerServices.value = JSON.stringify(this.passengerServices);

                                this.$refs.inputParentSeats.value = JSON.stringify(cleanExtraSeatsMap);

                                this.$refs.inputPayments.value = JSON.stringify(this.payments);


                                return true;
                            }
                        }));

                    });
                </script>
                <div x-show="openBusModal"
                    class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                    x-cloak>

                    <div class="bg-white border border-slate-100 shadow-2xl rounded-[2.5rem] p-6 max-w-4xl w-full max-h-[92vh] flex flex-col relative text-right"
                        @click.stop>

                        {{-- رأس النافذة المنبثقة --}}
                        <div class="flex justify-between items-center border-b border-slate-100 pb-4 mb-4 flex-shrink-0">
                            <div>
                                <h3 class="text-lg font-black text-slate-900 flex items-center gap-2">🎫 لوحة حجز وإصدار
                                    تذاكر الرحلة</h3>
                                @if ($errors->any())
                                    <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 text-right"
                                        role="alert">
                                        <ul class="list-disc list-inside">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if (session('error'))
                                    <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 text-right">
                                        {{ session('error') }}
                                    </div>
                                @endif
                                <p class="text-slate-400 text-[11px] font-bold mt-0.5"> الرحلة: <span
                                        class="text-blue-600 font-black">{{ $trip->title }}</span> | سعر المقعد الأساسي:
                                    <span class="text-emerald-600 font-black">{{ number_format($trip->price, 2) }} ⃁</span>
                                </p>
                            </div>
                            <button @click="openBusModal = false" type="button"
                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-slate-100 font-bold transition-colors">✕</button>
                        </div>

                        {{-- الفورم --}}
                        <form action="{{ route('agency.trips.addstoreBooking') }}" method="POST"
                            enctype="multipart/form-data" @submit="prepareAndSubmit($event)"
                            class="flex-1 flex flex-col md:flex-row gap-6 overflow-hidden min-h-0">
                            @csrf
                            <input type="hidden" name="trip_id" value="{{ $trip->id }}">

                            {{-- تحويل المقاعد إلى مصفوفة --}}
                            <template x-for="seat in [...selectedSeats].sort((a,b)=>a-b)" :key="'s' + seat">
                                <input type="hidden" name="selected_seats[]" :value="seat">
                            </template>

                            <input type="hidden" x-ref="inputPassengerNames" name="passenger_names">
                            <input type="hidden" x-ref="inputPassengerIds" name="passenger_ids">
                            <input type="hidden" x-ref="inputPassengerGenders" name="passenger_genders">
                            <input type="hidden" x-ref="inputRoomQuantities" name="room_quantities">
                            <input type="hidden" x-ref="inputRoomAssignments" name="room_assignments">
                            <input type="hidden" x-ref="inputPassengerMeals" name="passenger_meals">
                            <input type="hidden" x-ref="inputPackageSelections" name="package_selections">
                            <input type="hidden" x-ref="inputPassengerServices" name="passenger_services">
                            <input type="hidden" x-ref="inputParentSeats" name="parent_seats">
                            <input type="hidden" x-ref="inputPayments" name="payments_data">
                            <input type="hidden" x-ref="inputSharedRoomTypes" name="shared_room_types">

                            {{-- الشق الأيمن --}}
                            <div class="flex-1 overflow-y-auto space-y-4 pr-1 pl-3" style="scrollbar-width: thin;">

                                {{-- كرت معلومات الاتصال وطريقة الدفع --}}
                                <div class="bg-slate-50/70 rounded-2xl p-4 border border-slate-100 space-y-3.5">
                                    <h4
                                        class="text-xs font-black text-slate-800 flex items-center gap-2 border-b border-slate-200/60 pb-2">
                                        👤 بيانات العميل وطريقة الدفع</h4>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="text-[11px] text-slate-500 font-bold mb-1 block">رقم هاتف العميل
                                                *</label>
                                            <input type="tel" name="customer_phone" x-model="customerPhone"
                                                @input="customerPhone = customerPhone.replace(/[^0-9]/g, '').slice(0, 10)"
                                                required pattern="[0-9]{10}" minlength="10" maxlength="10"
                                                inputmode="numeric" placeholder="05xxxxxxxx"
                                                title="يرجى إدخال رقم هاتف مكون من 10 أرقام فقط"
                                                class="w-full bg-white border border-slate-200 focus:border-blue-500 rounded-xl px-3 py-2 text-xs font-medium outline-none">
                                        </div>
                                        <div>
                                            <label class="text-[11px] text-slate-500 font-bold mb-1 block">البريد
                                                الإلكتروني للعميل</label>
                                            <input type="email" name="customer_email" x-model="customerEmail"
                                                placeholder="name@domain.com"
                                                class="w-full bg-white border border-slate-200 focus:border-blue-500 rounded-xl px-3 py-2 text-xs font-medium outline-none">
                                        </div>
                                    </div>

                                    <div class="pt-2 border-t border-slate-200/60 space-y-3">
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="text-[11px] text-slate-500 font-bold block">💳 طرق الدفع (يمكنك
                                                الدفع بأكثر من طريقة) *</label>
                                            <button type="button" @click="addPayment()"
                                                class="text-[10px] font-black text-blue-600 bg-blue-50 hover:bg-blue-100 px-2 py-1.5 rounded-lg transition-colors flex items-center gap-1 active:scale-95">
                                                <span>➕</span> إضافة دفعة أخرى
                                            </button>
                                        </div>

                                        <div class="space-y-2 max-h-40 overflow-y-auto pr-1"
                                            style="scrollbar-width: thin;">
                                            <template x-for="(payment, index) in payments" :key="index">
                                                <div
                                                    class="flex flex-wrap sm:flex-nowrap items-end gap-2 bg-white p-2.5 rounded-xl border border-blue-100 shadow-sm relative">

                                                    <div class="w-full sm:w-auto flex-1">
                                                        <label class="text-[9px] text-slate-500 font-bold mb-1 block">طريقة
                                                            الدفع</label>
                                                        <select x-model="payment.method"
                                                            class="w-full bg-slate-50 border border-slate-200 focus:border-blue-500 rounded-lg px-2 py-1.5 text-xs font-bold outline-none">
                                                            <option value="moyasar">إلكتروني (Moyasar)</option>
                                                            <option value="balance">محفظة الوكالة</option>
                                                            <option value="bank_transfer">تحويل بنكي</option>
                                                            <option value="cash">نقدي (كاش)</option>
                                                        </select>
                                                    </div>

                                                    <div class="w-full sm:w-auto flex-1">
                                                        <label
                                                            class="text-[9px] text-slate-500 font-bold mb-1 block">المبلغ
                                                            (ر.س)</label>
                                                        <input type="number" step="0.01" min="0"
                                                            x-model.number="payment.amount"
                                                            class="w-full bg-slate-50 border border-slate-200 focus:border-blue-500 rounded-lg px-2 py-1.5 text-xs font-bold outline-none text-blue-900"
                                                            placeholder="0.00">
                                                    </div>

                                                    <div class="w-full sm:w-auto flex-1"
                                                        x-show="payment.method !== 'cash'">
                                                        <label class="text-[9px] text-slate-500 font-bold mb-1 block">رقم
                                                            المرجع (اختياري)</label>
                                                        <input type="text" x-model="payment.reference"
                                                            class="w-full bg-slate-50 border border-slate-200 focus:border-blue-500 rounded-lg px-2 py-1.5 text-xs outline-none"
                                                            placeholder="رقم الحوالة/العملية">
                                                    </div>

                                                    <button type="button" @click="removePayment(index)"
                                                        x-show="payments.length > 1"
                                                        class="w-7 h-7 flex items-center justify-center bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-lg shrink-0 transition-colors text-xs font-bold"
                                                        title="حذف هذه الدفعة">
                                                        ✕
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- كرت حجز باقات السكن --}}
                                @php
                                    $hasVisibleRooms =
                                        ($roomOptions['single']['price'] ?? 0) > 0 ||
                                        ($roomOptions['double']['price'] ?? 0) > 0 ||
                                        ($roomOptions['triple']['price'] ?? 0) > 0 ||
                                        ($roomOptions['quad']['price'] ?? 0) > 0;
                                @endphp

                                @if ($hasVisibleRooms)
                                    <div class="bg-slate-50/70 rounded-2xl p-4 border border-slate-100 space-y-4">
                                        <div>
                                            <h4 class="text-xs font-black text-slate-800 flex items-center gap-2">🏢 حجز
                                                وتخصيص باقات السكن المتاحة</h4>
                                            <p class="text-[10px] text-slate-400 font-bold mt-0.5">حدد عدد الغرف المطلوبة
                                                لتسكين أفراد عائلة العميل بالرحلة.</p>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            @if (isset($roomOptions['single']) && ($roomOptions['single']['price'] ?? 0) > 0)
                                                <div
                                                    class="bg-white p-3 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between">
                                                    <div class="flex flex-col mb-2">
                                                        <span class="text-xs font-black text-slate-700">غرفة مفردة
                                                            🛌</span>
                                                        <span class="text-[11px] font-black text-emerald-600 mt-0.5"
                                                            x-text="getRoomPrice('single').toLocaleString() + ' ⃁' "></span>
                                                        @if (($roomOptions['single']['count'] ?? 0) > 0)
                                                            <span class="text-[9px] font-bold text-slate-400 mt-0.5">متبقي:
                                                                {{ $roomOptions['single']['count'] }}</span>
                                                        @endif
                                                    </div>
                                                    @if (($roomOptions['single']['count'] ?? 0) > 0)
                                                        <div
                                                            class="flex items-center bg-slate-50 border border-slate-200 rounded-lg p-1 focus-within:border-blue-500 transition-colors w-full">
                                                            <button type="button"
                                                                @click="roomQuantities.single = Math.max(0, (parseInt(roomQuantities.single) || 0) - 1)"
                                                                :disabled="roomQuantities.single <= 0"
                                                                class="w-7 h-7 flex items-center justify-center bg-white hover:bg-slate-200 text-slate-700 disabled:opacity-30 disabled:cursor-not-allowed rounded-md shadow-sm transition-all font-black text-sm shrink-0 active:scale-95">-</button>
                                                            <input type="number" min="0"
                                                                max="{{ $roomOptions['single']['count'] }}"
                                                                x-model.number="roomQuantities.single"
                                                                class="w-full bg-transparent border-0 text-center font-mono font-bold text-xs outline-none focus:ring-0 p-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                                            <button type="button"
                                                                @click="roomQuantities.single = Math.min({{ $roomOptions['single']['count'] }}, (parseInt(roomQuantities.single) || 0) + 1)"
                                                                :disabled="roomQuantities.single >=
                                                                    {{ $roomOptions['single']['count'] }}"
                                                                class="w-7 h-7 flex items-center justify-center bg-white hover:bg-slate-200 text-slate-700 disabled:opacity-30 disabled:cursor-not-allowed rounded-md shadow-sm transition-all font-black text-sm shrink-0 active:scale-95">+</button>
                                                        </div>
                                                    @else
                                                        <div
                                                            class="w-full bg-red-50 text-red-600 font-black text-center rounded-xl py-1.5 text-[11px] border border-red-100/70 select-none">
                                                            غير متاح ❌</div>
                                                    @endif
                                                </div>
                                            @endif

                                            @if (isset($roomOptions['double']) && ($roomOptions['double']['price'] ?? 0) > 0)
                                                <div
                                                    class="bg-white p-3 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between">
                                                    <div class="flex flex-col mb-2">
                                                        <span class="text-xs font-black text-slate-700">غرفة ثنائية
                                                            👥</span>
                                                        <span class="text-[11px] font-black text-emerald-600 mt-0.5"
                                                            x-text="getRoomPrice('double').toLocaleString() + ' ⃁' "></span>
                                                        @if (($roomOptions['double']['count'] ?? 0) > 0)
                                                            <span class="text-[9px] font-bold text-slate-400 mt-0.5">متبقي:
                                                                {{ $roomOptions['double']['count'] }}</span>
                                                        @endif
                                                    </div>
                                                    @if (($roomOptions['double']['count'] ?? 0) > 0)
                                                        <div
                                                            class="flex items-center bg-slate-50 border border-slate-200 rounded-lg p-1 focus-within:border-blue-500 transition-colors w-full">
                                                            <button type="button"
                                                                @click="roomQuantities.double = Math.max(0, (parseInt(roomQuantities.double) || 0) - 1)"
                                                                :disabled="roomQuantities.double <= 0"
                                                                class="w-7 h-7 flex items-center justify-center bg-white hover:bg-slate-200 text-slate-700 disabled:opacity-30 disabled:cursor-not-allowed rounded-md shadow-sm transition-all font-black text-sm shrink-0 active:scale-95">-</button>
                                                            <input type="number" min="0"
                                                                max="{{ $roomOptions['double']['count'] }}"
                                                                x-model.number="roomQuantities.double"
                                                                class="w-full bg-transparent border-0 text-center font-mono font-bold text-xs outline-none focus:ring-0 p-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                                            <button type="button"
                                                                @click="roomQuantities.double = Math.min({{ $roomOptions['double']['count'] }}, (parseInt(roomQuantities.double) || 0) + 1)"
                                                                :disabled="roomQuantities.double >=
                                                                    {{ $roomOptions['double']['count'] }}"
                                                                class="w-7 h-7 flex items-center justify-center bg-white hover:bg-slate-200 text-slate-700 disabled:opacity-30 disabled:cursor-not-allowed rounded-md shadow-sm transition-all font-black text-sm shrink-0 active:scale-95">+</button>
                                                        </div>
                                                    @else
                                                        <div
                                                            class="w-full bg-red-50 text-red-600 font-black text-center rounded-xl py-1.5 text-[11px] border border-red-100/70 select-none">
                                                            غير متاح ❌</div>
                                                    @endif
                                                </div>
                                            @endif

                                            @if (isset($roomOptions['triple']) && ($roomOptions['triple']['price'] ?? 0) > 0)
                                                <div
                                                    class="bg-white p-3 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between">
                                                    <div class="flex flex-col mb-2">
                                                        <span class="text-xs font-black text-slate-700">غرفة ثلاثية
                                                            👑</span>
                                                        <span class="text-[11px] font-black text-emerald-600 mt-0.5"
                                                            x-text="getRoomPrice('triple').toLocaleString() + ' ⃁' "></span>
                                                        @if (($roomOptions['triple']['count'] ?? 0) > 0)
                                                            <span class="text-[9px] font-bold text-slate-400 mt-0.5">متبقي:
                                                                {{ $roomOptions['triple']['count'] }}</span>
                                                        @endif
                                                    </div>
                                                    @if (($roomOptions['triple']['count'] ?? 0) > 0)
                                                        <div
                                                            class="flex items-center bg-slate-50 border border-slate-200 rounded-lg p-1 focus-within:border-blue-500 transition-colors w-full">
                                                            <button type="button"
                                                                @click="roomQuantities.triple = Math.max(0, (parseInt(roomQuantities.triple) || 0) - 1)"
                                                                :disabled="roomQuantities.triple <= 0"
                                                                class="w-7 h-7 flex items-center justify-center bg-white hover:bg-slate-200 text-slate-700 disabled:opacity-30 disabled:cursor-not-allowed rounded-md shadow-sm transition-all font-black text-sm shrink-0 active:scale-95">-</button>
                                                            <input type="number" min="0"
                                                                max="{{ $roomOptions['triple']['count'] }}"
                                                                x-model.number="roomQuantities.triple"
                                                                class="w-full bg-transparent border-0 text-center font-mono font-bold text-xs outline-none focus:ring-0 p-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                                            <button type="button"
                                                                @click="roomQuantities.triple = Math.min({{ $roomOptions['triple']['count'] }}, (parseInt(roomQuantities.triple) || 0) + 1)"
                                                                :disabled="roomQuantities.triple >=
                                                                    {{ $roomOptions['triple']['count'] }}"
                                                                class="w-7 h-7 flex items-center justify-center bg-white hover:bg-slate-200 text-slate-700 disabled:opacity-30 disabled:cursor-not-allowed rounded-md shadow-sm transition-all font-black text-sm shrink-0 active:scale-95">+</button>
                                                        </div>
                                                    @else
                                                        <div
                                                            class="w-full bg-red-50 text-red-600 font-black text-center rounded-xl py-1.5 text-[11px] border border-red-100/70 select-none">
                                                            غير متاح ❌</div>
                                                    @endif
                                                </div>
                                            @endif

                                            @if (isset($roomOptions['quad']) && ($roomOptions['quad']['price'] ?? 0) > 0)
                                                <div
                                                    class="bg-white p-3 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between">
                                                    <div class="flex flex-col mb-2">
                                                        <span class="text-xs font-black text-slate-700">غرفة رباعية
                                                            🏰</span>
                                                        <span class="text-[11px] font-black text-emerald-600 mt-0.5"
                                                            x-text="getRoomPrice('quad').toLocaleString() + ' ⃁' "></span>
                                                        @if (($roomOptions['quad']['count'] ?? 0) > 0)
                                                            <span class="text-[9px] font-bold text-slate-400 mt-0.5">متبقي:
                                                                {{ $roomOptions['quad']['count'] }}</span>
                                                        @endif
                                                    </div>
                                                    @if (($roomOptions['quad']['count'] ?? 0) > 0)
                                                        <div
                                                            class="flex items-center bg-slate-50 border border-slate-200 rounded-lg p-1 focus-within:border-blue-500 transition-colors w-full">
                                                            <button type="button"
                                                                @click="roomQuantities.quad = Math.max(0, (parseInt(roomQuantities.quad) || 0) - 1)"
                                                                :disabled="roomQuantities.quad <= 0"
                                                                class="w-7 h-7 flex items-center justify-center bg-white hover:bg-slate-200 text-slate-700 disabled:opacity-30 disabled:cursor-not-allowed rounded-md shadow-sm transition-all font-black text-sm shrink-0 active:scale-95">-</button>
                                                            <input type="number" min="0"
                                                                max="{{ $roomOptions['quad']['count'] }}"
                                                                x-model.number="roomQuantities.quad"
                                                                class="w-full bg-transparent border-0 text-center font-mono font-bold text-xs outline-none focus:ring-0 p-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                                            <button type="button"
                                                                @click="roomQuantities.quad = Math.min({{ $roomOptions['quad']['count'] }}, (parseInt(roomQuantities.quad) || 0) + 1)"
                                                                :disabled="roomQuantities.quad >=
                                                                    {{ $roomOptions['quad']['count'] }}"
                                                                class="w-7 h-7 flex items-center justify-center bg-white hover:bg-slate-200 text-slate-700 disabled:opacity-30 disabled:cursor-not-allowed rounded-md shadow-sm transition-all font-black text-sm shrink-0 active:scale-95">+</button>
                                                        </div>
                                                    @else
                                                        <div
                                                            class="w-full bg-red-50 text-red-600 font-black text-center rounded-xl py-1.5 text-[11px] border border-red-100/70 select-none">
                                                            غير متاح ❌</div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- كشف التفاصيل المختارة --}}
                                <div x-show="selectedSeats.length > 0 || roomQuantities.single > 0 || roomQuantities.double > 0 || roomQuantities.triple > 0 || roomQuantities.quad > 0"
                                    x-transition
                                    class="bg-amber-50/50 rounded-2xl p-4 border border-amber-200/70 space-y-2 text-xs">
                                    <h4
                                        class="font-black text-slate-800 flex items-center gap-1.5 border-b border-amber-200 pb-1.5">
                                        📋 كشف تفاصيل البيانات المختارة:</h4>
                                    <div x-show="selectedSeats.length > 0"
                                        class="flex justify-between items-start gap-2 text-slate-600">
                                        <span
                                            class="font-bold shrink-0">{{ $isFlight ? 'المقاعد المحددة بالطائرة:' : 'المقاعد المحددة بالباص:' }}</span>
                                        <div class="flex flex-wrap gap-1 justify-end">
                                            <template x-for="s in [...selectedSeats].sort((a,b)=>a-b)"
                                                :key="'chip-' + s">
                                                <button type="button" @click="cancelSeat(s)" title="إلغاء المقعد"
                                                    class="inline-flex items-center gap-1 font-mono bg-white px-2 py-0.5 rounded border border-amber-200 font-black text-blue-600 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-300 transition-all">
                                                    <span x-text="s"></span>
                                                    <span class="text-[10px]">✕</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <div x-show="selectedSeats.length > 0"
                                        class="pt-1.5 border-t border-dashed border-amber-200 flex justify-between items-center text-slate-500 text-[11px]">
                                        <span class="font-bold">✨ الخدمات الإضافية:</span>
                                        <span class="font-black text-indigo-700" x-text="getServicesSummary()"></span>
                                    </div>

                                    <div class="space-y-1">
                                        <template x-if="roomQuantities.single > 0">
                                            <div class="flex justify-between items-center text-slate-600">
                                                <span>غرف نوم مفردة:</span>
                                                <span class="font-black text-slate-800"
                                                    x-text="roomQuantities.single + ' غرف' "></span>
                                            </div>
                                        </template>
                                        <template x-if="roomQuantities.double > 0">
                                            <div class="flex justify-between items-center text-slate-600">
                                                <span>غرف نوم ثنائية:</span>
                                                <span class="font-black text-slate-800"
                                                    x-text="roomQuantities.double + ' غرف' "></span>
                                            </div>
                                        </template>
                                        <template x-if="roomQuantities.triple > 0">
                                            <div class="flex justify-between items-center text-slate-600">
                                                <span>غرف نوم ثلاثية:</span>
                                                <span class="font-black text-slate-800"
                                                    x-text="roomQuantities.triple + ' غرف' "></span>
                                            </div>
                                        </template>
                                        <template x-if="roomQuantities.quad > 0">
                                            <div class="flex justify-between items-center text-slate-600">
                                                <span>غرف نوم رباعية:</span>
                                                <span class="font-black text-slate-800"
                                                    x-text="roomQuantities.quad + ' غرف' "></span>
                                            </div>
                                        </template>
                                    </div>

                                    <div x-show="selectedSeats.length > 0"
                                        class="pt-1.5 border-t border-dashed border-amber-200 flex justify-between items-center text-slate-500 text-[11px]">
                                        <span class="font-bold">الوجبات والباقات المضافة للركاب:</span>
                                        <span class="font-black text-amber-800" x-text="getMealsSummary()"></span>
                                    </div>
                                </div>


                                {{-- كرت التكلفة الاجمالية --}}
                                <div class="bg-blue-900 text-white rounded-2xl p-4 space-y-3 shadow-lg shadow-blue-900/20">
                                    <div class="flex items-center justify-between">
                                        <div class="space-y-0.5">
                                            <span class="text-[10px] font-bold text-blue-200 block">إجمالي قيمة الفاتورة
                                                النهائية</span>
                                            <span class="text-xs font-bold text-white block">المقاعد المحجوزة: <span
                                                    x-text="selectedSeats.length"
                                                    class="font-mono text-amber-300 font-black"></span> تذاكر</span>
                                        </div>
                                        <div class="text-left">
                                            <span class="text-xl font-black text-amber-300 font-mono"
                                                x-text="(Number(calculateTotal()) || 0).toLocaleString()">0</span>
                                            <span class="text-[16px] font-black text-blue-100 mr-1">ر.س</span>
                                        </div>
                                    </div>

                                    <template x-if="calculateTotalPaid() > 0">
                                        <div class="pt-2 border-t border-blue-800/80 space-y-1 text-xs">
                                            <div class="flex justify-between text-emerald-300 font-bold">
                                                <span>💵 إجمالي المدفوع الآن:</span>
                                                <span class="font-mono"
                                                    x-text="(calculateTotalPaid() || 0).toLocaleString() + ' ر.س'"></span>
                                            </div>
                                            <div class="flex justify-between font-black"
                                                :class="(calculateTotal() - calculateTotalPaid()) > 0 ? 'text-rose-300' :
                                                    'text-slate-300'">
                                                <span
                                                    x-text="(calculateTotal() - calculateTotalPaid()) > 0 ? '💳 المتبقي (غير مدفوع):' : '💳 المتبقي للدفع (مكتمل):'"></span>
                                                <span class="font-mono text-sm"
                                                    x-text="Math.max(0, (Number(calculateTotal()) || 0) - (Number(calculateTotalPaid()) || 0)).toLocaleString() + ' ر.س'"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="pt-1">
                                    <button type="submit"
                                        :disabled="selectedSeats.length === 0 || hasInvalidAssignments()"
                                        class="w-full py-3.5 bg-blue-600 text-white rounded-2xl font-black text-sm shadow-md shadow-blue-200 hover:bg-blue-700 transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                        <span>✨</span> اعتماد وتثبيت حجز التذاكر الآن
                                    </button>

                                </div>
                                <p x-show="hasInvalidAssignments()"
                                    class="text-[11px] font-black text-rose-600 text-center mt-2">
                                    ⚠️ يوجد مقعد بدون سكن أو بدون نوع الغرفة المشتركة
                                </p>
                            </div>

                            {{-- الشق الأيسر: خريطة مقاعد الحافلة أو الطائرة --}}
                            <div class="w-full md:w-[380px] bg-slate-50 rounded-3xl p-4 border border-slate-100 flex flex-col items-center justify-between overflow-hidden"
                                dir="ltr">
                                <div class="w-full mb-3" dir="rtl">
                                    <div
                                        class="text-center text-[10px] font-bold p-2.5 bg-white rounded-xl border shadow-sm tracking-wide flex flex-wrap items-center justify-center gap-3">
                                        <span class="flex items-center gap-1.5"><span
                                                class="w-2.5 h-2.5 bg-amber-400 rounded-full"></span> مقعد محدد</span>
                                        <span class="flex items-center gap-1.5"><span
                                                class="w-2.5 h-2.5 bg-pink-200 rounded-full"></span> حجز نساء</span>
                                        <span class="flex items-center gap-1.5"><span
                                                class="w-2.5 h-2.5 bg-blue-200 rounded-full"></span> حجز رجال</span>
                                        <span class="flex items-center gap-1.5"><span
                                                class="w-2.5 h-2.5 bg-slate-700 rounded-full"></span> مقعد مغلق 🔒</span>
                                    </div>
                                </div>

                                <div class="w-full flex-1 overflow-y-auto pb-2" style="scrollbar-width: thin;">
                                    <div class="relative shadow-xl overflow-hidden rounded-[2rem] max-w-[310px] mx-auto scale-95 origin-top"
                                        style="@if ($isFlight) background: #f8fafc; border: 2px solid #e2e8f0; @else background-image: url('{{ $busUrlImage }}'); background-size: 100% 100%; background-position: top center; background-repeat: no-repeat; @endif">

                                        @if ($isFlight)
                                            <div
                                                class="w-full bg-slate-800 text-white text-center py-4 rounded-t-[2rem] font-black text-xs tracking-wider flex flex-col items-center justify-center gap-1">
                                                <span>✈️ مقدمة وعجلة الطائرة (COCKPIT)</span>
                                                <div class="w-12 h-1 bg-slate-600 rounded-full mt-1"></div>
                                            </div>
                                        @endif

                                        <div
                                            class="grid @if ($isFlight) grid-cols-7 px-4 pt-4 @else grid-cols-5 px-6 pr-[3.25rem] pt-[11rem] @endif gap-y-3.5 gap-x-1.5 relative z-10 pb-8 seat-grid">
                                            @foreach ($layout_config as $rowString)
                                                @foreach (str_split($rowString) as $cellType)
                                                    @php
                                                        $isBooked = false;
                                                        $btnClass = '';
                                                        $iconClass = '';
                                                        $seatShape = '';
                                                        $isStaffSeat = false;
                                                    @endphp
                                                    @if ($cellType == 'S' || $cellType == 'X')
                                                        @php
                                                            $i = $seatCounter++;
                                                            $isStaffSeat = $cellType == 'X';
                                                            $isBooked = $isStaffSeat
                                                                ? false
                                                                : isset($bookedSeatsMap[$i]);
                                                            $seatDetail = $booked_seats_details[$i] ?? null;
                                                            $roomType = is_array($seatDetail)
                                                                ? $seatDetail['room_type'] ??
                                                                    ($seatDetail['assignment'] ?? null)
                                                                : $seatDetail->room_type ??
                                                                    ($seatDetail->assignment ?? null);
                                                            $isShared = $roomType === 'shared';
                                                            $rawGender = is_array($seatDetail)
                                                                ? $seatDetail['gender'] ?? null
                                                                : $seatDetail->gender ?? null;
                                                            $bookedGender = $rawGender
                                                                ? strtolower(trim($rawGender))
                                                                : null;
                                                            $isFemaleZone = $isAutoSplit && $i > $halfSeats;
                                                            $seatShape = $isFemaleZone ? 'female' : 'male';
                                                            $isBlocked = false;
                                                            if ($isBooked && $seatDetail) {
                                                                $name = is_array($seatDetail)
                                                                    ? $seatDetail['name'] ?? null
                                                                    : $seatDetail->name ?? null;
                                                                if (
                                                                    $roomType === 'blocked' ||
                                                                    $roomType === 'closed' ||
                                                                    (is_string($name) &&
                                                                        (str_contains($name, 'مغلق') ||
                                                                            str_contains($name, 'حظر') ||
                                                                            str_contains($name, 'إغلاق')))
                                                                ) {
                                                                    $isBlocked = true;
                                                                }
                                                            }
                                                            if ($isBlocked) {
                                                                $btnClass =
                                                                    'bg-slate-700 border-slate-800 text-slate-100 cursor-pointer hover:bg-slate-800 transition-all';
                                                                $iconClass = 'text-slate-400';
                                                            } elseif ($isBooked) {
                                                                if (
                                                                    $bookedGender === 'female' ||
                                                                    ($isFemaleZone && !$bookedGender)
                                                                ) {
                                                                    $btnClass =
                                                                        'bg-gradient-to-b from-pink-100 to-pink-200 border-pink-400 text-pink-800 cursor-pointer';
                                                                    $iconClass = 'text-pink-500';
                                                                    $seatShape = 'female';
                                                                } else {
                                                                    $btnClass =
                                                                        'bg-gradient-to-b from-blue-100 to-blue-200 border-blue-400 text-blue-800 cursor-pointer';
                                                                    $iconClass = 'text-blue-600';
                                                                }
                                                            } else {
                                                                $btnClass = $isFemaleZone
                                                                    ? 'bg-white border-pink-200 text-pink-700 hover:bg-pink-50'
                                                                    : 'bg-white border-blue-200 text-blue-700 hover:bg-blue-50';
                                                                $iconClass = $isFemaleZone
                                                                    ? 'text-pink-300'
                                                                    : 'text-blue-300';
                                                            }
                                                        @endphp

                                                        @if ($isStaffSeat)
                                                            <button type="button"
                                                                @click="showTicket({{ $i }})"
                                                                class="relative w-full h-10 rounded-t-lg rounded-b-md"></button>
                                                        @else
                                                            <button type="button"
                                                                class="relative w-full h-11 rounded-t-xl rounded-b-md transition-all flex flex-col items-center justify-center border shadow-sm"
                                                                @if ($isBooked) @click="showTicket({{ $i }})" :class="'{{ $btnClass }}'" @else @click="
                                                                if(!isSelected({{ $i }})){ toggleSeat({{ $i }}); } 
                                                                activeModalSeat = {{ $i }};
                                                                initModalSeat({{ $i }});
                                                                activeSeatZone = '{{ $isAutoSplit ? ($isFemaleZone ? 'female' : 'male') : 'any' }}';
                                                                if (activeSeatZone === 'female') { passengerGenders[{{ $i }}] = 'female'; } else if (activeSeatZone === 'male') { passengerGenders[{{ $i }}] = 'male'; }
                                                                openPassengerModal = true;
                                                            " :class="isSelected({{ $i }}) ? 'bg-gradient-to-b from-amber-400 to-amber-500 border-amber-600 text-amber-950 shadow-md scale-95' : '{{ $btnClass }}'" @endif>
                                                                <span
                                                                    class="text-[10px] font-black z-10 mb-0.5">{{ $i }}</span>
                                                                <div class="flex flex-col items-center scale-75"
                                                                    :class="isSelected({{ $i }}) ?
                                                                        'text-amber-100' : '{{ $iconClass }}'">
                                                                    @if ($isBlocked)
                                                                        <span class="text-xs">🔒</span>
                                                                    @elseif ($seatShape === 'female')
                                                                        <div class="w-2.5 h-2.5 bg-current rounded-full">
                                                                        </div>
                                                                        <div
                                                                            class="w-3.5 h-2 bg-current rounded-t-full -mt-0.5">
                                                                        </div>
                                                                    @else
                                                                        <div class="w-2 h-2 bg-current rounded-sm"></div>
                                                                        <div
                                                                            class="w-3.5 h-1.5 bg-current rounded-t-md -mt-0.5">
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </button>
                                                        @endif
                                                    @endif
                                                    @if ($cellType == '.')
                                                        <div class="w-full h-11 flex items-center justify-center">
                                                            <span
                                                                class="text-[9px] text-slate-300 font-bold tracking-widest rotate-90">•••</span>
                                                        </div>
                                                    @elseif($cellType == '-')
                                                        <div class="w-full h-2 bg-zinc-300 rounded-full"></div>
                                                    @elseif($cellType == 'D')
                                                        <div class="col-span-2"></div>
                                                    @endif
                                                @endforeach
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>

                {{-- 2. 👤 نافذة إدخال بيانات المسافر --}}
                <div x-show="openPassengerModal"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
                    @click="openPassengerModal = false" style="display: none;">

                    <div class="bg-white border border-slate-100 shadow-2xl rounded-[2rem] p-6 max-w-[480px] w-full max-h-[85vh] flex flex-col transform text-right text-slate-800 relative overflow-hidden"
                        @click.stop>

                        <template x-if="activeModalSeat">
                            <div class="flex flex-col flex-1 overflow-hidden relative z-10">

                                <div
                                    class="flex justify-between items-center border-b border-slate-100 pb-3.5 mb-4 flex-shrink-0">
                                    <h3 class="text-base font-black flex items-center gap-2.5 text-slate-900">
                                        <span
                                            class="w-9 h-9 flex items-center justify-center bg-indigo-50 text-indigo-600 rounded-2xl text-sm shadow-sm">💺</span>
                                        <span>بيانات راكب المقعد (<span x-text="activeModalSeat"
                                                class="text-indigo-600 font-black"></span>)</span>
                                    </h3>
                                    <button @click="openPassengerModal = false"
                                        class="w-8 h-8 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all text-xs font-bold">✕</button>
                                </div>

                                <div class="flex-1 overflow-y-auto space-y-4 pl-2 pr-0.5 pb-2 min-h-0"
                                    style="scrollbar-width: thin;">

                                    {{-- إغلاق المقعد للوكالة --}}
                                    <div
                                        class="bg-slate-100 rounded-2xl p-4 border border-slate-200 text-right space-y-2 mb-2">
                                        <div class="flex justify-between items-center">
                                            <span class="text-[11px] text-slate-700 font-black">🚫 إدارة مقاعد الوكالة
                                                الخاصة:</span>
                                            <span class="text-[9px] text-slate-400 font-bold">تعطيل المقعد وحجبه عن
                                                الحجوزات العامة</span>
                                        </div>
                                        <form action="{{ route('agency.trips.addstoreBooking') }}" method="POST"
                                            class="w-full">
                                            @csrf
                                            <input type="hidden" name="trip_id" value="{{ $trip->id }}">
                                            <input type="hidden" name="selected_seats[]" :value="activeModalSeat">
                                            <input type="hidden" name="customer_phone" value="0500000000">
                                            <input type="hidden" name="passenger_names" value="مقعد مغلق للوكالة">
                                            <input type="hidden" name="passenger_ids" value="0000000000">
                                            <input type="hidden" name="passenger_genders" value="male">
                                            <input type="hidden" name="room_type" value="">
                                            <input type="hidden" name="is_locked" value="1">
                                            <input type="hidden" name="room_assignments"
                                                :value="JSON.stringify({
                                                    [activeModalSeat]: null
                                                })">
                                            <input type="hidden" name="package_selections"
                                                :value="JSON.stringify({
                                                    [activeModalSeat]: 'custom'
                                                })">
                                            <input type="hidden" name="payment_method" value="balance">
                                            <input type="hidden" name="room_quantities"
                                                value='{"single":0,"double":0,"triple":0,"quad":0}'>

                                            <button type="submit"
                                                class="w-full py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-black text-xs rounded-xl transition-all shadow-sm flex items-center justify-center gap-1.5">
                                                <span>🔒</span> إغلاق هذا المقعد (حظر اختياري للوكالة)
                                            </button>
                                        </form>
                                    </div>

                                    <div class="space-y-3.5">
                                        <div>
                                            <label class="text-[11px] text-slate-500 mb-1 block font-bold">الاسم الكامل
                                                للمسافر: *</label>
                                            <input type="text" x-model="passengerNames[activeModalSeat]"
                                                placeholder="اكتب الاسم الرباعي للمسافر"
                                                class="w-full bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-xl px-4 py-2.5 text-xs outline-none font-medium">
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label
                                                    class="text-[10px] text-slate-400 font-black block mb-1 uppercase tracking-wider">رقم
                                                    الهوية / الإقامة:</label>
                                                <input type="text" x-model="passengerIds[activeModalSeat]"
                                                    @input="passengerIds[activeModalSeat] = $event.target.value.replace(/[^0-9]/g, '').slice(0, 10)"
                                                    placeholder="10 أرقام فقط" maxlength="10" minlength="10" required
                                                    inputmode="numeric" pattern="[0-9]{10}"
                                                    class="w-full text-xs font-mono font-bold text-slate-700 bg-slate-50 px-2 py-2.5 rounded-xl border border-slate-200/60 shadow-sm text-center focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all">
                                            </div>

                                            <div>
                                                <label
                                                    class="text-[10px] text-slate-400 font-black block mb-1 uppercase tracking-wider">الجنس:</label>
                                                <div class="grid gap-2"
                                                    :class="activeSeatZone === 'any' ? 'grid-cols-2' : 'grid-cols-1'">
                                                    <button type="button"
                                                        x-show="activeSeatZone === 'any' || activeSeatZone === 'male'"
                                                        @click="passengerGenders[activeModalSeat] = 'male'"
                                                        class="flex items-center justify-center gap-1 py-2.5 rounded-xl border transition-all text-xs font-black select-none focus:outline-none"
                                                        :class="passengerGenders[activeModalSeat] === 'male' ?
                                                            'bg-indigo-50 border-indigo-200 text-indigo-700 ring-1 ring-indigo-500' :
                                                            'bg-slate-50 border-slate-200/60 hover:bg-slate-100 text-slate-600'"><span>👨
                                                            ذكر</span></button>
                                                    <button type="button"
                                                        x-show="activeSeatZone === 'any' || activeSeatZone === 'female'"
                                                        @click="passengerGenders[activeModalSeat] = 'female'"
                                                        class="flex items-center justify-center gap-1 py-2.5 rounded-xl border transition-all text-xs font-black select-none focus:outline-none"
                                                        :class="passengerGenders[activeModalSeat] === 'female' ?
                                                            'bg-rose-50 border-rose-200 text-rose-700 ring-1 ring-rose-500' :
                                                            'bg-slate-50 border-slate-200/60 hover:bg-slate-100 text-slate-600'"><span>👩
                                                            أنثى</span></button>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- كرت إضافة مقاعد إضافية للمسافر --}}
                                        @if ($trip->allow_to_add_seat)
                                            <div x-data="{ enableExtraSeats: false }"
                                                x-effect="enableExtraSeats = Boolean(passengerExtraSeats[activeModalSeat] && passengerExtraSeats[activeModalSeat].length > 0)"
                                                class="mt-4 bg-indigo-50/60 p-4 rounded-2xl border border-indigo-100 transition-all">

                                                <label
                                                    class="flex items-center justify-between cursor-pointer select-none">
                                                    <div class="flex items-center gap-2">
                                                        <span>💺</span>
                                                        <span class="text-[11px] text-indigo-800 font-black">
                                                            إضافة مقاعد إضافية لهذا المسافر (لتوسعة المساحة)
                                                        </span>
                                                    </div>
                                                    <input type="checkbox" x-model="enableExtraSeats"
                                                        @change="
                 if (!enableExtraSeats && passengerExtraSeats[activeModalSeat]) { 
                    passengerExtraSeats[activeModalSeat].forEach(extra => {
                        const extraNum = Number(extra);
                        selectedSeats = selectedSeats.filter(s => Number(s) !== extraNum);
                        delete seatRoomAssignments[extraNum];
                        delete seatPackageSelection[extraNum];
                        if(passengerData) delete passengerData[extraNum];
                    });
                    passengerExtraSeats[activeModalSeat] = []; 
                 }
                  "
                                                        class="text-indigo-600 rounded border-slate-300 w-4 h-4 focus:ring-indigo-500 cursor-pointer">
                                                </label>

                                                <div x-show="enableExtraSeats" x-transition
                                                    class="mt-3 pt-3 border-t border-indigo-100/80">
                                                    <p class="text-[9px] text-indigo-600 mb-3 font-bold">
                                                        اختر المقاعد الإضافية من القائمة لتحديدها فوراً لهذا المسافر بدون
                                                        وجبات أو خدمات.
                                                    </p>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <template x-for="seat in getAvailableExtraSeats()"
                                                            :key="seat">
                                                            <label
                                                                class="flex items-center gap-2.5 p-2.5 bg-white rounded-xl border border-indigo-100 cursor-pointer hover:border-indigo-300 transition-all">
                                                                <input type="checkbox" :value="seat"
                                                                    :checked="passengerExtraSeats[activeModalSeat] &&
                                                                        passengerExtraSeats[activeModalSeat].map(Number)
                                                                        .includes(Number(seat))"
                                                                    @change="toggleExtraSeat(Number(seat))"
                                                                    class="text-indigo-600 rounded border-slate-300 w-4 h-4 focus:ring-indigo-500">
                                                                <span class="text-xs font-bold text-slate-700"
                                                                    x-text="'مقعد رقم ' + seat"></span>
                                                            </label>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- كرت الخدمات الإضافية المتاحة لكل مسافر --}}
                                        <template x-if="extraServices.length > 0 && activeModalSeat">
                                            <div class="bg-slate-50/80 rounded-2xl p-4 border border-slate-100 space-y-3">
                                                <h4
                                                    class="text-xs font-black text-slate-800 flex items-center gap-2 border-b border-slate-200/60 pb-2">
                                                    ✨ الخدمات الإضافية المتاحة للمسافر</h4>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    <template x-for="(service, sIdx) in extraServices"
                                                        :key="sIdx">
                                                        <label
                                                            class="flex items-start gap-2.5 p-2 bg-white rounded-xl border border-slate-200 hover:border-blue-300 transition-all cursor-pointer">
                                                            <input type="checkbox"
                                                                x-model="passengerServices[activeModalSeat][sIdx]"
                                                                class="mt-0.5 rounded text-blue-600 focus:ring-blue-500 w-4 h-4">
                                                            <div class="flex-1 min-w-0">
                                                                <div class="flex justify-between items-center gap-1">
                                                                    <span class="text-xs font-bold text-slate-800 truncate"
                                                                        x-text="service.name"></span>
                                                                    <span
                                                                        class="text-[11px] font-black text-emerald-600 font-mono shrink-0"
                                                                        x-text="parseFloat(service.price).toLocaleString() + ' ⃁'"></span>
                                                                </div>
                                                                <p class="text-[10px] text-slate-400 font-medium leading-tight mt-0.5"
                                                                    x-text="service.description"></p>
                                                            </div>
                                                        </label>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        {{-- تصنيف السكن --}}
                                        <div>
                                            <label class="text-[11px] text-slate-500 mb-2 block font-bold">تصنيف المقعد أو
                                                نوع الغرفة للراكب: *</label>
                                            <div class="grid grid-cols-2 gap-2 mb-3">
                                                @if ($trip->allows_shared_rooms)
                                                    <button type="button"
                                                        @click="setAssignment(activeModalSeat, 'shared'); delete selectedRoomForSeat[activeModalSeat];"
                                                        class="p-3 border rounded-xl text-xs font-black transition-all flex flex-col items-center justify-center gap-1.5"
                                                        :class="seatRoomAssignments[activeModalSeat] === 'shared' ?
                                                            'border-indigo-600 bg-indigo-50 text-indigo-700 ring-2 ring-indigo-500/20 shadow-sm' :
                                                            'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                                                        <span class="text-base">👥</span><span>مشارك (shared)</span>
                                                    </button>
                                                @endif

                                                <button type="button"
                                                    @click="setAssignment(activeModalSeat, selectedRoomForSeat[activeModalSeat] || '')"
                                                    class="p-3 border rounded-xl text-xs font-black transition-all flex flex-col items-center justify-center gap-1.5"
                                                    :class="(seatRoomAssignments[activeModalSeat] !== 'shared' &&
                                                        seatRoomAssignments[activeModalSeat] !== 'blocked') ?
                                                    'border-emerald-600 bg-emerald-50 text-emerald-700 ring-2 ring-emerald-500/20 shadow-sm' :
                                                    'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                                                    <span class="text-base">🏢</span><span>ضمن غرفة محجوزة</span>
                                                </button>
                                            </div>

                                            {{-- اختيار نوع الغرفة المشتركة (مطابق لصفحة العميل) --}}
                                            <div x-show="seatRoomAssignments[activeModalSeat] === 'shared'" x-transition
                                                class="mb-3 p-3 bg-indigo-50/50 border border-indigo-100 rounded-xl space-y-2">
                                                <label class="text-[11px] text-slate-600 font-bold block">
                                                    حدد نوع الغرفة المشتركة: <span class="text-red-500">*</span>
                                                </label>
                                                <p x-show="seatRoomAssignments[activeModalSeat] === 'shared' && !sharedRoomTypes[activeModalSeat]"
                                                    class="text-[10px] font-black text-rose-600">⚠️ اختر نوع الغرفة
                                                    المشتركة لإكمال الحجز</p>
                                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                    <template x-for="type in getAvailableSharedRoomTypes()"
                                                        :key="type.id">
                                                        <button type="button"
                                                            @click="sharedRoomTypes[activeModalSeat] = type.id"
                                                            :class="sharedRoomTypes[activeModalSeat] === type.id ?
                                                                'bg-indigo-600 text-white border-indigo-600 shadow-sm' :
                                                                'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                                            class="py-2 border rounded-xl text-xs font-bold transition-all"
                                                            x-text="type.name"></button>
                                                    </template>
                                                </div>
                                            </div>

                                            <div x-show="seatRoomAssignments[activeModalSeat] !== 'shared' && seatRoomAssignments[activeModalSeat] !== 'blocked'"
                                                x-transition
                                                class="p-3 bg-emerald-50/60 rounded-xl border border-emerald-200 space-y-2">
                                                <span class="text-[10px] font-black text-emerald-800 block">حدد الغرفة
                                                    المحجوزة لتخصيص هذا المسافر بها:</span>
                                                <template x-if="getSelectedRoomsList().length === 0">
                                                    <p
                                                        class="text-[10px] font-bold text-rose-600 bg-white p-2 rounded-lg border border-rose-100 text-center">
                                                        ⚠️ لم تقم بتحديد/إضافة أي غرف في الفاتورة في الأعلى بعد.</p>
                                                </template>
                                                <div class="grid grid-cols-1 gap-2">
                                                    <template x-for="room in getSelectedRoomsList()"
                                                        :key="room.key">
                                                        <label
                                                            @click="if (!isRoomFull(room.key, room.count, activeModalSeat)) { selectedRoomForSeat[activeModalSeat] = room.key; setAssignment(activeModalSeat, room.key); }"
                                                            class="flex items-center justify-between p-2.5 rounded-xl border shadow-xs text-xs font-bold transition-all"
                                                            :class="{
                                                                'opacity-50 cursor-not-allowed bg-slate-100 border-slate-200 text-slate-400': isRoomFull(
                                                                    room.key, room.count, activeModalSeat
                                                                ),
                                                                'border-emerald-500 bg-emerald-100/30 text-emerald-900 ring-1 ring-emerald-400 cursor-pointer': selectedRoomForSeat[
                                                                        activeModalSeat] === room
                                                                    .key,
                                                                'border-slate-200 text-slate-700 hover:bg-slate-50 cursor-pointer': selectedRoomForSeat[
                                                                    activeModalSeat] !== room.key && !isRoomFull(
                                                                    room.key, room.count, activeModalSeat)
                                                            }">
                                                            <div class="flex items-center gap-2">
                                                                <input type="radio"
                                                                    :name="'room_select_' + activeModalSeat"
                                                                    :value="room.key"
                                                                    :checked="selectedRoomForSeat[activeModalSeat] === room.key"
                                                                    :disabled="isRoomFull(room.key, room.count, activeModalSeat)"
                                                                    class="text-emerald-600 focus:ring-emerald-500 disabled:opacity-50">
                                                                <span x-text="room.name"></span>
                                                                <template
                                                                    x-if="isRoomFull(room.key, room.count, activeModalSeat)">
                                                                    <span
                                                                        class="text-[9px] bg-rose-100 text-rose-700 px-1.5 py-0.5 rounded font-black">مكتملة</span>
                                                                </template>
                                                            </div>
                                                            <span
                                                                class="text-[10px] font-black px-2 py-0.5 rounded-md transition-colors"
                                                                :class="isRoomFull(room.key, room.count, activeModalSeat) ?
                                                                    'bg-rose-100 text-rose-700' :
                                                                    'bg-slate-100 text-slate-500'"
                                                                x-text="'المسافرون: ' + getAssignedCount(room.key) + ' / ' + getRoomMaxCapacity(room.key, room.count)"></span>
                                                        </label>
                                                    </template>
                                                </div>

                                                <template
                                                    x-if="['single', 'double', 'triple', 'quad'].includes(seatRoomAssignments[activeModalSeat]) && roomPackages[seatRoomAssignments[activeModalSeat]]">
                                                    <div
                                                        class="mt-3 p-3 bg-amber-50 rounded-xl border border-amber-200/80 space-y-2">
                                                        <div class="flex items-center justify-between">
                                                            <span
                                                                class="text-xs font-black text-amber-900 flex items-center gap-1.5"><span>🎁</span>
                                                                باقة وجبات الغرفة المختارة (<span
                                                                    x-text="roomPackages[seatRoomAssignments[activeModalSeat]].name"></span>)</span>
                                                            <button type="button"
                                                                @click="seatPackageSelection[activeModalSeat] = (seatPackageSelection[activeModalSeat] === 'package' ? 'custom' : 'package'); if (seatPackageSelection[activeModalSeat] === 'package' && roomPackages[seatRoomAssignments[activeModalSeat]]?.meals) { passengerMeals[activeModalSeat] = { ...roomPackages[seatRoomAssignments[activeModalSeat]].meals }; }"
                                                                class="px-2.5 py-1 rounded-lg text-[10px] font-black transition-all"
                                                                :class="seatPackageSelection[activeModalSeat] === 'package' ?
                                                                    'bg-amber-600 text-white shadow-sm' :
                                                                    'bg-white text-amber-800 border border-amber-300'">
                                                                <span
                                                                    x-text="seatPackageSelection[activeModalSeat] === 'package' ? '✓ مفعلة بباقة الغرفة' : 'تفعيل باقة الغرفة'"></span>
                                                            </button>
                                                        </div>
                                                        <div class="flex flex-wrap gap-2 pt-1 text-[11px] font-bold">
                                                            <span
                                                                class="px-2.5 py-1 rounded-lg border text-[10px] flex items-center gap-1"
                                                                :class="roomPackages[seatRoomAssignments[activeModalSeat]]
                                                                    ?.meals?.breakfast ?
                                                                    'bg-emerald-100 border-emerald-300 text-emerald-800' :
                                                                    'bg-slate-100 border-slate-200 text-slate-400 line-through'">🍳
                                                                إفطار <span
                                                                    x-text="roomPackages[seatRoomAssignments[activeModalSeat]]?.meals?.breakfast ? '✓ (مشمول)' : '✕ (غير مشمول)'"></span></span>
                                                            <span
                                                                class="px-2.5 py-1 rounded-lg border text-[10px] flex items-center gap-1"
                                                                :class="roomPackages[seatRoomAssignments[activeModalSeat]]
                                                                    ?.meals?.lunch ?
                                                                    'bg-emerald-100 border-emerald-300 text-emerald-800' :
                                                                    'bg-slate-100 border-slate-200 text-slate-400 line-through'">🍗
                                                                غداء <span
                                                                    x-text="roomPackages[seatRoomAssignments[activeModalSeat]]?.meals?.lunch ? '✓ (مشمول)' : '✕ (غير مشمول)'"></span></span>
                                                            <span
                                                                class="px-2.5 py-1 rounded-lg border text-[10px] flex items-center gap-1"
                                                                :class="roomPackages[seatRoomAssignments[activeModalSeat]]
                                                                    ?.meals?.dinner ?
                                                                    'bg-emerald-100 border-emerald-300 text-emerald-800' :
                                                                    'bg-slate-100 border-slate-200 text-slate-400 line-through'">🍔
                                                                عشاء <span
                                                                    x-text="roomPackages[seatRoomAssignments[activeModalSeat]]?.meals?.dinner ? '✓ (مشمول)' : '✕ (غير مشمول)'"></span></span>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        {{-- وجبات الطعام --}}
                                        <div class="bg-amber-50/40 rounded-2xl p-4 border border-amber-100 space-y-3">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h4
                                                        class="text-xs font-black text-amber-900 flex items-center gap-1.5">
                                                        🍽 وجبات الطعام التغذوية المتاحة بالرحلة</h4>
                                                    <p class="text-[10px] text-amber-600 font-bold mt-0.5">تتغير الخيارات
                                                        تلقائياً وتُقفل عند اختيار وضع الباقة المعتمدة للغرفة.</p>
                                                </div>
                                                <template x-if="seatPackageSelection[activeModalSeat] === 'package'">
                                                    <span
                                                        class="px-2 py-0.5 bg-amber-200 text-amber-900 text-[9px] font-black rounded-md">مشمول
                                                        بالباقة</span>
                                                </template>
                                            </div>

                                            <div class="space-y-2"
                                                :class="seatPackageSelection[activeModalSeat] === 'package' ?
                                                    'opacity-80 pointer-events-none' : ''">
                                                @if (!empty($mealOptions['individual']['breakfast']['active']))
                                                    <label
                                                        class="flex items-center justify-between p-2.5 bg-white rounded-xl border border-amber-100 hover:border-amber-300 transition-all cursor-pointer">
                                                        <div class="flex items-center gap-2">
                                                            <input type="checkbox"
                                                                x-model="passengerMeals[activeModalSeat].breakfast"
                                                                class="rounded text-amber-600 focus:ring-amber-500 w-4 h-4">
                                                            <span class="text-xs font-bold text-slate-700">🍳 وجبة
                                                                إفطار</span>
                                                        </div>
                                                        <span class="text-[11px] font-black text-amber-600"
                                                            x-text="getMealPrice(activeModalSeat, 'breakfast') + ' ⃁'"></span>
                                                    </label>
                                                @endif
                                                @if (!empty($mealOptions['individual']['lunch']['active']))
                                                    <label
                                                        class="flex items-center justify-between p-2.5 bg-white rounded-xl border border-amber-100 hover:border-amber-300 transition-all cursor-pointer">
                                                        <div class="flex items-center gap-2">
                                                            <input type="checkbox"
                                                                x-model="passengerMeals[activeModalSeat].lunch"
                                                                class="rounded text-amber-600 focus:ring-amber-500 w-4 h-4">
                                                            <span class="text-xs font-bold text-slate-700">🍗 وجبة
                                                                غداء</span>
                                                        </div>
                                                        <span class="text-[11px] font-black text-amber-600"
                                                            x-text="getMealPrice(activeModalSeat, 'lunch') + ' ⃁'"></span>
                                                    </label>
                                                @endif
                                                @if (!empty($mealOptions['individual']['dinner']['active']))
                                                    <label
                                                        class="flex items-center justify-between p-2.5 bg-white rounded-xl border border-amber-100 hover:border-amber-300 transition-all cursor-pointer">
                                                        <div class="flex items-center gap-2">
                                                            <input type="checkbox"
                                                                x-model="passengerMeals[activeModalSeat].dinner"
                                                                class="rounded text-amber-600 focus:ring-amber-500 w-4 h-4">
                                                            <span class="text-xs font-bold text-slate-700">🍔 وجبة
                                                                عشاء</span>
                                                        </div>
                                                        <span class="text-[11px] font-black text-amber-600"
                                                            x-text="getMealPrice(activeModalSeat, 'dinner') + ' ⃁'"></span>
                                                    </label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 bg-slate-50 border-t border-slate-100 shrink-0 grid grid-cols-2 gap-2">
                                    <button type="button" @click="openPassengerModal = false"
                                        class="w-full py-3 bg-indigo-600 text-white rounded-xl font-black text-xs shadow-md shadow-indigo-200 hover:bg-indigo-700 transition-all active:scale-95">
                                        حفظ ✓
                                    </button>
                                    <button type="button" @click="cancelSeat(activeModalSeat)"
                                        class="w-full py-3 bg-rose-50 text-rose-700 border border-rose-200 rounded-xl font-black text-xs hover:bg-rose-100 transition-all active:scale-95">
                                        🗑 إلغاء هذا المقعد
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- 3. 🎫 نافذة عرض بيانات العملاء للمقاعد المحجوزة مسبقاً (المطلوبة) --}}
                <div x-show="openBookedTicketModal"
                    class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                    @click="openBookedTicketModal = false" style="display: none;">

                    <div class="bg-white border border-slate-100 shadow-2xl rounded-[2rem] p-6 max-w-md w-full flex flex-col transform text-right relative overflow-hidden"
                        @click.stop>

                        <div class="flex justify-between items-center border-b border-slate-100 pb-3 mb-4 flex-shrink-0">
                            <h3 class="text-base font-black flex items-center gap-2 text-slate-900">
                                <span>🎫</span> بيانات الراكب للمقعد (<span x-text="activeBookedSeat"
                                    class="text-blue-600"></span>)
                            </h3>
                            <button @click="openBookedTicketModal = false"
                                class="w-8 h-8 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all text-xs font-bold">✕</button>
                        </div>

                        <div class="space-y-4">
                            <template x-if="activeBookedSeat && bookedSeatsDetails[activeBookedSeat]">
                                <div>
                                    {{-- إذا كان المقعد مغلق --}}
                                    <template
                                        x-if="bookedSeatsDetails[activeBookedSeat].assignment === 'blocked' || bookedSeatsDetails[activeBookedSeat].assignment === 'closed' || bookedSeatsDetails[activeBookedSeat].name?.includes('مغلق')">
                                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 text-center">
                                            <span class="text-4xl mb-3 block">🔒</span>
                                            <h4 class="text-sm font-black text-slate-700 mb-1">المقعد مغلق ومحجوز للوكالة
                                            </h4>
                                            <p class="text-[10px] text-slate-500 font-bold mb-4">هذا المقعد غير متاح
                                                للعملاء حالياً وتم حجبه.</p>
                                            <button type="button"
                                                @click="unlockSeat(bookedSeatsDetails[activeBookedSeat].booking_id, activeBookedSeat)"
                                                class="w-full py-2 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-xs font-black hover:bg-emerald-100 transition-all shadow-sm flex justify-center items-center gap-1.5 active:scale-95">
                                                <span>🔓</span> فك القفل وإتاحة المقعد
                                            </button>
                                        </div>
                                    </template>

                                    {{-- إذا كان المقعد لعميل محجوز --}}
                                    <template
                                        x-if="!(bookedSeatsDetails[activeBookedSeat].assignment === 'blocked' || bookedSeatsDetails[activeBookedSeat].assignment === 'closed' || bookedSeatsDetails[activeBookedSeat].name?.includes('مغلق'))">
                                        <div class="space-y-3">

                                            {{-- 1. العميل الأساسي --}}
                                            <template
                                                x-if="!bookedSeatsDetails[activeBookedSeat].is_extra_seat && !bookedSeatsDetails[activeBookedSeat].is_extra">
                                                <div
                                                    class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100 flex items-center gap-4">
                                                    <div
                                                        class="shrink-0 bg-white p-1.5 rounded-xl border border-indigo-100 shadow-sm">
                                                        <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=${bookedSeatsDetails[activeBookedSeat].identity || bookedSeatsDetails[activeBookedSeat].passenger_national_id || '000000'}`"
                                                            class="w-16 h-16 object-cover rounded-lg" alt="QR Code">
                                                    </div>
                                                    <div class="flex-1 grid grid-cols-2 gap-3">
                                                        <div>
                                                            <span
                                                                class="text-[10px] font-bold text-slate-400 block mb-1">الاسم
                                                                الكامل</span>
                                                            <span class="text-xs font-black text-slate-800"
                                                                x-text="bookedSeatsDetails[activeBookedSeat].passenger_name || bookedSeatsDetails[activeBookedSeat].name || 'غير متوفر'"></span>
                                                        </div>
                                                        <div>
                                                            <span
                                                                class="text-[10px] font-bold text-slate-400 block mb-1">رقم
                                                                الهوية / الجواز</span>
                                                            <span class="text-xs font-black text-slate-800 font-mono"
                                                                x-text="bookedSeatsDetails[activeBookedSeat].passenger_national_id || bookedSeatsDetails[activeBookedSeat].identity || 'غير متوفر'"></span>
                                                        </div>
                                                        <div>
                                                            <span
                                                                class="text-[10px] font-bold text-slate-400 block mb-1">الجنس</span>
                                                            <span class="text-[11px] font-black text-slate-800"
                                                                x-text="(bookedSeatsDetails[activeBookedSeat].gender === 'female' || bookedSeatsDetails[activeBookedSeat].gender === 'أنثى') ? '👩 أنثى' : '👨 ذكر'"></span>
                                                        </div>
                                                        <div>
                                                            <span
                                                                class="text-[10px] font-bold text-slate-400 block mb-1">التسكين
                                                                / الغرفة</span>
                                                            <span
                                                                class="text-[11px] font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-100"
                                                                x-text="
            (bookedSeatsDetails[activeBookedSeat].room_type === 'shared' || bookedSeatsDetails[activeBookedSeat].assignment === 'shared' || bookedSeatsDetails[activeBookedSeat].room_type?.includes('مشترك') || bookedSeatsDetails[activeBookedSeat].room_type?.includes('مشاركة'))
            ? ('مشارك' + (bookedSeatsDetails[activeBookedSeat].shared_room_type ? ' (' + (
                {single:'مفردة', double:'ثنائية', triple:'ثلاثية', quad:'رباعية'}[bookedSeatsDetails[activeBookedSeat].shared_room_type] || bookedSeatsDetails[activeBookedSeat].shared_room_type
            ) + ')' : ''))
            : (bookedSeatsDetails[activeBookedSeat].room_type || 'غير محدد')
        ">
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            {{-- 2. المقعد الإضافي --}}
                                            <template
                                                x-if="bookedSeatsDetails[activeBookedSeat].is_extra_seat || bookedSeatsDetails[activeBookedSeat].is_extra">
                                                <div
                                                    class="bg-amber-50/60 p-4 rounded-2xl border border-amber-200 flex items-center gap-4">
                                                    <div
                                                        class="shrink-0 bg-white p-1.5 rounded-xl border border-amber-200 shadow-sm">
                                                        <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=${bookedSeatsDetails[activeBookedSeat].passenger_national_id || bookedSeatsDetails[activeBookedSeat].parent_identity || '000000'}`"
                                                            class="w-16 h-16 object-cover rounded-lg" alt="QR Code">
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-1.5 mb-3">
                                                            <span class="text-amber-500 text-sm">🔗</span>
                                                            <span class="text-[10px] font-black text-amber-800">هذا المقعد
                                                                إضافي ومرتبط بالعميل الأساسي:</span>
                                                        </div>
                                                        <div class="grid grid-cols-2 gap-4">
                                                            <div>
                                                                <span
                                                                    class="text-[9px] font-bold text-amber-600/70 block mb-0.5">اسم
                                                                    العميل الأساسي</span>
                                                                <span class="text-[11px] font-black text-amber-900"
                                                                    x-text="bookedSeatsDetails[activeBookedSeat].passenger_name || bookedSeatsDetails[activeBookedSeat].parent_name || 'غير متوفر'"></span>
                                                            </div>
                                                            <div>
                                                                <span
                                                                    class="text-[9px] font-bold text-amber-600/70 block mb-0.5">هوية
                                                                    العميل الأساسي</span>
                                                                <span
                                                                    class="text-[11px] font-black text-amber-900 font-mono"
                                                                    x-text="bookedSeatsDetails[activeBookedSeat].passenger_national_id || bookedSeatsDetails[activeBookedSeat].parent_identity || 'غير متوفر'"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                                    <span class="text-[10px] font-bold text-slate-400 block mb-1">رقم الحجز
                                                        المرجعي</span>
                                                    <span class="text-xs font-black text-slate-700 font-mono"
                                                        x-text="bookedSeatsDetails[activeBookedSeat].booking_id ? '#' + bookedSeatsDetails[activeBookedSeat].booking_id : 'غير متوفر'"></span>
                                                </div>
                                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                                    <span class="text-[10px] font-bold text-slate-400 block mb-1">حالة
                                                        الدفع / التذكرة</span>
                                                    <span class="text-[11px] font-black"
                                                        :class="(bookedSeatsDetails[activeBookedSeat].status === 'مؤكد' ||
                                                            bookedSeatsDetails[activeBookedSeat].status === 'confirmed'
                                                        ) ? 'text-emerald-600' : 'text-slate-700'"
                                                        x-text="bookedSeatsDetails[activeBookedSeat].status || 'مؤكد'"></span>
                                                </div>
                                            </div>

                                            {{-- قسم أزرار العمليات والتعديل --}}
                                            <div class="pt-3 border-t border-slate-100 space-y-2">
                                                <div class="grid grid-cols-2 gap-2">
                                                    <button type="button" @click="changeSeatNumber(activeBookedSeat)"
                                                        class="py-2.5 px-3 rounded-xl border text-xs font-black transition-all flex items-center justify-center gap-1.5 bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100 shadow-sm active:scale-95">
                                                        <span>🪑</span>
                                                        <span>تغيير رقم المقعد</span>
                                                    </button>

                                                    {{-- زر حذف المقعد --}}
                                                    <button type="button"
                                                        @click="deleteBookedSeat(bookedSeatsDetails[activeBookedSeat].id, activeBookedSeat, bookedSeatsDetails[activeBookedSeat].booking_id)"
                                                        class="py-2.5 px-3 bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5 shadow-sm active:scale-95">
                                                        <span>🗑️</span> حذف المقعد
                                                    </button>
                                                </div>

                                                {{-- زر الانتقال للحجز --}}
                                                <template x-if="bookedSeatsDetails[activeBookedSeat].booking_id">
                                                    <a :href="`/agency/bookings?id=${bookedSeatsDetails[activeBookedSeat].booking_id}`"
                                                        class="w-full py-2.5 bg-blue-600 text-white rounded-xl text-xs font-black hover:bg-blue-700 transition-all text-center shadow-md shadow-blue-200 active:scale-95 flex items-center justify-center gap-1.5">
                                                        <span>🧾</span> الانتقال لإدارة الحجوزات
                                                    </a>
                                                </template>
                                            </div>

                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <!-- نافذة تغيير رقم المقعد -->
                <div x-show="openChangeSeatModal"
                    class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                    @click="openChangeSeatModal = false" x-cloak style="display: none;">

                    <div class="bg-white border border-slate-100 shadow-2xl rounded-3xl p-6 max-w-md w-full text-right relative"
                        @click.stop>

                        <!-- رأس النافذة -->
                        <div class="flex justify-between items-center border-b border-slate-100 pb-3 mb-4">
                            <h3 class="text-lg font-black text-slate-900 flex items-center gap-2">
                                <span>🔄</span> تغيير رقم المقعد
                            </h3>
                            <button @click="openChangeSeatModal = false" type="button"
                                class="w-8 h-8 flex items-center justify-center rounded-xl bg-slate-50 text-slate-400 hover:bg-slate-100 transition-colors">✕</button>
                        </div>

                        <!-- محتوى النافذة -->
                        <div class="space-y-4">
                            <p class="text-sm text-slate-600 font-bold">
                                المقعد الحالي: <span class="text-blue-600 font-black"
                                    x-text="seatToMove.currentNum"></span>
                            </p>

                            <div>
                                <label class="text-[11px] text-slate-500 font-bold mb-1 block">رقم المقعد الجديد المستهدف
                                    *</label>
                                <input type="number" min="1" x-model="targetSeatNumber"
                                    class="w-full bg-slate-50 border border-slate-200 focus:border-blue-500 rounded-xl px-3 py-2 text-sm font-bold outline-none"
                                    placeholder="أدخل رقم المقعد الجديد">
                            </div>
                        </div>

                        <!-- أزرار الإجراءات -->
                        <div class="mt-6 flex gap-3">
                            <button @click="submitChangeSeat()" :disabled="isSubmitting || !targetSeatNumber"
                                class="flex-1 py-2.5 bg-blue-600 text-white rounded-xl font-black text-sm shadow-md hover:bg-blue-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                <span x-show="!isSubmitting">حفظ التغيير</span>
                                <span x-show="isSubmitting">جاري الحفظ...</span>
                            </button>
                            <button @click="openChangeSeatModal = false" type="button"
                                class="flex-1 py-2.5 bg-slate-100 text-slate-700 rounded-xl font-black text-sm hover:bg-slate-200 transition-all">
                                إلغاء
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        @endif
    </div>
@endsection
