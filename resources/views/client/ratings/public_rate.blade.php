<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقييم الخدمات - {{ $agency->name ?? 'الوكالة' }}</title>
    
    <!-- خط Tajawal لتصميم أكثر احترافية -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS & Alpine.js -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Tajawal', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex items-center justify-center p-4 sm:p-6">

    <div class="w-full max-w-lg bg-white rounded-[2.5rem] p-6 sm:p-8 shadow-xl border border-slate-200/60 my-auto"
        x-data="{
            ratings: { staff: 0, organization: 0, price_value: 0, transport: 0 },
            hovers: { staff: 0, organization: 0, price_value: 0, transport: 0 }
        }">

        {{-- الهيدر ورسالة الترحيب + صورة الوكالة --}}
        <div class="text-center mb-8">
            <div class="relative w-20 h-20 mx-auto mb-4">
                @if (!empty($agency->logo))
                    <img src="{{ asset('storage/' . $agency->logo) }}" 
                         alt="{{ $agency->name }}" 
                         class="w-20 h-20 rounded-3xl object-cover shadow-md border-2 border-white ring-4 ring-slate-100">
                @else
                    <div class="w-20 h-20 bg-gradient-to-br from-indigo-500 to-slate-900 text-white font-black text-2xl flex items-center justify-center rounded-3xl shadow-md border-2 border-white ring-4 ring-slate-100">
                        {{ mb_substr($agency->name ?? 'ع', 0, 1) }}
                    </div>
                @endif
                <span class="absolute -bottom-1 -left-1 bg-amber-400 text-slate-900 text-xs px-2 py-0.5 rounded-full font-black shadow-sm border border-white">
                    ★ 5.0
                </span>
            </div>

            <h1 class="text-xl font-black text-slate-900">{{ $agency->name ?? 'وكالة السفر' }}</h1>
            <p class="text-xs font-bold text-slate-500 mt-1">
                يسعدنا تقييم تجربتك معنا لتطوير خدماتنا القادمة ✨
            </p>
        </div>

        {{-- تنبيه النجاح عبر نافذة منبثة تفاعلية --}}
        @if (session('success'))
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        title: 'تم بنجاح! ✨',
                        text: "{{ session('success') }}",
                        icon: 'success',
                        confirmButtonText: 'رائع 👍',
                        confirmButtonColor: '#0f172a',
                        customClass: {
                            popup: 'rounded-[2.5rem] p-6 font-sans',
                            confirmButton: 'rounded-2xl px-6 py-3 font-black text-xs'
                        }
                    });
                });
            </script>
        @endif

        <form action="{{ route('client.rate.store', $agency->id) }}" method="POST" class="space-y-3.5">
            @csrf

            {{-- 🟢 التقاط رقم الرحلة القادم من الرابط --}}
            <input type="hidden" name="trip_id" value="{{ request('trip_id') }}">

            {{-- 1. حافلة النقل --}}
            <div class="flex items-center justify-between bg-slate-50 hover:bg-slate-100/80 p-4 rounded-2xl border border-slate-100 transition-all">
                <span class="text-xs font-black text-slate-700 flex items-center gap-2">
                    <span class="p-1.5 bg-indigo-50 text-indigo-600 rounded-xl text-base">🚌</span>
                    راحة وجودة الحافلة
                </span>
                <div class="flex gap-1" dir="ltr">
                    <template x-for="i in 5">
                        <button type="button" 
                            @mouseenter="hovers.transport = i" 
                            @mouseleave="hovers.transport = 0"
                            @click="ratings.transport = i"
                            class="text-2xl transition-transform active:scale-125 focus:outline-none"
                            :class="(hovers.transport >= i || ratings.transport >= i) ? 'text-amber-400 drop-shadow-sm' : 'text-slate-200'">★</button>
                    </template>
                </div>
                <input type="hidden" name="transport_rating" :value="ratings.transport" required>
            </div>

            {{-- 2. الكادر والمشرفين --}}
            <div class="flex items-center justify-between bg-slate-50 hover:bg-slate-100/80 p-4 rounded-2xl border border-slate-100 transition-all">
                <span class="text-xs font-black text-slate-700 flex items-center gap-2">
                    <span class="p-1.5 bg-amber-50 text-amber-600 rounded-xl text-base">👨‍💼</span>
                    تعامل المشرفين
                </span>
                <div class="flex gap-1" dir="ltr">
                    <template x-for="i in 5">
                        <button type="button" 
                            @mouseenter="hovers.staff = i" 
                            @mouseleave="hovers.staff = 0"
                            @click="ratings.staff = i"
                            class="text-2xl transition-transform active:scale-125 focus:outline-none"
                            :class="(hovers.staff >= i || ratings.staff >= i) ? 'text-amber-400 drop-shadow-sm' : 'text-slate-200'">★</button>
                    </template>
                </div>
                <input type="hidden" name="staff_rating" :value="ratings.staff" required>
            </div>

            {{-- 3. التنظيم والوقت --}}
            <div class="flex items-center justify-between bg-slate-50 hover:bg-slate-100/80 p-4 rounded-2xl border border-slate-100 transition-all">
                <span class="text-xs font-black text-slate-700 flex items-center gap-2">
                    <span class="p-1.5 bg-emerald-50 text-emerald-600 rounded-xl text-base">📅</span>
                    التنظيم والمواعيد
                </span>
                <div class="flex gap-1" dir="ltr">
                    <template x-for="i in 5">
                        <button type="button" 
                            @mouseenter="hovers.organization = i"
                            @mouseleave="hovers.organization = 0" 
                            @click="ratings.organization = i"
                            class="text-2xl transition-transform active:scale-125 focus:outline-none"
                            :class="(hovers.organization >= i || ratings.organization >= i) ? 'text-amber-400 drop-shadow-sm' : 'text-slate-200'">★</button>
                    </template>
                </div>
                <input type="hidden" name="organization_rating" :value="ratings.organization" required>
            </div>

            {{-- 4. القيمة مقابل السعر --}}
            <div class="flex items-center justify-between bg-slate-50 hover:bg-slate-100/80 p-4 rounded-2xl border border-slate-100 transition-all">
                <span class="text-xs font-black text-slate-700 flex items-center gap-2">
                    <span class="p-1.5 bg-rose-50 text-rose-600 rounded-xl text-base">💰</span>
                    الخدمة مقابل السعر
                </span>
                <div class="flex gap-1" dir="ltr">
                    <template x-for="i in 5">
                        <button type="button" 
                            @mouseenter="hovers.price_value = i" 
                            @mouseleave="hovers.price_value = 0"
                            @click="ratings.price_value = i"
                            class="text-2xl transition-transform active:scale-125 focus:outline-none"
                            :class="(hovers.price_value >= i || ratings.price_value >= i) ? 'text-amber-400 drop-shadow-sm' : 'text-slate-200'">★</button>
                    </template>
                </div>
                <input type="hidden" name="price_value_rating" :value="ratings.price_value" required>
            </div>

            {{-- الملاحظات --}}
            <div class="pt-2">
                <label class="block text-xs font-bold text-slate-500 mr-1 mb-2">ملاحظات أو اقتراحات (اختياري)</label>
                <textarea rows="3" name="comment" placeholder="اكتب ملاحظاتك بحرية لتحسين تجربتك القادمة..."
                    class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-4 text-xs font-medium text-right text-slate-800 placeholder-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-50 outline-none transition-all resize-none shadow-inner"></textarea>
            </div>

            <button type="submit"
                class="w-full py-4 bg-slate-900 text-white hover:bg-indigo-600 rounded-2xl font-black text-xs shadow-xl shadow-slate-200 hover:shadow-indigo-200 transition-all transform active:scale-[0.98] mt-4">
                إرسال التقييم ✨
            </button>
        </form>
    </div>

</body>

</html>