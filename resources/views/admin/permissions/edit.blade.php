@extends('admin.layouts')
@section('title', 'Edit Permission')

@section('content')
<div class="space-y-10 pb-20">

    {{-- Header --}}
    <div class="flex items-center gap-5">
        <a href="{{ route('permissions.index') }}"
           class="w-12 h-12 rounded-xl bg-white border border-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-900 hover:shadow-md transition-all">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900 leading-none uppercase">Edit Permission</h1>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] mt-3">Modifying: <code class="text-violet-600">{{ $permission->name }}</code></p>
        </div>
    </div>

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-400">
        <a href="{{ route('roles.index') }}" class="hover:text-violet-600 transition-colors">Roles</a>
        <i class="bi bi-chevron-right text-slate-300 text-[8px]"></i>
        <a href="{{ route('permissions.index') }}" class="hover:text-violet-600 transition-colors">Permissions</a>
        <i class="bi bi-chevron-right text-slate-300 text-[8px]"></i>
        <span class="text-slate-900">Edit</span>
    </nav>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-100 p-6 rounded-[2rem] space-y-2 animate-fadeIn">
            <div class="flex items-center gap-3 mb-2">
                <i class="bi bi-exclamation-octagon-fill text-xl text-red-500"></i>
                <h4 class="text-xs font-black uppercase tracking-widest text-red-700">Please fix the following errors</h4>
            </div>
            <ul class="list-disc pl-5 text-sm font-bold text-red-600 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $corePermissions = ['manage-products', 'manage-orders', 'manage-users', 'manage-roles'];
        $isCore = in_array($permission->name, $corePermissions);
    @endphp

    {{-- Core Permission Warning --}}
    @if($isCore)
        <div class="bg-amber-50 border border-amber-100 p-5 rounded-2xl flex items-start gap-4">
            <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="bi bi-shield-fill text-amber-600 text-lg"></i>
            </div>
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-amber-700">System-Protected Permission</p>
                <p class="text-sm font-bold text-amber-600 mt-1">This is a core permission. Its name cannot be changed, but you can view the roles it is assigned to below.</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

        {{-- Main Form --}}
        <div class="lg:col-span-2 space-y-8">

            {{-- Edit Form --}}
            <form action="{{ route('permissions.update', $permission) }}" method="POST">
                @csrf @method('PUT')

                <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 p-10 space-y-8">
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.25em] border-b border-slate-50 pb-4">Permission Details</h2>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Permission Identifier</label>
                        <div class="relative">
                            <i class="bi bi-key absolute left-5 top-1/2 -translate-y-1/2 {{ $isCore ? 'text-amber-400' : 'text-slate-400' }}"></i>
                            <input type="text" name="name" value="{{ old('name', $permission->name) }}"
                                   placeholder="e.g. manage-reports"
                                   class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-5 py-4 text-xs font-bold focus:ring-2 focus:ring-violet-600/20 outline-none transition-all {{ $isCore ? 'opacity-60 cursor-not-allowed' : '' }}"
                                   {{ $isCore ? 'readonly' : '' }} required autocomplete="off">
                        </div>
                        @if($isCore)
                            <p class="text-[9px] text-amber-500 font-bold ml-1">
                                <i class="bi bi-lock-fill mr-1"></i> Core permission names are immutable.
                            </p>
                        @else
                            <p class="text-[9px] text-slate-400 font-bold ml-1">Lowercase letters and hyphens only.</p>
                        @endif
                    </div>

                    {{-- Live Preview --}}
                    <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-2">Current Value</p>
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1.5 bg-white rounded-lg border border-slate-200 text-[10px] font-black text-slate-400 uppercase tracking-widest">guard: admin</span>
                            <i class="bi bi-arrow-right text-slate-300"></i>
                            <code id="permNamePreview" class="text-sm font-black text-violet-700">{{ $permission->name }}</code>
                        </div>
                    </div>
                </div>

                @if(!$isCore)
                    <div class="flex items-center gap-4 mt-8">
                        <button type="submit"
                                class="bg-violet-600 text-white px-10 py-5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-2xl shadow-violet-600/20 hover:bg-violet-700 hover:scale-[1.01] active:scale-[0.99] transition-all">
                            <i class="bi bi-save mr-2"></i> Save Changes
                        </button>
                        <a href="{{ route('permissions.index') }}"
                           class="bg-white border border-slate-200 text-slate-500 px-10 py-5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all">
                            Cancel
                        </a>
                    </div>
                @else
                    <div class="flex items-center gap-4 mt-8">
                        <a href="{{ route('permissions.index') }}"
                           class="bg-white border border-slate-200 text-slate-500 px-10 py-5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all inline-flex items-center gap-2">
                            <i class="bi bi-arrow-left"></i> Back to Permissions
                        </a>
                    </div>
                @endif
            </form>

            {{-- Roles Assigned Card --}}
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
                <div class="px-10 py-6 border-b border-slate-50 bg-slate-50/30 flex items-center justify-between">
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em]">Roles with This Permission</h2>
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest px-3 py-1 bg-white rounded-full border border-slate-100">
                        {{ count($roles->where('has_permission', true)) }} assigned
                    </span>
                </div>
                <div class="divide-y divide-slate-50">
                    @forelse($roles->where('has_permission', true) as $role)
                        <div class="px-10 py-5 flex items-center justify-between group hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                                    <i class="bi bi-shield-check text-emerald-500"></i>
                                </div>
                                <div>
                                    <span class="text-xs font-black text-slate-800 uppercase tracking-tight">{{ $role->name }}</span>
                                    <p class="text-[9px] text-slate-400 font-bold mt-0.5">guard: {{ $role->guard_name }}</p>
                                </div>
                            </div>
                            <a href="{{ route('roles.edit', $role) }}"
                               class="text-[9px] font-black uppercase tracking-widest text-violet-500 hover:text-violet-700 transition-colors">
                                Edit Role <i class="bi bi-arrow-right ml-1"></i>
                            </a>
                        </div>
                    @empty
                        <div class="px-10 py-10 text-center">
                            <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.2em]">Not assigned to any roles yet</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Info Panel --}}
        <div class="space-y-6">
            <div class="bg-gradient-to-br from-violet-600 to-indigo-600 rounded-[2.5rem] p-8 text-white shadow-2xl shadow-violet-600/20">
                <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center mb-6">
                    <i class="bi bi-info-circle text-2xl"></i>
                </div>
                <h3 class="text-xs font-black uppercase tracking-widest mb-4">Permission Info</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-[9px] text-white/50 font-black uppercase tracking-widest">ID</p>
                        <p class="text-sm font-black text-white/90 mt-1">#{{ $permission->id }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] text-white/50 font-black uppercase tracking-widest">Guard</p>
                        <p class="text-sm font-black text-white/90 mt-1">{{ $permission->guard_name }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] text-white/50 font-black uppercase tracking-widest">Created</p>
                        <p class="text-sm font-black text-white/90 mt-1">{{ $permission->created_at->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] text-white/50 font-black uppercase tracking-widest">Total Roles</p>
                        <p class="text-sm font-black text-white/90 mt-1">{{ $roles->where('has_permission', true)->count() }}</p>
                    </div>
                </div>
            </div>

            @if(!$isCore)
                <div class="bg-red-50 border border-red-100 rounded-[2rem] p-8">
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-red-700 mb-3">Danger Zone</h3>
                    <p class="text-xs font-bold text-red-500 mb-5">
                        Deleting this permission will remove it from all assigned roles immediately.
                    </p>
                    <form action="{{ route('permissions.destroy', $permission) }}" method="POST"
                          onsubmit="return confirm('Permanently delete \'{{ $permission->name }}\'? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="w-full bg-red-500 text-white py-3.5 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-red-700 transition-all">
                            <i class="bi bi-trash-fill mr-2"></i> Delete Permission
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    const nameInput = document.querySelector('[name=name]');
    const preview = document.getElementById('permNamePreview');
    if (nameInput && !nameInput.readOnly) {
        nameInput.addEventListener('input', function () {
            let pos = this.selectionStart;
            this.value = this.value.toLowerCase().replace(/[\s_]+/g, '-');
            this.setSelectionRange(pos, pos);
            preview.textContent = this.value || '—';
        });
    }
</script>
@endpush
@endsection
