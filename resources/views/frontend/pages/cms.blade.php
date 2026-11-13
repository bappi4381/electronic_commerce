@extends('frontend.layout')

@section('title', $page->title)

@push('head')
    @if($page->meta_description)
        <meta name="description" content="{{ $page->meta_description }}">
    @endif
@endpush

@section('content')
<div class="bg-slate-900 py-16 relative overflow-hidden">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-0 right-0 w-96 h-96 bg-primary rounded-full blur-[150px]"></div>
    </div>
    <div class="max-w-4xl mx-auto px-4 relative z-10 text-center">
        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tighter uppercase mb-4 italic">{{ $page->title }}</h1>
        <div class="flex items-center justify-center gap-2 text-slate-400 font-bold text-xs uppercase tracking-widest">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">{{ __('Home') }}</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="text-white">{{ $page->title }}</span>
        </div>
    </div>
</div>

<section class="py-16 bg-slate-50">
    <div class="max-w-4xl mx-auto px-4">
        <article class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-8 lg:p-12 prose prose-slate max-w-none prose-headings:font-black prose-a:text-primary">
            {!! clean((string) $page->content) !!}
        </article>
        <p class="mt-6 text-center text-[10px] font-black uppercase tracking-widest text-slate-400">{{ __('Last updated') }} {{ $page->updated_at->format('d M Y') }}</p>
    </div>
</section>
@endsection
