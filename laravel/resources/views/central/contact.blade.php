@extends('layouts.marketing')

@section('title', 'Contact')
@section('description', 'Talk to the '.config('sjpos.product_name').' team: questions, a demo, or help getting started.')

@section('content')
@php
    $operator = config('tenancy.operator');
    $whatsapp = preg_replace('/\D+/', '', (string) $operator['phone']);
@endphp
<section class="bl-hero pb-5">
    <div class="container text-center" style="max-width: 720px">
        <span class="bl-eyebrow">Contact</span>
        <h1 class="mt-3 mb-4" style="font-size: clamp(2.2rem, 4.6vw, 3.2rem)">We're happy to help</h1>
        <p class="bl-lead">Questions, a quick demo, or help moving your customers and products in. Talk to a real person.</p>
    </div>
</section>

<section class="bl-section-sm pt-0">
    <div class="container">
        <div class="row g-4 justify-content-center">
            @if ($whatsapp !== '')
                <div class="col-md-6 col-lg-4">
                    <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="text-decoration-none">
                        <div class="bl-card text-center">
                            <div class="bl-icon green mx-auto"><i class="bi bi-whatsapp"></i></div>
                            <h3>WhatsApp</h3>
                            <p>{{ $operator['phone'] }}</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="tel:{{ $whatsapp }}" class="text-decoration-none">
                        <div class="bl-card text-center">
                            <div class="bl-icon mx-auto"><i class="bi bi-telephone"></i></div>
                            <h3>Call us</h3>
                            <p>{{ $operator['phone'] }}</p>
                        </div>
                    </a>
                </div>
            @endif
            @if ($operator['email'])
                <div class="col-md-6 col-lg-4">
                    <a href="mailto:{{ $operator['email'] }}" class="text-decoration-none">
                        <div class="bl-card text-center">
                            <div class="bl-icon violet mx-auto"><i class="bi bi-envelope"></i></div>
                            <h3>Email</h3>
                            <p>{{ $operator['email'] }}</p>
                        </div>
                    </a>
                </div>
            @endif
            <div class="col-md-6 col-lg-4">
                <a href="{{ route('central.signup') }}" class="text-decoration-none">
                    <div class="bl-card text-center">
                        <div class="bl-icon amber mx-auto"><i class="bi bi-rocket-takeoff"></i></div>
                        <h3>Try it yourself</h3>
                        <p>Start a free 14-day trial. Setting up takes a few minutes.</p>
                    </div>
                </a>
            </div>
        </div>
        <p class="text-center text-muted-2 mt-5 mb-0">{{ $operator['name'] }} · {{ $operator['country'] }}</p>
    </div>
</section>
@endsection
