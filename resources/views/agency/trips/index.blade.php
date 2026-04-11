@extends('layouts.agency')

@section('content')
    <div class="container mx-auto px-4 py-8 animate-fade-in" x-data="{ openEditModal: false, openReviewsModal: false, activeTrip: {}, reviews: [] }">

        {{-- الهيدر --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-black text-slate-800">إدارة الرحلات 🚀</h1>
                <p class="text-sm text-slate-400 mt-1">استعرض، عدّل، وتابع حالات الحجز وتقييمات عملائك</p>
            </div>
            <a href="{{ route('agency.trips.create') }}"
                class="px-8 py-4 bg-blue-600 text-white rounded-2xl shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all flex items-center font-black text-sm uppercase tracking-widest">
                <span class="ml-2">➕</span> إضافة رحلة جديدة
            </a>
        </div>

        {{-- شبكة الإحصائيات --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            @php
                $tripsCollection = collect($trips);
                $stats = [
                    ['label' => 'إجمالي الرحلات', 'value' => $tripsCollection->count(), 'icon' => '📊', 'color' => 'blue'],
                    ['label' => 'رحلات نشطة', 'value' => $tripsCollection->whereIn('status', ['approved', 'pending'])->count(), 'icon' => '🟢', 'color' => 'green'],
                    ['label' => 'متوسط التقييم', 'value' => '4.8', 'icon' => '⭐', 'color' => 'yellow'],
                    ['label' => 'حجوزات جديدة', 'value' => '+30', 'icon' => '🔔', 'color' => 'pink'],
                ];
            @endphp
            @foreach($stats as $stat)
                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-50 hover:shadow-md transition-all group">
                    <div class="flex justify-between items-start mb-4">
                        <span class="text-2xl opacity-50 group-hover:opacity-100 transition-opacity">{{ $stat['icon'] }}</span>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $stat['label'] }}</p>
                    </div>
                    <h2 class="text-3xl font-black text-gray-800 tracking-tighter">{{ $stat['value'] }}</h2>
                </div>
            @endforeach
        </div>

        {{-- الجدول --}}
        <div class="bg-white rounded-[2.5rem] shadow-sm overflow-hidden border border-slate-100">
            <table class="min-w-full divide-y divide-gray-100 text-right" dir="rtl">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase">الرحلة والتقييم</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase">التوقيت</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase">التكلفة</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase">الاجمالي</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase">الامتلاء</th>
                        <th class="p-6 text-[10px] font-black text-slate-400 uppercase">الحالة</th>
                        <th class="p-6 text-center text-[10px] font-black text-slate-400 uppercase">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach ($trips as $trip)
                        @php
                            $seats_filled = $trip->total_seats - $trip->available_seats;
                            $seats_percent = $trip->total_seats > 0 ? ($seats_filled / $trip->total_seats) * 100 : 0;
                            $total_price = $trip->total_seats * $trip->price;

                            $status_map = [
                                'approved' => ['class' => 'bg-green-100 text-green-700', 'text' => 'معتمدة'],
                                'pending' => ['class' => 'bg-amber-100 text-amber-700', 'text' => 'انتظار'],
                                'completed' => ['class' => 'bg-slate-100 text-slate-500', 'text' => 'منتهية']
                            ];
                            $current_status = $status_map[$trip->status] ?? $status_map['completed'];
                            $transportType = property_exists($trip, 'transport_type') ? $trip->transport_type : 'bus';
                            $seatMapUrl = ($transportType === 'flight')
                                ? route('agency.trips.flightmap', ['id' => $trip->id])
                                : route('agency.trips.seatmap', ['id' => $trip->id]);
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-all group">
                            <td class="p-6">
                                <div class="flex items-center gap-4">
                                    <span class="text-2xl bg-slate-50 w-12 h-12 flex items-center justify-center rounded-2xl group-hover:bg-white transition-colors shadow-sm">
                                        {{ $transportType === 'flight' ? '✈️' : '🚌' }}
                                    </span>
                                    <div>
                                        <div class="font-black text-slate-800 group-hover:text-blue-600 transition-colors">{{ $trip->title }}</div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[9px] font-bold text-amber-500 bg-amber-50 px-2 py-0.5 rounded-lg">⭐ 4.5</span>
                                            {{-- زر عرض التقييمات --}}
                                            <button @click="openReviewsModal = true; activeTrip = {{ json_encode($trip) }}" class="text-[9px] text-blue-500 font-black hover:underline">عرض الآراء</button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-6">
                                <span class="text-xs font-black text-slate-600 italic">📅 {{ $trip->date }}</span><br>
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">{{ $trip->time }}</span>
                            </td>
                            <td class="p-6">
                                <div class="text-xs font-black text-slate-800">{{ $trip->price }} <small class="opacity-50">ر.س</small></div>
                                <div class="text-[9px] text-slate-400 font-bold">للمقعد الواحد</div>
                            </td>
                            <td class="p-6">
                                <div class="text-xs font-black text-slate-800">{{ $total_price }} <small class="opacity-50">ر.س</small></div>
                            </td>
                            <td class="p-6">
                                <div class="w-32">
                                    <div class="flex justify-between mb-1 text-[9px] font-black uppercase">
                                        <span class="text-slate-400">{{ $seats_filled }} / {{ $trip->total_seats }}</span>
                                        <span class="text-blue-600">{{ round($seats_percent) }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                                        <div class="bg-blue-600 h-full rounded-full transition-all duration-700" style="width: {{ $seats_percent }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-6 text-center">
                                <span class="px-3 py-1 text-[9px] rounded-full font-black uppercase tracking-widest {{ $current_status['class'] }}">
                                    {{ $current_status['text'] }}
                                </span>
                            </td>
                            <td class="p-6">
                                <div class="flex justify-center items-center gap-2">
                                    <a href="{{ $seatMapUrl }}" class="w-10 h-10 flex items-center justify-center bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm">💺</a>

                                    @if ($trip->status != 'completed')
                                        <button @click="openEditModal = true; activeTrip = {{ json_encode($trip) }}"
                                            class="w-10 h-10 flex items-center justify-center bg-slate-50 text-slate-600 rounded-xl hover:bg-slate-800 hover:text-white transition-all shadow-sm">✏️</button>
                                        <button class="w-10 h-10 flex items-center justify-center bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm">🗑️</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Modal آراء المسافرين (Reviews) --}}
        <div x-show="openReviewsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm" x-cloak x-transition>
            <div class="bg-white w-full max-w-2xl rounded-[3rem] p-10 shadow-2xl relative" @click.away="openReviewsModal = false">
                <button @click="openReviewsModal = false" class="absolute top-8 left-8 text-slate-300 hover:text-slate-900 font-black text-xl">✕</button>

                <h3 class="text-2xl font-black text-slate-800 italic mb-2">آراء المسافرين ✨</h3>
                <p class="text-xs text-slate-400 font-bold uppercase mb-8" x-text="activeTrip.title"></p>

                <div class="space-y-4 max-h-[400px] overflow-y-auto pr-4 custom-scrollbar">
                    {{-- مثال لتعليق --}}
                    @foreach(range(1, 3) as $i)
                    <div class="p-6 bg-slate-50 rounded-[2rem] border border-slate-100">
                        <div class="flex justify-between items-center mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center font-black text-blue-600">ع</div>
                                <p class="text-sm font-black text-slate-700">عبدالله بن خالد</p>
                            </div>
                            <span class="text-amber-500 font-black text-xs">⭐⭐⭐⭐⭐</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium leading-relaxed italic">"كانت الرحلة مريحة جداً والحافلة نظيفة، السائق كان جداً محترم وملتزم بالمواعيد. شكراً لكم!"</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div x-show="openEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak x-transition>
            <div class="bg-white w-full max-w-lg rounded-[3rem] p-10 shadow-2xl relative" @click.away="openEditModal = false">
                <button @click="openEditModal = false" class="absolute top-10 left-10 text-slate-300 hover:text-red-500 transition-colors font-black">✕</button>

                <div class="mb-8">
                    <h3 class="text-2xl font-black text-slate-800 italic text-center">تحديث بيانات الرحلة</h3>
                </div>

                <form action="#" class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 mr-2">عنوان الرحلة</label>
                        <input type="text" x-model="activeTrip.title" class="w-full bg-slate-50 border-none rounded-2xl p-5 font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 mr-2">سعر المقعد</label>
                            <input type="number" x-model="activeTrip.price" class="w-full bg-slate-50 border-none rounded-2xl p-5 font-bold text-blue-600 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 mr-2">الحالة برمجياً</label>
                            <select x-model="activeTrip.status" class="w-full bg-slate-50 border-none rounded-2xl p-5 font-black text-slate-700 outline-none">
                                <option value="pending">قيد الانتظار</option>
                                <option value="approved">معتمدة</option>
                                <option value="completed">منتهية</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-5 bg-slate-900 text-white rounded-[2rem] font-black text-sm shadow-xl hover:bg-blue-600 transition-all">حفظ التغييرات ✅</button>
                </form>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .animate-fade-in { animation: fadeIn 0.6s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
@endsection


