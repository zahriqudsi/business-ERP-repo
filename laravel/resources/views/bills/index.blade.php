@extends('layouts.app')

@section('title', 'Vendor Bills')

@section('content')
<div class="animate-fade">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
      <h2 class="h3 mb-1"><i class="bi bi-file-earmark-text me-2 text-warning"></i>Vendor Bills</h2>
      <p class="text-muted small">Track supplier bills and payables.</p>
    </div>
    <a href="{{ route('bills.create') }}" class="btn btn-warning shadow-sm"><i class="bi bi-plus-lg me-2"></i>Add Bill</a>
  </div>

  <!-- Summary KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm text-center p-3">
        <div class="fs-4 fw-bold text-warning">{{ rs($summary->total, 0) }}</div>
        <div class="text-muted small">Total Bills</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm text-center p-3">
        <div class="fs-4 fw-bold text-danger">{{ rs($summary->unpaid, 0) }}</div>
        <div class="text-muted small">Outstanding</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm text-center p-3">
        <div class="fs-4 fw-bold text-danger">{{ (int) $summary->cnt_pending }}</div>
        <div class="text-muted small">Pending Bills</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm text-center p-3">
        <div class="fs-4 fw-bold text-dark">{{ rs($summary->overdue, 0) }}</div>
        <div class="text-muted small">Overdue</div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th class="ps-4">Bill #</th>
            <th>Supplier</th>
            <th>Bill Date</th>
            <th>Due Date</th>
            <th>Amount</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Status</th>
            <th class="text-center pe-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($bills as $bill)
          <tr>
            <td class="ps-4 fw-bold text-warning">{{ $bill->display_no }}</td>
            <td class="fw-semibold">{{ $bill->supplier_name }}</td>
            <td class="text-muted">{{ $bill->bill_date->format('d M Y') }}</td>
            <td class="{{ $bill->is_overdue ? 'text-danger fw-bold' : 'text-muted' }}">
              {{ $bill->due_date?->format('d M Y') ?? '-' }}
            </td>
            <td class="fw-bold">{{ rs($bill->amount) }}</td>
            <td class="text-success">{{ rs($bill->amount_paid) }}</td>
            <td class="{{ $bill->balance > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ rs($bill->balance) }}</td>
            <td><span class="badge bg-{{ \App\Models\VendorBill::STATUS_BADGES[$bill->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $bill->status)) }}</span></td>
            <td class="text-center pe-3">
              <div class="d-flex justify-content-center gap-1">
                <a href="{{ route('bills.edit', $bill) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                @can('delete-records')
                <form method="POST" action="{{ route('bills.destroy', $bill) }}" onsubmit="return confirm('Delete this bill?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                </form>
                @endcan
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="9" class="text-center py-5 text-muted">No bills yet. <a href="{{ route('bills.create') }}">Add your first bill</a>.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
