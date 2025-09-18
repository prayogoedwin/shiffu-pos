<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $sale->reference ?? 'Receipt' }}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            line-height: 1.3;
        }

        body {
            width: 58mm;
            margin: 0 auto;
            padding: 2mm;
            background: white;
            color: black;
        }

        h2 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        td, th {
            padding: 2px 0;
            border: none;
            vertical-align: top;
        }

        tr {
            border-bottom: 1px dashed #333;
        }

        .no-border {
            border-bottom: none !important;
        }

        .centered {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .left {
            text-align: left;
        }

        .company-info {
            text-align: center;
            margin-bottom: 8px;
        }

        .company-info p {
            font-size: 9px;
            line-height: 1.2;
            margin: 2px 0;
        }

        .transaction-info {
            margin: 8px 0;
            font-size: 10px;
            line-height: 1.4;
        }

        .item-row td:first-child {
            width: 70%;
        }

        .item-row td:last-child {
            width: 30%;
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            font-size: 12px;
        }

        .payment-method {
            background-color: #f0f0f0;
            text-align: center;
            padding: 5px;
            margin: 5px 0;
            border: 1px solid #ddd;
        }

        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 9px;
        }

        /* Print specific styles */
        @media print {
            @page {
                size: 58mm auto;
                margin: 0;
            }

            body {
                width: 58mm;
                padding: 1mm;
                font-size: 10px;
            }

            .no-print {
                display: none !important;
            }

            h2 {
                font-size: 13px;
            }

            td, th {
                padding: 1px 0;
            }
        }

        .print-controls {
            text-align: center;
            margin-bottom: 10px;
        }

        .print-controls button {
            margin: 0 5px;
            padding: 5px 10px;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="no-print print-controls">
    {{-- <button id="printBtn" onclick="printReceipt()">🖨️ Print Receipt</button> --}}
    {{-- <button onclick="window.print()">🖨️ Print Receipt</button> --}}
    <button id="printBtn" onclick="printReceipt()">🖨️ Print Receipt</button>
    {{-- <button onclick="closeWindow()">❌ Close</button> --}}
    <button onclick="goBack()">⬅️ Back</button>
    <p style="font-size: 10px; color: #666; margin-top: 5px;">
        Jika tombol print tidak berfungsi, gunakan: <br>
        <strong>Ctrl+P</strong> (Windows) atau <strong>Cmd+P</strong> (Mac)
    </p>
</div>

<div id="receipt-data">
    <!-- Company Header -->
    <div class="company-info">
        <h2>{{ settings()->company_name ?? 'TOKO SAYA' }}</h2>
        <p>
            {{ settings()->company_email ?? 'email@toko.com' }}, {{ settings()->company_phone ?? '021-123456' }}<br>
            {{ settings()->company_address ?? 'Alamat Toko' }}
        </p>
    </div>

    <!-- Transaction Info -->
    <div class="transaction-info">
        <div>Tanggal: {{ \Carbon\Carbon::parse($sale->date)->format('d M, Y') }}</div>
        <div>No Invoice: {{ $sale->reference }}</div>
        {{-- <div>Nama: {{ $sale->customer_name ?? 'Walk-in Customer' }}</div> --}}
    </div>

    <!-- Items Table -->
    <table>
        <tbody>
        @foreach($sale->saleDetails as $saleDetail)
            <tr class="item-row">
                <td colspan="2">
                    <div style="font-weight: bold;">{{ $saleDetail->product->product_name }}</div>
                    <div style="font-size: 9px;">{{ $saleDetail->quantity }} x {{ format_currency($saleDetail->price) }}</div>
                </td>
                <td class="right">{{ format_currency($saleDetail->sub_total) }}</td>
            </tr>
        @endforeach

        @if($sale->tax_percentage)
            <tr>
                <td colspan="2">Tax ({{ $sale->tax_percentage }}%)</td>
                <td class="right">{{ format_currency($sale->tax_amount) }}</td>
            </tr>
        @endif

        @if($sale->discount_percentage)
            <tr>
                <td colspan="2">Discount ({{ $sale->discount_percentage }}%)</td>
                <td class="right">-{{ format_currency($sale->discount_amount) }}</td>
            </tr>
        @endif

        @if($sale->shipping_amount)
            <tr>
                <td colspan="2">Shipping</td>
                <td class="right">{{ format_currency($sale->shipping_amount) }}</td>
            </tr>
        @endif

        <tr class="total-row">
            <td colspan="2"><strong>GRAND TOTAL</strong></td>
            <td class="right"><strong>{{ format_currency($sale->total_amount) }}</strong></td>
        </tr>

        <tr class="no-border">
            <td colspan="2">Nominal dibayar</td>
            <td class="right">{{ format_currency($sale->paid_amount) }}</td>
        </tr>

        <tr class="no-border">
            <td colspan="2">Kembalian</td>
            <td class="right">{{ format_currency($sale->paid_amount - $sale->total_amount) }}</td>
        </tr>
        </tbody>
    </table>

    <!-- Payment Method -->
    <div class="payment-method">
        {{ $sale->payment_method ?? 'CASH' }}
    </div>

    <!-- Barcode (Optional) -->
    {{-- 
    <div class="centered" style="margin-top: 10px;">
        {!! \Milon\Barcode\Facades\DNS1DFacade::getBarcodeSVG($sale->reference, 'C128', 1, 20, 'black', false) !!}
    </div>
    --}}

    <!-- Footer -->
    <div class="footer">
        <div>TERIMA KASIH</div>
        <div>{{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>

<script>

    window.onload = function() {
        window.print();
    };

    function printReceipt() {
    console.log("Print button clicked!");
    try {
        window.print();
    } catch (e) {
        console.error("Print error:", e);
        setTimeout(() => window.print(), 500);
    }
}

    function closeWindow() {
        try {
            window.close();
        } catch (e) {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                alert('Silakan tutup tab ini secara manual');
            }
        }
    }

    // Keyboard shortcut detection
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            // User pakai Ctrl+P, tidak perlu intercept
            console.log('Manual print shortcut detected');
        }
    });

    // Auto focus ke tombol print saat load
    window.addEventListener('load', function() {
        document.getElementById('printBtn')?.focus();
    });
</script>

</body>
</html>