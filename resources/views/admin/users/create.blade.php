@extends('admin.layouts')

@section('title', 'Create User')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 leading-none">Create User</h1>
            <p class="text-sm font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">Add a new customer account</p>
        </div>
        <a href="{{ route('users.index') }}" class="bg-white border border-slate-200 text-slate-600 px-5 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    {{-- Form Card --}}
    <div class="bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            
            <div class="p-8 lg:p-10">
                @include('admin.users.form')
            </div>

            <div class="px-8 py-6 bg-slate-50 border-t border-slate-100 flex justify-end gap-4">
                <button type="submit" class="bg-primary text-white px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-primary-dark transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                    <i class="bi bi-check-lg"></i> Create User
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
