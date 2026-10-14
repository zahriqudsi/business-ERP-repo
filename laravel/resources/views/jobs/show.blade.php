@extends('layouts.app')

@section('title', 'Job Note '.$job->formatted_no)

@push('styles')
<style>
.job-sheet { max-width: 900px; margin: 0 auto; }
.job-sheet .card-body { overflow-x: auto; }
.job-sheet .job-doc { min-width: 560px; }
@media print {
    @page { size: A4; margin: 12mm; }
    body { background: #fff !important; }
    .navbar, .no-print { display: none !important; }
    /* custom-styles.css hides main > * when printing (meant for modal printing) */
    main > .job-view { display: block !important; }
    main.container { padding: 0 !important; max-width: none; }
    .job-sheet { box-shadow: none !important; max-width: none; }
    .job-sheet .card-body { padding: 0 !important; }
    .job-doc .jd-strip td, .job-doc .jd-rule, .job-doc .jd-card-head { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
@endpush

@section('content')
<div class="animate-fade job-view">
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4 no-print">
        <a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>All Job Notes</a>
        <div class="d-flex flex-wrap gap-2">
            @unless ($job->is_signed)
                <a href="#signNow" class="btn btn-warning fw-bold"><i class="bi bi-pen me-1"></i>Customer Sign</a>
            @endunless
            <a href="{{ route('jobs.edit', $job) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Edit</a>
            <button type="button" onclick="window.print()" class="btn btn-outline-primary"><i class="bi bi-printer me-1"></i>Print</button>
            <a href="{{ route('documents.email', ['type' => 'job', 'id' => $job->id]) }}" class="btn btn-outline-primary"><i class="bi bi-envelope me-1"></i>Email</a>
            <a href="{{ route('jobs.pdf', $job) }}" target="_blank" class="btn btn-primary"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
        </div>
    </div>

    <div class="job-sheet card paper border-0 shadow-sm mb-4" data-bs-theme="light">
        <div class="card-body p-3 p-md-4 p-lg-5">
            @include('jobs._document', ['logoSrc' => logo_url()])
        </div>
    </div>

    @unless ($job->is_signed)
    <div class="card border-0 shadow-sm mb-5 no-print" id="signNow">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-dark small fw-bold text-uppercase"><i class="bi bi-pen me-2 text-primary"></i>Customer Signature</h5>
        </div>
        <div class="card-body p-4">
            @include('jobs._signature-pad', ['defaultName' => $job->contact_person ?: $job->customer_name])
            <div class="text-end mt-3">
                <button type="button" id="saveSigBtn" class="btn btn-primary px-4 fw-bold"><i class="bi bi-check2-circle me-1"></i>Save Signature</button>
            </div>
            <div id="sigError" class="alert alert-danger mt-3 d-none"></div>
        </div>
    </div>
    @endunless
</div>
@endsection

@unless ($job->is_signed)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const status = document.getElementById('sigStatus');
    const errorBox = document.getElementById('sigError');
    const btn = document.getElementById('saveSigBtn');
    const pad = new SignaturePad(document.getElementById('signatureCanvas'), {
        onChange: p => {
            status.textContent = p.isEmpty() ? 'Not signed' : 'Signature captured';
            status.className = 'small ms-auto ' + (p.isEmpty() ? 'text-muted' : 'text-success fw-bold');
        }
    });
    document.getElementById('sigUndo').addEventListener('click', () => pad.undo());
    document.getElementById('sigClear').addEventListener('click', () => pad.clear());

    const showError = msg => {
        errorBox.textContent = msg;
        errorBox.classList.remove('d-none');
        btn.disabled = false;
    };

    btn.addEventListener('click', () => {
        errorBox.classList.add('d-none');
        if (pad.isEmpty()) {
            showError('Please ask the customer to sign in the box above.');
            return;
        }
        const body = new FormData();
        body.append('customer_signature', pad.toDataURL());
        body.append('signed_name', document.getElementById('signed_name').value);
        btn.disabled = true;

        sjFetch(@json(route('jobs.sign', $job)), { method: 'POST', body })
            .then(res => res.json())
            .then(data => data.status === 'success' ? window.location.reload() : showError(data.message))
            .catch(() => showError('Could not save the signature. Please try again.'));
    });
});
</script>
@endpush
@endunless
