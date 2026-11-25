@extends('layouts.agency')

@section('content')
    <!-- تضمين مكتبة التنبيهات -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="p-6 bg-slate-50 min-h-screen">

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800">طلبات الرحلات الخاصة 🚀</h1>
                <p class="text-xs font-semibold text-slate-500 mt-1">استعراض وإدارة طلبات الرحلات المخصصة الواردة للوكالة</p>
            </div>
        </div>

        <!-- Quick Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 block mb-1">إجمالي الطلبات</span>
                    <span class="text-2xl font-black text-slate-800">{{ $stats['total'] }}</span>
                </div>
                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center font-bold text-xl">
                    📋
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 block mb-1">موجهة لوكالتك مباشرة</span>
                    <span class="text-2xl font-black text-emerald-600">{{ $stats['direct'] }}</span>
                </div>
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center font-bold text-xl">
                    🎯
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 block mb-1">طلبات عامة (لكافة الوكالات)</span>
                    <span class="text-2xl font-black text-blue-600">{{ $stats['public'] }}</span>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center font-bold text-xl">
                    🌐
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 block mb-1">قيد الانتظار</span>
                    <span class="text-2xl font-black text-amber-500">{{ $stats['pending'] }}</span>
                </div>
                <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-xl flex items-center justify-center font-bold text-xl">
                    ⏳
                </div>
            </div>
        </div>

        <!-- Filters Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm mb-6">
            <form method="GET" action="{{ route('agency.trips.private_trips') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <!-- Search -->
                <div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="ابحث باسم العميل أو المدينة..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none">
                </div>

                <!-- Request Scope -->
                <div>
                    <select name="type" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none">
                        <option value="">جميع أنواع الطلبات</option>
                        <option value="direct" {{ request('type') == 'direct' ? 'selected' : '' }}>خاصة بوكالتك فقط</option>
                        <option value="public" {{ request('type') == 'public' ? 'selected' : '' }}>عامة لجميع الوكالات</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none">
                        <option value="">جميع الحالات</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                        <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>مقبول / تم تقديم عرض</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>مرفوض</option>
                    </select>
                </div>

                <!-- Submit Filter -->
                <div class="flex gap-2">
                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl py-2.5 transition-all">
                        تطبيق الفلترة
                    </button>
                    @if (request()->hasAny(['search', 'type', 'status']))
                        <a href="{{ route('agency.trips.private_trips') }}" class="px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl flex items-center justify-center transition-all">
                            إلغاء
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Requests Grid / List -->
        @if ($requests->count() > 0)
            <div class="grid grid-cols-1 gap-4">
                @foreach ($requests as $item)
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-all">

                        <!-- Top Bar of Request Card -->
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3 mb-4">
                            <div class="flex items-center gap-2">
                                <!-- Direct / Public Badge -->
                                @if ($item->agency_id)
                                    <span class="bg-emerald-50 text-emerald-700 text-[11px] font-black px-2.5 py-1 rounded-lg border border-emerald-200/50">
                                        🎯 موجه لوكالتك حصراً
                                    </span>
                                @else
                                    <span class="bg-blue-50 text-blue-700 text-[11px] font-black px-2.5 py-1 rounded-lg border border-blue-200/50">
                                        🌐 طلب عام لكافة الوكالات
                                    </span>
                                @endif

                                <!-- Status Badge -->
                                @if ($item->status == 'pending')
                                    <span class="bg-amber-50 text-amber-600 text-[11px] font-bold px-2.5 py-1 rounded-lg">
                                        ⏳ قيد الانتظار
                                    </span>
                                @elseif($item->status == 'accepted')
                                    <span class="bg-emerald-50 text-emerald-600 text-[11px] font-bold px-2.5 py-1 rounded-lg">
                                        ✅ تم قبول الطلب
                                    </span>
                                @else
                                    <span class="bg-slate-100 text-slate-600 text-[11px] font-bold px-2.5 py-1 rounded-lg">
                                        {{ $item->status == 'rejected' ? 'مرفوض' : $item->status }}
                                    </span>
                                @endif
                            </div>

                            <span class="text-[11px] font-semibold text-slate-400">
                                تاريخ الطلب: {{ $item->created_at ? $item->created_at->diffForHumans() : '-' }}
                            </span>
                        </div>

                        <!-- Main Content -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">

                            <!-- Route & Trip Details -->
                            <div class="md:col-span-2">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-base font-black text-slate-800">{{ $item->from_city }}</span>
                                    <span class="text-slate-400">⬅️</span>
                                    <span class="text-base font-black text-slate-800">{{ $item->to_city }}</span>
                                </div>

                                <div class="flex flex-wrap gap-2 text-xs font-semibold text-slate-500">
                                    <span class="bg-slate-100 px-2.5 py-1 rounded-lg">
                                        📅 المغادرة: <strong class="text-slate-700">{{ $item->departure_date ? $item->departure_date->format('Y-m-d') : 'غير محدد' }}</strong>
                                    </span>
                                    @if ($item->return_date)
                                        <span class="bg-slate-100 px-2.5 py-1 rounded-lg">
                                            🔄 العودة: <strong class="text-slate-700">{{ $item->return_date->format('Y-m-d') }}</strong>
                                        </span>
                                    @endif
                                    <span class="bg-slate-100 px-2.5 py-1 rounded-lg">
                                        👥 الركاب: <strong class="text-slate-700">{{ $item->passengers_count }}</strong>
                                    </span>
                                    <span class="bg-slate-100 px-2.5 py-1 rounded-lg">
                                        🚘 الوسيلة:
                                        <strong class="text-slate-700">
                                            @switch($item->transport_type)
                                                @case('bus') حافلة (باص) @break
                                                @case('van') فان عائلي @break
                                                @case('car_vip') سيارة VIP @break
                                                @case('flight_charter') طيران خاص @break
                                                @default {{ $item->transport_type }}
                                            @endswitch
                                        </strong>
                                    </span>
                                </div>
                            </div>

                            <!-- Contact Details -->
                            <div class="border-t md:border-t-0 md:border-r border-slate-100 pt-3 md:pt-0 md:pr-4">
                                <span class="block text-[11px] font-bold text-slate-400 mb-1">صاحب الطلب:</span>
                                <h4 class="text-sm font-black text-slate-800 mb-1">{{ $item->contact_name }}</h4>
                                <p class="text-xs font-bold text-slate-600 dir-ltr text-right mb-2">{{ $item->contact_phone }}</p>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col gap-2 justify-center">
                                @php
                                    $cleanPhone = preg_replace('/[^0-9]/', '', $item->contact_phone);
                                    if (str_starts_with($cleanPhone, '05')) {
                                        $cleanPhone = '966' . substr($cleanPhone, 1);
                                    }
                                    $whatsappMessage = urlencode("مرحباً {$item->contact_name}، نتواصل معك بخصوص طلبك للرحلة الخاصة من {$item->from_city} إلى {$item->to_city}.");
                                @endphp

                                <a href="https://wa.me/{{ $cleanPhone }}?text={{ $whatsappMessage }}" target="_blank"
                                    class="w-full py-2.5 px-3 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold rounded-xl flex items-center justify-center gap-2 transition-all shadow-sm">
                                    تواصل عبر واتساب
                                </a>

                                @if($item->status == 'pending')
                                    <!-- نموذج الرفض -->
                                    <form id="reject-form-{{ $item->id }}" action="{{ route('agency.private_trips.reject', $item->id) }}" method="POST" class="w-full">
                                        @csrf
                                        @method('PATCH')
                                        <button type="button" onclick="confirmReject({{ $item->id }})" class="w-full py-2.5 px-3 bg-red-500 hover:bg-red-600 text-white text-xs font-bold rounded-xl flex items-center justify-center gap-2 transition-all shadow-sm">
                                            ❌ رفض الطلب
                                        </button>
                                    </form>
                                @endif

                                @if ($item->status !== 'accepted' && $item->status !== 'rejected')
                                    <a href="{{ route('agency.trips.create', ['private_request_id' => $item->id, 'user_id' => $item->user_id]) }}" class="w-full py-2.5 px-3 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl flex items-center justify-center gap-2 transition-all shadow-sm">
                                        ➕ تقديم عرض / إنشاء رحلة
                                    </a>
                                @endif
                            </div>
                        </div>

                        <!-- Additional Notes -->
                        @if ($item->notes)
                            <div class="mt-4 pt-3 border-t border-slate-100 bg-slate-50 p-3 rounded-xl text-xs text-slate-600">
                                <strong class="text-slate-700 font-bold">ملاحظات العميل:</strong> {{ $item->notes }}
                            </div>
                        @endif

                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $requests->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
                <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center text-2xl mx-auto mb-4">
                    🔍
                </div>
                <h3 class="text-base font-bold text-slate-700 mb-1">لا توجد طلبات رحلات خاصة حالياً</h3>
                <p class="text-xs text-slate-400">ستظهر الطلبات الموجهة لوكالتك أو الطلبات العامة هنا بمجرد إرسالها من العملاء.</p>
            </div>
        @endif
    </div>

    <!-- 🟢 تم نقل السكريبت هنا خارج حلقة التكرار -->
    <script>
        function confirmReject(id) {
            Swal.fire({
                title: 'هل أنت متأكد من رفض هذا الطلب؟',
                text: "لن تتأثر البيانات الأخرى، وسيتم تغيير حالة الطلب إلى مرفوض.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'نعم، أرفض الطلب',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`reject-form-${id}`).submit();
                }
            });
        }

        // إظهار رسالة النجاح المنبثقة عند العودة من الكنترولر
        document.addEventListener('DOMContentLoaded', function() {
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'تمت العملية بنجاح!',
                    text: "{{ session('success') }}",
                    timer: 2500,
                    showConfirmButton: false
                });
            @endif
        });
    </script>
@endsection