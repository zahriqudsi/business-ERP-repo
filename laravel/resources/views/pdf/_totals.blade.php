{{-- Totals block for invoice/quotation PDFs. Expects $doc (Invoice|Quotation). --}}
<div class="totals">
    @if ($doc->hasTax())
        <p><strong>Sub Total:</strong> {{ rs($doc->net_total) }}</p>
        <p><strong>{{ config('sjpos.tax.name') ?: 'Tax' }} ({{ rtrim(rtrim(number_format($doc->tax_rate, 2), '0'), '.') }}%):</strong> {{ rs($doc->tax_amount) }}</p>
        <p><strong>Total: {{ rs($doc->subtotal) }}</strong></p>
    @else
        <p><strong>Total: {{ rs($doc->items->sum('total')) }}</strong></p>
    @endif
</div>
