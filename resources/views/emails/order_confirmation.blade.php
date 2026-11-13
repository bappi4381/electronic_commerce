@component('mail::message')
# Hello {{ $order->customerDisplayName() }}

Your order **{{ $order->order_id }}** has been placed successfully!

@if((float) $order->discount_amount > 0)
**Discount ({{ $order->coupon_code }}):** -{{ number_format($order->discount_amount, 2) }} Tk  
@endif
**Delivery Charge:** {{ number_format($order->delivery_charge, 2) }} Tk  
**Total:** {{ number_format($order->total_price, 2) }} Tk  
**Payment Method:** {{ ucfirst($paymentMethod) }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
