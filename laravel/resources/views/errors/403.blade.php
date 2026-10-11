@extends('layouts.app')

@section('title', 'Not allowed')

@section('content')
<div class="animate-fade d-flex justify-content-center">
  <div class="card text-center p-5" style="max-width: 520px;">
    <div class="mb-3"><span class="kpi-icon tone-amber" style="width:56px;height:56px;font-size:1.6rem"><i class="bi bi-shield-lock"></i></span></div>
    <h1 class="h4">You don't have access to this page</h1>
    <p class="text-body-secondary mb-4">
      @auth
        Your role is <strong>{{ \App\Models\User::ROLES[auth()->user()->role] ?? auth()->user()->role }}</strong>. Ask the owner if you need access.
      @else
        Please sign in first.
      @endauth
    </p>
    <div class="d-flex gap-2 justify-content-center">
      <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="btn btn-outline-secondary">Go back</a>
      <a href="{{ route('dashboard') }}" class="btn btn-primary">Dashboard</a>
    </div>
  </div>
</div>
@endsection
