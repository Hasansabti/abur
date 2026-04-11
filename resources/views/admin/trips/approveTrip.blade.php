@extends('layouts.admin')

@section('content')
    <div class="container mx-auto px-4 py-8 animate-fade-in" x-data="{ filter: 'all' }">

        {{-- رأس الصفحة --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tighter flex items-center gap-3">
                    مركز اعتماد الرحلات
                    <span
                        class="text-sm bg-orange-500 text-white px-3 py-1 rounded-full font-bold tracking-normal animate-pulse">
                        {{ isset($pendingTrips) ? $pendingTrips->count() : 0 }} رحلات معلقة
                    </span>
                </h1>
                <p class="text-slate-500 mt-2 font-medium">مراجعة الطلبات المضافة حديثاً من الوكالات والتحقق من جودة العروض.
                </p>
            </div>

            {{-- فلتر سريع تفاعلي --}}
            <div class="flex gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
                <button @click="filter = 'all'"
                    :class="filter === 'all' ? 'bg-slate-900 text-white shadow-lg' : 'text-slate-400'"
                    class="px-5 py-2 rounded-xl text-xs font-black transition-all">الكل</button>
                <button @click="filter = 'bus'"
                    :class="filter === 'bus' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400'"
                    class="px-5 py-2 rounded-xl text-xs font-black transition-all">باصات 🚌</button>
                <button @click="filter = 'flight'"
                    :class="filter === 'flight' ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-400'"
                    class="px-5 py-2 rounded-xl text-xs font-black transition-all">طيران ✈️</button>
            </div>
        </div>

        {{-- عرض الرسائل التنبيهية (نجاح/خطأ) --}}
        @if (session('success'))
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-2xl font-bold flex items-center gap-2 animate-bounce">
                ✅ {{ session('success') }}
            </div>
        @endif

        {{-- قائمة الرحلات --}}
        <div class="grid grid-cols-1 gap-8">
            @foreach ($pendingTrips as $trip)
                <div x-show="filter === 'all' || filter === '{{ $trip->transport_type }}'"
                    class="bg-white rounded-[3rem] border border-slate-100 shadow-sm hover:shadow-2xl transition-all duration-500 overflow-hidden group">

                    <div class="flex flex-col lg:flex-row">

                        {{-- قسم الصورة - يطابق $trip->image --}}
                        <div class="lg:w-80 relative h-64 lg:h-auto overflow-hidden">
                            <img src="{{ $trip->image }}"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                alt="Trip Image">
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-900/20 to-transparent">
                            </div>

                            {{-- شارة نوع الرحلة --}}
                            <div
                                class="absolute top-6 right-6 bg-white/95 backdrop-blur-md px-4 py-2 rounded-2xl text-[10px] font-black text-slate-800 shadow-xl">
                                ID: #{{ $trip->id }}
                            </div>
                        </div>

                        {{-- قسم التفاصيل - يطابق بيانات الكنترولر --}}
                        <div class="p-10 flex-grow grid grid-cols-1 md:grid-cols-3 gap-8 items-center">

                            {{-- العمود 1: الوكالة والمسار --}}
                            <div>
                                <p class="text-[10px] text-blue-600 font-black uppercase tracking-widest mb-1">
                                    {{ $trip->agency->name }}</p>
                                <h3 class="text-2xl font-black text-slate-900 mb-4 tracking-tight">{{ $trip->route }}</h3>
                                <div class="flex items-center gap-2 text-slate-500">
                                    <span class="text-lg">📅</span>
                                    <span class="text-xs font-bold">{{ $trip->date }}</span>
                                </div>
                            </div>

                            {{-- العمود 2: المركبة والسعر --}}
                            <div class="space-y-4 border-r border-slate-100 pr-8">
                                <div>
                                    <p class="text-[10px] text-slate-400 font-black uppercase mb-1">المركبة والسعة</p>
                                    <p class="text-sm font-black text-slate-700">{{ $trip->vehicle_model }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold italic">{{ $trip->capacity }} مقعد متاح
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400 font-black uppercase mb-1">السعر المقترح</p>
                                    <p class="text-3xl font-black text-indigo-600">{{ $trip->price }} <small
                                            class="text-xs">ر.س</small></p>
                                </div>
                            </div>

                            {{-- العمود 3: إجراءات الاعتماد --}}
                            <div class="flex flex-col gap-3">
                                {{-- زر الاعتماد --}}
                                <form action="{{ route('admin.trips.approveTrip', $trip->id) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="w-full py-4 bg-slate-900 text-white rounded-2xl font-black text-xs hover:bg-blue-600 transition-all shadow-xl shadow-slate-200 flex items-center justify-center gap-2 group/btn">
                                        <span class="group-hover/btn:scale-125 transition-transform">✅</span>
                                        اعتماد ونشر الرحلة
                                    </button>
                                </form>

                                <div class="flex gap-2">
                                    {{-- زر الرفض --}}
                                    <form action="{{ route('admin.reject_trip', $trip->id) }}" method="POST"
                                        class="flex-1">
                                        @csrf
                                        <button type="submit"
                                            class="w-full py-4 bg-slate-50 text-slate-400 rounded-2xl font-black text-[10px] hover:bg-red-50 hover:text-red-500 transition-all border border-slate-100 uppercase">
                                            رفض الطلب
                                        </button>
                                    </form>

                                    {{-- زر معاينة التفاصيل --}}
                                    <button
                                        class="px-5 py-4 bg-slate-50 text-slate-400 rounded-2xl hover:bg-indigo-50 hover:text-indigo-600 transition-all border border-slate-100">
                                        📄
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- تذييل البطاقة الذكي --}}
                    <div class="bg-slate-50/50 p-4 px-10 flex items-center justify-between border-t border-slate-50">
                        <div class="flex items-center gap-4">
                            <span class="flex items-center gap-1.5 text-[9px] font-black text-slate-400 uppercase">
                                <span
                                    class="w-2 h-2 rounded-full {{ $trip->transport_type == 'bus' ? 'bg-blue-500' : 'bg-indigo-500' }}"></span>
                                وسيلة النقل: {{ $trip->transport_type == 'bus' ? 'باص' : 'طيران' }}
                            </span>
                        </div>
                        <p class="text-[9px] font-bold text-slate-300 italic">تم تقديم الطلب بواسطة مدير وكالة
                            {{ $trip->agency->name }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- في حال كانت القائمة فارغة --}}
        @if ($pendingTrips->isEmpty())
            <div class="py-32 text-center bg-white rounded-[4rem] border-4 border-dashed border-slate-50 shadow-inner">
                <div class="text-8xl mb-6">🏜️</div>
                <h2 class="text-2xl font-black text-slate-800">لا توجد طلبات معلقة حالياً</h2>
                <p class="text-slate-400 mt-2 font-medium">كل الرحلات تم تدقيقها بنجاح. استرح قليلاً!</p>
            </div>
        @endif

    </div>

    <style>
        .animate-fade-in {
            animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
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
    </style>
@endsection
