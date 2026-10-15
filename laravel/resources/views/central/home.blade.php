@extends('layouts.marketing')

@section('description', config('sjpos.product_name').' is simple online software for quotations, invoices, payments, repair job notes, stock and reports. Free for 14 days.')

@section('content')
@php $product = config('sjpos.product_name'); @endphp

{{-- Hero --}}
<section class="bl-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="bl-eyebrow mb-3"><i class="bi bi-stars"></i> Made for small businesses in Sri Lanka</span>
                <h1 class="mt-3 mb-4">Quotes, invoices and repair jobs, <span class="bl-highlight">all in one place</span></h1>
                <p class="bl-lead mb-4">{{ $product }} replaces the receipt book and the Excel sheet. Send professional quotations and invoices, record payments, take in repairs with the customer's signature, and always know who owes you.</p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="{{ route('central.signup') }}" class="btn btn-brand btn-lg">Start your free trial <i class="bi bi-arrow-right ms-1"></i></a>
                    <a href="{{ route('central.features') }}" class="btn btn-ghost btn-lg">See all features</a>
                </div>
                <ul class="bl-checks">
                    <li>14 days free</li>
                    <li>No card needed</li>
                    <li>Ready in minutes</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="position-relative">
                    <div class="bl-window">
                        <div class="bl-window-bar"><i></i><i></i><i></i><span>acme.{{ config('tenancy.base_domain') }} · Dashboard</span></div>
                        <div class="bl-window-body">
                            <div class="row g-2 mb-3">
                                <div class="col-4"><div class="bl-stat"><div class="label">Sales this month</div><div class="value">Rs. 1.84M</div></div></div>
                                <div class="col-4"><div class="bl-stat"><div class="label">Customers owe</div><div class="value">Rs. 312K</div></div></div>
                                <div class="col-4"><div class="bl-stat"><div class="label">Open jobs</div><div class="value">14</div></div></div>
                            </div>
                            <div class="bl-stat mb-3">
                                <div class="d-flex justify-content-between mb-2"><span class="label">Sales vs collected · last 6 months</span><span class="bl-pill bl-pill-green">+18%</span></div>
                                <div class="bl-bars">
                                    <div style="height:45%"></div><div class="alt" style="height:38%"></div>
                                    <div style="height:62%"></div><div class="alt" style="height:51%"></div>
                                    <div style="height:58%"></div><div class="alt" style="height:55%"></div>
                                    <div style="height:74%"></div><div class="alt" style="height:66%"></div>
                                    <div style="height:69%"></div><div class="alt" style="height:64%"></div>
                                    <div style="height:92%"></div><div class="alt" style="height:80%"></div>
                                </div>
                            </div>
                            <table class="bl-mini-table">
                                <thead><tr><th>Invoice</th><th>Customer</th><th class="text-end">Amount</th><th class="text-end">Status</th></tr></thead>
                                <tbody>
                                    <tr><td>#1042</td><td>Lanka Office Supplies</td><td class="text-end">Rs. 45,000</td><td class="text-end"><span class="bl-pill bl-pill-green">Paid</span></td></tr>
                                    <tr><td>#1041</td><td>Green Leaf Café</td><td class="text-end">Rs. 18,500</td><td class="text-end"><span class="bl-pill bl-pill-amber">Due</span></td></tr>
                                    <tr><td>#1040</td><td>Ruwan Perera</td><td class="text-end">Rs. 7,250</td><td class="text-end"><span class="bl-pill bl-pill-green">Paid</span></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="bl-float" style="left:24px; bottom:-58px"><i class="bi bi-check-circle-fill text-success"></i><span><strong>Payment received</strong><br><span class="text-muted-2">Invoice #1041 · Rs. 18,500</span></span></div>
                    <div class="bl-float" style="right:-20px; top:-22px"><i class="bi bi-pen-fill text-primary"></i><span><strong>Job note signed</strong><br><span class="text-muted-2">MacBook Pro · JN-0214</span></span></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- The flow --}}
<section class="bl-section-sm bl-band">
    <div class="container text-center">
        <p class="text-muted-2 fw-semibold mb-3">Everything connects, so you never type the same thing twice</p>
        <div class="bl-flow">
            <span class="bl-flow-step"><i class="bi bi-file-earmark-text text-primary me-1"></i> Quotation</span><i class="bi bi-arrow-right bl-flow-arrow"></i>
            <span class="bl-flow-step"><i class="bi bi-receipt text-primary me-1"></i> Invoice</span><i class="bi bi-arrow-right bl-flow-arrow"></i>
            <span class="bl-flow-step"><i class="bi bi-cash-coin text-success me-1"></i> Payment</span><i class="bi bi-arrow-right bl-flow-arrow"></i>
            <span class="bl-flow-step"><i class="bi bi-box-seam text-warning me-1"></i> Stock</span><i class="bi bi-arrow-right bl-flow-arrow"></i>
            <span class="bl-flow-step"><i class="bi bi-bar-chart-line text-primary me-1"></i> Reports</span>
        </div>
    </div>
</section>

{{-- Everything you need --}}
<section class="bl-section">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width: 720px">
            <span class="bl-eyebrow">Features</span>
            <h2 class="bl-title mt-3">Everything a small business needs to stay on top of its money</h2>
            <p class="bl-lead mt-3">One simple system instead of five tools, built around how shops and service businesses actually work.</p>
        </div>
        <div class="row g-4">
            @foreach ([
                ['receipt', '', 'Invoices & quotations', 'Professional PDFs with your logo, tax and bank details. Email them in one click, and turn a quote into an invoice.'],
                ['tools', 'amber', 'Repair job notes', 'Take in devices with the fault, accessories and condition, and the customer\'s signature on screen.'],
                ['cash-coin', 'green', 'Payments & what\'s owed', 'Record cash, bank and cheque payments. Balances update by themselves, and you see who owes what, and for how long.'],
                ['box-seam', 'sky', 'Stock that keeps itself', 'Invoices and purchases update stock automatically, with low-stock warnings and a full history for every product.'],
                ['bar-chart-line', 'violet', 'Reports that make sense', 'Sales, profit, payments, stock value and customer statements, ready to print or download.'],
                ['people', 'pink', 'Your whole team', 'A login for everyone, with owner, manager and staff roles, and a log of who did what.'],
            ] as [$icon, $tone, $title, $text])
                <div class="col-md-6 col-lg-4">
                    <div class="bl-card">
                        <div class="bl-icon {{ $tone }}"><i class="bi bi-{{ $icon }}"></i></div>
                        <h3>{{ $title }}</h3>
                        <p>{{ $text }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Feature rows --}}
<section class="bl-section bl-band">
    <div class="container">

        <div class="row align-items-center g-5 bl-feature-row">
            <div class="col-lg-6">
                <span class="bl-eyebrow">Quotations → invoices</span>
                <h2 class="bl-title mt-3">From quote to paid invoice, without retyping a thing</h2>
                <p class="bl-lead mt-3">Send a price, win the job, and turn the quotation into an invoice in one click. Customer, items, prices and tax all carry over.</p>
                <ul class="bl-ticks">
                    <li>Your logo, address and bank details on every PDF</li>
                    <li>Optional tax (such as VAT) on each document</li>
                    <li>Email to the customer straight from {{ $product }}</li>
                    <li>Numbering that never repeats</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="bl-window">
                    <div class="bl-window-bar"><i></i><i></i><i></i><span>Quotation #1180</span></div>
                    <div class="bl-window-body">
                        <div class="d-flex justify-content-between mb-3"><div><div class="label text-muted-2 small">Prepared for</div><strong>Lanka Office Supplies</strong></div><div class="text-end"><div class="text-muted-2 small">Valid until</div><strong>22 Oct 2026</strong></div></div>
                        <table class="bl-mini-table mb-3">
                            <thead><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th></tr></thead>
                            <tbody>
                                <tr><td>Laser printer</td><td class="text-end">2</td><td class="text-end">45,000</td><td class="text-end">90,000</td></tr>
                                <tr><td>Toner cartridge</td><td class="text-end">5</td><td class="text-end">3,500</td><td class="text-end">17,500</td></tr>
                                <tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bold">Rs. 107,500</td></tr>
                            </tbody>
                        </table>
                        <div class="d-flex gap-2 justify-content-end"><span class="btn btn-sm btn-ghost py-1">Email</span><span class="btn btn-sm btn-brand py-1"><i class="bi bi-arrow-repeat me-1"></i>Convert to invoice</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row align-items-center g-5 bl-feature-row flex-lg-row-reverse">
            <div class="col-lg-6">
                <span class="bl-eyebrow">Repair job notes</span>
                <h2 class="bl-title mt-3">Take in repairs the professional way</h2>
                <p class="bl-lead mt-3">Record the device, the fault, the accessories and its condition, and let the customer sign on the screen. Print or email the job note as their receipt.</p>
                <ul class="bl-ticks">
                    <li>On-screen customer signature, on a tablet or phone</li>
                    <li>Automatic job numbers</li>
                    <li>Your terms and conditions on every job note</li>
                    <li>See every job still waiting for a signature</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="bl-window">
                    <div class="bl-window-bar"><i></i><i></i><i></i><span>Job note JN-0214</span></div>
                    <div class="bl-window-body">
                        <div class="row g-2 mb-3 small">
                            <div class="col-6"><span class="text-muted-2">Customer</span><br><strong>Ruwan Perera</strong></div>
                            <div class="col-6"><span class="text-muted-2">Item</span><br><strong>MacBook Pro 16"</strong></div>
                            <div class="col-6"><span class="text-muted-2">Fault</span><br>Not charging, screen flickers</div>
                            <div class="col-6"><span class="text-muted-2">Accessories</span><br>Charger, sleeve</div>
                        </div>
                        <div class="text-muted-2 small mb-1">Customer signature</div>
                        <div class="bl-signature">
                            <svg width="180" height="44" viewBox="0 0 180 44" aria-hidden="true"><path d="M6 32 C 22 6, 34 40, 48 20 S 70 8, 78 28 S 104 36, 116 14 S 142 30, 150 22 S 168 18, 174 26" fill="none" stroke="#3730a3" stroke-width="2.5" stroke-linecap="round"/></svg>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3"><span class="bl-pill bl-pill-green"><i class="bi bi-check2"></i> Signed</span><span class="small text-muted-2">Collected 15 Oct 2026</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row align-items-center g-5 bl-feature-row">
            <div class="col-lg-6">
                <span class="bl-eyebrow">Getting paid</span>
                <h2 class="bl-title mt-3">Always know who owes you, and for how long</h2>
                <p class="bl-lead mt-3">Every payment updates the invoice by itself. See your outstanding balances grouped by age, and send a customer their statement in a click.</p>
                <ul class="bl-ticks">
                    <li>Cash, bank transfer, cheque and online payments</li>
                    <li>Part payments, with the balance always right</li>
                    <li>Aging from current to 90+ days</li>
                    <li>Customer statements by email</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="bl-window">
                    <div class="bl-window-bar"><i></i><i></i><i></i><span>Outstanding AR</span></div>
                    <div class="bl-window-body">
                        @foreach ([['Current', 52, 'bg-success', 'Rs. 162,400'], ['1–30 days', 28, 'bg-primary', 'Rs. 87,250'], ['31–60 days', 14, 'bg-warning', 'Rs. 43,800'], ['60+ days', 6, 'bg-danger', 'Rs. 18,700']] as [$label, $width, $bar, $amount])
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1"><span>{{ $label }}</span><strong>{{ $amount }}</strong></div>
                                <div class="progress" style="height:8px"><div class="progress-bar {{ $bar }}" style="width: {{ $width }}%"></div></div>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-between align-items-center border-top pt-3"><span class="text-muted-2 small">Total customers owe</span><strong class="fs-5">Rs. 312,150</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row align-items-center g-5 bl-feature-row flex-lg-row-reverse">
            <div class="col-lg-6">
                <span class="bl-eyebrow">Stock</span>
                <h2 class="bl-title mt-3">Stock levels that look after themselves</h2>
                <p class="bl-lead mt-3">Sell it and it leaves stock; buy it and it comes back in. Get warned before you run out, and see the value of what's on your shelves.</p>
                <ul class="bl-ticks">
                    <li>Automatic stock from invoices and purchases</li>
                    <li>Low-stock warnings you set per product</li>
                    <li>Counts and adjustments with a reason</li>
                    <li>Stock value at cost and at selling price</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="bl-window">
                    <div class="bl-window-bar"><i></i><i></i><i></i><span>Products &amp; Stock</span></div>
                    <div class="bl-window-body">
                        <table class="bl-mini-table">
                            <thead><tr><th>Product</th><th class="text-end">Price</th><th class="text-end">In stock</th></tr></thead>
                            <tbody>
                                <tr><td>SSD 512GB <span class="text-muted-2">Samsung</span></td><td class="text-end">Rs. 12,500</td><td class="text-end">24</td></tr>
                                <tr><td>Phone screen guard</td><td class="text-end">Rs. 950</td><td class="text-end">140</td></tr>
                                <tr><td>Laptop charger 65W</td><td class="text-end">Rs. 6,800</td><td class="text-end"><span class="bl-pill bl-pill-amber">3 · low</span></td></tr>
                                <tr><td>Toner cartridge</td><td class="text-end">Rs. 3,500</td><td class="text-end">18</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Who it's for --}}
<section class="bl-section">
    <div class="container">
        <div class="text-center mx-auto mb-5" style="max-width: 680px">
            <span class="bl-eyebrow">Who it's for</span>
            <h2 class="bl-title mt-3">Made for businesses like yours</h2>
        </div>
        <div class="row g-4">
            @foreach ([
                ['repair-shops', 'tools', 'amber', 'Repair shops', 'Phones, computers, electronics and CCTV. Job notes with signatures, then quotes and invoices for the work and the parts.'],
                ['retail-and-trading', 'shop', 'sky', 'Shops & traders', 'Hardware, stationery, IT and spare parts. Stock, purchases from suppliers, invoices and who still owes you.'],
                ['service-businesses', 'briefcase', 'violet', 'Service businesses', 'Installers, agencies and contractors. Quotations, invoices, part payments and clear reports.'],
            ] as [$slug, $icon, $tone, $title, $text])
                <div class="col-md-4">
                    <a href="{{ route('central.solutions', $slug) }}" class="text-decoration-none">
                        <div class="bl-card">
                            <div class="bl-icon {{ $tone }}"><i class="bi bi-{{ $icon }}"></i></div>
                            <h3>{{ $title }}</h3>
                            <p class="mb-3">{{ $text }}</p>
                            <span class="fw-semibold text-primary">Learn more <i class="bi bi-arrow-right"></i></span>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Safe and private --}}
<section class="bl-section bl-band">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <span class="bl-eyebrow"><i class="bi bi-shield-lock"></i> Safe &amp; private</span>
                <h2 class="bl-title mt-3">Your business data stays yours</h2>
                <p class="bl-lead mt-3">Every business on {{ $product }} gets its own private database. Nobody else can see your customers, prices or numbers.</p>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    @foreach ([
                        ['database-lock', 'Your own private database', 'Kept completely apart from every other business.'],
                        ['cloud-arrow-down', 'Nightly backups', 'Your data is backed up every night, and owners can download a copy at any time.'],
                        ['person-badge', 'Roles and an activity log', 'Decide who can delete or see reports, and see who changed what.'],
                        ['phone', 'Works on any device', 'Computer, tablet or phone, in any modern browser. Nothing to install.'],
                    ] as [$icon, $title, $text])
                        <div class="col-sm-6">
                            <div class="bl-card py-4">
                                <div class="d-flex gap-3"><i class="bi bi-{{ $icon }} fs-3 text-primary"></i><div><h3 class="fs-6 mb-1">{{ $title }}</h3><p class="small">{{ $text }}</p></div></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Pricing teaser --}}
<section class="bl-section-sm">
    <div class="container">
        <div class="bl-card d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 p-4 p-md-5">
            <div>
                <h2 class="bl-title mb-2" style="font-size:1.75rem">Simple monthly pricing</h2>
                <p class="text-muted-2 mb-0">
                    @if ($startingPlan)
                        Plans from <strong class="text-dark">{{ $startingPlan->currency === 'LKR' ? 'Rs.' : $startingPlan->currency }} {{ number_format($startingPlan->price_monthly) }}</strong> a month. Start with 14 days free.
                    @else
                        Start with 14 days free, then pick the plan that fits your business.
                    @endif
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('central.pricing') }}" class="btn btn-ghost">See pricing</a>
                <a href="{{ route('central.signup') }}" class="btn btn-brand">Start free trial</a>
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="bl-section-sm">
    <div class="container" style="max-width: 860px">
        <h2 class="bl-title text-center mb-5">Questions people ask</h2>
        @include('central._faq')
    </div>
</section>
@endsection
