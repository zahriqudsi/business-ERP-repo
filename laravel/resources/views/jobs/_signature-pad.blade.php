{{-- Signature pad UI. Expects $defaultName (optional) to prefill the signer name. --}}
<div class="signature-block">
    <p class="small text-muted mb-2">
        <i class="bi bi-info-circle me-1"></i>
        By signing below, the customer confirms the details above are correct and agrees to the terms &amp; conditions.
    </p>
    <div class="signature-wrap">
        <canvas id="signatureCanvas" class="signature-canvas" aria-label="Customer signature pad"></canvas>
        <div class="signature-guide"><span>&times;</span> Sign here</div>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
        <button type="button" class="btn btn-outline-secondary btn-sm w-auto mb-0" id="sigUndo"><i class="bi bi-arrow-counterclockwise me-1"></i>Undo</button>
        <button type="button" class="btn btn-outline-danger btn-sm w-auto mb-0" id="sigClear"><i class="bi bi-eraser me-1"></i>Clear</button>
        <span class="small text-muted ms-auto" id="sigStatus">Not signed</span>
    </div>
    <div class="mt-3">
        <label for="signed_name" class="form-label small fw-bold text-muted text-uppercase">Signed By (Name)</label>
        <input type="text" id="signed_name" name="signed_name" class="form-control" placeholder="Customer's name"
            value="{{ $defaultName ?? '' }}">
    </div>
    <input type="hidden" name="customer_signature" id="customer_signature">
</div>

@once
@push('styles')
<style>
.signature-wrap {
    position: relative;
    border: 2px dashed #b8c4d6;
    border-radius: 12px;
    background: #fbfcfe;
    height: 220px;
}
.signature-canvas {
    width: 100%;
    height: 100%;
    display: block;
    border-radius: 12px;
    cursor: crosshair;
    /* Stop the page scrolling / zooming / magnifier while signing on iPad & tablets */
    touch-action: none;
    -webkit-user-select: none;
    user-select: none;
    -webkit-touch-callout: none;
    position: relative;
    z-index: 1;
}
.signature-guide {
    position: absolute;
    left: 24px;
    right: 24px;
    bottom: 48px;
    border-bottom: 1px solid #c7d0dd;
    color: #9aa7b8;
    font-size: 13px;
    padding-bottom: 4px;
    pointer-events: none;
}
.signature-guide span { font-size: 20px; margin-right: 6px; }
@media (max-width: 576px) { .signature-wrap { height: 180px; } }
</style>
@endpush
@push('scripts')
<script src="{{ asset('assets/js/signature-pad.js') }}"></script>
@endpush
@endonce
