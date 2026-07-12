@extends('frontend.layout')

@section('title', 'Page Not Found')

@section('content')
<div class="min-h-[70vh] flex flex-col items-center justify-center bg-white py-16 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Decorative background blobs -->
    <div class="absolute top-0 left-0 w-64 h-64 bg-blue-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob"></div>
    <div class="absolute top-0 right-0 w-64 h-64 bg-purple-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-64 h-64 bg-pink-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob animation-delay-4000"></div>

    <div class="max-w-xl w-full text-center relative z-10">
        <div class="mb-8">
            <!-- Animated 404 SVG or Text -->
            <h1 class="text-[150px] font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600 leading-none drop-shadow-sm select-none">
                404
            </h1>
        </div>
        
        <h2 class="text-3xl font-bold text-slate-800 mb-4 font-outfit tracking-tight">Oops! Product or Page Not Found</h2>
        
        <p class="text-lg text-slate-500 mb-8 max-w-md mx-auto">
            The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('home') }}" 
               class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-base font-medium rounded-full text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="bi bi-house-door mr-2 text-lg"></i>
                Back to Homepage
            </a>
            
            <a href="{{ route('products.index') }}" 
               class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 border-2 border-slate-200 text-base font-medium rounded-full text-slate-700 bg-white hover:bg-slate-50 hover:border-slate-300 hover:text-slate-900 transition-all duration-300 shadow-sm hover:shadow focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                <i class="bi bi-shop mr-2 text-lg"></i>
                Browse Products
            </a>
        </div>
        
        <!-- Search suggestion -->
        <div class="mt-12 pt-8 border-t border-slate-100">
            <p class="text-sm text-slate-500 mb-4">Or try searching for what you need:</p>
            <form action="{{ route('products.index') }}" method="GET" class="max-w-md mx-auto relative">
                <input type="text" name="search" placeholder="Search for products..." 
                       class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm">
                <button type="submit" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600 transition-colors">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    @keyframes blob {
        0% { transform: translate(0px, 0px) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
        100% { transform: translate(0px, 0px) scale(1); }
    }
    .animate-blob {
        animation: blob 7s infinite;
    }
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    .animation-delay-4000 {
        animation-delay: 4s;
    }
</style>
@endsection
