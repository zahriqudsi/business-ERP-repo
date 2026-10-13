@php($company = config('sjpos.company'))
<table class="plain" style="margin-top: 0;">
    <tr>
        <td style="width: 70%; vertical-align: middle;">
            <img src="{{ logo_data_uri() }}" style="max-width: 150px; height: auto;">
        </td>
        <td style="width: 50%; vertical-align: top; text-align: left;">
            <h2 style="margin-top: 0;">{{ $company['name'] }}</h2>
            <p style="margin: 0;">
                {!! implode('<br>', array_map('e', $company['address_lines'])) !!}<br>
                Phone: {{ $company['phone'] }}<br>
                Email: {{ $company['email'] }}
                @if (config('sjpos.tax.registration_no'))
                    <br>{{ config('sjpos.tax.name') ?: 'Tax' }} Reg. No: {{ config('sjpos.tax.registration_no') }}
                @endif
            </p>
        </td>
    </tr>
</table>
