<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_no }}</title>
    @include('pdf._styles')
</head>

<body>
    @include('pdf._letterhead')

    <div class="doc-title">Invoice</div>

    <table class="plain">
        <tr>
            <td style="padding: 8px;">
                <strong>Customer Name:</strong> {{ $invoice->customer_name }}
            </td>
            <td style="text-align: right; padding: 8px;">
                <strong>Invoice No:</strong> {{ $invoice->invoice_no }}<br>
                <strong>Date:</strong> {{ $invoice->date?->format('Y-m-d') }}
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Description</th>
                <th>Brand</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
            <tr>
                <td>{{ $item->item_no }}</td>
                <td style="width: 50%;">{!! nl2br(e($item->description)) !!}</td>
                <td>{{ $item->brand }}</td>
                <td>{{ $item->quantity }}</td>
                <td style="width: 15%;">{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @include('pdf._totals', ['doc' => $invoice])

    <div class="terms-conditions">
        <strong>Terms and Conditions:</strong><br>
        {!! nl2br(e($invoice->terms_conditions ?: config('sjpos.invoice.default_terms'))) !!}
    </div>

    <div class="bank-details" style="margin-top: 20px;">
        @if ($invoice->bankDetail)
            <strong>Bank Details:</strong><br>
            <p><strong>Bank Name:</strong> {{ $invoice->bankDetail->bank_name }}</p>
            <p><strong>Account Name:</strong> {{ $invoice->bankDetail->account_name }}</p>
            <p><strong>Branch:</strong> {{ $invoice->bankDetail->branch }}</p>
            <p><strong>Account Number:</strong> {{ $invoice->bankDetail->account_number }}</p>
        @else
            <strong>Notes:</strong><br>
            @forelse ($invoice->notes as $note)
                <p>{!! nl2br(e($note->notes)) !!}</p>
            @empty
                <p>No notes available for this invoice.</p>
            @endforelse
        @endif
    </div>

    <div class="thank-you">Thank you for your business!</div>
</body>

</html>
