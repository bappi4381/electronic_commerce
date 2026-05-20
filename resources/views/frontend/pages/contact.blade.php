@extends('frontend.layout')

@section('title', 'Contact Us | Premium Electronics Store')

@section('content')
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16 space-y-4">
            <span class="text-primary font-black uppercase tracking-[0.2em] text-xs">Get In Touch</span>
            <h2 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tighter uppercase italic">Contact Us</h2>
            <div class="w-20 h-1.5 bg-primary rounded-full mx-auto"></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 max-w-5xl mx-auto">
            <div class="bg-slate-50 p-12 rounded-[40px] space-y-8">
                <h3 class="text-2xl font-black tracking-tighter">Reach Out to Us</h3>
                <p class="text-slate-500 font-medium">Have a question about our products, an order, or something else? We'd love to hear from you.</p>
                
                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <i class="bi bi-geo-alt text-primary text-xl"></i>
                        <p class="text-slate-900 font-bold leading-tight">123 Tech Avenue, Silicon Valley,<br>California, USA</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <i class="bi bi-telephone text-primary text-xl"></i>
                        <p class="text-slate-900 font-bold">+1 (555) 123-4567</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <i class="bi bi-envelope text-primary text-xl"></i>
                        <p class="text-slate-900 font-bold">support@onemall.tech</p>
                    </div>
                </div>
            </div>

            <form class="space-y-6 bg-white p-12 rounded-[40px] shadow-2xl border border-slate-100">
                <div class="space-y-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Full Name</label>
                    <input type="text" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-none outline-none focus:ring-2 focus:ring-primary/20 transition-all font-bold text-sm" placeholder="John Doe">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Email Address</label>
                    <input type="email" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-none outline-none focus:ring-2 focus:ring-primary/20 transition-all font-bold text-sm" placeholder="john@example.com">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">Message</label>
                    <textarea rows="4" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-none outline-none focus:ring-2 focus:ring-primary/20 transition-all font-bold text-sm resize-none" placeholder="How can we help you?"></textarea>
                </div>
                <button type="button" class="w-full py-5 bg-primary hover:bg-primary-dark text-white font-black uppercase tracking-widest text-xs rounded-2xl transition-all shadow-xl shadow-primary/30 active:scale-95">Send Message</button>
            </form>
        </div>
    </div>
</section>
@endsection
