@extends('layouts.app')

@section('title', 'Outstanding AR · '.$customer)

@section('content')
<div class="animate-fade">

  <!-- Page Header -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
      <a href="{{ route('outstanding-ar') }}" class="text-muted small text-decoration-none d-inline-flex align-items-center mb-2">
        <i class="bi bi-arrow-left me-1"></i>Back to Customers
      </a>
      <h2 class="h3 mb-1"><i class="bi bi-person-circle me-2 text-warning"></i>{{ $customer }}</h2>
      <p class="text-muted small">Outstanding invoices for this customer.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('payments.receive') }}" class="btn btn-success btn-sm">
        <i class="bi bi-cash-coin me-1"></i>Receive Payment
      </a>
    </div>
  </div>

  <!-- Customer Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 text-center">
        <div class="fs-4 fw-bold text-secondary">{{ $invoiceCount }}</div>
        <div class="text-muted small">Unpaid Invoices</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 text-center">
        <div class="fs-4 fw-bold text-secondary">{{ rs($totalInvoiced, 0) }}</div>
        <div class="text-muted small">Total Invoiced</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 text-center">
        <div class="fs-4 fw-bold text-success">{{ rs($totalPaid, 0) }}</div>
        <div class="text-muted small">Paid So Far</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 text-center">
        <div class="fs-4 fw-bold text-danger">{{ rs($totalBalance, 0) }}</div>
        <div class="text-muted small">Total Amount Due</div>
      </div>
    </div>
  </div>

  <!-- Search within this customer's invoices -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
      <form method="GET" action="{{ route('outstanding-ar') }}" class="row g-2 align-items-end">
        <input type="hidden" name="customer" value="{{ $customer }}">
        <div class="col-md-5">
          <label class="form-label small fw-semibold mb-1">Search Invoice # / Item</label>
          <input type="text" name="inv_search" class="form-control form-control-sm"
            placeholder="Invoice number, item description or brand..." value="{{ $search }}">
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
          <a href="{{ route('outstanding-ar', ['customer' => $customer]) }}" class="btn btn-outline-secondary btn-sm ms-1">Clear</a>
        </div>
        <div class="col-auto ms-auto text-muted small pt-2">
          {{ $invoiceCount }} {{ Str::plural('invoice', $invoiceCount) }} outstanding
        </div>
      </form>
    </div>
  </div>

  @forelse ($invoices as $inv)
  <!-- One card per outstanding invoice, with its items (newest first) -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 bg-white">
      <div class="d-flex align-items-center gap-2">
        <a href="{{ route('invoices.show', $inv->id) }}" class="fw-bold text-primary text-decoration-none fs-6">
          Invoice #{{ $inv->invoice_no }}
        </a>
        <span class="text-muted small">{{ \Illuminate\Support\Carbon::parse($inv->date)->format('d M Y') }}</span>
        <span class="badge {{ days_badge((int) $inv->days_outstanding) }}">
          {{ $inv->days_outstanding <= 0 ? 'Today' : $inv->days_outstanding.' days' }}
        </span>
      </div>
      <a href="{{ route('payments.receive', ['invoice_id' => $inv->id]) }}" class="btn btn-sm btn-success" title="Record Payment">
        <i class="bi bi-cash-coin me-1"></i>Pay
      </a>
    </div>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-4" style="width:8%;">No</th>
            <th>Description</th>
            <th style="width:12%;">Brand</th>
            <th class="text-center" style="width:8%;">Qty</th>
            <th class="text-end" style="width:14%;">Unit Price</th>
            <th class="text-end pe-4" style="width:14%;">Total</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($inv->items as $item)
          <tr>
            <td class="ps-4">{{ $item->item_no }}</td>
            <td>{!! nl2br(e($item->description)) !!}</td>
            <td><span class="badge bg-light text-dark border">{{ $item->brand }}</span></td>
            <td class="text-center">{{ $item->quantity }}</td>
            <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
            <td class="text-end pe-4 fw-semibold">{{ number_format($item->total, 2) }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center text-muted py-3">No items recorded for this invoice.</td>
          </tr>
          @endforelse
        </tbody>
        <tfoot class="table-light">
          <tr>
            <td colspan="4" class="text-end border-0 py-2"><span class="text-muted small fw-bold text-uppercase me-3">Invoiced</span></td>
            <td colspan="2" class="text-end border-0 py-2 pe-4 fw-semibold">{{ rs($inv->subtotal) }}</td>
          </tr>
          @if ($inv->amount_paid > 0)
          <tr>
            <td colspan="4" class="text-end border-0 py-2"><span class="text-muted small fw-bold text-uppercase me-3">Paid</span></td>
            <td colspan="2" class="text-end border-0 py-2 pe-4 fw-semibold text-success">{{ rs($inv->amount_paid) }}</td>
          </tr>
          @endif
          <tr>
            <td colspan="4" class="text-end border-0 py-2"><span class="text-muted small fw-bold text-uppercase me-3">Total Amount Due</span></td>
            <td colspan="2" class="text-end border-0 py-2 pe-4 fw-bold fs-5 text-danger">{{ rs($inv->balance) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
  @empty
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
      <i class="bi bi-check-circle text-success fs-1 d-block mb-2"></i>
      <span class="text-muted">
        {{ $search !== '' ? 'No outstanding invoices match "'.$search.'".' : 'No outstanding invoices for this customer. All payments are up to date!' }}
      </span>
    </div>
  </div>
  @endforelse

  @if ($invoices->hasPages())
  <nav class="mt-4">
    {{ $invoices->links() }}
  </nav>
  @endif
</div>
@endsection
