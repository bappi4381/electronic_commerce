<?php

namespace App\Http\Controllers\Admin\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Mail\OrderConfirmation;
use App\Services\CustomerPasswordReset;
use App\Services\OrderStatusService;
use App\Services\RefundService;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    public function __construct(private StockService $stock, private OrderStatusService $orderStatus)
    {
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q'              => 'nullable|string|max:100',
            'status'         => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'date_from'      => 'nullable|date',
            'date_to'        => 'nullable|date|after_or_equal:date_from',
            'per_page'       => 'nullable|integer|in:10,25,50,100',
        ]);

        $orders = $this->filteredOrders($filters)
            ->with('user', 'orderCourier')
            ->latest()
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();

        $statusCounts = Order::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.orders.index', compact('orders', 'filters', 'statusCounts'));
    }

    public function bulkStatus(Request $request)
    {
        $data = $request->validate([
            'order_ids'   => 'required|array|min:1|max:200',
            'order_ids.*' => 'integer',
            'status'      => ['required', Rule::enum(OrderStatus::class)],
            'note'        => 'nullable|string|max:255',
        ]);

        $orders = Order::whereIn('id', $data['order_ids'])->get();
        $result = $this->orderStatus->bulk($orders, OrderStatus::from($data['status']), auth('admin')->id(), $data['note'] ?? null);

        $redirect = back()->with('success', count($result['updated']) . ' order(s) moved to ' . OrderStatus::from($data['status'])->label() . '.');

        if ($result['failed']) {
            $redirect->with('bulk_failures', $result['failed']);
        }

        return $redirect;
    }

    public function create()
    {
        return view('admin.orders.create', [
            'oldItems' => $this->pickerItems(collect(old('items', []))->pluck('variant_id')->filter()->all()),
        ]);
    }

    public function store(Request $request, CustomerPasswordReset $passwordReset)
    {
        $request->validate([
            'email'          => 'required|email',
            'name'           => 'required|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'address'        => 'nullable|string|max:500',
            'items'              => 'required|array|min:1|max:50',
            'items.*.variant_id' => 'required|integer|distinct|exists:product_variants,id',
            'items.*.quantity'   => 'required|integer|min:1|max:999',
            'payment_method'     => 'required|in:cod,online',
        ], [
            'items.required'             => 'Add at least one product.',
            'items.*.variant_id.distinct' => 'The same product variant was added twice; change its quantity instead.',
        ]);

        $adminId = auth('admin')->id();

        [$order, $user, $isNewUser] = DB::transaction(function () use ($request, $adminId) {
            // New customers choose their own password via a reset link
            $user = User::where('email', $request->email)->first();
            $isNewUser = !$user;

            if ($isNewUser) {
                $user = User::create([
                    'name'     => $request->name,
                    'email'    => $request->email,
                    'phone'    => $request->phone,
                    'address'  => $request->address,
                    'password' => Hash::make(Str::random(64)),
                ]);
            }

            $order = Order::create([
                'user_id'          => $user->id,
                'customer_name'    => $request->name,
                'customer_email'   => $request->email,
                'phone'            => $request->phone ?: $user->phone,
                'shipping_address' => $request->address,
                'total_price'      => 0,
                'status'           => OrderStatus::Pending->value,
                'payment_method'   => $request->payment_method,
                'payment_status'   => $request->payment_method == 'cod' ? PaymentStatus::Unpaid->value : PaymentStatus::Pending->value,
            ]);

            $totalPrice = 0;
            foreach ($request->input('items') as $line) {
                $qty = (int) $line['quantity'];
                $variant = $this->stock->reserve((int) $line['variant_id'], $qty, $order, 'Reserved on admin order create', $adminId);
                $price = $variant->unitPrice();

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'quantity'   => $qty,
                    'price'      => $price,
                    'subtotal'   => $price * $qty,
                ]);

                $totalPrice += $price * $qty;
            }

            $order->update(['total_price' => $totalPrice]);
            $this->orderStatus->recordInitial($order, $adminId, 'Manual order created by admin');

            return [$order, $user, $isNewUser];
        });

        Mail::to($user->email)->queue(new OrderConfirmation($order, $request->payment_method));

        $redirect = redirect()->route('orders.index')->with('success', 'Order created successfully for ' . $user->name . '!');

        if ($isNewUser) {
            try {
                $passwordReset->sendResetLink($user->email, newAccount: true);
            } catch (\Throwable $e) {
                report($e);
                $redirect->with('error', 'The order was created, but the set-password email could not be sent. Ask the customer to use "Forgot password".');
            }
        }

        return $redirect;
    }

    public function show(Order $order, RefundService $refunds)
    {
        $order->load([
            'orderItems.product', 'orderItems.variant.attributeValues.attribute',
            'user', 'statusHistories.admin', 'payments', 'refunds.admin', 'orderCourier.admin', 'courierLogs',
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'refundable' => $refunds->refundableAmount($order),
            'refundMethods' => Refund::METHODS,
        ]);
    }

    public function edit(Order $order)
    {
        $order->load('orderItems.product', 'orderItems.variant', 'user');
        $itemsEditable = $order->itemsEditable();

        return view('admin.orders.edit', compact('order', 'itemsEditable'));
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'customer_name'          => 'nullable|string|max:100',
            'phone'                  => 'nullable|string|max:20',
            'shipping_address'       => 'nullable|string|max:255',
            'city'                   => 'nullable|string|max:100',
            'region'                 => 'nullable|string|max:100',
            'alt_phone'              => 'nullable|string|max:20',
            'order_notes'            => 'nullable|string|max:1000',
            'delivery_charge'        => 'nullable|numeric|min:0|max:99999',
            'items'                  => 'nullable|array',
            'items.*.quantity'       => 'required|integer|min:0|max:999',
            'new_items'              => 'nullable|array',
            'new_items.*.variant_id' => 'nullable|exists:product_variants,id',
            'new_items.*.quantity'   => 'nullable|integer|min:1|max:999',
        ]);

        DB::transaction(function () use ($order, $data) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $order->fill(Arr::only($data, ['customer_name', 'phone', 'shipping_address', 'city', 'region', 'alt_phone', 'order_notes']));

            // Items and charges are frozen once the order is paid or has left the warehouse.
            if ($order->itemsEditable()) {
                $this->syncItems($order, $data['items'] ?? [], $data['new_items'] ?? []);
                $order->delivery_charge = $data['delivery_charge'] ?? $order->delivery_charge;
                $order->total_price = max(0, $order->orderItems()->sum('subtotal') + $order->delivery_charge - (float) $order->discount_amount);
            }

            $order->save();
        });

        return redirect()->route('orders.show', $order)->with('success', 'Order updated successfully.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status'  => ['required', Rule::enum(OrderStatus::class)],
            'note'    => 'nullable|string|max:255',
            'restock' => 'nullable|boolean',
        ]);

        $to = OrderStatus::from($data['status']);
        $this->orderStatus->transition($order, $to, auth('admin')->id(), $data['note'] ?? null, $request->boolean('restock'));

        return redirect()->back()->with('success', "Order moved to {$to->label()}.");
    }

    /**
     * Apply quantity changes (0 removes the line) and new lines, keeping variant stock in sync.
     */
    private function syncItems(Order $order, array $quantities, array $newItems): void
    {
        $adminId = auth('admin')->id();

        foreach ($order->orderItems()->with('variant')->get() as $item) {
            if (!isset($quantities[$item->id]['quantity'])) {
                continue;
            }

            $newQty = (int) $quantities[$item->id]['quantity'];
            $delta = $newQty - $item->quantity;

            if ($delta === 0) {
                continue;
            }

            if ($item->variant && $delta > 0) {
                $this->stock->reserve($item->variant, $delta, $order, 'Reserved on admin order edit', $adminId, allowInactive: true);
            } elseif ($item->variant) {
                $this->stock->release($item->variant, -$delta, $order, 'Released on admin order edit', $adminId);
            }

            if ($newQty === 0) {
                $item->delete();
            } else {
                $item->update(['quantity' => $newQty, 'subtotal' => $item->price * $newQty]);
            }
        }

        foreach ($newItems as $line) {
            if (empty($line['variant_id']) || empty($line['quantity'])) {
                continue;
            }

            $qty = (int) $line['quantity'];
            $variant = $this->stock->reserve((int) $line['variant_id'], $qty, $order, 'Reserved on admin order edit', $adminId);

            $existing = $order->orderItems()->where('variant_id', $variant->id)->first();
            if ($existing) {
                $existing->update([
                    'quantity' => $existing->quantity + $qty,
                    'subtotal' => $existing->price * ($existing->quantity + $qty),
                ]);
                continue;
            }

            $price = $variant->unitPrice();

            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
                'quantity'   => $qty,
                'price'      => $price,
                'subtotal'   => $price * $qty,
            ]);
        }

        if (!$order->orderItems()->exists()) {
            throw ValidationException::withMessages([
                'items' => 'An order must keep at least one item. Cancel the order instead.',
            ]);
        }
    }

    /**
     * Soft-delete the order; items and payments are kept. Stock still held in the warehouse is released.
     */
    public function destroy(Order $order)
    {
        DB::transaction(function () use ($order) {
            $order = Order::with('orderItems.variant', 'orderItems.product')->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->statusEnum()?->holdsStock() && !$order->stock_released_at) {
                $this->stock->releaseOrder($order, 'Released because the order was deleted', auth('admin')->id());
                $order->forceFill(['stock_released_at' => now()])->save();
            }

            $order->delete();
        });

        return redirect()->route('orders.index')->with('success', 'Order deleted successfully!');
    }

    public function generateInvoice($id)
    {
        $order = $this->invoiceOrder($id);

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

    private function invoiceOrder($id): Order
    {
        return Order::with(['user', 'orderItems.product', 'orderItems.variant.attributeValues.attribute', 'payments', 'orderCourier'])->findOrFail($id);
    }

    /**
     * Re-selected picker entries after a failed submit, in the shape the lookup endpoint returns.
     */
    private function pickerItems(array $variantIds): array
    {
        if (!$variantIds) {
            return [];
        }

        return ProductVariant::with(['product' => fn ($q) => $q->withTrashed()])
            ->whereIn('id', $variantIds)
            ->get()
            ->mapWithKeys(fn (ProductVariant $v) => [$v->id => $v->toPickerArray()])
            ->all();
    }

    private function filteredOrders(array $filters): Builder
    {
        return Order::query()
            ->when($filters['q'] ?? null, function (Builder $query, string $term) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
                $query->where(function (Builder $query) use ($like) {
                    $query->where('order_id', 'like', $like)
                        ->orWhere('customer_name', 'like', $like)
                        ->orWhere('customer_email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('alt_phone', 'like', $like)
                        ->orWhereHas('user', fn (Builder $user) => $user
                            ->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone', 'like', $like));
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['payment_status'] ?? null, fn (Builder $query, string $status) => $query->where('payment_status', $status))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date));
    }
}
