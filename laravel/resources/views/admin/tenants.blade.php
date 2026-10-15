@extends('layouts.platform')

@section('title', 'Businesses')
@section('brand_url', route('admin.tenants.index'))

@section('nav')
    @include('admin._nav')
@endsection

@section('content')
@php
    $badges = ['trial' => 'info', 'active' => 'success', 'past_due' => 'warning', 'suspended' => 'secondary', 'cancelled' => 'dark', 'provisioning' => 'light', 'failed' => 'danger'];
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <h1 class="h3 fw-bold mb-0">Businesses <span class="text-body-secondary fs-5">{{ $tenants->total() }}</span></h1>
    <form method="GET" class="d-flex gap-2">
        <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Name, subdomain or owner email">
        <button type="submit" class="btn btn-outline-primary">Search</button>
    </form>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>Business</th><th>Status</th><th>Plan</th><th>Signed up</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($tenants as $tenant)
                    <tr>
                        <td>
                            <a href="{{ route('admin.tenants.show', $tenant) }}" class="fw-semibold text-decoration-none">{{ $tenant->name }}</a>
                            <div class="small text-body-secondary">
                                {{ $tenant->slug }}.{{ config('tenancy.base_domain') }}
                                @foreach ($tenant->domains as $domain) · {{ $domain->domain }} @endforeach
                                @if ($tenant->owner_email) · {{ $tenant->owner_email }} @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $badges[$tenant->status] ?? 'secondary' }}">{{ str_replace('_', ' ', $tenant->status) }}</span>
                            @if ($tenant->billingDueAt())
                                <div class="small text-body-secondary">{{ $tenant->paid_until ? 'paid until' : 'trial ends' }} {{ $tenant->billingDueAt()->format('j M Y') }}</div>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.tenants.plan', $tenant) }}" class="d-flex gap-1">
                                @csrf
                                @method('PUT')
                                <select name="plan_id" class="form-select form-select-sm" aria-label="Plan for {{ $tenant->name }}" onchange="this.form.submit()">
                                    <option value="">No plan</option>
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}" @selected($tenant->plan_id === $plan->id)>{{ $plan->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="small text-nowrap">{{ $tenant->created_at?->format('j M Y') }}</td>
                        <td class="text-end text-nowrap">
                            @if ($tenant->isUsable())
                                <form method="POST" action="{{ route('admin.tenants.impersonate', $tenant) }}" class="d-inline" onsubmit="return confirm('Sign in to {{ $tenant->name }} as its owner? This is recorded in its activity log.')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Sign in as owner</button>
                                </form>
                                <form method="POST" action="{{ route('admin.tenants.suspend', $tenant) }}" class="d-inline" onsubmit="return confirm('Pause {{ $tenant->name }}? Its users won\'t be able to sign in.')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning">Pause</button>
                                </form>
                            @elseif ($tenant->status === 'suspended')
                                <form method="POST" action="{{ route('admin.tenants.resume', $tenant) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">Resume</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-body-secondary py-4">No businesses found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $tenants->links() }}

<h2 class="h5 fw-bold mt-5 mb-3">Recent activity</h2>
<ul class="list-group shadow-sm">
    @forelse ($activity as $entry)
        <li class="list-group-item small d-flex justify-content-between gap-3">
            <span>{{ $entry->description }}@if ($entry->admin) <span class="text-body-secondary">· {{ $entry->admin->name }}</span>@endif</span>
            <span class="text-body-secondary text-nowrap">{{ $entry->created_at?->diffForHumans() }}</span>
        </li>
    @empty
        <li class="list-group-item small text-body-secondary">Nothing yet.</li>
    @endforelse
</ul>
@endsection
