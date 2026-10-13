@extends('layouts.app')

@php($editing = $invoice->exists)

@section('title', $editing ? 'Edit Invoice' : 'New Invoice')

@section('content')
<div class="animate-fade">
    <div class="mb-4">
        @if ($editing)
            <h2 class="h3 mb-1 text-dark">Edit Invoice</h2>
            <p class="text-muted small">Update the details for Invoice #{{ $invoice->invoice_no }}.</p>
        @else
            <h2 class="h3 mb-1 text-dark">Create New Invoice</h2>
            <p class="text-muted small">Fill in the details below to generate a professional invoice.</p>
        @endif
    </div>

    <form id="invoiceForm" data-ajax-form="{{ $editing ? 'redirect' : 'modal' }}"
          action="{{ $editing ? route('invoices.update', $invoice) : route('invoices.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <!-- Invoice Header Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-5">
                        <label for="customer_name" class="form-label small fw-bold text-muted text-uppercase">Customer Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                            <input type="text" id="customer_name" name="customer_name" value="{{ $invoice->customer_name }}"
                                class="form-control border-start-0 ps-0" placeholder="Client or Company Name" list="customer-names" autocomplete="off" required>
                        </div>
                        @include('partials.party-datalist', ['id' => 'customer-names', 'names' => $customerNames])
                    </div>
                    <div class="col-md-3">
                        <label for="date" class="form-label small fw-bold text-muted text-uppercase">Invoice Date</label>
                        <input type="date" id="date" name="date" value="{{ $invoice->date?->format('Y-m-d') }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label for="invoice_no" class="form-label small fw-bold text-muted text-uppercase">Invoice Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-hash text-muted"></i></span>
                            <input type="text" id="invoice_no" name="invoice_no" value="{{ $invoice->invoice_no }}"
                                class="form-control border-start-0 ps-0 fw-bold text-primary" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('partials.line-items', [
            'items' => $invoice->items ?? collect(),
            'heading' => 'Billing Items',
            'totalLabel' => 'Grand Total',
            'accent' => 'primary',
            'document' => $invoice,
        ])

        <div class="row g-4 mb-5">
            <!-- Terms Card -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-dark small fw-bold text-uppercase">Terms & Conditions</h5>
                    </div>
                    <div class="card-body">
                        <textarea name="terms_conditions" class="form-control form-control-sm bg-light-focus" rows="5" placeholder="Enter terms and conditions here...">{{ $invoice->terms_conditions ?: config('sjpos.invoice.default_terms') }}</textarea>
                        <div class="mt-2 text-muted extra-small">
                            <i class="bi bi-info-circle me-1"></i> These will be printed at the bottom of the invoice.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Info Card -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-dark small fw-bold text-uppercase">Additional Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="info_type" class="form-label small fw-semibold text-muted">Display on Invoice:</label>
                            <select id="info_type" name="details_type" class="form-select" onchange="toggleInfoType()">
                                <option value="">None</option>
                                <option value="bank" @selected($detailsType === 'bank')>Bank Details</option>
                                <option value="notes" @selected($detailsType === 'notes')>Notes/Instructions</option>
                            </select>
                        </div>

                        <!-- Bank Details Section -->
                        <div id="bank_details_section" class="bg-light rounded p-3 mb-0" style="display:none;">
                            <div class="row g-3">
                                @foreach (['Account' => 'account_name', 'Bank' => 'bank_name', 'Number' => 'account_number', 'Branch' => 'branch'] as $label => $key)
                                <div class="col-6">
                                    <div class="small fw-bold text-muted mb-1 text-uppercase" style="font-size:10px">{{ $label }}</div>
                                    <div class="fw-semibold small">{{ config("sjpos.bank.$key") }}</div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Notes Section -->
                        <div id="notes_section" class="bg-light rounded p-3 mb-0" style="display:none;">
                            <label class="small fw-bold text-muted mb-2 text-uppercase d-block" style="font-size:10px">Preview Note</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control form-control-sm bg-white" readonly>{{ config('sjpos.invoice.default_note') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-3 mt-2">
            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary px-5 py-2">
                <i class="bi bi-x-circle me-1"></i> {{ $editing ? 'Discard Changes' : 'Cancel' }}
            </a>
            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                <i class="bi bi-check2-circle me-1"></i> {{ $editing ? 'Save & Update Invoice' : 'Save & Generate Invoice' }}
            </button>
        </div>
    </form>
</div>

@include('partials.modals', [
    'status' => 'Invoice Status',
    'warning' => 'You cannot remove the first row of the invoice. At least one item must exist.',
])
@endsection

@push('scripts')
<script src="{{ asset('assets/js/line-items.js') }}"></script>
@endpush
