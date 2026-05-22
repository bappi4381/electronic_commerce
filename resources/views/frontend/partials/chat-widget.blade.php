<div x-data="globalChat()" class="fixed bottom-24 md:bottom-6 right-4 md:right-6 z-[100]">
    <!-- Chat Button -->
    <button @click="open = !open" :class="open ? 'scale-0 opacity-0' : 'scale-100 opacity-100'" class="w-14 h-14 md:w-16 md:h-16 bg-primary text-white rounded-full flex items-center justify-center text-2xl shadow-2xl shadow-primary/40 hover:scale-110 active:scale-95 transition-all absolute bottom-0 right-0 z-[100]">
        <i class="bi bi-chat-dots-fill"></i>
    </button>

    <!-- Chat Window -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-10 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-10 scale-95"
         class="absolute bottom-16 md:bottom-0 right-0 w-[calc(100vw-32px)] sm:w-[400px] h-[400px] md:h-[500px] max-w-[350px] sm:max-w-none bg-white rounded-3xl shadow-2xl flex flex-col overflow-hidden border border-slate-100 origin-bottom-right z-40" style="display: none;">
        
        <!-- Header -->
        <div class="bg-primary p-6 text-white flex justify-between items-center relative overflow-hidden">
            <div class="absolute inset-0 bg-white/10 w-full h-full transform -skew-x-12 translate-x-full group-hover:translate-x-0 transition-transform"></div>
            <div class="relative z-10">
                <h3 class="font-black tracking-widest uppercase text-sm">Live Support</h3>
                <p class="text-[10px] font-bold text-white/70 uppercase tracking-widest mt-1">We typically reply in minutes</p>
            </div>
            <button @click="open = false" class="relative z-10 w-8 h-8 flex items-center justify-center bg-white/20 rounded-full hover:bg-white/30 transition-colors">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 p-6 overflow-y-auto space-y-4 bg-slate-50 relative" id="globalChatBox">
            <template x-for="msg in messages" :key="msg.id">
                <div :class="msg.is_admin ? 'flex justify-start' : 'flex justify-end'">
                    <div :class="msg.is_admin ? 'bg-white text-slate-900 rounded-tr-2xl border border-slate-100' : 'bg-primary text-white rounded-tl-2xl'" class="px-5 py-3 rounded-b-2xl max-w-[85%] shadow-sm">
                        <p class="text-xs font-bold leading-relaxed" x-text="msg.message"></p>
                        <span class="text-[9px] opacity-70 mt-1.5 block font-bold tracking-widest" x-text="formatDate(msg.created_at)"></span>
                    </div>
                </div>
            </template>
            <template x-if="messages.length === 0">
                <div class="h-full flex items-center justify-center text-center opacity-40 flex-col pt-10">
                    <i class="bi bi-chat-square-heart text-5xl text-slate-300"></i>
                    <p class="mt-4 text-xs font-black uppercase tracking-widest text-slate-400">Ask us anything!</p>
                </div>
            </template>
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-white border-t border-slate-100">
            <form @submit.prevent="sendMessage" class="flex gap-2">
                <input type="text" x-model="newMessage" class="flex-1 px-5 py-3 bg-slate-50 border border-slate-100 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 transition-all font-bold text-xs" placeholder="Type message..." required>
                <button type="submit" class="w-12 h-12 bg-primary text-white flex items-center justify-center rounded-xl shadow-lg shadow-primary/20 hover:bg-primary-dark transition-colors active:scale-95 disabled:opacity-50" :disabled="!newMessage.trim()">
                    <i class="bi bi-send-fill text-sm"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('globalChat', () => ({
            open: false,
            messages: [],
            newMessage: '',
            identifier: '{{ auth()->id() ?? session()->getId() }}',
            init() {
                this.fetchMessages();
                
                if (window.Echo) {
                    const channelId = String(this.identifier);
                    console.log('📡 Subscribing to: chat.' + channelId);
                    
                    window.Echo.channel(`chat.${channelId}`)
                        .listen('.message.sent', (e) => {
                            console.log('⚡ REAL-TIME MESSAGE RECEIVED:', e);
                            if(e.is_admin) {
                                this.messages.push(e);
                                this.scrollToBottom();
                                if(this.open) {
                                    this.markAsRead();
                                }
                            }
                        });
                }

                window.addEventListener('open-chat', () => {
                    this.open = true;
                    this.scrollToBottom();
                    this.markAsRead();
                });

                this.$watch('open', value => {
                    if(value) {
                        this.scrollToBottom();
                        this.markAsRead();
                    }
                });
            },
            markAsRead() {
                fetch('{{ route("chat.markRead") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
            },
            fetchMessages() {
                fetch('{{ route("chat.fetch") }}')
                    .then(res => res.json())
                    .then(data => {
                        this.messages = data;
                        if(this.open) this.scrollToBottom();
                    });
            },
            sendMessage() {
                if (!this.newMessage.trim()) return;
                
                let messageText = this.newMessage;
                this.newMessage = ''; 

                let tempId = Date.now();
                this.messages.push({
                    id: tempId,
                    is_admin: false,
                    message: messageText,
                    created_at: new Date().toISOString()
                });
                this.scrollToBottom();

                fetch('{{ route("chat.send") }}', {
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
                    let box = document.getElementById('globalChatBox');
                    if(box) box.scrollTop = box.scrollHeight;
                }, 100);
            }
        }));
    });
</script>
