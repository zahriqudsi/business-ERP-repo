<?php

/*
|--------------------------------------------------------------------------
| Business settings (defaults)
|--------------------------------------------------------------------------
|
| Neutral defaults for a fresh install. The owner fills in the real values on
| the Settings page; those are stored in the `settings` table and loaded over
| this file on every request (see App\Services\Settings).
|
*/

return [

    // The software's own name, shown under the business name in the sidebar
    'product_name' => env('PRODUCT_NAME', 'Business Manager'),

    'company' => [
        'name' => 'My Business',
        'tagline' => '',
        'address' => '',
        'address_lines' => [],
        'phone' => '',
        'email' => '',
        'website' => '',
        'job_phone' => '',
        'job_email' => '',
        'logo' => 'assets/img/default-logo.png',
    ],

    // Shown before every amount: "Rs. 1,250.00"
    'currency_symbol' => 'Rs.',

    // Login verification code emailed after a correct password.
    'otp' => [
        'enabled' => (bool) env('OTP_ENABLED', false),
        'email' => env('OTP_EMAIL', ''),
        'ttl_minutes' => 5,
        'max_attempts' => 5,
        'resend_cooldown' => 60,
    ],

    // Bank details offered on the invoice form.
    'bank' => [
        'account_name' => '',
        'bank_name' => '',
        'account_number' => '',
        'branch' => '',
    ],

    // Invoices and purchases unpaid for longer than this many days count as overdue.
    'credit_days' => 30,

    // Sales tax added on top of invoice and quotation lines.
    'tax' => [
        'enabled' => false,
        'name' => 'VAT',
        'rate' => 0,
        'registration_no' => '',
    ],

    'invoice' => [
        'first_number' => 1,
        'default_terms' => "1. Goods once sold are not returnable.\n2. Please produce this invoice for any warranty claims.",
        'default_note' => 'Thank you for your business.',
    ],

    'quotation' => [
        'first_number' => 1,
        'default_terms' => "1. Validity: This offer is valid for 7 days.\n2. Payment: 100% advance upon confirmation.",
    ],

    'job' => [
        'first_number' => 1,
        'signature_max_bytes' => 1048576,
        'default_terms' => "The device is accepted for repair on the understanding that the customer has backed up their data. We are not liable for any data loss during diagnosis or repair.\n"
            ."The customer will be informed of the issue and the estimated cost before any repair is started.\n"
            ."Repair work starts only after customer approval.\n"
            .'We are not responsible for accessories that are not listed on this job note.',
    ],

    // Database backups made from the Backups page or the daily schedule.
    'backup' => [
        'keep' => 14,
    ],

];
