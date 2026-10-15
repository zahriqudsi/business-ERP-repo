@extends('layouts.marketing')

@section('title', $solution['title'])
@section('description', $solution['headline'].'. '.$solution['lead'])

@section('content')
<section class="bl-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="bl-eyebrow"><i class="bi bi-{{ $solution['icon'] }}"></i> {{ $solution['eyebrow'] }}</span>
                <h1 class="mt-3 mb-4" style="font-size: clamp(2.2rem, 4.6vw, 3.4rem)">{{ $solution['headline'] }}</h1>
                <p class="bl-lead mb-4">{{ $solution['lead'] }}</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('central.signup') }}" class="btn btn-brand btn-lg">Start your free trial</a>
                    <a href="{{ route('central.pricing') }}" class="btn btn-ghost btn-lg">See pricing</a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="bl-card p-4 p-md-5">
                    <h2 class="fs-5 fw-bold mb-3">Sound familiar?</h2>
                    <ul class="list-unstyled mb-0">
                        @foreach ($solution['pains'] as $pain)
                            <li class="d-flex gap-2 mb-3"><i class="bi bi-x-circle-fill text-danger mt-1"></i><span>{{ $pain }}</span></li>
                        @endforeach
                    </ul>
                    <p class="fw-semibold text-primary mb-0 mt-2"><i class="bi bi-arrow-down-circle me-1"></i>{{ config('sjpos.product_name') }} fixes all three.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bl-section bl-band">
    <div class="container">
        <h2 class="bl-title text-center mb-5">How {{ config('sjpos.product_name') }} helps</h2>
        <div class="row g-4">
            @foreach ($solution['points'] as [$icon, $title, $text])
                <div class="col-md-6 col-lg-3">
                    <div class="bl-card">
                        <div class="bl-icon"><i class="bi bi-{{ $icon }}"></i></div>
                        <h3>{{ $title }}</h3>
                        <p>{{ $text }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="bl-section-sm">
    <div class="container text-center">
        <p class="text-muted-2 mb-3">Also made for</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            @foreach (\App\Http\Controllers\Central\SolutionController::SOLUTIONS as $otherSlug => $other)
                @continue($otherSlug === $slug)
                <a href="{{ route('central.solutions', $otherSlug) }}" class="btn btn-ghost"><i class="bi bi-{{ $other['icon'] }} me-1"></i>{{ $other['title'] }}</a>
            @endforeach
        </div>
    </div>
</section>
@endsection
