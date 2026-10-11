@extends('layouts.app')

@section('title', $product->name)

@php($stockBadge = ['In stock' => 'success', 'Low stock' => 'warning text-dark', 'Out of stock' => 'danger', 'Service' => 'secondary'])
@php($typeBadge = ['sale' => ['Sale', 'primary'], 'purchase' => ['Purchase', 'success'], 'adjustment' => ['Adjustment', 'secondary']])

@section('content')
<div class="animate-fade">
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
    <div>
      <a href="{{ route('products.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>All products</a>
      <h2 class="h3 mt-1 mb-1">{{ $product->name }}</h2>
      <div class="d-flex flex-wrap gap-2 align-items-center small text-muted">
        @if ($product->sku)<span class="font-monospace">{{ $product->sku }}</span>@endif
        @if ($product->brand)<span>{{ $product->brand }}</span>@endif
        @if ($product->category)<span class="badge bg-light text-dark border">{{ $product->category }}</span>@endif
        <span class="badge bg-{{ $stockBadge[$product->stock_status] }}">{{ $product->stock_status }}</span>
        @unless ($product->is_active)<span class="badge bg-light text-muted border">Inactive</span>@endunless
      </div>
      @if ($product->description)<p class="small mt-2 mb-0">{{ $product->description }}</p>@endif
    </div>
    <div class="d-flex gap-2 flex-wrap">
      @if ($product->track_stock)
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#adjustModal"><i class="bi bi-arrow-left-right me-1"></i>Adjust stock</button>
      @endif
      <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
      @can('delete-records')
      <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Delete</button>
      </form>
      @endcan
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="text-muted small">In stock</div>
        <div class="fs-3 fw-bold {{ $product->track_stock && $product->stock_qty <= 0 ? 'text-danger' : '' }}">{{ $product->track_stock ? $product->stock_qty : '—' }}</div>
        @if ($product->track_stock && $product->reorder_level !== null)<div class="small text-muted">Reorder at {{ $product->reorder_level }}</div>@endif
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="text-muted small">Selling price</div>
        <div class="fs-4 fw-bold">{{ rs($product->unit_price) }}</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="text-muted small">Cost price</div>
        <div class="fs-4 fw-bold">{{ rs($product->cost_price) }}</div>
        @if ($product->unit_price > 0 && $product->cost_price > 0)
          <div class="small text-success">{{ number_format(($product->unit_price - $product->cost_price) / $product->unit_price * 100, 1) }}% margin</div>
        @endif
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 h-100">
        <div class="text-muted small">Sold this month</div>
        <div class="fs-4 fw-bold">{{ $soldThisMonth }}</div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-2"></i>Stock history</div>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th class="ps-4">When</th><th>Type</th><th>Source / note</th><th>By</th><th class="text-end pe-4">Change</th></tr></thead>
        <tbody>
          @forelse ($movements as $movement)
          <tr>
            <td class="ps-4 text-muted small">{{ $movement->created_at?->format('d M Y H:i') }}</td>
            <td><span class="badge bg-{{ $typeBadge[$movement->type][1] }}">{{ $typeBadge[$movement->type][0] }}</span></td>
            <td>
              @if ($movement->source_url)
                <a href="{{ $movement->source_url }}" class="text-decoration-none">{{ ucfirst($movement->source_type) }} #{{ $movement->source_id }}</a>
              @endif
              @if ($movement->note)<span class="text-muted small">{{ $movement->note }}</span>@endif
            </td>
            <td class="small text-muted">{{ $movement->user?->username ?? '-' }}</td>
            <td class="text-end pe-4 fw-bold {{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center py-4 text-muted">No stock movements yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if ($movements->hasPages())
    <div class="mt-4 d-flex justify-content-center">{{ $movements->links() }}</div>
  @endif
</div>

@if ($product->track_stock)
<div class="modal fade" id="adjustModal" tabindex="-1" aria-labelledby="adjustModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('products.adjust', $product) }}" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title" id="adjustModalLabel">Adjust stock</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted">Currently <strong>{{ $product->stock_qty }}</strong> in stock. Sales and purchases update stock on their own; use this for counts, damage or returns.</p>
        <div class="btn-group w-100 mb-3" role="group">
          <input type="radio" class="btn-check" name="mode" id="modeAdd" value="add" checked>
          <label class="btn btn-outline-success" for="modeAdd">Add</label>
          <input type="radio" class="btn-check" name="mode" id="modeRemove" value="remove">
          <label class="btn btn-outline-danger" for="modeRemove">Remove</label>
          <input type="radio" class="btn-check" name="mode" id="modeSet" value="set">
          <label class="btn btn-outline-primary" for="modeSet">Set count</label>
        </div>
        <label for="adjustQty" class="form-label fw-semibold small">Quantity</label>
        <input type="number" id="adjustQty" name="quantity" class="form-control mb-3" min="0" required>
        <label for="adjustNote" class="form-label fw-semibold small">Reason</label>
        <input type="text" id="adjustNote" name="note" class="form-control" maxlength="255" placeholder="e.g. Monthly count, damaged unit, customer return">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>
@endif
@endsection
