<?php

use App\Services\Tenancy\TenantManager;

if (! function_exists('currency')) {
    /**
     * The currency symbol from Settings, e.g. "Rs.".
     */
    function currency(): string
    {
        return (string) config('sjpos.currency_symbol', 'Rs.');
    }
}

if (! function_exists('rs')) {
    /**
     * "Rs. 1,234.50" (with the currency symbol from Settings)
     */
    function rs(float|int|string|null $amount, int $decimals = 2): string
    {
        return currency().' '.number_format((float) $amount, $decimals);
    }
}

if (! function_exists('rs_short')) {
    /**
     * Compact amount for dashboard tiles: "Rs. 1.25M", "Rs. 12.5K", "Rs. 950".
     */
    function rs_short(float|int|string|null $amount): string
    {
        $amount = (float) $amount;

        return currency().' '.match (true) {
            $amount >= 1_000_000 => number_format($amount / 1_000_000, 2).'M',
            $amount >= 1_000 => number_format($amount / 1_000, 1).'K',
            default => number_format($amount, 0),
        };
    }
}

if (! function_exists('days_badge')) {
    /**
     * Bootstrap badge class for an invoice's age in days (Outstanding AR).
     */
    function days_badge(int $days): string
    {
        return match (true) {
            $days <= 0 => 'bg-success',
            $days <= 30 => 'bg-primary',
            $days <= 60 => 'bg-warning text-dark',
            $days <= 90 => 'bg-orange',
            default => 'bg-danger',
        };
    }
}

if (! function_exists('logo_path')) {
    /**
     * The logo file on disk. Uploaded logos ("branding/...") live in the business's own storage;
     * built-in images and logos uploaded before tenancy ("uploads/...") are under public/.
     */
    function logo_path(): string
    {
        $logo = (string) config('sjpos.company.logo');
        $path = str_starts_with($logo, 'branding/')
            ? app(TenantManager::class)->storagePath($logo)
            : public_path($logo);

        return is_file($path) ? $path : public_path('assets/img/default-logo.png');
    }
}

if (! function_exists('logo_url')) {
    /**
     * Where pages load the logo from: the logo route for uploaded logos, else the public file.
     */
    function logo_url(): string
    {
        $logo = (string) config('sjpos.company.logo');

        return str_starts_with($logo, 'branding/')
            ? route('logo', ['v' => crc32($logo)])
            : asset($logo);
    }
}

if (! function_exists('logo_data_uri')) {
    /**
     * Logo embedded as a data URI so PDFs don't depend on fetching remote URLs.
     */
    function logo_data_uri(): string
    {
        $path = logo_path();

        return 'data:'.(mime_content_type($path) ?: 'image/png').';base64,'.base64_encode((string) file_get_contents($path));
    }
}

if (! function_exists('company_upper')) {
    /**
     * The business name in capitals, as printed on job notes.
     */
    function company_upper(): string
    {
        return mb_strtoupper((string) config('sjpos.company.name'));
    }
}
