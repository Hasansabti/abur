{{--
    صفحة دفع موحّدة (حجز + مدفوعات إضافية)
    المتغير المطلوب $pay:
    [
        'kind'         => 'booking' | 'extra',
        'booking_id'   => int,
        'trip_title'   => string|null,
        'amount'       => float (بالريال),
        'description'  => string|null,        // سبب الطلب (للإضافي)
        'callback_url' => string,
        'metadata'     => array,              // قيم نصية فقط
        'expires_in'   => int|null,           // ثواني، null = بدون عداد
        'abort_url'    => string|null,        // لإلغاء الحجز (للحجز فقط)
        'back_url'     => string|null,        // زر العودة (للإضافي)
    ]
--}}
@php
    $isExtra = ($pay['kind'] ?? 'booking') === 'extra';
    $accent = $isExtra ? '#d97706' : '#2563eb';
    $accentDark = $isExtra ? '#b45309' : '#1d4ed8';
    $accentRgb = $isExtra ? '217, 119, 6' : '37, 99, 235';
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isExtra ? 'دفع مبلغ إضافي' : 'إتمام الدفع' }} - عبور</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <link rel="stylesheet" href="https://cdn.moyasar.com/mpf/1.14.0/moyasar.css" />
    <script src="https://cdn.moyasar.com/mpf/1.14.0/moyasar.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Tajawal', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --accent: {{ $accent }};
            --accent-dark: {{ $accentDark }};
            --accent-rgb: {{ $accentRgb }};
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: radial-gradient(circle at top, #f1f5f9 0%, #e2e8f0 100%);
        }

        .accent-text {
            color: var(--accent);
        }

        .mysr-form .mysr-footer,
        .mysr-form [class*="footer"],
        .mysr-form .mysr-branding {
            display: none !important;
        }

        .mysr-form {
            max-width: 100% !important;
            margin: 0 !important;
        }

        .mysr-form .mysr-form-group label {
            font-family: 'Tajawal', sans-serif !important;
            font-weight: 700 !important;
            color: #334155 !important;
            font-size: 0.875rem !important;
            margin-bottom: 0.5rem !important;
        }

        .mysr-form .mysr-form-group input,
        .mysr-form .mysr-form-group select {
            border-radius: 0.85rem !important;
            border: 1.5px solid #cbd5e1 !important;
            padding: 0.75rem 1rem !important;
            font-family: 'Tajawal', sans-serif !important;
            background-color: #f8fafc !important;
            color: #0f172a !important;
            transition: all 0.2s ease-in-out !important;
        }

        .mysr-form .mysr-form-group input:focus {
            background-color: #ffffff !important;
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 4px rgba(var(--accent-rgb), 0.12) !important;
        }

        .mysr-form button.mysr-btn-submit {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%) !important;
            border-radius: 1rem !important;
            font-family: 'Tajawal', sans-serif !important;
            font-weight: 800 !important;
            font-size: 1.1rem !important;
            padding: 0.9rem !important;
            margin-top: 1rem !important;
            box-shadow: 0 10px 20px -5px rgba(var(--accent-rgb), 0.35) !important;
            transition: all 0.2s ease-in-out !important;
        }

        .mysr-form button.mysr-btn-submit:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 14px 24px -4px rgba(var(--accent-rgb), 0.45) !important;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 md:p-8 relative">

    <div class="absolute top-10 right-10 w-80 h-80 rounded-full blur-3xl pointer-events-none"
        style="background: rgba(var(--accent-rgb), 0.15)"></div>
    <div class="absolute bottom-10 left-10 w-80 h-80 bg-indigo-300/20 rounded-full blur-3xl pointer-events-none"></div>

    <div
        class="max-w-4xl w-full bg-white/90 backdrop-blur-md rounded-3xl shadow-2xl overflow-hidden border border-white/80 z-10">

        <!-- الشريط العلوي -->
        <div class="bg-white px-6 py-5 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div
                    class="w-11 h-11 bg-blue-600 rounded-2xl flex items-center justify-center font-black text-2xl text-white shadow-md shadow-blue-500/20">
                    ع
                </div>
                <div>
                    <h1 class="font-extrabold text-xl text-slate-900 tracking-wide">عبور <span
                            class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">OBOUR</span>
                    </h1>
                    <p class="text-xs text-slate-400">بوابة الدفع الآمنة والسريعة</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12">

            <!-- العمود الأيمن: الملخص -->
            <div
                class="lg:col-span-5 bg-slate-50/70 p-6 lg:p-8 border-b lg:border-b-0 lg:border-l border-slate-100 flex flex-col justify-between">
                <div>
                    @if (!empty($pay['expires_in']))
                        <div class="text-center my-4">
                            <span class="text-xs text-slate-500 font-bold">الوقت المتبقي لإتمام الدفع:</span>
                            <span id="countdown-timer" class="text-lg font-black text-red-600 font-mono dir-ltr">00:00</span>
                        </div>
                    @endif

                    <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider mb-4">
                        {{ $isExtra ? 'تفاصيل الدفع الإضافي' : 'تفاصيل الحجز' }}
                    </h3>

                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/70 mb-6 space-y-3">
                        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                            <span class="text-slate-500 text-sm">رقم الحجز:</span>
                            <span
                                class="font-black text-blue-600 bg-blue-50 px-3 py-1 rounded-xl text-sm border border-blue-100">#BK-{{ $pay['booking_id'] }}</span>
                        </div>

                        @if (!empty($pay['trip_title']))
                            <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                                <span class="text-slate-500 text-sm">الرحلة:</span>
                                <span
                                    class="font-bold text-slate-800 text-sm truncate max-w-[150px]">{{ $pay['trip_title'] }}</span>
                            </div>
                        @endif

                        @if (!empty($pay['description']))
                            <div class="pb-3 border-b border-slate-100">
                                <span class="block text-slate-500 text-sm mb-1">سبب الطلب:</span>
                                <p
                                    class="text-xs font-bold text-amber-900 bg-amber-50 border border-amber-200 rounded-xl p-3 leading-relaxed">
                                    {{ $pay['description'] }}
                                </p>
                            </div>
                        @endif

                        <div class="flex justify-between items-center pt-2">
                            <span class="text-slate-900 font-extrabold text-base">
                                {{ $isExtra ? 'المبلغ المطلوب:' : 'المبلغ الإجمالي:' }}
                            </span>
                            <div class="text-left">
                                <span
                                    class="text-2xl font-black accent-text">{{ number_format($pay['amount'], 2) }}</span>
                                <span class="text-xs font-bold text-slate-400">ر.س</span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2.5 px-1">
                        <div class="flex items-center gap-2.5 text-xs font-medium text-slate-600">
                            <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                            <span>
                                {{ $isExtra ? 'يُضاف المبلغ إلى حجزك فور اكتمال الدفع' : 'إصدار تذكرة سفرك فور اكتمال الدفع مباشرة' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2.5 text-xs font-medium text-slate-600">
                            <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                            <span>معالجة الدفع مشفرة تماماً وحسب المعايير البنكية</span>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-200/60">
                    <p class="text-[11px] text-slate-400 font-bold text-center mb-3">وسائل الدفع المتاحة</p>
                    <div class="flex items-center justify-center gap-4 text-slate-400 text-2xl opacity-80">
                        <i class="fa-brands fa-cc-visa hover:text-blue-600 transition-colors" title="Visa"></i>
                        <i class="fa-brands fa-cc-mastercard hover:text-red-500 transition-colors"
                            title="Mastercard"></i>
                        <i class="fa-brands fa-apple-pay hover:text-slate-900 transition-colors text-3xl"
                            title="Apple Pay"></i>
                        <span
                            class="text-[11px] font-black bg-slate-200/80 text-slate-700 px-2 py-0.5 rounded-md border border-slate-300/50">مدى</span>
                    </div>
                </div>
            </div>

            <!-- العمود الأيسر: نموذج البطاقة -->
            <div class="lg:col-span-7 p-6 lg:p-8 bg-white flex flex-col justify-center">

                @if (session('error'))
                    <div
                        class="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-2xl text-sm font-bold mb-6 flex items-center gap-3 shadow-sm">
                        <i class="fa-solid fa-circle-exclamation text-lg text-rose-500"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                <div class="mb-6">
                    <h2 class="text-xl font-black text-slate-900">بيانات البطاقة</h2>
                    <p class="text-xs text-slate-500 mt-1">أدخل بيانات بطاقتك البنكية لإتمام الدفع بأمان</p>
                </div>

                <div class="mysr-form"></div>

                <div class="mt-4 pt-3 border-t border-slate-100">
                    @if (!empty($pay['abort_url']))
                        {{-- إلغاء الحجز (للحجز فقط) --}}
                        <form action="{{ $pay['abort_url'] }}" method="POST"
                            onsubmit="try { Object.keys(sessionStorage).filter(k => k.startsWith('booking_draft_')).forEach(k => sessionStorage.removeItem(k)); } catch (e) {}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                onclick="return confirm('هل أنت متأكد من رغبتك في إلغاء هذا الحجز بالكامل؟ سيتم فقدان مقاعدك.');"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200/80 font-bold text-xs rounded-xl transition-all active:scale-95">
                                <i class="fa-solid fa-xmark"></i>
                                <span>إلغاء الحجز كلياً والعودة</span>
                            </button>
                        </form>
                    @elseif(!empty($pay['back_url']))
                        {{-- العودة (للإضافي) --}}
                        <a href="{{ $pay['back_url'] }}"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 font-bold text-xs rounded-xl transition-all active:scale-95">
                            <i class="fa-solid fa-arrow-right"></i>
                            <span>العودة إلى حسابي</span>
                        </a>
                    @endif
                </div>

                <div class="mt-6 text-center">
                    <p class="text-xs text-slate-400">
                        بالضغط على زر الدفع، فإنك توافق على <a href="#"
                            class="underline font-semibold text-slate-600 hover:text-blue-600">شروط وإرشادات الحجز</a>.
                    </p>
                </div>
            </div>

        </div>
    </div>

    <script>
        @if (!$isExtra)
            // الحجز انسجل في السيرفر، فالمسودة المؤقتة ما عاد نحتاجها
            try {
                Object.keys(sessionStorage)
                    .filter(k => k.startsWith('booking_draft_'))
                    .forEach(k => sessionStorage.removeItem(k));
            } catch (e) {}
        @endif

        @if (!empty($pay['expires_in']))
            document.addEventListener('DOMContentLoaded', function() {
                const tripsUrl = "{{ route('client.trips') }}";
                let remainingSeconds = {{ (int) $pay['expires_in'] }};
                const timerElement = document.getElementById('countdown-timer');

                if (remainingSeconds <= 0) {
                    alert('انتهت المهلة المحددة لإتمام عملية الدفع لهذا الحجز.');
                    window.location.href = tripsUrl;
                    return;
                }

                function updateTimerDisplay() {
                    const m = Math.floor(Math.max(0, remainingSeconds) / 60).toString().padStart(2, '0');
                    const s = (Math.max(0, remainingSeconds) % 60).toString().padStart(2, '0');
                    if (timerElement) timerElement.textContent = `${m}:${s}`;
                }

                updateTimerDisplay();

                const countdownInterval = setInterval(() => {
                    remainingSeconds--;
                    updateTimerDisplay();

                    if (remainingSeconds <= 0) {
                        clearInterval(countdownInterval);
                        alert('انتهت المهلة المحددة لإتمام عملية الدفع، سيتم توجيهك الآن لصفحة الرحلات.');
                        window.location.href = tripsUrl;
                    }
                }, 1000);
            });
        @endif

        Moyasar.init({
            element: '.mysr-form',
            amount: {{ (int) round($pay['amount'] * 100) }},
            currency: 'SAR',
            description: @js($isExtra ? 'دفع إضافي للحجز #BK-' . $pay['booking_id'] : 'دفع حجز رقم #BK-' . $pay['booking_id']),
            publishable_api_key: '{{ config('services.moyasar.publishable_key') }}',
            callback_url: @js($pay['callback_url']),
            methods: ['creditcard'],
            metadata: @js($pay['metadata'] ?? new stdClass()),

            apple_pay: {
                label: 'عبور للرحلات',
                country: 'SA',
                validate_merchant_url: 'https://api.moyasar.com/v1/applepay/initiate'
            },

            on_failure: function(error) {
                alert('سبب الفشل: ' + JSON.stringify(error));
            }
        });
    </script>

</body>

</html>