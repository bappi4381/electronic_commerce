@extends('user.layout')

@section('title', __('Messages'))

@section('account')
<div class="space-y-5" x-data="chatComponent()">
    <header>
        <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ __('Messages') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('Questions about an order, delivery or a product? Our team replies here.') }}</p>
    </header>

    <section class="flex h-[calc(100dvh-20rem)] min-h-[420px] flex-col overflow-hidden rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white md:h-[600px]" aria-label="{{ __('Conversation with support') }}">
        <div class="flex items-center gap-3 border-b border-[var(--sf-border)] px-4 py-3 md:px-5">
            <span class="relative flex size-10 items-center justify-center rounded-full bg-brand-600 text-white" aria-hidden="true">
                <i class="bi bi-headset"></i>
                <span class="absolute bottom-0 right-0 size-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
            </span>
            <div>
                <p class="text-sm font-semibold text-slate-900">{{ \App\Models\Setting::get('site_name', 'ONEMALL') }} {{ __('Support') }}</p>
                <p class="text-xs text-slate-500">{{ __('We usually reply within minutes') }}</p>
            </div>
        </div>

        <div x-ref="box" class="flex-1 space-y-3 overflow-y-auto bg-slate-50/60 px-4 py-5 md:px-5" role="log" aria-live="polite">
            <template x-if="loading">
                <div class="space-y-3" aria-hidden="true">
                    <div class="sf-skeleton h-12 w-2/3 rounded-2xl"></div>
                    <div class="sf-skeleton ml-auto h-12 w-1/2 rounded-2xl"></div>
                </div>
            </template>
            <template x-if="!loading && messages.length === 0">
                <div class="flex h-full flex-col items-center justify-center text-center">
                    <span class="flex size-14 items-center justify-center rounded-full bg-brand-50 text-2xl text-brand-600" aria-hidden="true"><i class="bi bi-chat-heart"></i></span>
                    <p class="mt-3 font-semibold text-slate-900">{{ __('Start a conversation') }}</p>
                    <p class="mt-1 max-w-xs text-sm text-slate-500">{{ __('Tell us your order number if your question is about an order.') }}</p>
                </div>
            </template>
            <template x-for="msg in messages" :key="msg.id">
                <div class="flex" :class="msg.is_admin ? 'justify-start' : 'justify-end'">
                    <div class="max-w-[85%] sm:max-w-md">
                        <div class="whitespace-pre-line break-words rounded-2xl px-4 py-2.5 text-sm shadow-[var(--sf-shadow-sm)]"
                             :class="msg.is_admin ? 'rounded-bl-md bg-white text-slate-800 ring-1 ring-[var(--sf-border)]' : 'rounded-br-md bg-brand-600 text-white'"
                             x-text="msg.message"></div>
                        <p class="mt-1 px-1 text-[11px] text-slate-400" :class="!msg.is_admin && 'text-right'">
                            <span x-text="formatDate(msg.created_at)"></span>
                            <span x-show="msg.failed" class="font-semibold text-rose-600"> · {{ __('Not sent') }}</span>
                        </p>
                    </div>
                </div>
            </template>
        </div>

        <form @submit.prevent="sendMessage" class="flex items-end gap-2 border-t border-[var(--sf-border)] p-3 md:p-4">
            <label for="chat-message" class="sr-only">{{ __('Message') }}</label>
            <textarea id="chat-message" x-model="newMessage" rows="1" maxlength="2000" placeholder="{{ __('Type a message…') }}"
                      @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); sendMessage(); }"
                      class="max-h-32 min-h-11 flex-1 resize-none rounded-[var(--sf-radius-sm)] border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-primary/25"></textarea>
            <button type="submit" :disabled="!newMessage.trim()"
                    class="flex size-11 shrink-0 items-center justify-center rounded-[var(--sf-radius-sm)] bg-brand-600 text-white hover:bg-brand-700 disabled:bg-slate-200 disabled:text-slate-400 sf-focus">
                <span class="sr-only">{{ __('Send') }}</span><i class="bi bi-send-fill" aria-hidden="true"></i>
            </button>
        </form>
    </section>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('chatComponent', () => ({
            messages: [],
            newMessage: '',
            loading: true,
            channel: @json(\App\Support\ChatChannel::forUser(auth()->id())),
            init() {
                this.fetchMessages();
                this.markAsRead();

                if (window.Echo && this.channel) {
                    window.Echo.private(this.channel).listen('.message.sent', (e) => {
                        if (e.is_admin) {
                            this.messages.push(e);
                            this.scrollToBottom();
                            this.markAsRead();
                        }
                    });
                }
            },
            headers(json = false) {
                return Object.assign({ 'X-CSRF-TOKEN': @js(csrf_token()), 'Accept': 'application/json' }, json ? { 'Content-Type': 'application/json' } : {});
            },
            markAsRead() {
                fetch(@js(route('user.messages.markRead')), { method: 'POST', headers: this.headers() });
            },
            fetchMessages() {
                fetch(@js(route('user.messages.fetch')), { headers: this.headers() })
                    .then(res => res.json())
                    .then(data => { this.messages = data; this.scrollToBottom(); })
                    .finally(() => this.loading = false);
            },
            sendMessage() {
                const text = this.newMessage.trim();
                if (!text) return;
                this.newMessage = '';

                const tempId = 'tmp-' + Date.now();
                this.messages.push({ id: tempId, is_admin: false, message: text, created_at: new Date().toISOString() });
                this.scrollToBottom();

                fetch(@js(route('user.messages.send')), { method: 'POST', headers: this.headers(true), body: JSON.stringify({ message: text }) })
                    .then(res => res.ok ? res.json() : Promise.reject())
                    .then(data => {
                        const msg = this.messages.find(m => m.id === tempId);
                        if (msg && data.message) msg.id = data.message.id;
                    })
                    .catch(() => {
                        const msg = this.messages.find(m => m.id === tempId);
                        if (msg) msg.failed = true;
                    });
            },
            formatDate(value) {
                if (!value) return '';
                const date = new Date(value);
                const sameDay = date.toDateString() === new Date().toDateString();
                return sameDay
                    ? date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                    : date.toLocaleDateString([], { day: 'numeric', month: 'short' }) + ', ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            },
            scrollToBottom() {
                this.$nextTick(() => { this.$refs.box.scrollTop = this.$refs.box.scrollHeight; });
            },
        }));
    });
</script>
@endpush
@endsection
