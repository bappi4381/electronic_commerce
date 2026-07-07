@extends('admin.layouts')
@section('title', 'Bulk Product Import')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Bulk Product Import</h1>
            <p class="text-sm text-slate-500 mt-1">Upload an Excel or CSV file to import products and variants in bulk.</p>
        </div>
        <a href="{{ route('admin.products.index') }}"
            class="flex items-center gap-2 text-sm font-bold text-slate-600 bg-white border border-slate-200 px-4 py-2 rounded-xl hover:bg-slate-50 transition-all">
            <i class="bi bi-arrow-left"></i> Back to Products
        </a>
    </div>

    @if(session('success'))
        <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 rounded-2xl text-sm font-semibold">
            <i class="bi bi-check-circle-fill text-lg"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
            @foreach($errors->all() as $e)
                <p class="text-red-600 text-sm font-semibold">• {{ $e }}</p>
            @endforeach
        </div>
    @endif

    {{-- How It Works --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h2 class="text-base font-black text-slate-800 mb-4 flex items-center gap-2">
            <i class="bi bi-info-circle text-primary"></i> How It Works
        </h2>
        <ol class="space-y-3 text-sm text-slate-600">
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-black flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                <span>Download the <strong>CSV Template</strong> below and fill it in with your product data.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-black flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                <span>Each <strong>row = one variant</strong>. If a product (same <code class="bg-slate-100 px-1 rounded">name_en</code>) has multiple variants (e.g. Red-M, Red-L), add one row per variant.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-black flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                <span>Columns like <code class="bg-slate-100 px-1 rounded">attribute_color</code> or <code class="bg-slate-100 px-1 rounded">attribute_size</code> are dynamic — add any attribute you need.</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-black flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                <span>Upload the completed file and click <strong>Import</strong>. Products will be created automatically.</span>
            </li>
        </ol>

        <a href="{{ route('admin.products.import.template') }}"
            class="inline-flex items-center gap-2 mt-5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm px-5 py-3 rounded-xl transition-all">
            <i class="bi bi-file-earmark-arrow-down text-lg text-emerald-600"></i>
            Download CSV Template
        </a>
    </div>

    {{-- Column Reference --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h2 class="text-base font-black text-slate-800 mb-4">Column Reference</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-left">
                        <th class="px-4 py-2.5 font-bold text-slate-600 text-xs uppercase tracking-wider rounded-l-lg">Column</th>
                        <th class="px-4 py-2.5 font-bold text-slate-600 text-xs uppercase tracking-wider">Required</th>
                        <th class="px-4 py-2.5 font-bold text-slate-600 text-xs uppercase tracking-wider rounded-r-lg">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach([
                        ['name_en', true, 'Product name in English. Groups rows into one product.'],
                        ['name_bn', false, 'Product name in Bengali.'],
                        ['description_en', false, 'Product description (English). Taken from first row.'],
                        ['description_bn', false, 'Product description (Bengali). Taken from first row.'],
                        ['category', false, 'Category name. Created automatically if not found.'],
                        ['price', true, 'Base price of the product (e.g. 500).'],
                        ['discount', false, 'Discount percentage (0–100). Default: 0.'],
                        ['brand', false, 'Brand name.'],
                        ['model', false, 'Model name.'],
                        ['sku', false, 'Unique SKU for this variant.'],
                        ['variant_price', false, 'Override price for this specific variant. Leave blank to use base price.'],
                        ['stock', true, 'Stock quantity for this specific variant.'],
                        ['attribute_color', false, 'Value for the "Color" attribute (e.g. Red, Blue).'],
                        ['attribute_size', false, 'Value for the "Size" attribute (e.g. M, L, XL).'],
                        ['attribute_*', false, 'Any attribute column — format: attribute_{attribute_name}.'],
                    ] as [$col, $req, $desc])
                    <tr>
                        <td class="px-4 py-3"><code class="bg-slate-100 text-slate-700 font-bold text-xs px-2 py-0.5 rounded">{{ $col }}</code></td>
                        <td class="px-4 py-3">
                            @if($req)
                                <span class="text-red-500 font-bold text-xs">Required</span>
                            @else
                                <span class="text-slate-400 text-xs">Optional</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs">{{ $desc }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Upload Form --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h2 class="text-base font-black text-slate-800 mb-5 flex items-center gap-2">
            <i class="bi bi-cloud-upload text-primary"></i> Upload File
        </h2>
        <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label
                class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-2xl p-10 cursor-pointer hover:border-primary/50 hover:bg-primary/5 transition-all group"
                id="drop-zone">
                <i class="bi bi-file-earmark-excel text-5xl text-slate-300 group-hover:text-primary transition-colors mb-3"></i>
                <p class="font-bold text-slate-600 group-hover:text-primary transition-colors" id="file-label">
                    Drag & drop or click to upload
                </p>
                <p class="text-xs text-slate-400 mt-1">Supports: .xlsx, .xls, .csv — Max 10MB</p>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="hidden" id="fileInput"
                    onchange="updateLabel(this)">
            </label>

            <button type="submit"
                class="w-full mt-5 bg-primary hover:bg-primary-dark text-white font-black py-3.5 rounded-xl transition-all text-sm tracking-wide flex items-center justify-center gap-2">
                <i class="bi bi-upload"></i> Start Import
            </button>
        </form>
    </div>

</div>

<script>
function updateLabel(input) {
    const label = document.getElementById('file-label');
    if (input.files && input.files[0]) {
        label.textContent = input.files[0].name;
    }
}
</script>
@endsection
