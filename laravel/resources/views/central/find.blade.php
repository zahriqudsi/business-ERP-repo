@extends('layouts.platform')

@section('title', 'Find my business')
@section('brand_url', route('central.home'))

@section('nav')
    <a href="{{ route('central.signup') }}" class="btn btn-primary btn-sm">Start free</a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
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
@endsection
