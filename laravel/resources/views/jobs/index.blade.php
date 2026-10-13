@extends('layouts.app')

@section('title', 'Job Notes')

@section('content')
<div class="animate-fade">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h2 class="h3 mb-1 text-dark">Job Notes</h2>
            <p class="text-muted small">Devices received for repair.</p>
        </div>
        <div>
            <a href="{{ route('jobs.create') }}" class="btn btn-primary shadow-sm"><i class="bi bi-plus-lg me-2"></i>New Job Note</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchInput" placeholder="Search job no, customer, item, serial..." class="form-control border-start-0 ps-0">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table data-list-table data-per-page="20" data-empty-text="No job notes match your search." class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Job #</th>
                        <th>Customer</th>
                        <th>Item</th>
                        <th>Collected</th>
                        <th>Signature</th>
                        <th class="text-center pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jobs as $job)
                    <tr>
                        <td class="ps-4"><span class="fw-bold text-primary">{{ $job->formatted_no }}</span></td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $job->customer_name }}</div>
                            <div class="text-muted small">{{ trim($job->contact_person.' '.$job->contact_no) }}</div>
                        </td>
                        <td>
                            <div>{{ $job->item }}</div>
                            <div class="text-muted small">{{ $job->serial_no }}</div>
                        </td>
                        <td class="text-muted"><i class="bi bi-calendar-event me-2"></i>{{ $job->collected_date->format('d/m/Y') }}</td>
                        <td>
                            @if ($job->signed)
                                <span class="badge bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle me-1"></i>Signed</span>
                            @else
                                <span class="badge bg-warning bg-opacity-25 text-dark"><i class="bi bi-hourglass me-1"></i>Pending</span>
                            @endif
                        </td>
                        <td class="text-center pe-4">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('jobs.show', $job) }}" class="action-btn-circle btn-light-info" title="View"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('jobs.edit', $job) }}" class="action-btn-circle btn-light-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                @unless ($job->signed)
                                    <a href="{{ route('jobs.show', $job) }}#signNow" class="action-btn-circle btn-light-primary" title="Customer Sign"><i class="bi bi-pen"></i></a>
                                @endunless
                                <a href="{{ route('jobs.pdf', $job) }}" target="_blank" class="action-btn-circle btn-light-success" title="PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                                @can('delete-records')
                                <button type="button" class="action-btn-circle btn-light-danger" title="Delete"
                                    data-delete-url="{{ route('jobs.destroy', $job) }}"
                                    data-delete-label="job note {{ $job->formatted_no }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr data-empty><td colspan="6" class="text-center py-5 text-muted">No job notes yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="pagination" class="d-flex justify-content-center mt-4"></div>
</div>

@include('partials.modals', ['delete' => 'Delete Permanently'])
@endsection

@push('scripts')
<script src="{{ asset('assets/js/list-table.js') }}"></script>
@endpush
