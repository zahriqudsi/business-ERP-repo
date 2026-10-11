@extends('layouts.app')

@php($editing = $product->exists)

@section('title', $editing ? 'Edit '.$product->name : 'Add Product')

@section('content')
<div class="animate-fade">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 mb-0"><i class="bi bi-{{ $editing ? 'pencil' : 'plus-circle' }} me-2 text-primary"></i>{{ $editing ? 'Edit Product' : 'Add Product' }}</h2>
    <a href="{{ $editing ? route('products.show', $product) : route('products.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-9">
      <form method="POST" action="{{ $editing ? route('products.update', $product) : route('products.store') }}" class="card border-0 shadow-sm">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="card-body p-4">
          <div class="row g-3">
            <div class="col-md-8">
              <label for="name" class="form-label fw-semibold small">Name <span class="text-danger">*</span></label>
              <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $product->name) }}" required maxlength="255" autofocus>
            </div>
            <div class="col-md-4">
              <label for="sku" class="form-label fw-semibold small">SKU / Code</label>
              <input type="text" id="sku" name="sku" class="form-control font-monospace" value="{{ old('sku', $product->sku) }}" maxlength="50">
            </div>
            <div class="col-md-6">
              <label for="brand" class="form-label fw-semibold small">Brand</label>
              <input type="text" id="brand" name="brand" class="form-control" value="{{ old('brand', $product->brand) }}" maxlength="100">
            </div>
            <div class="col-md-6">
              <label for="category" class="form-label fw-semibold small">Category</label>
              <input type="text" id="category" name="category" class="form-control" value="{{ old('category', $product->category) }}" maxlength="100" list="categories" placeholder="e.g. Storage, Accessories, Repairs">
              <datalist id="categories">
                @foreach ($categories as $category)
                  <option value="{{ $category }}"></option>
                @endforeach
              </datalist>
            </div>
            <div class="col-12">
              <label for="description" class="form-label fw-semibold small">Description</label>
              <textarea id="description" name="description" class="form-control" rows="2">{{ old('description', $product->description) }}</textarea>
            </div>
            <div class="col-md-6">
              <label for="unit_price" class="form-label fw-semibold small">Selling price <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">{{ currency() }}</span>
                <input type="number" id="unit_price" name="unit_price" class="form-control" value="{{ old('unit_price', $product->unit_price) }}" step="0.01" min="0" required>
              </div>
            </div>
            <div class="col-md-6">
              <label for="cost_price" class="form-label fw-semibold small">Cost price</label>
              <div class="input-group">
                <span class="input-group-text">{{ currency() }}</span>
                <input type="number" id="cost_price" name="cost_price" class="form-control" value="{{ old('cost_price', $product->cost_price) }}" step="0.01" min="0">
              </div>
              <div class="form-text">Used for profit and stock-value reports.</div>
            </div>

            <div class="col-12"><hr class="my-1"></div>

            <div class="col-12">
              <div class="form-check form-switch">
                <input type="hidden" name="track_stock" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="track_stock" name="track_stock" value="1" @checked(old('track_stock', $product->track_stock))>
                <label class="form-check-label fw-semibold small" for="track_stock">Track stock</label>
                <div class="form-text mt-0">Turn off for services such as repairs or labour.</div>
              </div>
            </div>
            <div class="col-md-6 stock-field">
              <label for="reorder_level" class="form-label fw-semibold small">Reorder level</label>
              <input type="number" id="reorder_level" name="reorder_level" class="form-control" value="{{ old('reorder_level', $product->reorder_level) }}" min="0">
              <div class="form-text">Shown as “low stock” at or below this quantity.</div>
            </div>
            @unless ($editing)
            <div class="col-md-6 stock-field">
              <label for="opening_stock" class="form-label fw-semibold small">Opening stock</label>
              <input type="number" id="opening_stock" name="opening_stock" class="form-control" value="{{ old('opening_stock', 0) }}" min="0">
              <div class="form-text">How many you have right now.</div>
            </div>
            @endunless
            <div class="col-12">
              <div class="form-check form-switch">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $product->is_active))>
                <label class="form-check-label fw-semibold small" for="is_active">Active</label>
                <div class="form-text mt-0">Inactive products are hidden from the pickers but kept on old documents.</div>
              </div>
            </div>
          </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2 p-3">
          <a href="{{ $editing ? route('products.show', $product) : route('products.index') }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $editing ? 'Save Changes' : 'Add Product' }}</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('track_stock');
  const sync = () => document.querySelectorAll('.stock-field').forEach(el => el.classList.toggle('d-none', !toggle.checked));
  toggle.addEventListener('change', sync);
  sync();
});
</script>
@endpush
