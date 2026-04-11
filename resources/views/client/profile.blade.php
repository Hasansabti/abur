@extends('layouts.client')

@section('content')
    <div class="container mx-auto px-4 py-10 max-w-6xl animate-fade-in" x-data="{
        tab: 'bookings',
        openRating: false,
        selectedTrip: {},
        idUploaded: false,
        companions: [],
        rating: 0,
        hover: 0
    }">

        {{-- 1. الهيدر --}}
        <div
            class="bg-slate-950 rounded-[3.5rem] p-8 md:p-12 mb-10 text-white relative overflow-hidden shadow-2xl border border-slate-900">
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-indigo-600 rounded-full blur-[120px] opacity-20"></div>
            <div class="absolute left-10 bottom-0 opacity-5 select-none pointer-events-none">
                <h1 class="text-[120px] font-black italic tracking-tighter">OBOUR</h1>
            </div>

            <div class="relative z-10 flex flex-col md:flex-row items-center gap-8">
                <div class="relative group">
                    <img src="{{ $user->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=4f46e5&color=fff' }}"
                        class="w-32 h-32 rounded-[2.5rem] border-4 border-slate-800 shadow-2xl object-cover transform group-hover:scale-105 transition-all duration-500"
                        alt="Profile">
                    <div class="absolute -bottom-2 -right-2 bg-green-500 w-6 h-6 rounded-full border-4 border-slate-950">
                    </div>
                    <label
                        class="absolute inset-0 flex items-center justify-center bg-black/60 rounded-[2.5rem] opacity-0 group-hover:opacity-100 transition-all cursor-pointer text-[10px] font-black italic backdrop-blur-sm">
                        تغيير 📷
                        <input type="file" class="hidden">
                    </label>
                </div>

                <div class="text-center md:text-right flex-grow">
                    <div class="flex flex-col md:flex-row items-center gap-3 mb-2">
                        <h1 class="text-4xl font-black tracking-tighter italic">{{ $user->name }}</h1>
                        @if ($user->is_verified)
                            <span
                                class="bg-indigo-500/20 text-indigo-400 text-[9px] px-3 py-1 rounded-full font-black border border-indigo-500/30 italic uppercase">Verified
                                Traveler ✅</span>
                        @endif
                    </div>

                    <div class="flex flex-wrap justify-center md:justify-start gap-4 items-center opacity-70">
                        <span class="font-bold text-xs italic tracking-widest">📞 {{ $user->phone }}</span>
                        <span class="hidden md:block w-1.5 h-1.5 bg-slate-700 rounded-full"></span>
                        <span class="font-bold text-xs italic tracking-widest">📧 {{ $user->email }}</span>
                    </div>

                    <div class="mt-8 flex flex-wrap justify-center md:justify-start gap-3">
                        <button @click="tab = 'bookings'"
                            :class="tab === 'bookings' ? 'bg-indigo-600 shadow-indigo-500/20' : 'bg-slate-800'"
                            class="px-8 py-3 rounded-2xl text-[10px] font-black transition-all uppercase tracking-widest shadow-xl border border-white/5">رحلاتي</button>
                        <button @click="tab = 'settings'"
                            :class="tab === 'settings' ? 'bg-indigo-600 shadow-indigo-500/20' : 'bg-slate-800'"
                            class="px-8 py-3 rounded-2xl text-[10px] font-black transition-all uppercase tracking-widest shadow-xl border border-white/5">الإعدادات</button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 w-full md:w-auto">
                    <div
                        class="bg-white/5 backdrop-blur-xl p-6 rounded-[2.5rem] border border-white/10 text-center group hover:border-indigo-500/50 transition-colors">
                        <p class="text-3xl font-black text-white group-hover:scale-110 transition-transform">
                            {{ $user->trips_count }}</p>
                        <p class="text-[9px] text-slate-500 font-black uppercase mt-1 tracking-widest">Trip</p>
                    </div>
                    <div
                        class="bg-indigo-600/10 backdrop-blur-xl p-6 rounded-[2.5rem] border border-indigo-500/20 text-center relative overflow-hidden group">
                        <p class="text-3xl font-black text-indigo-500">{{ $user->points }}</p>
                        <p class="text-[9px] text-indigo-400 font-black uppercase mt-1 tracking-widest">Points</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">

            {{-- المحتوى الرئيسي --}}
            <div class="lg:col-span-8">

                {{-- التبويب 1: سجل الحجوزات --}}
                <div x-show="tab === 'bookings'" class="space-y-6 animate-fade-in" x-transition>
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-black text-slate-900 italic">سجل الحجوزات 🎫</h2>
                        <div class="bg-slate-100 p-1 rounded-xl flex gap-1">
                            <button
                                class="px-4 py-1.5 bg-white shadow-sm rounded-lg text-[10px] font-black italic">الكل</button>
                            <button
                                class="px-4 py-1.5 text-slate-400 rounded-lg text-[10px] font-black italic">القادمة</button>
                        </div>
                    </div>

                    @forelse($bookings as $booking)
                        <div
                            class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm hover:shadow-2xl hover:-translate-y-1 transition-all duration-500 flex flex-col md:flex-row items-center justify-between gap-6 group">
                            <div class="flex items-center gap-6 w-full md:w-auto">
                                <div
                                    class="w-16 h-16 {{ $booking->type == 'bus' ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600' }} rounded-[1.8rem] flex items-center justify-center text-2xl group-hover:scale-110 transition-transform duration-500 shadow-inner">
                                    {{ $booking->type == 'bus' ? '🚌' : '✈️' }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span
                                            class="text-[9px] text-slate-400 font-black uppercase tracking-widest italic">#{{ $booking->id }}</span>
                                        <span class="w-1 h-1 bg-slate-200 rounded-full"></span>
                                        <span
                                            class="text-[8px] font-black uppercase px-2 py-0.5 rounded-md {{ $booking->status == 'completed' ? 'bg-green-50 text-green-600' : 'bg-blue-50 text-blue-600' }}">
                                            {{ $booking->status == 'completed' ? 'تمت الرحلة' : 'قادمة' }}
                                        </span>
                                    </div>
                                    <h3 class="text-xl font-black text-slate-900">{{ $booking->route }}</h3>
                                    <p class="text-xs text-slate-500 font-bold mt-1 opacity-70">📅 {{ $booking->date }} | ⏰
                                        {{ $booking->time }}</p>
                                </div>
                            </div>

                            <div
                                class="flex items-center gap-6 w-full md:w-auto justify-between md:justify-end border-t md:border-t-0 pt-4 md:pt-0">
                                <div class="text-right">
                                    <p class="text-2xl font-black text-slate-900 tracking-tighter">{{ $booking->price }}
                                        <small class="text-[10px] text-slate-400 italic font-bold">ر.س</small></p>
                                    @if ($booking->status == 'completed')
                                        <button
                                            @click="openRating = true; selectedTrip = {id: {{ $booking->id }}, route: '{{ $booking->route }}'}"
                                            class="text-[9px] font-black text-indigo-600 hover:text-slate-950 transition-colors flex items-center gap-1 mt-1 uppercase italic tracking-widest">
                                            ⭐ قيم الآن
                                        </button>
                                    @endif
                                </div>
                                <a href="{{ route('client.success', ['id' => $booking->id]) }}"
                                    class="w-14 h-14 bg-slate-50 hover:bg-indigo-600 hover:text-white rounded-2xl flex items-center justify-center transition-all shadow-sm group-hover:rotate-6">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-24 bg-slate-50 rounded-[3.5rem] border-2 border-dashed border-slate-200">
                            <div class="text-4xl mb-4">🌍</div>
                            <p class="text-slate-400 font-black text-xs italic uppercase tracking-[0.3em]">اكتشف وجهتك
                                القادمة مع عبور</p>
                            <a href="/"
                                class="inline-block mt-6 px-8 py-3 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest italic shadow-lg shadow-indigo-100">احجز
                                رحلتك الأولى</a>
                        </div>
                    @endforelse
                </div>

                {{-- التبويب 2: إعدادات الحساب --}}
                <div x-show="tab === 'settings'" class="animate-fade-in space-y-8" x-transition>
                    <div class="bg-white rounded-[3.5rem] p-10 shadow-sm border border-slate-100">
                        <h2 class="text-2xl font-black text-slate-900 italic mb-10">إدارة الحساب 👤</h2>

                        <form action="#" method="POST" class="space-y-8">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="group">
                                    <label
                                        class="block text-[10px] font-black text-slate-400 uppercase mb-3 mr-2 italic tracking-widest transition-colors group-focus-within:text-indigo-600">الاسم
                                        الكامل</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 opacity-30">👤</span>
                                        <input type="text" name="name" value="{{ $user->name }}"
                                            class="w-full bg-slate-50 border-2 border-transparent rounded-2xl p-4 pl-12 text-sm font-black outline-none focus:bg-white focus:border-indigo-100 transition-all">
                                    </div>
                                </div>
                                <div class="group">
                                    <label
                                        class="block text-[10px] font-black text-slate-400 uppercase mb-3 mr-2 italic tracking-widest">تاريخ
                                        الميلاد</label>
                                    <input type="date" name="birthday"
                                        class="w-full bg-slate-50 border-2 border-transparent rounded-2xl p-4 text-sm font-black outline-none focus:bg-white focus:border-indigo-100 transition-all">
                                </div>
                                <div class="group">
                                    <label
                                        class="block text-[10px] font-black text-slate-400 uppercase mb-3 mr-2 italic tracking-widest">الجنس</label>
                                    <select
                                        class="w-full bg-slate-50 border-2 border-transparent rounded-2xl p-4 text-sm font-black outline-none focus:bg-white focus:border-indigo-100 appearance-none">
                                        <option value="male">ذكر</option>
                                        <option value="female">أنثى</option>
                                    </select>
                                </div>
                                <div class="group">
                                    <label
                                        class="block text-[10px] font-black text-slate-400 uppercase mb-3 mr-2 italic tracking-widest">المدينة</label>
                                    <input type="text" name="city" placeholder="الرياض"
                                        class="w-full bg-slate-50 border-2 border-transparent rounded-2xl p-4 text-sm font-black outline-none focus:bg-white focus:border-indigo-100 transition-all">
                                </div>
                            </div>

                            {{-- قسم المرافقين  --}}
                            <div class="pt-10 border-t border-slate-50">
                                <div class="flex justify-between items-center mb-8">
                                    <div>
                                        <h4 class="text-sm font-black text-slate-900 uppercase italic tracking-tighter">
                                            المرافقين الدائمين 👥</h4>
                                        <p class="text-[9px] text-slate-400 font-bold italic">أضف أفراد عائلتك لسرعة الحجز
                                            مستقبلاً</p>
                                    </div>
                                    <button type="button" @click="companions.push({name: ''})"
                                        class="text-[9px] font-black text-indigo-600 bg-indigo-50 px-5 py-2.5 rounded-xl hover:bg-indigo-600 hover:text-white transition-all uppercase italic tracking-widest shadow-sm">+
                                        إضافة</button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <template x-for="(comp, index) in companions" :key="index">
                                        <div class="flex gap-2 animate-slide-up group">
                                            <div class="relative flex-grow">
                                                <span
                                                    class="absolute left-4 top-1/2 -translate-y-1/2 text-xs opacity-40">👤</span>
                                                <input type="text" x-model="comp.name"
                                                    placeholder="اسم المرافق الكامل"
                                                    class="w-full bg-slate-50 border-none rounded-2xl p-4 pl-10 text-xs font-black outline-none focus:ring-2 focus:ring-indigo-100 transition-all">
                                            </div>
                                            <button @click="companions.splice(index, 1)" type="button"
                                                class="w-12 h-12 flex items-center justify-center text-red-400 hover:bg-red-50 rounded-2xl transition-all font-black">✕</button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <button type="submit"
                                class="w-full py-6 bg-slate-950 text-white rounded-[2.2rem] font-black text-sm shadow-2xl hover:bg-indigo-600 transition-all active:scale-95 mt-10 italic tracking-widest uppercase border-b-4 border-black/20">
                                تحديث البيانات الشخصية ✅
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- 3. القسم الجانبي --}}
            <div class="lg:col-span-4 space-y-8">
                {{-- بطاقة توثيق الهوية --}}
                <div
                    class="bg-white rounded-[3.5rem] p-10 border border-slate-100 shadow-sm text-center relative overflow-hidden group">
                    <div
                        class="absolute -top-10 -left-10 w-32 h-32 bg-indigo-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700">
                    </div>
                    <div class="relative">
                        <div
                            class="w-20 h-20 bg-indigo-50 text-indigo-600 rounded-[2.5rem] flex items-center justify-center text-3xl mx-auto mb-6 shadow-inner transform group-hover:rotate-12 transition-transform duration-500 italic">
                            🆔</div>
                        <h3 class="text-xl font-black text-slate-900 mb-2 italic tracking-tighter">توثيق الهوية</h3>
                        <p class="text-[10px] text-slate-400 font-bold leading-relaxed mb-8 italic">ارفع صورة هويتك
                            لاستخراج بياناتك تلقائياً وضمان رحلة آمنة وموثقة.</p>

                        <label class="cursor-pointer block group/btn">
                            <span
                                class="block py-5 px-6 bg-indigo-600 text-white rounded-[1.8rem] text-[10px] font-black shadow-xl shadow-indigo-100 group-hover/btn:bg-slate-900 transition-all uppercase italic tracking-widest">رفع
                                الهوية الآن 📤</span>
                            <input type="file" class="hidden" @change="idUploaded = true">
                        </label>
                        <br>
                        <form method="get" action="{{ route('client.auth.login') }}">
                            @csrf

                            <button type="submit" class="cursor-pointer block group/btn w-full">
                                <span
                                    class="block py-5 px-6 bg-red-600 text-white rounded-[1.8rem] text-[10px] font-black shadow-xl shadow-red-100 group-hover/btn:bg-slate-900 transition-all uppercase italic tracking-widest">
                                    تسجيل خروج 🚪
                                </span>
                            </button>
                        </form>

                        <template x-if="idUploaded">
                            <div
                                class="mt-6 p-4 bg-green-50 text-green-600 rounded-2xl text-[9px] font-black animate-pulse flex items-center justify-center gap-2 border border-green-100 italic">
                                <span>✅ جاري معالجة الهوية ذكياً...</span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- دعوة الأصدقاء --}}
                <div
                    class="bg-gradient-to-br from-indigo-700 to-indigo-900 rounded-[3.5rem] p-10 text-white shadow-2xl shadow-indigo-100 relative group overflow-hidden border border-white/10">
                    <div
                        class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full group-hover:scale-150 transition-transform duration-700">
                    </div>
                    <div class="absolute top-4 right-8 text-4xl opacity-20 italic font-black">50 pts</div>

                    <h4 class="text-2xl font-black mb-3 italic tracking-tighter">ادعُ أصدقاءك 🎁</h4>
                    <p class="text-indigo-100 text-[10px] font-bold leading-relaxed mb-8 italic">شارك كودك الخاص واحصل على
                        نقاط مجانية مقابل كل صديق ينضم إلينا!</p>

                    <div class="relative group/copy mb-6">
                        <div
                            class="bg-white/10 backdrop-blur-md p-5 rounded-2xl border border-white/20 text-center font-black text-sm tracking-[0.4em] italic shadow-inner">
                            OBOUR-2026</div>
                        <div
                            class="absolute inset-0 bg-white opacity-0 group-hover/copy:opacity-5 transition-opacity rounded-2xl cursor-pointer">
                        </div>
                    </div>

                    <button
                        class="w-full py-5 bg-white text-indigo-900 rounded-[1.8rem] font-black text-[10px] uppercase shadow-2xl hover:scale-[1.02] transition-all active:scale-95 italic tracking-widest">نسخ
                        كود الدعوة</button>
                </div>
            </div>
        </div>

        {{-- نافذة التقيي --}}
        <div x-show="openRating"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-lg"
            x-transition x-cloak>

            <div @click.away="openRating = false"
                class="bg-white w-full max-w-lg rounded-[4rem] p-12 shadow-2xl relative animate-slide-up border border-white/20">

                <button @click="openRating = false"
                    class="absolute top-10 left-10 text-slate-300 hover:text-slate-950 transition-colors">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>

                <div class="text-center mb-12">
                    <div
                        class="w-24 h-24 bg-amber-50 text-amber-500 text-5xl flex items-center justify-center rounded-[2.8rem] mx-auto mb-8 shadow-inner animate-bounce italic">
                        ★</div>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tighter italic">رأيك يهمنا في عبور</h2>
                    <p class="text-indigo-600 text-[10px] font-black mt-4 uppercase tracking-[0.4em] italic"
                        x-text="selectedTrip.route"></p>
                </div>

                <form action="#" method="POST" class="space-y-10">
                    @csrf
                    <div class="flex justify-center gap-4">
                        <template x-for="i in 5">
                            <button type="button" @mouseenter="hover = i" @mouseleave="hover = 0" @click="rating = i"
                                class="text-5xl transition-all duration-300 transform hover:scale-125 focus:outline-none"
                                :class="(hover >= i || rating >= i) ?
                                'text-amber-400 drop-shadow-[0_0_15px_rgba(251,191,36,0.3)]' : 'text-slate-100'">
                                ★
                            </button>
                        </template>
                    </div>

                    <div class="relative">
                        <textarea rows="4" placeholder="كيف كانت جودة الخدمة، نظافة الحافلة، وتعامل السائق؟"
                            class="w-full bg-slate-50 border-2 border-transparent rounded-[2.8rem] p-8 text-sm focus:bg-white focus:border-indigo-100 outline-none transition-all resize-none italic font-black shadow-inner text-center"></textarea>
                    </div>

                    <button type="submit"
                        class="w-full py-6 bg-slate-950 text-white rounded-[2.2rem] font-black text-sm shadow-2xl hover:bg-indigo-600 transition-all uppercase tracking-widest italic border-b-4 border-black/20">
                        إرسال التقييم للمدير ✨
                    </button>
                </form>
            </div>
        </div>

    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }

        .animate-fade-in {
            animation: fadeIn 1s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .animate-slide-up {
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
    </style>
@endsection
