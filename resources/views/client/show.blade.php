@extends('layouts.client')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <h2 class="text-2xl font-black text-gray-800 mb-4">تفاصيل الرحلة والخدمات</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="text-center p-3 bg-blue-50 rounded-2xl">
                        <span class="block text-xl">📶</span>
                        <span class="text-xs text-blue-600 font-bold">إنترنت مجاني</span>
                    </div>
                    <div class="text-center p-3 bg-green-50 rounded-2xl">
                        <span class="block text-xl">🍱</span>
                        <span class="text-xs text-green-600 font-bold">وجبة جافة</span>
                    </div>
                    <div class="text-center p-3 bg-purple-50 rounded-2xl">
                        <span class="block text-xl">🔌</span>
                        <span class="text-xs text-purple-600 font-bold">شاحن USB</span>
                    </div>
                    <div class="text-center p-3 bg-red-50 rounded-2xl">
                        <span class="block text-xl">❄️</span>
                        <span class="text-xs text-red-600 font-bold">تكييف مركزي</span>
                    </div>
                </div>
                <p class="text-gray-600 leading-relaxed italic text-sm">
                    "نحن في وكالة الفارس نهتم براحتكم، الرحلة تشمل التوقف في الاستراحات المعتمدة مع توفير مياه باردة طوال الطريق."
                </p>
            </div>

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-800">آراء المسافرين</h3>
                    <div class="flex items-center space-x-2 space-x-reverse text-yellow-500">
                        <span class="text-2xl font-black">4.8</span>
                        <span class="text-sm text-gray-400">من 5</span>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="border-b border-gray-50 pb-4">
                        <div class="flex justify-between mb-2">
                            <span class="font-bold text-gray-700">محمد س.</span>
                            <span class="text-[10px] text-gray-400 font-mono">منذ يومين</span>
                        </div>
                        <div class="flex text-yellow-400 text-[10px] mb-2">★★★★★</div>
                        <p class="text-gray-600 text-sm">بصراحة الباص مريح جداً والسائق خلوق والالتزام بالوقت كان ممتاز. أنصح بالتعامل معهم.</p>
                    </div>

                    <div class="border-b border-gray-50 pb-4">
                        <div class="flex justify-between mb-2">
                            <span class="font-bold text-gray-700">نورة أ.</span>
                            <span class="text-[10px] text-gray-400 font-mono">منذ أسبوع</span>
                        </div>
                        <div class="flex text-yellow-400 text-[10px] mb-2">★★★★☆</div>
                        <p class="text-gray-600 text-sm">التنظيم رائع، لكن كان هناك تأخير بسيط في نقطة التجمع بالرياض. بشكل عام تجربة جيدة.</p>
                    </div>
                </div>

                <button class="w-full mt-4 py-2 text-blue-600 text-sm font-bold hover:underline">عرض المزيد من التقييمات</button>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-blue-600 p-6 rounded-3xl shadow-xl text-white sticky top-24">
                <h3 class="font-bold mb-4 border-b border-blue-500 pb-2">ملخص الحجز</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span>سعر التذكرة:</span> <span>150 ر.س</span></div>
                    <div class="flex justify-between text-blue-200"><span>الضريبة:</span> <span>تلقائي</span></div>
                    <hr class="border-blue-500">
                    <div class="flex justify-between font-black text-xl"><span>الإجمالي:</span> <span>150 ر.س</span></div>
                </div>

                <a href="#" class="block w-full mt-6 py-4 bg-orange-500 hover:bg-orange-600 text-center rounded-2xl font-black transition shadow-lg">
                    تأكيد واختيار المقعد
                </a>
                <p class="text-[10px] text-blue-200 text-center mt-4">بضغطك على تأكيد أنت توافق على شروط الإلغاء</p>
            </div>
        </div>

    </div>
</div>
@endsection
