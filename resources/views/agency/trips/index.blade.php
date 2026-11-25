@extends('layouts.agency')

@section('content')
    {{-- الحاوية الرئيسية للتحكم بالصفحة والمودالات العامة --}}
    <div x-data="{
        deleteModal: false,
        qrModal: false,
        qrUrl: '',
        qrTitle: '',
        activeSeatZone: 'any',
        deleteUrl: '',
        openBusModal: new URLSearchParams(window.location.search).has('selected_trip_id')
    }" x-cloak>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-8">
            <div class="max-w-7xl mx-auto">

                {{-- رأس الصفحة --}}
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                    <div>
                        <h3 class="text-2xl font-black text-slate-800">إدارة وجدولة الرحلات 🚍 ✈️</h3>
                        <p class="text-slate-400 text-xs font-bold mt-1">تتبع رحلاتك الحالية، المقاعد المتاحة، وإدارة مخططات
                            الحافلات والطائرات .</p>
                    </div>
                    <a href="{{ route('agency.trips.create') }}"
                        class="px-5 py-3 bg-blue-600 text-white rounded-xl font-bold text-sm shadow-md shadow-blue-200 hover:bg-blue-700 transition-all active:scale-95 flex items-center gap-2">
                        <span>➕</span> إضافة رحلة جديدة
                    </a>
                </div>

                <!-- مكتبة SweetAlert2 (ضعه في الصفحة أو في ملف الـ Layout الرئيسي) -->
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
                {{-- إحصائيات لوحة التحكم التشغيلية --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    {{-- الكرت 1: إجمالي الرحلات --}}
                    <div
                        class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-slate-400 text-[10px] font-black uppercase tracking-wider block">إجمالي
                                الرحلات</span>
                            <span class="text-2xl font-black text-slate-800 mt-1 block">{{ $trips->total() }}</span>
                        </div>
                        <div class="w-11 h-11 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-lg">
                            🗺️</div>
                    </div>

                    {{-- الكرت 2: الرحلات النشطة الصفحة الحالية --}}
                    <div
                        class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-slate-400 text-[10px] font-black uppercase tracking-wider block">الرحلات
                                النشطة (الصفحة)</span>
                            <span
                                class="text-2xl font-black text-emerald-600 mt-1 block">{{ $trips->where('status', 'active')->count() }}</span>
                        </div>
                        <div
                            class="w-11 h-11 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-lg">
                            ✅</div>
                    </div>

                    {{-- الكرت 3: إجمالي المقاعد المحجوزة بالصفحة --}}
                    <div
                        class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-slate-400 text-[10px] font-black uppercase tracking-wider block">المقاعد
                                المحجوزة حالياً</span>
                            <span class="text-2xl font-black text-blue-600 mt-1 block">{{ $trips->sum('booked_count') }}
                                <span class="text-[11px] font-bold">مقعد</span></span>
                        </div>
                        <div
                            class="w-11 h-11 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-lg">
                            🎟️</div>
                    </div>

                    {{-- الكرت 4: نسبة إشغال الحافلات والطائرات --}}
                    <div
                        class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-slate-400 text-[10px] font-black uppercase tracking-wider block">نسبة إشغال
                                الحجوزات</span>
                            @php
                                $totalSeatsAll = $trips->sum('total_seats');
                                $totalBookedAll = $trips->sum('booked_count');
                                $occupancyRate =
                                    $totalSeatsAll > 0 ? round(($totalBookedAll / $totalSeatsAll) * 100) : 0;
                            @endphp
                            <span class="text-2xl font-black text-purple-600 mt-1 block">{{ $occupancyRate }}%</span>
                        </div>
                        <div
                            class="w-11 h-11 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center text-lg">
                            📈</div>
                    </div>
                </div>

                {{-- شريط البحث والفلترة --}}
                <div class="mb-6 bg-white p-4 rounded-3xl border border-slate-100 shadow-sm">
                    <form action="{{ route('agency.trips.index') }}" method="GET"
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

                {{-- جدول الرحلات الرئيسي --}}
                <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-right border-collapse">
                            <thead>
                                <tr
                                    class="bg-slate-50 border-b border-slate-100 text-slate-400 text-[11px] font-black uppercase tracking-wider">
                                    <th class="p-6">معرف الرحلة</th>
                                    <th class="p-6">عنوان / مسار الرحلة</th>
                                    <th class="p-6">نوع النقل</th>
                                    <th class="p-6">تاريخ ووقت المغادرة</th>
                                    <th class="p-6">المدة</th>
                                    <th class="p-6">المقاعد المباعة</th>
                                    <th class="p-6">حالة الرحلة</th>
                                    <th class="p-6 text-center">إجراءات التحكم</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-sm font-bold text-slate-700">
                                @forelse($trips as $t)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="p-6 text-slate-400 text-xs font-mono">#{{ $t->id }}</td>
                                        <td class="p-6">
                                            <div class="font-black text-slate-800">{{ $t->title }}</div>
                                        </td>
                                        <td class="p-6">
                                            @if ($t->transport_type === 'flight')
                                                <span
                                                    class="inline-flex items-center gap-1.5 px-3 py-1 bg-indigo-50 border border-indigo-100 text-indigo-600 rounded-full text-[10px] font-black">✈️
                                                    طيران</span>
                                            @else
                                                <span
                                                    class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 border border-amber-100 text-amber-600 rounded-full text-[10px] font-black">🚌
                                                    حافلة برية</span>
                                            @endif
                                        </td>
                                        <td class="p-6">
                                            <div class="text-slate-700">{{ $t->date }}</div>
                                            <div class="text-slate-400 text-[11px] font-medium mt-0.5">
                                                {{ $t->departure_at }}</div>
                                        </td>
                                        <td class="p-6 text-slate-500">{{ $t->days }} أيام</td>
                                        <td class="p-6">
                                            <div class="flex items-center gap-2">
                                                <div class="w-24 bg-slate-100 h-2 rounded-full overflow-hidden">
                                                    @php
                                                        $percentage =
                                                            $t->total_seats > 0
                                                                ? (($t->total_seats - $t->available_seats) /
                                                                        $t->total_seats) *
                                                                    100
                                                                : 0;
                                                    @endphp
                                                    <div class="bg-blue-600 h-full rounded-full"
                                                        style="width: {{ $percentage }}%"></div>
                                                </div>
                                                <span
                                                    class="text-xs font-black text-slate-800">{{ $t->total_seats - $t->available_seats }}
                                                    / {{ $t->total_seats }}</span>
                                            </div>
                                        </td>
                                        <td class="p-6">
                                            @if ($t->status === 'active')
                                                <span
                                                    class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-black bg-emerald-50 border border-emerald-100 text-emerald-600">●
                                                    في الطريق</span>
                                            @elseif ($t->status === 'completed' || $t->status === 'تمت الرحلة')
                                                <span
                                                    class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-black bg-blue-50 border border-blue-100 text-blue-600">●
                                                    تمت الرحلة</span>
                                            @elseif ($t->status === 'approved')
                                                <span
                                                    class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-black bg-emerald-50 border border-emerald-100 text-emerald-600">●
                                                    معتمدة</span>
                                            @elseif($t->status === 'pending' || $t->status === 'published')
                                                <span
                                                    class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-black bg-amber-50 border border-amber-100 text-amber-600">●
                                                    قيد المراجعة</span>
                                            @elseif($t->status === 'draft')
                                                <span
                                                    class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-black bg-slate-100 border border-slate-200 text-slate-600">●
                                                    مسودة</span>
                                            @elseif($t->status === 'cancellation_requested')
                                                <span
                                                    class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-black bg-orange-50 border border-orange-100 text-orange-600">●
                                                    تم طلب إلغاء الرحلة</span>
                                            @elseif($t->status === 'cancelled')
                                                <span
                                                    class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-black bg-rose-50 border border-rose-100 text-rose-600">●
                                                    ملغاة</span>
                                            @else
                                                <span
                                                    class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-black bg-rose-50 border border-rose-100 text-rose-600">●
                                                    مرفوضة</span>
                                            @endif
                                        </td>

                                        <td class="p-6 text-center">
                                            <div class="flex items-center justify-center gap-1.5">




                                                <a href="{{ route('agency.trips.edit', $t->id) }}"
                                                    class="p-2.5 bg-sky-50 text-sky-600 hover:bg-sky-100 rounded-xl transition-all active:scale-95 text-xs font-black inline-flex items-center gap-1">
                                                    ✏️ تعديل
                                                </a>
                                                <button
                                                    @click="qrUrl = '{{ route('client.rate.public', ['agency' => $t->agency_id]) }}?trip_id={{ $t->id }}'; qrTitle = '{{ $t->title }}'; qrModal = true"
                                                    type="button"
                                                    class="p-2.5 bg-amber-50 text-amber-600 hover:bg-amber-100 rounded-xl transition-all active:scale-95 text-xs font-black inline-flex items-center gap-1">
                                                    📱 QR التقييم
                                                </button>

                                                <button
                                                    @click="deleteUrl = '{{ route('agency.trips.request-cancellation', $t->id) }}'; deleteModal = true"
                                                    type="button"
                                                    class="p-2.5 bg-amber-50 text-amber-600 hover:bg-amber-100 rounded-xl transition-all active:scale-95 text-xs font-black inline-flex items-center gap-1">
                                                    🚫 طلب إلغاء
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="p-12 text-center text-slate-400 font-bold">
                                            <div class="text-3xl mb-2">📭</div> لا توجد أي رحلات مسجلة مطابقة للبحث حالياً.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- قسم الترقيم والباجينيشن (Pagination Links) --}}
                    @if ($trips->hasPages())
                        <div
                            class="p-6 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4">
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

            </div>
        </main>




        {{-- مودال الحذف الرئيسي للرحلة --}}
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4" x-show="deleteModal" x-transition x-cloak
            style="display: none;">
            <div class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm" @click="deleteModal = false"></div>
            <div class="bg-white rounded-[2rem] p-8 max-w-sm w-full shadow-2xl relative z-10 text-center">

                <!-- أيقونة التحذير بألوان تناسب طلب الإلغاء -->
                <div
                    class="w-14 h-14 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4">
                    🚫
                </div>

                <!-- العنوان والوصف المعدل -->
                <h3 class="text-lg font-black text-slate-800 mb-2">تأكيد طلب إلغاء الرحلة</h3>
                <p class="text-slate-400 font-bold text-xs mb-6">سيتم تقديم طلب لإلغاء هذه الرحلة وتغيير حالتها في النظام.
                </p>

                <!-- النموذج مع تغيير طريقة الإرسال إلى PATCH -->
                <form :action="deleteUrl" method="POST" class="flex flex-col gap-2">
                    @csrf
                    @method('PATCH') <!-- تم التغيير من DELETE إلى PATCH -->

                    <button type="submit"
                        class="w-full py-3 bg-amber-600 hover:bg-amber-700 text-white font-black rounded-xl text-sm transition-all">
                        نعم، إرسال طلب الإلغاء
                    </button>
                    <button type="button" @click="deleteModal = false"
                        class="w-full py-3 bg-slate-50 text-slate-500 font-black rounded-xl text-sm border border-slate-100 hover:bg-slate-100 transition-all">
                        تراجع
                    </button>
                </form>
            </div>
        </div>
        {{-- مودال عرض وطباعة QR Code --}}
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4" x-show="qrModal" x-transition x-cloak
            style="display: none;">
            <div class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm" @click="qrModal = false"></div>
            <div class="bg-white rounded-[2.5rem] p-6 max-w-sm w-full shadow-2xl relative z-10 text-center">

                <div
                    class="w-12 h-12 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center text-xl mx-auto mb-3">
                    ⭐
                </div>

                <h3 class="text-base font-black text-slate-800" x-text="qrTitle"></h3>
                <p class="text-slate-400 font-bold text-xs mt-1 mb-4">امسح الرمز لتقييم الرحلة من الباص</p>


                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 inline-block my-2">
                    <template x-if="qrUrl">
                        <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(qrUrl)"
                            alt="QR Code" class="w-48 h-48 mx-auto rounded-xl shadow-sm">
                    </template>
                </div>

                <div class="flex flex-col gap-2 mt-4">
                    <button onclick="window.print()" type="button"
                        class="w-full py-3 bg-slate-900 text-white font-black rounded-xl text-xs shadow-md hover:bg-indigo-600 transition-all">
                        🖨️ طباعة ملصق الـ QR
                    </button>
                    <button type="button" @click="qrModal = false"
                        class="w-full py-2.5 bg-slate-100 text-slate-600 font-bold rounded-xl text-xs">
                        إغلاق
                    </button>
                </div>

            </div>
        </div>

    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    {{-- مكتبة توليد ملفات الـ PDF مباشرة من المتصفح --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
        function downloadSecurityReport(tripId, buttonElement) {
            const originalText = buttonElement.innerHTML;
            buttonElement.disabled = true;
            buttonElement.innerHTML = `⏳ جاري التحضير...`;

            fetch('/agency/trips/' + tripId + '/security-manifest-data')
                .then(response => {
                    if (!response.ok) throw new Error('فشل في جلب البيانات');
                    return response.json();
                })
                .then(data => {
                    let passengerRows = '';

                    if (data.passengers && data.passengers.length > 0) {
                        data.passengers.forEach((p, index) => {
                            if (String(p.seat_number).toUpperCase().startsWith('H')) {
                                return;
                            }

                            passengerRows += `
                            <tr style="background: ${index % 2 === 0 ? '#ffffff' : '#f8fafc'}; border-bottom: 1.5px solid #e2e8f0;">
                                <td style="padding: 10px; border-left: 1px solid #e2e8f0; text-align: center; font-weight: bold; color: #475569;">${index + 1}</td>
                                <td style="padding: 10px; border-left: 1px solid #e2e8f0; text-align: center; font-weight: 900; color: #2563eb;">${p.seat_number}</td>
                                <td style="padding: 10px; border-left: 1px solid #e2e8f0; text-align: right; padding-right: 15px; font-weight: 800; color: #1e293b;">${p.name}</td>
                                <td style="padding: 10px; border-left: 1px solid #e2e8f0; text-align: center; font-family: monospace; font-weight: bold; color: #334155; letter-spacing: 1px;">${p.identity}</td>
                                <td style="padding: 10px; text-align: center; font-weight: bold;">${p.gender === 'female' ? 'أنثى 👩' : 'ذكر 👨'}</td>
                            </tr>`;
                        });
                    } else {
                        passengerRows = `
                        <tr>
                            <td colspan="5" style="padding: 25px; text-align: center; color: #64748b; font-weight: bold;">
                                لا يوجد ركاب مسجلين في هذه الرحلة حتى الآن
                            </td>
                        </tr>`;
                    }

                    const trip = data.trip || {};
                    const agency = data.agency || {};
                    const isFlightTrip = trip.transport_type === 'flight';

                    const plateLetters = trip.plate_letters || '---';
                    const plateNumbers = trip.plate_numbers || '----';

                    const passengersList = data.passengers || data.bookings || [];
                    const totalPassengers = passengersList.length;

                    const maleCount = passengersList.filter(p => p.gender === 'male' || p.gender === 'ذكر' || p
                        .gender === 'M').length;
                    const femaleCount = passengersList.filter(p => p.gender === 'female' || p.gender === 'أنثى' || p
                        .gender === 'F').length;

                    const busCapacity = (trip.bus && trip.bus.capacity) ? trip.bus.capacity : (trip.total_seats || 0);
                    const remainingSeats = busCapacity - totalPassengers;



                    buttonElement.disabled = false;
                    buttonElement.innerHTML = originalText;
                })
                .catch(error => {
                    console.error('خطأ:', error);
                    alert('حدث خطأ أثناء جلب البيانات، الرجاء التحقق من اتصالك بالإنترنت أو الخادم.');
                    buttonElement.disabled = false;
                    buttonElement.innerHTML = originalText;
                });
        }
    </script>
@endsection
