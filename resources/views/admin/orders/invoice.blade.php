<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_id }}</title>
    <style>
        @page {
            margin: 40px;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            color: #3b3e66;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.5;
        }
        
        /* Centered spacing-tracked INVOICE Title */
        .invoice-title {
            text-align: center;
            font-size: 32px;
            font-weight: normal;
            letter-spacing: 5px;
            color: #3b3e66;
            margin-top: 10px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        
        /* Pastel Green divider line from example image */
        .divider {
            border-bottom: 2px solid #b2d4c9;
            margin-bottom: 25px;
        }
        
        /* Grid table for Metadata & To details */
        .info-table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }
        .info-table td {
            vertical-align: top;
            padding: 0;
        }
        .meta-title {
            font-weight: bold;
            color: #3b3e66;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .meta-value {
            color: #4a5568;
            margin-bottom: 12px;
            font-size: 11px;
        }
        .to-title {
            font-weight: bold;
            color: #3b3e66;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .to-value {
            color: #4a5568;
            font-size: 11px;
            line-height: 1.4;
        }
        
        /* Horizontal bar panel for Sales/Job/Terms */
        .mid-bar {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .mid-bar th {
            color: #3b3e66;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 1px;
            padding: 6px 10px;
            text-align: left;
        }
        .mid-bar td {
            background-color: #eaf2ef;
            color: #4a5568;
            padding: 10px;
            border: 1px solid #b2d4c9;
            font-size: 11px;
        }

        /* Sleek items grid with custom borders */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            color: #3b3e66;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 1px;
            padding: 8px 10px;
            border-bottom: 2px solid #b2d4c9;
            text-align: left;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #b2d4c9;
            border-left: 1px solid #b2d4c9;
            border-right: 1px solid #b2d4c9;
            color: #4a5568;
        }
        .items-table tr:first-child td {
            border-top: 1px solid #b2d4c9;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }

        /* Calculations alignment card */
        .summary-wrapper {
            width: 100%;
            margin-top: 20px;
        }
        .summary-table {
            width: 280px;
            float: right;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 6px 10px;
            font-size: 10px;
        }
        .summary-label {
            font-weight: bold;
            color: #3b3e66;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: right;
            width: 60%;
        }
        .summary-value {
            text-align: right;
            background-color: #eaf2ef;
            border: 1px solid #b2d4c9;
            color: #3b3e66;
            font-weight: bold;
            width: 40%;
            font-size: 11px;
        }
        
        .clear {
            clear: both;
        }

        /* Aesthetic center-aligned printable invoice footers */
        .footer-payable {
            text-align: center;
            margin-top: 70px;
            color: #3b3e66;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 1px;
        }
        .footer-thankyou {
            text-align: center;
            margin-top: 4px;
            color: #6b7280;
            font-style: italic;
            font-size: 11px;
        }
        .footer-company {
            text-align: center;
            margin-top: 30px;
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
            color: #3b3e66;
            font-weight: bold;
            font-size: 8px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <div class="invoice-title">Invoice</div>
    
    <div class="divider"></div>

    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <div class="meta-title">Invoice #:</div>
                <div class="meta-value">{{ $order->order_id }}</div>

                <div class="meta-title">Date:</div>
                <div class="meta-value">{{ $order->created_at->format('F d, Y') }}</div>

                <div class="meta-title">Status:</div>
                <div class="meta-value">{{ $order->statusEnum()?->label() ?? $order->status }} / {{ $order->paymentStatusEnum()?->label() ?? $order->payment_status }}</div>
            </td>
            <td style="width: 50%; text-align: right;">
                <div style="display: inline-block; text-align: left; width: 240px;">
                    <div class="to-title">Ship To:</div>
                    <div class="to-value">
                        <strong>{{ $order->customerDisplayName() }}</strong><br>
                        @if($order->customerDisplayPhone()){{ $order->customerDisplayPhone() }}<br>@endif
                        @if($order->alt_phone)Alt: {{ $order->alt_phone }}<br>@endif
                        @if($order->customerDisplayEmail()){{ $order->customerDisplayEmail() }}<br>@endif
                        {{ $order->shipping_address ?? 'Shipping address not available' }}<br>
                        {{ collect([$order->region, $order->city])->filter()->implode(', ') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="mid-bar">
        <thead>
            <tr>
                <th style="width: 25%;">Payment Method</th>
                <th style="width: 25%;">Payment Status</th>
                <th style="width: 25%;">Courier</th>
                <th style="width: 25%;">Tracking</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ strtoupper($order->payment_method ?? 'COD') }}</td>
                <td>{{ $order->paymentStatusEnum()?->label() ?? $order->payment_status }}</td>
                <td>{{ $order->orderCourier?->courierLabel() ?? '—' }}</td>
                <td>{{ $order->orderCourier?->tracking_code ?: ($order->orderCourier?->consignment_id ?? '—') }}</td>
            </tr>
        </tbody>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 8%;" class="text-center">Qty</th>
                <th style="width: 42%;">Description</th>
                <th style="width: 20%;">SKU / Variant</th>
                <th style="width: 15%;" class="text-right">Unit Price</th>
                <th style="width: 15%;" class="text-right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderItems as $item)
            <tr>
                <td class="text-center">{{ $item->quantity }}</td>
                <td>{{ $item->product?->name ?? 'Product N/A' }}</td>
                <td>
                    {{ $item->variant?->sku ?: '—' }}
                    @if($item->variant && $item->variant->attributeValues->isNotEmpty())
                        <br><span style="font-size: 9px; color: #6b6f92;">{{ $item->variant->attributeValues->map(fn ($v) => $v->attribute?->name . ': ' . $v->value)->implode(', ') }}</span>
                    @endif
                </td>
                <td class="text-right">{{ number_format($item->price, 2) }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach

            @for($i = count($order->orderItems); $i < 6; $i++)
            <tr>
                <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
            </tr>
            @endfor
        </tbody>
    </table>

    <div class="summary-wrapper">
        <table class="summary-table">
            <tr>
                <td class="summary-label">Subtotal</td>
                <td class="summary-value">{{ number_format($order->orderItems->sum('subtotal'), 2) }}</td>
            </tr>
            @if((float) $order->discount_amount > 0)
            <tr>
                <td class="summary-label">Discount ({{ $order->coupon_code }})</td>
                <td class="summary-value">-{{ number_format($order->discount_amount, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td class="summary-label">Delivery Charge</td>
                <td class="summary-value">{{ number_format($order->delivery_charge ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Total (BDT)</td>
                <td class="summary-value" style="background-color: #d1e5dd; border-width: 2px;">{{ number_format($order->total_price, 2) }}</td>
            </tr>
        </table>
        <div class="clear"></div>
    </div>

    <div class="footer-thankyou">
        Thank you for your business!
    </div>

    <div class="footer-company">
        {{ \App\Models\Setting::get('site_name', config('app.name')) }}
        @if(\App\Models\Setting::get('contact_address')) | {{ \App\Models\Setting::get('contact_address') }}@endif
        @if(\App\Models\Setting::get('contact_phone')) | Phone: {{ \App\Models\Setting::get('contact_phone') }}@endif
        @if(\App\Models\Setting::get('contact_email')) | {{ \App\Models\Setting::get('contact_email') }}@endif
    </div>

</body>
</html>
