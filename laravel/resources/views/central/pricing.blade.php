@extends('layouts.marketing')

@section('title', 'Pricing')
@section('description', 'Simple monthly pricing for '.config('sjpos.product_name').'. Every plan includes every feature. Start with 14 days free.')

@section('content')
@php
    $money = fn ($plan, $amount) => ($plan->currency === 'LKR' ? 'Rs.' : $plan->currency).' '.number_format($amount);
@endphp
<section class="bl-hero pb-5">
    <div class="container text-center" style="max-width: 760px">
        <span class="bl-eyebrow">Pricing</span>
        <h1 class="mt-3 mb-4" style="font-size: clamp(2.2rem, 4.6vw, 3.4rem)">Simple pricing. <span class="bl-highlight">Every feature included.</span></h1>
        <p class="bl-lead mb-4">Plans differ only in size. Start with 14 days free, no card needed.</p>
        @if ($hasYearly)
            <div class="bl-switch" role="group" aria-label="Billing period">
                <button type="button" class="active" data-period="monthly">Monthly</button>
                <button type="button" data-period="yearly">Yearly</button>
            </div>
        @endif
    </div>
</section>

<section class="bl-section-sm pt-0">
    <div class="container">
        @if ($plans->isEmpty())
            <div class="bl-card text-center p-5 mx-auto" style="max-width: 640px">
                <h2 class="fs-4 fw-bold">Pricing is on its way</h2>
                <p class="mb-4">Start your free trial now, and we'll help you choose the right plan for your business.</p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="{{ route('central.signup') }}" class="btn btn-brand">Start free trial</a>
                    <a href="{{ route('central.contact') }}" class="btn btn-ghost">Contact us</a>
                </div>
            </div>
        @else
            <div class="row g-4 justify-content-center">
                @foreach ($plans as $plan)
                    <div class="col-md-6 col-lg-4">
                        <div class="bl-plan {{ $plan->id === $recommendedId ? 'featured' : '' }}">
                            @if ($plan->id === $recommendedId)<span class="badge-featured">Recommended</span>@endif
                            <h2 class="fs-4 fw-bold mb-3">{{ $plan->name }}</h2>
                            <div class="mb-4" data-price-monthly>
                                <span class="bl-price">{{ $money($plan, $plan->price_monthly) }}</span> <small class="text-muted-2">/ month</small>
                            </div>
                            @if ($hasYearly)
                                <div class="mb-4 d-none" data-price-yearly>
                                    @if ($plan->price_yearly > 0)
                                        <span class="bl-price">{{ $money($plan, $plan->price_yearly) }}</span> <small class="text-muted-2">/ year</small>
                                        @if ($plan->price_monthly > 0 && $plan->price_yearly < $plan->price_monthly * 12)
                                            <div class="small text-success fw-semibold mt-2">Save {{ $money($plan, $plan->price_monthly * 12 - $plan->price_yearly) }} a year</div>
                                        @endif
                                    @else
                                        <span class="bl-price">{{ $money($plan, $plan->price_monthly * 12) }}</span> <small class="text-muted-2">/ year</small>
                                    @endif
                                </div>
                            @endif
                            <ul class="bl-ticks mt-0 mb-4">
                                @foreach (\App\Models\Landlord\Plan::LIMITS as $key => $label)
                                    <li>{{ $plan->limit($key) === null ? 'Unlimited' : 'Up to '.number_format($plan->limit($key)) }} {{ $label }}</li>
                                @endforeach
                                <li>Every feature included</li>
                                <li>Nightly backups</li>
                            </ul>
                            <a href="{{ route('central.signup') }}" class="btn {{ $plan->id === $recommendedId ? 'btn-brand' : 'btn-ghost' }} w-100 mt-auto">Start free trial</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

<section class="bl-section-sm bl-band">
    <div class="container">
        <h2 class="bl-title text-center mb-5" style="font-size: clamp(1.6rem, 3vw, 2.2rem)">Included in every plan</h2>
        <div class="row g-3">
            @foreach ([
                'Quotations and invoices with your logo', 'One-click quote to invoice', 'Tax such as VAT', 'Email PDFs to customers',
                'Payments and part payments', 'Outstanding balances and aging', 'Customer statements', 'Repair job notes with signatures',
                'Purchases and vendor bills', 'Products and automatic stock', 'Eight ready-made reports', 'Owner, manager and staff roles',
                'Activity log', 'Optional sign-in codes', 'Nightly backups', 'Your own private database',
            ] as $feature)
                <div class="col-sm-6 col-lg-3"><div class="d-flex gap-2 align-items-start"><i class="bi bi-check-circle-fill text-success mt-1"></i><span>{{ $feature }}</span></div></div>
            @endforeach
        </div>
    </div>
</section>

<section class="bl-section-sm">
    <div class="container" style="max-width: 860px">
        <h2 class="bl-title text-center mb-5">Questions about pricing</h2>
        @include('central._faq')
    </div>
</section>
@endsection

@if ($hasYearly)
    @push('scripts')
    <script>
        document.querySelectorAll('[data-period]').forEach(function (button) {
            button.addEventListener('click', function () {
                const yearly = button.dataset.period === 'yearly';
                document.querySelectorAll('[data-period]').forEach(b => b.classList.toggle('active', b === button));
                document.querySelectorAll('[data-price-monthly]').forEach(el => el.classList.toggle('d-none', yearly));
                document.querySelectorAll('[data-price-yearly]').forEach(el => el.classList.toggle('d-none', ! yearly));
            });
        });
    </script>
    @endpush
@endif
