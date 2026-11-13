@extends('admin.layouts')
@section('title', 'Messages')
@section('ui-v2', '1')

@section('content')
<x-admin.page-header title="Messages" :description="number_format($conversations->total()) . ' ' . \Illuminate\Support\Str::plural('conversation', $conversations->total()) . ' with customers.'" />

<div class="flex h-[calc(100vh-15rem)] min-h-[28rem] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900" x-data="adminChat()">
    {{-- Conversations --}}
    <aside class="flex w-full shrink-0 flex-col border-r border-gray-200 md:w-80 dark:border-gray-800" :class="selectedId && 'max-md:hidden'" aria-label="Conversations">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-800">
            <label for="convo-filter" class="sr-only">Filter conversations</label>
            <div class="relative">
                <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                <input id="convo-filter" type="search" x-model="filter" placeholder="Filter by name…"
                       class="h-9 w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 text-sm shadow-xs placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900">
            </div>
        </div>
        <ul class="flex-1 divide-y divide-gray-100 overflow-y-auto dark:divide-gray-800">
            @forelse($conversations as $convo)
                <li x-show="!filter || @js(mb_strtolower($convo->name)).includes(filter.toLowerCase())">
                    <button type="button" @click="selectConvo(@js($convo->identifier), @js($convo->name), @js($convo->channel))"
                            :aria-current="selectedId === @js($convo->identifier) ? 'true' : null"
                            class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors focus-ring"
                            :class="selectedId === @js($convo->identifier) ? 'bg-brand-50 dark:bg-brand-500/10' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50'">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300" aria-hidden="true">
                            {{ mb_strtoupper(mb_substr($convo->name, 0, 1)) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-baseline justify-between gap-2">
                                <span class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $convo->name }}</span>
                                <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ $convo->last_message_at->diffForHumans(null, true) }}</span>
                            </span>
                            <span class="mt-0.5 flex items-center justify-between gap-2">
                                <span class="truncate text-sm text-gray-500 dark:text-gray-400">
                                    @if($convo->last_msg?->is_admin)<span class="font-medium text-gray-700 dark:text-gray-300">You: </span>@endif{{ $convo->last_msg?->message }}
                                </span>
                                @if($convo->unread_count > 0)
                                    <span class="min-w-5 shrink-0 rounded-full bg-brand-600 px-1.5 py-0.5 text-center text-xs font-medium text-white dark:bg-brand-500 dark:text-gray-950"
                                          x-show="!read.includes(@js($convo->identifier))">
                                        {{ $convo->unread_count }}<span class="sr-only"> unread</span>
                                    </span>
                                @endif
                            </span>
                        </span>
                    </button>
                </li>
            @empty
                <li><x-admin.empty-state title="No conversations yet" description="Messages customers send from their account appear here." icon="bi-chat-left" /></li>
            @endforelse
        </ul>
        @if($conversations->hasPages())
            <nav class="flex items-center justify-between border-t border-gray-200 px-4 py-2 text-sm dark:border-gray-800" aria-label="Conversation pages">
                <x-admin.button variant="ghost" size="sm" :href="$conversations->previousPageUrl()" icon="bi-chevron-left" :class="$conversations->onFirstPage() ? 'pointer-events-none opacity-40' : ''">Newer</x-admin.button>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $conversations->currentPage() }} / {{ $conversations->lastPage() }}</span>
                <x-admin.button variant="ghost" size="sm" :href="$conversations->nextPageUrl()" icon-right="bi-chevron-right" :class="$conversations->hasMorePages() ? '' : 'pointer-events-none opacity-40'">Older</x-admin.button>
            </nav>
        @endif
    </aside>

    {{-- Chat --}}
    <section class="flex min-w-0 flex-1 flex-col" :class="!selectedId && 'max-md:hidden'" aria-label="Conversation">
        <div x-show="!selectedId" class="flex h-full items-center justify-center">
            <x-admin.empty-state title="Select a conversation" description="Pick a customer on the left to read and reply." icon="bi-chat-square-dots" />
        </div>

        <template x-if="selectedId">
            <div class="flex h-full flex-col">
                <header class="flex shrink-0 items-center gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                    <button type="button" @click="close()" class="flex size-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 focus-ring md:hidden dark:hover:bg-gray-800">
                        <span class="sr-only">Back to conversations</span><i class="bi bi-arrow-left" aria-hidden="true"></i>
                    </button>
                    <span class="flex size-9 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300" aria-hidden="true" x-text="selectedName.charAt(0).toUpperCase()"></span>
                    <p class="truncate font-medium text-gray-900 dark:text-white" x-text="selectedName"></p>
                </header>

                <div class="flex-1 space-y-3 overflow-y-auto bg-gray-50 px-4 py-6 sm:px-6 dark:bg-gray-950/40" x-ref="box" aria-live="polite">
                    <div x-show="loading" class="flex justify-center py-10 text-gray-400">
                        <i class="bi bi-arrow-repeat animate-spin text-2xl" aria-hidden="true"></i><span class="sr-only">Loading messages</span>
                    </div>
                    <template x-for="msg in messages" :key="msg.id">
                        <div class="flex" :class="msg.is_admin ? 'justify-end' : 'justify-start'">
                            <div class="max-w-[80%] rounded-2xl px-4 py-2.5 shadow-xs sm:max-w-md"
                                 :class="msg.is_admin ? 'rounded-br-md bg-brand-600 text-white dark:bg-brand-500 dark:text-gray-950' : 'rounded-bl-md border border-gray-200 bg-white text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200'">
                                <p class="whitespace-pre-line break-words text-sm" x-text="msg.message"></p>
                                <p class="mt-1 text-right text-[11px] opacity-70" x-text="formatDate(msg.created_at)"></p>
                            </div>
                        </div>
                    </template>
                    <p x-show="!loading && !messages.length" class="py-16 text-center text-sm text-gray-500 dark:text-gray-400">No messages in this conversation yet.</p>
                </div>

                @can('messages.create')
                    <form @submit.prevent="sendMessage" class="flex shrink-0 items-end gap-2 border-t border-gray-200 p-3 dark:border-gray-800">
                        <label for="chat-reply" class="sr-only">Reply</label>
                        <textarea id="chat-reply" x-model="newMessage" rows="1" maxlength="2000" placeholder="Write a reply…"
                                  @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); sendMessage(); }"
                                  class="block max-h-32 min-h-10 flex-1 resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900"></textarea>
                        <x-admin.button type="submit" icon="bi-send" :loading="false" ::disabled="!newMessage.trim()">Send</x-admin.button>
                    </form>
                    <p class="px-3 pb-2 text-xs text-gray-400 dark:text-gray-500">Enter to send, Shift + Enter for a new line.</p>
                @endcan
            </div>
        </template>
    </section>
</div>

@push('scripts')
<script>
    function adminChat() {
        const urls = {
            fetch: @js(route('admin.messages.fetch', ['identifier' => '__ID__'])),
            read: @js(route('admin.messages.markRead', ['identifier' => '__ID__'])),
            send: @js(route('admin.messages.send', ['identifier' => '__ID__'])),
        };
        const url = (key, id) => urls[key].replace('__ID__', encodeURIComponent(id));
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        return {
            selectedId: null,
            selectedName: '',
            selectedChannel: null,
            messages: [],
            newMessage: '',
            loading: false,
            filter: '',
            read: [],

            selectConvo(id, name, channel) {
                if (window.Echo && this.selectedChannel) {
                    try { window.Echo.leave(this.selectedChannel); } catch (e) { console.error('Echo leave error:', e); }
                }

                this.selectedId = id;
                this.selectedName = name;
                this.selectedChannel = channel;
                this.loading = true;
                this.messages = [];
                if (!this.read.includes(id)) this.read.push(id);

                this.fetchMessages();
                this.markAsRead(id);

                if (window.Echo && channel) {
                    window.Echo.private(channel).listen('.message.sent', (e) => {
                        if (!e.is_admin) {
                            this.messages.push(e);
                            this.scrollToBottom();
                            this.markAsRead(id);
                        }
                    });
                }
            },

            close() {
                if (window.Echo && this.selectedChannel) {
                    try { window.Echo.leave(this.selectedChannel); } catch (e) { /* already left */ }
                }
                this.selectedId = null;
                this.selectedChannel = null;
            },

            markAsRead(id) {
                fetch(url('read', id), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                }).catch(err => console.error('Error marking as read:', err));
            },

            fetchMessages() {
                if (!this.selectedId) return;
                const id = this.selectedId;

                fetch(url('fetch', id), { headers: { 'Accept': 'application/json' } })
                    .then(res => { if (!res.ok) throw new Error('Server error: ' + res.status); return res.json(); })
                    .then(data => {
                        if (this.selectedId !== id) return;
                        this.messages = data;
                        this.loading = false;
                        this.scrollToBottom();
                    })
                    .catch(err => {
                        console.error('Fetch error:', err);
                        this.loading = false;
                        window.toast('Could not load this conversation.', 'error');
                    });
            },

            sendMessage() {
                if (!this.newMessage.trim() || !this.selectedId) return;

                const msgText = this.newMessage;
                const tempId = 'temp-' + Date.now();
                this.newMessage = '';
                this.messages.push({ id: tempId, is_admin: true, message: msgText, created_at: new Date().toISOString() });
                this.scrollToBottom();

                fetch(url('send', this.selectedId), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ message: msgText }),
                })
                    .then(res => { if (!res.ok) throw new Error('Send error: ' + res.status); return res.json(); })
                    .then(data => {
                        const index = this.messages.findIndex(m => m.id === tempId);
                        if (index !== -1 && data.message) this.messages[index].id = data.message.id;
                    })
                    .catch(err => {
                        console.error('Send error:', err);
                        this.messages = this.messages.filter(m => m.id !== tempId);
                        this.newMessage = msgText;
                        window.toast('Message not sent. Please try again.', 'error');
                    });
            },

            formatDate(dateStr) {
                if (!dateStr) return '';
                return new Date(dateStr).toLocaleString([], { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
            },

            scrollToBottom() {
                this.$nextTick(() => {
                    const box = this.$refs.box;
                    if (box) box.scrollTop = box.scrollHeight;
                });
            },
        };
    }
</script>
@endpush
@endsection
