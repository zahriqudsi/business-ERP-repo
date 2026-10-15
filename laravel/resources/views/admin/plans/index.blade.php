@extends('layouts.platform')

@section('title', 'Plans')
@section('brand_url', route('admin.tenants.index'))
@section('nav')
    @include('admin._nav')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center gap-3 mb-4">
    <h1 class="h3 fw-bold mb-0">Plans</h1>
    <a href="{{ route('admin.plans.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add plan</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>Plan</th><th>Monthly</th><th>Yearly</th><th>Limits</th><th>Businesses</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr class="{{ $plan->is_active ? '' : 'text-body-secondary' }}">
                        <td>
                            <div class="fw-semibold">{{ $plan->name }}</div>
                            <div class="small text-body-secondary">{{ $plan->code }}@unless ($plan->is_active) · switched off @endunless @unless ($plan->is_public) · hidden @endunless</div>
                        </td>
                        <td class="text-nowrap">{{ $plan->currency }} {{ number_format($plan->price_monthly, 2) }}</td>
                        <td class="text-nowrap">{{ $plan->currency }} {{ number_format($plan->price_yearly, 2) }}</td>
                        <td class="small">
                            @foreach (\App\Models\Landlord\Plan::LIMITS as $key => $label)
                                <div>{{ $plan->limit($key) ?? 'Unlimited' }} {{ $label }}</div>
                            @endforeach
                        </td>
                        <td>{{ $plan->tenants_count }}</td>
                        <td class="text-end"><a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-body-secondary py-4">No plans yet. Businesses without a plan have no limits.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
