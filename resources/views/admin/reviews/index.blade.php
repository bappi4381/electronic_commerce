@extends('admin.layouts')
@section('title', 'Reviews')
@section('ui-v2', '1')

@section('content')
@php
    $tabs = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
    $canEdit = auth()->user()->can('reviews.edit');
@endphp

<div x-data="{ selected: [] }">
    <x-admin.page-header title="Reviews" description="Reviews show on the storefront only after approval. Approved ratings feed each product's average." />

    <nav aria-label="Filter by status" class="mb-4 flex flex-wrap gap-2">
        @foreach($tabs as $key => $name)
            @php($active = $status === $key)
            <a href="{{ route('admin.reviews.index', ['status' => $key]) }}" @if($active) aria-current="page" @endif
               @class(['inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium focus-ring',
                       'border-gray-900 bg-gray-900 text-white dark:border-white dark:bg-white dark:text-gray-900' => $active,
                       'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:text-white' => !$active])>
                {{ $name }} <span class="tabular-nums opacity-60">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    @if($canEdit)
        <form method="POST" action="{{ route('admin.reviews.bulk') }}" x-show="selected.length" x-cloak
              class="sticky top-20 z-30 mb-4 flex flex-wrap items-center gap-3 rounded-xl bg-gray-900 p-3 text-white shadow-lg dark:bg-gray-800 dark:ring-1 dark:ring-gray-700">
            @csrf
            <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
            <span class="px-1 text-sm font-medium"><span x-text="selected.length"></span> selected</span>
            <x-admin.button type="submit" size="sm" name="action" value="approve" icon="bi-check-lg">Approve</x-admin.button>
            <x-admin.button type="submit" size="sm" variant="danger" name="action" value="reject" icon="bi-x-lg">Reject</x-admin.button>
            <button type="button" @click="selected = []" class="h-8 rounded-lg px-3 text-xs font-medium text-white/80 hover:bg-white/10 focus-ring">Clear</button>
        </form>
    @endif

    <x-admin.data-table :paginator="$reviews" search="search" search-placeholder="Text, customer or product…" :preserve="['status']"
        :columns="array_values(array_filter([
            $canEdit ? ['label' => 'Select', 'srOnly' => true, 'class' => 'w-10'] : null,
            ['label' => 'Review'],
            ['label' => 'Actions', 'srOnly' => true, 'align' => 'right'],
        ]))"
        :empty-title="'No ' . $status . ' reviews'" empty-description="Reviews customers leave on products appear here." empty-icon="bi-star">
        <x-slot:filters>
            <label for="filter-rating" class="sr-only">Rating</label>
            <select id="filter-rating" name="rating" onchange="this.form.requestSubmit()"
                    class="h-10 rounded-lg border border-gray-300 bg-white pl-3 pr-9 text-sm text-gray-700 shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                <option value="">Any rating</option>
                @for($i = 5; $i >= 1; $i--)
                    <option value="{{ $i }}" @selected(($filters['rating'] ?? null) == $i)>{{ $i }} {{ \Illuminate\Support\Str::plural('star', $i) }}</option>
                @endfor
            </select>
        </x-slot:filters>

        @foreach($reviews as $review)
            <tr class="align-top transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40" :class="selected.includes('{{ $review->id }}') && 'bg-brand-50/60 dark:bg-brand-500/5'">
                @if($canEdit)
                    <td class="w-10 !align-top">
                        <label class="sr-only" for="review-{{ $review->id }}">Select review by {{ $review->user?->name }}</label>
                        <input id="review-{{ $review->id }}" type="checkbox" value="{{ $review->id }}" x-model="selected"
                               class="mt-1 size-4 rounded border-gray-300 text-brand-600 focus:ring-2 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-800">
                    </td>
                @endif
                <td data-primary class="!align-top md:w-full md:max-w-0">
                    <div class="min-w-0 space-y-2 font-normal">
                        <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $review->user?->name ?? 'Deleted customer' }}</span>
                            @if($review->user && $review->user->status !== 'active')
                                <x-admin.status-badge status="blocked" label="Blocked" />
                            @endif
                            <span class="text-gray-500 dark:text-gray-400">on</span>
                            @if($review->product)
                                <a href="{{ route('admin.products.show', $review->product_id) }}" class="truncate rounded font-medium text-brand-600 hover:underline focus-ring dark:text-brand-400">{{ $review->product->name }}</a>
                            @else
                                <span class="text-gray-400">deleted product</span>
                            @endif
                        </p>
                        <p class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                            @if($review->rating)
                                <span class="text-sm text-yellow-500" aria-label="{{ $review->rating }} out of 5 stars">{{ str_repeat('★', $review->rating) }}<span class="text-gray-300 dark:text-gray-600">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                            @else
                                <span>No rating</span>
                            @endif
                            <time datetime="{{ $review->created_at->toIso8601String() }}">{{ $review->created_at->format('d M Y, h:i A') }}</time>
                            <x-admin.status-badge :status="$review->status" />
                        </p>
                        <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $review->comment }}</p>
                        @if($review->moderated_at)
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($review->status) }} {{ $review->moderated_at->diffForHumans() }}{{ $review->moderator ? ' by ' . $review->moderator->name : '' }}</p>
                        @endif
                    </div>
                </td>
                <td class="whitespace-nowrap text-right !align-top">
                    <div class="inline-flex items-center gap-1">
                        @if($canEdit)
                            @if($review->status !== 'approved')
                                <form method="POST" action="{{ route('admin.reviews.approve', $review->id) }}">
                                    @csrf @method('PATCH')
                                    <x-admin.button type="submit" variant="secondary" size="sm" icon="bi-check-lg">Approve</x-admin.button>
                                </form>
                            @endif
                            @if($review->status !== 'rejected')
                                <form method="POST" action="{{ route('admin.reviews.reject', $review->id) }}">
                                    @csrf @method('PATCH')
                                    <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-x-lg">Reject</x-admin.button>
                                </form>
                            @endif
                        @endif
                        @can('reviews.delete')
                            <form method="POST" action="{{ route('admin.reviews.destroy', $review->id) }}"
                                  data-confirm="The review is removed permanently and the product rating is recalculated." data-confirm-title="Delete this review?" data-confirm-button="Delete">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" aria-label="Delete review" />
                            </form>
                        @endcan
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.data-table>
</div>
@endsection
