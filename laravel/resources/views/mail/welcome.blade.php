<div style="font-family:Arial,sans-serif;max-width:520px;margin:auto;padding:24px;border:1px solid #eee;border-radius:8px;">
    <h2 style="color:#333;">{{ $businessName }} is ready</h2>
    <p>Your business is set up on {{ config('sjpos.product_name') }}. Sign in here:</p>
    <p style="text-align:center;margin:24px 0;">
        <a href="{{ $loginUrl }}" style="background:#4f46e5;color:#fff;padding:12px 20px;border-radius:6px;text-decoration:none;font-weight:bold;">Sign in to {{ $businessName }}</a>
    </p>
    <p>Your username is <strong>{{ $username }}</strong>. Bookmark the address: <a href="{{ $loginUrl }}">{{ $loginUrl }}</a></p>
    <p style="color:#777;font-size:13px;">Start with Settings to add your logo, address and bank details so they appear on your invoices.</p>
</div>
