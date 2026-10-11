@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
<div class="animate-fade">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h2 class="h3 mb-1">Invoice Management</h2>
            <p class="text-muted small">Manage, view, and track all customer invoices.</p>
        </div>
        <div>
            <a href="{{ route('invoices.create') }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-lg me-2"></i>New Invoice
            </a>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchInput" placeholder="Search by customer or invoice #..."
                            class="form-control border-start-0 ps-0">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table data-list-table data-empty-text="No invoices found matching your search." class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Invoice #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th class="text-center pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold text-primary">{{ $invoice->invoice_no }}</span>
                            <div class="text-muted extra-small">ID: {{ $invoice->id }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $invoice->customer_name }}</div>
                        </td>
                        <td>
                            <div class="text-muted"><i class="bi bi-calendar-event me-2"></i>{{ $invoice->date?->format('Y-m-d') }}</div>
                        </td>
                        <td>
                            <span class="badge bg-success bg-opacity-10 text-success fs-6 fw-bold">
                                {{ rs($invoice->items_sum_total) }}
                            </span>
                        </td>
                        <td class="text-center pe-4">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="action-btn-circle btn-light-info" title="View Details"
                                    data-view-url="{{ route('invoices.show', ['invoice' => $invoice, 'ajax' => 1]) }}">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a href="{{ route('invoices.edit', $invoice) }}" class="action-btn-circle btn-light-primary" title="Edit Invoice">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @can('delete-records')
                                <button type="button" class="action-btn-circle btn-light-danger" title="Delete Invoice"
                                    data-delete-url="{{ route('invoices.destroy', $invoice) }}"
                                    data-delete-label="invoice #{{ $invoice->invoice_no }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr data-empty><td colspan="5" class="text-center py-5 text-muted">No invoices found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Summary Footer -->
        <div class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <span class="text-muted small">Showing all records</span>
            <div class="h5 mb-0 fw-bold">
                <span class="text-muted small fw-normal me-2">Grand Total:</span>
                <span class="text-dark">{{ rs($grandTotal) }}</span>
            </div>
        </div>
    </div>

    <div id="pagination" class="d-flex justify-content-center mt-4"></div>
</div>

@include('partials.modals', ['document' => 'Invoice Document', 'delete' => 'Delete Invoice'])
@endsection

@push('scripts')
<script src="{{ asset('assets/js/list-table.js') }}"></script>
@endpush
