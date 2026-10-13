@extends('layouts.app')

@php
    $received = $type === 'received';
    $selected = $documents->firstWhere('id', $selectedId);
    $balance = fn ($doc) => $doc->subtotal - $doc->amount_paid;
@endphp

@section('title', $received ? 'Receive Payment' : 'Make Payment')

@section('content')
<div class="animate-fade">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      @if ($received)
        <h2 class="h3 mb-1"><i class="bi bi-cash-coin me-2 text-success"></i>Record Payment Received</h2>
        <p class="text-muted small">Record a payment received from a customer.</p>
      @else
        <h2 class="h3 mb-1"><i class="bi bi-credit-card me-2 text-primary"></i>Record Payment Made</h2>
        <p class="text-muted small">Record a payment made to a supplier.</p>
      @endif
    </div>
    <a href="{{ route('payments.index', ['type' => $type]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm">
        <div class="card-header fw-semibold"><i class="bi bi-{{ $received ? 'receipt' : 'truck' }} me-2"></i>Payment Details</div>
        <div class="card-body">
          <form method="POST" action="{{ $received ? route('payments.receive.store') : route('payments.make.store') }}">
            @csrf
            <div class="mb-3">
              <label class="form-label fw-semibold small">Link to {{ $received ? 'Invoice' : 'Purchase' }} (Optional)</label>
              <select name="{{ $received ? 'invoice_id' : 'purchase_id' }}" class="form-select" id="docSelect" onchange="fillFromDocument(this)">
                <option value="">-- Select {{ $received ? 'Invoice' : 'Purchase' }} --</option>
                @foreach ($documents as $doc)
                <option value="{{ $doc->id }}"
                  data-party="{{ $received ? $doc->customer_name : $doc->supplier_name }}"
                  data-balance="{{ $balance($doc) }}"
                  @selected(old($received ? 'invoice_id' : 'purchase_id', $selectedId) == $doc->id)>
                  @if ($received)
                    #{{ $doc->invoice_no }} - {{ $doc->customer_name }}
                  @else
                    {{ $doc->display_no }} - {{ $doc->supplier_name }}
                  @endif
                  (Balance: {{ rs($balance($doc)) }})
                </option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold small">{{ $received ? 'Customer' : 'Supplier' }} Name <span class="text-danger">*</span></label>
              <input type="text" name="{{ $received ? 'customer_name' : 'supplier_name' }}" id="partyName" class="form-control"
                value="{{ old($received ? 'customer_name' : 'supplier_name', $selected ? ($received ? $selected->customer_name : $selected->supplier_name) : '') }}"
                list="party-names" autocomplete="off" required>
              @include('partials.party-datalist', ['id' => 'party-names', 'names' => $received ? $customerNames : $supplierNames])
            </div>
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold small">Amount <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text">{{ currency() }}</span>
                  <input type="number" name="amount" id="payAmount" class="form-control"
                    value="{{ old('amount', $selected ? number_format($balance($selected), 2, '.', '') : '') }}" step="0.01" min="0.01" required>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small">Payment Date <span class="text-danger">*</span></label>
                <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}" required>
              </div>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold small">Method</label>
                <select name="method" class="form-select">
                  @foreach (\App\Models\PaymentReceived::METHODS as $value => $label)
                  <option value="{{ $value }}" @selected(old('method', 'bank_transfer') === $value)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small">Reference #</label>
                <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" placeholder="Transaction / Cheque No.">
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label fw-semibold small">Notes</label>
              <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
            </div>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-{{ $received ? 'success' : 'primary' }}"><i class="bi bi-check-lg me-2"></i>Save Payment</button>
              <a href="{{ route('payments.index', ['type' => $type]) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function fillFromDocument(sel) {
  const opt = sel.selectedOptions[0];
  if (opt.value) {
    document.getElementById('partyName').value = opt.dataset.party;
    document.getElementById('payAmount').value = parseFloat(opt.dataset.balance).toFixed(2);
  }
}
</script>
@endpush
