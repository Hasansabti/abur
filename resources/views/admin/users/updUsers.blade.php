@extends('layouts.admin')

@section('content')
    <div class="max-w-7xl mx-auto p-4 md:p-8" dir="rtl">

        {{-- الرأس --}}
        <div class="flex flex-col lg:flex-row justify-between gap-6 mb-10">

            <div>
                <h1 class="text-3xl font-black text-slate-800">
                    إدارة المستخدمين
                </h1>
                <p class="text-slate-500 mt-2">
                    التحكم في بيانات الأعضاء وإدارة الصلاحيات
                </p>
            </div>

            {{-- حقل البحث --}}
            <div class="w-full md:w-[26rem]">
                <form action="{{ request()->url() }}" method="GET" class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
                    </svg>

                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="بحث بالاسم أو البريد أو الهوية أو الهاتف..."
                        class="w-full rounded-2xl border border-slate-200 bg-white py-3 pr-12 pl-4 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500 outline-none">
                </form>
            </div>

        </div>

        {{-- الرسائل --}}
        @if (session('success'))
            <div class="mb-6 rounded-2xl bg-emerald-50 border border-emerald-100 p-4 text-emerald-700">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-2xl bg-red-50 border border-red-100 p-4 text-red-600">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        {{-- البطاقة والجدول --}}
        <div class="bg-white border border-slate-100 rounded-3xl shadow-sm overflow-hidden">

            {{-- رأس الجدول --}}
            <div class="px-6 py-5 border-b bg-slate-50">
                <div class="flex justify-between items-center">
                    <h3 class="font-black text-slate-700">
                        قائمة المستخدمين
                    </h3>
                    <span class="text-sm text-slate-500">
                        {{ $users->total() }} مستخدم
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs">
                            <th class="p-5">المستخدم</th>
                            <th class="p-5">المدينة</th>
                            <th class="p-5">الهاتف</th>
                            <th class="p-5">الهوية</th>
                            <th class="p-5">الحجوزات الحالية</th>
                            <th class="p-5">الحالة</th>
                            <th class="p-5">إجراءات</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($users as $user)
                            <tr class="border-t hover:bg-slate-50">
                                <td class="p-5">
                                    <div class="flex gap-3 items-center">
                                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center">
                                            {{ $user->gender == 'male' ? '👨🏻' : '👩🏻' }}
                                        </div>
                                        <div>
                                            <div class="font-bold">
                                                {{ $user->name }}
                                            </div>
                                            <div class="text-xs text-slate-400">
                                                {{ $user->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-5">
                                    {{ $user->city ?: '—' }}
                                </td>

                                <td class="p-5">
                                    {{ $user->phone ?: '—' }}
                                </td>

                                <td class="p-5">
                                    {{ $user->national_id ?: '—' }}
                                </td>

                                <td class="p-5">
                                    {{ $user->bookings_count }}
                                </td>

                                <td class="p-5">
                                    @if ($user->is_verified == 1)
                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700">
                                            موثق
                                        </span>
                                    @elseif($user->is_verified == 2)
                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-50 text-red-700">
                                            تم رفض التوثيق
                                        </span>
                                    @else
                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-50 text-yellow-700">
                                            غير موثق
                                        </span>
                                    @endif
                                </td>

                                <td class="p-5">
                                    <a href="{{ route('admin.users.edit', $user->id) }}"
                                        class="inline-flex px-4 py-2 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition">
                                        تعديل
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-16 text-center text-slate-400">
                                    لا يوجد مستخدمين مطابقين للبحث
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- الترقيم --}}
            <div class="p-6 border-t flex flex-col md:flex-row justify-between gap-4 items-center">
                <div class="text-sm text-slate-500">
                    عرض {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} من إجمالي {{ $users->total() }}
                </div>

                <div>
                    {{ $users->links() }}
                </div>
            </div>

        </div>

    </div>
@endsection