<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        .section { margin-top: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f9fafb; }
        .totals td { border: none; padding: 4px 0; }
        .totals tr td:last-child { text-align: right; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; background: #eff6ff; color: #1d4ed8; font-size: 11px; }
    </style>
</head>
<body>
    <h1>Invoice #{{ $invoice->id }}</h1>
    <p class="muted">{{ $invoicePayload['hospital_name'] ?? 'Hospital' }} &mdash; {{ optional($invoice->created_at)->format('d M Y, h:i A') }}</p>

    <div class="section">
        <strong>Patient:</strong> {{ $invoicePayload['member_name'] ?? 'N/A' }}<br>
        <strong>Member ID:</strong> {{ $invoicePayload['member_id'] ?? 'N/A' }}<br>
        <strong>Bill Type:</strong> <span class="badge">{{ $invoice->is_in_patient ? 'In-Patient' : 'Out-Patient' }}</span><br>
        <strong>Status:</strong> {{ ucfirst((string) $invoice->status) }}<br>
        @if($transactionId)
            <strong>Transaction ID:</strong> {{ $transactionId }}<br>
        @endif
        @if(!empty($bookingMeta['booking_id']))
            <strong>Booking ID:</strong> {{ $bookingMeta['booking_id'] }} ({{ $bookingMeta['booking_type'] }})<br>
        @endif
    </div>

    <div class="section">
        <strong>Service Types</strong>
        <p>{{ implode(', ', is_array($invoice->service_types) ? $invoice->service_types : []) }}</p>
    </div>

    @php
        $details = is_array($invoice->invoice_details) ? $invoice->invoice_details : [];
    @endphp

    @if(!empty($details))
        <div class="section">
            <strong>Bill Details</strong>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($details['procedures'] ?? []) as $item)
                        <tr>
                            <td>{{ $item['name'] ?? 'Procedure' }}</td>
                            <td>{{ number_format((float) ($item['amount'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                    @foreach(($details['lab_test'] ?? []) as $item)
                        <tr>
                            <td>{{ $item['name'] ?? 'Lab Test' }}</td>
                            <td>{{ number_format((float) ($item['amount'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                    @foreach(($details['package'] ?? []) as $item)
                        <tr>
                            <td>{{ $item['name'] ?? 'Package' }}</td>
                            <td>{{ number_format((float) ($item['amount'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                    @if(!empty($details['pharmacy']['amount']))
                        <tr>
                            <td>Pharmacy</td>
                            <td>{{ number_format((float) $details['pharmacy']['amount'], 2) }}</td>
                        </tr>
                    @endif
                    @if(!empty($details['family_package']))
                        @foreach($details['family_package'] as $item)
                            <tr>
                                <td>{{ $item['package_name'] ?? 'Family Package' }}</td>
                                <td>{{ number_format((float) ($item['amount'] ?? 0), 2) }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    @endif

    <div class="section totals">
        <table>
            <tr><td>Subtotal</td><td>₹{{ number_format((float) ($invoice->amount ?? 0), 2) }}</td></tr>
            <tr><td>GST</td><td>₹{{ number_format((float) ($invoice->total_gst ?? 0), 2) }}</td></tr>
            <tr><td>Service Charges</td><td>₹{{ number_format((float) ($invoice->service_charges ?? 0), 2) }}</td></tr>
            <tr><td>Gateway Charges</td><td>₹{{ number_format((float) ($invoice->payment_gateway_charges ?? 0), 2) }}</td></tr>
            @if((float) ($invoice->discount_price ?? 0) > 0)
                <tr><td>Discount</td><td>- ₹{{ number_format((float) $invoice->discount_price, 2) }}</td></tr>
            @endif
            <tr><td><strong>Total Amount</strong></td><td><strong>₹{{ number_format((float) ($invoice->total_amount ?? 0), 2) }}</strong></td></tr>
        </table>
    </div>
</body>
</html>
