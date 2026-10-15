<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ config('sjpos.product_name') }}: invoices, quotations, job notes, stock and reports for your business.">
    <meta name="color-scheme" content="light dark">

    <title>@hasSection('title')@yield('title') | @endif {{ config('sjpos.product_name') }}</title>

    <script>
        (function () {
            let saved = 'auto';
            try { saved = localStorage.getItem('sj-theme') || 'auto'; } catch (e) {}
            const dark = saved === 'dark' || (saved === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('assets/css/custom-styles.css') }}?v={{ filemtime(public_path('assets/css/custom-styles.css')) }}">
    @stack('styles')
</head>

<body class="bg-body-tertiary">
    <nav class="navbar border-bottom bg-body">
        <div class="container">
            <a class="navbar-brand fw-bold" href="@yield('brand_url', '#')"><i class="bi bi-receipt-cutoff text-primary me-2"></i>{{ config('sjpos.product_name') }}</a>
            <div class="d-flex gap-2">@yield('nav')</div>
        </div>
    </nav>

    <main class="container py-5">
        {{-- Notice pages are rendered before the session starts, without $errors --}}
        @isset($errors)
            @include('partials.flash')
        @endisset
        @yield('content')
    </main>

    @if (Route::has('central.terms'))
        <footer class="container pb-4 small text-body-secondary d-flex flex-wrap gap-3">
            <span>&copy; {{ now()->year }} {{ config('tenancy.operator.name') }}</span>
            <a href="{{ route('central.terms') }}" class="text-body-secondary">Terms</a>
            <a href="{{ route('central.privacy') }}" class="text-body-secondary">Privacy</a>
        </footer>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
