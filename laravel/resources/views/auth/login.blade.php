@extends('layouts.app')

@section('title', 'Log In')

@section('content')
<div class="row justify-content-center align-items-center min-vh-75 mt-5">
    <div class="col-md-5 col-lg-4">
        <div class="card border-0 shadow-lg p-0 overflow-hidden">
            <div class="hero-gradient p-4 text-center text-white">
                <div class="d-inline-flex align-items-center justify-content-center bg-white bg-opacity-25 rounded-circle mb-3" style="width: 72px; height: 72px;">
                    <i class="bi bi-shield-lock text-white fs-1"></i>
                </div>
                <h3 class="mb-1 fw-800 text-white">Welcome Back</h3>
                <p class="text-white opacity-75 small mb-0">{{ config('sjpos.company.name') }}</p>
            </div>

            <div class="card-body p-4 p-md-5">
                @error('login')
                <div class="alert alert-danger border-0 small text-center mb-4 py-2 animate-fade">{{ $message }}</div>
                @enderror
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase ls-1">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                            <input type="text" name="username" value="{{ old('username') }}" class="form-control border-start-0 ps-0" placeholder="Enter username" required autofocus>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase ls-1">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-key text-muted"></i></span>
                            <input type="password" name="password" class="form-control border-start-0 ps-0" placeholder="Enter password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm d-flex align-items-center justify-content-center">
                        <span>Sign In to Dashboard</span>
                        <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center mt-4 text-muted small">
            &copy; {{ date('Y') }} <span class="fw-bold">{{ config('sjpos.company.name') }}</span>. Secure access.
        </p>
    </div>
</div>
@endsection
