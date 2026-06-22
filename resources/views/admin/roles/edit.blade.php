@extends('admin.layouts')
@section('title', 'Edit Administrative Role')

@section('content')
<div class="space-y-10 pb-20">
    {{-- Header --}}
    <div class="flex items-center gap-5">
        <a href="{{ route('roles.index') }}" class="w-12 h-12 rounded-xl bg-white border border-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-900 hover:shadow-md transition-all">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900 leading-none">EDIT ADMINISTRATIVE ROLE</h1>
            <p class="text-xs font-black text-slate-400 uppercase tracking-[0.25em] mt-3">Modify privileges and properties of the "{{ $role->name }}" role</p>
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

    {{-- Edit Form --}}
    <form action="{{ route('roles.update', $role) }}" method="POST" class="space-y-10">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 gap-10">
            {{-- General Info Card --}}
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 p-10 space-y-8">
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.25em] border-b border-slate-50 pb-4">Role Profile</h2>
                
                <div class="max-w-xl space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Role Name</label>
                    <input type="text" name="name" value="{{ old('name', $role->name) }}" placeholder="e.g. Content Moderator, Support Agent" 
                           class="w-full bg-slate-50 border-none rounded-xl px-5 py-4 text-xs font-bold focus:ring-2 focus:ring-primary/20 outline-none transition-all" 
                           {{ $role->name === 'Super Admin' ? 'readonly' : '' }} required>
                    
                    @if($role->name === 'Super Admin')
                        <p class="text-[9px] text-amber-500 font-bold ml-1"><i class="bi bi-exclamation-triangle-fill mr-1"></i>The system-protected role name "Super Admin" cannot be changed.</p>
                    @else
                        <p class="text-[9px] text-slate-400 font-bold ml-1">Provide a concise, descriptive title for the administrative role.</p>
                    @endif
                </div>
            </div>

            {{-- Permissions Selection Card --}}
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 p-10 space-y-8">
                <div class="flex items-center justify-between border-b border-slate-50 pb-4">
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.25em]">Access Permissions Matrix</h2>
                    <div class="flex gap-4">
                        <button type="button" onclick="toggleAllPermissions(true)" class="text-[9px] font-black uppercase tracking-widest text-primary hover:text-black transition-colors">Select All</button>
                        <span class="text-slate-300">|</span>
                        <button type="button" onclick="toggleAllPermissions(false)" class="text-[9px] font-black uppercase tracking-widest text-slate-400 hover:text-black transition-colors">Deselect All</button>
                    </div>
                </div>

                @php
                    $groupedPermissions = [];
                    foreach($permissions as $permission) {
                        $name = $permission->name;
                        if (str_contains($name, 'product') || str_contains($name, 'category')) {
                            $group = 'Inventory & Products';
                        } elseif (str_contains($name, 'order')) {
                            $group = 'Sales & Orders';
                        } elseif (str_contains($name, 'user')) {
                            $group = 'Users & Customers';
                        } elseif (str_contains($name, 'role')) {
                            $group = 'Roles & Security';
                        } else {
                            $group = 'General System Settings';
                        }
                        $groupedPermissions[$group][] = $permission;
                    }
                @endphp

                <div class="space-y-10">
                    @forelse($groupedPermissions as $groupName => $perms)
                        <div class="space-y-4">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-1">{{ $groupName }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($perms as $permission)
                                    @php
                                        $isChecked = in_array($permission->id, $rolePermissions);
                                    @endphp
                                    <label class="relative flex items-start p-5 border border-slate-100 hover:border-primary/20 rounded-2xl cursor-pointer hover:bg-slate-50/50 transition-all select-none group">
                                        <div class="flex items-center h-5 mt-0.5">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" 
                                                   class="permission-checkbox w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary/20 accent-primary cursor-pointer"
                                                   {{ $isChecked ? 'checked' : '' }}>
                                        </div>
                                        <div class="ml-4 text-xs">
                                            <span class="font-black text-slate-800 uppercase tracking-tight group-hover:text-primary transition-colors">{{ str_replace('-', ' ', $permission->name) }}</span>
                                            <p class="text-[10px] text-slate-400 font-bold mt-1">Grants authority to execute actions under "{{ $permission->name }}".</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10">
                            <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">No permissions found in database</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center gap-4">
            <button type="submit" class="bg-slate-900 text-white px-10 py-5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-2xl shadow-slate-900/20 hover:scale-[1.01] active:scale-[0.99] transition-all">
                Update Role Settings
            </button>
            <a href="{{ route('roles.index') }}" class="bg-white border border-slate-200 text-slate-500 px-10 py-5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all">
                Discard Changes
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function toggleAllPermissions(checked) {
        document.querySelectorAll('.permission-checkbox').forEach(cb => {
            cb.checked = checked;
        });
    }
</script>
@endpush
@endsection
