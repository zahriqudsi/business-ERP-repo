@extends('layouts.app')

@section('title', 'Verify Login')

@section('content')
<div class="row justify-content-center align-items-center min-vh-75 mt-5">
    <div class="col-md-5 col-lg-4">
        <div class="card border-0 shadow-lg p-0 overflow-hidden">
            <div class="hero-gradient p-4 text-center text-white">
                <div class="d-inline-flex align-items-center justify-content-center bg-white bg-opacity-25 rounded-circle mb-3" style="width: 72px; height: 72px;">
                    <i class="bi bi-envelope-check text-white fs-1"></i>
                </div>
                <h3 class="mb-1 fw-800 text-white">Verify It's You</h3>
                <p class="text-white opacity-75 small mb-0">Enter the code sent to your email</p>
            </div>

            <div class="card-body p-4 p-md-5">
                @error('otp')
                <div class="alert alert-danger border-0 small text-center mb-4 py-2">{{ $message }}</div>
                @enderror
                @if (session('info'))
                <div class="alert alert-success border-0 small text-center mb-4 py-2">{{ session('info') }}</div>
                @endif

                <form method="POST" action="{{ route('otp.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase ls-1">Verification Code</label>
                        <input type="text" name="otp" class="form-control text-center fs-3 fw-bold" style="letter-spacing:8px;" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" placeholder="000000" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm mb-3">
                        Verify &amp; Continue
                    </button>
                </form>
                <form method="POST" action="{{ route('otp.resend') }}">
                    @csrf
                    <button type="submit" class="btn btn-link w-100 text-decoration-none small">
                        Didn't get a code? Resend
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
