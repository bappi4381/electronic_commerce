@extends('admin.layouts')
@section('title', 'Attribute Manager')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6 max-w-4xl mx-auto" x-data="{ step: parseInt(localStorage.getItem('attributeManagerStep')) || 1 }" x-init="$watch('step', value => localStorage.setItem('attributeManagerStep', value))">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Attribute Manager</h1>
            <p class="text-sm text-slate-500 mt-1">Manage product attributes like Color, Size, RAM, Storage etc.</p>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 rounded-2xl text-sm font-semibold mb-6">
            <i class="bi bi-check-circle-fill text-lg"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl text-sm font-semibold mb-6">
            <i class="bi bi-exclamation-circle-fill text-lg"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Stepper UI --}}
    <div class="mb-8 relative pt-4">
        <div class="absolute left-0 top-1/2 w-full h-1 bg-slate-200 rounded-full z-0 -translate-y-1/2 mt-2"></div>
        <div class="absolute left-0 top-1/2 h-1 bg-primary rounded-full z-0 transition-all duration-500 -translate-y-1/2 mt-2" 
             :style="'width: ' + ((step - 1) * 50) + '%'"></div>
        
        <div class="relative z-10 flex justify-between">
            <!-- Step 1 -->
            <div class="flex flex-col items-center gap-2 cursor-pointer" @click="step = 1">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all duration-300 border-4 border-slate-50" 
                     :class="step >= 1 ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-slate-200 text-slate-400'">1</div>
                <span class="text-[10px] font-black uppercase tracking-widest" :class="step >= 1 ? 'text-primary' : 'text-slate-400'">Create Attribute</span>
            </div>
            <!-- Step 2 -->
            <div class="flex flex-col items-center gap-2 cursor-pointer" @click="step = 2">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all duration-300 border-4 border-slate-50" 
                     :class="step >= 2 ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-slate-200 text-slate-400'">2</div>
                <span class="text-[10px] font-black uppercase tracking-widest" :class="step >= 2 ? 'text-primary' : 'text-slate-400'">Add Values</span>
            </div>
            <!-- Step 3 -->
            <div class="flex flex-col items-center gap-2 cursor-pointer" @click="step = 3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all duration-300 border-4 border-slate-50" 
                     :class="step >= 3 ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-slate-200 text-slate-400'">3</div>
                <span class="text-[10px] font-black uppercase tracking-widest" :class="step >= 3 ? 'text-primary' : 'text-slate-400'">Map to Category</span>
            </div>
        </div>
    </div>


    <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm p-6 md:p-8">

        {{-- STEP 1: Create Attribute --}}
        <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
            
            <div class="mb-6 pb-6 border-b border-slate-100">
                <h2 class="text-xl font-black text-slate-800">1. Create Attribute</h2>
                <p class="text-sm text-slate-500 mt-1">Add new product attributes that can be used across categories.</p>
            </div>

            <form action="{{ route('admin.attributes.store') }}" method="POST" class="space-y-6 max-w-2xl">
                @csrf
                <div>
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Attribute Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold outline-none focus:border-primary transition-all hover:border-primary/40"
                        placeholder="e.g. Color, Size, Storage" required>
                    @error('name')<p class="text-red-500 text-xs mt-2">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Input Type</label>
                    <select name="type"
                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary transition-all appearance-none bg-white cursor-pointer hover:border-primary/40">
                        <option value="select">Dropdown (Select)</option>
                        <option value="radio">Radio Button</option>
                        <option value="text">Text Input</option>
                    </select>
                </div>

                <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer hover:border-primary/40 hover:shadow-sm transition-all group">
                    <input type="checkbox" name="is_filterable" value="1" checked class="h-4 w-4 accent-primary rounded">
                    <span class="text-sm font-bold text-slate-700">Show attribute in shop filter sidebar</span>
                </label>

                <div class="pt-4 flex justify-between items-center border-t border-slate-100 mt-8">
                    <div></div>
                    <div class="flex items-center gap-3">
                        <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-8 py-3.5 rounded-xl text-sm font-black flex items-center gap-2 transition-all shadow-lg shadow-primary/30 hover:-translate-y-0.5">
                            <i class="bi bi-plus-lg text-lg"></i> Create Attribute
                        </button>
                        <button type="button" @click="step = 2" class="bg-slate-900 hover:bg-slate-800 text-white px-6 py-3.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shadow-md">
                            Next Step <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- STEP 2: Attribute Library --}}
        <div x-show="step === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;" x-cloak>
            
            <div class="mb-6 pb-6 border-b border-slate-100">
                <h2 class="text-xl font-black text-slate-800">2. Attribute Library (Add Values)</h2>
                <p class="text-sm text-slate-500 mt-1">Manage all attributes, update settings, and add values in one place.</p>
            </div>

            <div class="space-y-4">
                @forelse($attributes as $attribute)
                    <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-slate-50 shadow-sm" x-data="{ editOpen: false, addValueOpen: false }">
                        <div class="flex flex-col gap-4 px-6 py-5 md:flex-row md:items-center md:justify-between bg-white">
                            <div class="min-w-0">
                                <p class="text-base font-black text-slate-900">{{ $attribute->name }}</p>
                                <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                    <span class="rounded-full bg-slate-100 px-2 py-1">{{ $attribute->type }}</span>
                                    @if($attribute->is_filterable)
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-emerald-700">Filterable</span>
                                    @endif
                                    <span class="rounded-full bg-slate-100 px-2 py-1">{{ $attribute->values_count }} values</span>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap items-center gap-2">
                                @foreach($attribute->categories as $cat)
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700">{{ $cat->getTranslation('name','en') }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid gap-4 px-6 py-5 md:grid-cols-[1fr_auto] md:items-start">
                            <div class="space-y-4">
                                <div x-show="editOpen" x-cloak x-transition class="rounded-[1.5rem] border border-primary/10 bg-primary/5 p-5">
                                    <form action="{{ route('admin.attributes.update', $attribute) }}" method="POST" class="grid gap-4 sm:grid-cols-2">
                                        @csrf @method('PUT')
                                        <div>
                                            <label class="text-xs font-black uppercase tracking-[0.2em] text-slate-600 mb-1.5 block">Name</label>
                                            <input type="text" name="name" value="{{ $attribute->name }}"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold outline-none focus:border-primary transition-all" required>
                                        </div>
                                        <div>
                                            <label class="text-xs font-black uppercase tracking-[0.2em] text-slate-600 mb-1.5 block">Type</label>
                                            <select name="type"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold outline-none focus:border-primary transition-all">
                                                <option value="select" {{ $attribute->type === 'select' ? 'selected' : '' }}>Select</option>
                                                <option value="radio" {{ $attribute->type === 'radio' ? 'selected' : '' }}>Radio</option>
                                                <option value="text" {{ $attribute->type === 'text' ? 'selected' : '' }}>Text</option>
                                            </select>
                                        </div>
                                        <div class="sm:col-span-2 flex items-center justify-between gap-3 mt-2">
                                            <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 cursor-pointer">
                                                <input type="checkbox" name="is_filterable" value="1" {{ $attribute->is_filterable ? 'checked' : '' }} class="h-4 w-4 accent-primary rounded">
                                                Filterable
                                            </label>
                                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-primary-dark shadow-sm">
                                                Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <div x-show="addValueOpen" x-cloak x-transition class="rounded-[1.5rem] border border-emerald-100 bg-emerald-50 p-5">
                                    <form action="{{ route('admin.attributes.values.store', $attribute) }}" method="POST" class="grid gap-4 md:grid-cols-[1.5fr_minmax(140px,auto)] md:items-end">
                                        @csrf
                                        <div>
                                            <label class="text-xs font-black uppercase tracking-[0.2em] text-slate-600 mb-1.5 block">Add value</label>
                                            <input type="text" name="value"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold outline-none focus:border-primary transition-all"
                                                placeholder="e.g. Red, XL, 128GB" required>
                                        </div>
                                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-emerald-700">
                                            Save Value
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <button @click.prevent="addValueOpen = !addValueOpen"
                                    class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 transition hover:bg-emerald-100">
                                    <i class="bi bi-plus-lg mr-1"></i> <span x-text="addValueOpen ? 'Cancel' : 'Add Value'"></span>
                                </button>
                                <button @click.prevent="editOpen = !editOpen"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-slate-300">
                                    <span x-text="editOpen ? 'Hide' : 'Edit'"></span> Attribute
                                </button>
                                <form action="{{ route('admin.attributes.destroy', $attribute) }}" method="POST" class="inline-flex w-full" onsubmit="return confirm('Delete this attribute?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-full rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-100">
                                        Delete Attribute
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 m-4 mt-0 shadow-sm">
                            @if($attribute->values->isEmpty())
                                <p class="text-sm text-slate-400">No values yet. Click "Add Value" to add possible options.</p>
                            @else
                                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach($attribute->values as $value)
                                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-slate-300 transition-all">
                                            <span>{{ $value->value }}</span>
                                            <form action="{{ route('admin.attributes.values.destroy', $value) }}" method="POST" onsubmit="return confirm('Remove this value?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-slate-400 hover:text-red-500 transition-colors">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-12 text-center shadow-sm">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                            <i class="bi bi-tags text-3xl"></i>
                        </div>
                        <p class="text-lg font-bold text-slate-800">No attributes found yet.</p>
                        <p class="mt-2 text-sm text-slate-500">Go back to Step 1 to create your first attribute.</p>
                    </div>
                @endforelse

                <div class="pt-4 flex justify-between items-center border-t border-slate-100 mt-8">
                    <button type="button" @click="step = 1" class="text-slate-500 hover:text-slate-800 font-bold text-sm flex items-center gap-2 transition-colors">
                        <i class="bi bi-arrow-left"></i> Previous
                    </button>
                    <button type="button" @click="step = 3" class="bg-slate-900 hover:bg-slate-800 text-white px-6 py-3.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shadow-md">
                        Next Step <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- STEP 3: Map Attributes --}}
        <div x-show="step === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;" x-cloak>
            
            <div class="mb-6 pb-6 border-b border-slate-100">
                <h2 class="text-xl font-black text-slate-800">3. Map Attributes to Category</h2>
                <p class="text-sm text-slate-500 mt-1">Choose which attributes should be available for each category.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                {{-- Form Box --}}
                <div class="bg-slate-50 border border-slate-200 rounded-[2rem] p-6 shadow-sm">
                    <h3 class="font-bold text-slate-800 mb-4">Assign Attributes</h3>
                    <form action="{{ route('admin.attributes.attachToCategory') }}" method="POST" class="space-y-6">
                        @csrf
                        <div>
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Category <span class="text-red-500">*</span></label>
                            <select name="category_id" required
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary transition-all bg-white cursor-pointer hover:border-primary/40">
                                <option value="">Choose category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->getTranslation('name','en') }}</option>
                                    @foreach($cat->children as $child)
                                        <option value="{{ $child->id }}">&nbsp;&nbsp;↳ {{ $child->getTranslation('name','en') }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-1.5">Available Attributes</label>
                            <div class="grid grid-cols-1 gap-3 max-h-64 overflow-y-auto rounded-xl border border-slate-200 bg-white p-4">
                                @forelse($attributes as $attribute)
                                    <label class="flex items-center gap-3 rounded-xl border border-transparent bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-200 cursor-pointer">
                                        <input type="checkbox" name="attribute_ids[]" value="{{ $attribute->id }}" class="h-4 w-4 accent-primary rounded">
                                        <div>
                                            <span>{{ $attribute->name }}</span>
                                            <p class="text-[11px] text-slate-400 mt-1">{{ ucfirst($attribute->type) }} field</p>
                                        </div>
                                    </label>
                                @empty
                                    <p class="text-sm text-slate-400 text-center py-4">No attributes created yet.</p>
                                @endforelse
                            </div>
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/30 transition hover:bg-primary-dark">
                            Save Category Attributes
                        </button>
                    </form>
                </div>

                {{-- Assignments List --}}
                <div class="space-y-4">
                    <h3 class="font-bold text-slate-800 mb-2">Current Assignments</h3>
                    @foreach($categories as $cat)
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">{{ $cat->getTranslation('name', 'en') }}</p>
                                    <p class="text-[10px] uppercase tracking-wider text-slate-400 mt-0.5">Parent category</p>
                                </div>
                                <span class="inline-flex rounded-full bg-primary/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.1em] text-primary">{{ $cat->attributes->count() }} attributes</span>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse($cat->attributes as $attribute)
                                    <span class="rounded-full bg-slate-50 px-3 py-1 text-[11px] font-semibold text-slate-700 border border-slate-200">{{ $attribute->name }}</span>
                                @empty
                                    <span class="text-[11px] text-slate-400 italic">No attributes assigned yet.</span>
                                @endforelse
                            </div>

                            @if($cat->children->isNotEmpty())
                                <div class="mt-4 space-y-3">
                                    @foreach($cat->children as $child)
                                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                            <p class="text-sm font-semibold text-slate-900">↳ {{ $child->getTranslation('name', 'en') }}</p>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @forelse($child->attributes as $attribute)
                                                    <span class="rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-slate-700 border border-slate-200">{{ $attribute->name }}</span>
                                                @empty
                                                    <span class="text-[11px] text-slate-400 italic">No attributes assigned.</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="pt-4 flex justify-between items-center border-t border-slate-100 mt-8">
                <button type="button" @click="step = 2" class="text-slate-500 hover:text-slate-800 font-bold text-sm flex items-center gap-2 transition-colors">
                    <i class="bi bi-arrow-left"></i> Previous
                </button>
            </div>
        </div>

    </div>
</div>

@endsection
