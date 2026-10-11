<div style="font-family:Arial,sans-serif;max-width:480px;margin:auto;padding:24px;border:1px solid #eee;border-radius:8px;">
    <h2 style="color:#333;">Verification Code</h2>
    <p>Hi {{ $name }},</p>
    <p>Use the code below to complete your login to <strong>{{ config('sjpos.company.name') }}</strong>:</p>
    <div style="font-size:32px;font-weight:bold;letter-spacing:6px;text-align:center;background:#f5f5f5;padding:16px;border-radius:6px;margin:20px 0;">
        {{ $otp }}
    </div>
    <p style="color:#777;font-size:13px;">This code expires in {{ config('sjpos.otp.ttl_minutes') }} minutes. If you didn't request this, you can safely ignore this email.</p>
</div>
