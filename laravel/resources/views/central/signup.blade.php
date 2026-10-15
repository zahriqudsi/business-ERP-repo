@extends('layouts.platform')

@section('title', 'Start your free trial')
@section('brand_url', route('central.home'))

@section('nav')
    <a href="{{ route('central.find') }}" class="btn btn-outline-secondary btn-sm">Sign in</a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 fw-bold mb-1">Start your free trial</h1>
                <p class="text-body-secondary mb-4">Your business gets its own address and its own private data.</p>

                <form method="POST" action="{{ route('central.signup.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label for="business_name" class="form-label small fw-semibold">Business name</label>
                        <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}" class="form-control @error('business_name') is-invalid @enderror" maxlength="150" required autofocus>
                        @error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="slug" class="form-label small fw-semibold">Your address</label>
                        <div class="input-group has-validation">
                            <input type="text" id="slug" name="slug" value="{{ old('slug') }}" class="form-control @error('slug') is-invalid @enderror" maxlength="63" pattern="[a-z0-9-]+" placeholder="yourbusiness" required>
                            <span class="input-group-text">.{{ $baseDomain }}</span>
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text">Lowercase letters, numbers and hyphens.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="name" class="form-label small fw-semibold">Your name</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" maxlength="100" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label small fw-semibold">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" maxlength="255" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="username" class="form-label small fw-semibold">Username for signing in</label>
                        <input type="text" id="username" name="username" value="{{ old('username') }}" class="form-control @error('username') is-invalid @enderror" maxlength="100" autocomplete="username" required>
                        @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label small fw-semibold">Password</label>
                        <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" minlength="8" autocomplete="new-password" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label small fw-semibold">Password again</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" minlength="8" autocomplete="new-password" required>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input @error('accept_terms') is-invalid @enderror" type="checkbox" id="accept_terms" name="accept_terms" value="1" @checked(old('accept_terms')) required>
                            <label class="form-check-label small" for="accept_terms">
                                I agree to the <a href="{{ route('central.terms') }}" target="_blank">Terms of Service</a> and <a href="{{ route('central.privacy') }}" target="_blank">Privacy Policy</a>.
                            </label>
                            @error('accept_terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Create my business</button>
                        <p class="small text-body-secondary text-center mt-2 mb-0">14 days free. Setting up takes a few seconds.</p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
