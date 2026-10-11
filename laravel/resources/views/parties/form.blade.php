@extends('layouts.app')

@php($plural = $kind.'s')
@php($editing = $party->exists)

@section('title', $editing ? 'Edit '.$party->name : 'Add '.ucfirst($kind))

@section('content')
<div class="animate-fade">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 mb-0">
      <i class="bi bi-{{ $editing ? 'pencil' : 'plus-circle' }} me-2 text-primary"></i>{{ $editing ? 'Edit '.ucfirst($kind) : 'Add '.ucfirst($kind) }}
    </h2>
    <a href="{{ $editing ? route($plural.'.show', $party) : route($plural.'.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <form method="POST" action="{{ $editing ? route($plural.'.update', $party) : route($plural.'.store') }}">
            @csrf
            @if ($editing) @method('PUT') @endif
            <div class="row g-3">
              <div class="col-12">
                <label for="name" class="form-label fw-semibold small">{{ $kind === 'customer' ? 'Customer / Company' : 'Supplier' }} Name <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $party->name) }}" required maxlength="255" autofocus>
                @if ($editing)
                  <div class="form-text">Renaming also updates the name on this {{ $kind }}'s existing documents.</div>
                @endif
              </div>
              <div class="col-md-6">
                <label for="contact_person" class="form-label fw-semibold small">Contact Person</label>
                <input type="text" id="contact_person" name="contact_person" class="form-control" value="{{ old('contact_person', $party->contact_person) }}">
              </div>
              <div class="col-md-6">
                <label for="phone" class="form-label fw-semibold small">Phone</label>
                <input type="tel" id="phone" name="phone" class="form-control" value="{{ old('phone', $party->phone) }}">
              </div>
              <div class="col-12">
                <label for="email" class="form-label fw-semibold small">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $party->email) }}">
                @if ($kind === 'customer')
                  <div class="form-text">Used when emailing invoices, quotations and job notes.</div>
                @endif
              </div>
              <div class="col-12">
                <label for="address" class="form-label fw-semibold small">Address</label>
                <textarea id="address" name="address" class="form-control" rows="2">{{ old('address', $party->address) }}</textarea>
              </div>
              <div class="col-12">
                <label for="notes" class="form-label fw-semibold small">Notes</label>
                <textarea id="notes" name="notes" class="form-control" rows="2">{{ old('notes', $party->notes) }}</textarea>
              </div>
            </div>
            <div class="d-flex gap-2 justify-content-end mt-4">
              <a href="{{ $editing ? route($plural.'.show', $party) : route($plural.'.index') }}" class="btn btn-outline-secondary">Cancel</a>
              <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $editing ? 'Save Changes' : 'Add '.ucfirst($kind) }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
