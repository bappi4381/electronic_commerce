<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->id }}</title>
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
                <div class="meta-title">Date:</div>
                <div class="meta-value">{{ $order->created_at->format('F d, Y') }}</div>
                
                <div class="meta-title">Invoice #:</div>
                <div class="meta-value">{{ $order->id }}</div>
                
                <div class="meta-title">Customer ID:</div>
                <div class="meta-value">CUST-{{ str_pad($order->user_id ?? 1, 5, '0', STR_PAD_LEFT) }}</div>
            </td>
            <td style="width: 50%; text-align: right;">
                <div style="display: inline-block; text-align: left; width: 220px;">
                    <div class="to-title">To:</div>
                    <div class="to-value">
                        <strong>{{ $order->user->name ?? 'Customer Name' }}</strong><br>
                        @if(isset($order->user->phone))
                            {{ $order->user->phone }}<br>
                        @endif
                        @if(isset($order->user->email))
                            {{ $order->user->email }}<br>
                        @endif
                        {{ $order->shipping_address ?? 'Shipping Address Not Available' }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Horizontal metadata panel matching image styling -->
    <table class="mid-bar">
        <thead>
            <tr>
                <th style="width: 25%;">Salesperson</th>
                <th style="width: 25%;">Job</th>
                <th style="width: 25%;">Payment Terms</th>
                <th style="width: 25%;">Due Date</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>ONEMALL Sales Team</td>
                <td>Online Purchase</td>
                <td>{{ strtoupper($order->payment_method ?? 'COD') }}</td>
                <td>Due on receipt</td>
            </tr>
        </tbody>
    </table>

    <!-- Main items grid -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 10%;" class="text-center">Qty</th>
                <th style="width: 50%;">Description</th>
                <th style="width: 20%;" class="text-right">Unit Price</th>
                <th style="width: 20%;" class="text-right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderItems as $item)
            <tr>
                <td class="text-center">{{ $item->quantity }}</td>
                <td>{{ $item->product->name ?? 'Product N/A' }}</td>
                <td class="text-right">{{ number_format($item->price, 2) }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
            
            <!-- Smart padding loop to fill empty lines (up to 6 total rows) matching the classic design aesthetic -->
            @php
                $blankRowsCount = 6 - count($order->orderItems);
            @endphp
            @if($blankRowsCount > 0)
                @for($i = 0; $i < $blankRowsCount; $i++)
                <tr>
                    <td class="text-center" style="color: transparent;">&nbsp;</td>
                    <td>&nbsp;</td>
                    <td class="text-right" style="color: transparent;">&nbsp;</td>
                    <td class="text-right" style="color: transparent;">&nbsp;</td>
                </tr>
                @endfor
            @endif
        </tbody>
    </table>

    <!-- Bottom summary values -->
    <div class="summary-wrapper">
        <table class="summary-table">
            <tr>
                <td class="summary-label">Subtotal</td>
                <td class="summary-value">{{ number_format($order->total_price - ($order->delivery_charge ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Delivery Charge</td>
                <td class="summary-value">{{ number_format($order->delivery_charge ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Total</td>
                <td class="summary-value" style="background-color: #d1e5dd; border-width: 2px;">{{ number_format($order->total_price, 2) }}</td>
            </tr>
        </table>
        <div class="clear"></div>
    </div>

    <!-- Centered bottom headers -->
    <div class="footer-payable">
        Make all checks payable to ONEMALL CO.
    </div>
    <div class="footer-thankyou">
        Thank you for your business!
    </div>
    
    <div class="footer-company">
        ONEMALL CO. | 123 Tech Avenue, Silicon Valley, CA | Phone: +1 (555) 123-4567 | Fax: +1 (555) 123-4568
    </div>

</body>
</html>
