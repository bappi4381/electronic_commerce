@extends('user.layout')

@section('title', 'Messages')

@section('content')
<div class="max-w-6xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700" x-data="chatComponent()">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tighter uppercase">Messages</h2>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Connect with our support and management team.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden bg-white min-h-[500px] flex flex-col relative">
        <!-- Chat Area -->
        <div class="flex-1 p-6 overflow-y-auto space-y-4 max-h-[400px]" id="chatBox">
            <template x-for="msg in messages" :key="msg.id">
                <div :class="msg.is_admin ? 'flex justify-start' : 'flex justify-end'">
                    <div :class="msg.is_admin ? 'bg-slate-100 text-slate-900 rounded-tr-2xl' : 'bg-primary text-white rounded-tl-2xl'" class="px-6 py-4 rounded-b-2xl max-w-sm shadow-sm">
                        <p class="text-sm font-medium" x-text="msg.message"></p>
                        <span class="text-[10px] opacity-70 mt-2 block" x-text="formatDate(msg.created_at)"></span>
                    </div>
                </div>
            </template>
            <template x-if="messages.length === 0">
                <div class="h-full flex items-center justify-center text-center opacity-50 flex-col pt-20">
                    <i class="bi bi-chat-square-text text-6xl text-slate-300"></i>
                    <p class="mt-4 text-sm font-bold uppercase tracking-widest text-slate-400">Start a conversation!</p>
                </div>
            </template>
        </div>

        <!-- Input Area -->
        <div class="p-6 border-t border-slate-100 bg-slate-50">
            <form @submit.prevent="sendMessage" class="flex gap-4">
                <input type="text" x-model="newMessage" class="flex-1 px-6 py-4 bg-white border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-primary/20 transition-all font-medium text-sm" placeholder="Type your message here..." required>
                <button type="submit" class="px-8 py-4 bg-primary text-white font-black uppercase tracking-widest text-[10px] rounded-2xl shadow-xl shadow-primary/20 hover:scale-105 active:scale-95 transition-all">
                    Send
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('chatComponent', () => ({
            messages: [],
            newMessage: '',
            userId: {{ auth()->id() ?? 'null' }},
            init() {
                this.fetchMessages();
                this.markAsRead();
                
                // Initialize Echo listener (Requires Echo and Pusher to be compiled and configured)
                if (window.Echo && this.userId) {
                    const channelId = String(this.userId);
                    console.log('📡 Subscribing to: chat.' + channelId);
                    window.Echo.channel(`chat.${channelId}`)
                        .listen('.message.sent', (e) => {
                            console.log('⚡ REAL-TIME MESSAGE RECEIVED:', e);
                            // Only add if it's from admin
                            if(e.is_admin) {
                                this.messages.push(e);
                                this.scrollToBottom();
                                this.markAsRead();
                            }
                        });
                }
            },
            markAsRead() {
                fetch('{{ route("user.messages.markRead") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
            },
            fetchMessages() {
                fetch('{{ route("user.messages.fetch") }}')
                    .then(res => res.json())
                    .then(data => {
                        this.messages = data;
                        this.scrollToBottom();
                    });
            },
            sendMessage() {
                if (!this.newMessage.trim()) return;
                
                let messageText = this.newMessage;
                this.newMessage = ''; // Optimistic clear

                // Optimistically add to UI
                let tempId = Date.now();
                this.messages.push({
                    id: tempId,
                    is_admin: false,
                    message: messageText,
                    created_at: new Date().toISOString()
                });
                this.scrollToBottom();

                fetch('{{ route("user.messages.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ message: messageText })
                })
                .then(res => res.json())
                .then(data => {
                    // Replace temp optimistic message with DB real message ID if needed
                    let index = this.messages.findIndex(m => m.id === tempId);
                    if(index !== -1 && data.message) {
                        this.messages[index].id = data.message.id;
                    }
                });
            },
            formatDate(dateStr) {
                if(!dateStr) return '';
                return new Date(dateStr).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            },
            scrollToBottom() {
                setTimeout(() => {
                    let box = document.getElementById('chatBox');
                    if(box) box.scrollTop = box.scrollHeight;
                }, 100);
            }
        }));
    });
</script>
@endpush
@endsection
