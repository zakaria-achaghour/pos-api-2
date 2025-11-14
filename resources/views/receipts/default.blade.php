<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $order->order_number ?? $order->id }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            font-family: "Courier New", Courier, monospace;
        }
        .text-right { text-align: right; }
        .receipt {
            width: 80mm;
            margin: 0 auto;
            padding: 16px;
            color: #111;
        }
        .text-center { text-align: center; }
        .muted { color: #666; font-size: 12px; }
        .logo {
            max-width: 60px;
            max-height: 60px;
            margin: 0 auto 8px;
            display: block;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 4px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .meta, .totals, .items { width: 100%; border-collapse: collapse; }
        .meta td {
            font-size: 12px;
            padding: 2px 0;
        }
        .items th, .items td {
            font-size: 12px;
            padding: 4px 0;
        }
        .items th {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            text-align: left;
        }
        .items td.qty,
        .items td.price,
        .items td.total {
            text-align: right;
        }
        .totals td {
            font-size: 12px;
            padding: 2px 0;
        }
        .totals td.label {
            text-align: left;
        }
        .totals td.value {
            text-align: right;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .footer {
            margin-top: 12px;
            text-align: center;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="receipt">
    <div class="text-center">
        @if(!empty($company['logo']))
            <img src="{{ $company['logo'] }}" alt="{{ $company['name'] }}" class="logo">
        @endif
        <h1>{{ $company['name'] }}</h1>
        @if(!empty($company['address']))
            <div class="muted">{{ $company['address'] }}</div>
        @endif
        @if(!empty($company['phone']))
            <div class="muted">Phone: {{ $company['phone'] }}</div>
        @endif
    </div>

    <div class="divider"></div>

    <table class="meta">
        <tr>
            <td>Receipt:</td>
            <td class="text-right">#{{ $order->order_number ?? $order->id }}</td>
        </tr>
        <tr>
            <td>Date:</td>
            <td class="text-right">{{ $printedAt->format('Y-m-d H:i') }}</td>
        </tr>
        <tr>
            <td>Table:</td>
            <td class="text-right">{{ $tableLabel }}</td>
        </tr>
        @if($order->waiter)
            <tr>
                <td>Server:</td>
                <td class="text-right">{{ $order->waiter->full_name ?? $order->waiter->first_name }}</td>
            </tr>
        @endif
    </table>

    <div class="divider"></div>

    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th class="qty">Qty</th>
                <th class="price">Price</th>
                <th class="total">Total</th>
            </tr>
        </thead>
        <tbody>
        @foreach($order->orderItems as $item)
            <tr>
                <td>{{ $item->menuItem->name ?? 'Item #'.$item->id }}</td>
                <td class="qty">{{ $item->quantity }}</td>
                <td class="price">{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="total">{{ number_format((float) $item->quantity * (float) $item->unit_price, 2) }}</td>
            </tr>
            @if(!empty($item->special_instructions))
                <tr>
                    <td colspan="4" class="muted">Notes: {{ $item->special_instructions }}</td>
                </tr>
            @endif
        @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <table class="totals">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">{{ number_format((float) ($order->subtotal ?? 0), 2) }}</td>
        </tr>
        <tr>
            <td class="label">Tax</td>
            <td class="value">{{ number_format($taxAmount, 2) }}</td>
        </tr>
        @if($serviceCharge > 0)
            <tr>
                <td class="label">Service</td>
                <td class="value">{{ number_format($serviceCharge, 2) }}</td>
            </tr>
        @endif
        @if($discountAmount > 0)
            <tr>
                <td class="label">Discount</td>
                <td class="value">-{{ number_format($discountAmount, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td class="label"><strong>Total</strong></td>
            <td class="value"><strong>{{ number_format((float) ($order->total ?? 0), 2) }}</strong></td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="footer">
        <div>Thank you for dining with us!</div>
        <div class="muted">Printed {{ $printedAt->format('Y-m-d H:i') }}</div>
    </div>
</div>

@if($autoPrint)
    <script>
        window.onload = function () {
            if (typeof window.print === 'function') {
                window.print();
            }
        };
    </script>
@endif
</body>
</html>
