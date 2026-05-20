<?php

namespace App\Http\Controllers\Admin\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user')->latest()->paginate(10);
        return view('admin.orders.index', compact('orders'));
    }
    public function create()
    {
        $users = User::select('id', 'name', 'email')->get();
        $products = Product::all();
        return view('admin.orders.create', compact('users', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'products' => 'required|array|min:1',
            'products.*' => 'exists:products,id',
            'payment_method' => 'required|in:cod,online',
        ]);

        // 1️⃣ User check or create
        $password = null;
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $password = uniqid('pass_');
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'password' => Hash::make($password),
            ]);
        }

        // 2️⃣ Calculate total price
        $totalPrice = 0;
        foreach ($request->products as $productId) {
            $product = Product::findOrFail($productId);
            $totalPrice += $product->price;
        }

        // 3️⃣ Create unique order ID
        $orderId = 'ORD' . strtoupper(uniqid());

        // 4️⃣ Create Order
        $order = Order::create([
            'order_id' => $orderId,
            'user_id' => $user->id,
            'total_price' => $totalPrice,
            'status' => $request->payment_method == 'cod' ? 'pending' : 'processing',
        ]);

        // 5️⃣ Create Order Items
        foreach ($request->products as $productId) {
            $product = Product::findOrFail($productId);
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => $product->price,
                'subtotal' => $product->price,
            ]);
        }

        // 6️⃣ Send email (same structure as user panel)
        Mail::send('emails.order_confirmation', [
            'name' => $user->name,
            'order_id' => $order->order_id,
            'total' => $totalPrice,
            'password' => $password,
            'payment_method' => $request->payment_method,
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)->subject('Order Confirmation - BookSaw');
        });

        return redirect()->route('orders.index')->with('success', 'Order created successfully for ' . $user->name . '!');
    }
    public function show(Order $order)
    {
        $order->load('orderItems.product', 'user');
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Order $order, $status)
    {
        if (!in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) {
            return redirect()->back()->with('error', 'Invalid status!');
        }

        $order->update(['status' => $status]);
        return redirect()->back()->with('success', 'Order status updated successfully!');
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return redirect()->route('orders.index')->with('success', 'Order deleted successfully!');
    }
    public function generateInvoice($id)
    {
        $order = Order::with(['user', 'orderItems.product'])->findOrFail($id);

        $pdf = Pdf::loadView('admin.orders.invoice', compact('order'));

        // save PDF
        $fileName = 'invoice_'.$order->id.'.pdf';
        $path = 'invoices/'.$fileName;
        Storage::disk('public')->put($path, $pdf->output());

        $order->invoice_path = $path;
        $order->save();

        return redirect()->back()->with('success', 'Invoice generated successfully!');
    }

    public function downloadInvoice($id)
    {
        $order = Order::findOrFail($id);
        if (!$order->invoice_path || !Storage::disk('public')->exists($order->invoice_path)) {
            return redirect()->back()->with('error', 'Invoice not found!');
        }

        return response()->download(storage_path('app/public/' . $order->invoice_path));
    }
}