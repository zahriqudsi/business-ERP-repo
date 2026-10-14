{{-- Quotation document: used on the quotation page and loaded into the list page's modal. --}}
@php($company = config('sjpos.company'))
<div class="invoice-details paper animate-fade" data-bs-theme="light">
    <div class="header">
        <img src="{{ asset($company['logo']) }}" alt="{{ $company['name'] }} logo">
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
        <h2 class="text-uppercase fw-bold">Quotation</h2>
    </div>

    <div class="invoice-info-container">
        <div>
            <p class="invoice-info"><span class="text-muted small fw-bold text-uppercase d-block mb-1">Prepared For:</span>
                <span class="fs-5 fw-bold">{{ $quotation->prepared_for }}</span>
            </p>
        </div>
        <div class="invoice-info-wrap">
            <p class="invoice-info"><span class="text-muted small fw-bold text-uppercase">Quotation #:</span> <strong>{{ $quotation->quotation_no }}</strong></p>
            <p class="invoice-info"><span class="text-muted small fw-bold text-uppercase">Date:</span> <strong>{{ $quotation->date?->format('Y-m-d') }}</strong></p>
            <p class="invoice-info"><span class="text-muted small fw-bold text-uppercase">Valid Until:</span> <strong>{{ $quotation->validity_date?->format('Y-m-d') }}</strong></p>
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
                @foreach ($quotation->items as $item)
                <tr>
                    <td>{{ $item->item_no }}</td>
                    <td>{!! nl2br(e($item->description)) !!}</td>
                    <td><span class="badge bg-light text-dark border">{{ $item->brand }}</span></td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-end pe-4 fw-bold">{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
                @include('partials.document-totals', ['doc' => $quotation, 'accent' => 'success', 'colspan' => 5])
            </tbody>
        </table>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-md-7">
            <div class="terms-conditions">
                <h5 class="small">Terms & Conditions</h5>
                <div class="terms-text">
                    {!! nl2br(e($quotation->terms_conditions ?: config('sjpos.quotation.default_terms'))) !!}
                </div>
            </div>
        </div>
        <div class="col-md-5 d-flex align-items-end justify-content-end">
            <div class="text-center pb-4">
                <p class="small text-muted mb-0">Prepared By: <br><strong class="fs-6 text-dark">{{ $quotation->prepared_by }}</strong></p>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-center gap-3 mt-5 no-print flex-wrap">
        <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank" class="btn btn-primary px-4 py-2 fw-bold shadow-sm">
            <i class="bi bi-file-earmark-pdf me-2"></i>Download PDF
        </a>
        <a href="{{ route('documents.email', ['type' => 'quotation', 'id' => $quotation->id]) }}" class="btn btn-outline-primary px-4 py-2 fw-bold">
            <i class="bi bi-envelope me-2"></i>Email
        </a>

        @if ($quotation->invoice)
            <a href="{{ route('invoices.show', $quotation->invoice) }}" class="btn btn-outline-success px-4 py-2 fw-bold">
                <i class="bi bi-check2-circle me-2"></i>Converted to Invoice #{{ $quotation->invoice->invoice_no }}
            </a>
        @else
            <form method="POST" action="{{ route('quotations.convert', $quotation) }}" class="d-inline"
                  onsubmit="return confirm('Create a new invoice from quotation #{{ $quotation->quotation_no }}?')">
                @csrf
                <button type="submit" class="btn btn-success px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-arrow-repeat me-2"></i>Convert to Invoice
                </button>
            </form>
        @endif

        <button type="button" onclick="window.print()" class="btn btn-outline-secondary px-4 py-2 fw-bold">
            <i class="bi bi-printer me-2"></i>Print
        </button>
    </div>
</div>
