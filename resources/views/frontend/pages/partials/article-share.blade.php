{{-- Share buttons for an article: Facebook, WhatsApp and copy link. --}}
<div class="flex items-center gap-2" x-data="{ copied: false }">
    <span class="mr-1 text-xs font-medium text-slate-500">{{ __('Share') }}</span>
    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($url) }}" target="_blank" rel="noopener" aria-label="{{ __('Share on Facebook') }}"
       class="flex size-9 items-center justify-center rounded-full border border-[var(--sf-border)] text-slate-600 hover:border-[#1877f2] hover:text-[#1877f2] sf-focus"><i class="bi bi-facebook" aria-hidden="true"></i></a>
    <a href="https://wa.me/?text={{ $shareText }}" target="_blank" rel="noopener" aria-label="{{ __('Share on WhatsApp') }}"
       class="flex size-9 items-center justify-center rounded-full border border-[var(--sf-border)] text-slate-600 hover:border-[#25d366] hover:text-[#128c7e] sf-focus"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
    <button type="button" @click="navigator.clipboard?.writeText(@js($url)); copied = true; setTimeout(() => copied = false, 2000)"
            :aria-label="copied ? @js(__('Link copied')) : @js(__('Copy link'))"
            class="flex size-9 items-center justify-center rounded-full border border-[var(--sf-border)] text-slate-600 hover:border-brand-600 hover:text-brand-700 sf-focus">
        <i class="bi" :class="copied ? 'bi-check2 text-emerald-600' : 'bi-link-45deg'" aria-hidden="true"></i>
    </button>
</div>
