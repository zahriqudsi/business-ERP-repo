{{--
    Job note document, shared by jobs.show (screen/print) and pdf.job-note (Dompdf).
    Table-based layout on purpose: Dompdf has no flexbox/grid support.
    Expects: $job, $logoSrc (image URL or data URI).
--}}
@php
    $v = fn ($value) => trim((string) $value) === '' ? '&ndash;' : nl2br(e(trim((string) $value)));
    $terms = array_filter(array_map('trim', explode("\n", (string) $job->terms_conditions)));
    $company = config('sjpos.company');
@endphp
<div class="job-doc">
    <style>
        .job-doc { font-family: "Segoe UI", "Helvetica Neue", DejaVu Sans, Arial, sans-serif; color: #1f2937; font-size: 12px; line-height: 1.45; background: #fff; }
        .job-doc table { width: 100%; border-collapse: collapse; }
        .job-doc td { vertical-align: top; }
        .job-doc .jd-logo { width: 74px; height: 74px; }
        .job-doc .jd-company { font-size: 20px; font-weight: bold; color: #0b2545; letter-spacing: 0.5px; margin: 0; }
        .job-doc .jd-tagline { font-size: 10px; color: #f29e1f; text-transform: uppercase; letter-spacing: 1.5px; font-weight: bold; margin: 2px 0 6px; }
        .job-doc .jd-contact { font-size: 10.5px; color: #6b7280; margin: 0; }
        .job-doc .jd-title { font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: #6b7280; margin: 0; text-align: right; }
        .job-doc .jd-number { font-size: 34px; font-weight: bold; color: #0b2545; margin: 0; text-align: right; line-height: 1.1; }
        .job-doc .jd-number span { color: #f29e1f; }
        .job-doc .jd-date { text-align: right; font-size: 11px; color: #6b7280; margin: 2px 0 0; }
        .job-doc .jd-rule { height: 4px; background: #0b2545; border-bottom: 3px solid #f29e1f; margin: 10px 0 12px; }
        .job-doc .jd-card { border: 1px solid #e3e8ef; border-radius: 8px; }
        .job-doc .jd-card-head { background: #f3f6fa; color: #0b2545; font-weight: bold; text-transform: uppercase; font-size: 10.5px; letter-spacing: 1.2px; padding: 6px 12px; border-bottom: 1px solid #e3e8ef; border-radius: 8px 8px 0 0; }
        .job-doc .jd-row td { padding: 5px 12px; border-bottom: 1px solid #eef1f5; }
        .job-doc .jd-row:last-child td { border-bottom: 0; }
        .job-doc .jd-label { color: #6b7280; width: 38%; font-size: 11px; }
        .job-doc .jd-value { color: #111827; font-weight: bold; }
        .job-doc .jd-strip td { background: #0b2545; color: #fff; padding: 7px 12px; }
        .job-doc .jd-strip .jd-strip-label { display: block; font-size: 9px; letter-spacing: 1.2px; text-transform: uppercase; color: #aebbd0; }
        .job-doc .jd-strip .jd-strip-value { font-size: 12.5px; font-weight: bold; }
        .job-doc .jd-terms { margin: 0; padding: 8px 12px 8px 30px; font-size: 10.5px; color: #374151; }
        .job-doc .jd-terms li { margin-bottom: 2px; }
        .job-doc .jd-sig-box { height: 76px; border-bottom: 1.5px solid #0b2545; text-align: center; }
        .job-doc .jd-sig-box img { max-height: 72px; max-width: 100%; }
        .job-doc .jd-sig-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1.2px; color: #6b7280; margin-top: 6px; }
        .job-doc .jd-sig-name { font-weight: bold; color: #0b2545; }
        .job-doc .jd-pending { display: inline-block; margin-top: 28px; color: #b45309; font-size: 11px; font-style: italic; }
        .job-doc .jd-footer { text-align: center; font-size: 10.5px; color: #6b7280; margin-top: 12px; padding-top: 8px; border-top: 1px solid #e3e8ef; }
    </style>

    <!-- Header -->
    <table>
        <tr>
            <td style="width: 84px;"><img class="jd-logo" src="{{ $logoSrc }}" alt="{{ $company['name'] }}"></td>
            <td>
                <p class="jd-company">{{ company_upper() }}</p>
                <p class="jd-tagline">{{ $company['tagline'] }}</p>
                <p class="jd-contact">{{ $company['job_phone'] ?: $company['phone'] }} &nbsp;&bull;&nbsp; {{ $company['job_email'] ?: $company['email'] }}</p>
            </td>
            <td style="width: 170px;">
                <p class="jd-title">Job Note</p>
                <p class="jd-number"><span>#</span>{{ $job->formatted_no }}</p>
                <p class="jd-date">Created {{ $job->created_at?->format('d M Y') }}</p>
            </td>
        </tr>
    </table>
    <div class="jd-rule"></div>

    <!-- Customer + Device -->
    <table>
        <tr>
            <td style="width: 49%;">
                <div class="jd-card">
                    <div class="jd-card-head">Customer</div>
                    <table>
                        <tr class="jd-row"><td class="jd-label">Name</td><td class="jd-value">{!! $v($job->customer_name) !!}</td></tr>
                        <tr class="jd-row"><td class="jd-label">Contact Person</td><td class="jd-value">{!! $v($job->contact_person) !!}</td></tr>
                        <tr class="jd-row"><td class="jd-label">Contact No.</td><td class="jd-value">{!! $v($job->contact_no) !!}</td></tr>
                        <tr class="jd-row"><td class="jd-label">Email</td><td class="jd-value">{!! $v($job->email) !!}</td></tr>
                    </table>
                </div>
            </td>
            <td style="width: 2%;"></td>
            <td style="width: 49%;">
                <div class="jd-card">
                    <div class="jd-card-head">Device</div>
                    <table>
                        <tr class="jd-row"><td class="jd-label">Item</td><td class="jd-value">{!! $v($job->item) !!}</td></tr>
                        <tr class="jd-row"><td class="jd-label">Serial No.</td><td class="jd-value">{!! $v($job->serial_no) !!}</td></tr>
                        <tr class="jd-row"><td class="jd-label">Accessories</td><td class="jd-value">{!! $v($job->accessories_received) !!}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Fault + Condition -->
    <table style="margin-top: 10px;">
        <tr>
            <td style="width: 49%;">
                <div class="jd-card">
                    <div class="jd-card-head">Reported Fault</div>
                    <div style="padding: 8px 12px; min-height: 30px;" class="jd-value">{!! $v($job->fault) !!}</div>
                </div>
            </td>
            <td style="width: 2%;"></td>
            <td style="width: 49%;">
                <div class="jd-card">
                    <div class="jd-card-head">Condition at Collection</div>
                    <div style="padding: 8px 12px; min-height: 30px;" class="jd-value">{!! $v($job->device_condition) !!}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Remarks -->
    <div class="jd-card" style="margin-top: 10px;">
        <div class="jd-card-head">Remarks</div>
        <div style="padding: 8px 12px; min-height: 30px; color: #111827;">{!! $v($job->remarks) !!}</div>
    </div>

    <!-- Collection strip -->
    <table style="margin-top: 10px;">
        <tr class="jd-strip">
            <td style="border-radius: 8px 0 0 8px;"><span class="jd-strip-label">Collected Date</span><span class="jd-strip-value">{{ $job->collected_date->format('d/m/Y') }}</span></td>
            <td><span class="jd-strip-label">Collected By</span><span class="jd-strip-value">{{ $job->collected_by }}</span></td>
            <td style="border-radius: 0 8px 8px 0;"><span class="jd-strip-label">Job No.</span><span class="jd-strip-value">{{ $job->formatted_no }}</span></td>
        </tr>
    </table>

    <!-- Terms -->
    <div class="jd-card" style="margin-top: 10px;">
        <div class="jd-card-head">Terms &amp; Conditions</div>
        <ol class="jd-terms">
            @foreach ($terms as $term)
                <li>{{ $term }}</li>
            @endforeach
        </ol>
    </div>

    <!-- Signatures -->
    <table style="margin-top: 16px; page-break-inside: avoid;">
        <tr>
            <td style="width: 48%;">
                <div class="jd-sig-box">
                    @if ($job->is_signed)
                        <img src="{{ $job->customer_signature }}" alt="Customer signature">
                    @else
                        <span class="jd-pending">Awaiting customer signature</span>
                    @endif
                </div>
                <div class="jd-sig-label">Customer Signature</div>
                @if ($job->is_signed)
                    <div><span class="jd-sig-name">{{ $job->signed_name }}</span>
                        &nbsp;<span style="color:#6b7280; font-size: 10.5px;">signed {{ $job->signed_at?->format('d/m/Y h:i A') }}</span></div>
                @endif
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%;">
                <div class="jd-sig-box"></div>
                <div class="jd-sig-label">Received By &mdash; {{ $company['name'] }}</div>
                <div class="jd-sig-name">{{ $job->collected_by }}</div>
            </td>
        </tr>
    </table>

    <div class="jd-footer">Thank you for choosing <strong style="color:#0b2545;">{{ company_upper() }}</strong>. Please keep this job note to collect your device.</div>
</div>
