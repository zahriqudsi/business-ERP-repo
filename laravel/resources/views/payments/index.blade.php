@extends('layouts.app')

@php
    $received = $type === 'received';
@endphp

@section('title', 'Payments')

@section('content')
<div class="animate-fade">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
      <h2 class="h3 mb-1"><i class="bi bi-cash-stack me-2 text-primary"></i>Payments</h2>
      <p class="text-muted small">Track all payments received and made.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('payments.receive') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg me-1"></i>Received</a>
      <a href="{{ route('payments.make') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Made</a>
    </div>
  </div>

  <!-- Tab Filter -->
  <ul class="nav nav-pills mb-3">
    <li class="nav-item">
      <a class="payments nav-link {{ $received ? 'active' : '' }}" href="{{ route('payments.index', ['type' => 'received']) }}">
        <i class="bi bi-arrow-down-circle me-1"></i>Received
      </a>
    </li>
    <li class="nav-item">
      <a class="payments nav-link {{ $received ? '' : 'active' }}" href="{{ route('payments.index', ['type' => 'made']) }}">
        <i class="bi bi-arrow-up-circle me-1"></i>Made
      </a>
    </li>
  </ul>

  <div class="card border-0 shadow-sm overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th class="ps-4">Date</th>
            <th>{{ $received ? 'Customer' : 'Supplier' }}</th>
            <th>{{ $received ? 'Invoice #' : 'PO #' }}</th>
            <th>Method</th>
            <th>Reference</th>
            <th class="text-end">Amount</th>
            <th class="text-center pe-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($payments as $payment)
          @php
              $party = $received ? $payment->customer_name : $payment->supplier_name;
              $editData = [
                  'url' => route('payments.update', [$type, $payment->id]),
                  'party' => $party,
                  'amount' => $payment->amount,
                  'date' => $payment->payment_date->format('Y-m-d'),
                  'method' => $payment->method,
                  'reference' => $payment->reference,
                  'notes' => $payment->notes,
              ];
          @endphp
          <tr>
            <td class="ps-4 text-muted">{{ $payment->payment_date->format('d M Y') }}</td>
            <td class="fw-semibold">{{ $party }}</td>
            <td>
              @if ($received && $payment->invoice)
                <span class="badge bg-primary bg-opacity-10 text-primary">#{{ $payment->invoice->invoice_no }}</span>
              @elseif (! $received && $payment->purchase?->purchase_no)
                <span class="badge bg-success bg-opacity-10 text-success">{{ $payment->purchase->purchase_no }}</span>
              @else
                <span class="text-muted">-</span>
              @endif
            </td>
            <td>{{ ucfirst(str_replace('_', ' ', (string) $payment->method)) }}</td>
            <td class="text-muted small">{{ $payment->reference ?? '-' }}</td>
            <td class="text-end fw-bold text-{{ $received ? 'success' : 'danger' }}">{{ rs($payment->amount) }}</td>
            <td class="text-center pe-3">
              <div class="d-flex justify-content-center gap-1">
                <button type="button" class="btn btn-sm btn-outline-primary" title="Edit"
                  data-edit-payment='@json($editData)'>
                  <i class="bi bi-pencil"></i>
                </button>
                @can('delete-records')
                <form method="POST" action="{{ route('payments.destroy', [$type, $payment->id]) }}"
                      onsubmit="return confirm('Delete this payment? The {{ $received ? 'invoice' : 'purchase' }} balance will be reversed.')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
                @endcan
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="7" class="text-center py-5 text-muted">No payments recorded yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer bg-light border-0 py-3 px-4 text-end">
      <span class="fw-bold">Total {{ $received ? 'Received' : 'Made' }}:
        <span class="text-{{ $received ? 'success' : 'danger' }}">{{ rs($total) }}</span>
      </span>
    </div>
  </div>
</div>

<!-- ── Edit Payment Modal ────────────────────────────────── -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <form method="POST" id="editForm">
        @csrf
        @method('PUT')

        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold">Edit Payment {{ $received ? 'Received' : 'Made' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold small">{{ $received ? 'Customer' : 'Supplier' }} Name</label>
            <input type="text" name="party_name" id="edit_party_name" class="form-control" required>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Amount ({{ currency() }})</label>
              <input type="number" name="amount" id="edit_amount" class="form-control" step="0.01" min="0.01" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Payment Date</label>
              <input type="date" name="payment_date" id="edit_payment_date" class="form-control" required>
            </div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Method</label>
              <select name="method" id="edit_method" class="form-select">
                @foreach (\App\Models\PaymentReceived::METHODS as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Reference #</label>
              <input type="text" name="reference" id="edit_reference" class="form-control">
            </div>
          </div>
          <div class="mb-1">
            <label class="form-label fw-semibold small">Notes</label>
            <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
          </div>
        </div>

        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Payment</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('click', e => {
    const btn = e.target.closest('[data-edit-payment]');
    if (!btn) return;
    const p = JSON.parse(btn.dataset.editPayment);

    document.getElementById('editForm').action = p.url;
    document.getElementById('edit_party_name').value = p.party ?? '';
    document.getElementById('edit_amount').value = p.amount;
    document.getElementById('edit_payment_date').value = p.date;
    document.getElementById('edit_method').value = p.method ?? 'bank_transfer';
    document.getElementById('edit_reference').value = p.reference ?? '';
    document.getElementById('edit_notes').value = p.notes ?? '';

    bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show();
});
</script>
@endpush
