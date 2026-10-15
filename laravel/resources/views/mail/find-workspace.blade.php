<div style="font-family:Arial,sans-serif;max-width:520px;margin:auto;padding:24px;border:1px solid #eee;border-radius:8px;">
    <h2 style="color:#333;">Your sign-in addresses</h2>
    <p>You asked for the addresses of the businesses you own on {{ config('sjpos.product_name') }}:</p>
    <ul>
        @foreach ($workspaces as $workspace)
            <li><strong>{{ $workspace['name'] }}</strong>: <a href="{{ $workspace['url'] }}">{{ $workspace['url'] }}</a></li>
        @endforeach
    </ul>
    <p style="color:#777;font-size:13px;">If you didn't ask for this, you can ignore this email.</p>
</div>
