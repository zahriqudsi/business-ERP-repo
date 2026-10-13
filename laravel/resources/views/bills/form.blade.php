@extends('layouts.app')

@php($editing = $bill->exists)

@section('title', $editing ? 'Edit Bill' : 'Add Vendor Bill')

@section('content')
<div class="animate-fade">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      @if ($editing)
        <h2 class="h3 mb-0"><i class="bi bi-pencil me-2"></i>Edit Bill</h2>
      @else
        <h2 class="h3 mb-1"><i class="bi bi-plus-circle me-2 text-warning"></i>Add Vendor Bill</h2>
        <p class="text-muted small">Record a supplier bill or payable.</p>
      @endif
    </div>
    <a href="{{ route('bills.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm">
        <div class="card-header fw-semibold"><i class="bi bi-file-earmark-text me-2"></i>Bill Details</div>
        <div class="card-body">
          <form method="POST" action="{{ $editing ? route('bills.update', $bill) : route('bills.store') }}">
            @csrf
            @if ($editing) @method('PUT') @endif
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold small">Supplier Name <span class="text-danger">*</span></label>
                <input type="text" name="supplier_name" class="form-control" value="{{ old('supplier_name', $bill->supplier_name) }}" list="supplier-names" autocomplete="off" required>
                @include('partials.party-datalist', ['id' => 'supplier-names', 'names' => $supplierNames])
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small">Bill Number</label>
                <input type="text" name="bill_no" class="form-control" value="{{ old('bill_no', $bill->bill_no) }}" placeholder="BILL-001">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small">Bill Date <span class="text-danger">*</span></label>
                <input type="date" name="bill_date" class="form-control" value="{{ old('bill_date', $bill->bill_date?->format('Y-m-d')) }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small">Due Date</label>
                <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $bill->due_date?->format('Y-m-d')) }}">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small">Total Amount <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text">{{ currency() }}</span>
                  <input type="number" name="amount" class="form-control" value="{{ old('amount', $bill->amount) }}" step="0.01" min="0.01" required>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small">{{ $editing ? 'Amount Paid' : 'Amount Already Paid' }}</label>
                <div class="input-group">
                  <span class="input-group-text">{{ currency() }}</span>
                  <input type="number" name="amount_paid" class="form-control" value="{{ old('amount_paid', $bill->amount_paid) }}" step="0.01" min="0">
                </div>
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold small">Notes</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $bill->notes) }}</textarea>
              </div>
            </div>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-warning"><i class="bi bi-check-lg me-2"></i>{{ $editing ? 'Update Bill' : 'Save Bill' }}</button>
              <a href="{{ route('bills.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
