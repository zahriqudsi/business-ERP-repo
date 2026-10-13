@extends('layouts.app')

@section('title', 'Outstanding AR')

@section('content')
<div class="animate-fade">

  <!-- Page Header -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
      <h2 class="h3 mb-1"><i class="bi bi-hourglass-split me-2 text-warning"></i>Outstanding AR</h2>
      <p class="text-muted small">Customers with unpaid or partially paid invoice balances.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Invoice
      </a>
      <a href="{{ route('payments.receive') }}" class="btn btn-success btn-sm">
        <i class="bi bi-cash-coin me-1"></i>Receive Payment
      </a>
    </div>
  </div>

  <!-- KPI Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 text-center">
        <div class="fs-4 fw-bold text-danger">{{ rs($totalOutstanding, 0) }}</div>
        <div class="text-muted small">Total Outstanding</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 text-center">
        <div class="fs-4 fw-bold text-primary">{{ $customers->count() }}</div>
        <div class="text-muted small">Customers Owing</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 text-center">
        <div class="fs-4 fw-bold text-secondary">{{ $invoiceCount }}</div>
        <div class="text-muted small">Unpaid Invoices</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 text-center">
        <div class="fs-4 fw-bold text-success">{{ rs($totalPaid, 0) }}</div>
        <div class="text-muted small">Partially Collected</div>
      </div>
    </div>
  </div>

  <!-- Aging Summary -->
  @php($buckets = [
      'current' => ['Current', 'bg-success', 'text-success'],
      '1_30' => ['1–30 days', 'bg-primary', 'text-primary'],
      '31_60' => ['31–60 days', 'bg-warning', 'text-warning'],
      '61_90' => ['61–90 days', 'bg-orange', 'text-orange'],
      'over_90' => ['Over 90 days', 'bg-danger', 'text-danger'],
  ])
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header fw-semibold"><i class="bi bi-bar-chart me-2"></i>Aging Summary</div>
    <div class="card-body">
      <div class="row g-3 text-center">
        @foreach ($buckets as $key => [$label, $bg, $text])
        <div class="col {{ $loop->first ? '' : 'border-start' }}">
          <div class="fw-bold {{ $text }} fs-5">{{ rs($aging[$key], 0) }}</div>
          <div class="text-muted small">{!! $loop->first ? 'Current<br><span class="text-success">0 days</span>' : e($label) !!}</div>
        </div>
        @endforeach
      </div>

      @if ($totalOutstanding > 0)
      <div class="progress mt-3" style="height:10px; border-radius:10px;">
        @foreach ($buckets as $key => [$label, $bg])
        <div class="progress-bar {{ $bg }}" style="width:{{ $aging[$key] / $totalOutstanding * 100 }}%" title="{{ $label }}"></div>
        @endforeach
      </div>
      <div class="d-flex gap-3 mt-2 flex-wrap" style="font-size:.75rem">
        @foreach ($buckets as [$label, $bg])
        <span><span class="badge {{ $bg }}">&nbsp;</span> {{ $label }}</span>
        @endforeach
      </div>
      @endif
    </div>
  </div>

  <!-- Filter -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
      <form method="GET" action="{{ route('outstanding-ar') }}" class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="form-label small fw-semibold mb-1">Search Customer</label>
          <input type="text" name="search" class="form-control form-control-sm"
            placeholder="Type customer name..." value="{{ $search }}">
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
          <a href="{{ route('outstanding-ar') }}" class="btn btn-outline-secondary btn-sm ms-1">Clear</a>
        </div>
        <div class="col-auto ms-auto text-muted small pt-2">
          {{ $customers->count() }} {{ Str::plural('customer', $customers->count()) }} owing
        </div>
      </form>
    </div>
  </div>

  <!-- Customer List Table -->
  <div class="card border-0 shadow-sm overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-4">Customer</th>
            <th class="text-center">Invoices</th>
            <th>Oldest Outstanding</th>
            <th class="text-end">Invoiced</th>
            <th class="text-end">Paid</th>
            <th class="text-end">Total Amount Due</th>
            <th class="text-center pe-3">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($customers as $customer)
          <tr>
            <td class="ps-4 fw-semibold">{{ $customer->name }}</td>
            <td class="text-center">
              <span class="badge bg-light text-dark border">{{ $customer->invoice_count }}</span>
            </td>
            <td>
              <span class="badge {{ days_badge($customer->max_days) }}">
                {{ $customer->max_days <= 0 ? 'Today' : $customer->max_days.' days' }}
              </span>
            </td>
            <td class="text-end">{{ rs($customer->total_invoiced) }}</td>
            <td class="text-end text-success">
              @if ($customer->total_paid > 0)
                {{ rs($customer->total_paid) }}
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
            <td class="text-end fw-bold text-danger">{{ rs($customer->total_balance) }}</td>
            <td class="text-center pe-3">
              <a href="{{ route('outstanding-ar', ['customer' => $customer->name]) }}"
                 class="btn btn-sm btn-outline-primary" title="View Invoices">
                <i class="bi bi-eye me-1"></i>View
              </a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="text-center py-5">
              <i class="bi bi-check-circle text-success fs-1 d-block mb-2"></i>
              <span class="text-muted">No outstanding balances{{ $search !== '' ? ' for this search' : '' }}. All payments are up to date!</span>
            </td>
          </tr>
          @endforelse
        </tbody>
        @if ($customers->isNotEmpty())
        <tfoot class="table-light fw-bold">
          <tr>
            <td colspan="3" class="ps-4 py-3">Total ({{ $customers->count() }} customers)</td>
            <td class="text-end">{{ rs($totalInvoiced) }}</td>
            <td class="text-end text-success">{{ rs($totalPaid) }}</td>
            <td class="text-end text-danger fs-6">{{ rs($totalOutstanding) }}</td>
            <td></td>
          </tr>
        </tfoot>
        @endif
      </table>
    </div>
  </div>
</div>
@endsection
