@extends('layouts.app')

@section('title', 'Email '.$document['label'])

@section('content')
<div class="animate-fade">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <a href="{{ $document['back'] }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to {{ $document['label'] }}</a>
      <h2 class="h3 mt-1 mb-0"><i class="bi bi-envelope me-2 text-primary"></i>Email {{ $document['label'] }}</h2>
    </div>
  </div>

  @unless ($mailIsLive)
    <div class="alert alert-warning small">
      <i class="bi bi-exclamation-triangle me-1"></i>
      Email delivery isn't set up yet, so messages are written to the log file instead of being sent.
      Add your mail server (SMTP or Mailgun) details to the <code>.env</code> file to send for real.
    </div>
  @endunless

  <div class="row g-4">
    <div class="col-lg-8">
      <form method="POST" action="{{ route('documents.email.send', ['type' => $type, 'id' => $id]) }}" class="card border-0 shadow-sm">
        @csrf
        @isset($document['from'])
          <input type="hidden" name="period_from" value="{{ $document['from']->toDateString() }}">
          <input type="hidden" name="period_to" value="{{ $document['to']->toDateString() }}">
        @endisset
        <div class="card-body p-4">
          <div class="mb-3">
            <label for="to" class="form-label fw-semibold small">To <span class="text-danger">*</span></label>
            <input type="email" id="to" name="to" class="form-control" value="{{ old('to', $document['email']) }}" required autofocus placeholder="customer@example.com">
            @if ($document['customer'] && blank($document['customer']->email))
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="save_email" value="1" id="save_email" @checked(old('save_email', true))>
                <label class="form-check-label small" for="save_email">Save this address on {{ $document['customer']->name }}'s customer record</label>
              </div>
            @endif
          </div>
          <div class="mb-3">
            <label for="cc" class="form-label fw-semibold small">CC</label>
            <input type="text" id="cc" name="cc" class="form-control" value="{{ old('cc') }}" placeholder="Optional, separate several with commas">
          </div>
          <div class="mb-3">
            <label for="subject" class="form-label fw-semibold small">Subject <span class="text-danger">*</span></label>
            <input type="text" id="subject" name="subject" class="form-control" value="{{ old('subject', $document['subject']) }}" required maxlength="255">
          </div>
          <div class="mb-3">
            <label for="message" class="form-label fw-semibold small">Message <span class="text-danger">*</span></label>
            <textarea id="message" name="message" class="form-control" rows="9" required maxlength="5000">{{ old('message', $document['message']) }}</textarea>
          </div>
          <div class="small text-muted"><i class="bi bi-paperclip me-1"></i>The {{ strtolower($document['label']) }} PDF is attached automatically. Replies go to {{ config('sjpos.company.email') }}.</div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2 p-3">
          <a href="{{ $document['back'] }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Send</button>
        </div>
      </form>
    </div>

    <div class="col-lg-4">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold small"><i class="bi bi-clock-history me-2"></i>Previously sent</div>
        <ul class="list-group list-group-flush small">
          @forelse ($history as $sent)
            <li class="list-group-item">
              <div class="fw-semibold">{{ $sent->recipient }}</div>
              @if ($sent->cc)<div class="text-muted">CC {{ $sent->cc }}</div>@endif
              <div class="text-muted">{{ $sent->sent_at->format('d M Y H:i') }}{{ $sent->user ? ' by '.$sent->user->username : '' }}</div>
            </li>
          @empty
            <li class="list-group-item text-muted">Not emailed yet.</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection
