@extends('layouts.platform')

@section('title', $plan->exists ? 'Edit plan' : 'Add plan')
@section('brand_url', route('admin.tenants.index'))
@section('nav')
    @include('admin._nav')
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <h1 class="h3 fw-bold mb-4">{{ $plan->exists ? "Edit {$plan->name}" : 'Add plan' }}</h1>

        <form method="POST" action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" class="card border-0 shadow-sm">
            @csrf
            @if ($plan->exists) @method('PUT') @endif
            <div class="card-body row g-3 p-4">
                <div class="col-md-8">
                    <label for="name" class="form-label small fw-semibold">Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $plan->name) }}" class="form-control" maxlength="100" required>
                </div>
                <div class="col-md-4">
                    <label for="code" class="form-label small fw-semibold">Code</label>
                    <input type="text" id="code" name="code" value="{{ old('code', $plan->code) }}" class="form-control" maxlength="30" placeholder="starter" required>
                    <div class="form-text">Used in TENANCY_SIGNUP_PLAN.</div>
                </div>
                <div class="col-md-4">
                    <label for="currency" class="form-label small fw-semibold">Currency</label>
                    <input type="text" id="currency" name="currency" value="{{ old('currency', $plan->currency) }}" class="form-control text-uppercase" maxlength="3" required>
                </div>
                <div class="col-md-4">
                    <label for="price_monthly" class="form-label small fw-semibold">Price a month</label>
                    <input type="number" id="price_monthly" name="price_monthly" value="{{ old('price_monthly', $plan->price_monthly ?? 0) }}" class="form-control" min="0" step="0.01" required>
                </div>
                <div class="col-md-4">
                    <label for="price_yearly" class="form-label small fw-semibold">Price a year</label>
                    <input type="number" id="price_yearly" name="price_yearly" value="{{ old('price_yearly', $plan->price_yearly ?? 0) }}" class="form-control" min="0" step="0.01" required>
                </div>
                @foreach (\App\Models\Landlord\Plan::LIMITS as $key => $label)
                    <div class="col-md-6">
                        <label for="limit_{{ $key }}" class="form-label small fw-semibold">Most {{ $label }}</label>
                        <input type="number" id="limit_{{ $key }}" name="limits[{{ $key }}]" value="{{ old("limits.{$key}", $plan->limit($key)) }}" class="form-control" min="1" placeholder="Unlimited">
                    </div>
                @endforeach
                <div class="col-12 d-flex gap-4">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $plan->is_active))>
                        <label class="form-check-label" for="is_active">Can be chosen</label>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_public" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_public" name="is_public" value="1" @checked(old('is_public', $plan->is_public))>
                        <label class="form-check-label" for="is_public">Shown publicly</label>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex justify-content-end gap-2 p-3">
                <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save plan</button>
            </div>
        </form>
    </div>
</div>
@endsection
