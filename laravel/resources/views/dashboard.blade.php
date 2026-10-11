@extends('layouts.app')

@section('title', 'Dashboard')

@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $trendMax = max(1, collect($trend)->max(fn ($m) => max($m['sales'], $m['collected'])));
    $canSeeReports = auth()->user()->can('view-reports');
@endphp

@push('styles')
<style>
.period-pills { background: var(--bs-secondary-bg); border-radius: 12px; padding: 4px; display: inline-flex; flex-wrap: wrap; gap: 2px; }
.period-pills a { border-radius: 9px; padding: .35rem .85rem; font-size: .82rem; font-weight: 600; color: var(--bs-secondary-color); text-decoration: none; }
.period-pills a:hover { color: var(--bs-body-color); }
.period-pills a.active { background: var(--sj-surface); color: var(--bs-body-color); box-shadow: var(--sj-shadow); }

.trend-chart { display: flex; align-items: flex-end; gap: 4%; height: 190px; padding: 0 .25rem; }
.trend-month { flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%; }
.trend-bars { flex: 1; width: 100%; display: flex; align-items: flex-end; justify-content: center; gap: 5px; }
.trend-bar { width: 38%; max-width: 26px; border-radius: 6px 6px 2px 2px; min-height: 3px; transition: opacity .15s; }
.trend-bar.sales { background: linear-gradient(180deg, #818cf8, #6366f1); }
.trend-bar.collected { background: linear-gradient(180deg, #6ee7b7, #10b981); }
.trend-month:hover .trend-bar { opacity: .8; }
.trend-label { font-size: .75rem; color: var(--bs-secondary-color); margin-top: .4rem; }
.legend-dot { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 5px; vertical-align: -1px; }

.quick-link { display: flex; align-items: center; gap: .75rem; padding: .6rem .7rem; border-radius: 10px; text-decoration: none; color: var(--bs-body-color); font-weight: 500; }
.quick-link:hover { background: var(--bs-secondary-bg); color: var(--bs-body-color); }
.quick-link .kpi-icon { width: 34px; height: 34px; font-size: 1rem; border-radius: 9px; }

.attention-item { display: flex; align-items: center; gap: .75rem; padding: .7rem 1.15rem; border-bottom: 1px solid var(--sj-border); text-decoration: none; color: var(--bs-body-color); }
.attention-item:last-child { border-bottom: 0; }
.attention-item:hover { background: var(--bs-tertiary-bg); color: var(--bs-body-color); }
</style>
@endpush

@section('content')
<div class="animate-fade">

  <div class="page-header">
    <div>
      <h1 class="h3 mb-1">{{ $greeting }}, {{ auth()->user()->username }}</h1>
      <p class="text-body-secondary small mb-0">{{ $label }}</p>
    </div>
    <nav class="period-pills" aria-label="Period">
      @foreach (\App\Http\Controllers\DashboardController::PERIODS as $key => $name)
        <a href="{{ route('dashboard', ['period' => $key]) }}" class="{{ $period === $key ? 'active' : '' }}" @if ($period === $key) aria-current="page" @endif>{{ $name }}</a>
      @endforeach
    </nav>
  </div>

  {{-- Money in / out for the period --}}
  <div class="row g-3 mb-3">
    @foreach ([
        ['Sales', $sales->total, $sales->cnt.' invoices', 'graph-up-arrow', 'indigo', $canSeeReports ? route('reports.show', 'sales') : route('invoices.index')],
        ['Payments Received', $received->total, $received->cnt.' payments', 'arrow-down-circle', 'green', route('payments.index')],
        ['Purchases', $purchases->total, $purchases->cnt.' orders', 'cart', 'sky', route('purchases.index')],
        ['Payments Made', $made->total, $made->cnt.' payments', 'arrow-up-circle', 'violet', route('payments.index', ['type' => 'made'])],
    ] as [$kpiLabel, $value, $sub, $icon, $tone, $link])
    <div class="col-6 col-xl-3">
      <a href="{{ $link }}" class="card kpi-card text-decoration-none">
        <div class="d-flex justify-content-between align-items-start">
          <div class="kpi-label">{{ $kpiLabel }}</div>
          <span class="kpi-icon tone-{{ $tone }}"><i class="bi bi-{{ $icon }}"></i></span>
        </div>
        <div class="kpi-value">{{ rs_short($value) }}</div>
        <div class="kpi-sub">{{ $sub }}</div>
      </a>
    </div>
    @endforeach
  </div>

  {{-- Balances right now --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
      <a href="{{ route('outstanding-ar') }}" class="card kpi-card text-decoration-none">
        <div class="d-flex justify-content-between align-items-start">
          <div class="kpi-label">Customers owe you</div>
          <span class="kpi-icon tone-amber"><i class="bi bi-hourglass-split"></i></span>
        </div>
        <div class="kpi-value">{{ rs_short($ar->total) }}</div>
        <div class="kpi-sub {{ $ar->overdue > 0 ? 'text-danger fw-semibold' : '' }}">{{ rs_short($ar->overdue) }} over {{ config('sjpos.credit_days') }} days</div>
      </a>
    </div>
    <div class="col-6 col-xl-3">
      <a href="{{ $canSeeReports ? route('reports.show', 'payables') : route('bills.index') }}" class="card kpi-card text-decoration-none">
        <div class="d-flex justify-content-between align-items-start">
          <div class="kpi-label">You owe suppliers</div>
          <span class="kpi-icon tone-red"><i class="bi bi-exclamation-triangle"></i></span>
        </div>
        <div class="kpi-value">{{ rs_short($ap->total) }}</div>
        <div class="kpi-sub">{{ rs_short($ap->overdue) }} over {{ config('sjpos.credit_days') }} days / past due</div>
      </a>
    </div>
    <div class="col-6 col-xl-3">
      <a href="{{ route('jobs.index') }}" class="card kpi-card text-decoration-none">
        <div class="d-flex justify-content-between align-items-start">
          <div class="kpi-label">Jobs awaiting signature</div>
          <span class="kpi-icon tone-sky"><i class="bi bi-pen"></i></span>
        </div>
        <div class="kpi-value">{{ $jobsPendingSignature }}</div>
        <div class="kpi-sub">job notes not yet signed</div>
      </a>
    </div>
    <div class="col-6 col-xl-3">
      <a href="{{ route('products.index', ['filter' => 'low']) }}" class="card kpi-card text-decoration-none">
        <div class="d-flex justify-content-between align-items-start">
          <div class="kpi-label">Low stock</div>
          <span class="kpi-icon tone-violet"><i class="bi bi-box-seam"></i></span>
        </div>
        <div class="kpi-value">{{ $lowStock->count() }}{{ $lowStock->count() === 5 ? '+' : '' }}</div>
        <div class="kpi-sub">products to reorder</div>
      </a>
    </div>
  </div>

  <div class="row g-4 mb-4">
    {{-- Six-month trend --}}
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
          <span>Sales vs collected · last 6 months</span>
          <span class="small fw-normal text-body-secondary">
            <span class="legend-dot" style="background:#6366f1"></span>Sales
            <span class="legend-dot ms-3" style="background:#10b981"></span>Collected
          </span>
        </div>
        <div class="card-body">
          <div class="trend-chart" role="img" aria-label="Bar chart of monthly sales and collections">
            @foreach ($trend as $month)
              <div class="trend-month" title="{{ $month['label'] }}: sales {{ rs($month['sales']) }}, collected {{ rs($month['collected']) }}">
                <div class="trend-bars">
                  <div class="trend-bar sales" style="height: {{ round($month['sales'] / $trendMax * 100, 1) }}%"></div>
                  <div class="trend-bar collected" style="height: {{ round($month['collected'] / $trendMax * 100, 1) }}%"></div>
                </div>
                <div class="trend-label">{{ $month['label'] }}</div>
              </div>
            @endforeach
          </div>
          <div class="d-flex justify-content-between small text-body-secondary mt-3 border-top pt-2">
            <span>6-month sales <strong class="text-body">{{ rs_short(collect($trend)->sum('sales')) }}</strong></span>
            <span>collected <strong class="text-body">{{ rs_short(collect($trend)->sum('collected')) }}</strong></span>
          </div>
        </div>
      </div>
    </div>

    {{-- Quick actions --}}
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">Quick actions</div>
        <div class="card-body p-2">
          @foreach ([
              ['New invoice', 'invoices.create', 'receipt', 'indigo'],
              ['New quotation', 'quotations.create', 'file-earmark-text', 'green'],
              ['New job note', 'jobs.create', 'tools', 'sky'],
              ['Receive a payment', 'payments.receive', 'cash-coin', 'amber'],
              ['Record a purchase', 'purchases.create', 'cart-plus', 'violet'],
              $canSeeReports ? ['View reports', 'reports.index', 'bar-chart-line', 'red'] : ['Add a customer', 'customers.create', 'person-plus', 'red'],
          ] as [$text, $route, $icon, $tone])
            <a href="{{ route($route) }}" class="quick-link">
              <span class="kpi-icon tone-{{ $tone }}"><i class="bi bi-{{ $icon }}"></i></span>{{ $text }}
              <i class="bi bi-chevron-right ms-auto text-body-tertiary small"></i>
            </a>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    {{-- Recent invoices --}}
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Recent invoices</span>
          <a href="{{ route('invoices.index') }}" class="small fw-semibold text-decoration-none">View all</a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="ps-3">Invoice</th><th>Customer</th><th>Date</th><th class="text-end">Amount</th><th class="text-end pe-3">Status</th></tr></thead>
            <tbody>
              @forelse ($recentInvoices as $inv)
              <tr>
                <td class="ps-3 fw-semibold"><a href="{{ route('invoices.show', $inv) }}" class="text-decoration-none">#{{ $inv->invoice_no }}</a></td>
                <td>
                  @if ($inv->customer_id)
                    <a href="{{ route('customers.show', $inv->customer_id) }}" class="text-body text-decoration-none">{{ $inv->customer_name }}</a>
                  @else
                    {{ $inv->customer_name }}
                  @endif
                </td>
                <td class="text-body-secondary">{{ $inv->date?->format('d M Y') }}</td>
                <td class="text-end fw-semibold">{{ rs_short($inv->subtotal) }}</td>
                <td class="text-end pe-3"><span class="status-badge status-{{ $inv->status }}">{{ ucfirst($inv->status) }}</span></td>
              </tr>
              @empty
              <tr><td colspan="5" class="text-center text-body-secondary py-4">No invoices yet</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Needs attention --}}
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">Needs your attention</div>
        @php($nothingToDo = $pendingBills->isEmpty() && $lowStock->isEmpty() && $jobsPendingSignature === 0)
        @if ($nothingToDo)
          <div class="card-body text-center text-body-secondary py-5">
            <i class="bi bi-check2-circle fs-1 text-success"></i>
            <p class="mt-2 mb-0">All caught up!</p>
          </div>
        @else
          <div>
            @foreach ($pendingBills as $bill)
              <a href="{{ route('bills.edit', $bill) }}" class="attention-item">
                <span class="kpi-icon tone-{{ $bill->is_overdue ? 'red' : 'amber' }}" style="width:32px;height:32px;font-size:.9rem"><i class="bi bi-file-earmark-ruled"></i></span>
                <span class="flex-grow-1 min-w-0">
                  <span class="d-block text-truncate fw-semibold small">{{ $bill->supplier_name }} · {{ $bill->display_no }}</span>
                  <span class="d-block small {{ $bill->is_overdue ? 'text-danger' : 'text-body-secondary' }}">{{ $bill->due_date ? 'Due '.$bill->due_date->format('d M') : 'No due date' }}</span>
                </span>
                <span class="small fw-semibold">{{ rs_short($bill->balance) }}</span>
              </a>
            @endforeach
            @foreach ($lowStock as $product)
              <a href="{{ route('products.show', $product) }}" class="attention-item">
                <span class="kpi-icon tone-violet" style="width:32px;height:32px;font-size:.9rem"><i class="bi bi-box-seam"></i></span>
                <span class="flex-grow-1 min-w-0">
                  <span class="d-block text-truncate fw-semibold small">{{ $product->name }}</span>
                  <span class="d-block small text-body-secondary">{{ $product->brand ?: 'Low stock' }}</span>
                </span>
                <span class="small fw-bold {{ $product->stock_qty <= 0 ? 'text-danger' : 'text-warning' }}">{{ $product->stock_qty }} left</span>
              </a>
            @endforeach
            @if ($jobsPendingSignature > 0)
              <a href="{{ route('jobs.index') }}" class="attention-item">
                <span class="kpi-icon tone-sky" style="width:32px;height:32px;font-size:.9rem"><i class="bi bi-pen"></i></span>
                <span class="flex-grow-1 small fw-semibold">{{ $jobsPendingSignature }} job {{ \Illuminate\Support\Str::plural('note', $jobsPendingSignature) }} awaiting signature</span>
                <i class="bi bi-chevron-right small text-body-tertiary"></i>
              </a>
            @endif
          </div>
        @endif
      </div>
    </div>
  </div>

  <div class="row g-4">
    {{-- Recent job notes --}}
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Recent job notes</span>
          <a href="{{ route('jobs.index') }}" class="small fw-semibold text-decoration-none">View all</a>
        </div>
        @if ($recentJobs->isNotEmpty())
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="ps-3">Job</th><th>Customer</th><th>Item</th><th>Received</th><th class="text-end pe-3">Signature</th></tr></thead>
            <tbody>
              @foreach ($recentJobs as $job)
              <tr class="cursor-pointer" onclick="location.href='{{ route('jobs.show', $job) }}'">
                <td class="ps-3 fw-semibold text-primary">{{ $job->formatted_no }}</td>
                <td>{{ $job->customer_name }}</td>
                <td class="text-body-secondary">{{ $job->item }}</td>
                <td class="text-body-secondary">{{ $job->collected_date->format('d M Y') }}</td>
                <td class="text-end pe-3">
                  @if ($job->signed)
                    <span class="status-badge status-paid">Signed</span>
                  @else
                    <a href="{{ route('jobs.show', $job) }}#signNow" class="status-badge status-overdue text-decoration-none" onclick="event.stopPropagation()">Sign now</a>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @else
          <div class="card-body text-center text-body-secondary py-5">
            <i class="bi bi-tools fs-1 text-primary"></i>
            <p class="mt-2 mb-0">No job notes yet. <a href="{{ route('jobs.create') }}">Create the first one</a>.</p>
          </div>
        @endif
      </div>
    </div>

    {{-- Top customers --}}
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">Top customers</div>
        <div class="card-body p-2">
          @php($topRevenue = max(1, (float) ($topCustomers->first()->revenue ?? 1)))
          @forelse ($topCustomers as $customer)
            <a href="{{ $customer->customer_id ? route('customers.show', $customer->customer_id) : '#' }}" class="quick-link flex-column align-items-stretch gap-1">
              <span class="d-flex justify-content-between small">
                <span class="fw-semibold text-truncate me-2">{{ $customer->customer_name }}</span>
                <span class="fw-semibold">{{ rs_short($customer->revenue) }}</span>
              </span>
              <span class="progress" style="height:5px" role="presentation">
                <span class="progress-bar" style="width: {{ round($customer->revenue / $topRevenue * 100) }}%; background: var(--sj-primary)"></span>
              </span>
            </a>
          @empty
            <p class="text-center text-body-secondary py-4 mb-0">No sales in this period</p>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
