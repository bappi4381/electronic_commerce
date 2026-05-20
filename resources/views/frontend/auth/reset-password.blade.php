@extends('frontend.layout')

@section('title', 'Reset Password - Recovery')

@section('content')
<!-- Page Header -->
<div class="bg-slate-900 py-16 relative overflow-hidden">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-0 right-0 w-96 h-96 bg-primary rounded-full blur-[150px]"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tighter uppercase mb-4">Set New Password</h1>
        <div class="flex items-center justify-center gap-2 text-slate-400 font-bold text-xs uppercase tracking-widest">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="text-white">Reset Password</span>
        </div>
    </div>
</div>

<section class="py-24 bg-slate-50">
    <div class="max-w-md mx-auto px-4">
        <div class="bg-white rounded-[40px] shadow-xl shadow-slate-200/50 border border-slate-100 p-10 lg:p-12 relative overflow-hidden">
            <!-- Decorative circle -->
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-primary/5 rounded-full blur-2xl"></div>
            
            <div class="relative z-10">
                <h3 class="text-2xl font-black text-slate-900 tracking-tighter uppercase mb-2">New Password</h3>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-10">Please enter your new secure password below.</p>

                @if (session('error'))
                    <div class="mb-8 p-4 bg-red-50 border border-red-100 text-red-700 rounded-2xl flex items-center gap-3">
                        <i class="bi bi-exclamation-triangle-fill text-xl"></i>
                        <span class="font-bold text-xs">{{ session('error') }}</span>
                    </div>
                @endif

                <form action="{{ route('password.update') }}" method="POST" class="space-y-6">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    
                    <div class="space-y-2">
                        <label for="email" class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Email Address</label>
                        <div class="relative group">
                            <i class="bi bi-envelope absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors"></i>
                            <input type="email" name="email" id="email" value="{{ $email ?? old('email') }}" 
                                   class="w-full bg-slate-50 border-none rounded-2xl pl-14 pr-6 py-4 text-sm font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none" 
                                   placeholder="you@example.com" required readonly>
                        </div>
                        @error('email') <p class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="password" class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">New Password</label>
                        <div class="relative group">
                            <i class="bi bi-lock absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors"></i>
                            <input type="password" name="password" id="password" 
                                   class="w-full bg-slate-50 border-none rounded-2xl pl-14 pr-6 py-4 text-sm font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none" 
                                   placeholder="••••••••" required>
                        </div>
                        @error('password') <p class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="password_confirmation" class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Confirm New Password</label>
                        <div class="relative group">
                            <i class="bi bi-shield-lock absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors"></i>
                            <input type="password" name="password_confirmation" id="password_confirmation" 
                                   class="w-full bg-slate-50 border-none rounded-2xl pl-14 pr-6 py-4 text-sm font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none" 
                                   placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-5 bg-primary hover:bg-primary-dark text-white font-black uppercase tracking-[0.2em] text-xs rounded-2xl shadow-xl shadow-primary/30 transition-all active:scale-95 flex items-center justify-center gap-3 group">
                        Update Password
                        <i class="bi bi-check-circle group-hover:scale-110 transition-transform"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
