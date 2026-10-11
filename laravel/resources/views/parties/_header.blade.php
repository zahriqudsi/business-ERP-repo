{{-- Name, contact details, money summary and edit/merge/delete actions for a customer or supplier page. --}}
@php($plural = $kind.'s')
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
  <div>
    <a href="{{ route($plural.'.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>All {{ $plural }}</a>
    <h2 class="h3 mt-1 mb-2">{{ $party->name }}</h2>
    <div class="d-flex flex-wrap gap-3 small text-muted">
      @if ($party->contact_person)<span><i class="bi bi-person me-1"></i>{{ $party->contact_person }}</span>@endif
      @if ($party->phone)<a href="tel:{{ $party->phone }}" class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $party->phone }}</a>@endif
      @if ($party->email)<a href="mailto:{{ $party->email }}" class="text-muted"><i class="bi bi-envelope me-1"></i>{{ $party->email }}</a>@endif
      @if ($party->address)<span><i class="bi bi-geo-alt me-1"></i>{{ $party->address }}</span>@endif
      @if (! $party->contact_person && ! $party->phone && ! $party->email && ! $party->address)
        <a href="{{ route($plural.'.edit', $party) }}" class="text-decoration-none"><i class="bi bi-plus-circle me-1"></i>Add contact details</a>
      @endif
    </div>
    @if ($party->notes)<p class="small mt-2 mb-0 text-body-secondary"><i class="bi bi-sticky me-1"></i>{{ $party->notes }}</p>@endif
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="{{ route($plural.'.edit', $party) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
    @can('delete-records')
    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#mergeModal"><i class="bi bi-intersect me-1"></i>Merge</button>
    <form method="POST" action="{{ route($plural.'.destroy', $party) }}" onsubmit="return confirm('Delete {{ addslashes($party->name) }}?')">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Delete</button>
    </form>
    @endcan
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm p-3 h-100">
      <div class="text-muted small">{{ $kind === 'customer' ? 'Total invoiced' : 'Total billed' }}</div>
      <div class="fs-4 fw-bold">{{ rs($billed) }}</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm p-3 h-100">
      <div class="text-muted small">Paid</div>
      <div class="fs-4 fw-bold text-success">{{ rs($paid) }}</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm p-3 h-100">
      <div class="text-muted small">{{ $kind === 'customer' ? 'Balance owed to you' : 'Balance you owe' }}</div>
      <div class="fs-4 fw-bold {{ $balance > 0 ? 'text-danger' : 'text-muted' }}">{{ rs($balance) }}</div>
    </div>
  </div>
</div>

<div class="modal fade" id="mergeModal" tabindex="-1" aria-labelledby="mergeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route($plural.'.merge', $party) }}" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title" id="mergeModalLabel">Merge “{{ $party->name }}”</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted">Use this when the same {{ $kind }} was typed two different ways. All of this {{ $kind }}'s documents and payments move to the one you choose, and “{{ $party->name }}” is removed.</p>
        <label for="target_id" class="form-label fw-semibold small">Merge into</label>
        <select id="target_id" name="target_id" class="form-select" required>
          <option value="">Choose a {{ $kind }}…</option>
          @foreach ($mergeTargets as $target)
            <option value="{{ $target->id }}">{{ $target->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Merge</button>
      </div>
    </form>
  </div>
</div>
