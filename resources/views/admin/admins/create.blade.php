@extends('admin.layouts')
@section('title', 'Add Administrator')

@section('content')
<div class="space-y-10 pb-20">
    {{-- Header --}}
    <div class="flex items-center gap-5">
        <a href="{{ route('admins.index') }}" class="w-12 h-12 rounded-xl bg-white border border-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-900 hover:shadow-md transition-all">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900 leading-none">ADD PORTAL ADMINISTRATOR</h1>
            <p class="text-xs font-black text-slate-400 uppercase tracking-[0.25em] mt-3">Register a new team member and configure their security clearance</p>
        </div>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-100 p-6 rounded-[2rem] space-y-2 animate-fadeIn">
            <div class="flex items-center gap-3 text-red-500 mb-2">
                <i class="bi bi-exclamation-octagon-fill text-xl"></i>
                <h4 class="text-xs font-black uppercase tracking-widest text-red-700">Invalid Information Provided</h4>
            </div>
            <ul class="list-disc pl-5 text-sm font-bold text-red-600 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Create Form --}}
    <form action="{{ route('admins.store') }}" method="POST" class="space-y-10">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            {{-- Account Information (Takes 2 Columns on large screens) --}}
            <div class="lg:col-span-2 bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 p-10 space-y-8">
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.25em] border-b border-slate-50 pb-4">Profile Credentials</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Full Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. John Doe" 
                               class="w-full bg-slate-50 border-none rounded-xl px-5 py-4 text-xs font-bold focus:ring-2 focus:ring-primary/20 outline-none transition-all" required>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="e.g. john@domain.com" 
                               class="w-full bg-slate-50 border-none rounded-xl px-5 py-4 text-xs font-bold focus:ring-2 focus:ring-primary/20 outline-none transition-all" required>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Password</label>
                        <input type="password" name="password" placeholder="••••••••" 
                               class="w-full bg-slate-50 border-none rounded-xl px-5 py-4 text-xs font-bold focus:ring-2 focus:ring-primary/20 outline-none transition-all" required>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Confirm Password</label>
                        <input type="password" name="password_confirmation" placeholder="••••••••" 
                               class="w-full bg-slate-50 border-none rounded-xl px-5 py-4 text-xs font-bold focus:ring-2 focus:ring-primary/20 outline-none transition-all" required>
                    </div>
                </div>
            </div>

            {{-- Role Clearance (Takes 1 Column) --}}
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 p-10 space-y-8">
                <div class="flex items-center justify-between border-b border-slate-50 pb-4">
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.25em]">Security Roles</h2>
                    <button type="button" onclick="clearAllRoles()" class="text-[9px] font-black uppercase tracking-widest text-slate-400 hover:text-red-500 transition-colors">Clear All</button>
                </div>

                <div class="space-y-4">
                    @forelse($roles as $role)
                        <label class="relative flex items-center p-4 border border-slate-100 hover:border-primary/20 rounded-2xl cursor-pointer hover:bg-slate-50/50 transition-all select-none group">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}" 
                                       class="role-checkbox w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary/20 accent-primary cursor-pointer">
                            </div>
                            <div class="ml-4">
                                <span class="text-xs font-black text-slate-800 uppercase tracking-tight group-hover:text-primary transition-colors">{{ $role->name }}</span>
                                <p class="text-[9px] text-slate-400 font-bold mt-1">Assigns all permissions associated with "{{ $role->name }}".</p>
                            </div>
                        </label>
                    @empty
                        <div class="text-center py-6">
                            <p class="text-[9px] font-black text-slate-300 uppercase tracking-widest">No security roles found</p>
                            <a href="{{ route('roles.create') }}" class="text-[9px] font-black text-primary uppercase tracking-widest mt-2 block hover:underline">Create a Role</a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center gap-4">
            <button type="submit" class="bg-slate-900 text-white px-10 py-5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-2xl shadow-slate-900/20 hover:scale-[1.01] active:scale-[0.99] transition-all">
                Publish Admin Account
            </button>
            <a href="{{ route('admins.index') }}" class="bg-white border border-slate-200 text-slate-500 px-10 py-5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all">
                Discard Changes
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function clearAllRoles() {
        document.querySelectorAll('.role-checkbox').forEach(cb => {
            cb.checked = false;
        });
    }
</script>
@endpush
@endsection
