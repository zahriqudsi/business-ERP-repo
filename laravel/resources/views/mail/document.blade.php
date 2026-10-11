@php($company = config('sjpos.company'))
<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;padding:24px;color:#1f2937;line-height:1.55;">
    <div style="border-bottom:3px solid #1e3a8a;padding-bottom:10px;margin-bottom:18px;">
        <div style="font-size:20px;font-weight:bold;color:#1e3a8a;">{{ $company['name'] }}</div>
        <div style="font-size:12px;color:#6b7280;">{{ $company['tagline'] }}</div>
    </div>

    <div style="font-size:14px;">{!! nl2br(e($messageText)) !!}</div>

    <p style="font-size:13px;color:#6b7280;margin-top:20px;">
        <span style="display:inline-block;background:#f3f4f6;border-radius:4px;padding:6px 10px;">📎 {{ $fileName }}</span>
    </p>

    <div style="border-top:1px solid #e5e7eb;margin-top:24px;padding-top:12px;font-size:12px;color:#6b7280;">
        {{ $company['name'] }} · {{ $company['address'] }}<br>
        {{ $company['phone'] }} · {{ $company['email'] }}
    </div>
</div>
