@extends('layouts.platform')

@section('brand_url', route('central.home'))

@section('nav')
    <a href="{{ route('central.find') }}" class="btn btn-outline-secondary btn-sm">Sign in</a>
    <a href="{{ route('central.signup') }}" class="btn btn-primary btn-sm">Start free</a>
@endsection

@section('content')
<div class="row justify-content-center text-center py-5">
    <div class="col-lg-8">
        <h1 class="display-5 fw-bold mb-3">Invoices, quotations and job notes for your business</h1>
        <p class="lead text-body-secondary mb-4">
            Send invoices and quotations, track payments and what customers owe, record repair jobs with
            customer signatures, keep stock and see your reports. Your business gets its own private space.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="{{ route('central.signup') }}" class="btn btn-primary btn-lg px-4">Start your free trial</a>
            <a href="{{ route('central.find') }}" class="btn btn-outline-secondary btn-lg px-4">Find my business</a>
        </div>
        <p class="small text-body-secondary mt-3 mb-0">14 days free. No card needed.</p>
    </div>
</div>

<div class="row g-4 mt-2">
    @foreach ([
        ['receipt', 'Invoices & quotations', 'PDFs with your logo, emailed straight to customers. Turn a quotation into an invoice in one click.'],
        ['cash-coin', 'Payments & what\'s owed', 'Record payments in and out, and see every customer\'s balance and how long it has been outstanding.'],
        ['tools', 'Repair job notes', 'Take in devices with the customer\'s on-screen signature, and print or email the job note.'],
    ] as [$icon, $title, $text])
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <i class="bi bi-{{ $icon }} fs-2 text-primary"></i>
                    <h2 class="h5 fw-bold mt-3">{{ $title }}</h2>
                    <p class="text-body-secondary mb-0">{{ $text }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
