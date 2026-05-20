@extends('user.layout')

@section('title', 'Profile Settings')

@section('content')
<div class="max-w-5xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header Section -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tighter uppercase">Profile Settings</h2>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Manage your personal information and security.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-100 text-green-700 rounded-2xl flex items-center gap-3 shadow-sm animate-in zoom-in-95 duration-300">
            <i class="bi bi-check-circle-fill text-xl"></i>
            <span class="font-bold text-sm">{{ session('success') }}</span>
        </div>
    @endif

    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Sidebar: Avatar & Quick Info -->
            <div class="lg:col-span-4 space-y-8">
                <div class="card border-0 shadow-sm p-10 text-center relative overflow-hidden bg-white">
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-primary/5 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10 space-y-8">
                        <div class="relative inline-block group">
                            <div class="w-40 h-40 rounded-[48px] overflow-hidden border-4 border-white shadow-2xl mx-auto relative group-hover:scale-105 transition-all duration-500">
                                <img src="{{ $user->avatar 
                                            ? asset('storage/' . $user->avatar) 
                                            : 'https://via.placeholder.com/200x200/f1f5f9/94a3b8?text=' . strtoupper(substr($user->name ?? 'U', 0, 2)) }}" 
                                     class="w-full h-full object-cover" 
                                     alt="{{ $user->name }}" id="avatarPreview">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <i class="bi bi-camera text-white text-3xl"></i>
                                </div>
                            </div>
                            
                            <label for="avatar" class="absolute -bottom-2 -right-2 w-12 h-12 bg-primary text-white rounded-2xl flex items-center justify-center cursor-pointer shadow-xl hover:bg-primary-dark hover:scale-110 active:scale-95 transition-all border-4 border-white">
                                <i class="bi bi-pencil-fill text-sm"></i>
                                <input type="file" name="avatar" id="avatar" class="hidden" onchange="previewImage(this)">
                            </label>
                        </div>

                        <div>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tighter uppercase mb-1">{{ $user->name }}</h3>
                            <p class="text-[10px] font-black uppercase text-primary tracking-[0.2em] bg-primary/5 inline-block px-4 py-1.5 rounded-full border border-primary/10">{{ $user->user_id }}</p>
                        </div>

                        <div class="pt-8 border-t border-slate-50 grid grid-cols-2 gap-4">
                            <div class="text-center p-4 bg-slate-50/50 rounded-2xl">
                                <p class="text-[9px] font-black uppercase text-slate-400 tracking-widest mb-1">Orders</p>
                                <span class="text-xl font-black text-slate-900 block">{{ $user->orders()->count() }}</span>
                            </div>
                            <div class="text-center p-4 bg-slate-50/50 rounded-2xl">
                                <p class="text-[9px] font-black uppercase text-slate-400 tracking-widest mb-1">Joined</p>
                                <span class="text-sm font-black text-slate-900 block mt-1">{{ $user->created_at->format('M Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm p-8 bg-slate-900 text-white relative overflow-hidden group">
                    <h3 class="text-xs font-black tracking-[0.2em] uppercase mb-4 relative z-10">Data Privacy</h3>
                    <p class="text-[11px] text-slate-400 font-medium leading-relaxed mb-6 relative z-10">Your information is encrypted and stored securely. We never share your personal data with third parties.</p>
                    <a href="#" class="text-[10px] font-black text-primary uppercase tracking-widest hover:underline relative z-10">Learn More</a>
                    <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-primary/20 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
                </div>
            </div>

            <!-- Main Form Column -->
            <div class="lg:col-span-8 space-y-8">
                <!-- Personal Info Card -->
                <div class="card border-0 shadow-sm p-10 lg:p-12 bg-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-slate-50 rounded-bl-[100px] -z-10"></div>
                    
                    <h4 class="text-lg font-black text-slate-900 tracking-tighter uppercase mb-10 flex items-center gap-4">
                        <span class="w-10 h-10 bg-primary text-white rounded-xl flex items-center justify-center shadow-lg shadow-primary/20">
                            <i class="bi bi-person-badge"></i>
                        </span>
                        Personal Information
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-3">
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Full Name</label>
                            <div class="relative group">
                                <i class="bi bi-person absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary transition-colors"></i>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                                       class="w-full bg-slate-50/50 border border-slate-100 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-primary/5 focus:border-primary focus:bg-white transition-all outline-none" required>
                            </div>
                            @error('name') <p class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Email Address</label>
                            <div class="relative group">
                                <i class="bi bi-envelope absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary transition-colors"></i>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" 
                                       class="w-full bg-slate-50/50 border border-slate-100 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-primary/5 focus:border-primary focus:bg-white transition-all outline-none" required>
                            </div>
                            @error('email') <p class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Phone Number</label>
                            <div class="relative group">
                                <i class="bi bi-phone absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary transition-colors"></i>
                                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" 
                                       class="w-full bg-slate-50/50 border border-slate-100 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-primary/5 focus:border-primary focus:bg-white transition-all outline-none">
                            </div>
                            @error('phone') <p class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">City / Region</label>
                            <div class="relative group">
                                <i class="bi bi-geo-alt absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary transition-colors"></i>
                                <input type="text" name="city" value="{{ old('city', $user->city) }}" 
                                       class="w-full bg-slate-50/50 border border-slate-100 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-primary/5 focus:border-primary focus:bg-white transition-all outline-none">
                            </div>
                        </div>

                        <div class="col-span-full space-y-3">
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Shipping Address</label>
                            <div class="relative group">
                                <i class="bi bi-map absolute left-5 top-6 text-slate-300 group-focus-within:text-primary transition-colors"></i>
                                <textarea name="address" rows="3" 
                                          class="w-full bg-slate-50/50 border border-slate-100 rounded-3xl pl-12 pr-6 py-5 text-sm font-bold focus:ring-4 focus:ring-primary/5 focus:border-primary focus:bg-white transition-all outline-none resize-none leading-relaxed">{{ old('address', $user->address) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Card -->
                <div class="card border-0 shadow-sm p-10 lg:p-12 bg-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-rose-50 rounded-bl-[100px] -z-10"></div>
                    
                    <h4 class="text-lg font-black text-slate-900 tracking-tighter uppercase mb-10 flex items-center gap-4">
                        <span class="w-10 h-10 bg-rose-500 text-white rounded-xl flex items-center justify-center shadow-lg shadow-rose-500/20">
                            <i class="bi bi-shield-lock"></i>
                        </span>
                        Account Security
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-3">
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">New Password <span class="text-slate-300 ml-1">(Optional)</span></label>
                            <div class="relative group">
                                <i class="bi bi-key absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary transition-colors"></i>
                                <input type="password" name="password" 
                                       class="w-full bg-slate-50/50 border border-slate-100 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-primary/5 focus:border-primary focus:bg-white transition-all outline-none placeholder:text-slate-300" placeholder="Minimum 8 characters">
                            </div>
                            @error('password') <p class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-3">
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Confirm Password</label>
                            <div class="relative group">
                                <i class="bi bi-shield-check absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary transition-colors"></i>
                                <input type="password" name="password_confirmation" 
                                       class="w-full bg-slate-50/50 border border-slate-100 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold focus:ring-4 focus:ring-primary/5 focus:border-primary focus:bg-white transition-all outline-none placeholder:text-slate-300" placeholder="Repeat new password">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6 pt-4">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center sm:text-left">
                        Last profile update: <span class="text-slate-600 ml-1">{{ $user->updated_at->diffForHumans() }}</span>
                    </p>
                    <button type="submit" class="w-full sm:w-auto px-16 py-5 bg-slate-900 hover:bg-black text-white font-black uppercase tracking-[0.2em] text-[10px] rounded-2xl shadow-2xl shadow-slate-900/30 hover:scale-105 active:scale-95 transition-all flex items-center justify-center gap-3 group">
                        Update Account
                        <i class="bi bi-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('avatarPreview');
                preview.src = e.target.result;
                preview.parentElement.classList.add('animate-in', 'zoom-in-95', 'duration-500');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection
