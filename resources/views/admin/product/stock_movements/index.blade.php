@extends('admin.layouts')
@section('title', 'Stock Movements')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-800">Stock Movements</h1>
            <p class="text-sm text-slate-500 mt-1">View recent stock changes and perform manual adjustments.</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="text-sm font-bold text-slate-600 bg-white border border-slate-200 px-4 py-2 rounded-xl hover:bg-slate-50">Back to Products</a>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <h2 class="text-lg font-black text-slate-900 mb-3">Manual Stock Adjustment</h2>
            <form action="{{ route('admin.products.stock_movements.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-black uppercase tracking-[0.2em] text-slate-600 mb-2">Variant</label>
                    <select id="variant-select" name="variant_id" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                        <option value="">Select variant</option>
                        @foreach($variants as $v)
                            <option value="{{ $v->id }}" data-stock="{{ $v->stock }}">{{ $v->product->getTranslation('name','en') }} — {{ $v->sku ?? 'no-sku' }} (stock: {{ $v->stock }})</option>
                        @endforeach
                    </select>
                    <p id="variant-current-stock" class="text-sm text-slate-500 mt-2">Current stock: —</p>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-[0.2em] text-slate-600 mb-2">Change (use negative to decrease)</label>
                    <input type="number" name="change" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700" placeholder="e.g. 10 or -5">
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-[0.2em] text-slate-600 mb-2">Reason</label>
                    <input type="text" name="reason" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700" placeholder="e.g. Stock take correction">
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-[0.2em] text-slate-600 mb-2">Type</label>
                    <select name="type" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                        <option value="adjustment">Adjustment</option>
                        <option value="correction">Correction</option>
                        <option value="return">Return</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-xl">Apply Adjustment</button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <h2 class="text-lg font-black text-slate-900 mb-4">Recent Movements</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-500 uppercase tracking-wider">
                            <th class="px-3 py-2">When</th>
                            <th class="px-3 py-2">Product / SKU</th>
                            <th class="px-3 py-2">Change</th>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2">Reason</th>
                            <th class="px-3 py-2">By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $m)
                            <tr class="border-t border-slate-100">
                                <td class="px-3 py-3 text-slate-500">{{ $m->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-3 py-3">
                                    <div class="font-semibold">{{ optional($m->variant->product)->getTranslation('name','en') }}</div>
                                    <div class="text-xs text-slate-400">{{ $m->variant->sku ?? '—' }}</div>
                                </td>
                                <td class="px-3 py-3">{{ $m->change > 0 ? '+' . $m->change : $m->change }}</td>
                                <td class="px-3 py-3 text-slate-600">{{ $m->type }}</td>
                                <td class="px-3 py-3 text-slate-600">{{ $m->reason }}</td>
                                <td class="px-3 py-3 text-slate-600">{{ $m->admin->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-6 text-center text-slate-400">No stock movements yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $movements->links() }}</div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('variant-select');
        const stockEl = document.getElementById('variant-current-stock');
        const changeInput = document.querySelector('input[name="change"]');

        function updateStockDisplay() {
            const opt = select.options[select.selectedIndex];
            if (!opt || !opt.value) {
                stockEl.textContent = 'Current stock: —';
                return;
            }
            const stock = opt.getAttribute('data-stock') || '0';
            stockEl.textContent = 'Current stock: ' + stock;
        }

        select.addEventListener('change', updateStockDisplay);
        updateStockDisplay();

        // simple validation: warn when decreasing below zero (client-side only)
        changeInput.addEventListener('input', function () {
            const delta = parseInt(this.value || 0, 10);
            const opt = select.options[select.selectedIndex];
            if (!opt || !opt.value) return;
            const stock = parseInt(opt.getAttribute('data-stock') || '0', 10);
            if (stock + delta < 0) {
                this.setCustomValidity('This adjustment would make stock negative.');
            } else {
                this.setCustomValidity('');
            }
        });
    });
</script>
@endpush
@endsection
