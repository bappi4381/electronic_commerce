@extends('admin.layouts')
@section('title', 'User Registry')

@section('content')
<div class="space-y-8">
    {{-- Header & Search --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900 leading-none">User Registry</h1>
            <p class="text-sm font-bold text-slate-400 uppercase tracking-[0.2em] mt-3">Manage and monitor customer accounts</p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-4 flex-grow max-w-2xl">
            <form action="{{ route('users.index') }}" method="GET" class="flex-grow flex relative group">
                <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors"></i>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Search by name, email, phone..." 
                       class="w-full bg-white border border-slate-200 rounded-xl pl-12 pr-4 py-3 text-sm font-semibold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all shadow-sm">
                @if(request('search'))
                    <a href="{{ route('users.index') }}" class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-red-500">Clear</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-100 p-4 rounded-2xl flex items-center gap-4 animate-fadeIn">
            <div class="w-10 h-10 bg-green-500 rounded-xl flex items-center justify-center text-white text-xl shadow-lg shadow-green-500/20">
                <i class="bi bi-check-lg"></i>
            </div>
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-green-700">Account Update</p>
                <p class="text-sm font-bold text-green-600">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Users Table --}}
    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Identity</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">User Details</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Location</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Status</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-8 py-6">
                                <span class="text-xs font-black text-slate-400 tracking-tighter italic">#{{ $user->user_id }}</span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 font-black text-xs uppercase group-hover:bg-primary group-hover:text-white transition-all shadow-sm">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-sm font-black text-slate-900 leading-tight group-hover:text-primary transition-colors">{{ $user->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-bold mt-0.5">{{ $user->email }}</span>
                                        @if($user->phone)
                                            <span class="text-[9px] text-slate-300 font-bold mt-0.5"><i class="bi bi-telephone mr-1"></i>{{ $user->phone }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-slate-700">{{ $user->city ?? 'No City' }}</span>
                                    <span class="text-[9px] text-slate-400 font-black uppercase tracking-widest">{{ $user->country ?? 'Global' }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                @php $isActive = $user->status == 'active'; @endphp
                                <span class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest {{ $isActive ? 'bg-green-100 text-green-600 border border-green-200' : 'bg-red-100 text-red-600 border border-red-200' }}">
                                    {{ $user->status }}
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex justify-end items-center gap-2">
                                    <form action="{{ route('users.toggleStatus', $user) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-slate-900 hover:text-white transition-all shadow-sm" title="{{ $isActive ? 'Block Access' : 'Restore Access' }}">
                                            <i class="bi {{ $isActive ? 'bi-lock-fill' : 'bi-unlock-fill' }}"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('users.edit', $user) }}" class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-orange-500 hover:text-white transition-all shadow-sm">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="{{ route('users.show', $user) }}" class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-primary hover:text-white transition-all shadow-sm">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Permanently delete this user?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-red-600 hover:text-white transition-all shadow-sm">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center opacity-20">
                                    <i class="bi bi-people text-6xl"></i>
                                    <span class="text-xs font-black uppercase tracking-widest mt-4">Zero Users Discovered</span>
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
        {{ $users->links() }}
    </div>
</div>
@endsection

