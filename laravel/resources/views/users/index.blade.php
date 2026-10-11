@extends('layouts.app')

@section('title', 'Users')

@php($roleBadge = ['owner' => 'primary', 'manager' => 'info', 'staff' => 'secondary'])

@section('content')
<div class="animate-fade">
  <div class="page-header">
    <div>
      <h1 class="h3 mb-1"><i class="bi bi-person-gear me-2 text-primary"></i>Users</h1>
      <p class="text-body-secondary small mb-0">Give each person their own login so the activity log shows who did what.</p>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Add user</a>
  </div>

  <div class="card mb-4">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th class="ps-4">User</th><th>Role</th><th>Status</th><th>Last sign-in</th><th class="text-end pe-4"></th></tr></thead>
        <tbody>
          @foreach ($users as $user)
          <tr>
            <td class="ps-4">
              <div class="fw-semibold">{{ $user->display_name }} @if ($user->is(auth()->user()))<span class="badge bg-light text-dark border ms-1">You</span>@endif</div>
              <div class="small text-body-secondary">{{ $user->username }}{{ $user->email ? ' · '.$user->email : '' }}</div>
            </td>
            <td><span class="badge text-bg-{{ $roleBadge[$user->role] }}">{{ \App\Models\User::ROLES[$user->role] }}</span></td>
            <td>
              @if ($user->is_active)
                <span class="status-badge status-paid">Active</span>
              @else
                <span class="status-badge status-draft">Switched off</span>
              @endif
            </td>
            <td class="small text-body-secondary">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
            <td class="text-end pe-4"><a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="row g-3">
    @foreach (\App\Models\User::ROLE_DESCRIPTIONS as $role => $description)
      <div class="col-md-4">
        <div class="card kpi-card">
          <div class="fw-semibold mb-1"><span class="badge text-bg-{{ $roleBadge[$role] }} me-1">{{ \App\Models\User::ROLES[$role] }}</span></div>
          <div class="small text-body-secondary">{{ $description }}</div>
        </div>
      </div>
    @endforeach
  </div>
</div>
@endsection
