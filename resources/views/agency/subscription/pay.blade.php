@extends('layouts.agency')

@section('content')
    @php
        $cycleMap = ['monthly' => 'شهري', 'yearly' => 'سنوي'];
        $amountHalalas = (int) round($subscription->price_paid * 100);
    @endphp

    {{--
        مهم: استبدل سطري الـ CSS والـ JS التاليين بنفس السطرين الموجودين في صفحة الدفع الحالية
        (resources/views/client/payment/pay.blade.php) حتى تتطابق نسخة نموذج ميسر.
    --}}
    <link rel="stylesheet" href="https://cdn.moyasar.com/mpf/1.14.0/moyasar.css" />
    <script src="https://cdn.moyasar.com/mpf/1.14.0/moyasar.js"></script>

    <div class="p-4 sm:p-6 lg:p-8 max-w-xl mx-auto space-y-6" dir="rtl">

        <div>
            <h3 class="text-2xl font-bold text-slate-900 tracking-tight">إتمام الدفع</h3>
            <p class="text-xs text-slate-500 mt-1">دفع آمن عبر بوابة ميسر، ويتفعّل اشتراكك فور نجاح العملية.</p>
        </div>

        @if (session('error'))
            <div class="p-4 rounded-2xl bg-rose-50 text-rose-700 border border-rose-200/80 text-sm font-medium">{{ session('error') }}</div>
        @endif

        {{-- ملخص الطلب --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500">الباقة</span>
                <span class="font-black text-slate-900">{{ $subscription->plan->name }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500">الدورة</span>
                <span class="font-bold text-slate-700 text-sm">{{ $cycleMap[$subscription->billing_cycle] }}</span>
            </div>
            <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                <span class="text-xs text-slate-500">المبلغ المستحق</span>
                <span class="text-2xl font-black text-slate-900 font-mono">
                    {{ number_format($subscription->price_paid, 2) }}
                    <span class="text-xs font-normal text-slate-400">ر.س</span>
                </span>
            </div>
        </div>

        {{-- نموذج ميسر --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <div class="mysr-form"></div>
        </div>

        <form method="POST" action="{{ route('agency.subscription.cancel-checkout', $subscription) }}"
            onsubmit="return confirm('إلغاء عملية الاشتراك؟')" class="text-center">
            @csrf @method('DELETE')
            <button class="text-xs font-bold text-slate-400 hover:text-rose-600">إلغاء والعودة</button>
        </form>
    </div>

    <script>
        Moyasar.init({
            element: '.mysr-form',
            amount: {{ $amountHalalas }},
            currency: 'SAR',
            description: 'اشتراك باقة {{ $subscription->plan->name }} ({{ $cycleMap[$subscription->billing_cycle] }}) - {{ $subscription->agency->name }}',
            publishable_api_key: '{{ config('services.moyasar.publishable_key') }}',
            callback_url: '{{ route('agency.subscription.callback', $subscription) }}',
            methods: ['creditcard'],
            supported_networks: ['visa', 'mastercard', 'mada'],
            // الربط بالاشتراك: يتحقق منه السيرفر قبل التفعيل (ولا يعتمد عليه وحده)
            metadata: { subscription_id: '{{ $subscription->id }}' },
        });
    </script>
@endsection