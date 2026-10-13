@extends('layouts.app')

@section('title', 'My Account')

@section('content')
<div class="animate-fade">
  <div class="page-header">
    <div>
      <h1 class="h3 mb-1"><i class="bi bi-person-circle me-2 text-primary"></i>My account</h1>
      <p class="text-body-secondary small mb-0">Signed in as <strong>{{ $user->username }}</strong> · {{ \App\Models\User::ROLES[$user->role] }}</p>
    </div>
  </div>

  <form method="POST" action="{{ route('account.update') }}" class="row g-4">
    @csrf
    @method('PUT')
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">Your details</div>
        <div class="card-body">
          <div class="mb-3">
            <label for="name" class="form-label small fw-semibold">Full name</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" maxlength="100" autocomplete="name">
          </div>
          <div>
            <label for="email" class="form-label small fw-semibold">Email</label>
            <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" autocomplete="email">
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">Change password</div>
        <div class="card-body">
          <div class="mb-3">
            <label for="current_password" class="form-label small fw-semibold">Current password</label>
            <input type="password" id="current_password" name="current_password" class="form-control" autocomplete="current-password">
          </div>
          <div class="mb-3">
            <label for="password" class="form-label small fw-semibold">New password</label>
            <input type="password" id="password" name="password" class="form-control" autocomplete="new-password" minlength="8">
            <div class="form-text">At least 8 characters. Leave empty to keep your current password.</div>
          </div>
          <div>
            <label for="password_confirmation" class="form-label small fw-semibold">Repeat new password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password">
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 d-flex justify-content-end">
      <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Save</button>
    </div>
  </form>
</div>
@endsection
