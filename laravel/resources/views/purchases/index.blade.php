@extends('layouts.app')

@section('title', 'Purchases')

@section('content')
<div class="animate-fade">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
      <h2 class="h3 mb-1"><i class="bi bi-truck me-2 text-success"></i>Purchase Orders</h2>
      <p class="text-muted small">Manage purchases from suppliers.</p>
    </div>
    <a href="{{ route('purchases.create') }}" class="btn btn-success shadow-sm"><i class="bi bi-plus-lg me-2"></i>New Purchase</a>
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <input type="text" id="searchInput" placeholder="Search by supplier or PO #..." class="form-control">
    </div>
  </div>

  @php($statusBadge = ['draft' => 'secondary', 'confirmed' => 'primary', 'paid' => 'success', 'cancelled' => 'danger'])

  <div class="card border-0 shadow-sm overflow-hidden">
    <div class="table-responsive">
      <table data-list-table data-per-page="20" data-empty-text="No purchases found matching your search." class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th class="ps-4">PO #</th>
            <th>Supplier</th>
            <th>Date</th>
            <th>Total</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Status</th>
            <th class="text-center pe-4">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($purchases as $purchase)
          <tr>
            <td class="ps-4 fw-bold text-primary">{{ $purchase->display_no }}</td>
            <td class="fw-semibold">{{ $purchase->supplier_name }}</td>
            <td class="text-muted">{{ $purchase->date->format('d M Y') }}</td>
            <td class="fw-bold">{{ rs($purchase->subtotal) }}</td>
            <td class="text-success">{{ rs($purchase->amount_paid) }}</td>
            <td class="{{ $purchase->balance > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ rs($purchase->balance) }}</td>
            <td><span class="badge bg-{{ $statusBadge[$purchase->status] ?? 'secondary' }}">{{ ucfirst($purchase->status) }}</span></td>
            <td class="text-center pe-4">
              <div class="d-flex justify-content-center gap-2">
                <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                @can('delete-records')
                <form method="POST" action="{{ route('purchases.destroy', $purchase) }}" onsubmit="return confirm('Delete this purchase?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                </form>
                @endcan
              </div>
            </td>
          </tr>
          @empty
          <tr data-empty><td colspan="8" class="text-center py-5 text-muted">No purchases found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center">
      <span class="text-muted small">Showing all records</span>
      <span class="fw-bold">
        Grand Total: {{ rs($purchases->sum('subtotal')) }}
        &nbsp;|&nbsp;
        <span class="text-success">Paid: {{ rs($purchases->sum('amount_paid')) }}</span>
      </span>
    </div>
  </div>

  <div id="pagination" class="d-flex justify-content-center mt-4"></div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/list-table.js') }}"></script>
@endpush
