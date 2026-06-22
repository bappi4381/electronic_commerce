@extends('admin.layouts')
@section('title', 'Create Permission')

@section('content')
<div class="space-y-10 pb-20">

    {{-- Header --}}
    <div class="flex items-center gap-5">
        <a href="{{ route('permissions.index') }}"
           class="w-12 h-12 rounded-xl bg-white border border-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-900 hover:shadow-md transition-all">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900 leading-none uppercase">Create New Permission</h1>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] mt-3">Define a new access control key for the admin guard</p>
        </div>
    </div>

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-400">
        <a href="{{ route('roles.index') }}" class="hover:text-violet-600 transition-colors">Roles</a>
        <i class="bi bi-chevron-right text-slate-300 text-[8px]"></i>
        <a href="{{ route('permissions.index') }}" class="hover:text-violet-600 transition-colors">Permissions</a>
        <i class="bi bi-chevron-right text-slate-300 text-[8px]"></i>
        <span class="text-slate-900">Create</span>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

        {{-- Main Form --}}
        <div class="lg:col-span-2">
            <form action="{{ route('permissions.store') }}" method="POST" class="space-y-10">
                @csrf

                <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 p-10 space-y-8">
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.25em] border-b border-slate-50 pb-4">Permission Details</h2>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">
                            Permission Identifier <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i class="bi bi-key absolute left-5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   placeholder="e.g. manage-reports, view-analytics"
                                   class="w-full bg-slate-50 border-none rounded-xl pl-12 pr-5 py-4 text-xs font-bold focus:ring-2 focus:ring-violet-600/20 outline-none transition-all @error('name') ring-2 ring-red-400 @enderror"
                                   required autocomplete="off">
                        </div>
                        <p class="text-[9px] text-slate-400 font-bold ml-1">
                            Use lowercase letters and hyphens only. Example: <code class="text-violet-600">manage-reports</code>
                        </p>
                    </div>

                    {{-- Slug Preview --}}
                    <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-2">Live Preview</p>
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1.5 bg-white rounded-lg border border-slate-200 text-[10px] font-black text-slate-400 uppercase tracking-widest">guard: admin</span>
                            <i class="bi bi-arrow-right text-slate-300"></i>
                            <code id="permNamePreview" class="text-sm font-black text-violet-700">—</code>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit"
                            class="bg-violet-600 text-white px-10 py-5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-2xl shadow-violet-600/20 hover:bg-violet-700 hover:scale-[1.01] active:scale-[0.99] transition-all">
                        <i class="bi bi-key mr-2"></i> Register Permission
                    </button>
                    <a href="{{ route('permissions.index') }}"
                       class="bg-white border border-slate-200 text-slate-500 px-10 py-5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        {{-- Guide Panel --}}
        <div class="space-y-6">
            <div class="bg-gradient-to-br from-violet-600 to-indigo-600 rounded-[2.5rem] p-8 text-white shadow-2xl shadow-violet-600/20">
                <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center mb-6">
                    <i class="bi bi-lightbulb text-2xl"></i>
                </div>
                <h3 class="text-xs font-black uppercase tracking-widest mb-4">Naming Guide</h3>
                <ul class="space-y-3 text-[11px] font-bold text-white/80">
                    <li class="flex items-start gap-2">
                        <i class="bi bi-check-circle-fill text-white/60 mt-0.5 flex-shrink-0"></i>
                        Use kebab-case format
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="bi bi-check-circle-fill text-white/60 mt-0.5 flex-shrink-0"></i>
                        Start with an action verb
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="bi bi-check-circle-fill text-white/60 mt-0.5 flex-shrink-0"></i>
                        Be specific and descriptive
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="bi bi-x-circle-fill text-red-300 mt-0.5 flex-shrink-0"></i>
                        No spaces or uppercase letters
                    </li>
                </ul>
            </div>

            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-xl p-8 space-y-4">
                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400">Examples</h3>
                <div class="space-y-2">
                    @foreach(['manage-reports', 'view-analytics', 'export-data', 'manage-coupons', 'view-logs'] as $eg)
                        <div class="flex items-center gap-2 cursor-pointer group" onclick="document.querySelector('[name=name]').value='{{ $eg }}'; updatePreview('{{ $eg }}')">
                            <div class="w-2 h-2 rounded-full bg-violet-200 group-hover:bg-violet-600 transition-colors flex-shrink-0"></div>
                            <code class="text-xs text-slate-500 group-hover:text-violet-600 font-bold transition-colors">{{ $eg }}</code>
                        </div>
                    @endforeach
                </div>
                <p class="text-[9px] text-slate-300 font-bold">Click any example to use it.</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const nameInput = document.querySelector('[name=name]');
    const preview = document.getElementById('permNamePreview');

    function updatePreview(val) {
        val = val || nameInput.value;
        const sanitized = val.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
        preview.textContent = sanitized || '—';
    }

    nameInput.addEventListener('input', function () {
        // Auto-format the input
        let pos = this.selectionStart;
        this.value = this.value.toLowerCase().replace(/[\s_]+/g, '-');
        this.setSelectionRange(pos, pos);
        updatePreview(this.value);
    });

    // Run once on load (for old() repopulation)
    updatePreview(nameInput.value);
</script>
@endpush
@endsection
