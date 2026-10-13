@extends('layouts.app')

@section('title', $customer->name)

@php($statusBadge = ['draft' => 'secondary', 'confirmed' => 'primary', 'paid' => 'success', 'cancelled' => 'danger'])

@section('content')
<div class="animate-fade">
  @include('parties._header', ['kind' => 'customer', 'party' => $customer])

  <div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('invoices.create', ['customer_name' => $customer->name]) }}" class="btn btn-primary btn-sm"><i class="bi bi-receipt me-1"></i>New Invoice</a>
    <a href="{{ route('quotations.create', ['prepared_for' => $customer->name]) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-text me-1"></i>New Quotation</a>
    <a href="{{ route('jobs.create', ['customer_name' => $customer->name]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-tools me-1"></i>New Job Note</a>
    @can('view-reports')
    <a href="{{ route('reports.show', ['report' => 'statement', 'customer_id' => $customer->id]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-person-lines-fill me-1"></i>Statement</a>
    @endcan
    @if ($balance > 0)
      <a href="{{ route('outstanding-ar', ['customer' => $customer->name]) }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-hourglass-split me-1"></i>Outstanding invoices</a>
    @endif
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom-0 pt-3">
      <ul class="nav nav-tabs card-header-tabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-invoices" type="button" role="tab">Invoices <span class="badge bg-light text-dark border ms-1">{{ $invoices->count() }}</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-quotations" type="button" role="tab">Quotations <span class="badge bg-light text-dark border ms-1">{{ $quotations->count() }}</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-jobs" type="button" role="tab">Job Notes <span class="badge bg-light text-dark border ms-1">{{ $jobs->count() }}</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-payments" type="button" role="tab">Payments <span class="badge bg-light text-dark border ms-1">{{ $payments->count() }}</span></button></li>
      </ul>
    </div>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="tab-invoices" role="tabpanel">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="ps-4">Invoice #</th><th>Date</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end pe-4">Balance</th></tr></thead>
            <tbody>
              @forelse ($invoices as $invoice)
              <tr>
                <td class="ps-4"><a href="{{ route('invoices.show', $invoice) }}" class="fw-bold text-decoration-none">#{{ $invoice->invoice_no }}</a></td>
                <td class="text-muted">{{ $invoice->date?->format('d M Y') }}</td>
                <td><span class="badge bg-{{ $statusBadge[$invoice->status] ?? 'secondary' }}">{{ ucfirst($invoice->status) }}</span></td>
                <td class="text-end">{{ rs($invoice->subtotal) }}</td>
                <td class="text-end text-success">{{ rs($invoice->amount_paid) }}</td>
                <td class="text-end pe-4 {{ $invoice->status === 'confirmed' && $invoice->balance > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                  {{ rs($invoice->status === 'confirmed' ? $invoice->balance : 0) }}
                  @if ($invoice->acceptsPayments())
                    <a href="{{ route('payments.receive', ['invoice_id' => $invoice->id]) }}" class="btn btn-sm btn-outline-success ms-2" title="Receive payment"><i class="bi bi-cash"></i></a>
                  @endif
                </td>
              </tr>
              @empty
              <tr><td colspan="6" class="text-center py-4 text-muted">No invoices yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="tab-pane fade" id="tab-quotations" role="tabpanel">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="ps-4">Quotation #</th><th>Date</th><th>Valid until</th><th>Prepared by</th><th class="text-end pe-4">Total</th></tr></thead>
            <tbody>
              @forelse ($quotations as $quotation)
              <tr>
                <td class="ps-4"><a href="{{ route('quotations.show', $quotation) }}" class="fw-bold text-decoration-none text-success">#{{ $quotation->quotation_no }}</a></td>
                <td class="text-muted">{{ $quotation->date?->format('d M Y') }}</td>
                <td class="text-muted">{{ $quotation->validity_date?->format('d M Y') }}</td>
                <td>{{ $quotation->prepared_by }}</td>
                <td class="text-end pe-4">{{ rs($quotation->subtotal) }}</td>
              </tr>
              @empty
              <tr><td colspan="5" class="text-center py-4 text-muted">No quotations yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="tab-pane fade" id="tab-jobs" role="tabpanel">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="ps-4">Job #</th><th>Received</th><th>Item</th><th>Serial</th><th class="pe-4">Signature</th></tr></thead>
            <tbody>
              @forelse ($jobs as $job)
              <tr>
                <td class="ps-4"><a href="{{ route('jobs.show', $job) }}" class="fw-bold text-decoration-none">{{ $job->formatted_no }}</a></td>
                <td class="text-muted">{{ $job->collected_date?->format('d M Y') }}</td>
                <td>{{ $job->item }}</td>
                <td class="text-muted small">{{ $job->serial_no ?: '-' }}</td>
                <td class="pe-4">
                  @if ($job->is_signed)
                    <span class="badge bg-success">Signed</span>
                  @else
                    <span class="badge bg-warning text-dark">Awaiting</span>
                  @endif
                </td>
              </tr>
              @empty
              <tr><td colspan="5" class="text-center py-4 text-muted">No job notes yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="tab-pane fade" id="tab-payments" role="tabpanel">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="ps-4">Date</th><th>Invoice</th><th>Method</th><th>Reference</th><th class="text-end pe-4">Amount</th></tr></thead>
            <tbody>
              @forelse ($payments as $payment)
              <tr>
                <td class="ps-4 text-muted">{{ $payment->payment_date?->format('d M Y') }}</td>
                <td>@if ($payment->invoice)<a href="{{ route('invoices.show', $payment->invoice) }}" class="text-decoration-none">#{{ $payment->invoice->invoice_no }}</a>@else<span class="text-muted">-</span>@endif</td>
                <td>{{ \App\Models\PaymentReceived::METHODS[$payment->method] ?? $payment->method }}</td>
                <td class="text-muted small">{{ $payment->reference ?: '-' }}</td>
                <td class="text-end pe-4 fw-semibold text-success">{{ rs($payment->amount) }}</td>
              </tr>
              @empty
              <tr><td colspan="5" class="text-center py-4 text-muted">No payments yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
