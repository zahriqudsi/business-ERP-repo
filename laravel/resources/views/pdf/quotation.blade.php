<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Quotation {{ $quotation->quotation_no }}</title>
    @include('pdf._styles')
</head>

<body>
    @include('pdf._letterhead')

    <h1 class="doc-title">Quotation</h1>

    <table class="plain">
        <tr>
            <td style="padding: 8px;">
                <strong>Prepared for :</strong> {{ ucwords($quotation->prepared_for) }}
            </td>
            <td style="text-align: right; padding: 8px;">
                <strong>Quotation No:</strong> {{ $quotation->quotation_no }}<br>
                <strong>Date:</strong> {{ $quotation->date?->format('Y-m-d') }}<br>
                <strong>Validity:</strong> {{ $quotation->validity_date?->format('Y-m-d') }}
            </td>
        </tr>
    </table>

    @php($total = 0)
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
            @foreach ($quotation->items as $item)
            @php($lineTotal = $item->quantity * $item->unit_price)
            @php($total += $lineTotal)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td style="width: 40%;">{!! nl2br(e($item->description)) !!}</td>
                <td>{{ $item->brand }}</td>
                <td>{{ $item->quantity }}</td>
                <td style="width: 15%;">{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($lineTotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @include('pdf._totals', ['doc' => $quotation])

    <div class="terms-conditions">
        <h1>Terms & Conditions</h1>
        <div>{!! nl2br(e($quotation->terms_conditions)) !!}</div>
    </div>

    <div class="thank-you">
        Thank you for considering our quotation!
    </div>
</body>

</html>
