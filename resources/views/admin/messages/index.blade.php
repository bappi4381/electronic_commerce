@extends('admin.layouts')

@section('title', 'Messages')

@section('content')
<div class="h-[calc(100vh-260px)] flex gap-6" x-data="adminChat()">

    {{-- Conversations List --}}
    <div class="w-80 bg-white rounded-3xl shadow-sm border border-slate-100 flex flex-col shrink-0">
        <div class="p-6 border-b border-slate-100">
            <h3 class="text-sm font-black tracking-[0.2em] uppercase text-slate-900">Conversations</h3>
            <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-widest">{{ $conversations->count() }} active</p>
        </div>
        <div class="flex-1 overflow-y-auto divide-y divide-slate-50">
            @forelse($conversations as $convo)
            <button @click="selectConvo('{{ $convo->identifier }}', '{{ addslashes($convo->name) }}')"
                :class="selectedId === '{{ $convo->identifier }}' ? 'bg-primary/5 border-l-4 border-primary' : 'hover:bg-slate-50'"
                class="w-full text-left px-5 py-4 transition-all">
                <div class="flex justify-between items-center mb-1">
                    <span class="font-black text-[11px] uppercase tracking-widest text-slate-900" :class="selectedId === '{{ $convo->identifier }}' ? 'text-primary' : ''">{{ $convo->name }}</span>
                    <span class="text-[9px] text-slate-400 font-bold">{{ $convo->last_message_at->diffForHumans(null, true) }}</span>
                </div>
                <div class="flex justify-between items-center gap-2">
                    <p class="text-xs text-slate-500 truncate font-medium flex-1">
                        @if($convo->last_msg->is_admin)<span class="text-primary font-bold">You: </span>@endif
                        {{ $convo->last_msg->message }}
                    </p>
                    @if($convo->unread_count > 0)
                    <span class="bg-primary text-white text-[9px] font-black px-2 py-1 rounded-full min-w-[20px] text-center" x-show="selectedId !== '{{ $convo->identifier }}'">
                        {{ $convo->unread_count }}
                    </span>
                    @endif
                </div>
            </button>
            @empty
            <div class="p-10 text-center opacity-40">
                <i class="bi bi-chat-left text-4xl text-slate-300"></i>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mt-3">No conversations yet</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Chat Window --}}
    <div class="flex-1 bg-white rounded-3xl shadow-sm border border-slate-100 flex flex-col overflow-hidden relative">

        {{-- Empty State --}}
        <div x-show="!selectedId" class="h-full flex flex-col items-center justify-center text-center opacity-20 gap-4">
            <i class="bi bi-chat-square-dots text-8xl text-slate-300"></i>
            <p class="font-black text-sm uppercase tracking-[0.2em]">Select a conversation</p>
        </div>

        {{-- Active Chat --}}
        <div x-show="selectedId" class="flex flex-col h-full" style="display: none;" :style="selectedId ? 'display: flex' : 'display: none'">
            {{-- Header --}}
            <div class="px-8 py-5 border-b border-slate-100 flex items-center gap-4 bg-slate-50/60 shrink-0">
                <div class="w-10 h-10 rounded-2xl bg-primary/10 flex items-center justify-center text-primary font-black text-lg">
                    <i class="bi bi-person"></i>
                </div>
                <div>
                    <p class="font-black text-sm uppercase tracking-widest text-slate-900" x-text="selectedName"></p>
                    <span class="text-[10px] font-bold text-green-500 uppercase tracking-widest">● Active</span>
                </div>
            </div>

            {{-- Messages --}}
            <div class="flex-1 overflow-y-auto p-8 space-y-4" id="adminChatBox">
                <div x-show="loading" class="flex justify-center py-10 opacity-40">
                    <i class="bi bi-arrow-repeat animate-spin text-2xl"></i>
                </div>
                
                <div x-show="!loading" class="space-y-4">
                    <template x-for="msg in messages" :key="msg.id">
                        <div :class="msg.is_admin ? 'flex justify-end' : 'flex justify-start'">
                            <div :class="msg.is_admin
                                ? 'bg-primary text-white rounded-tl-3xl shadow-md shadow-primary/15'
                                : 'bg-slate-100 text-slate-800 rounded-tr-3xl'"
                                class="px-6 py-4 rounded-b-3xl max-w-md">
                                <p class="text-sm font-medium leading-relaxed" x-text="msg.message"></p>
                                <span class="text-[9px] font-bold opacity-60 mt-2 block" x-text="formatDate(msg.created_at)"></span>
                            </div>
                        </div>
                    </template>
                    <div x-show="messages.length === 0" class="flex flex-col items-center justify-center py-16 opacity-30">
                        <i class="bi bi-chat text-5xl text-slate-300 mb-3"></i>
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-400">No messages in this conversation</p>
                    </div>
                </div>
            </div>

            {{-- Input --}}
            <div class="p-6 border-t border-slate-100 bg-slate-50/60 shrink-0">
                <form @submit.prevent="sendMessage" class="flex gap-3">
                    <input type="text" x-model="newMessage"
                        class="flex-1 px-6 py-4 bg-white border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-primary/20 transition-all font-semibold text-sm placeholder:text-slate-300"
                        placeholder="Type your reply to this customer...">
                    <button type="submit"
                        :disabled="!newMessage.trim()"
                        class="bg-primary hover:bg-primary-dark disabled:opacity-40 text-white px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-lg shadow-primary/20 transition-all active:scale-95">
                        <i class="bi bi-send-fill mr-2"></i>Send
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function adminChat() {
        return {
            selectedId: null,
            selectedName: '',
            messages: [],
            newMessage: '',
            loading: false,
            echoChannel: null,

            selectConvo(id, name) {
                console.log('Selecting conversation:', id, name);
                
                // Unsubscribe from previous channel if exists
                if (window.Echo && this.selectedId) {
                    try {
                        window.Echo.leave(`chat.${this.selectedId}`);
                    } catch(e) { console.error('Echo leave error:', e); }
                }

                this.selectedId = id;
                this.selectedName = name;
                this.loading = true;
                this.messages = [];
                
                this.fetchMessages();
                this.markAsRead(id);

                // Subscribe to Pusher for incoming messages
                if (window.Echo) {
                    console.log('Subscribing to channel: chat.' + id);
                    this.echoChannel = window.Echo.channel(`chat.${id}`)
                        .listen('.message.sent', (e) => {
                            console.log('Real-time message received:', e);
                            if (!e.is_admin) {
                                this.messages.push(e);
                                this.scrollToBottom();
                                this.markAsRead(id);
                            }
                        });
                }
            },

            markAsRead(id) {
                fetch(`/admin/messages/${id}/mark-read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                }).catch(err => console.error('Error marking as read:', err));
            },

            fetchMessages() {
                if(!this.selectedId) return;
                console.log('Fetching messages for:', this.selectedId);
                
                fetch(`/admin/messages/${this.selectedId}/fetch`, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => {
                    if(!res.ok) throw new Error('Server error: ' + res.status);
                    return res.json();
                })
                .then(data => {
                    this.messages = data;
                    this.loading = false;
                    this.scrollToBottom();
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    this.loading = false;
                });
            },

            sendMessage() {
                if (!this.newMessage.trim() || !this.selectedId) return;

                let msgText = this.newMessage;
                this.newMessage = '';

                let tempId = 'temp-' + Date.now();
                this.messages.push({
                    id: tempId,
                    is_admin: true,
                    message: msgText,
                    created_at: new Date().toISOString()
                });
                this.scrollToBottom();

                fetch(`/admin/messages/${this.selectedId}/send`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ message: msgText })
                })
                .then(res => {
                    if(!res.ok) throw new Error('Send error: ' + res.status);
                    return res.json();
                })
                .then(data => {
                    let index = this.messages.findIndex(m => m.id === tempId);
                    if (index !== -1 && data.message) {
                        this.messages[index].id = data.message.id;
                    }
                })
                .catch(err => {
                    console.error('Send error:', err);
                    alert('Failed to send message. Please try again.');
                });
            },

            formatDate(dateStr) {
                if (!dateStr) return '';
                return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            },

            scrollToBottom() {
                setTimeout(() => {
                    const box = document.getElementById('adminChatBox');
                    if (box) box.scrollTop = box.scrollHeight;
                }, 120);
            }
        };
    }
</script>
@endpush
@endsection
