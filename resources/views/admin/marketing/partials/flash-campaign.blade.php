{{--
    Flash deal campaign fields, shared by Settings › Campaigns and Marketing. Must sit inside a form that saves
    flash_deal_* settings; the "End campaign" button submits the separate #flash-end-form (render it outside that form).
--}}
@php
    $canEdit ??= true;
    $titleRequired ??= false;
    $flashSale = app(\App\Services\FlashSale::class);
    $flashSale->syncIfDue();
    $status = $flashSale->status();
    $startsAt = $flashSale->startsAt();
    $endsAt = $flashSale->endsAt();
    $flagged = $flashSale->flaggedCount();
    $tz = config('store.timezone', 'Asia/Dhaka');
    [$statusLabel, $statusTone, $statusText] = match ($status) {
        \App\Services\FlashSale::STATUS_SCHEDULED => ['Scheduled', 'blue', 'Starts ' . $startsAt->diffForHumans() . ' (' . $startsAt->format('d M, h:i A') . ').'],
        \App\Services\FlashSale::STATUS_RUNNING => ['Running', 'green', $endsAt ? 'Ends ' . $endsAt->diffForHumans() . ' (' . $endsAt->format('d M, h:i A') . ').' : 'No end time: runs until you end it.'],
        \App\Services\FlashSale::STATUS_ENDED => ['Ended', 'gray', 'Ended ' . $endsAt->diffForHumans() . '.' . ($flagged ? ' Turn on automatic removal or use “End campaign now” to take the products out.' : ' Set new times to run another campaign.')],
        default => ['Not running', 'gray', 'Tick "Flash" on products in Marketing to add them.'],
    };
@endphp

<div class="space-y-5">
    <div class="flex flex-wrap items-center gap-3 rounded-lg bg-gray-50 px-4 py-3 text-sm dark:bg-gray-800/60">
        <x-admin.status-badge :tone="$statusTone" :label="$statusLabel" />
        <span class="text-gray-600 dark:text-gray-300">{{ $statusText }}</span>
        <span class="ml-auto text-xs text-gray-500 dark:text-gray-400">{{ $flagged }} {{ \Illuminate\Support\Str::plural('product', $flagged) }} in the campaign</span>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-admin.input name="flash_deal_title" label="Campaign title" :required="$titleRequired" :optional="!$titleRequired" maxlength="255"
                       :value="\App\Models\Setting::get('flash_deal_title')" placeholder="e.g. Eid Special" :disabled="!$canEdit" />
        <x-admin.input name="flash_deal_start_time" type="datetime-local" label="Starts at" optional
                       :value="$startsAt?->format('Y-m-d\TH:i')" :disabled="!$canEdit" hint="Empty = starts immediately." />
        <x-admin.input name="flash_deal_end_time" type="datetime-local" label="Ends at" optional
                       :value="$endsAt?->format('Y-m-d\TH:i')" :disabled="!$canEdit" :hint="'Times are ' . $tz . ' time.'" />
    </div>

    <x-admin.toggle name="flash_deal_auto_end" label="Remove products automatically when it ends"
                    description="At the end time, flash prices switch back to regular prices and products leave the campaign. Sold-out products drop out of the deal on their own."
                    :checked="$flashSale->autoEnd()" />

    @if($canEdit && $flagged > 0)
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
            <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <input type="checkbox" name="remove_discounts" value="1" checked form="flash-end-form"
                       class="size-4 rounded border-gray-300 text-brand-600 focus:ring-2 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-800">
                Also remove discounts that were only for this campaign
            </label>
            <x-admin.button type="submit" form="flash-end-form" variant="danger" size="sm" icon="bi-stop-circle">End campaign now</x-admin.button>
        </div>
    @endif
</div>
