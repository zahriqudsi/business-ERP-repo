@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="animate-fade">
  <div class="mb-4">
    <h2 class="h3 mb-1"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Reports</h2>
    <p class="text-muted small mb-0">Every report can be downloaded as a spreadsheet (CSV) or a PDF.</p>
  </div>

  <div class="row g-3">
    @foreach ($reports as $key => [$title, $description, $icon])
    <div class="col-md-6 col-xl-3">
      <a href="{{ route('reports.show', $key) }}" class="card border-0 shadow-sm h-100 text-decoration-none report-card">
        <div class="card-body">
          <div class="report-icon mb-3"><i class="bi bi-{{ $icon }}"></i></div>
          <h3 class="h6 fw-bold text-body mb-1">{{ $title }}</h3>
          <p class="small text-muted mb-0">{{ $description }}</p>
        </div>
      </a>
    </div>
    @endforeach
  </div>
</div>
@endsection
