@extends('admin.layouts')
@section('title', 'Administrators')

@section('content')
<div class="space-y-8">
    {{-- Header & Actions --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900 leading-none">ADMINISTRATIVE USERS</h1>
            <p class="text-xs font-black text-slate-400 uppercase tracking-[0.25em] mt-3">Manage portal administrators and adjust security roles</p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-4 items-center flex-grow max-w-2xl justify-end">
            <form action="{{ route('admins.index') }}" method="GET" class="flex-grow flex relative group max-w-md w-full">
                <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors"></i>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Search admins by name or email..." 
                       class="w-full bg-white border border-slate-200 rounded-xl pl-12 pr-4 py-3.5 text-xs font-bold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all shadow-sm">
                @if(request('search'))
                    <a href="{{ route('admins.index') }}" class="absolute right-4 top-1/2 -translate-y-1/2 text-[9px] font-black uppercase tracking-widest text-slate-400 hover:text-red-500">Clear</a>
                @endif
            </form>
            <a href="{{ route('admins.create') }}" class="bg-primary text-white px-8 py-4 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-primary/20 hover:bg-black transition-all flex items-center gap-2 whitespace-nowrap">
                <i class="bi bi-person-plus-fill"></i> Add Administrator
            </a>
        </div>
    </div>

    {{-- System Messages --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex items-center gap-4 animate-fadeIn">
            <div class="w-10 h-10 bg-emerald-500 rounded-xl flex items-center justify-center text-white text-xl shadow-lg shadow-emerald-500/20">
                <i class="bi bi-check-lg"></i>
            </div>
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-emerald-700">Success</p>
                <p class="text-sm font-bold text-emerald-600">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-100 p-4 rounded-2xl flex items-center gap-4 animate-fadeIn">
            <div class="w-10 h-10 bg-red-500 rounded-xl flex items-center justify-center text-white text-xl shadow-lg shadow-red-500/20">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-red-700">Operation Restrained</p>
                <p class="text-sm font-bold text-red-600">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Admins Table --}}
    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Administrator</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Security Roles</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Registered At</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($admins as $admin)
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-8 py-6 w-1/3">
                                <div class="flex items-center gap-4">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($admin->name) }}&background=20a7db&color=fff" 
                                         class="w-11 h-11 rounded-xl shadow-sm border border-slate-100" alt="Avatar">
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-sm font-black text-slate-900 uppercase tracking-tight leading-none group-hover:text-primary transition-colors truncate">
                                            {{ $admin->name }}
                                            @if($admin->id === Auth::guard('admin')->id())
                                                <span class="ml-1.5 px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-primary/10 text-primary border border-primary/20">You</span>
                                            @endif
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-bold mt-1.5 truncate">{{ $admin->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex flex-wrap gap-2">
                                    @forelse($admin->roles as $role)
                                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest {{ $role->name === 'Super Admin' ? 'bg-violet-50 text-violet-600 border border-violet-100' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="text-[10px] text-slate-400 font-bold italic">No role assigned</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span class="text-xs font-bold text-slate-500">
                                    {{ $admin->created_at ? $admin->created_at->format('M d, Y h:i A') : 'System Account' }}
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex justify-end items-center gap-2">
                                    <a href="{{ route('admins.edit', $admin) }}" class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-orange-500 hover:text-white transition-all shadow-sm">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    
                                    @if($admin->email !== 'admin@example.com' && $admin->id !== Auth::guard('admin')->id())
                                        <form action="{{ route('admins.destroy', $admin) }}" method="POST" onsubmit="return confirm('Revoke and delete this administrator? This action is permanent.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-red-600 hover:text-white transition-all shadow-sm">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-300 italic pr-3 select-none">
                                            {{ $admin->email === 'admin@example.com' ? 'Core Admin' : 'Active Session' }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center opacity-25">
                                    <i class="bi bi-people text-6xl text-slate-300"></i>
                                    <span class="text-xs font-black uppercase tracking-widest mt-4">No Administrators Found</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="flex justify-center mt-6">
        {{ $admins->links() }}
    </div>
</div>
@endsection
