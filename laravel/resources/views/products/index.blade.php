@extends('layouts.app')

@section('title', 'Products')

@php($stockBadge = ['In stock' => 'success', 'Low stock' => 'warning text-dark', 'Out of stock' => 'danger', 'Service' => 'secondary'])

@section('content')
<div class="animate-fade">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
      <h2 class="h3 mb-1"><i class="bi bi-box-seam me-2 text-primary"></i>Products & Stock</h2>
      <p class="text-muted small mb-0">Pick these on invoices, quotations and purchases. Stock updates automatically.</p>
    </div>
    <a href="{{ route('products.create') }}" class="btn btn-primary shadow-sm"><i class="bi bi-plus-lg me-2"></i>Add Product</a>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="text-muted small">Active products</div>
        <div class="fs-4 fw-bold">{{ (int) $summary->products }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="text-muted small">Stock value (cost)</div>
        <div class="fs-4 fw-bold">{{ rs_short($summary->stock_value) }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="text-muted small">Stock value (selling)</div>
        <div class="fs-4 fw-bold text-success">{{ rs_short($summary->retail_value) }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <a href="{{ route('products.index', ['filter' => 'low']) }}" class="card border-0 shadow-sm p-3 h-100 text-decoration-none">
        <div class="text-muted small">Low / out of stock</div>
        <div class="fs-4 fw-bold {{ $lowStockCount ? 'text-danger' : 'text-muted' }}">{{ $lowStockCount }}</div>
      </a>
    </div>
  </div>

  <form method="GET" class="card border-0 shadow-sm p-3 mb-4">
    <div class="row g-2 align-items-center">
      <div class="col-md">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Search name, SKU, brand or category">
        </div>
      </div>
      <div class="col-md-auto">
        <select name="filter" class="form-select" onchange="this.form.submit()">
          @foreach (\App\Http\Controllers\ProductController::FILTERS as $value => $label)
            <option value="{{ $value }}" @selected($filter === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-auto"><button class="btn btn-outline-primary w-100">Search</button></div>
    </div>
  </form>

  <div class="card border-0 shadow-sm overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th class="ps-4">Product</th>
            <th>Category</th>
            <th class="text-end">Price</th>
            <th class="text-end">Cost</th>
            <th class="text-center">In stock</th>
            <th class="pe-4">Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($products as $product)
          <tr class="cursor-pointer" onclick="window.location='{{ route('products.show', $product) }}'">
            <td class="ps-4">
              <a href="{{ route('products.show', $product) }}" class="fw-semibold text-decoration-none">{{ $product->name }}</a>
              <div class="small text-muted">
                @if ($product->sku)<span class="font-monospace">{{ $product->sku }}</span>@endif
                @if ($product->sku && $product->brand) · @endif
                {{ $product->brand }}
              </div>
            </td>
            <td class="text-muted">{{ $product->category ?: '-' }}</td>
            <td class="text-end fw-semibold">{{ rs($product->unit_price) }}</td>
            <td class="text-end text-muted">{{ rs($product->cost_price) }}</td>
            <td class="text-center fw-bold {{ $product->track_stock && $product->stock_qty <= 0 ? 'text-danger' : '' }}">{{ $product->track_stock ? $product->stock_qty : '—' }}</td>
            <td class="pe-4">
              <span class="badge bg-{{ $stockBadge[$product->stock_status] }}">{{ $product->stock_status }}</span>
              @unless ($product->is_active)<span class="badge bg-light text-muted border">Inactive</span>@endunless
            </td>
          </tr>
          @empty
          <tr><td colspan="6" class="text-center py-5 text-muted">
            @if ($search !== '' || $filter !== 'all')
              No products match.
            @else
              No products yet. <a href="{{ route('products.create') }}">Add your first product</a>.
            @endif
          </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @if ($products->hasPages())
    <div class="mt-4 d-flex justify-content-center">{{ $products->links() }}</div>
  @endif
</div>
@endsection
