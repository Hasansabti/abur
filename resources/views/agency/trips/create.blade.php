@extends('layouts.agency')

@section('content')

    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  1) تجهيز البيانات                                            ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    @php
        // ----- دوال مساعدة -----
        // قيمة منطقية تحترم old() بعد فشل التحقق، وإلا تستخدم القيمة المحفوظة
        $bool = fn($key, $default = false) => session()->hasOldInput()
            ? filter_var(old($key), FILTER_VALIDATE_BOOLEAN)
            : (bool) $default;
        $dt = fn($v) => $v ? date('Y-m-d\TH:i', strtotime($v)) : '';
        $arr = fn($v) => is_array($v) ? $v : (json_decode($v ?? '[]', true) ?: []);

        // ----- العميل / الطلب الخاص -----
        $selectedUserId = old('user_id', $trip->user_id ?? request('user_id'));
        $selectedRequestId = old(
            'private_trip_request_id',
            $trip->private_trip_request_id ?? request('private_request_id'),
        );
        $targetUser = $user ?? ($trip->user ?? ($selectedUserId ? \App\Models\User::find($selectedUserId) : null));
        $hasCustomer = $targetUser || $selectedUserId;

        // ----- صورة الغلاف -----
        $coverImageUrl = !empty($trip->cover_image)
            ? (str_starts_with($trip->cover_image, 'http')
                ? $trip->cover_image
                : asset('storage/' . $trip->cover_image))
            : '';

        // ----- المدن -----
        $allSaudiCities = [
            'الرياض',
            'مكة المكرمة',
            'المدينة المنورة',
            'جدة',
            'الدمام',
            'الخبر',
            'الظهران',
            'الهفوف',
            'الأحساء',
            'الطائف',
            'تبوك',
            'بريدة',
            'عنيزة',
            'حائل',
            'أبها',
            'خميس مشيط',
            'جازان',
            'نجران',
            'الباحة',
            'سكاكا',
            'عرعر',
            'الجبيل',
            'القطيف',
            'ينبع',
            'الخرج',
            'حفر الباطن',
            'القريات',
            'بيشة',
            'المجمعة',
            'الدوادمي',
            'شرورة',
            'رابغ',
            'طريف',
            'القنفذة',
            'الليث',
            'وادي الدواسر',
            'الزلفي',
            'شقراء',
            'بقيق',
            'رأس تنورة',
            'النعيرية',
            'الخفجي',
            'العلا',
            'أملج',
            'الوجه',
            'ضباء',
            'طبرجل',
            'تيماء',
            'دومة الجندل',
            'بلجرشي',
            'النماص',
            'محايل عسير',
            'صبيا',
            'أبو عريش',
            'صامطة',
            'أحد رفيدة',
            'السليل',
            'عفيف',
            'القويعية',
            'الغاط',
            'ثادق',
            'حوطة بني تميم',
            'الأفلاج',
            'رنية',
            'تربة',
        ];
        $cityFields = [['from_city', 'من (المدينة)', 'اختر المدينة...'], ['to_city', 'إلى (الوجهة)', 'اختر الوجهة...']];

        // ----- خيارات النظام / النقل / المسار: [name, model, label, accent, [[value, icon, text]...]] -----
        $choiceGroups = [
            [
                'trip_type',
                'tripType',
                'نظام الرحلة',
                'blue',
                [['one_way', '↗️', 'ذهاب فقط'], ['round_trip', '🔄', 'ذهاب وعودة']],
            ],
            ['transport_type', 'transport', 'وسيلة النقل', 'indigo', [['bus', '🚌', 'بري'], ['flight', '✈️', 'طيران']]],
            [
                'route_type',
                'routeType',
                'نوع المسار',
                'purple',
                [['domestic', '🇸🇦', 'داخلية'], ['international', '🌍', 'خارجية']],
            ],
        ];

        // ----- المميزات -----
        $predefinedFeatures = [
            'iraq_sheikh' => 'يرافقك شيخ',
            'coaching' => 'يوجد كوادر بالرحلة',
            'meals' => 'شاملة الوجبات',
            'wifi' => 'إنترنت مجاني',
            'holy_places' => 'زيارة المشاعر',
            'close_to_haram' => 'قريب من الحرم',
        ];
        $currentFeatures = (array) old('features', $arr($trip->features ?? null));
        $customFeatures = array_values(array_diff($currentFeatures, $predefinedFeatures));

        // ----- الخدمات الإضافية -----
        $currentExtraServices = array_values((array) old('extra_services', $arr($trip->extra_services ?? null)));

        // ----- الوجبات: [modelKey, activeName, priceName, icon, label, placeholder] -----
        $meals = [
            ['hasBreakfast', 'bf_price_active', 'breakfast_price', '🍳', 'وجبة فطور', 'سعر الفطور المفرد'],
            ['hasLunch', 'ln_price_active', 'lunch_price', '🍗', 'وجبة غداء', 'سعر الغداء المفرد'],
            ['hasDinner', 'dn_price_active', 'dinner_price', '🍔', 'وجبة عشاء', 'سعر العشاء المفرد'],
        ];
        // [name, label, شرط الظهور, باقة كاملة؟]
        $bundles = [
            ['price_bf_ln', '🛍️ سعر باقة (فطور + غداء) للراكب', 'hasBreakfast && hasLunch', false],
            ['price_ln_dn', '🛍️ سعر باقة (غداء + عشاء) للراكب', 'hasLunch && hasDinner', false],
            ['price_bf_dn', '🛍️ سعر باقة (فطور + عشاء) للراكب', 'hasBreakfast && hasDinner', false],
            [
                'price_all_meals',
                '🎁 سعر الباقة الكاملة (فطور + غداء + عشاء)',
                'hasBreakfast && hasLunch && hasDinner',
                true,
            ],
        ];

        // ----- الغرف -----
        $roomTypes = ['single' => 'فردية', 'double' => 'ثنائية', 'triple' => 'ثلاثية', 'quad' => 'رباعية'];
        $roomOptions = is_string($trip->room_options ?? null)
            ? (json_decode($trip->room_options, true) ?:
            [])
            : $trip->room_options ?? [];
        // ----- الباص الثاني -----
        $overflowTrip = !empty($trip->id) ? $trip->overflowTrip : null;
        $overflowActive = $overflowTrip && !$overflowTrip->is_overflow_standby;
        $overflowHasBookings = $overflowTrip
            ? \App\Models\BookingSeat::where('trip_id', $overflowTrip->id)->exists()
            : false;

        // ----- المفاتيح داخل قسم السكن -----
        $toggles = [
            [
                'تفعيل هذا الخيار يسمح للعملاء بحجز أسِرّة منفردة داخل غرفة مشتركة. حدد أنواع الغرف المتاحة للمشاركة من بطاقات الغرف أعلاه.',
                'sharedRooms',
                '👥',
                'bg-indigo-50 text-indigo-600',
                'الغرف المشتركة',
                'تفعيل هذا الخيار يسمح للعملاء بحجز أسِرّة منفردة داخل غرفة مشتركة.',
            ],
            [
                'allow_to_add_seat',
                'extraSeat',
                '💺',
                'bg-indigo-50 text-indigo-600',
                'مقاعد إضافية',
                'هذا الخيار يسمح للعميل بحجز مقعد إضافي.',
            ],
            [
                'is_security_approved',
                'autoSeating',
                '🔀',
                'bg-indigo-50 text-indigo-600',
                'ترتيب المقاعد',
                'إعادة توزيع المقاعد ديناميكياً لصالح الفئة الأعلى طلباً عند بلوغ إشغالها 80% (تفعيل الزر يوقف التوزيع التلقائي للمقاعد).',
            ],
            [
                'allow_accommodation_only',
                'accomOnly',
                '🏢',
                'bg-emerald-50 text-emerald-600',
                'السماح بحجز السكن فقط',
                'تفعيل هذا الخيار يتيح للمسافرين حجز الغرف الفندقية بدون خدمات النقل والذهاب.',
            ],
        ];

        // ----- الحسابات البنكية -----
        $savedAccounts = [];
        if (old('bank_names')) {
            foreach (old('bank_names') as $i => $bankName) {
                $savedAccounts[] = [
                    'bank_name' => $bankName,
                    'iban' => old("iban_numbers.$i"),
                    'account_number' => old("account_number.$i"),
                    'qr_code' => old("old_bank_images.$i"),
                ];
            }
        } else {
            $savedAccounts = $arr($trip->bank_accounts ?? null);
            if (empty($savedAccounts) && isset($bankAccounts) && $bankAccounts->count() > 0) {
                foreach ($bankAccounts as $account) {
                    $savedAccounts[] = [
                        'bank_name' => $account->bank_name,
                        'iban' => $account->iban,
                        'account_number' => $account->account_number,
                        'qr_code' => $account->qr_code ?? '',
                    ];
                }
            }
        }
        if (empty($savedAccounts)) {
            $savedAccounts = [['bank_name' => '', 'iban' => '', 'account_number' => '', 'qr_code' => '']];
        }
        // تجهيز رابط صورة QR لكل حساب
        $savedAccounts = array_map(function ($a) {
            $p = $a['qr_code'] ?? '';
            $a['qr_url'] = $p ? (str_starts_with($p, 'storage/') ? asset($p) : asset('storage/' . $p)) : '';
            return $a;
        }, $savedAccounts);

        // ----- الملاحظات الافتراضية -----
        $defaultNotes =
            "شروط وملاحظات عامة للرحلة:\n" .
            "• الالتزام بوقت التجمع والانطلاق، والتأخير قد يؤدي إلى مغادرة الرحلة دون انتظار.\n" .
            "• التأكد من إحضار الهوية / الوثائق المطلوبة وأي تصاريح لازمة.\n" .
            "• الالتزام بتعليمات المنظم وقواعد السلامة طوال الرحلة.\n" .
            "• كل مشارك مسؤول عن أغراضه الشخصية ومقتنياته.\n" .
            "• الالتزام بالأنظمة والتعليمات الخاصة بالموقع أو الوجهة.\n" .
            "• المحافظة على نظافة المكان وعدم ترك المخلفات.\n" .
            "• يمنع التصرفات التي قد تعرض الشخص أو الآخرين للخطر.\n" .
            "• البرنامج والأوقات قابلة للتعديل عند الحاجة وفقًا للظروف أو تعليمات الجهة المنظمة.\n" .
            '• التسجيل في الرحلة يعني الاطلاع على الشروط والملاحظات والموافقة عليها.';

        // ----- الحالة الابتدائية لنموذج Alpine -----
        $formState = [
            'tripType' => old('trip_type', $trip->trip_type ?: 'one_way'),
            'transport' => old('transport_type', $trip->transport_type ?: 'bus'),
            'routeType' => old('route_type', $trip->route_type ?: 'domestic'),
            'departureAt' => old('departure_at', $dt($trip->departure_at ?? null)),
            'returnAt' => old('return_at', $dt($trip->return_at ?? null)),
            'seats' => (string) old('total_seats', $trip->total_seats ?? ''),
            'isConditional' => $bool('is_conditional', $trip->is_conditional ?? false),
            'hasBreakfast' => $bool('bf_price_active', !empty($trip->breakfast_price)),
            'hasLunch' => $bool('ln_price_active', !empty($trip->lunch_price)),
            'hasDinner' => $bool('dn_price_active', !empty($trip->dinner_price)),
            'sharedRooms' => $bool('allows_shared_rooms', $trip->allows_shared_rooms ?? false),
            'extraSeat' => $bool('allow_to_add_seat', $trip->allow_to_add_seat ?? false),
            'autoSeating' => $bool('is_security_approved', $trip->is_security_approved ?? true),
            'accomOnly' => $bool('allow_accommodation_only', $trip->allow_accommodation_only ?? true),

            'overflowEnabled' => $bool('overflow_enabled', $trip->overflow_enabled ?? false),
            'overflowSeats' => (string) old('overflow_total_seats', $overflowTrip->total_seats ?? ''),
        ];
    @endphp


    <div class="container mx-auto px-4 py-8 max-w-5xl text-right" dir="rtl">

        {{-- ═════════════ أخطاء السيرفر ═════════════ --}}
        @if ($errors->any())
            <div class="rise mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-sm font-bold shadow-sm">
                <div class="flex items-center gap-2 mb-2 text-base">
                    <span>⚠️</span><span>لم يتم نشر الرحلة بسبب النواقص التالية:</span>
                </div>
                <ul class="list-disc list-inside pr-4 text-xs font-semibold space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ═════════════ الهيدر ═════════════ --}}
        <header class="rise flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h1 class="text-3xl font-black text-slate-800 flex items-center gap-3">
                    <span
                        class="w-12 h-12 bg-blue-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-blue-100">🚀</span>
                    {{ isset($trip->id) ? 'تعديل بيانات الرحلة' : 'إضافة رحلة جديدة' }}
                </h1>
                <p class="text-sm text-slate-400 mt-2 font-medium">قم بتعبئة بيانات الرحلة وتسميتها لنشرها في "عبور"</p>
            </div>
            <div class="flex items-center gap-2 bg-white p-2 rounded-2xl border border-slate-100 shadow-sm">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Agency Mode</span>
            </div>
        </header>


        {{-- ╔══════════════════════════════════════════════════════════════╗
             ║  2) النموذج                                                   ║
             ╚══════════════════════════════════════════════════════════════╝ --}}
        <form method="POST" enctype="multipart/form-data" class="stagger flex flex-col gap-6"
            action="{{ isset($trip->id) ? route('agency.trips.update', $trip->id) : route('agency.trips.store') }}"
            x-data="tripForm(@js($formState))" @submit="lock()" @pageshow.window="submitting = false">

            <div hidden>
                @csrf
                @if (isset($trip->id))
                    @method('PUT')
                @endif
                @if ($selectedUserId)
                    <input type="hidden" name="user_id" value="{{ $selectedUserId }}">
                @endif
                @if ($selectedRequestId)
                    <input type="hidden" name="private_trip_request_id" value="{{ $selectedRequestId }}">
                @endif
            </div>


            {{-- ═════════════ (أ) البيانات الأساسية + الغلاف ═════════════ --}}
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- ----- العمود الأيمن (2/3) ----- --}}
                <div class="lg:col-span-2 flex flex-col gap-6">

                    {{-- كارت العميل (رحلة خاصة) --}}
                    @if ($hasCustomer)
                        <div
                            class="bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent p-6 rounded-[2rem] border border-amber-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-14 h-14 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-2xl shadow-md shadow-amber-500/20">
                                    👤</div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="bg-amber-500 text-white text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider">رحلة
                                            خاصة مخصصة</span>
                                        @if ($selectedRequestId)
                                            <span class="text-xs text-amber-800 font-bold">#طلب رقم
                                                {{ $selectedRequestId }}</span>
                                        @endif
                                    </div>
                                    <h3 class="text-base font-black text-slate-800 mt-1">
                                        {{ $targetUser->name ?? 'عميل محدد' }}</h3>
                                    <p class="text-xs text-slate-500 font-semibold flex items-center gap-2 mt-0.5">
                                        <span>📞 {{ $targetUser->phone ?? 'غير متوفر' }}</span>
                                        @if (!empty($targetUser->email))
                                            <span>• ✉️ {{ $targetUser->email }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div
                                class="text-xs font-bold text-amber-800 bg-amber-100/80 px-4 py-2 rounded-xl border border-amber-200 text-center">
                                سيتم تخصيص هذه الرحلة لهذا العميل مباشرة 🎯
                            </div>
                        </div>
                    @endif

                    {{-- التسمية + النظام + النقل + المسار --}}
                    <div class="card">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                            <div class="md:col-span-3">
                                <label class="field-label">تسمية الرحلة (العنوان التسويقي)</label>
                                <input type="text" name="title" required placeholder="مثلاً: رحلة خاصة - العائلات"
                                    value="{{ old('title', $trip->title ?? '') }}"
                                    class="field-input !text-lg !py-4 @error('title') is-invalid @enderror">
                                @error('title')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>

                            @foreach ($choiceGroups as [$name, $model, $label, $acc, $options])
                                <div>
                                    <label class="field-label">{{ $label }}</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach ($options as [$value, $icon, $text])
                                            <label class="opt acc-{{ $acc }}">
                                                <input type="radio" name="{{ $name }}"
                                                    value="{{ $value }}" x-model="{{ $model }}"
                                                    class="opt-input sr-only">
                                                <span
                                                    class="opt-pill"><span>{{ $icon }}</span><span>{{ $text }}</span></span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- المسار والتوقيت --}}
                    <div class="card">
                        <h2 class="card-title">
                            <span class="card-icon bg-blue-100 text-blue-600">🧭</span> المسار والتوقيت
                        </h2>

                        {{-- المدن --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($cityFields as [$field, $label, $placeholder])
                                <div class="relative" :class="open ? 'z-30' : 'z-10'" x-data="citySelect(@js(old($field, $trip->{$field} ?? '')))"
                                    @click.outside="open = false" @keydown.escape="open = false">

                                    <label class="field-label">{{ $label }}</label>

                                    <div class="relative">
                                        {{-- حقل مخفي بصرياً حتى يعمل required فعلياً --}}
                                        <input type="text" name="{{ $field }}" :value="selected" required
                                            tabindex="-1" class="absolute inset-0 w-full opacity-0 pointer-events-none"
                                            @focus="open = true">

                                        <button type="button" @click="toggle()"
                                            class="field-input flex items-center justify-between text-right hover:bg-slate-100/80">
                                            <span x-text="selected || '{{ $placeholder }}'"
                                                :class="selected ? 'text-slate-800' : 'text-slate-400'"></span>
                                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-300"
                                                :class="open && 'rotate-180 text-blue-500'" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <div x-show="open" x-cloak x-bind="popFx"
                                            class="absolute top-full right-0 z-50 mt-1.5 w-full bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-56 overflow-y-auto soft-scroll">
                                            <div class="sticky top-0 bg-white pb-1.5 z-10">
                                                <input type="text" x-ref="q" x-model="search"
                                                    placeholder="ابحث عن مدينة..."
                                                    class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-700 outline-none focus:border-blue-500">
                                            </div>
                                            <div class="space-y-0.5">
                                                <template x-for="city in filtered" :key="city">
                                                    <button type="button" @click="pick(city)" x-text="city"
                                                        class="w-full text-right px-2.5 py-1.5 text-xs font-bold rounded-lg transition-colors hover:bg-slate-50"
                                                        :class="selected === city ? 'bg-blue-50 text-blue-600' :
                                                            'text-slate-600'"></button>
                                                </template>
                                                <div x-show="!filtered.length"
                                                    class="text-center py-3 text-xs text-slate-400">لا توجد نتائج 🔍</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- تاريخ الانطلاق --}}
                        <div class="mt-5">
                            <label for="departure_at_input" class="field-label">تاريخ الانطلاق</label>
                            <input type="datetime-local" name="departure_at" id="departure_at_input" required
                                x-model="departureAt" class="field-input @error('departure_at') is-invalid @enderror">
                            @error('departure_at')
                                <p class="field-error">⚠️ {{ $message }}</p>
                            @enderror
                        </div>

                        {{-- تفاصيل العودة + الأيام والليالي (تنزلق عند اختيار ذهاب وعودة) --}}
                        <div class="reveal" :class="{ open: isRound }">
                            <div class="reveal-inner" :inert="!isRound">
                                <div class="mt-5 p-5 rounded-2xl bg-blue-50/40 border border-dashed border-blue-200">
                                    <p class="text-[11px] font-black text-blue-500 mb-4 uppercase tracking-widest">تفاصيل
                                        العودة</p>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="field-label">تاريخ العودة</label>
                                            <input type="datetime-local" name="return_at" id="return_at_input"
                                                x-model="returnAt" :min="departureAt" :required="isRound"
                                                class="field-input !bg-white @error('return_at') is-invalid @enderror">
                                            @error('return_at')
                                                <p class="field-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="field-label">☀️ مدة الرحلة (أيام)</label>
                                            <input type="number" name="duration_days" id="duration_days_input" readonly
                                                min="1" required :value="days"
                                                class="field-input @error('duration_days') is-invalid @enderror">
                                            @error('duration_days')
                                                <p class="field-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="field-label">🌙 عدد الليالي</label>
                                            <input type="number" name="duration_nights" id="duration_nights_input"
                                                readonly min="0" :value="nights"
                                                class="field-input @error('duration_nights') is-invalid @enderror">
                                            @error('duration_nights')
                                                <p class="field-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <p x-show="invalidReturn" x-transition class="field-error">⚠️ تاريخ العودة يجب أن يكون
                                        بعد تاريخ الانطلاق.</p>
                                </div>
                            </div>
                        </div>

                        {{-- في حالة الذهاب فقط تُرسل القيم الافتراضية (يوم واحد / صفر ليالي) --}}
                        <template x-if="!isRound">
                            <div hidden>
                                <input type="hidden" name="duration_days" value="1">
                                <input type="hidden" name="duration_nights" value="0">
                            </div>
                        </template>
                    </div>
                </div>

                {{-- ----- العمود الأيسر (1/3) ----- --}}
                <div class="flex flex-col gap-6">

                    {{-- صورة الغلاف --}}
                    <div x-data="coverImage(@js($coverImageUrl))" :style="preview ? `background-image:url('${preview}')` : ''"
                        class="relative w-full h-[220px] border-2 border-dashed border-slate-200 rounded-[2rem] overflow-hidden bg-cover bg-center hover:border-blue-300 transition-colors duration-300 group">
                        <label
                            class="w-full h-full flex flex-col items-center justify-center cursor-pointer p-4 transition-colors duration-300"
                            :class="preview ? 'bg-white/30 hover:bg-white/60' : 'bg-white/80 hover:bg-slate-50'">
                            <input type="file" name="cover_image" accept="image/*" class="sr-only"
                                @change="pick($event)">
                            <span x-show="!preview"
                                class="text-4xl mb-3 group-hover:scale-110 transition-transform duration-300">🖼️</span>
                            <span class="text-xs font-black text-slate-600 uppercase tracking-widest px-3 py-1 rounded-lg"
                                :class="preview && 'bg-white/80'"
                                x-text="changed ? 'تم اختيار صورة جديدة (اضغط للتغيير)' : (preview ? 'تغيير صورة الغلاف' : 'صورة الغلاف للرحلة')"></span>
                        </label>
                    </div>

                    {{-- رحلة خاصة (VIP) --}}
                    @if ($hasCustomer)
                        <div class="card !p-6 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="card-icon bg-amber-50 text-amber-600 border border-amber-100">👑</span>
                                <div>
                                    <h4 class="text-xs font-black text-slate-800">تفعيل رحلة خاصة</h4>
                                    <p class="text-[10px] text-slate-400 font-bold">رحلة خاصة غير مدرجة للعامة (VIP)</p>
                                </div>
                            </div>
                            <label class="switch acc-amber">
                                <input type="checkbox" name="is_private" value="1" class="sr-only"
                                    {{ $bool('is_private', $trip->is_private ?? false) || $selectedUserId ? 'checked' : '' }}>
                                <span class="switch-track"></span>
                            </label>
                        </div>
                    @endif

                    {{-- نوع المركبة وعدد المقاعد --}}
                    <div class="card !p-6">
                        <label class="field-label"
                            x-text="transport === 'flight' ? 'نوع الطائرة وعدد المقاعد' : 'نوع المركبة وعدد المقاعد'">نوع
                            المركبة وعدد المقاعد</label>
                        <select name="total_seats" required @change="seats = $event.target.value"
                            class="field-input @error('total_seats') is-invalid @enderror">
                            <option value="" disabled :selected="!seats">اختر النوع وعدد المقاعد...</option>
                            <template x-for="o in seatOptions" :key="o.value">
                                <option :value="o.value" x-text="o.text" :selected="o.value == seats"></option>
                            </template>
                        </select>
                        @error('total_seats')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>


            {{-- ═════════════ (ب) رحلة مشروطة (نظام تجميع المسافرين) ═════════════ --}}
            <section class="card border-amber-100 bg-gradient-to-br from-white to-amber-50/30">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="card-icon bg-amber-100 text-amber-700">🎯</span>
                        <div>
                            <label for="is_conditional"
                                class="text-sm font-black text-slate-800 block cursor-pointer select-none">
                                رحلة مشروطة باكتفاء العدد (نظام تجميع المسافرين)
                            </label>
                            <span class="text-[11px] text-slate-400 block font-medium mt-0.5 leading-relaxed">
                                تفعيل هذا الخيار يجعل الرحلة تتطلب حجز عدد محدد من المقاعد قبل المهلة لتأكيد الانطلاق، وفي
                                حال عدم اكتمال العدد يُلغى الطلب تلقائياً.
                            </span>
                        </div>
                    </div>
                    <label class="switch acc-amber">
                        <input type="checkbox" name="is_conditional" id="is_conditional" value="1"
                            x-model="isConditional" class="sr-only">
                        <span class="switch-track"></span>
                    </label>
                </div>

                <div class="reveal" :class="{ open: isConditional }">
                    <div class="reveal-inner" :inert="!isConditional">
                        <div class="mt-6 pt-6 border-t border-amber-100/80 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="field-label">الحد الأدنى للمقاعد لتأكيد الرحلة <span
                                        class="text-red-500">*</span></label>
                                <input type="number" name="min_seats_required" min="1"
                                    placeholder="مثلاً: 10 مقاعد" :required="isConditional"
                                    value="{{ old('min_seats_required', $trip->min_seats_required ?? '') }}"
                                    class="field-input focus:!border-amber-500 focus:!ring-amber-500/10 @error('min_seats_required') is-invalid @enderror">
                                @error('min_seats_required')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="field-label">المهلة الزمنية لاكتمال العدد <span
                                        class="text-red-500">*</span></label>
                                <input type="datetime-local" name="threshold_deadline_at" :required="isConditional"
                                    value="{{ old('threshold_deadline_at', $dt($trip->threshold_deadline_at ?? null)) }}"
                                    class="field-input focus:!border-amber-500 focus:!ring-amber-500/10 @error('threshold_deadline_at') is-invalid @enderror">
                                @error('threshold_deadline_at')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </section>


            {{-- ═════════════ (ج) المميزات ═════════════ --}}
            <section class="card" x-data="featuresPicker(@js($customFeatures))">
                <h2 class="card-title"><span class="card-icon bg-amber-100 text-amber-600">✨</span> مميزات الرحلة وكوادرها
                </h2>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                    @foreach ($predefinedFeatures as $featureName)
                        <label class="opt acc-blue">
                            <input type="checkbox" name="features[]" value="{{ $featureName }}"
                                class="opt-input sr-only" {{ in_array($featureName, $currentFeatures) ? 'checked' : '' }}>
                            <span class="opt-pill sm">{{ $featureName }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="border-t border-slate-100 pt-6">
                    <label class="field-label">هل لديك مميزات إضافية تريد كتابتها؟</label>
                    <div class="flex gap-2 max-w-md">
                        <input type="text" x-model="draft" @keydown.enter.prevent="add()"
                            placeholder="مثال: حافلات VIP حديثة، عشاء فاخر..." class="field-input !py-2.5 !text-xs">
                        <button type="button" @click="add()"
                            class="btn bg-slate-900 hover:bg-slate-800 text-white shrink-0">إضافة +</button>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-4" x-show="items.length">
                        <template x-for="(feature, i) in items" :key="feature + i">
                            <div
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-[11px] font-black bg-blue-50 border border-blue-100 text-blue-700 shadow-sm">
                                <input type="hidden" name="features[]" :value="feature">
                                <span x-text="feature"></span>
                                <button type="button" @click="items.splice(i, 1)"
                                    class="text-blue-400 hover:text-blue-600 transition-colors">✕</button>
                            </div>
                        </template>
                    </div>
                </div>
            </section>


            {{-- ═════════════ (د) الخدمات الإضافية ═════════════ --}}
            <section class="card" x-data="extraServices(@js($currentExtraServices))">
                <h2 class="card-title"><span class="card-icon bg-purple-100 text-purple-600">🧰</span> الخدمات الإضافية
                    والطلبات الخاصة (اختياري للمسافر)</h2>
                <p class="text-xs text-slate-500 font-medium -mt-4 mb-6">
                    أضف الخدمات التي يمكن للمسافر طلبها ودفع قيمتها أثناء اختيار المقاعد (مثل: حضانة الأطفال، كرسي متحرك،
                    حقائب إضافية...).
                </p>

                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                        <div class="md:col-span-5">
                            <label class="field-label">اسم الخدمة <span class="text-red-500">*</span></label>
                            <input type="text" x-model="draft.name" @keydown.enter.prevent="add()"
                                placeholder="مثال: خدمة حضانة الأطفال، حقيبة إضافية..."
                                class="field-input !bg-white !py-2.5 !text-xs focus:!border-purple-500 focus:!ring-purple-500/10">
                        </div>
                        <div class="md:col-span-3">
                            <label class="field-label">السعر (ر.س)</label>
                            <input type="number" step="0.01" min="0" x-model="draft.price"
                                @keydown.enter.prevent="add()" placeholder="0.00 (ضع 0 للمجاني)"
                                class="field-input !bg-white !py-2.5 !text-xs focus:!border-purple-500 focus:!ring-purple-500/10">
                        </div>
                        <div class="md:col-span-4">
                            <label class="field-label">وصف الخدمة (اختياري)</label>
                            <input type="text" x-model="draft.description" @keydown.enter.prevent="add()"
                                placeholder="مثال: رعاية الطفل طوال وقت السفر..."
                                class="field-input !bg-white !py-2.5 !text-xs focus:!border-purple-500 focus:!ring-purple-500/10">
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between gap-3">
                        <p x-show="error" x-transition x-text="error" class="field-error !mt-0"></p>
                        <button type="button" @click="add()"
                            class="btn bg-purple-600 hover:bg-purple-700 text-white mr-auto">+ إضافة الخدمة للرحلة</button>
                    </div>
                </div>

                <div x-show="services.length" class="space-y-3">
                    <label class="field-label">الخدمات المضافة للرحلة حالياً:</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="(service, i) in services" :key="service.uid">
                            <div
                                class="flex items-center justify-between p-4 bg-white border border-slate-200 rounded-2xl shadow-sm hover:border-purple-300 transition-colors duration-200">
                                <input type="hidden" :name="`extra_services[${i}][name]`" :value="service.name">
                                <input type="hidden" :name="`extra_services[${i}][price]`" :value="service.price">
                                <input type="hidden" :name="`extra_services[${i}][description]`"
                                    :value="service.description">

                                <div class="flex-grow pl-3">
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-xs text-slate-800" x-text="service.name"></span>
                                        <span
                                            class="px-2 py-0.5 rounded-lg text-[10px] font-black bg-purple-50 text-purple-700 border border-purple-100"
                                            x-text="service.price > 0 ? service.price + ' ر.س' : 'مجاناً'"></span>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-1"
                                        x-text="service.description || 'بدون وصف إضافي'"></p>
                                </div>

                                <button type="button" @click="remove(i)"
                                    class="w-8 h-8 rounded-xl bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-600 flex items-center justify-center text-xs font-bold transition-colors shrink-0">✕</button>
                            </div>
                        </template>
                    </div>
                </div>

                <div x-show="!services.length" class="text-center py-6 border border-dashed border-slate-200 rounded-2xl">
                    <p class="text-xs font-bold text-slate-400">لم تقم بإضافة أي خدمات إضافية لهذه الرحلة بعد.</p>
                </div>
            </section>


            {{-- ═════════════ (هـ) الوجبات ═════════════ --}}
            <section class="card">
                <h2 class="card-title"><span class="card-icon bg-orange-100 text-orange-600">🍽️</span> إعدادات الوجبات
                    والأسعار الخاصة</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    @foreach ($meals as [$model, $activeName, $priceName, $icon, $label, $placeholder])
                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="hidden" name="{{ $activeName }}" value="false">
                                <input type="checkbox" name="{{ $activeName }}" value="true"
                                    x-model="{{ $model }}"
                                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm font-bold text-slate-700">{{ $icon }}
                                    {{ $label }}</span>
                            </label>

                            <div class="reveal" :class="{ open: {{ $model }} }">
                                <div class="reveal-inner" :inert="!{{ $model }}">
                                    <input type="number" step="0.01" name="{{ $priceName }}"
                                        placeholder="{{ $placeholder }}"
                                        value="{{ old($priceName, $trip->{$priceName} ?? '') }}"
                                        class="field-input !bg-white mt-4">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- باقات الوجبات (تظهر عند تفعيل وجبتين أو أكثر) --}}
                <div class="reveal" :class="{ open: mealCount >= 2 }">
                    <div class="reveal-inner" :inert="mealCount < 2">
                        <div class="border-t border-slate-100 pt-8 mt-8">
                            <h3 class="text-sm font-black mb-6 flex items-center gap-3 text-emerald-700">
                                <span class="card-icon !w-8 !h-8 bg-emerald-100 text-emerald-600">💰</span> أسعار باقات
                                الوجبات (الخصومات)
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                @foreach ($bundles as [$name, $label, $cond, $full])
                                    <div x-show="{{ $cond }}" x-bind="viewFx"
                                        class="rounded-2xl p-5 border {{ $full ? 'bg-blue-50 border-blue-100 md:col-span-2' : 'bg-emerald-50/50 border-emerald-100' }}">
                                        <label
                                            class="block text-sm font-bold mb-3 {{ $full ? 'text-blue-900' : 'text-emerald-900' }}">{{ $label }}</label>
                                        <input type="number" step="0.01" name="{{ $name }}"
                                            value="{{ old($name, $trip->{$name} ?? '') }}" class="field-input !bg-white">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>


            {{-- ═════════════ (و) السكن والأسعار ═════════════ --}}
            <section class="card">
                <h2 class="card-title"><span class="card-icon bg-emerald-100 text-emerald-600">🏨</span> توزيع السكن
                    والأسعار التفصيلية للغرف وباقات الوجبات</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($roomTypes as $key => $label)
                        <div x-data="roomPackages('{{ $key }}', @js(old("room_options.$key.packages", $roomOptions[$key]['packages'] ?? [])))"
                            class="relative bg-slate-50/60 p-6 pt-8 rounded-3xl border border-slate-100">

                            <div
                                class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-white border border-slate-100 rounded-full text-[10px] font-black text-slate-400">
                                غرفة {{ $label }}
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 block mb-1">السعر</label>
                                    <input type="number" min="0" name="room_options[{{ $key }}][price]"
                                        value="{{ old("room_options.$key.price", $roomOptions[$key]['price'] ?? 0) }}"
                                        class="field-input !bg-white !py-2 !px-3 !text-xs !text-blue-600">
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 block mb-1">عدد الغرف</label>
                                    <input type="number" min="0" name="room_options[{{ $key }}][count]"
                                        value="{{ old("room_options.$key.count", $roomOptions[$key]['count'] ?? 0) }}"
                                        class="field-input !bg-white !py-2 !px-3 !text-xs">
                                </div>
                            </div>

                            <div class="border-t border-slate-200/60 pt-4 mt-4">
                                <span class="text-[10px] font-black text-slate-500 block mb-2">🍱 باقات الوجبات
                                    المخصصة:</span>

                                <div class="space-y-3">
                                    <template x-for="(pkg, i) in packages" :key="pkg.uid">
                                        <div
                                            class="relative bg-white p-3 rounded-2xl border border-slate-200/60 shadow-sm space-y-2.5">
                                            <button type="button" @click="remove(i)"
                                                class="absolute -top-2 -left-2 w-5 h-5 bg-red-50 text-red-500 hover:bg-red-100 rounded-full flex items-center justify-center text-[10px] border border-red-100 transition-colors">✕</button>

                                            <div class="grid grid-cols-2 gap-2">
                                                <input type="text" :name="n(i, 'name')" x-model="pkg.name"
                                                    placeholder="الاسم" class="field-input !py-2 !px-2.5 !text-[11px]">
                                                <input type="number" :name="n(i, 'price')" x-model="pkg.price"
                                                    placeholder="ريال"
                                                    class="field-input !py-2 !px-2.5 !text-[11px] !text-emerald-600">
                                            </div>

                                            <div class="flex items-center gap-1">
                                                @foreach ([['breakfast', '🍳 فطور', 'emerald'], ['lunch', '🍽️ غداء', 'amber'], ['dinner', '🌙 عشاء', 'indigo']] as [$m, $ml, $mc])
                                                    <button type="button"
                                                        @click="pkg.meals.{{ $m }} = !pkg.meals.{{ $m }}"
                                                        :class="pkg.meals.{{ $m }} ?
                                                            'bg-{{ $mc }}-50 border-{{ $mc }}-200 text-{{ $mc }}-700' :
                                                            'bg-slate-50 border-slate-100 text-slate-400'"
                                                        class="flex-1 py-1.5 rounded-lg border text-[9px] font-bold transition-colors duration-200">{{ $ml }}</button>
                                                    <input type="hidden" :name="n(i, 'meals', '{{ $m }}')"
                                                        :value="pkg.meals.{{ $m }} ? 1 : 0">
                                                @endforeach
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <button type="button" @click="add()"
                                    class="w-full mt-3 py-2 border border-dashed border-slate-300 rounded-xl text-[10px] font-bold text-slate-500 hover:text-blue-600 hover:border-blue-300 transition-colors">
                                    ➕ إضافة باقة وجبات
                                </button>
                            </div>
                            {{-- متاحة للمشاركة (تظهر فقط لو فعّلت الغرف المشتركة) --}}
                            <div x-show="sharedRooms" x-transition class="mt-3">
                                <input type="hidden" name="room_options[{{ $key }}][shared]" value="0"
                                    :disabled="!sharedRooms">
                                <label
                                    class="flex items-center gap-2 px-3 py-2 rounded-xl border border-indigo-100 bg-indigo-50/50 cursor-pointer hover:bg-indigo-50 transition-colors">
                                    <input type="checkbox" name="room_options[{{ $key }}][shared]"
                                        value="1" :disabled="!sharedRooms"
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        {{ old("room_options.$key.shared", $roomOptions[$key]['shared'] ?? false) ? 'checked' : '' }}>
                                    <span class="text-[10px] font-black text-indigo-700">👥 متاحة للمشاركة</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- المفاتيح --}}
                @foreach ($toggles as [$name, $model, $icon, $tone, $title, $desc])
                    <div class="toggle-row">
                        <div class="flex items-center gap-3">
                            <span class="card-icon !w-8 !h-8 !text-sm {{ $tone }}">{{ $icon }}</span>
                            <div>
                                <label for="{{ $name }}"
                                    class="text-sm font-black text-slate-800 block cursor-pointer select-none">{{ $title }}</label>
                                <span
                                    class="text-[11px] text-slate-400 block font-medium mt-0.5 leading-relaxed">{{ $desc }}</span>
                            </div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="{{ $name }}" id="{{ $name }}" value="1"
                                x-model="{{ $model }}" class="sr-only">
                            <span class="switch-track"></span>
                        </label>
                    </div>
                @endforeach

                {{-- خصم السكن فقط --}}
                <div class="reveal" :class="{ open: accomOnly }">
                    <div class="reveal-inner" :inert="!accomOnly">
                        <div class="pt-5">
                            <label class="field-label !text-sm !text-slate-700">
                                قيمة الخصم المالي عند اختيار <span class="text-indigo-600">السكن فقط</span>
                                <span class="text-red-600 font-extrabold">(يُطبق على كل فرد داخل الغرفة)</span>
                            </label>
                            <input type="number" step="0.01" min="0" name="accommodation_only_discount"
                                placeholder="0.00"
                                value="{{ old('accommodation_only_discount', $trip->accommodation_only_discount ?? '0.00') }}"
                                class="field-input @error('accommodation_only_discount') is-invalid @enderror">
                            @error('accommodation_only_discount')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- سعر المقعد الإضافي --}}
                <div class="mt-6 pt-5 border-t border-dashed border-slate-200">
                    <label class="field-label">سعر المقعد الإضافي (للفرد)</label>
                    <input type="number" min="0" name="price" placeholder="ريال سعودي"
                        :required="sharedRooms" value="{{ old('price', $trip->price ?? 0) }}"
                        class="field-input @error('price') is-invalid @enderror">
                    @error('price')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </section>


            {{-- ═════════════ (ز) البنك + المركبة والكابتن ═════════════ --}}
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- بيانات التحويل البنكي --}}
                <div class="card lg:col-span-2" x-data="bankAccounts(@js($savedAccounts))">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-base font-black flex items-center gap-3 text-slate-800">
                            <span class="card-icon bg-slate-100">💳</span> بيانات التحويل البنكي
                        </h2>
                        <button type="button" @click="add()"
                            class="text-[10px] font-black bg-blue-50 text-blue-600 px-4 py-2 rounded-xl hover:bg-blue-600 hover:text-white transition-all duration-200">
                            + إضافة حساب آخر
                        </button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(row, i) in rows" :key="row.id">
                            <div
                                class="relative grid grid-cols-1 md:grid-cols-3 gap-3 p-4 bg-slate-50/60 rounded-2xl border border-slate-100">

                                <input type="text" :name="`bank_names[${i}]`" x-model="row.bank_name"
                                    placeholder="اسم البنك" required class="field-input !bg-white">
                                <input type="text" :name="`iban_numbers[${i}]`" x-model="row.iban"
                                    placeholder="الايبان SA..." required class="field-input !bg-white" dir="ltr">
                                <input type="text" :name="`account_number[${i}]`" x-model="row.account_number"
                                    placeholder="رقم الحساب" required class="field-input !bg-white" dir="ltr">

                                <label
                                    class="relative overflow-hidden p-4 bg-white border border-dashed border-blue-200 rounded-xl text-center cursor-pointer hover:bg-blue-50 transition-colors duration-200 bg-cover bg-center"
                                    :style="row.preview ? `background-image:url('${row.preview}')` : ''">
                                    <input type="hidden" :name="`old_bank_images[${i}]`" :value="row.qr_code">
                                    <input type="file" :name="`bank_images[${i}]`" accept="image/*" class="sr-only"
                                        @change="pick(row, $event)">
                                    <span class="relative z-10 text-[10px] font-black text-blue-600 pointer-events-none"
                                        :class="row.preview && 'bg-white/90 px-2 py-1 rounded-md shadow-sm'"
                                        x-text="row.preview ? '📎 تعديل صورة QR' : '📎 إرفاق صورة QR'"></span>
                                </label>

                                <button type="button" x-show="rows.length > 1" @click="remove(i)"
                                    class="absolute -top-2 -left-2 bg-red-500 hover:bg-red-600 text-white w-5 h-5 rounded-full text-[10px] font-black flex items-center justify-center shadow-md transition-colors">✕</button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- المركبة والكابتن --}}
                <div class="card lg:col-span-1 space-y-6">
                    <label
                        class="flex items-center gap-3 p-4 bg-amber-50 rounded-xl border border-amber-100 cursor-pointer">
                        <input type="checkbox" name="auto_split" value="1" class="rounded text-amber-600"
                            {{ $bool('auto_split', $trip->auto_split ?? false) ? 'checked' : '' }}>
                        <span class="text-[10px] font-black text-amber-800 uppercase">فصل تلقائي (نساء / رجال)</span>
                    </label>

                    <div>
                        <h2 class="text-base font-black mb-4 flex items-center gap-3 text-slate-800">
                            <span class="card-icon !w-8 !h-8 !text-sm bg-slate-100 text-slate-600"
                                x-text="transport === 'flight' ? '✈️' : '🚌'">🚌</span>
                            المركبة المخصصة
                        </h2>
                        <select name="vehicle_id" class="field-input">
                            <option value="" disabled
                                {{ old('vehicle_id', $trip->vehicle_id ?? null) ? '' : 'selected' }}>اختر المركبة من
                                الأسطول...</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}"
                                    {{ old('vehicle_id', $trip->vehicle_id ?? null) == $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->vehicle_model }} - ({{ $vehicle->plate_number }}) | سعة
                                    {{ $vehicle->total_seats }} مقعد
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <hr class="border-slate-100">

                    <div>
                        <h2 class="text-base font-black mb-4 flex items-center gap-3 text-slate-800">
                            <span class="card-icon !w-8 !h-8 !text-sm bg-slate-100 text-slate-600">🪪</span> كابتن الرحلة
                        </h2>
                        <select name="driver_id" class="field-input">
                            <option value="" disabled
                                {{ old('driver_id', $trip->driver_id ?? null) ? '' : 'selected' }}>اختر كابتن الرحلة...
                            </option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}"
                                    {{ old('driver_id', $trip->driver_id ?? null) == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->name }} - ({{ $driver->phone }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            {{-- ═════════════ (ز-٢) الباص الثاني الاحتياطي ═════════════ --}}
            <section class="card border-sky-100 bg-gradient-to-br from-white to-sky-50/30" x-show="isBus" x-cloak>
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="card-icon bg-sky-100 text-sky-700">🚌</span>
                        <div>
                            <label for="overflow_enabled"
                                class="text-sm font-black text-slate-800 block cursor-pointer select-none">
                                باص ثاني احتياطي (يُفتح تلقائياً عند اكتمال الباص الأول)
                            </label>
                            <span class="text-[11px] text-slate-400 block font-medium mt-0.5 leading-relaxed">
                                أول ما تمتلئ مقاعد هذي الرحلة، تظهر للعملاء رحلة ثانية بنفس البيانات والأسعار على باص آخر
                                بمقاعده وكابتنه.
                            </span>
                        </div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="overflow_enabled" id="overflow_enabled" value="1"
                            x-model="overflowEnabled" :disabled="!isBus || {{ $overflowActive ? 'true' : 'false' }}"
                            class="sr-only">
                        <span class="switch-track"></span>
                    </label>
                </div>

                @if ($overflowTrip)
                    <div
                        class="mt-5 p-4 rounded-2xl border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3
                {{ $overflowActive ? 'bg-emerald-50 border-emerald-100' : 'bg-amber-50 border-amber-100' }}">
                        <div class="text-xs font-bold {{ $overflowActive ? 'text-emerald-800' : 'text-amber-800' }}">
                            @if ($overflowActive)
                                ✅ الباص الثاني مفعّل (رحلة #{{ $overflowTrip->id }}) وظاهر للعملاء.
                                @if ($overflowHasBookings)
                                    <span class="block font-semibold mt-1">عليه حجوزات، فما يمكن إغلاقه. عدّله من قائمة
                                        الرحلات.</span>
                                @endif
                            @else
                                ⏳ الباص الثاني جاهز كاحتياطي (رحلة #{{ $overflowTrip->id }}) وغير ظاهر للعملاء.
                                <span class="block font-semibold mt-1">يتفعّل تلقائياً عند اكتمال الباص الأول، أو تفتحه
                                    الحين بنفسك.</span>
                            @endif
                        </div>

                        @if ($overflowActive)
                            @unless ($overflowHasBookings)
                                <button type="submit" form="overflow-close-form"
                                    onclick="return confirm('إغلاق الباص الثاني وإرجاعه لوضع احتياطي؟')"
                                    class="btn !py-2 !px-4 !text-xs bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shrink-0">
                                    إغلاق الباص الثاني
                                </button>
                            @endunless
                        @else
                            <button type="submit" form="overflow-open-form"
                                onclick="return confirm('فتح الباص الثاني الآن وإظهاره للعملاء؟ تأكد أنك حفظت تعديلات الرحلة أولاً.')"
                                class="btn !py-2 !px-4 !text-xs bg-sky-600 hover:bg-sky-700 text-white shrink-0">
                                🚌 فتح الباص الثاني الآن
                            </button>
                        @endif
                    </div>
                @elseif (!empty($trip->id))
                    <p class="mt-4 text-[11px] font-bold text-slate-400">
                        احفظ الرحلة أولاً ليتم تجهيز الباص الثاني، وبعدها يظهر هنا زر الفتح اليدوي.
                    </p>
                @endif

                <div class="reveal" :class="{ open: overflowOn }">
                    <div class="reveal-inner" :inert="!overflowOn">
                        <div class="mt-6 pt-6 border-t border-sky-100/80 grid grid-cols-1 md:grid-cols-3 gap-5">

                            <div>
                                <label class="field-label">نوع الباص الثاني وعدد مقاعده <span
                                        class="text-red-500">*</span></label>
                                <select name="overflow_total_seats" :required="overflowOn"
                                    :disabled="!overflowOn || {{ $overflowActive ? 'true' : 'false' }}"
                                    @change="overflowSeats = $event.target.value"
                                    class="field-input @error('overflow_total_seats') is-invalid @enderror">
                                    <option value="" disabled :selected="!overflowSeats">اختر النوع وعدد المقاعد...
                                    </option>
                                    <template x-for="o in seatOptions" :key="'ov' + o.value">
                                        <option :value="o.value" x-text="o.text"
                                            :selected="o.value == overflowSeats"></option>
                                    </template>
                                </select>
                                @error('overflow_total_seats')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="field-label">الباص الثاني من الأسطول</label>
                                <select name="overflow_vehicle_id"
                                    :disabled="!overflowOn || {{ $overflowActive ? 'true' : 'false' }}"
                                    class="field-input @error('overflow_vehicle_id') is-invalid @enderror">
                                    <option value="">يحدد لاحقاً</option>
                                    @foreach ($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}"
                                            {{ old('overflow_vehicle_id', $overflowTrip->vehicle_id ?? null) == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->vehicle_model }} - ({{ $vehicle->plate_number }}) | سعة
                                            {{ $vehicle->total_seats }} مقعد
                                        </option>
                                    @endforeach
                                </select>
                                @error('overflow_vehicle_id')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="field-label">كابتن الباص الثاني</label>
                                <select name="overflow_driver_id"
                                    :disabled="!overflowOn || {{ $overflowActive ? 'true' : 'false' }}"
                                    class="field-input @error('overflow_driver_id') is-invalid @enderror">
                                    <option value="">يحدد لاحقاً</option>
                                    @foreach ($drivers as $driver)
                                        <option value="{{ $driver->id }}"
                                            {{ old('overflow_driver_id', $overflowTrip->driver_id ?? null) == $driver->id ? 'selected' : '' }}>
                                            {{ $driver->name }} - ({{ $driver->phone }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('overflow_driver_id')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <p class="mt-4 text-[11px] text-slate-400 font-medium leading-relaxed">
                            ℹ️ الباص الثاني يظهر عندك كمسودة "احتياطية" لين يتفعّل. غرف الفندق للباص الثاني تبدأ بعدد صفر
                            (حتى ما تنباع نفس الغرف مرتين)، وتقدر تعدّلها بعد تفعيله.
                        </p>
                    </div>
                </div>
            </section>


            {{-- ═════════════ (ح) الشروط والملاحظات ═════════════ --}}
            <section class="card !p-6">
                <label class="field-label">شروط الرحلة أو الملاحظات الإضافية</label>
                <textarea name="notes" rows="8" placeholder="اكتب شروط إلغاء الحجز، أو متطلبات إضافية هنا..."
                    class="field-input !rounded-[1.25rem] !p-5 font-medium leading-relaxed">{{ old('notes', $trip->notes ?? $defaultNotes) }}</textarea>
            </section>


            {{-- ═════════════ (ط) أزرار الحفظ (شريط ثابت أسفل الصفحة) ═════════════ --}}
            <section class="sticky bottom-3 z-20">
                <div
                    class="flex flex-col sm:flex-row justify-end items-center gap-3 p-3 rounded-[1.5rem] bg-white/85 backdrop-blur border border-slate-200/70 shadow-lg shadow-slate-200/60">

                    <button type="submit" name="action" value="draft"
                        :class="submitting && 'opacity-60 pointer-events-none'"
                        class="btn w-full sm:w-auto justify-center bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-sm">
                        <span class="text-base">📁</span>
                        <span>{{ $trip->exists ? 'تحديث وحفظ في المسودة' : 'حفظ كمسودة جديدة' }}</span>
                    </button>

                    <button type="submit" name="action" value="publish"
                        :class="submitting && 'opacity-60 pointer-events-none'"
                        class="btn w-full sm:w-auto justify-center !px-8 bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-100 hover:shadow-indigo-200">
                        <span class="text-base" x-text="submitting ? '⏳' : '🚀'">🚀</span>
                        <span>{{ $trip->exists ? 'تحديث ونشر الرحلة الآن' : 'نشر الرحلة للمسافرين فوراً' }}</span>
                    </button>
                </div>
            </section>

        </form>
        @if (!empty($trip->id) && $overflowTrip)
            <form id="overflow-open-form" method="POST" action="{{ route('agency.trips.overflow.open', $trip->id) }}"
                hidden>@csrf</form>
            <form id="overflow-close-form" method="POST"
                action="{{ route('agency.trips.overflow.close', $trip->id) }}" hidden>@csrf</form>
        @endif
    </div>


    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  3) التنسيقات                                                 ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    <style type="text/tailwindcss">
        @layer components {
            .card {
                @apply bg-white rounded-[2rem] border border-slate-100 shadow-sm p-6 sm:p-8;
            }

            .card-title {
                @apply text-base font-black text-slate-800 flex items-center gap-3 mb-6;
            }

            .card-icon {
                @apply w-9 h-9 rounded-xl flex items-center justify-center text-base shrink-0;
            }

            .field-label {
                @apply block text-[11px] font-black text-slate-500 mb-2;
            }

            .field-error {
                @apply text-red-500 text-xs mt-1.5 font-bold;
            }

            .field-input {
                @apply w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-sm font-bold text-slate-800 placeholder:text-slate-400 placeholder:font-semibold outline-none transition-all duration-200 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10;
            }

            .field-input.is-invalid {
                @apply border-red-500 focus:border-red-500 focus:ring-red-500/10;
            }

            .field-input[readonly] {
                @apply bg-slate-100 text-slate-500 cursor-default focus:ring-0 focus:border-slate-200;
            }

            .btn {
                @apply inline-flex items-center gap-2 px-5 py-3 rounded-2xl font-bold text-sm transition-all duration-200 active:scale-95 cursor-pointer;
            }

            .toggle-row {
                @apply flex items-center justify-between gap-4 mt-6 pt-6 border-t border-slate-100;
            }
        }
    </style>

    <style>
        /* ===== أزرار الاختيار (radio / checkbox) ===== */
        .opt {
            position: relative;
            display: block;
            cursor: pointer;
            --c: #2563eb;
        }

        .opt.acc-indigo {
            --c: #4f46e5;
        }

        .opt.acc-purple {
            --c: #9333ea;
        }

        .opt-pill {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            min-height: 3rem;
            padding: .5rem .75rem;
            border-radius: .75rem;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            font-size: .75rem;
            font-weight: 900;
            text-align: center;
            transition: all .25s cubic-bezier(.4, 0, .2, 1);
        }

        .opt-pill.sm {
            min-height: 2.75rem;
            font-size: 10px;
            padding: .75rem;
        }

        .opt:hover .opt-pill {
            border-color: color-mix(in srgb, var(--c) 45%, #e2e8f0);
        }

        .opt-input:checked+.opt-pill {
            background: var(--c);
            border-color: var(--c);
            color: #fff;
            box-shadow: 0 8px 18px -8px var(--c);
        }

        .opt-input:focus-visible+.opt-pill {
            outline: 2px solid var(--c);
            outline-offset: 2px;
        }

        /* ===== مفتاح التشغيل ===== */
        .switch {
            position: relative;
            display: inline-flex;
            flex-shrink: 0;
            cursor: pointer;
            --sw: #10b981;
        }

        .switch.acc-amber {
            --sw: #f59e0b;
        }

        .switch-track {
            position: relative;
            width: 2.75rem;
            height: 1.5rem;
            border-radius: 9999px;
            background: #e2e8f0;
            transition: background .25s ease;
        }

        .switch-track::after {
            content: '';
            position: absolute;
            top: 2px;
            inset-inline-start: 2px;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 9999px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .25);
            transition: transform .3s cubic-bezier(.4, 0, .2, 1);
        }

        .switch input:checked+.switch-track {
            background: var(--sw);
        }

        .switch input:checked+.switch-track::after {
            transform: translateX(-1.25rem);
        }

        .switch input:focus-visible+.switch-track {
            outline: 2px solid var(--sw);
            outline-offset: 2px;
        }

        /* ===== الانزلاق السلس (نفس أسلوب القائمة الجانبية) ===== */
        .reveal {
            display: grid;
            grid-template-rows: 0fr;
            opacity: 0;
            transition: grid-template-rows .35s cubic-bezier(.4, 0, .2, 1), opacity .3s ease;
        }

        .reveal.open {
            grid-template-rows: 1fr;
            opacity: 1;
        }

        .reveal-inner {
            overflow: hidden;
            min-height: 0;
            padding: 3px;
            margin: -3px;
        }

        /* ===== حركات x-transition ===== */
        .pop-active {
            transition: opacity .2s ease, transform .25s cubic-bezier(.4, 0, .2, 1);
        }

        .pop-from {
            opacity: 0;
            transform: translateY(-6px) scale(.97);
        }

        .pop-to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .view-active {
            transition: opacity .25s ease, transform .25s ease;
        }

        .view-from {
            opacity: 0;
            transform: translateY(4px);
        }

        .view-to {
            opacity: 1;
            transform: translateY(0);
        }

        /* ===== ظهور الأقسام تباعاً (backwards: حتى لا يبقى transform يكسر القوائم المنسدلة) ===== */
        @keyframes riseIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
        }

        .rise {
            animation: riseIn .5s cubic-bezier(.16, 1, .3, 1) backwards;
        }

        .stagger>section {
            animation: riseIn .5s cubic-bezier(.16, 1, .3, 1) backwards;
        }

        .stagger>section:nth-of-type(2) {
            animation-delay: .04s;
        }

        .stagger>section:nth-of-type(3) {
            animation-delay: .08s;
        }

        .stagger>section:nth-of-type(4) {
            animation-delay: .12s;
        }

        .stagger>section:nth-of-type(5) {
            animation-delay: .16s;
        }

        .stagger>section:nth-of-type(6) {
            animation-delay: .20s;
        }

        .stagger>section:nth-of-type(n+7) {
            animation-delay: .24s;
        }

        /* ===== شريط التمرير ===== */
        .soft-scroll::-webkit-scrollbar {
            width: 5px;
        }

        .soft-scroll::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .soft-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .soft-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>


    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  4) منطق Alpine.js                                            ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    <script>
        const SAUDI_CITIES = @js($allSaudiCities);

        const VEHICLE_OPTIONS = {
            bus: [{
                    value: '49',
                    text: 'حافلة كبيرة (49 مقعد)'
                },
                {
                    value: '30',
                    text: 'حافلة متوسطة (30 مقعد)'
                },
                {
                    value: '15',
                    text: 'ميني باص / فان (15 مقعد)'
                },
                {
                    value: '7',
                    text: 'سيارة عائلية SUV (7 مقاعد)'
                },
                {
                    value: '4',
                    text: 'سيارة سيدان (4 مقاعد)'
                },
            ],
            flight: [{
                    value: '180',
                    text: 'طائرة ركاب ضخمة (180 مقعد)'
                },
                {
                    value: '120',
                    text: 'طائرة تجارية متوسطة (120 مقعد)'
                },
                {
                    value: '70',
                    text: 'طائرة إقليمية (70 مقعد)'
                },
                {
                    value: '12',
                    text: 'طائرة خاصة VIP (12 مقعد)'
                },
                {
                    value: '6',
                    text: 'طائرة خاصة صغيرة (6 مقاعد)'
                },
            ],
        };

        // رقم اليوم (UTC) من قيمة datetime-local، لحساب الليالي بتواريخ التقويم
        const dayIndex = (v) => {
            if (!v) return null;
            const [y, m, d] = v.slice(0, 10).split('-').map(Number);
            return Date.UTC(y, m - 1, d) / 86400000;
        };

        // معرّف فريد للعناصر المتكررة
        let uidCounter = 0;
        const uid = () => ++uidCounter;

        // يبني كائن x-transition لاستخدامه عبر x-bind
        const makeFx = (p, both = true) => ({
            'x-transition:enter': `${p}-active`,
            'x-transition:enter-start': `${p}-from`,
            'x-transition:enter-end': `${p}-to`,
            ...(both && {
                'x-transition:leave': `${p}-active`,
                'x-transition:leave-start': `${p}-to`,
                'x-transition:leave-end': `${p}-from`,
            }),
        });

        document.addEventListener('alpine:init', () => {

            // ───────────── النموذج الرئيسي ─────────────
            Alpine.data('tripForm', (state) => ({
                ...state,
                submitting: false,
                popFx: makeFx('pop'),
                viewFx: makeFx('view'),

                init() {
                    // عند تغيير وسيلة النقل: نصفّر المقاعد إن لم تكن ضمن الخيارات الجديدة
                    this.$watch('transport', () => {
                        if (!this.seatOptions.some(o => o.value == this.seats)) this.seats = '';
                        if (!this.seatOptions.some(o => o.value == this.overflowSeats)) this
                            .overflowSeats = '';
                    });
                    // الذهاب فقط: نمسح تاريخ العودة
                    this.$watch('tripType', (t) => {
                        if (t !== 'round_trip') this.returnAt = '';
                    });
                },

                get isRound() {
                    return this.tripType === 'round_trip';
                },


                get isBus() {
                    return this.transport === 'bus';
                },
                get overflowOn() {
                    return this.isBus && this.overflowEnabled;
                },

                // عدد الليالي = فرق التواريخ، والأيام = الليالي + 1
                get nights() {
                    if (!this.isRound) return 0;
                    const a = dayIndex(this.departureAt),
                        b = dayIndex(this.returnAt);
                    return (a === null || b === null || b < a) ? 0 : b - a;
                },
                get days() {
                    return this.nights + 1;
                },

                get invalidReturn() {
                    return this.isRound && this.departureAt && this.returnAt && this.returnAt <=
                        this.departureAt;
                },

                get seatOptions() {
                    return VEHICLE_OPTIONS[this.transport] || VEHICLE_OPTIONS.bus;
                },
                get mealCount() {
                    return [this.hasBreakfast, this.hasLunch, this.hasDinner].filter(Boolean)
                        .length;
                },

                // قفل مزدوج للإرسال (setTimeout حتى لا يضيع اسم الزر المضغوط)
                lock() {
                    setTimeout(() => this.submitting = true, 0);
                },
            }));

            // ───────────── قائمة المدن ─────────────
            Alpine.data('citySelect', (selected) => ({
                selected,
                open: false,
                search: '',
                cities: SAUDI_CITIES,
                get filtered() {
                    const q = this.search.trim();
                    return q ? this.cities.filter(c => c.includes(q)) : this.cities;
                },
                toggle() {
                    this.open = !this.open;
                    if (this.open) this.$nextTick(() => this.$refs.q?.focus());
                },
                pick(city) {
                    this.selected = city;
                    this.open = false;
                    this.search = '';
                },
            }));

            // ───────────── صورة الغلاف ─────────────
            Alpine.data('coverImage', (url) => ({
                preview: url,
                changed: false,
                pick(e) {
                    const file = e.target.files?.[0];
                    if (!file) return;
                    this.preview = URL.createObjectURL(file);
                    this.changed = true;
                },
            }));

            // ───────────── المميزات المخصصة ─────────────
            Alpine.data('featuresPicker', (items) => ({
                items,
                draft: '',
                add() {
                    const v = this.draft.trim();
                    if (v && !this.items.includes(v)) this.items.push(v);
                    this.draft = '';
                },
            }));

            // ───────────── الخدمات الإضافية ─────────────
            Alpine.data('extraServices', (items) => ({
                services: items.map(s => ({
                    uid: uid(),
                    name: s.name ?? '',
                    price: Number(s.price) || 0,
                    description: s.description ?? '',
                })),
                draft: {
                    name: '',
                    price: '',
                    description: ''
                },
                error: '',
                add() {
                    const name = this.draft.name.trim();
                    if (!name) {
                        this.error = 'يرجى إدخال اسم الخدمة';
                        return;
                    }
                    this.error = '';
                    this.services.push({
                        uid: uid(),
                        name,
                        price: this.draft.price !== '' ? (parseFloat(this.draft.price) || 0) :
                            0,
                        description: this.draft.description.trim(),
                    });
                    this.draft = {
                        name: '',
                        price: '',
                        description: ''
                    };
                },
                remove(i) {
                    this.services.splice(i, 1);
                },
            }));

            // ───────────── باقات وجبات كل نوع غرفة ─────────────
            Alpine.data('roomPackages', (type, items) => {
                const on = (v) => !!Number(v);
                const make = (p = {}) => ({
                    uid: uid(),
                    name: p.name ?? '',
                    price: p.price ?? 0,
                    meals: {
                        breakfast: on(p.meals?.breakfast),
                        lunch: on(p.meals?.lunch),
                        dinner: on(p.meals?.dinner)
                    },
                });
                return {
                    packages: (items || []).map(make),
                    add() {
                        this.packages.push(make());
                    },
                    remove(i) {
                        this.packages.splice(i, 1);
                    },
                    // اسم الحقل: room_options[type][packages][i][a][b]
                    n(i, ...parts) {
                        return `room_options[${type}][packages][${i}]` + parts.map(p => `[${p}]`).join('');
                    },
                };
            });

            // ───────────── الحسابات البنكية ─────────────
            Alpine.data('bankAccounts', (items) => {
                const make = (r = {}) => ({
                    id: uid(),
                    bank_name: r.bank_name ?? '',
                    iban: r.iban ?? '',
                    account_number: r.account_number ?? '',
                    qr_code: r.qr_code ?? '',
                    preview: r.qr_url ?? '',
                });
                return {
                    rows: items.map(make),
                    add() {
                        this.rows.push(make());
                    },
                    remove(i) {
                        if (this.rows.length > 1) this.rows.splice(i, 1);
                    },
                    pick(row, e) {
                        const file = e.target.files?.[0];
                        if (file) row.preview = URL.createObjectURL(file);
                    },
                };
            });
        });
    </script>

@endsection
