@extends('admin.layouts')
@section('title', 'Attribute Manager')

@section('content')
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight">Attribute Manager</h1>
                <p class="text-sm text-slate-500 mt-1">Manage product attributes like Color, Size, RAM, Storage etc.</p>
            </div>
        </div>

        {{-- Alert Messages --}}
        @if(session('success'))
            <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 rounded-2xl text-sm font-semibold">
                <i class="bi bi-check-circle-fill text-lg"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl text-sm font-semibold">
                <i class="bi bi-exclamation-circle-fill text-lg"></i> {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-[420px_minmax(0,1fr)] gap-6">

            <div class="space-y-6">
                <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6">
                    <div class="flex items-start justify-between gap-4 mb-6">
                        <div>
                            <h2 class="flex items-center gap-2 text-xl font-black text-slate-900">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">1</span>
                                Create Attribute
                            </h2>
                            <p class="text-sm text-slate-500 mt-1">Add new product attributes that can be used across categories.</p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-primary/10 px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-primary">Quick Add</span>
                    </div>

                    <form action="{{ route('admin.attributes.store') }}" method="POST" class="space-y-5">
                        @csrf
                        <div>
                            <label class="block text-xs font-black uppercase tracking-[0.2em] text-slate-600 mb-2">Attribute Name</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition"
                                placeholder="e.g. Color, Size, Storage" required>
                            @error('name')<p class="text-red-500 text-xs mt-2">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-[0.2em] text-slate-600 mb-2">Input Type</label>
                            <select name="type"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary transition">
                                <option value="select">Dropdown (Select)</option>
                                <option value="radio">Radio Button</option>
                                <option value="text">Text Input</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <input type="checkbox" name="is_filterable" id="is_filterable" value="1" checked class="h-5 w-5 accent-primary rounded">
                            <label for="is_filterable" class="text-sm font-semibold text-slate-700">Show attribute in shop filter sidebar</label>
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-primary px-5 py-3 text-sm font-bold text-white shadow-lg shadow-primary/10 transition hover:bg-primary-dark">
                            <i class="bi bi-plus-lg"></i> Create Attribute
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6">
                    <div class="mb-6">
                        <h2 class="flex items-center gap-2 text-xl font-black text-slate-900">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">3</span>
                            Map Attributes to Category
                        </h2>
                        <p class="text-sm text-slate-500 mt-1">Choose which attributes should be available for each category or subcategory.</p>
                    </div>

                    <form action="{{ route('admin.attributes.attachToCategory') }}" method="POST" class="space-y-5">
                        @csrf

                        <div>
                            <label class="block text-xs font-black uppercase tracking-[0.2em] text-slate-600 mb-2">Category</label>
                            <select name="category_id" required
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary transition">
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
                            <label class="block text-xs font-black uppercase tracking-[0.2em] text-slate-600 mb-2">Available Attributes</label>
                            <div class="grid grid-cols-1 gap-3 max-h-64 overflow-y-auto rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                @foreach($attributes as $attribute)
                                    <label class="flex items-center gap-3 rounded-2xl border border-transparent bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-200 cursor-pointer">
                                        <input type="checkbox" name="attribute_ids[]" value="{{ $attribute->id }}" class="h-4 w-4 accent-primary rounded">
                                        <div>
                                            <span>{{ $attribute->name }}</span>
                                            <p class="text-[11px] text-slate-400 mt-1">{{ ucfirst($attribute->type) }} field</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/10 transition hover:bg-slate-800">
                            Save Category Attributes
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6">
                    <div class="mb-6">
                        <h2 class="text-xl font-black text-slate-900">Current Assignments</h2>
                        <p class="text-sm text-slate-500 mt-1">Review which attributes are linked to each category and subcategory.</p>
                    </div>

                    <div class="space-y-4">
                        @foreach($categories as $cat)
                            <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $cat->getTranslation('name', 'en') }}</p>
                                        <p class="text-xs text-slate-500">Parent category</p>
                                    </div>
                                    <span class="inline-flex rounded-full bg-primary/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.2em] text-primary">{{ $cat->attributes->count() }} attributes</span>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    @forelse($cat->attributes as $attribute)
                                        <span class="rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-slate-700 border border-slate-200">{{ $attribute->name }}</span>
                                    @empty
                                        <span class="text-[11px] text-slate-400">No attributes assigned yet.</span>
                                    @endforelse
                                </div>

                                @if($cat->children->isNotEmpty())
                                    <div class="mt-4 space-y-3">
                                        @foreach($cat->children as $child)
                                            <div class="rounded-[1.5rem] border border-slate-200 bg-white p-4">
                                                <p class="text-sm font-semibold text-slate-900">↳ {{ $child->getTranslation('name', 'en') }}</p>
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    @forelse($child->attributes as $attribute)
                                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700">{{ $attribute->name }}</span>
                                                    @empty
                                                        <span class="text-[11px] text-slate-400">No attributes assigned.</span>
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
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6">
                    <div class="mb-6">
                        <h2 class="flex items-center gap-2 text-xl font-black text-slate-900">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">2</span>
                            Attribute Library (Add Values)
                        </h2>
                        <p class="text-sm text-slate-500 mt-1">Manage all attributes, update settings, and add values in one place.</p>
                    </div>

                    @forelse($attributes as $attribute)
                        <div class="mb-4 overflow-hidden rounded-[1.75rem] border border-slate-200 bg-slate-50 shadow-sm" x-data="{ editOpen: false, addValueOpen: false }">
                            <div class="flex flex-col gap-4 px-6 py-5 md:flex-row md:items-center md:justify-between md:px-8 md:py-6 bg-white">
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

                            <div class="grid gap-4 px-6 py-5 md:grid-cols-[1fr_auto] md:items-start md:px-8">
                                <div class="space-y-4">
                                    <div x-show="editOpen" x-cloak x-transition class="rounded-[1.5rem] border border-primary/10 bg-primary/5 p-4">
                                        <form action="{{ route('admin.attributes.update', $attribute) }}" method="POST" class="grid gap-4 sm:grid-cols-2">
                                            @csrf @method('PUT')
                                            <div>
                                                <label class="text-xs font-black uppercase tracking-[0.2em] text-slate-600">Name</label>
                                                <input type="text" name="name" value="{{ $attribute->name }}"
                                                    class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition" required>
                                            </div>
                                            <div>
                                                <label class="text-xs font-black uppercase tracking-[0.2em] text-slate-600">Type</label>
                                                <select name="type"
                                                    class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary transition">
                                                    <option value="select" {{ $attribute->type === 'select' ? 'selected' : '' }}>Select</option>
                                                    <option value="radio" {{ $attribute->type === 'radio' ? 'selected' : '' }}>Radio</option>
                                                    <option value="text" {{ $attribute->type === 'text' ? 'selected' : '' }}>Text</option>
                                                </select>
                                            </div>
                                            <div class="sm:col-span-2 flex items-center justify-between gap-3 mt-2">
                                                <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 cursor-pointer">
                                                    <input type="checkbox" name="is_filterable" value="1" {{ $attribute->is_filterable ? 'checked' : '' }} class="h-4 w-4 accent-primary rounded">
                                                    Filterable
                                                </label>
                                                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-primary-dark shadow-sm">
                                                    Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <div x-show="addValueOpen" x-cloak x-transition class="rounded-[1.5rem] border border-emerald-100 bg-emerald-50 p-4">
                                        <form action="{{ route('admin.attributes.values.store', $attribute) }}" method="POST" class="grid gap-4 md:grid-cols-[1.5fr_minmax(140px,auto)] md:items-end">
                                            @csrf
                                            <div>
                                                <label class="text-xs font-black uppercase tracking-[0.2em] text-slate-600">Add value</label>
                                                <input type="text" name="value"
                                                    class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary transition"
                                                    placeholder="e.g. Red, XL, 128GB" required>
                                            </div>
                                            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-700">
                                                Add Value
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <button @click.prevent="addValueOpen = !addValueOpen"
                                        class="w-full rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 transition hover:bg-emerald-100">
                                        <i class="bi bi-plus-lg mr-1"></i> <span x-text="addValueOpen ? 'Cancel' : 'Add Value'"></span>
                                    </button>
                                    <button @click.prevent="editOpen = !editOpen"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-slate-300">
                                        <span x-text="editOpen ? 'Hide' : 'Edit'"></span> Attribute
                                    </button>
                                    <form action="{{ route('admin.attributes.destroy', $attribute) }}" method="POST" class="inline-flex w-full">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-full rounded-2xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-100">
                                            Delete Attribute
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6">
                                @if($attribute->values->isEmpty())
                                    <p class="text-sm text-slate-400">No values yet. Click "Add Value" to add possible options.</p>
                                @else
                                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach($attribute->values as $value)
                                            <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
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
                    </div>
                @empty
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-12 text-center shadow-sm">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                            <i class="bi bi-tags text-3xl"></i>
                        </div>
                        <p class="text-lg font-bold text-slate-800">No attributes found yet.</p>
                        <p class="mt-2 text-sm text-slate-500">Create your first attribute with the form on the left.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
