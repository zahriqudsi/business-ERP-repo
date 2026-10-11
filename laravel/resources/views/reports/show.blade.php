@extends('layouts.app')

@php([$title, $description, $icon, $usesPeriod] = $meta)

@section('title', $title)

@section('content')
<div class="animate-fade">
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
    <div>
      <a href="{{ route('reports.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>All reports</a>
      <h2 class="h3 mt-1 mb-1"><i class="bi bi-{{ $icon }} me-2 text-primary"></i>{{ $report?->title ?? $title }}</h2>
      <p class="text-muted small mb-0">{{ $report ? $report->periodLabel() : $description }}</p>
    </div>
    @if ($report)
    <div class="d-flex gap-2">
      @if ($key === 'statement')
        <a href="{{ route('documents.email', ['type' => 'statement', 'id' => $customerId, 'period_from' => $from->toDateString(), 'period_to' => $to->toDateString()]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-envelope me-1"></i>Email statement</a>
      @endif
      <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-filetype-csv me-1"></i>Download CSV</a>
      <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-filetype-pdf me-1"></i>Download PDF</a>
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    </div>
    @endif
  </div>

  @if ($usesPeriod || $key === 'statement')
  <form method="GET" class="card border-0 shadow-sm p-3 mb-4 d-print-none" id="reportFilters">
    <div class="row g-2 align-items-end">
      @if ($key === 'statement')
      <div class="col-md-4">
        <label for="customer_id" class="form-label small fw-semibold mb-1">Customer</label>
        <select id="customer_id" name="customer_id" class="form-select" required>
          <option value="">Choose a customer…</option>
          @foreach ($customers as $customer)
            <option value="{{ $customer->id }}" @selected((int) $customerId === $customer->id)>{{ $customer->name }}</option>
          @endforeach
        </select>
      </div>
      @endif
      <div class="col-md-3">
        <label for="period" class="form-label small fw-semibold mb-1">Period</label>
        <select id="period" name="period" class="form-select">
          @foreach (\App\Http\Controllers\ReportController::PERIODS as $value => $label)
            <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-6 col-md-2 custom-dates">
        <label for="from" class="form-label small fw-semibold mb-1">From</label>
        <input type="date" id="from" name="from" class="form-control" value="{{ $from->toDateString() }}">
      </div>
      <div class="col-6 col-md-2 custom-dates">
        <label for="to" class="form-label small fw-semibold mb-1">To</label>
        <input type="date" id="to" name="to" class="form-control" value="{{ $to->toDateString() }}">
      </div>
      <div class="col-md-auto">
        <button class="btn btn-primary w-100"><i class="bi bi-arrow-repeat me-1"></i>Show</button>
      </div>
    </div>
  </form>
  @endif

  @if (! $report)
    <div class="card border-0 shadow-sm p-5 text-center text-muted">
      <i class="bi bi-person-lines-fill fs-1 text-primary"></i>
      <p class="mt-2 mb-0">Choose a customer to see their statement.</p>
    </div>
  @else
    <div class="row g-3 mb-4">
      @foreach ($report->summary as $label => [$value, $type])
      <div class="col-6 col-md">
        <div class="card kpi-card">
          <div class="kpi-label">{{ $label }}</div>
          <div class="kpi-value text-nowrap {{ $type === 'money' && $value < 0 ? 'text-danger' : '' }}" style="font-size:1.2rem">{{ \App\Services\Reports\Report::format($value, $type) }}</div>
        </div>
      </div>
      @endforeach
    </div>

    <div class="report-screen">
      @include('reports._tables', ['tableClass' => 'table table-sm table-hover align-middle mb-0'])
    </div>
  @endif
</div>
@endsection

@push('styles')
<style>
  .report-screen .report-table-block { background: var(--bs-body-bg); border-radius: .75rem; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); margin-bottom: 1.5rem; overflow: hidden; }
  .report-screen .report-table-title { font-size: .9rem; font-weight: 700; margin: 0; padding: .9rem 1rem; border-bottom: 1px solid var(--bs-border-color); }
  .report-screen table th:first-child, .report-screen table td:first-child { padding-left: 1rem; }
  .report-screen table th:last-child, .report-screen table td:last-child { padding-right: 1rem; }
  .report-screen tfoot th { border-top: 2px solid var(--bs-border-color); }
  .report-note { font-size: .8rem; color: var(--bs-secondary-color); }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const period = document.getElementById('period');
  if (!period) return;
  const sync = () => document.querySelectorAll('.custom-dates').forEach(el => el.classList.toggle('d-none', period.value !== 'custom'));
  period.addEventListener('change', () => { sync(); if (period.value !== 'custom') period.form.requestSubmit(); });
  document.getElementById('customer_id')?.addEventListener('change', e => e.target.form.requestSubmit());
  sync();
});
</script>
@endpush
