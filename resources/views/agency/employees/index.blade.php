@extends('layouts.agency')

@section('content')
<div dir="rtl" class="min-h-screen bg-slate-50/50 p-4 sm:p-6 lg:p-8 font-sans" x-data="{
    deleteModalOpen: false,
    deleteUrl: '',
    confirmDelete(url) {
        this.deleteUrl = url;
        this.deleteModalOpen = true;
    },
    editModalOpen: false,
    editUrl: '',
    editData: {
        id: '',
        name: '',
        email: '',
        phone: '',
        role: '',
        permissions: []
    },
    openEditModal(emp) {
        this.editUrl = '{{ route('agency.employees') }}/' + emp.id;
        let perms = [];
        try {
            perms = typeof emp.permissions === 'string' ? JSON.parse(emp.permissions) : (emp.permissions || []);
        } catch(e) {
            perms = [];
        }
        this.editData = {
            id: emp.id,
            name: emp.name,
            email: emp.email,
            phone: emp.phone || '',
            role: emp.role,
            permissions: Array.isArray(perms) ? perms : []
        };
        this.editModalOpen = true;
    }
}">
    
    {{-- تنبيه النجاح --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition class="mb-6 flex items-center justify-between rounded-xl bg-emerald-50 p-4 border border-emerald-200 text-emerald-800 shadow-sm">
            <div class="flex items-center space-x-3 space-x-reverse">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="font-medium text-sm">{{ session('success') }}</span>
            </div>
            <button @click="show = false" class="text-emerald-500 hover:text-emerald-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- تنبيه الأخطاء --}}
    @if($errors->any())
        <div x-data="{ show: true }" x-show="show" x-transition class="mb-6 rounded-xl bg-rose-50 p-4 border border-rose-200 text-rose-800 shadow-sm">
            <div class="flex items-start justify-between">
                <div class="flex items-start space-x-3 space-x-reverse">
                    <svg class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="font-semibold text-sm">يرجى مراجعة الأخطاء التالية:</p>
                        <ul class="mt-2 list-disc list-inside text-xs space-y-1 text-rose-700">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button @click="show = false" class="text-rose-500 hover:text-rose-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    @endif

    {{-- كروت إحصائيات الموظفين --}}
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 mb-1">إجمالي الموظفين</p>
                <h3 class="text-2xl font-black text-slate-800">{{ $stats['total'] ?? 0 }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 mb-1">مدراء النظام</p>
                <h3 class="text-2xl font-black text-rose-600">{{ $stats['admin'] ?? 0 }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 mb-1">المحاسبون والمالية</p>
                <h3 class="text-2xl font-black text-sky-600">{{ $stats['accountant'] ?? 0 }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 mb-1">خدمة العملاء والحجز</p>
                <h3 class="text-2xl font-black text-emerald-600">{{ $stats['staff'] ?? 0 }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        {{-- قسم إضافة موظف جديد --}}
        <div class="lg:col-span-4 bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
            <div class="bg-indigo-600 px-6 py-4 text-white flex items-center gap-2 font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <span>إضافة موظف جديد</span>
            </div>

            <form action="{{ route('agency.employees.store') }}" method="POST" class="p-6 space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">اسم الموظف <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="أدخل اسم الموظف الثلاثي" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">البريد الإلكتروني <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="employee@agency.com" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">رقم الهاتف</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="05xxxxxxxx"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">كلمة المرور <span class="text-rose-500">*</span></label>
                    <input type="password" name="password" placeholder="••••••••" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">الدور الوظيفي <span class="text-rose-500">*</span></label>
                    <select name="role" id="role-select" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition outline-none bg-white">
                        <option value="" disabled selected>اختر الصلاحية...</option>
                        <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>موظف حجز وخدمة عملاء</option>
                        <option value="accountant" {{ old('role') == 'accountant' ? 'selected' : '' }}>محاسب / مالية</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>مدير نظام (كامل الصلاحيات)</option>
                    </select>
                </div>
                
                {{-- قسم تحديد الصلاحيات والصفحات --}}
                <div id="permissions-section">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">الصفحات المسموح بالدخول لها <span class="text-xs text-slate-400 font-normal">(اختر الصفحات)</span></label>
                    <div class="grid grid-cols-2 gap-3 bg-slate-50 p-4 rounded-xl border border-slate-200/80">
                        
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="dashboard" checked class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-sm font-medium text-slate-700">لوحة التحكم</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="trips" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-sm font-medium text-slate-700">الرحلات والسكن</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="bookings" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-sm font-medium text-slate-700">إدارة الحجوزات</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="finance" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-sm font-medium text-slate-700">السجلات المالية</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="reports" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-sm font-medium text-slate-700">التقارير</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="rooms" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-sm font-medium text-slate-700">إدارة التسكين</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="employees" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-sm font-medium text-slate-700">إدارة الموظفين</span>
                        </label>
                        
                    </div>
                </div>

                <button type="submit" 
                    class="w-full mt-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-xl shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span>حفظ الموظف</span>
                </button>
            </form>
        </div>

        {{-- قسم قائمة الموظفين مع حقل البحث --}}
        <div class="lg:col-span-8 space-y-4">

            {{-- شريط البحث والفلترة --}}
            <form action="{{ url()->current() }}" method="GET" class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200/80 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث بالاسم، البريد أو الهاتف..."
                        class="w-full pr-10 pl-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm outline-none transition">
                </div>

                <div class="w-full sm:w-48">
                    <select name="role" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm outline-none bg-white transition">
                        <option value="">جميع الأدوار</option>
                        <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>مدير نظام</option>
                        <option value="accountant" {{ request('role') == 'accountant' ? 'selected' : '' }}>محاسب</option>
                        <option value="staff" {{ request('role') == 'staff' ? 'selected' : '' }}>موظف حجز</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-medium text-sm rounded-xl transition flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <span>بحث</span>
                    </button>

                    @if(request()->filled('search') || request()->filled('role'))
                        <a href="{{ url()->current() }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium text-sm rounded-xl transition text-center flex items-center justify-center" title="إلغاء البحث">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                </div>
            </form>

            {{-- الجدول --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="bg-slate-900 px-6 py-4 text-white flex justify-between items-center">
                    <div class="flex items-center gap-2 font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>قائمة موظفي الوكالة</span>
                    </div>
                    <span class="text-xs bg-slate-800 px-3 py-1 rounded-full text-slate-300">
                        العدد المعروض: {{ $employees->count() }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-right border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3.5 px-4 text-center">#</th>
                                <th class="py-3.5 px-4">الاسم / الايميل</th>
                                <th class="py-3.5 px-4 text-center">الدور</th>
                                <th class="py-3.5 px-4 w-1/3">صلاحيات الوصول</th>
                                <th class="py-3.5 px-4 text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-sm">
                            @php
                                $permNames = [
                                    'dashboard' => 'لوحة التحكم',
                                    'trips' => 'الرحلات',
                                    'bookings' => 'الحجوزات',
                                    'finance' => 'المالية',
                                    'reports' => 'التقارير',
                                    'employees' => 'الموظفين'
                                ];
                            @endphp
                            @forelse($employees as $employee)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-3.5 px-4 text-center font-medium text-slate-400">
                                        {{ $loop->iteration + ($employees->firstItem() ? $employees->firstItem() - 1 : 0) }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-semibold text-slate-800">{{ $employee->name }}</div>
                                        <div class="text-xs text-slate-500 mt-0.5">{{ $employee->email }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        @if($employee->role === 'admin')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">مدير نظام</span>
                                        @elseif($employee->role === 'accountant')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800 border border-sky-200">محاسب</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">موظف حجز</span>
                                        @endif
                                        
                                        <div class="mt-1">
                                            @if($employee->is_active)
                                                <span class="text-[9px] text-emerald-600 font-bold">نشط</span>
                                            @else
                                                <span class="text-[9px] text-red-600 font-bold">مجمد</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex flex-wrap gap-1">
                                            @if($employee->role === 'admin')
                                                <span class="px-2 py-0.5 bg-slate-800 text-white text-[10px] rounded">كافة الصلاحيات (أدمن)</span>
                                            @else
                                                @php
                                                    $employeePerms = is_string($employee->permissions) ? json_decode($employee->permissions, true) : $employee->permissions;
                                                    $employeePerms = is_array($employeePerms) ? $employeePerms : [];
                                                @endphp
                                                
                                                @forelse($employeePerms as $perm)
                                                    @if(isset($permNames[$perm]))
                                                        <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-100 text-[10px] rounded">{{ $permNames[$perm] }}</span>
                                                    @endif
                                                @empty
                                                    <span class="text-xs text-slate-400 font-medium">- لا توجد صلاحيات محددة -</span>
                                                @endforelse
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            {{-- زر تعديل بيانات الموظف --}}
                                            <button type="button" 
                                                @click="openEditModal({{ json_encode($employee) }})"
                                                title="تعديل الموظف"
                                                class="p-2 rounded-lg border border-indigo-200 text-indigo-600 hover:bg-indigo-50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>

                                            {{-- زر تغيير الحالة --}}
                                            <form action="{{ route('agency.employees.toggle', $employee->id) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                    title="{{ $employee->is_active ? 'تجميد الحساب' : 'تنشيط الحساب' }}"
                                                    class="p-2 rounded-lg border transition-colors {{ $employee->is_active ? 'border-amber-200 text-amber-600 hover:bg-amber-50' : 'border-emerald-200 text-emerald-600 hover:bg-emerald-50' }}">
                                                    @if($employee->is_active)
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    @endif
                                                </button>
                                            </form>

                                            {{-- زر الحذف الفوري --}}
                                            <button type="button" 
                                                @click="confirmDelete('{{ route('agency.employees.destroy', $employee->id) }}')"
                                                title="حذف الموظف"
                                                class="p-2 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400">
                                        <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        <span>لا يوجد موظفين يطابقون نتائج البحث.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(method_exists($employees, 'hasPages') && $employees->hasPages())
                    <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                        {{ $employees->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- نافذة تعديل بيانات الموظف (Edit Modal) --}}
    <div x-show="editModalOpen" 
         x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 overflow-y-auto"
         style="display: none;" x-cloak>
        <div @click.away="editModalOpen = false" 
             x-transition.scale
             class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-100 overflow-hidden my-8">
            
            <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center">
                <h3 class="font-bold text-base flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <span>تعديل بيانات الموظف</span>
                </h3>
                <button @click="editModalOpen = false" class="text-indigo-200 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="editUrl" method="POST" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">اسم الموظف <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="editData.name" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">البريد الإلكتروني <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" x-model="editData.email" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">رقم الهاتف</label>
                    <input type="text" name="phone" x-model="editData.phone"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">كلمة المرور الجديدة <span class="text-xs text-slate-400 font-normal">(اتركها فارغة لإبقاء كلمة المرور القديمة)</span></label>
                    <input type="password" name="password" placeholder="••••••••"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">الدور الوظيفي <span class="text-rose-500">*</span></label>
                    <select name="role" x-model="editData.role" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition outline-none bg-white">
                        <option value="staff">موظف حجز وخدمة عملاء</option>
                        <option value="accountant">محاسب / مالية</option>
                        <option value="admin">مدير نظام (كامل الصلاحيات)</option>
                    </select>
                </div>
                
                {{-- قسم الصلاحيات للتعديل --}}
                <div x-show="editData.role !== 'admin'">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">الصفحات المسموح بالدخول لها</label>
                    <div class="grid grid-cols-2 gap-3 bg-slate-50 p-4 rounded-xl border border-slate-200/80">
                        
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="dashboard" :checked="editData.permissions.includes('dashboard')" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-sm font-medium text-slate-700">لوحة التحكم</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="trips" :checked="editData.permissions.includes('trips')" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-sm font-medium text-slate-700">الرحلات والسكن</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="bookings" :checked="editData.permissions.includes('bookings')" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-sm font-medium text-slate-700">إدارة الحجوزات</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="finance" :checked="editData.permissions.includes('finance')" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-sm font-medium text-slate-700">السجلات المالية</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="reports" :checked="editData.permissions.includes('reports')" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-sm font-medium text-slate-700">التقارير</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="rooms" :checked="editData.permissions.includes('rooms')" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-sm font-medium text-slate-700">إدارة التسكين</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="employees" :checked="editData.permissions.includes('employees')" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <span class="text-sm font-medium text-slate-700">إدارة الموظفين</span>
                        </label>
                        
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-3">
                    <button type="button" @click="editModalOpen = false" 
                        class="w-1/2 py-2.5 px-4 rounded-xl border border-slate-300 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">
                        إلغاء
                    </button>
                    <button type="submit" 
                        class="w-1/2 py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm shadow-md transition">
                        حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- نافذة التأكيد والحذف --}}
    <div x-show="deleteModalOpen" 
         x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4"
         style="display: none;" x-cloak>
        <div @click.away="deleteModalOpen = false" 
             x-transition.scale
             class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100">
            
            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>

            <h3 class="text-lg font-bold text-slate-800 text-center mb-2">تأكيد حذف الموظف</h3>
            <p class="text-sm text-slate-500 text-center mb-6">هل أنت تأكد من إجراء عملية الحذف؟ لن تتمكن من استعادة بيانات الموظف بعد الحذف.</p>

            <form :action="deleteUrl" method="POST" class="flex items-center gap-3">
                @csrf
                @method('DELETE')
                <button type="button" @click="deleteModalOpen = false" 
                    class="w-1/2 py-2.5 px-4 rounded-xl border border-slate-300 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">
                    إلغاء
                </button>
                <button type="submit" 
                    class="w-1/2 py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-semibold text-sm shadow-sm transition">
                    تأكيد الحذف
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // إخفاء الصلاحيات إذا كان الدور المحدد هو أدمن في نموذج الإضافة
        document.getElementById('role-select').addEventListener('change', function() {
            const permSection = document.getElementById('permissions-section');
            if(this.value === 'admin') {
                permSection.style.opacity = '0.5';
                permSection.style.pointerEvents = 'none';
            } else {
                permSection.style.opacity = '1';
                permSection.style.pointerEvents = 'auto';
            }
        });
    </script>
</div>
@endsection