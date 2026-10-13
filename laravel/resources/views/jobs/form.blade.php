@extends('layouts.app')

@php($editing = $job->exists)

@section('title', $editing ? 'Edit Job Note' : 'New Job Note')

@section('content')
<div class="animate-fade">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h2 class="h3 mb-1 text-dark">{{ $editing ? 'Edit Job Note' : 'New Job Note' }}</h2>
            <p class="text-muted small mb-0">
                {{ $editing ? 'Update the details of this job note.' : "Record a device received for repair and capture the customer's signature." }}
            </p>
        </div>
        <span class="badge bg-primary fs-6 px-3 py-2 mt-3 mt-md-0">Job No. {{ \App\Models\JobNote::formatNumber($displayJobNo) }}</span>
    </div>

    <form id="jobForm" autocomplete="off" data-ajax-form="redirect"
          action="{{ $editing ? route('jobs.update', $job) : route('jobs.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="row g-4 mb-4">
            <!-- Customer -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-dark small fw-bold text-uppercase"><i class="bi bi-person me-2 text-primary"></i>Customer Details</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="customer_name" class="form-label small fw-bold text-muted text-uppercase">Customer / Company Name *</label>
                                <input type="text" id="customer_name" name="customer_name" class="form-control" value="{{ $job->customer_name }}" list="customer-names" autocomplete="off" required>
                                @include('partials.party-datalist', ['id' => 'customer-names', 'names' => $customerNames])
                            </div>
                            <div class="col-12">
                                <label for="contact_person" class="form-label small fw-bold text-muted text-uppercase">Contact Person</label>
                                <input type="text" id="contact_person" name="contact_person" class="form-control" value="{{ $job->contact_person }}">
                            </div>
                            <div class="col-md-6">
                                <label for="contact_no" class="form-label small fw-bold text-muted text-uppercase">Contact No.</label>
                                <input type="tel" id="contact_no" name="contact_no" class="form-control" inputmode="tel" value="{{ $job->contact_no }}">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label small fw-bold text-muted text-uppercase">Email</label>
                                <input type="email" id="email" name="email" class="form-control" inputmode="email" value="{{ $job->email }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Device -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-dark small fw-bold text-uppercase"><i class="bi bi-laptop me-2 text-primary"></i>Device Details</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label for="item" class="form-label small fw-bold text-muted text-uppercase">Item *</label>
                                <input type="text" id="item" name="item" class="form-control" placeholder="e.g. MacBook Pro 16-inch" value="{{ $job->item }}" required>
                            </div>
                            <div class="col-md-5">
                                <label for="serial_no" class="form-label small fw-bold text-muted text-uppercase">Serial No.</label>
                                <input type="text" id="serial_no" name="serial_no" class="form-control" autocapitalize="characters" value="{{ $job->serial_no }}">
                            </div>
                            <div class="col-12">
                                <label for="fault" class="form-label small fw-bold text-muted text-uppercase">Reported Fault *</label>
                                <textarea id="fault" name="fault" rows="2" class="form-control" required>{{ $job->fault }}</textarea>
                            </div>
                            <div class="col-12">
                                <label for="accessories_received" class="form-label small fw-bold text-muted text-uppercase">Accessories Received</label>
                                <input type="text" id="accessories_received" name="accessories_received" class="form-control" value="{{ $job->accessories_received }}">
                            </div>
                            <div class="col-12">
                                <label for="device_condition" class="form-label small fw-bold text-muted text-uppercase">Device Condition at Collection</label>
                                <textarea id="device_condition" name="device_condition" rows="2" class="form-control" placeholder="Scratches, dents, cracked screen, etc.">{{ $job->device_condition }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remarks -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-dark small fw-bold text-uppercase"><i class="bi bi-chat-left-text me-2 text-primary"></i>Remarks</h5>
            </div>
            <div class="card-body p-4">
                <textarea id="remarks" name="remarks" rows="3" class="form-control" placeholder="Additional notes (optional)">{{ $job->remarks }}</textarea>
            </div>
        </div>

        <!-- Collection -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-dark small fw-bold text-uppercase"><i class="bi bi-calendar-check me-2 text-primary"></i>Collection Details</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="job_no" class="form-label small fw-bold text-muted text-uppercase">Job No.</label>
                        <input type="text" id="job_no" class="form-control fw-bold text-primary" value="{{ \App\Models\JobNote::formatNumber($displayJobNo) }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label for="collected_date" class="form-label small fw-bold text-muted text-uppercase">Collected Date *</label>
                        <input type="date" id="collected_date" name="collected_date" class="form-control" value="{{ $job->collected_date?->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label for="collected_by" class="form-label small fw-bold text-muted text-uppercase">Collected By *</label>
                        <input type="text" id="collected_by" name="collected_by" class="form-control" value="{{ $job->collected_by }}" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terms -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-dark small fw-bold text-uppercase"><i class="bi bi-shield-check me-2 text-primary"></i>Terms &amp; Conditions</h5>
            </div>
            <div class="card-body">
                <textarea name="terms_conditions" class="form-control form-control-sm" rows="6">{{ $job->terms_conditions }}</textarea>
                <div class="form-text">One term per line.</div>
            </div>
        </div>

        <!-- Signature -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-dark small fw-bold text-uppercase"><i class="bi bi-pen me-2 text-primary"></i>Customer Signature</h5>
                @unless ($job->is_signed)
                    <span class="small text-muted">Optional now &mdash; can be signed later</span>
                @endunless
            </div>
            <div class="card-body p-4">
                @if ($job->is_signed)
                    <div class="d-flex flex-wrap align-items-center gap-4">
                        <img src="{{ $job->customer_signature }}" alt="Customer signature"
                            style="max-height: 90px; max-width: 280px; border-bottom: 1.5px solid #0b2545;">
                        <div class="small text-muted">
                            Signed by <strong class="text-dark">{{ $job->signed_name }}</strong><br>
                            on {{ $job->signed_at?->format('d/m/Y h:i A') }}
                        </div>
                    </div>
                    <div class="alert alert-warning small mt-3 mb-0">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="clear_signature" value="1" id="clear_signature">
                            <label class="form-check-label" for="clear_signature">
                                <strong>Remove this signature</strong> so the customer signs the updated job note again.
                                Tick this if you changed something the customer agreed to (fault, condition, accessories, terms).
                            </label>
                        </div>
                    </div>
                @else
                    @include('jobs._signature-pad', ['defaultName' => $editing ? ($job->contact_person ?: $job->customer_name) : ''])
                @endif
            </div>
        </div>

        <div class="d-flex justify-content-center gap-3 mb-5">
            <button type="button" onclick="history.back()" class="btn btn-outline-secondary px-5 py-2">
                <i class="bi bi-arrow-left-circle me-1"></i> Back
            </button>
            <button type="submit" id="saveJobBtn" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                <i class="bi bi-check2-circle me-1"></i> {{ $editing ? 'Update Job Note' : 'Save Job Note' }}
            </button>
        </div>
    </form>
</div>

@include('partials.modals', ['status' => 'Job Note'])
@endsection

@push('scripts')
<script>
// Picking a known customer fills in their saved contact details (only into empty fields)
document.addEventListener('DOMContentLoaded', () => {
    const contacts = @json($customerContacts);
    const fields = { contact_person: 'contact_person', contact_no: 'phone', email: 'email' };
    document.getElementById('customer_name').addEventListener('change', e => {
        const customer = contacts[e.target.value.trim().toLowerCase()];
        if (!customer) return;
        for (const [input, key] of Object.entries(fields)) {
            const el = document.getElementById(input);
            if (!el.value && customer[key]) {
                el.value = customer[key];
                el.dispatchEvent(new Event('input'));
            }
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('signatureCanvas');
    if (!canvas) return; // signature pad is only shown when the job note isn't signed yet

    const status = document.getElementById('sigStatus');
    const pad = new SignaturePad(canvas, {
        onChange: p => {
            status.textContent = p.isEmpty() ? 'Not signed' : 'Signature captured';
            status.className = 'small ms-auto ' + (p.isEmpty() ? 'text-muted' : 'text-success fw-bold');
        }
    });
    document.getElementById('sigUndo').addEventListener('click', () => pad.undo());
    document.getElementById('sigClear').addEventListener('click', () => pad.clear());

    // Default the signer name to the contact person / customer name
    const signedName = document.getElementById('signed_name');
    if (signedName.value) signedName.dataset.touched = '1';
    const syncName = () => {
        if (signedName.dataset.touched) return;
        signedName.value = document.getElementById('contact_person').value || document.getElementById('customer_name').value;
    };
    signedName.addEventListener('input', () => signedName.dataset.touched = '1');
    document.getElementById('customer_name').addEventListener('input', syncName);
    document.getElementById('contact_person').addEventListener('input', syncName);

    // Put the drawn signature into the form just before the AJAX submit (see app.js)
    document.getElementById('jobForm').beforeAjaxSubmit = () => {
        document.getElementById('customer_signature').value = pad.isEmpty() ? '' : pad.toDataURL();
    };
});
</script>
@endpush
