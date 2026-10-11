@extends('layouts.app')

@php($editing = $purchase->exists)
@php($items = $editing && $purchase->items->isNotEmpty() ? $purchase->items : collect([null]))

@section('title', $editing ? 'Edit Purchase' : 'New Purchase')

@section('content')
<div class="animate-fade">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      @if ($editing)
        <h2 class="h3 mb-0"><i class="bi bi-pencil me-2"></i>Edit Purchase</h2>
      @else
        <h2 class="h3 mb-1"><i class="bi bi-plus-circle me-2 text-success"></i>New Purchase Order</h2>
        <p class="text-muted small">Record a purchase from a supplier.</p>
      @endif
    </div>
    <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>

  <form method="POST" action="{{ $editing ? route('purchases.update', $purchase) : route('purchases.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header fw-semibold"><i class="bi bi-info-circle me-2"></i>Purchase Details</div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold small">Supplier Name <span class="text-danger">*</span></label>
                <input type="text" name="supplier_name" class="form-control" value="{{ old('supplier_name', $purchase->supplier_name) }}" list="supplier-names" autocomplete="off" required>
                @include('partials.party-datalist', ['id' => 'supplier-names', 'names' => $supplierNames])
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold small">PO Number</label>
                <input type="text" name="purchase_no" class="form-control" value="{{ old('purchase_no', $purchase->purchase_no) }}" placeholder="PO-001">
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold small">Date <span class="text-danger">*</span></label>
                <input type="date" name="date" class="form-control" value="{{ old('date', $purchase->date?->format('Y-m-d')) }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small">Status</label>
                <select name="status" class="form-select">
                  @foreach (\App\Models\Purchase::STATUSES as $status)
                  <option value="{{ $status }}" @selected(old('status', $purchase->status) === $status)>{{ ucfirst($status) }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small">Notes</label>
                <input type="text" name="notes" class="form-control" value="{{ old('notes', $purchase->notes) }}" placeholder="Optional notes...">
              </div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-header fw-semibold"><i class="bi bi-list-ul me-2"></i>Items</div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table align-middle mb-0" data-product-price="cost">
                <thead class="table-light">
                  <tr>
                    <th class="ps-3">Description</th>
                    <th>Brand</th>
                    <th style="width:80px">Qty</th>
                    <th style="width:120px">Unit Price</th>
                    <th style="width:120px">Total</th>
                    <th style="width:50px"></th>
                  </tr>
                </thead>
                <tbody id="itemsBody">
                  @foreach ($items as $item)
                  <tr>
                    <td class="ps-3">
                      <input type="text" class="form-control form-control-sm product-picker mb-1" list="product-list" value="{{ $item?->product?->picker_label }}" placeholder="Pick a product (optional)" autocomplete="off" aria-label="Product">
                      <input type="hidden" name="product_id[]" value="{{ $item?->product_id }}">
                      <input type="text" name="description[]" class="form-control form-control-sm" value="{{ $item?->description }}" placeholder="Description" {{ $loop->first && ! $editing ? 'required' : '' }}>
                      <span class="product-stock small"></span>
                    </td>
                    <td><input type="text" name="brand[]" class="form-control form-control-sm" value="{{ $item?->brand }}" placeholder="Brand"></td>
                    <td><input type="number" name="quantity[]" class="form-control form-control-sm qty" value="{{ $item?->quantity ?? 1 }}" min="0" oninput="calcRow(this)"></td>
                    <td><input type="number" name="unit_price[]" class="form-control form-control-sm price" value="{{ $item?->unit_price ?? 0 }}" step="0.01" min="0" oninput="calcRow(this)"></td>
                    <td><input type="number" class="form-control form-control-sm row-total" value="{{ number_format((float) ($item?->total ?? 0), 2, '.', '') }}" readonly tabindex="-1"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-dash"></i></button></td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            @include('partials.product-picker')
            <div class="p-3 border-top d-flex justify-content-between align-items-center">
              <button type="button" class="btn btn-outline-success btn-sm" onclick="addRow()"><i class="bi bi-plus me-1"></i>Add Item</button>
              <div class="fw-bold fs-5">Total: {{ currency() }} <span id="grandTotal">{{ number_format((float) $purchase->subtotal, 2) }}</span></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
          <div class="card-body d-grid gap-2">
            <button type="submit" class="btn btn-{{ $editing ? 'primary' : 'success' }}"><i class="bi bi-check-lg me-2"></i>{{ $editing ? 'Update Purchase' : 'Save Purchase' }}</button>
            <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
function calcRow(el) {
  const row = el.closest('tr');
  const qty = parseFloat(row.querySelector('.qty').value) || 0;
  const price = parseFloat(row.querySelector('.price').value) || 0;
  row.querySelector('.row-total').value = (qty * price).toFixed(2);
  updateGrandTotal();
}
function updateGrandTotal() {
  let total = 0;
  document.querySelectorAll('.row-total').forEach(e => total += parseFloat(e.value) || 0);
  document.getElementById('grandTotal').textContent = total.toFixed(2);
}
function addRow() {
  const tbody = document.getElementById('itemsBody');
  const row = tbody.rows[0].cloneNode(true);
  row.querySelectorAll('input').forEach(i => {
    if (i.classList.contains('qty')) i.value = 1;
    else if (i.type === 'number') i.value = 0;
    else i.value = '';
  });
  row.querySelectorAll('.product-stock').forEach(hint => { hint.textContent = ''; });
  tbody.appendChild(row);
}
function removeRow(btn) {
  const tbody = document.getElementById('itemsBody');
  if (tbody.rows.length > 1) { btn.closest('tr').remove(); updateGrandTotal(); }
}
</script>
@endpush
