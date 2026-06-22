@extends('admin.layouts')
@section('title', 'Permission Registry')

@section('content')
<div class="space-y-8">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 bg-gradient-to-br from-violet-600 to-indigo-600 rounded-[2rem] flex items-center justify-center text-white shadow-2xl shadow-violet-600/20">
                <i class="bi bi-key text-2xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-black tracking-tight text-slate-900 leading-none uppercase">Permission Registry</h1>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mt-2">Manage all system-level access permissions</p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 items-center flex-grow max-w-2xl justify-end">
            <form action="{{ route('permissions.index') }}" method="GET" class="flex-grow flex relative group max-w-md w-full">
                <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-violet-600 transition-colors"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search permissions..."
                       class="w-full bg-white border border-slate-200 rounded-xl pl-12 pr-4 py-3.5 text-xs font-bold focus:ring-4 focus:ring-violet-600/10 focus:border-violet-600 outline-none transition-all shadow-sm">
                @if(request('search'))
                    <a href="{{ route('permissions.index') }}" class="absolute right-4 top-1/2 -translate-y-1/2 text-[9px] font-black uppercase tracking-widest text-slate-400 hover:text-red-500">Clear</a>
                @endif
            </form>
            <a href="{{ route('permissions.create') }}"
               class="bg-violet-600 text-white px-8 py-4 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-violet-600/20 hover:bg-black transition-all flex items-center gap-2 whitespace-nowrap">
                <i class="bi bi-plus-lg"></i> New Permission
            </a>
        </div>
    </div>

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-400">
        <a href="{{ route('roles.index') }}" class="hover:text-violet-600 transition-colors">Roles</a>
        <i class="bi bi-chevron-right text-slate-300 text-[8px]"></i>
        <span class="text-slate-900">Permissions</span>
    </nav>

    {{-- Messages --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex items-center gap-4 animate-fadeIn">
            <div class="w-10 h-10 bg-emerald-500 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-500/20">
                <i class="bi bi-check-lg text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-emerald-700">Success</p>
                <p class="text-sm font-bold text-emerald-600">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-100 p-4 rounded-2xl flex items-center gap-4 animate-fadeIn">
            <div class="w-10 h-10 bg-red-500 rounded-xl flex items-center justify-center text-white shadow-lg shadow-red-500/20">
                <i class="bi bi-exclamation-triangle-fill text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-red-700">Restricted Operation</p>
                <p class="text-sm font-bold text-red-600">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $total = $permissions->total();
            $corePerms = ['manage-products', 'manage-orders', 'manage-users', 'manage-roles'];
        @endphp
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex items-center gap-4">
            <div class="w-10 h-10 bg-violet-50 rounded-xl flex items-center justify-center text-violet-600">
                <i class="bi bi-key text-lg"></i>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Total</p>
                <p class="text-xl font-black text-slate-900">{{ $total }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex items-center gap-4">
            <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center text-amber-600">
                <i class="bi bi-shield-fill text-lg"></i>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Core System</p>
                <p class="text-xl font-black text-slate-900">{{ count($corePerms) }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex items-center gap-4">
            <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600">
                <i class="bi bi-person-check text-lg"></i>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Custom</p>
                <p class="text-xl font-black text-slate-900">{{ max(0, $total - count($corePerms)) }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex items-center gap-4">
            <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
                <i class="bi bi-diagram-3 text-lg"></i>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Guard</p>
                <p class="text-lg font-black text-slate-900">Admin</p>
            </div>
        </div>
    </div>

    {{-- Permissions Table --}}
    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
        <div class="px-10 py-6 border-b border-slate-50 bg-slate-50/30 flex items-center justify-between">
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em]">Permission Matrix</h2>
            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest px-3 py-1 bg-white rounded-full border border-slate-100">
                {{ $permissions->total() }} total entries
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">#</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Permission Name</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Guard</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Assigned to Roles</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Type</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($permissions as $index => $permission)
                        @php $isCore = in_array($permission->name, $corePerms); @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors group">
                            <td class="px-8 py-5">
                                <span class="text-[9px] font-black text-slate-300 tracking-widest">{{ $permissions->firstItem() + $index }}</span>
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl {{ $isCore ? 'bg-amber-50' : 'bg-violet-50' }} flex items-center justify-center flex-shrink-0">
                                        <i class="bi bi-key text-sm {{ $isCore ? 'text-amber-500' : 'text-violet-500' }}"></i>
                                    </div>
                                    <div>
                                        <code class="text-xs font-black text-slate-800 tracking-tight group-hover:text-violet-600 transition-colors">{{ $permission->name }}</code>
                                        <p class="text-[9px] text-slate-400 font-bold mt-0.5 uppercase tracking-widest">
                                            {{ ucwords(str_replace('-', ' ', $permission->name)) }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest bg-indigo-50 text-indigo-600 border border-indigo-100">
                                    {{ $permission->guard_name }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest
                                    {{ $permission->roles_count > 0 ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-400 border border-slate-200' }}">
                                    {{ $permission->roles_count }} {{ Str::plural('role', $permission->roles_count) }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-center">
                                @if($isCore)
                                    <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest bg-amber-50 text-amber-600 border border-amber-100">
                                        <i class="bi bi-lock-fill mr-1"></i> Core
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest bg-slate-100 text-slate-500 border border-slate-200">
                                        Custom
                                    </span>
                                @endif
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex justify-end items-center gap-2">
                                    @if(!$isCore)
                                        <a href="{{ route('permissions.edit', $permission) }}"
                                           class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-violet-600 hover:text-white transition-all shadow-sm"
                                           title="Edit Permission">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="{{ route('permissions.destroy', $permission) }}" method="POST"
                                              onsubmit="return confirm('Delete permission \'{{ $permission->name }}\'? This is irreversible.')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-red-600 hover:text-white transition-all shadow-sm"
                                                    title="Delete Permission">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-300 italic pr-2">Protected</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-8 py-24 text-center">
                                <div class="flex flex-col items-center opacity-20">
                                    <i class="bi bi-key text-6xl text-slate-300"></i>
                                    <span class="text-xs font-black uppercase tracking-widest mt-4">No Permissions Defined</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="flex justify-center">
        {{ $permissions->links() }}
    </div>

</div>
@endsection
