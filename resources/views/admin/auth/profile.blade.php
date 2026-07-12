@extends('admin.layouts')

@section('title', 'Admin Profile')

@section('content')
<div class="space-y-8 max-w-6xl mx-auto">
    {{-- Header --}}
    <div>
        <h1 class="text-3xl font-black tracking-tight text-slate-900 leading-none">Account Settings</h1>
        <p class="text-sm font-bold text-slate-400 uppercase tracking-[0.2em] mt-3">Manage your personal information and security</p>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex items-center gap-4 animate-fadeIn">
            <div class="w-10 h-10 bg-emerald-500 rounded-xl flex items-center justify-center text-white text-xl shadow-lg shadow-emerald-500/20">
                <i class="bi bi-check-lg"></i>
            </div>
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-emerald-700">Profile Updated</p>
                <p class="text-sm font-bold text-emerald-600">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Profile Info Card (Left) --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/40 p-8 flex flex-col items-center text-center relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[100px] -mr-10 -mt-10"></div>
                
                <div class="relative w-32 h-32 rounded-3xl overflow-hidden shadow-2xl shadow-slate-200/50 mb-6 border-4 border-white z-10">
                    <img src="{{ $admin->avatar 
                                ? asset('storage/' . $admin->avatar) 
                                : 'https://ui-avatars.com/api/?name=' . urlencode($admin->name) . '&background=20a7db&color=fff&size=128' }}" 
                         class="w-full h-full object-cover" 
                         alt="{{ $admin->name }}">
                </div>
                
                <h3 class="text-xl font-black tracking-tight text-slate-900 mb-1 z-10">{{ $admin->name }}</h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 z-10">{{ $admin->email }}</p>
                
                <div class="mt-6 px-4 py-2 bg-slate-50 rounded-xl border border-slate-100 flex items-center gap-2 z-10">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-600">Active Admin</span>
                </div>
            </div>
        </div>

        {{-- Edit Profile Form (Right) --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100">
                    <h3 class="text-lg font-black tracking-tight text-slate-900">Update Profile</h3>
                </div>
                
                <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="p-8 space-y-6">
                        {{-- Name & Email Row --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label for="name" class="text-xs font-black uppercase tracking-widest text-slate-500">Full Name</label>
                                <input type="text" name="name" id="name" 
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all @error('name') border-red-500 focus:ring-red-500/10 focus:border-red-500 @enderror" 
                                       value="{{ old('name', $admin->name) }}" required>
                                @error('name')
                                    <span class="text-xs font-bold text-red-500">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="email" class="text-xs font-black uppercase tracking-widest text-slate-500">Email Address</label>
                                <input type="email" name="email" id="email" 
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all @error('email') border-red-500 focus:ring-red-500/10 focus:border-red-500 @enderror" 
                                       value="{{ old('email', $admin->email) }}" required>
                                @error('email')
                                    <span class="text-xs font-bold text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Avatar --}}
                        <div class="space-y-2">
                            <label for="avatar" class="text-xs font-black uppercase tracking-widest text-slate-500">Profile Picture</label>
                            <input type="file" name="avatar" id="avatar" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer @error('avatar') border-red-500 @enderror">
                            @error('avatar')
                                <span class="text-xs font-bold text-red-500">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="h-px bg-slate-100 my-6"></div>
                        <h4 class="text-sm font-black tracking-tight text-slate-700">Change Password <span class="text-[10px] text-slate-400 font-bold ml-2 tracking-normal">(Leave blank to keep current)</span></h4>

                        {{-- Password Row --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label for="password" class="text-xs font-black uppercase tracking-widest text-slate-500">New Password</label>
                                <input type="password" name="password" id="password" 
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all @error('password') border-red-500 @enderror">
                                @error('password')
                                    <span class="text-xs font-bold text-red-500">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="password_confirmation" class="text-xs font-black uppercase tracking-widest text-slate-500">Confirm Password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" 
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all">
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-6 bg-slate-50 border-t border-slate-100 flex justify-end">
                        <button type="submit" class="bg-primary text-white px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-primary-dark transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
