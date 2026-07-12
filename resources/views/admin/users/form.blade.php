<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="space-y-2">
        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all" required>
        @error('name') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
    </div>

    <div class="space-y-2">
        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Email Address <span class="text-red-500">*</span></label>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all" required>
        @error('email') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
    </div>

    @if(!isset($user))
    <div class="space-y-2">
        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Password <span class="text-red-500">*</span></label>
        <input type="password" name="password" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all" required>
        @error('password') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
    </div>

    <div class="space-y-2">
        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Confirm Password <span class="text-red-500">*</span></label>
        <input type="password" name="password_confirmation" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all" required>
    </div>
    @endif

    <div class="space-y-2">
        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all">
        @error('phone') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
    </div>

    <div class="space-y-2 md:col-span-2">
        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Address</label>
        <input type="text" name="address" value="{{ old('address', $user->address ?? '') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all">
        @error('address') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
    </div>

    <div class="space-y-2">
        <label class="text-xs font-black uppercase tracking-widest text-slate-500">City</label>
        <input type="text" name="city" value="{{ old('city', $user->city ?? '') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all">
        @error('city') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
    </div>

    <div class="space-y-2">
        <label class="text-xs font-black uppercase tracking-widest text-slate-500">State / Province</label>
        <input type="text" name="state" value="{{ old('state', $user->state ?? '') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all">
        @error('state') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
    </div>

    <div class="space-y-2">
        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Postal Code</label>
        <input type="text" name="postal_code" value="{{ old('postal_code', $user->postal_code ?? '') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all">
        @error('postal_code') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
    </div>

    <div class="space-y-2">
        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Country</label>
        <input type="text" name="country" value="{{ old('country', $user->country ?? '') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all">
        @error('country') <span class="text-xs font-bold text-red-500">{{ $message }}</span> @enderror
    </div>
</div>
