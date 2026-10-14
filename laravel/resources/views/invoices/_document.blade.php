{{-- Invoice document: used on the invoice page and loaded into the list page's modal. --}}
@php($company = config('sjpos.company'))
<div class="invoice-details paper animate-fade" data-bs-theme="light">
    <div class="header">
        <img src="{{ logo_url() }}" alt="{{ $company['name'] }} logo">
        <div class="contact-info">
            <p class="fw-bold fs-5 text-primary">{{ $company['name'] }}</p>
            <p>{{ $company['address'] }}</p>
            <p><i class="bi bi-telephone-fill me-2 tiny"></i>{{ $company['phone'] }}</p>
            <p><i class="bi bi-envelope-fill me-2 tiny"></i>{{ $company['email'] }}</p>
            @if (config('sjpos.tax.registration_no'))
                <p class="small">{{ config('sjpos.tax.name') ?: 'Tax' }} Reg. No: {{ config('sjpos.tax.registration_no') }}</p>
            @endif
        </div>
    </div>

    <div class="invoice-header">
        <h2 class="text-uppercase fw-bold">Invoice</h2>
    </div>

    <div class="invoice-info-container">
        <div>
            <p class="invoice-info"><span class="text-muted small fw-bold text-uppercase d-block mb-1">Billed To:</span>
                <span class="fs-5 fw-bold">{{ $invoice->customer_name }}</span>
            </p>
        </div>
        <div class="invoice-info-wrap">
            <p class="invoice-info"><span class="text-muted small fw-bold text-uppercase">Invoice #:</span> <strong>{{ $invoice->invoice_no }}</strong></p>
            <p class="invoice-info"><span class="text-muted small fw-bold text-uppercase">Date:</span> <strong>{{ $invoice->date?->format('Y-m-d') }}</strong></p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="bg-light">
                <tr>
                    <th style="width:8%;">No</th>
                    <th style="width:45%;">Description</th>
                    <th style="width:12%;">Brand</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end pe-4">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->item_no }}</td>
                    <td>{!! nl2br(e($item->description)) !!}</td>
                    <td><span class="badge bg-light text-dark border">{{ $item->brand }}</span></td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-end pe-4 fw-bold">{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
                @include('partials.document-totals', ['doc' => $invoice, 'accent' => 'primary', 'colspan' => 5])
            </tbody>
        </table>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-md-7">
            <div class="terms-conditions">
                <h1 class="small">Terms & Conditions</h1>
                <div class="terms-text">
                    {!! nl2br(e($invoice->terms_conditions ?: config('sjpos.invoice.default_terms'))) !!}
                </div>
            </div>
        </div>
        <div class="col-md-5">
            @if ($invoice->bankDetail || $invoice->notes->isNotEmpty())
            <div class="details-section">
                @if ($invoice->bankDetail)
                <h1 class="small">Payment Information</h1>
                <div class="bg-light p-3 rounded small">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Bank:</span>
                        <span class="fw-bold">{{ $invoice->bankDetail->bank_name }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Account:</span>
                        <span class="fw-bold">{{ $invoice->bankDetail->account_name }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Number:</span>
                        <span class="fw-bold">{{ $invoice->bankDetail->account_number }}</span>
                    </div>
                </div>
                @endif

                @if ($invoice->notes->isNotEmpty())
                <h1 class="small mt-3">Additional Notes</h1>
                <ul class="list-unstyled small text-muted">
                    @foreach ($invoice->notes as $note)
                    <li class="mb-1"><i class="bi bi-info-circle me-2"></i>{!! nl2br(e($note->notes)) !!}</li>
                    @endforeach
                </ul>
                @endif
            </div>
            @endif
        </div>
    </div>

    <div class="d-flex justify-content-center gap-3 mt-5 no-print">
        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="btn btn-primary px-4 py-2 fw-bold shadow-sm">
            <i class="bi bi-file-earmark-pdf me-2"></i>Download PDF
        </a>
        <a href="{{ route('documents.email', ['type' => 'invoice', 'id' => $invoice->id]) }}" class="btn btn-outline-primary px-4 py-2 fw-bold">
            <i class="bi bi-envelope me-2"></i>Email
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary px-4 py-2 fw-bold">
            <i class="bi bi-printer me-2"></i>Print
        </button>
    </div>
</div>
