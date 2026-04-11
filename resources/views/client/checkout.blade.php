@extends('layouts.client')

@section('content')
    <div class="container mx-auto px-4 py-10 max-w-6xl" x-data="{
        paymentMethod: 'card',
        selectedBank: null,
        banks: [
            { name: 'مصرف الراجحي', logo: '🏦', color: 'blue', iban: 'SA8280000000123456789001', acc: '123456789' },
            { name: 'البنك الأهلي SNB', logo: '🏛️', color: 'emerald', iban: 'SA4310000000987654321002', acc: '987654321' },
            { name: 'بنك البلاد', logo: '🏢', color: 'amber', iban: 'SA1510000000554433221103', acc: '554433221' }
        ],
        copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            alert('تم نسخ النص بنجاح ✅');
        }
    }">

        <div class="flex flex-col lg:flex-row gap-10">

            <div class="lg:col-span-2 flex-1 space-y-6">
                <h2 class="text-3xl font-black text-slate-800 tracking-tighter mb-8 text-right">إتمام عملية الحجز 💳</h2>

                <div class="grid grid-cols-3 gap-4 mb-10">
                    <button @click="paymentMethod = 'card'"
                        :class="paymentMethod === 'card' ? 'border-blue-600 bg-blue-50 shadow-md' :
                            'border-slate-100 bg-white'"
                        class="p-6 rounded-[2rem] border-2 transition-all flex flex-col items-center gap-3">
                        <span class="text-2xl">💳</span>
                        <span class="text-[10px] font-black text-slate-700">بطاقة مدى / فيزا</span>
                    </button>
                    <button @click="paymentMethod = 'tabby'"
                        :class="paymentMethod === 'tabby' ? 'border-teal-400 bg-teal-50 shadow-md' :
                            'border-slate-100 bg-white'"
                        class="p-6 rounded-[2rem] border-2 transition-all flex flex-col items-center gap-3">
                        <span class="text-2xl">💚</span>
                        <span class="text-[10px] font-black text-slate-700">تابي / تمارا</span>
                    </button>
                    <button @click="paymentMethod = 'bank'"
                        :class="paymentMethod === 'bank' ? 'border-amber-400 bg-amber-50 shadow-md' :
                            'border-slate-100 bg-white'"
                        class="p-6 rounded-[2rem] border-2 transition-all flex flex-col items-center gap-3">
                        <span class="text-2xl">🏦</span>
                        <span class="text-[10px] font-black text-slate-700 text-center">تحويل بنكي مباشر</span>
                    </button>
                </div>

                <div class="bg-white rounded-[2.5rem] p-10 border border-slate-100 shadow-sm min-h-[400px]">

                    <div x-show="paymentMethod === 'card'" class="space-y-6 animate-fade-in text-right">
                        <h4 class="font-black text-slate-800">بيانات البطاقة البنكية</h4>
                        <div class="grid grid-cols-1 gap-4 text-right">
                            <div>
                                <label
                                    class="text-[10px] text-slate-400 font-black mb-2 block tracking-widest uppercase">اسم
                                    صاحب البطاقة</label>
                                <input type="text"
                                    class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm focus:ring-2 focus:ring-blue-600 outline-none text-right">
                            </div>
                            <div>
                                <label
                                    class="text-[10px] text-slate-400 font-black mb-2 block tracking-widest uppercase">رقم
                                    البطاقة</label>
                                <input type="text" placeholder="0000 0000 0000 0000"
                                    class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm focus:ring-2 focus:ring-blue-600 outline-none ltr">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <input type="text" placeholder="MM/YY"
                                    class="bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm focus:ring-2 focus:ring-blue-600 outline-none text-center ltr">
                                <input type="text" placeholder="CVV"
                                    class="bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm focus:ring-2 focus:ring-blue-600 outline-none text-center ltr">
                            </div>
                        </div>
                    </div>

                    <div x-show="paymentMethod === 'tabby'" class="text-center py-10 space-y-6 animate-fade-in">
                        <div class="inline-block bg-teal-100 text-teal-700 px-6 py-2 rounded-full font-black text-xs">قسّمها
                            على 4 دفعات</div>
                        <p class="text-slate-500 font-medium italic">قريبا...</p>
                        <div class="flex justify-center gap-6 text-5xl opacity-40">📱 🛡️ ✨</div>
                    </div>

                    <div x-show="paymentMethod === 'bank'" class="space-y-8 animate-fade-in text-right">
                        <div>
                            <label class="text-[10px] text-slate-400 font-black mb-4 block tracking-widest uppercase">1.
                                اختر البنك الذي ستحول إليه:</label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <template x-for="bank in banks">
                                    <button @click="selectedBank = bank"
                                        :class="selectedBank?.name === bank.name ?
                                            'border-blue-600 bg-blue-50 ring-4 ring-blue-50' :
                                            'border-slate-100 bg-white'"
                                        class="p-4 border-2 rounded-2xl transition-all flex flex-col items-center gap-2 hover:border-blue-200">
                                        <span class="text-xl" x-text="bank.logo"></span>
                                        <span class="text-[10px] font-black text-slate-700 text-center"
                                            x-text="bank.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div x-show="selectedBank" x-transition
                            class="bg-slate-900 rounded-[2rem] p-8 text-white relative overflow-hidden">
                            <div class="absolute right-0 top-0 opacity-10 text-9xl transform translate-x-10 -translate-y-10"
                                x-text="selectedBank?.logo"></div>

                            <h5
                                class="text-blue-400 text-[10px] font-black mb-6 flex items-center gap-2 uppercase tracking-widest">
                                <span class="w-2 h-2 bg-blue-400 rounded-full animate-ping"></span> بيانات الحساب البنكي
                                الرسمي
                            </h5>

                            <div class="space-y-6 relative z-10">
                                <div class="flex flex-col gap-2">
                                    <span class="text-[9px] text-slate-400 font-black">رقم الآيبان (IBAN)</span>
                                    <div
                                        class="flex items-center justify-between bg-slate-800 p-4 rounded-xl border border-slate-700 group">
                                        <code class="text-sm font-mono text-blue-200 ltr tracking-wider"
                                            x-text="selectedBank?.iban"></code>
                                        <button @click="copyToClipboard(selectedBank.iban)"
                                            class="text-[10px] bg-blue-600 hover:bg-blue-500 px-3 py-1 rounded-lg transition-colors font-bold shrink-0">نسخ</button>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <span class="text-[9px] text-slate-400 font-black">رقم الحساب</span>
                                    <div
                                        class="flex items-center justify-between bg-slate-800 p-4 rounded-xl border border-slate-700 group">
                                        <code class="text-sm font-mono text-blue-200 ltr tracking-wider"
                                            x-text="selectedBank?.acc"></code>
                                        <button @click="copyToClipboard(selectedBank.acc)"
                                            class="text-[10px] bg-slate-700 hover:bg-slate-600 px-3 py-1 rounded-lg transition-colors font-bold shrink-0">نسخ</button>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-6 text-[10px] text-slate-500 italic font-bold">باسم: مؤسسة وكالة السفر للنقل
                                السياحي</p>
                        </div>

                        <div x-show="selectedBank">
                            <label class="text-[10px] text-slate-400 font-black mb-4 block tracking-widest uppercase">2.
                                ارفع صورة التحويل البنكي:</label>
                            <div
                                class="border-2 border-dashed border-slate-200 rounded-[2rem] p-10 text-center hover:border-blue-400 transition-colors cursor-pointer relative bg-slate-50/50">
                                <input type="file" class="absolute inset-0 opacity-0 cursor-pointer">
                                <div class="space-y-3">
                                    <span class="text-4xl">📸</span>
                                    <p class="text-xs font-black text-slate-700 tracking-tight">اضغط هنا لإرفاق إيصال
                                        التحويل</p>
                                    <p class="text-[9px] text-slate-400 font-bold italic">يتم تأكيد الحجز فور مراجعة الحوالة
                                        من قسم المحاسبة.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button onclick="window.location.href='{{ route('client.success') }}'"
                        class="w-full mt-10 py-5 bg-blue-600 text-white font-black rounded-[1.5rem] shadow-xl shadow-blue-200 hover:bg-blue-700 transition-all transform hover:-translate-y-1 flex items-center justify-center gap-3">
                        <span>إتمام الطلب الآن</span>
                        <span class="text-xl">🚀</span>
                    </button>
                </div>
            </div>

            <div class="lg:w-96 space-y-6">
                <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white shadow-2xl overflow-hidden relative">
                    <div class="absolute -right-10 -top-10 w-32 h-32 bg-blue-600 rounded-full blur-[60px] opacity-40"></div>

                    <h3 class="text-xl font-black mb-8 relative z-10 text-right">فاتورة الحجز</h3>

                    <div class="space-y-4 relative z-10 text-right">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-400">سعر المقعد:</span>
                            <span class="font-bold text-blue-400">{{ $unit_price }} ر.س</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-400">العدد:</span>
                            <span class="font-bold">{{ count($selected_seats) }} مقعد</span>
                        </div>
                        <div class="flex justify-between items-center text-sm pt-4 border-t border-slate-800">
                            <span class="text-slate-400 font-black uppercase tracking-widest text-[9px]">الإجمالي
                                النهائي</span>
                            <span class="text-3xl font-black text-white">{{ $total_price }} <small
                                    class="text-xs">ر.س</small></span>
                        </div>
                    </div>

                    <div
                        class="mt-8 bg-slate-800 p-4 rounded-2xl border border-slate-700 text-[10px] leading-relaxed text-slate-400 font-medium italic text-center">
                        تم احتساب السعر بناءً على السكن في (غرفة
                        {{ $room_type == 'quad' ? 'رباعية' : ($room_type == 'triple' ? 'ثلاثية' : 'خاصة') }}).
                    </div>
                </div>

                <div class="bg-gray-50 p-6 rounded-[2rem] border border-gray-100 text-center">
                    <p class="text-[10px] text-gray-400 font-black mb-4 uppercase tracking-tighter">مدعوم بواسطة أنظمة دفع
                        عالمية آمنة</p>
                    <div class="flex justify-center gap-4 grayscale opacity-50">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" class="h-4">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" class="h-4">
                        <span class="font-black text-xs text-slate-400">Mada</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .ltr {
            direction: ltr;
        }

        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
@endsection
