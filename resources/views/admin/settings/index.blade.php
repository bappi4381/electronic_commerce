@extends('admin.layouts')

@section('title', 'Global Settings')

@section('content')
    <div class="p-6 lg:p-10 bg-slate-50 min-h-screen">
        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tighter uppercase italic">System Configuration</h1>
                <p class="text-slate-500 text-xs font-bold uppercase tracking-[0.3em] mt-2 flex items-center gap-2">
                    <i class="bi bi-gear-fill text-primary"></i>
                    Manage your store's identity and global parameters
                </p>
            </div>
            <div>
                <button form="settingsForm" type="submit"
                    class="bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-2xl shadow-slate-900/20 hover:bg-primary transition-all active:scale-95 flex items-center gap-3">
                    <i class="bi bi-cloud-check-fill text-lg"></i>
                    Synchronize Settings
                </button>
            </div>
        </div>

        @if(session('success'))
            <div
                class="mb-8 bg-green-500 text-white p-5 rounded-2xl shadow-xl shadow-green-500/20 flex items-center gap-4 animate-bounce">
                <i class="bi bi-check-circle-fill text-2xl"></i>
                <span class="font-bold uppercase tracking-widest text-xs">{{ session('success') }}</span>
            </div>
        @endif

    {{-- Dynamic Tab Navigation Bar --}}
    <div class="flex border border-slate-200 mb-8 bg-white p-1.5 rounded-2xl shadow-sm gap-2 max-w-4xl">
        <button type="button" onclick="switchTab('store-identity')" id="btn-store-identity" 
            class="tab-btn flex-1 py-3 px-6 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all duration-200 flex items-center justify-center gap-2 bg-slate-900 text-white shadow-lg shadow-slate-900/10">
            <i class="bi bi-shop"></i> Store Identity
        </button>
        <button type="button" onclick="switchTab('shipping-delivery')" id="btn-shipping-delivery" 
            class="tab-btn flex-1 py-3 px-6 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all duration-200 flex items-center justify-center gap-2 text-slate-400 hover:bg-slate-50">
            <i class="bi bi-truck"></i> Shipping & Delivery
        </button>
        <button type="button" onclick="switchTab('contact-support')" id="btn-contact-support" 
            class="tab-btn flex-1 py-3 px-6 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all duration-200 flex items-center justify-center gap-2 text-slate-400 hover:bg-slate-50">
            <i class="bi bi-geo-alt"></i> Contact & Support
        </button>
        <button type="button" onclick="switchTab('footer-presentation')" id="btn-footer-presentation" 
            class="tab-btn flex-1 py-3 px-6 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all duration-200 flex items-center justify-center gap-2 text-slate-400 hover:bg-slate-50">
            <i class="bi bi-textarea-t"></i> Footer Text
        </button>
    </div>

    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" id="settingsForm">
        @csrf

        {{-- Tab 1: Store Identity & Branding --}}
        <div id="tabpanel-store-identity" class="tab-panel grid grid-cols-1 xl:grid-cols-3 gap-8">
            <div class="xl:col-span-2 space-y-8">
                {{-- General Identity Card --}}
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/50">
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                                <i class="bi bi-shop"></i>
                            </span>
                            Store Identity
                        </h3>
                    </div>
                    <div class="p-10 grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Store Display Name</label>
                            <input type="text" name="site_name" value="{{ $settings['site_name'] ?? '' }}" 
                                class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 transition-all font-bold text-slate-700 placeholder:text-slate-300"
                                placeholder="e.g. ONEMALL Premium">
                        </div>
                        
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Branding Logo</label>
                            <div class="flex items-center gap-4">
                                @if(isset($settings['logo']))
                                <div class="w-14 h-14 bg-slate-900 rounded-2xl flex items-center justify-center p-2 shadow-inner">
                                    <img src="{{ asset('storage/' . $settings['logo']) }}" class="max-h-full max-w-full object-contain">
                                </div>
                                @endif
                                <input type="file" name="logo" class="flex-1 text-xs text-slate-400 font-bold file:bg-slate-100 file:border-0 file:px-4 file:py-2 file:rounded-xl file:mr-4 file:text-slate-600 file:uppercase file:tracking-widest cursor-pointer">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                {{-- Helper Card --}}
                <div class="bg-white rounded-[2.5rem] border-2 border-dashed border-slate-100 p-10">
                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-4">Quick Tip</h4>
                    <p class="text-xs text-slate-500 font-medium leading-relaxed">
                        Make sure your logo has a transparent background (PNG or SVG) for the best look on both light and dark backgrounds.
                    </p>
                </div>
            </div>
        </div>

        {{-- Tab 2: Shipping & Delivery Setup --}}
        <div id="tabpanel-shipping-delivery" class="tab-panel grid grid-cols-1 xl:grid-cols-3 gap-8 hidden">
            <div class="xl:col-span-2 space-y-8">
                {{-- Shipping Charges Card --}}
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/50">
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                                <i class="bi bi-truck"></i>
                            </span>
                            Shipping & Delivery Setup
                        </h3>
                    </div>
                    <div class="p-10 grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Within Dhaka Charge (TK)</label>
                            <input type="number" name="shipping_charge_dhaka" value="{{ $settings['shipping_charge_dhaka'] ?? '80' }}" step="0.01"
                                class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 transition-all font-bold text-slate-700"
                                placeholder="e.g. 80">
                        </div>
                        
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Outside Dhaka Charge (TK)</label>
                            <input type="number" name="shipping_charge_outside" value="{{ $settings['shipping_charge_outside'] ?? '110' }}" step="0.01"
                                class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 transition-all font-bold text-slate-700"
                                placeholder="e.g. 110">
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                {{-- Dynamic Shipping Info Card --}}
                <div class="bg-slate-900 rounded-[2.5rem] shadow-2xl p-10 text-white relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-primary/20 rounded-full blur-3xl"></div>
                    <h3 class="text-sm font-black uppercase tracking-[0.2em] mb-6 flex items-center gap-3">
                        <i class="bi bi-info-circle text-primary"></i> Shipping Charge Info
                    </h3>
                    <p class="text-xs text-slate-300 leading-relaxed mb-4">
                        These rates are dynamically used on the frontend checkout page when the customer selects their delivery city.
                    </p>
                    <ul class="space-y-3 text-[10px] uppercase tracking-wider font-bold">
                        <li class="flex items-center gap-2 text-emerald-400">
                            <i class="bi bi-check-circle-fill"></i> Dhaka: Within City
                        </li>
                        <li class="flex items-center gap-2 text-indigo-400">
                            <i class="bi bi-check-circle-fill"></i> Other: Outside Dhaka
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Tab 3: Contact & Support & Social Presence --}}
        <div id="tabpanel-contact-support" class="tab-panel grid grid-cols-1 xl:grid-cols-3 gap-8 hidden">
            <div class="xl:col-span-2 space-y-8">
                {{-- Communication Card --}}
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/50">
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-500 flex items-center justify-center">
                                <i class="bi bi-geo-alt"></i>
                            </span>
                            Contact & Support
                        </h3>
                    </div>
                    <div class="p-10 space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-3">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Support Email</label>
                                <input type="email" name="contact_email" value="{{ $settings['contact_email'] ?? '' }}" 
                                    class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 transition-all font-bold text-slate-700"
                                    placeholder="support@onemall.tech">
                            </div>
                            <div class="space-y-3">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Contact Hotline</label>
                                <input type="text" name="contact_phone" value="{{ $settings['contact_phone'] ?? '' }}" 
                                    class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 transition-all font-bold text-slate-700"
                                    placeholder="+1 (555) 000-0000">
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Physical Address / HQ</label>
                            <textarea name="contact_address" rows="3" 
                                class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 transition-all font-bold text-slate-700"
                                placeholder="Enter full office address">{{ $settings['contact_address'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                {{-- Social Presence --}}
                <div class="bg-slate-900 rounded-[2.5rem] shadow-2xl p-10 text-white relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-primary/20 rounded-full blur-3xl"></div>
                    
                    <h3 class="text-sm font-black uppercase tracking-[0.2em] mb-10 flex items-center gap-3">
                        <i class="bi bi-share-fill text-primary"></i>
                        Social Presence
                    </h3>

                    <div class="space-y-8">
                        <div class="space-y-3">
                            <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                                <i class="bi bi-facebook"></i> Facebook Profile
                            </label>
                            <input type="url" name="facebook_url" value="{{ $settings['facebook_url'] ?? '' }}" 
                                class="w-full px-6 py-4 bg-white/5 border border-white/10 rounded-2xl focus:border-primary/50 focus:ring-0 transition-all font-bold text-sm"
                                placeholder="https://facebook.com/yourpage">
                        </div>

                        <div class="space-y-3">
                            <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                                <i class="bi bi-twitter-x"></i> X (Twitter) Handle
                            </label>
                            <input type="url" name="twitter_url" value="{{ $settings['twitter_url'] ?? '' }}" 
                                class="w-full px-6 py-4 bg-white/5 border border-white/10 rounded-2xl focus:border-primary/50 focus:ring-0 transition-all font-bold text-sm"
                                placeholder="https://twitter.com/yourhandle">
                        </div>

                        <div class="space-y-3">
                            <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                                <i class="bi bi-instagram"></i> Instagram Profile
                            </label>
                            <input type="url" name="instagram_url" value="{{ $settings['instagram_url'] ?? '' }}" 
                                class="w-full px-6 py-4 bg-white/5 border border-white/10 rounded-2xl focus:border-primary/50 focus:ring-0 transition-all font-bold text-sm"
                                placeholder="https://instagram.com/yourprofile">
                        </div>
                    </div>

                    <div class="mt-12 p-6 bg-white/5 rounded-3xl border border-white/5">
                        <p class="text-[10px] text-slate-400 font-medium leading-relaxed italic">
                            "Your social media links will automatically populate icons in the website footer for your customers to connect."
                        </p>
                    </div>
                </div>
            </div>
        </div>
        {{-- Tab 4: Footer Presentation --}}
        <div id="tabpanel-footer-presentation" class="tab-panel grid grid-cols-1 xl:grid-cols-3 gap-8 hidden">
            <div class="xl:col-span-2 space-y-8">
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/50">
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-500 flex items-center justify-center">
                                <i class="bi bi-textarea-t"></i>
                            </span>
                            Footer Presentation
                        </h3>
                    </div>
                    <div class="p-10">
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">About Text (Footer Description)</label>
                            <textarea name="footer_text" rows="4" 
                                class="w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 transition-all font-bold text-slate-700"
                                placeholder="A short description of your company for the footer...">{{ $settings['footer_text'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="space-y-8">
                <div class="bg-white rounded-[2.5rem] border-2 border-dashed border-slate-100 p-10">
                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-4">Quick Tip</h4>
                    <p class="text-xs text-slate-500 font-medium leading-relaxed">
                        This footer description text is shown dynamically at the bottom left section of your customer-facing ecommerce storefront.
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    function switchTab(tabId) {
        // Hide all tab panels
        document.querySelectorAll('.tab-panel').forEach(panel => {
            panel.classList.add('hidden');
        });
        
        // Show active panel
        document.getElementById('tabpanel-' + tabId).classList.remove('hidden');
        
        // Update all tab buttons to default inactive styles
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.className = "tab-btn flex-1 py-3 px-6 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all duration-200 flex items-center justify-center gap-2 text-slate-400 hover:bg-slate-50";
        });
        
        // Update active tab button style
        const activeBtn = document.getElementById('btn-' + tabId);
        activeBtn.className = "tab-btn flex-1 py-3 px-6 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all duration-200 flex items-center justify-center gap-2 bg-slate-900 text-white shadow-lg shadow-slate-900/10";

        // Keep active tab state in hash parameter so it persists on reload
        window.location.hash = tabId;
    }

    // Load active tab from URL hash on load
    document.addEventListener('DOMContentLoaded', function() {
        const hash = window.location.hash.substring(1);
        if (hash && ['store-identity', 'shipping-delivery', 'contact-support', 'footer-presentation'].includes(hash)) {
            switchTab(hash);
        }
    });
</script>

    <style>
        input::placeholder,
        textarea::placeholder {
            opacity: 0.4;
        }

        .shadow-sm {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02), 0 1px 2px -1px rgba(0, 0, 0, 0.02);
        }
    </style>
@endsection