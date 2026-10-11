@extends('layouts.app')

@section('title', $supplier->name)

@php($statusBadge = ['draft' => 'secondary', 'confirmed' => 'primary', 'paid' => 'success', 'cancelled' => 'danger'])

@section('content')
<div class="animate-fade">
  @include('parties._header', ['kind' => 'supplier', 'party' => $supplier])

  <div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('purchases.create', ['supplier_name' => $supplier->name]) }}" class="btn btn-primary btn-sm"><i class="bi bi-cart-plus me-1"></i>New Purchase</a>
    <a href="{{ route('bills.create', ['supplier_name' => $supplier->name]) }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-file-earmark-text me-1"></i>Add Bill</a>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom-0 pt-3">
      <ul class="nav nav-tabs card-header-tabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-purchases" type="button" role="tab">Purchases <span class="badge bg-light text-dark border ms-1">{{ $purchases->count() }}</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bills" type="button" role="tab">Bills <span class="badge bg-light text-dark border ms-1">{{ $bills->count() }}</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-payments" type="button" role="tab">Payments <span class="badge bg-light text-dark border ms-1">{{ $payments->count() }}</span></button></li>
      </ul>
    </div>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="tab-purchases" role="tabpanel">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="ps-4">Purchase #</th><th>Date</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end pe-4">Balance</th></tr></thead>
            <tbody>
              @forelse ($purchases as $purchase)
              <tr>
                <td class="ps-4"><a href="{{ route('purchases.show', $purchase) }}" class="fw-bold text-decoration-none">{{ $purchase->display_no }}</a></td>
                <td class="text-muted">{{ $purchase->date?->format('d M Y') }}</td>
                <td><span class="badge bg-{{ $statusBadge[$purchase->status] ?? 'secondary' }}">{{ ucfirst($purchase->status) }}</span></td>
                <td class="text-end">{{ rs($purchase->subtotal) }}</td>
                <td class="text-end text-success">{{ rs($purchase->amount_paid) }}</td>
                <td class="text-end pe-4 {{ $purchase->status === 'confirmed' && $purchase->balance > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                  {{ rs($purchase->status === 'confirmed' ? $purchase->balance : 0) }}
                  @if ($purchase->acceptsPayments())
                    <a href="{{ route('payments.make', ['purchase_id' => $purchase->id]) }}" class="btn btn-sm btn-outline-primary ms-2" title="Make payment"><i class="bi bi-cash"></i></a>
                  @endif
                </td>
              </tr>
              @empty
              <tr><td colspan="6" class="text-center py-4 text-muted">No purchases yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="tab-pane fade" id="tab-bills" role="tabpanel">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="ps-4">Bill #</th><th>Bill date</th><th>Due</th><th>Status</th><th class="text-end">Amount</th><th class="text-end pe-4">Balance</th></tr></thead>
            <tbody>
              @forelse ($bills as $bill)
              <tr>
                <td class="ps-4"><a href="{{ route('bills.edit', $bill) }}" class="fw-bold text-decoration-none text-warning">{{ $bill->display_no }}</a></td>
                <td class="text-muted">{{ $bill->bill_date?->format('d M Y') }}</td>
                <td class="{{ $bill->is_overdue ? 'text-danger fw-bold' : 'text-muted' }}">{{ $bill->due_date?->format('d M Y') ?? '-' }}</td>
                <td><span class="badge bg-{{ \App\Models\VendorBill::STATUS_BADGES[$bill->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $bill->status)) }}</span></td>
                <td class="text-end">{{ rs($bill->amount) }}</td>
                <td class="text-end pe-4 {{ $bill->balance > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ rs($bill->balance) }}</td>
              </tr>
              @empty
              <tr><td colspan="6" class="text-center py-4 text-muted">No bills yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="tab-pane fade" id="tab-payments" role="tabpanel">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="ps-4">Date</th><th>Purchase</th><th>Method</th><th>Reference</th><th class="text-end pe-4">Amount</th></tr></thead>
            <tbody>
              @forelse ($payments as $payment)
              <tr>
                <td class="ps-4 text-muted">{{ $payment->payment_date?->format('d M Y') }}</td>
                <td>@if ($payment->purchase)<a href="{{ route('purchases.show', $payment->purchase) }}" class="text-decoration-none">{{ $payment->purchase->display_no }}</a>@else<span class="text-muted">-</span>@endif</td>
                <td>{{ \App\Models\PaymentReceived::METHODS[$payment->method] ?? $payment->method }}</td>
                <td class="text-muted small">{{ $payment->reference ?: '-' }}</td>
                <td class="text-end pe-4 fw-semibold">{{ rs($payment->amount) }}</td>
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
