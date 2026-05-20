@extends('admin.layouts')
@section('title', 'Community Moderation: ' . $product->name)

@section('content')
<div class="max-w-7xl mx-auto pb-20">
    <div class="flex items-center justify-between mb-12">
        <div class="flex items-center gap-5">
            <div class="w-14 h-14 bg-slate-900 rounded-2xl flex items-center justify-center text-white shadow-xl shadow-slate-900/10">
                <i class="bi bi-chat-left-dots text-2xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 leading-none">Product Engagement</h1>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">Moderating Discussion for: {{ $product->name }}</p>
            </div>
        </div>
        <a href="{{ route('admin.products.index') }}" class="px-6 py-3 bg-slate-100 text-slate-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition-all">Back to Inventory</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        {{-- Product Preview --}}
        <div class="lg:col-span-1 space-y-8">
            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <div class="aspect-square bg-slate-50 rounded-3xl overflow-hidden mb-6">
                    <img src="{{ asset('storage/' . $product->images->first()->image) }}" class="w-full h-full object-contain p-4">
                </div>
                <h4 class="text-xs font-black uppercase tracking-widest text-slate-900 mb-2">{{ $product->name }}</h4>
                <p class="text-[10px] font-black text-primary uppercase tracking-widest mb-6">{{ number_format($product->price, 0) }} Tk</p>
                
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-3 border-b border-slate-50">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Total Comments</span>
                        <span class="text-xs font-black text-slate-900">{{ $product->comments->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-slate-50">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Total Reactions</span>
                        <span class="text-xs font-black text-slate-900">{{ $product->reactions->count() }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Comments List --}}
        <div class="lg:col-span-2">
            <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/40">
                <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-700 mb-10 pb-4 border-b border-slate-50">Community Feedback</h3>

                <div class="space-y-8">
                    @forelse($product->comments as $comment)
                        <div class="flex gap-6 p-6 rounded-3xl hover:bg-slate-50/50 transition-all border border-transparent hover:border-slate-100 group">
                            <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-slate-400 font-black">
                                {{ substr($comment->user->name, 0, 1) }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <h5 class="text-[10px] font-black text-slate-900 uppercase tracking-widest">{{ $comment->user->name }}</h5>
                                        <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ $comment->created_at->format('M d, Y - H:i') }}</p>
                                    </div>
                                    <form action="{{ route('admin.products.comment.destroy', $comment->id) }}" method="POST" onsubmit="return confirm('Delete this comment?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-8 h-8 bg-red-50 text-red-500 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:bg-red-500 hover:text-white">
                                            <i class="bi bi-trash text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed">{{ $comment->comment }}</p>
                                @if($comment->user->status !== 'active')
                                    <span class="inline-block mt-3 px-2 py-0.5 bg-red-100 text-red-600 text-[8px] font-black uppercase tracking-widest rounded">Blocked User</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-20">
                            <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">No discussions yet for this product.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
