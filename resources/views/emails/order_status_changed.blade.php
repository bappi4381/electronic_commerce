@component('mail::message')
# Hello {{ $order->customerDisplayName() }}

{{ $message }}

**Order:** {{ $order->order_id }}  
**Status:** {{ $statusLabel }}  
**Total:** {{ number_format($order->total_price, 2) }} Tk

@if($note)
> {{ $note }}
@endif

@if($courier && ($courier->tracking_code || $courier->consignment_id))
**Courier:** {{ $courier->courierLabel() }}  
**Tracking code:** {{ $courier->tracking_code ?: $courier->consignment_id }}

@if($courier->trackingUrl())
@component('mail::button', ['url' => $courier->trackingUrl()])
Track your parcel
@endcomponent
@endif
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent
