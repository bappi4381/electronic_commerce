{{--
    Table card with a GET toolbar (search + filters slot), click-to-sort headers, pagination and an empty state.
    Rows go in the default slot as <tr>; give each <td> a data-label so it renders as a card row on mobile,
    and data-primary on the cell that should act as the card title. Sorting reorders the current page;
    put a data-sort value on cells whose text does not sort naturally.

    :columns  list of ['label' => 'Total', 'sortable' => true, 'align' => 'right', 'srOnly' => false, 'class' => '']
    search    query parameter used by the controller (e.g. "search" or "q"); false hides the box
    preserve  query parameters to keep when the toolbar form is submitted
--}}
@props([
    'columns' => [],
    'paginator' => null,
    'search' => false,
    'searchPlaceholder' => 'Search…',
    'action' => null,
    'preserve' => [],
    'emptyTitle' => 'Nothing here yet',
    'emptyDescription' => null,
    'emptyIcon' => 'bi-inbox',
])

@php
    $action ??= url()->current();
    $query = request()->except(['page']);
    $filtered = collect($query)->except($preserve)->filter(fn ($v) => $v !== null && $v !== '' && $v !== [])->isNotEmpty();
    $hasToolbar = $search || isset($filters) || isset($toolbar);
    $align = ['right' => 'text-right', 'center' => 'text-center', 'left' => 'text-left'];
@endphp

<div x-data="dataTable()" {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900']) }}>
    @if($hasToolbar)
        <div class="flex flex-col gap-3 border-b border-gray-200 p-4 lg:flex-row lg:items-center lg:justify-between dark:border-gray-800">
            @if($search || isset($filters))
                <form method="GET" action="{{ $action }}" role="search" class="flex flex-1 flex-wrap items-center gap-2">
                    @foreach($preserve as $key)
                        @if(request()->filled($key))<input type="hidden" name="{{ $key }}" value="{{ request($key) }}">@endif
                    @endforeach
                    @if($search)
                        <div class="relative min-w-48 flex-1 sm:max-w-xs">
                            <label for="table-search" class="sr-only">Search</label>
                            <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                            <input type="search" id="table-search" name="{{ $search }}" value="{{ request($search) }}" placeholder="{{ $searchPlaceholder }}"
                                   class="h-10 w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 text-sm text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                    @endif
                    {{ $filters ?? '' }}
                    <x-admin.button type="submit" variant="secondary" :loading="false">Apply</x-admin.button>
                    @if($filtered)
                        <x-admin.button variant="ghost" :href="$action . ($preserve ? '?' . http_build_query(request()->only($preserve)) : '')" icon="bi-x-lg">Reset</x-admin.button>
                    @endif
                </form>
            @endif
            @isset($toolbar)
                <div class="flex flex-wrap items-center gap-2">{{ $toolbar }}</div>
            @endisset
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="admin-table min-w-full text-sm [&_td]:px-4 [&_td]:py-3 [&_td]:align-middle [&_td]:text-gray-700 dark:[&_td]:text-gray-300">
            @if($columns)
                <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/40">
                    <tr>
                        @foreach($columns as $index => $column)
                            @php($column = is_string($column) ? ['label' => $column] : $column)
                            <th scope="col" @if(!empty($column['sortable'])) :aria-sort="ariaSort({{ $index }})" @endif
                                class="whitespace-nowrap px-4 py-3 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 {{ $align[$column['align'] ?? 'left'] }} {{ $column['class'] ?? '' }}">
                                @if(!empty($column['sortable']))
                                    <button type="button" @click="sort({{ $index }})" class="group inline-flex items-center gap-1 rounded uppercase focus-ring hover:text-gray-900 dark:hover:text-gray-100">
                                        {{ $column['label'] }}
                                        <i class="bi text-[10px]" aria-hidden="true"
                                           :class="sortIndex === {{ $index }} ? (sortDir === 'asc' ? 'bi-chevron-up' : 'bi-chevron-down') : 'bi-chevron-expand opacity-0 group-hover:opacity-100'"></i>
                                    </button>
                                @else
                                    <span @class(['sr-only' => !empty($column['srOnly'])])>{{ $column['label'] }}</span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody x-ref="body" class="divide-y divide-gray-200 dark:divide-gray-800">
                @if($slot->hasActualContent())
                    {{ $slot }}
                @else
                    <tr>
                        <td colspan="{{ max(count($columns), 1) }}" class="admin-table-empty">
                            @isset($empty)
                                {{ $empty }}
                            @else
                                <x-admin.empty-state :title="$filtered ? 'No results match your filters' : $emptyTitle"
                                                     :description="$filtered ? 'Try a different search or reset the filters.' : $emptyDescription"
                                                     :icon="$filtered ? 'bi-search' : $emptyIcon" />
                            @endisset
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if($paginator instanceof \Illuminate\Contracts\Pagination\Paginator && ($paginator->hasPages() || $paginator->count()))
        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
            {{ $paginator->onEachSide(1)->links('admin.partials.pagination') }}
        </div>
    @endif
</div>
