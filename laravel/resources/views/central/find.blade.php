@extends('layouts.marketing')

@section('title', 'Find my business')

@section('no_cta', true)

@section('content')
<div class="bl-hero py-5">
<div class="container py-lg-4">
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="bl-form-card">
            <div>
                <h1 class="h4 fw-bold mb-1">Find my business</h1>
                <p class="text-body-secondary mb-4">Enter the email you signed up with and we'll email you the address to sign in at.</p>

                <form method="POST" action="{{ route('central.find.send') }}">
                    @csrf
                    <label for="email" class="form-label small fw-semibold">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control mb-3 @error('email') is-invalid @enderror" required autofocus>
                    <button type="submit" class="btn btn-primary w-100">Email me my sign-in address</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
</div>
@endsection
