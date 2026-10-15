@extends('layouts.platform')

@section('title', $tenant->name)
@section('brand_url', route('admin.tenants.index'))
@section('nav')
    @include('admin._nav')
@endsection

@section('content')
@php
    $badges = ['trial' => 'info', 'active' => 'success', 'past_due' => 'warning', 'suspended' => 'secondary', 'cancelled' => 'dark', 'provisioning' => 'light', 'failed' => 'danger'];
    $due = $tenant->billingDueAt();
@endphp
<a href="{{ route('admin.tenants.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>All businesses</a>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mt-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">{{ $tenant->name }}
            <span class="badge text-bg-{{ $badges[$tenant->status] ?? 'secondary' }} align-middle fs-6">{{ str_replace('_', ' ', $tenant->status) }}</span>
        </h1>
        <div class="text-body-secondary small">
            <a href="{{ $tenant->url() }}" target="_blank" rel="noopener">{{ $tenant->slug }}.{{ config('tenancy.base_domain') }}</a>
            @foreach ($tenant->domains as $domain) · {{ $domain->domain }} @endforeach
            · {{ $tenant->owner_email ?? 'no owner email' }} · signed up {{ $tenant->created_at?->format('j M Y') }}
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if ($tenant->isUsable())
            <form method="POST" action="{{ route('admin.tenants.impersonate', $tenant) }}" onsubmit="return confirm('Sign in to {{ $tenant->name }} as its owner? This is recorded in its activity log.')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary">Sign in as owner</button>
            </form>
            <form method="POST" action="{{ route('admin.tenants.suspend', $tenant) }}" onsubmit="return confirm('Pause {{ $tenant->name }}? Its users won\'t be able to sign in.')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-warning">Pause</button>
            </form>
        @endif
        @if (in_array($tenant->status, ['suspended', 'cancelled'], true))
            <form method="POST" action="{{ route('admin.tenants.resume', $tenant) }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-success">{{ $tenant->status === 'cancelled' ? 'Re-open' : 'Resume' }}</button>
            </form>
        @endif
        <form method="POST" action="{{ route('admin.tenants.backup', $tenant) }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i>Download backup</button>
        </form>
        @if ($tenant->status !== 'cancelled')
            <form method="POST" action="{{ route('admin.tenants.close', $tenant) }}" onsubmit="return confirm('Close {{ $tenant->name }}? Its users can no longer sign in. Its data is kept for {{ config('tenancy.billing.retention_days') }} days.')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger">Close account</button>
            </form>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 fw-bold">Plan &amp; paid time</h2>
                <dl class="row small mb-3">
                    <dt class="col-5">Plan</dt><dd class="col-7">{{ $tenant->plan?->name ?? 'No plan (no limits)' }}</dd>
                    <dt class="col-5">Trial ends</dt><dd class="col-7">{{ $tenant->trial_ends_at?->format('j M Y') ?? '–' }}</dd>
                    <dt class="col-5">Paid until</dt><dd class="col-7">{{ $tenant->paid_until?->format('j M Y') ?? '–' }}</dd>
                    <dt class="col-5">Must pay by</dt><dd class="col-7">{{ $due?->format('j M Y') ?? 'Never billed' }}</dd>
                    @if ($tenant->status === 'suspended')
                        <dt class="col-5">Paused</dt><dd class="col-7">{{ $tenant->suspended_at?->format('j M Y') }} ({{ $tenant->suspended_reason === 'billing' ? 'unpaid' : 'by staff' }})</dd>
                    @endif
                    @if ($tenant->data_purge_after)
                        <dt class="col-5">Data kept until</dt><dd class="col-7">{{ $tenant->data_purge_after->format('j M Y') }}</dd>
                    @endif
                </dl>
                <form method="POST" action="{{ route('admin.tenants.plan', $tenant) }}" class="d-flex gap-2">
                    @csrf
                    @method('PUT')
                    <select name="plan_id" class="form-select form-select-sm" aria-label="Change plan">
                        <option value="">No plan</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected($tenant->plan_id === $plan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">Change plan</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6 fw-bold">Record a payment</h2>
                <p class="small text-body-secondary">Extends the paid time from {{ $tenant->paid_until?->isFuture() ? $tenant->paid_until->format('j M Y') : 'today' }}, and resumes a business paused for not paying.</p>
                <form method="POST" action="{{ route('admin.tenants.payments.store', $tenant) }}" class="row g-2">
                    @csrf
                    <div class="col-6">
                        <label for="amount" class="form-label small fw-semibold">Amount</label>
                        <input type="number" id="amount" name="amount" value="{{ old('amount', $tenant->plan?->price_monthly) }}" class="form-control form-control-sm" min="0" step="0.01" required>
                    </div>
                    <div class="col-6">
                        <label for="months" class="form-label small fw-semibold">Months</label>
                        <input type="number" id="months" name="months" value="{{ old('months', 1) }}" class="form-control form-control-sm" min="1" max="36" required>
                    </div>
                    <div class="col-6">
                        <label for="method" class="form-label small fw-semibold">Paid by</label>
                        <select id="method" name="method" class="form-select form-select-sm">
                            @foreach ($methods as $value => $label)
                                <option value="{{ $value }}" @selected(old('method') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label for="paid_on" class="form-label small fw-semibold">Paid on</label>
                        <input type="date" id="paid_on" name="paid_on" value="{{ old('paid_on', today()->toDateString()) }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-6">
                        <label for="reference" class="form-label small fw-semibold">Reference</label>
                        <input type="text" id="reference" name="reference" value="{{ old('reference') }}" class="form-control form-control-sm" maxlength="100">
                    </div>
                    <div class="col-6">
                        <label for="pay_plan_id" class="form-label small fw-semibold">For plan</label>
                        <select id="pay_plan_id" name="plan_id" class="form-select form-select-sm">
                            <option value="">Current ({{ $tenant->plan?->name ?? 'none' }})</option>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <input type="text" name="note" value="{{ old('note') }}" class="form-control form-control-sm" maxlength="255" placeholder="Note (optional)" aria-label="Note">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Record payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 fw-bold">Payments</h2>
                <div class="table-responsive">
                    <table class="table table-sm small align-middle mb-0">
                        <thead><tr><th>Paid on</th><th>Amount</th><th>Months</th><th>Covers until</th><th>By</th></tr></thead>
                        <tbody>
                            @forelse ($tenant->payments as $payment)
                                <tr>
                                    <td>{{ $payment->paid_on->format('j M Y') }}</td>
                                    <td class="text-nowrap">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}<div class="text-body-secondary">{{ $methods[$payment->method] ?? $payment->method }}@if ($payment->reference) · {{ $payment->reference }}@endif</div></td>
                                    <td>{{ $payment->months }}@if ($payment->plan) <div class="text-body-secondary">{{ $payment->plan->name }}</div>@endif</td>
                                    <td>{{ $payment->covers_until->format('j M Y') }}</td>
                                    <td>{{ $payment->admin?->name ?? '–' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-body-secondary">No payments yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <h2 class="h6 fw-bold">Activity</h2>
        <ul class="list-group shadow-sm">
            @forelse ($activity as $entry)
                <li class="list-group-item small d-flex justify-content-between gap-3">
                    <span>{{ $entry->description }}@if ($entry->admin) <span class="text-body-secondary">· {{ $entry->admin->name }}</span>@endif</span>
                    <span class="text-body-secondary text-nowrap">{{ $entry->created_at?->format('j M Y H:i') }}</span>
                </li>
            @empty
                <li class="list-group-item small text-body-secondary">Nothing yet.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
