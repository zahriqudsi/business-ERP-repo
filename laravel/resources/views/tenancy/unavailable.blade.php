@extends('layouts.platform')

@section('title', $tenant->name)

@section('content')
@php
    [$icon, $heading, $text] = match ($tenant->status) {
        'provisioning' => ['hourglass-split', 'Almost ready', 'This business is still being set up. Please try again in a minute.'],
        'suspended' => ['pause-circle', 'This account is paused', 'Signing in to this business is switched off for now. The owner can contact support to switch it back on. No data has been deleted.'],
        'cancelled' => ['x-circle', 'This account is closed', 'This business has closed its account.'.($tenant->data_purge_after ? ' Its data is kept until '.$tenant->data_purge_after->format('j F Y').'; the owner can contact support before then.' : '')],
        default => ['exclamation-circle', 'Not available', 'This business can\'t be opened right now. Please contact support.'],
    };
@endphp
<div class="row justify-content-center text-center">
    <div class="col-md-7 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-5">
                <i class="bi bi-{{ $icon }} display-5 text-secondary"></i>
                <h1 class="h4 fw-bold mt-3">{{ $heading }}</h1>
                <p class="text-body-secondary mb-0"><strong>{{ $tenant->name }}</strong>: {{ $text }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
