{{--
    Single global confirmation dialog (rendered once by the layout). Replaces window.confirm():
        <form method="POST" data-confirm="This cannot be undone." data-confirm-title="Delete product?" data-confirm-button="Delete">
    The attributes can also sit on a submit button. From JS: await confirmAction({ title, message, tone }).
--}}
<div x-data x-show="$store.confirm.open" x-cloak
     x-on:keydown.escape.window="$store.confirm.open && $store.confirm.settle(false)"
     class="fixed inset-0 z-[60] overflow-y-auto" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-message">
    <div x-show="$store.confirm.open" x-transition.opacity class="fixed inset-0 bg-gray-950/50 backdrop-blur-[2px]" @click="$store.confirm.settle(false)" aria-hidden="true"></div>

    <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
        <div x-show="$store.confirm.open" x-transition
             x-effect="if ($store.confirm.open) $nextTick(() => $refs.cancel.focus())"
             @keydown.tab="if (document.activeElement === $refs.ok && !$event.shiftKey) { $event.preventDefault(); $refs.cancel.focus() } else if (document.activeElement === $refs.cancel && $event.shiftKey) { $event.preventDefault(); $refs.ok.focus() }"
             class="relative w-full rounded-xl bg-white p-6 shadow-xl ring-1 ring-gray-200 sm:max-w-md dark:bg-gray-900 dark:ring-gray-800">
            <div class="flex gap-4">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full"
                      :class="$store.confirm.tone === 'danger' ? 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400' : 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400'" aria-hidden="true">
                    <i class="bi" :class="$store.confirm.tone === 'danger' ? 'bi-exclamation-triangle' : 'bi-question-circle'"></i>
                </span>
                <div class="min-w-0">
                    <h2 id="confirm-title" class="text-base font-semibold text-gray-900 dark:text-white" x-text="$store.confirm.title"></h2>
                    <p id="confirm-message" class="mt-1 text-sm text-gray-600 dark:text-gray-400" x-text="$store.confirm.message" x-show="$store.confirm.message"></p>
                </div>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-admin.button variant="secondary" x-ref="cancel" @click="$store.confirm.settle(false)"><span x-text="$store.confirm.cancelText"></span></x-admin.button>
                <button type="button" x-ref="ok" @click="$store.confirm.settle(true)"
                        class="inline-flex h-10 items-center justify-center rounded-lg px-4 text-sm font-medium text-white shadow-xs transition-colors focus-ring"
                        :class="$store.confirm.tone === 'danger' ? 'bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-400' : 'bg-brand-600 hover:bg-brand-700 dark:bg-brand-500 dark:text-gray-950 dark:hover:bg-brand-400'"
                        x-text="$store.confirm.confirmText"></button>
            </div>
        </div>
    </div>
</div>
