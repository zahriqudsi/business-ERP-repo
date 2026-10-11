@extends('layouts.app')

@section('title', 'Quotations')

@section('content')
<div class="animate-fade">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h2 class="h3 mb-1 text-dark">Quotation Management</h2>
            <p class="text-muted small">Manage, view, and track all prepared quotations.</p>
        </div>
        <div>
            <a href="{{ route('quotations.create') }}" class="btn btn-success shadow-sm">
                <i class="bi bi-file-earmark-plus me-2"></i>New Quotation
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
                        <input type="text" id="searchInput" placeholder="Search quotations..."
                            class="form-control border-start-0 ps-0">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table data-list-table data-empty-text="No quotations found matching your search." class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Quotation #</th>
                        <th>Prepared For</th>
                        <th>Date</th>
                        <th>Sub Total</th>
                        <th class="text-center pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quotations as $quotation)
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold text-success">{{ $quotation->quotation_no }}</span>
                            <div class="text-muted extra-small">ID: {{ $quotation->id }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $quotation->prepared_for }}</div>
                        </td>
                        <td>
                            <div class="text-muted"><i class="bi bi-calendar-event me-2"></i>{{ $quotation->date?->format('Y-m-d') }}</div>
                        </td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary fs-6 fw-bold">
                                {{ rs($quotation->items_sum_total) }}
                            </span>
                        </td>
                        <td class="text-center pe-4">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="action-btn-circle btn-light-info" title="View Details"
                                    data-view-url="{{ route('quotations.show', ['quotation' => $quotation, 'ajax' => 1]) }}">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a href="{{ route('quotations.edit', $quotation) }}" class="action-btn-circle btn-light-primary" title="Edit Quotation">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @can('delete-records')
                                <button type="button" class="action-btn-circle btn-light-danger" title="Delete Quotation"
                                    data-delete-url="{{ route('quotations.destroy', $quotation) }}"
                                    data-delete-label="quotation #{{ $quotation->quotation_no }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr data-empty><td colspan="5" class="text-center py-5 text-muted">No quotations found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Summary Footer -->
        <div class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <span class="text-muted small">Showing all records</span>
            <div class="h5 mb-0 fw-bold">
                <span class="text-muted small fw-normal me-2">Aggregate Total:</span>
                <span class="text-dark">{{ rs($grandTotal) }}</span>
            </div>
        </div>
    </div>

    <div id="pagination" class="d-flex justify-content-center mt-4"></div>
</div>

@include('partials.modals', ['document' => 'Quotation Document', 'delete' => 'Delete Permanently'])
@endsection

@push('scripts')
<script src="{{ asset('assets/js/list-table.js') }}"></script>
@endpush
