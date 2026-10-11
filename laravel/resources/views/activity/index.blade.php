@extends('layouts.app')

@section('title', 'Activity Log')

@use('App\Models\ActivityLog')

@section('content')
<div class="animate-fade">
  <div class="page-header">
    <div>
      <h1 class="h3 mb-1"><i class="bi bi-clock-history me-2 text-primary"></i>Activity log</h1>
      <p class="text-body-secondary small mb-0">Who created, changed or deleted what, and when.</p>
    </div>
  </div>

  <form method="GET" class="card p-3 mb-4">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label for="search" class="form-label small fw-semibold mb-1">Search</label>
        <input type="search" id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="e.g. 1417 or Acme">
      </div>
      <div class="col-6 col-md-2">
        <label for="user_id" class="form-label small fw-semibold mb-1">Person</label>
        <select id="user_id" name="user_id" class="form-select">
          <option value="">Everyone</option>
          @foreach ($users as $user)
            <option value="{{ $user->id }}" @selected((int) ($filters['user_id'] ?? 0) === $user->id)>{{ $user->display_name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label for="action" class="form-label small fw-semibold mb-1">Action</label>
        <select id="action" name="action" class="form-select">
          <option value="">Any</option>
          @foreach (ActivityLog::ACTIONS as $value => [$label])
            <option value="{{ $value }}" @selected(($filters['action'] ?? '') === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label for="type" class="form-label small fw-semibold mb-1">Record</label>
        <select id="type" name="type" class="form-select">
          <option value="">Any</option>
          @foreach (ActivityLog::SUBJECTS as $value => [$label])
            <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-6 col-md-1">
        <label for="from" class="form-label small fw-semibold mb-1">From</label>
        <input type="date" id="from" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
      </div>
      <div class="col-6 col-md-1">
        <label for="to" class="form-label small fw-semibold mb-1">To</label>
        <input type="date" id="to" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
      </div>
      <div class="col-6 col-md-1 d-grid">
        <button class="btn btn-primary">Filter</button>
      </div>
    </div>
  </form>

  <div class="card">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th class="ps-4">When</th><th>Who</th><th>Action</th><th>What</th><th class="pe-4">Details</th></tr></thead>
        <tbody>
          @forelse ($entries as $entry)
          @php([$actionLabel, $tone] = ActivityLog::ACTIONS[$entry->action] ?? [ucfirst($entry->action), 'secondary'])
          <tr>
            <td class="ps-4 small text-body-secondary text-nowrap" title="{{ $entry->created_at->format('d M Y H:i:s') }}">
              {{ $entry->created_at->format('d M Y') }}<br>{{ $entry->created_at->format('H:i') }}
            </td>
            <td class="small">{{ $entry->user?->display_name ?? 'System' }}</td>
            <td><span class="badge text-bg-{{ $tone }}">{{ $actionLabel }}</span></td>
            <td>
              <div class="small text-body-secondary">{{ ActivityLog::SUBJECTS[$entry->subject_type][0] ?? '' }}</div>
              @if ($entry->subject_url)
                <a href="{{ $entry->subject_url }}" class="text-decoration-none">{{ $entry->description }}</a>
              @else
                {{ $entry->description }}
              @endif
            </td>
            <td class="pe-4 small">
              @if ($entry->changes)
                <ul class="list-unstyled mb-0">
                  @foreach (array_slice($entry->changes, 0, 6, true) as $field => [$old, $new])
                    <li><span class="text-body-secondary">{{ str_replace(['_', '.'], ' ', $field) }}:</span>
                      <span class="text-decoration-line-through text-body-secondary">{{ is_bool($old) ? ($old ? 'yes' : 'no') : (filled($old) ? $old : '—') }}</span>
                      → <strong>{{ is_bool($new) ? ($new ? 'yes' : 'no') : (filled($new) ? $new : '—') }}</strong></li>
                  @endforeach
                  @if (count($entry->changes) > 6)<li class="text-body-secondary">and {{ count($entry->changes) - 6 }} more</li>@endif
                </ul>
              @elseif ($entry->ip_address)
                <span class="text-body-secondary">{{ $entry->ip_address }}</span>
              @endif
            </td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center text-body-secondary py-5">No activity matches.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if ($entries->hasPages())
    <div class="mt-4 d-flex justify-content-center">{{ $entries->links() }}</div>
  @endif
</div>
@endsection
