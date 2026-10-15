@extends('layouts.app')

@section('title', 'Plan & billing')

@section('content')
@php
    $due = $tenant->billingDueAt();
    $statusText = match ($tenant->status) {
        'trial' => 'Free trial',
        'past_due' => 'Payment overdue',
        default => 'Active',
    };
@endphp
<div class="animate-fade">
  <div class="page-header">
    <div>
      <h1 class="h3 mb-1"><i class="bi bi-credit-card me-2 text-primary"></i>Plan &amp; billing</h1>
      <p class="text-body-secondary small mb-0">Your plan, what it allows and what you have paid.</p>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <div class="small text-body-secondary">Plan</div>
              <div class="h4 fw-bold mb-0">{{ $tenant->plan?->name ?? 'No plan' }}</div>
            </div>
            <span class="badge {{ $tenant->status === 'past_due' ? 'text-bg-warning' : ($tenant->status === 'trial' ? 'text-bg-info' : 'text-bg-success') }}">{{ $statusText }}</span>
          </div>
          @if ($tenant->plan)
            <p class="small mb-3">{{ $tenant->plan->currency }} {{ number_format($tenant->plan->price_monthly, 2) }} a month
              @if ($tenant->plan->price_yearly > 0) · {{ $tenant->plan->currency }} {{ number_format($tenant->plan->price_yearly, 2) }} a year @endif</p>
          @endif
          <dl class="row small mb-0">
            @if ($tenant->status === 'trial')
              <dt class="col-6">Trial ends</dt><dd class="col-6">{{ $tenant->trial_ends_at?->format('j M Y') ?? '–' }}</dd>
            @endif
            <dt class="col-6">Paid until</dt><dd class="col-6">{{ $tenant->paid_until?->format('j M Y') ?? '–' }}</dd>
            @if ($tenant->status === 'past_due' && $due)
              <dt class="col-6 text-danger">Paused after</dt><dd class="col-6 text-danger">{{ $due->copy()->addDays(config('tenancy.billing.grace_days'))->format('j M Y') }}</dd>
            @endif
          </dl>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-body">
          <h2 class="h6 fw-bold mb-3">What your plan allows</h2>
          @foreach ($usage as $item)
            @php $percent = $item['limit'] ? min(100, (int) round($item['used'] / $item['limit'] * 100)) : 0; @endphp
            <div class="mb-3">
              <div class="d-flex justify-content-between small">
                <span class="text-capitalize">{{ $item['label'] }}</span>
                <span>{{ $item['used'] }} / {{ $item['limit'] ?? 'unlimited' }}</span>
              </div>
              @if ($item['limit'])
                <div class="progress mt-1" role="progressbar" aria-label="{{ $item['label'] }}" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" style="height: 6px;">
                  <div class="progress-bar {{ $percent >= 100 ? 'bg-danger' : ($percent >= 80 ? 'bg-warning' : '') }}" style="width: {{ $percent }}%"></div>
                </div>
              @endif
            </div>
          @endforeach
          <p class="small text-body-secondary mb-0">Reaching a limit stops adding more; everything you already have keeps working. Contact us to change your plan.</p>
        </div>
      </div>
    </div>

    @if ($instructions !== '')
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <h2 class="h6 fw-bold">How to pay</h2>
            <div class="small mb-0">{!! nl2br(e($instructions)) !!}</div>
          </div>
        </div>
      </div>
    @endif

    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <h2 class="h6 fw-bold">Payments</h2>
          <div class="table-responsive">
            <table class="table table-sm small align-middle mb-0">
              <thead><tr><th>Paid on</th><th>Amount</th><th>Paid by</th><th>Months</th><th>Covers until</th></tr></thead>
              <tbody>
                @forelse ($tenant->payments as $payment)
                  <tr>
                    <td>{{ $payment->paid_on->format('j M Y') }}</td>
                    <td>{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                    <td>{{ $methods[$payment->method] ?? $payment->method }}@if ($payment->reference) · {{ $payment->reference }}@endif</td>
                    <td>{{ $payment->months }}</td>
                    <td>{{ $payment->covers_until->format('j M Y') }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-body-secondary">No payments yet.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
