@extends('layouts.marketing')

@section('title', 'Privacy Policy')

@section('no_cta', true)

@section('content')
<div class="bl-hero py-5">
<div class="container py-lg-4">
@php
    $operator = config('tenancy.operator');
    $product = config('sjpos.product_name');
@endphp
<article class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h2 fw-bold mb-1">Privacy Policy</h1>
        <p class="text-body-secondary small mb-4">Last updated {{ \Illuminate\Support\Carbon::parse('2026-10-15')->format('j F Y') }}</p>

        <p>This policy explains what personal information {{ $operator['name'] }} handles when businesses use {{ $product }}, and why.</p>

        <h2 class="h5 fw-bold mt-4">What we collect</h2>
        <ul>
            <li><strong>Account details:</strong> the business name, the owner's and users' names, usernames, email addresses and (encrypted) passwords.</li>
            <li><strong>Business records:</strong> what a business enters, such as its customers' names and contact details, invoices, quotations, job notes and payments. The business decides what it enters; we handle it on the business's behalf.</li>
            <li><strong>Technical records:</strong> sign-in times, an activity log of changes, and IP addresses, to keep accounts secure.</li>
        </ul>

        <h2 class="h5 fw-bold mt-4">How we use it</h2>
        <p>Only to provide the service: to sign users in, keep each business's records, send the emails a business asks us to send (such as invoices and sign-in codes), bill for plans and give support. We don't sell personal information or use business records for advertising.</p>

        <h2 class="h5 fw-bold mt-4">How it is kept</h2>
        <p>Each business's records are stored in its own database, apart from other businesses, and backed up regularly. Platform staff can open a business's account only to give support, and every such sign-in is recorded in that business's activity log.</p>

        <h2 class="h5 fw-bold mt-4">Who else handles it</h2>
        <p>We use service providers to run the platform, such as our hosting provider and our email delivery provider. They handle data only to provide their service to us.</p>

        <h2 class="h5 fw-bold mt-4">How long we keep it</h2>
        <p>For as long as the business's account is open. After an account is closed, its data is kept for {{ config('tenancy.billing.retention_days') }} days and may then be deleted.</p>

        <h2 class="h5 fw-bold mt-4">Your choices</h2>
        <p>Users can ask their business's owner to correct or remove their details. Owners can ask us for a copy of their business's data, or to close the account.@if ($operator['email']) Contact us at <a href="mailto:{{ $operator['email'] }}">{{ $operator['email'] }}</a>.@endif</p>
    </div>
</article>
</div>
</div>
@endsection
