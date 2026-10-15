@extends('layouts.platform')

@section('title', 'Platform admin')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="h4 fw-bold mb-1"><i class="bi bi-shield-lock me-2 text-primary"></i>Platform admin</h1>
                <p class="text-body-secondary small mb-4">For platform staff only. Businesses sign in at their own address.</p>

                <form method="POST" action="{{ route('admin.login.store') }}">
                    @csrf
                    <label for="email" class="form-label small fw-semibold">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control mb-3" autocomplete="username" required autofocus>
                    <label for="password" class="form-label small fw-semibold">Password</label>
                    <input type="password" id="password" name="password" class="form-control mb-4" autocomplete="current-password" required>
                    <button type="submit" class="btn btn-primary w-100">Sign in</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
