@extends('layouts.app')

@php($editing = $user->exists)

@section('title', $editing ? 'Edit '.$user->username : 'Add User')

@section('content')
<div class="animate-fade">
  <div class="page-header">
    <div>
      <a href="{{ route('users.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>All users</a>
      <h1 class="h3 mt-1 mb-0">{{ $editing ? 'Edit '.$user->display_name : 'Add a user' }}</h1>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <form method="POST" action="{{ $editing ? route('users.update', $user) : route('users.store') }}" class="card">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="card-body p-4 row g-3">
          <div class="col-md-6">
            <label for="username" class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
            <input type="text" id="username" name="username" class="form-control" value="{{ old('username', $user->username) }}" required maxlength="100" autocomplete="off">
            <div class="form-text">Used to sign in. Letters, numbers, dashes and underscores.</div>
          </div>
          <div class="col-md-6">
            <label for="name" class="form-label small fw-semibold">Full name</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" maxlength="100">
          </div>
          <div class="col-md-6">
            <label for="email" class="form-label small fw-semibold">Email</label>
            <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $user->email) }}">
          </div>
          <div class="col-md-6">
            <label for="role" class="form-label small fw-semibold">Role <span class="text-danger">*</span></label>
            <select id="role" name="role" class="form-select" required>
              @foreach (\App\Models\User::ROLES as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
              @endforeach
            </select>
            <div class="form-text" id="roleHelp"></div>
          </div>
          <div class="col-md-6">
            <label for="password" class="form-label small fw-semibold">{{ $editing ? 'New password' : 'Password' }} @unless ($editing)<span class="text-danger">*</span>@endunless</label>
            <input type="password" id="password" name="password" class="form-control" minlength="8" autocomplete="new-password" @unless ($editing) required @endunless>
            <div class="form-text">{{ $editing ? 'Leave empty to keep their current password.' : 'At least 8 characters. Share it with them privately.' }}</div>
          </div>
          <div class="col-md-6">
            <label for="password_confirmation" class="form-label small fw-semibold">Repeat password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password">
          </div>
          @if ($editing)
          <div class="col-12">
            <div class="form-check form-switch">
              <input type="hidden" name="is_active" value="0">
              <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $user->is_active))>
              <label class="form-check-label fw-semibold" for="is_active">Account is active</label>
              <div class="form-text mt-0">Switched-off users can't sign in. Their past work stays in the records.</div>
            </div>
          </div>
          @endif
        </div>
        <div class="card-footer d-flex justify-content-end gap-2 p-3">
          <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $editing ? 'Save changes' : 'Add user' }}</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const help = @json(\App\Models\User::ROLE_DESCRIPTIONS);
  const role = document.getElementById('role');
  const show = () => { document.getElementById('roleHelp').textContent = help[role.value] || ''; };
  role.addEventListener('change', show);
  show();
});
</script>
@endpush
