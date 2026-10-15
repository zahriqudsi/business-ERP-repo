@extends('layouts.platform')

@section('title', 'Terms of Service')
@section('brand_url', route('central.home'))

@section('content')
@php
    $operator = config('tenancy.operator');
    $product = config('sjpos.product_name');
@endphp
<article class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h2 fw-bold mb-1">Terms of Service</h1>
        <p class="text-body-secondary small mb-4">Last updated {{ \Illuminate\Support\Carbon::parse('2026-10-15')->format('j F Y') }}</p>

        <p>These terms apply when a business uses {{ $product }}, provided by {{ $operator['name'] }} ("we"). By signing up, you accept them on behalf of your business.</p>

        <h2 class="h5 fw-bold mt-4">1. Your account</h2>
        <p>You give correct details when signing up and keep your login details secret. You are responsible for what is done with your business's logins, including those of users you add.</p>

        <h2 class="h5 fw-bold mt-4">2. Your data</h2>
        <p>The invoices, customers, payments and other records you enter belong to your business. You give us the right to store and process them only to provide the service. Each business's data is kept in its own database, apart from other businesses. See our <a href="{{ route('central.privacy') }}">Privacy Policy</a>.</p>

        <h2 class="h5 fw-bold mt-4">3. Trial, plans and payment</h2>
        <p>A new business starts with a free trial. To keep using the service afterwards, you pay for a plan as shown on your Plan &amp; billing page. If payment is overdue, the account is paused after a grace period of {{ config('tenancy.billing.grace_days') }} days; nothing is deleted while it is paused. Plan limits (such as the number of users) apply as described for your plan.</p>

        <h2 class="h5 fw-bold mt-4">4. Acceptable use</h2>
        <p>Use the service lawfully and only for your business. Don't try to reach other businesses' data, disrupt the service, or send unlawful or unsolicited messages through it.</p>

        <h2 class="h5 fw-bold mt-4">5. Closing your account</h2>
        <p>You can ask us to close your account at any time. After closing, your data is kept for {{ config('tenancy.billing.retention_days') }} days so you can ask for a copy, and may then be deleted. We may close or pause accounts that break these terms.</p>

        <h2 class="h5 fw-bold mt-4">6. Availability and liability</h2>
        <p>We work to keep the service available and your data backed up, but we can't promise it will never be interrupted. To the extent the law allows, our liability is limited to the amount your business paid us in the three months before the claim.</p>

        <h2 class="h5 fw-bold mt-4">7. Changes</h2>
        <p>We may update these terms; we will tell account owners about important changes before they apply.</p>

        <h2 class="h5 fw-bold mt-4">8. Law and contact</h2>
        <p>These terms are governed by the laws of {{ $operator['country'] }}.@if ($operator['email']) Questions: <a href="mailto:{{ $operator['email'] }}">{{ $operator['email'] }}</a>.@endif</p>
    </div>
</article>
@endsection
