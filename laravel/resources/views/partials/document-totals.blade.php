{{-- Total rows at the foot of an invoice/quotation item table. Expects $doc (Invoice|Quotation), $accent, $colspan. --}}
@if ($doc->hasTax())
<tr>
    <td colspan="{{ $colspan }}" class="text-end border-0 pt-4"><span class="text-muted fw-bold small text-uppercase me-3">Sub Total</span></td>
    <td class="text-end border-0 pt-4 pe-4 fw-bold">{{ rs($doc->net_total) }}</td>
</tr>
<tr>
    <td colspan="{{ $colspan }}" class="text-end border-0 py-1"><span class="text-muted fw-bold small text-uppercase me-3">{{ config('sjpos.tax.name') ?: 'Tax' }} ({{ rtrim(rtrim(number_format($doc->tax_rate, 2), '0'), '.') }}%)</span></td>
    <td class="text-end border-0 py-1 pe-4 fw-bold">{{ rs($doc->tax_amount) }}</td>
</tr>
<tr>
    <td colspan="{{ $colspan }}" class="text-end border-0"><span class="text-muted fw-bold small text-uppercase me-3">Total</span></td>
    <td class="text-end border-0 pe-4 fw-bold fs-5 text-{{ $accent }}">{{ rs($doc->subtotal) }}</td>
</tr>
@else
<tr>
    <td colspan="{{ $colspan }}" class="text-end border-0 pt-4"><span class="text-muted fw-bold small text-uppercase me-3">Sub Total</span></td>
    <td class="text-end border-0 pt-4 pe-4 fw-bold fs-5 text-{{ $accent }}">{{ rs($doc->items->sum('total')) }}</td>
</tr>
@endif
