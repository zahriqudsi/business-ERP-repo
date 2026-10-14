@extends('layouts.app')

@section('title', 'Settings')

@php
    $value = fn (string $key, $default = '') => old($key, data_get($settings, $key, $default));
    $sections = [
        'business' => ['Business', 'shop'],
        'branding' => ['Logo', 'image'],
        'bank' => ['Bank account', 'bank'],
        'documents' => ['Documents', 'file-earmark-text'],
        'tax' => ['Tax', 'percent'],
        'general' => ['Money & backups', 'sliders'],
        'sign-in' => ['Sign-in', 'shield-lock'],
    ];
@endphp

@push('styles')
<style>
  .settings-nav { position: sticky; top: 76px; }
  .settings-nav a { display: flex; gap: .6rem; align-items: center; padding: .5rem .75rem; border-radius: 9px; color: var(--bs-secondary-color); text-decoration: none; font-weight: 500; }
  .settings-nav a:hover { background: var(--bs-secondary-bg); color: var(--bs-body-color); }
  .settings-section { scroll-margin-top: 76px; }
  .logo-preview { width: 120px; height: 120px; object-fit: contain; border: 1px dashed var(--bs-border-color); border-radius: 12px; padding: 8px; background: #fff; }
</style>
@endpush

@section('content')
<div class="animate-fade">
  <div class="page-header">
    <div>
      <h1 class="h3 mb-1"><i class="bi bi-gear me-2 text-primary"></i>Settings</h1>
      <p class="text-body-secondary small mb-0">These details appear on your invoices, quotations, job notes, emails and reports.</p>
    </div>
  </div>

  <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="row g-4">
    @csrf
    @method('PUT')

    <div class="col-lg-3 d-none d-lg-block">
      <nav class="settings-nav card p-2" aria-label="Settings sections">
        @foreach ($sections as $id => [$label, $icon])
          <a href="#{{ $id }}"><i class="bi bi-{{ $icon }}"></i>{{ $label }}</a>
        @endforeach
        <hr class="my-2">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Save settings</button>
      </nav>
    </div>

    <div class="col-lg-9">
      {{-- Business --}}
      <section id="business" class="card settings-section mb-4">
        <div class="card-header"><i class="bi bi-shop me-2"></i>Business</div>
        <div class="card-body row g-3">
          <div class="col-md-7">
            <label for="company_name" class="form-label small fw-semibold">Business name <span class="text-danger">*</span></label>
            <input type="text" id="company_name" name="company[name]" class="form-control" value="{{ $value('company.name') }}" required maxlength="100">
          </div>
          <div class="col-md-5">
            <label for="company_tagline" class="form-label small fw-semibold">Tagline</label>
            <input type="text" id="company_tagline" name="company[tagline]" class="form-control" value="{{ $value('company.tagline') }}" maxlength="150" placeholder="e.g. Laptop Repairs & Accessories">
          </div>
          <div class="col-md-6">
            <label for="company_address" class="form-label small fw-semibold">Address</label>
            <textarea id="company_address" name="company[address]" class="form-control" rows="3" maxlength="500" placeholder="One line per row, as printed on documents">{{ old('company.address', data_get($settings, 'company.address_multiline')) }}</textarea>
          </div>
          <div class="col-md-6 row g-3 m-0 p-0">
            <div class="col-12">
              <label for="company_phone" class="form-label small fw-semibold">Phone</label>
              <input type="tel" id="company_phone" name="company[phone]" class="form-control" value="{{ $value('company.phone') }}">
            </div>
            <div class="col-12">
              <label for="company_email" class="form-label small fw-semibold">Email</label>
              <input type="email" id="company_email" name="company[email]" class="form-control" value="{{ $value('company.email') }}">
              <div class="form-text">Customers' replies to emailed documents go here.</div>
            </div>
          </div>
          <div class="col-md-4">
            <label for="company_website" class="form-label small fw-semibold">Website</label>
            <input type="text" id="company_website" name="company[website]" class="form-control" value="{{ $value('company.website') }}" placeholder="example.com">
          </div>
          <div class="col-md-4">
            <label for="company_job_phone" class="form-label small fw-semibold">Job note phone</label>
            <input type="tel" id="company_job_phone" name="company[job_phone]" class="form-control" value="{{ $value('company.job_phone') }}" placeholder="Same as phone if empty">
          </div>
          <div class="col-md-4">
            <label for="company_job_email" class="form-label small fw-semibold">Job note email</label>
            <input type="email" id="company_job_email" name="company[job_email]" class="form-control" value="{{ $value('company.job_email') }}" placeholder="Same as email if empty">
          </div>
        </div>
      </section>

      {{-- Logo --}}
      <section id="branding" class="card settings-section mb-4">
        <div class="card-header"><i class="bi bi-image me-2"></i>Logo</div>
        <div class="card-body d-flex flex-wrap gap-4 align-items-center">
          <img src="{{ logo_url() }}" alt="Current logo" class="logo-preview" id="logoPreview">
          <div class="flex-grow-1">
            <label for="logo" class="form-label small fw-semibold">Upload a new logo</label>
            <input type="file" id="logo" name="logo" class="form-control" accept="image/png,image/jpeg">
            <div class="form-text">PNG or JPG, up to 2 MB. A square or wide image with a transparent background looks best.</div>
            @if (Str::startsWith((string) data_get($settings, 'company.logo'), ['uploads/', 'branding/']))
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo">
                <label class="form-check-label small" for="remove_logo">Remove my logo and use the default</label>
              </div>
            @endif
          </div>
        </div>
      </section>

      {{-- Bank --}}
      <section id="bank" class="card settings-section mb-4">
        <div class="card-header"><i class="bi bi-bank me-2"></i>Bank account <span class="fw-normal small text-body-secondary">· offered on invoices for bank transfers</span></div>
        <div class="card-body row g-3">
          @foreach (['account_name' => 'Account name', 'bank_name' => 'Bank', 'account_number' => 'Account number', 'branch' => 'Branch'] as $field => $label)
            <div class="col-md-6">
              <label for="bank_{{ $field }}" class="form-label small fw-semibold">{{ $label }}</label>
              <input type="text" id="bank_{{ $field }}" name="bank[{{ $field }}]" class="form-control" value="{{ $value('bank.'.$field) }}">
            </div>
          @endforeach
        </div>
      </section>

      {{-- Documents --}}
      <section id="documents" class="card settings-section mb-4">
        <div class="card-header"><i class="bi bi-file-earmark-text me-2"></i>Documents</div>
        <div class="card-body">
          <p class="small text-body-secondary">Numbers continue from your highest existing number; the starting number is used only when it's higher.</p>
          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label for="invoice_first_number" class="form-label small fw-semibold">First invoice number</label>
              <input type="number" id="invoice_first_number" name="invoice[first_number]" class="form-control" value="{{ $value('invoice.first_number') }}" min="1" required>
            </div>
            <div class="col-md-4">
              <label for="quotation_first_number" class="form-label small fw-semibold">First quotation number</label>
              <input type="number" id="quotation_first_number" name="quotation[first_number]" class="form-control" value="{{ $value('quotation.first_number') }}" min="1" required>
            </div>
            <div class="col-md-4">
              <label for="job_first_number" class="form-label small fw-semibold">First job note number</label>
              <input type="number" id="job_first_number" name="job[first_number]" class="form-control" value="{{ $value('job.first_number') }}" min="1" required>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="invoice_default_terms" class="form-label small fw-semibold">Invoice terms & conditions</label>
              <textarea id="invoice_default_terms" name="invoice[default_terms]" class="form-control" rows="5">{{ $value('invoice.default_terms') }}</textarea>
            </div>
            <div class="col-md-6">
              <label for="quotation_default_terms" class="form-label small fw-semibold">Quotation terms & conditions</label>
              <textarea id="quotation_default_terms" name="quotation[default_terms]" class="form-control" rows="5">{{ $value('quotation.default_terms') }}</textarea>
            </div>
            <div class="col-md-6">
              <label for="job_default_terms" class="form-label small fw-semibold">Job note terms</label>
              <textarea id="job_default_terms" name="job[default_terms]" class="form-control" rows="5">{{ $value('job.default_terms') }}</textarea>
            </div>
            <div class="col-md-6">
              <label for="invoice_default_note" class="form-label small fw-semibold">Default payment note on invoices</label>
              <textarea id="invoice_default_note" name="invoice[default_note]" class="form-control" rows="5">{{ $value('invoice.default_note') }}</textarea>
            </div>
          </div>
        </div>
      </section>

      {{-- Tax --}}
      <section id="tax" class="card settings-section mb-4">
        <div class="card-header"><i class="bi bi-percent me-2"></i>Tax</div>
        <div class="card-body row g-3">
          <div class="col-12">
            <div class="form-check form-switch">
              <input type="hidden" name="tax[enabled]" value="0">
              <input class="form-check-input" type="checkbox" role="switch" id="tax_enabled" name="tax[enabled]" value="1" @checked($value('tax.enabled'))>
              <label class="form-check-label fw-semibold" for="tax_enabled">Charge tax on invoices and quotations</label>
              <div class="form-text mt-0">Each invoice and quotation can still be saved without tax.</div>
            </div>
          </div>
          <div class="col-md-4 tax-field">
            <label for="tax_name" class="form-label small fw-semibold">Tax name</label>
            <input type="text" id="tax_name" name="tax[name]" class="form-control" value="{{ $value('tax.name') }}" maxlength="20" placeholder="VAT">
          </div>
          <div class="col-md-4 tax-field">
            <label for="tax_rate" class="form-label small fw-semibold">Rate</label>
            <div class="input-group">
              <input type="number" id="tax_rate" name="tax[rate]" class="form-control" value="{{ $value('tax.rate') }}" step="0.01" min="0" max="100">
              <span class="input-group-text">%</span>
            </div>
          </div>
          <div class="col-md-4 tax-field">
            <label for="tax_registration_no" class="form-label small fw-semibold">Tax registration no.</label>
            <input type="text" id="tax_registration_no" name="tax[registration_no]" class="form-control" value="{{ $value('tax.registration_no') }}" placeholder="Printed on documents">
          </div>
        </div>
      </section>

      {{-- General --}}
      <section id="general" class="card settings-section mb-4">
        <div class="card-header"><i class="bi bi-sliders me-2"></i>Money & backups</div>
        <div class="card-body row g-3">
          <div class="col-md-4">
            <label for="currency_symbol" class="form-label small fw-semibold">Currency symbol</label>
            <input type="text" id="currency_symbol" name="currency_symbol" class="form-control" value="{{ $value('currency_symbol') }}" maxlength="10" required>
          </div>
          <div class="col-md-4">
            <label for="credit_days" class="form-label small fw-semibold">Overdue after (days)</label>
            <input type="number" id="credit_days" name="credit_days" class="form-control" value="{{ $value('credit_days') }}" min="1" max="365" required>
            <div class="form-text">Unpaid invoices older than this count as overdue.</div>
          </div>
          <div class="col-md-4">
            <label for="backup_keep" class="form-label small fw-semibold">Backups to keep</label>
            <input type="number" id="backup_keep" name="backup[keep]" class="form-control" value="{{ $value('backup.keep') }}" min="1" max="365" required>
            <div class="form-text">Older backups are deleted automatically.</div>
          </div>
        </div>
      </section>

      {{-- Sign-in --}}
      <section id="sign-in" class="card settings-section mb-4">
        <div class="card-header"><i class="bi bi-shield-lock me-2"></i>Sign-in</div>
        <div class="card-body row g-3">
          <div class="col-12">
            <div class="form-check form-switch">
              <input type="hidden" name="otp[enabled]" value="0">
              <input class="form-check-input" type="checkbox" role="switch" id="otp_enabled" name="otp[enabled]" value="1" @checked($value('otp.enabled'))>
              <label class="form-check-label fw-semibold" for="otp_enabled">Ask for an emailed code after the password</label>
              <div class="form-text mt-0">Each user gets the code at the email address on their user account.</div>
            </div>
          </div>
          <div class="col-md-6">
            <label for="otp_email" class="form-label small fw-semibold">Codes for users without an email go to</label>
            <input type="email" id="otp_email" name="otp[email]" class="form-control" value="{{ $value('otp.email') }}" placeholder="owner@yourbusiness.com">
            <div class="form-text">Leave empty to stop users without an email from signing in while codes are on.</div>
          </div>
        </div>
      </section>

      <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Save settings</button>
      </div>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('tax_enabled');
  // Read-only (not disabled) so the saved name and rate are still sent and kept while tax is off
  const sync = () => document.querySelectorAll('.tax-field input').forEach(el => {
    el.readOnly = !toggle.checked;
    el.classList.toggle('bg-body-secondary', !toggle.checked);
  });
  toggle.addEventListener('change', sync);
  sync();

  document.getElementById('logo').addEventListener('change', e => {
    const file = e.target.files[0];
    if (file) document.getElementById('logoPreview').src = URL.createObjectURL(file);
  });
});
</script>
@endpush
