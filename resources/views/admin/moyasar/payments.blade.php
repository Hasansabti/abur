@extends('layouts.admin')

@section('content')
@php
    $statusAr = ['paid' => 'مدفوع', 'failed' => 'فشل', 'initiated' => 'بدأ', 'authorized' => 'معلّق', 'refunded' => 'مسترد', 'voided' => 'ملغى', 'captured' => 'محصّل'];
    $statusCls = ['paid' => 'bg-green-100 text-green-700', 'failed' => 'bg-red-100 text-red-700', 'refunded' => 'bg-indigo-100 text-indigo-700'];
    $matchMap = [
        'matched'  => ['متطابق', 'bg-green-100 text-green-700'],
        'mismatch' => ['غير متطابق', 'bg-red-100 text-red-700'],
        'amount'   => ['فرق مبلغ', 'bg-amber-100 text-amber-800'],
        'orphan'   => ['بدون حجز', 'bg-rose-100 text-rose-700'],
        'other'    => ['-', 'bg-gray-100 text-gray-500'],
    ];
@endphp

<div class="space-y-6 text-right" dir="rtl">

    <div>
        <h1 class="text-3xl font-black text-gray-800 tracking-tighter">مدفوعات ميسر</h1>
        <p class="text-gray-500 font-medium">مطابقة الدفعات مع الحجوزات والفواتير الإضافية.</p>
    </div>

    @if ($error)
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-sm font-bold text-rose-700">{{ $error }}</div>
    @endif

    {{-- الإحصائيات (للصفحة الحالية فقط) --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-[11px] font-bold text-gray-400">عدد الدفعات</p>
            <p class="text-2xl font-black text-gray-800">{{ $stats['count'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-[11px] font-bold text-gray-400">إجمالي المدفوع</p>
            <p class="text-2xl font-black text-green-600">{{ number_format($stats['paid'], 2) }}</p>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-[11px] font-bold text-gray-400">رسوم ميسر</p>
            <p class="text-2xl font-black text-gray-800">{{ number_format($stats['fees'], 2) }}</p>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
            <p class="text-[11px] font-bold text-gray-400">المسترد</p>
            <p class="text-2xl font-black text-indigo-600">{{ number_format($stats['refunded'], 2) }}</p>
        </div>
        <a href="{{ request()->fullUrlWithQuery(['match' => 'issues', 'page' => 1]) }}"
           class="p-4 rounded-2xl border shadow-sm {{ $stats['issues'] ? 'bg-rose-50 border-rose-200' : 'bg-white border-gray-100' }}">
            <p class="text-[11px] font-bold text-gray-400">تحتاج مراجعة</p>
            <p class="text-2xl font-black {{ $stats['issues'] ? 'text-rose-600' : 'text-gray-800' }}">{{ $stats['issues'] }}</p>
        </a>
    </div>

    {{-- الفلاتر --}}
    <form method="GET" class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-[11px] font-black text-gray-500 mb-1">حالة الدفع</label>
            <select name="status" class="bg-gray-50 rounded-xl px-3 py-2 text-xs font-bold outline-none">
                <option value="">الكل</option>
                @foreach (['paid' => 'مدفوع', 'failed' => 'فشل', 'refunded' => 'مسترد', 'initiated' => 'بدأ'] as $k => $v)
                    <option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-black text-gray-500 mb-1">المطابقة</label>
            <select name="match" class="bg-gray-50 rounded-xl px-3 py-2 text-xs font-bold outline-none">
                <option value="">الكل</option>
                <option value="issues" @selected($filter === 'issues')>تحتاج مراجعة</option>
                <option value="matched" @selected($filter === 'matched')>متطابق</option>
                <option value="mismatch" @selected($filter === 'mismatch')>غير متطابق</option>
                <option value="orphan" @selected($filter === 'orphan')>بدون حجز</option>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-black text-gray-500 mb-1">من</label>
            <input type="date" name="from" value="{{ $from }}" class="bg-gray-50 rounded-xl px-3 py-2 text-xs font-bold outline-none">
        </div>
        <div>
            <label class="block text-[11px] font-black text-gray-500 mb-1">إلى</label>
            <input type="date" name="to" value="{{ $to }}" class="bg-gray-50 rounded-xl px-3 py-2 text-xs font-bold outline-none">
        </div>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-xs font-black">تصفية</button>
        <a href="{{ url()->current() }}" class="text-xs font-bold text-gray-400 py-2">إعادة تعيين</a>
    </form>

    {{-- الجدول --}}
    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full border-collapse text-right">
            <thead>
                <tr class="bg-gray-50/60 border-b border-gray-100 text-[10px] font-black text-gray-400">
                    <th class="p-4">رقم الدفعة</th>
                    <th class="p-4">التاريخ</th>
                    <th class="p-4">المرتبط به</th>
                    <th class="p-4">المبلغ</th>
                    <th class="p-4">الوسيلة</th>
                    <th class="p-4 text-center">حالة ميسر</th>
                    <th class="p-4 text-center">المطابقة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($rows as $r)
                    <tr class="{{ in_array($r['match'], ['mismatch', 'orphan', 'amount']) ? 'bg-rose-50/40' : '' }}">
                        <td class="p-4 text-[11px] font-mono text-gray-600" dir="ltr">{{ \Illuminate\Support\Str::limit($r['id'], 13, '…') }}</td>
                        <td class="p-4 text-xs font-bold text-gray-600">
                            {{ $r['created'] ? \Carbon\Carbon::parse($r['created'])->setTimezone('Asia/Riyadh')->format('Y-m-d H:i') : '-' }}
                        </td>
                        <td class="p-4">
                            @if ($r['ref'])
                                <p class="text-xs font-black text-gray-800">{{ $r['ref']['label'] }}
                                    <span class="text-[10px] text-gray-400 font-bold">({{ $r['ref']['type'] }})</span></p>
                                <p class="text-[10px] text-gray-400 font-bold">{{ $r['ref']['sub'] }}</p>
                            @else
                                <span class="text-xs text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="p-4">
                            <p class="text-sm font-black text-gray-800">{{ number_format($r['amount'], 2) }} <small class="text-gray-400">ر.س</small></p>
                            @if ($r['refunded'] > 0)
                                <p class="text-[10px] font-bold text-indigo-600">مسترد {{ number_format($r['refunded'], 2) }}</p>
                            @endif
                        </td>
                        <td class="p-4 text-xs font-bold text-gray-600">{{ $r['method'] }}</td>
                        <td class="p-4 text-center">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-black {{ $statusCls[$r['status']] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $statusAr[$r['status']] ?? $r['status'] }}
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-black {{ $matchMap[$r['match']][1] }}">
                                {{ $matchMap[$r['match']][0] }}
                            </span>
                            @if ($r['note'])
                                <p class="text-[10px] font-bold text-rose-600 mt-1">{{ $r['note'] }}</p>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-8 text-center text-sm font-bold text-gray-400">لا توجد دفعات مطابقة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- التنقل بين الصفحات --}}
    @if (!empty($meta))
        <div class="flex items-center justify-between text-xs font-bold text-gray-500">
            <span>صفحة {{ $meta['current_page'] ?? $page }} من {{ $meta['total_pages'] ?? 1 }}
                ({{ $meta['total_count'] ?? 0 }} دفعة)</span>
            <div class="flex gap-2">
                @if (!empty($meta['prev_page']))
                    <a href="{{ request()->fullUrlWithQuery(['page' => $meta['prev_page']]) }}" class="px-4 py-2 bg-white border border-gray-200 rounded-xl">السابق</a>
                @endif
                @if (!empty($meta['next_page']))
                    <a href="{{ request()->fullUrlWithQuery(['page' => $meta['next_page']]) }}" class="px-4 py-2 bg-white border border-gray-200 rounded-xl">التالي</a>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection