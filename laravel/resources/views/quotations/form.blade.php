@extends('layouts.app')

@php($editing = $quotation->exists)

@section('title', $editing ? 'Edit Quotation' : 'New Quotation')

@section('content')
<div class="animate-fade">
    <div class="mb-4">
        @if ($editing)
            <h2 class="h3 mb-1 text-dark">Edit Quotation</h2>
            <p class="text-muted small">Update Price Estimate #{{ $quotation->quotation_no }}.</p>
        @else
            <h2 class="h3 mb-1 text-dark">Create New Quotation</h2>
            <p class="text-muted small">Prepare a professional price estimate for your client.</p>
        @endif
    </div>

    <form id="quotationForm" data-ajax-form="{{ $editing ? 'redirect' : 'modal' }}"
          action="{{ $editing ? route('quotations.update', $quotation) : route('quotations.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <!-- Quotation Header Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label for="prepared_for" class="form-label small fw-bold text-muted text-uppercase">Prepared For</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                            <input type="text" id="prepared_for" name="prepared_for" class="form-control border-start-0 ps-0"
                                value="{{ $quotation->prepared_for }}" placeholder="Client Name" list="customer-names" autocomplete="off" required>
                        </div>
                        @include('partials.party-datalist', ['id' => 'customer-names', 'names' => $customerNames])
                    </div>
                    <div class="col-md-2">
                        <label for="date" class="form-label small fw-bold text-muted text-uppercase">Date</label>
                        <input type="date" id="date" name="date" class="form-control" value="{{ $quotation->date?->format('Y-m-d') }}" required readonly>
                    </div>
                    <div class="col-md-2">
                        <label for="validity_date" class="form-label small fw-bold text-muted text-uppercase">Valid Until</label>
                        <input type="date" id="validity_date" name="validity_date" class="form-control"
                            value="{{ $quotation->validity_date?->format('Y-m-d') }}" @unless ($editing) min="{{ now()->toDateString() }}" @endunless required>
                    </div>
                    <div class="col-md-2">
                        <label for="prepared_by" class="form-label small fw-bold text-muted text-uppercase">Prepared By</label>
                        <input type="text" id="prepared_by" name="prepared_by" class="form-control"
                            value="{{ $quotation->prepared_by }}" placeholder="Your Name" required>
                    </div>
                    <div class="col-md-2">
                        <label for="quotation_no" class="form-label small fw-bold text-muted text-uppercase">Quotation #</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-hash text-muted"></i></span>
                            <input type="text" id="quotation_no" name="quotation_no" class="form-control border-start-0 ps-0 fw-bold text-success"
                                value="{{ $quotation->quotation_no }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('partials.line-items', [
            'items' => $quotation->items ?? collect(),
            'heading' => 'Estimate Items',
            'totalLabel' => 'Total Estimate',
            'accent' => 'success',
            'document' => $quotation,
        ])

        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-dark small fw-bold text-uppercase">Terms & Conditions</h5>
                    </div>
                    <div class="card-body">
                        <textarea name="terms_conditions" class="form-control form-control-sm bg-light-focus" rows="5" placeholder="Enter terms and conditions here...">{{ $quotation->terms_conditions ?: config('sjpos.quotation.default_terms') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-3 mt-2">
            <a href="{{ route('quotations.index') }}" class="btn btn-outline-secondary px-5 py-2">
                <i class="bi bi-arrow-left-circle me-1"></i> Back
            </a>
            <button type="submit" class="btn btn-success px-5 py-2 fw-bold shadow-sm">
                <i class="bi bi-check2-circle me-1"></i> {{ $editing ? 'Save & Update Quotation' : 'Save & Generate Quotation' }}
            </button>
        </div>
    </form>
</div>

@include('partials.modals', [
    'status' => 'Quotation Status',
    'warning' => 'You cannot remove the first row of the quotation. At least one item must exist.',
])
@endsection

@push('scripts')
<script src="{{ asset('assets/js/line-items.js') }}"></script>
@endpush
