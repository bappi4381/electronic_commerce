<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{

    public function index()
    {
        $totalProducts = Product::count();
        $totalOrders = Order::count();
        $totalUsers = User::count();
        $totalSales = Order::where('payment_status', 'paid')->sum('total_price');

        $recentOrders = Order::with('user')->latest()->take(5)->get();
        
        // Low Stock Products (Stock < 5)
        $lowStockProducts = Product::where('stock', '<', 5)->take(5)->get();
        
        // Data for Sales Chart (Last 6 Months)
        $salesData = Order::where('payment_status', 'paid')
            ->selectRaw('SUM(total_price) as total, MONTHNAME(created_at) as month')
            ->groupBy('month')
            ->orderByRaw('MIN(created_at) ASC')
            ->take(6)
            ->get();

        return view('admin.dashboard.index', compact(
            'totalProducts', 
            'totalOrders', 
            'totalUsers', 
            'totalSales', 
            'recentOrders',
            'lowStockProducts',
            'salesData'
        ));
    }
}
