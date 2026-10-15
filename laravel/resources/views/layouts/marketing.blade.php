<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('description', config('sjpos.product_name').': quotations, invoices, payments, repair job notes, stock and reports for small businesses, in one simple online system.')">
    <title>@hasSection('title')@yield('title') | @endif{{ config('sjpos.product_name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('assets/css/marketing.css') }}?v={{ filemtime(public_path('assets/css/marketing.css')) }}">
    @stack('styles')
</head>

<body class="marketing">
@php
    $product = config('sjpos.product_name');
    $solutions = [
        'repair-shops' => ['Repair shops', 'Job notes with signatures, quotes and invoices', 'tools'],
        'retail-and-trading' => ['Shops & traders', 'Stock, purchases, invoices and what\'s owed', 'shop'],
        'service-businesses' => ['Service businesses', 'Quotations, invoices and getting paid', 'briefcase'],
    ];
@endphp

<header class="bl-nav">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="bl-brand" href="{{ route('central.home') }}"><span class="bl-brand-mark"><i class="bi bi-receipt"></i></span>{{ $product }}</a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-controls="siteNav" aria-expanded="false" aria-label="Open menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="siteNav">
                <ul class="navbar-nav mx-auto gap-lg-1 mt-3 mt-lg-0">
                    <li class="nav-item"><a class="nav-link @if (request()->routeIs('central.features')) active @endif" href="{{ route('central.features') }}">Features</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle @if (request()->routeIs('central.solutions')) active @endif" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Solutions</a>
                        <ul class="dropdown-menu">
                            @foreach ($solutions as $slug => [$label, $hint, $icon])
                                <li><a class="dropdown-item d-flex gap-3" href="{{ route('central.solutions', $slug) }}"><i class="bi bi-{{ $icon }} fs-5 text-primary"></i><span><strong>{{ $label }}</strong><small>{{ $hint }}</small></span></a></li>
                            @endforeach
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link @if (request()->routeIs('central.pricing')) active @endif" href="{{ route('central.pricing') }}">Pricing</a></li>
                    <li class="nav-item"><a class="nav-link @if (request()->routeIs('central.contact')) active @endif" href="{{ route('central.contact') }}">Contact</a></li>
                </ul>
                <div class="d-flex flex-column flex-lg-row gap-2 mt-3 mt-lg-0">
                    <a href="{{ route('central.find') }}" class="btn btn-ghost">Sign in</a>
                    <a href="{{ route('central.signup') }}" class="btn btn-brand">Start free trial</a>
                </div>
            </div>
        </div>
    </nav>
</header>

<main>
    @isset($errors)
        @if (session('success') || $errors->any())
            <div class="container pt-4">@include('partials.flash')</div>
        @endif
    @endisset
    @yield('content')
</main>

@unless (View::hasSection('no_cta'))
    <section class="bl-section-sm">
        <div class="container">
            <div class="bl-cta text-center">
                <h2 class="bl-title mb-3">Run your business from one place</h2>
                <p class="bl-lead mb-4 mx-auto" style="max-width: 640px; color: rgba(255,255,255,.85)">Quotes, invoices, payments, repair jobs and stock. Set up in minutes, free for 14 days.</p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="{{ route('central.signup') }}" class="btn btn-light-brand btn-lg">Start your free trial</a>
                    <a href="{{ route('central.contact') }}" class="btn btn-lg text-white border border-white border-opacity-50">Talk to us</a>
                </div>
            </div>
        </div>
    </section>
@endunless

<footer class="bl-footer">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4">
                <a class="bl-brand mb-3" href="{{ route('central.home') }}"><span class="bl-brand-mark"><i class="bi bi-receipt"></i></span>{{ $product }}</a>
                <p class="mt-3 mb-0" style="max-width: 320px">Simple online software for quotations, invoices, payments, repair jobs and stock, made for small businesses in Sri Lanka.</p>
            </div>
            <div class="col-6 col-lg-2">
                <h4>Product</h4>
                <ul>
                    <li><a href="{{ route('central.features') }}">Features</a></li>
                    <li><a href="{{ route('central.pricing') }}">Pricing</a></li>
                    <li><a href="{{ route('central.signup') }}">Free trial</a></li>
                    <li><a href="{{ route('central.find') }}">Sign in</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h4>Solutions</h4>
                <ul>
                    @foreach ($solutions as $slug => [$label])
                        <li><a href="{{ route('central.solutions', $slug) }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h4>Company</h4>
                <ul>
                    <li><a href="{{ route('central.contact') }}">Contact</a></li>
                    <li><a href="{{ route('central.terms') }}">Terms of Service</a></li>
                    <li><a href="{{ route('central.privacy') }}">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="bl-footer-bottom d-flex flex-wrap justify-content-between gap-2">
            <span>&copy; {{ now()->year }} {{ config('tenancy.operator.name') }}. All rights reserved.</span>
            <span>{{ $product }} is made in Sri Lanka.</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>

</html>
