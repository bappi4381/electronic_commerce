@component('mail::message')
# Hello {{ $order->user->name }}

Your order **{{ $order->order_id }}** has been placed successfully!

**Total:** {{ number_format($order->total_price, 2) }} Tk  
**Delivery Charge:** {{ number_format($order->delivery_charge, 2) }} Tk  
**Payment Method:** {{ ucfirst($paymentMethod) }}

@if($password)
Your account password: **{{ $password }}**
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent

