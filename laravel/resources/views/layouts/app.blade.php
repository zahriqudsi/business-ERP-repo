<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ config('sjpos.product_name') }} for {{ config('sjpos.company.name') }}: invoices, quotations, job notes, stock and reports.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">

    <title>@hasSection('title')@yield('title') | @endif {{ config('sjpos.company.name') }}</title>
    <script>window.SJ_CURRENCY = @json(currency());</script>

    {{-- Apply the saved theme before anything paints, so dark mode doesn't flash white --}}
    <script>
        (function () {
            let saved = 'auto';
            try { saved = localStorage.getItem('sj-theme') || 'auto'; } catch (e) {}
            const dark = saved === 'dark' || (saved === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
            document.documentElement.dataset.themeChoice = saved;
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

@auth
@php
    $nav = [
        'Overview' => [
            ['Dashboard', 'dashboard', 'grid-1x2', 'dashboard'],
        ],
        'Sales' => [
            ['Invoices', 'invoices.index', 'receipt', 'invoices.*'],
            ['Quotations', 'quotations.index', 'file-earmark-text', 'quotations.*'],
            ['Outstanding AR', 'outstanding-ar', 'hourglass-split', 'outstanding-ar'],
            ['Payments', 'payments.index', 'cash-stack', 'payments.*'],
        ],
        'Service' => [
            ['Job Notes', 'jobs.index', 'tools', 'jobs.*'],
        ],
        'Purchasing' => [
            ['Purchases', 'purchases.index', 'cart', 'purchases.*'],
            ['Vendor Bills', 'bills.index', 'file-earmark-ruled', 'bills.*'],
        ],
        'Directory' => [
            ['Customers', 'customers.index', 'people', 'customers.*'],
            ['Suppliers', 'suppliers.index', 'truck', 'suppliers.*'],
            ['Products & Stock', 'products.index', 'box-seam', 'products.*'],
        ],
        'Insights' => [
            ['Reports', 'reports.index', 'bar-chart-line', 'reports.*', 'view-reports'],
        ],
        'Administration' => [
            ['Settings', 'settings.edit', 'gear', 'settings.*', 'manage-business'],
            ['Users', 'users.index', 'person-gear', 'users.*', 'manage-business'],
            ['Backups', 'backups.index', 'database-down', 'backups.*', 'manage-business'],
            ['Activity Log', 'activity.index', 'clock-history', 'activity.*', 'manage-business'],
        ],
    ];
    // Hide links (and empty sections) the signed-in user isn't allowed to open
    $nav = collect($nav)
        ->map(fn ($links) => array_values(array_filter($links, fn ($link) => ! isset($link[4]) || auth()->user()->can($link[4]))))
        ->filter()
        ->all();
    $quickCreate = [
        ['New Invoice', 'invoices.create', 'receipt'],
        ['New Quotation', 'quotations.create', 'file-earmark-text'],
        ['New Job Note', 'jobs.create', 'tools'],
        ['New Purchase', 'purchases.create', 'cart'],
        ['Receive Payment', 'payments.receive', 'arrow-down-circle'],
        ['Make Payment', 'payments.make', 'arrow-up-circle'],
        ['Add Customer', 'customers.create', 'person-plus'],
        ['Add Product', 'products.create', 'box-seam'],
    ];
@endphp
<body class="app-shell">
    <a href="#main" class="visually-hidden-focusable skip-link">Skip to content</a>

    <aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-label="Main navigation">
        <div class="sidebar-brand">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                <span class="brand-mark"><i class="bi bi-receipt-cutoff"></i></span>
                <span class="min-w-0">
                    <span class="brand-name text-truncate">{{ config('sjpos.company.name') }}</span>
                    <span class="brand-sub">{{ config('sjpos.product_name') }}</span>
                </span>
            </a>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close menu"></button>
        </div>

        <nav class="sidebar-nav">
            @foreach ($nav as $section => $links)
                <div class="nav-section">{{ $section }}</div>
                @foreach ($links as [$label, $route, $icon, $pattern])
                    @php($active = request()->routeIs($pattern))
                    <a href="{{ route($route) }}" class="sidebar-link {{ $active ? 'active' : '' }}" @if ($active) aria-current="page" @endif>
                        <i class="bi bi-{{ $icon }}"></i><span>{{ $label }}</span>
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div class="sidebar-footer">
            <div class="d-flex align-items-center gap-2">
                <span class="avatar">{{ strtoupper(mb_substr(auth()->user()->display_name, 0, 1)) }}</span>
                <a href="{{ route('account.edit') }}" class="flex-grow-1 min-w-0 text-decoration-none sidebar-user" title="My account">
                    <span class="d-block text-truncate small">{{ auth()->user()->display_name }}</span>
                    <span class="d-block brand-sub">{{ \App\Models\User::ROLES[auth()->user()->role] }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-sidebar-icon" title="Log out" aria-label="Log out"><i class="bi bi-box-arrow-right"></i></button>
                </form>
            </div>
        </div>
    </aside>

    <div class="app-main">
        <header class="topbar">
            <button class="btn btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div class="topbar-title text-truncate">@yield('title', config('sjpos.company.name'))</div>

            <div class="d-flex align-items-center gap-2 ms-auto">
                <div class="dropdown">
                    <button class="btn btn-primary btn-sm d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-plus-lg"></i><span class="d-none d-sm-inline">New</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        @foreach ($quickCreate as [$label, $route, $icon])
                            <li><a class="dropdown-item" href="{{ route($route) }}"><i class="bi bi-{{ $icon }} me-2 text-primary"></i>{{ $label }}</a></li>
                            @if ($loop->iteration === 4 || $loop->iteration === 6)<li><hr class="dropdown-divider"></li>@endif
                        @endforeach
                    </ul>
                </div>

                <div class="dropdown">
                    <button class="btn btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Change theme" title="Theme">
                        <i class="bi bi-circle-half" data-theme-icon></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><button class="dropdown-item" type="button" data-theme-value="light"><i class="bi bi-sun me-2"></i>Light</button></li>
                        <li><button class="dropdown-item" type="button" data-theme-value="dark"><i class="bi bi-moon-stars me-2"></i>Dark</button></li>
                        <li><button class="dropdown-item" type="button" data-theme-value="auto"><i class="bi bi-circle-half me-2"></i>Match device</button></li>
                    </ul>
                </div>
            </div>
        </header>

        <main id="main" class="app-content">
            @if (! app(\App\Services\Settings::class)->isConfigured() && ! request()->routeIs('settings.*'))
                <div class="alert alert-info d-flex flex-wrap align-items-center gap-2 d-print-none">
                    <i class="bi bi-rocket-takeoff fs-5"></i>
                    <span class="flex-grow-1"><strong>Welcome!</strong> Add your business name, logo and contact details so they appear on your invoices and quotations.</span>
                    @can('manage-business')
                        <a href="{{ route('settings.edit') }}" class="btn btn-primary btn-sm">Set up my business</a>
                    @else
                        <span class="small">Ask the owner to complete the Settings page.</span>
                    @endcan
                </div>
            @endif
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
@else
<body class="guest-shell">
    <div class="theme-guest-toggle">
        <button class="btn btn-icon" type="button" data-theme-cycle aria-label="Change theme" title="Theme"><i class="bi bi-circle-half" data-theme-icon></i></button>
    </div>
    <main id="main" class="guest-content">
        @include('partials.flash')
        @yield('content')
    </main>
@endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/app.js') }}?v={{ filemtime(public_path('assets/js/app.js')) }}"></script>
    @stack('scripts')
</body>

</html>
