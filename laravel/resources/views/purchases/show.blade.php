@extends('layouts.app')

@section('title', 'Purchase '.$purchase->display_no)

@section('content')
@php
    $pct = $purchase->subtotal > 0 ? $purchase->amount_paid / $purchase->subtotal * 100 : 0;
    $statusClass = ['draft' => 'secondary', 'confirmed' => 'primary', 'paid' => 'success', 'cancelled' => 'danger'][$purchase->status] ?? 'secondary';
@endphp
<div class="animate-fade">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="h3 mb-1"><i class="bi bi-eye me-2"></i>Purchase {{ $purchase->display_no }}</h2>
      <p class="text-muted small">{{ $purchase->supplier_name }} &bull; {{ $purchase->date->format('d M Y') }}</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
      <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header fw-semibold">Items</div>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr><th class="ps-3">Description</th><th>Brand</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr>
            </thead>
            <tbody>
              @foreach ($purchase->items as $item)
              <tr>
                <td class="ps-3">{!! nl2br(e($item->description)) !!}</td>
                <td>{{ $item->brand }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ rs($item->unit_price) }}</td>
                <td class="fw-bold">{{ rs($item->total) }}</td>
              </tr>
              @endforeach
            </tbody>
            <tfoot class="table-light">
              <tr><td colspan="4" class="text-end fw-bold pe-3">Total</td><td class="fw-bold">{{ rs($purchase->subtotal) }}</td></tr>
            </tfoot>
          </table>
        </div>
      </div>

      @if ($purchase->payments->isNotEmpty())
      <div class="card border-0 shadow-sm">
        <div class="card-header fw-semibold"><i class="bi bi-credit-card me-2 text-success"></i>Payments Made</div>
        <div class="table-responsive">
          <table class="table align-middle mb-0 small">
            <thead class="table-light">
              <tr><th class="ps-3">Date</th><th>Amount</th><th>Method</th><th>Reference</th><th>Notes</th></tr>
            </thead>
            <tbody>
              @foreach ($purchase->payments as $payment)
              <tr>
                <td class="ps-3">{{ $payment->payment_date->format('d M Y') }}</td>
                <td class="fw-bold text-success">{{ rs($payment->amount) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', (string) $payment->method)) }}</td>
                <td>{{ $payment->reference ?? '-' }}</td>
                <td>{{ $payment->notes ?? '-' }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      @endif
    </div>

    <div class="col-lg-4">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <h6 class="fw-bold mb-3">Summary</h6>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Status</span>
            <span class="badge bg-{{ $statusClass }}">{{ ucfirst($purchase->status) }}</span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Total</span>
            <strong>{{ rs($purchase->subtotal) }}</strong>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Paid</span>
            <strong class="text-success">{{ rs($purchase->amount_paid) }}</strong>
          </div>
          <div class="d-flex justify-content-between mb-3">
            <span class="text-muted">Balance</span>
            <strong class="text-{{ $purchase->balance > 0 ? 'danger' : 'success' }}">{{ rs($purchase->balance) }}</strong>
          </div>
          <div class="progress mb-2" style="height:8px">
            <div class="progress-bar bg-success" style="width:{{ min(100, $pct) }}%"></div>
          </div>
          <small class="text-muted">{{ number_format($pct, 0) }}% paid</small>
        </div>
        <div class="card-footer border-0 d-grid">
          <a href="{{ route('payments.make', ['purchase_id' => $purchase->id]) }}" class="btn btn-success btn-sm">
            <i class="bi bi-credit-card me-2"></i>Record Payment
          </a>
        </div>
      </div>
      @if ($purchase->notes)
      <div class="card border-0 shadow-sm">
        <div class="card-body"><h6 class="fw-bold">Notes</h6><p class="text-muted small mb-0">{!! nl2br(e($purchase->notes)) !!}</p></div>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection
