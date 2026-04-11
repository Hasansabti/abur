@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-6 py-10 max-w-6xl"
     x-data="{
        openModal: false,
        selectedName: '',
        selectedImage: '',
        selectedPhone: '',
        selectedEmail: '',
        selectedId: null,
        approvedIds: []
     }">

    {{-- Header --}}
    <div class="mb-12 flex justify-between items-center bg-white p-8 rounded-[3rem] shadow-sm border border-slate-50">
        <h1 class="text-3xl font-black text-slate-900 italic">مركز الاعتماد 🛂</h1>
        <div class="bg-indigo-600 text-white px-6 py-2 rounded-full font-black italic text-sm">
            طلبات: {{ count($requests) }}
        </div>
    </div>

    {{-- Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach($requests as $req)
            <div class="bg-white rounded-[2.5rem] p-7 shadow-xl border border-slate-50 relative">

                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-2xl">👤</div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-slate-900 italic">{{ $req->user_name }}</h3>
                            <span x-show="approvedIds.includes({{ $req->id }})" class="text-indigo-500 text-xs">✅</span>
                        </div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ $req->id_number }}</p>
                    </div>
                </div>

                <button @click="
                    selectedName = '{{ $req->user_name }}';
                    selectedImage = '{{ $req->id_image }}';
                    selectedPhone = '{{ $req->phone }}';
                    selectedEmail = '{{ $req->email }}';
                    selectedId = {{ $req->id }};
                    openModal = true;
                "
                x-show="!approvedIds.includes({{ $req->id }})"
                class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-600 transition-all italic">
                    مراجعة البيانات 👁️
                </button>

                <div x-show="approvedIds.includes({{ $req->id }})" x-cloak class="bg-green-50 text-green-600 p-4 rounded-2xl text-center font-black italic text-sm">
                    تم التوثيق ✅
                </div>
            </div>
        @endforeach
    </div>

    {{-- النافذة المنبثقة (Modal) --}}
    <div x-show="openModal"
         x-transition.opacity
         class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-900/90 backdrop-blur-sm p-4"
         x-cloak>

        <div @click.away="openModal = false"
             class="bg-white w-full max-w-lg rounded-[3rem] p-10 shadow-2xl relative">

            <div class="mb-8">
                <h2 class="text-2xl font-black text-slate-900 italic" x-text="selectedName"></h2>
                <p class="text-indigo-600 text-[10px] font-black uppercase tracking-widest mt-1">تفاصيل الهوية والاتصال</p>
            </div>

            <div class="space-y-6">
                <div class="rounded-3xl overflow-hidden border-4 border-slate-50 shadow-md h-40 bg-slate-100">
                    <img :src="selectedImage" class="w-full h-full object-cover">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-slate-50 p-4 rounded-2xl">
                        <span class="text-[8px] text-slate-400 block uppercase font-black">الجوال</span>
                        <span class="font-black text-slate-900 italic text-xs" x-text="selectedPhone"></span>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-2xl">
                        <span class="text-[8px] text-slate-400 block uppercase font-black">البريد</span>
                        <span class="font-black text-slate-900 italic text-[10px] truncate block" x-text="selectedEmail"></span>
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button @click="approvedIds.push(selectedId); openModal = false;"
                            class="flex-grow bg-indigo-600 text-white py-4 rounded-2xl font-black text-xs italic shadow-lg shadow-indigo-200">
                        اعتماد الهوية ✅
                    </button>
                    <button @click="openModal = false" class="px-6 py-4 bg-slate-100 text-slate-500 rounded-2xl font-black text-xs italic">
                        إغلاق
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@if(!View::hasSection('scripts'))
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endif

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
